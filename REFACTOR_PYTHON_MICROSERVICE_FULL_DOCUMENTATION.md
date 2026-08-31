# 📋 Dokumentasi Refactor: Queue → Python FastAPI Microservice

**Tanggal:** 31 Agustus 2026  
**Status:** ✅ LANGKAH 0-2 Selesai | ⚠️ Testing menemukan Timeout Issue  
**Tujuan:** Migrasi dari queue-based processing (Laravel) ke microservice architecture (Python) untuk kompatibilitas Vercel serverless

---

## 🎯 Alasan Refactor

### Masalah yang Dihadapi

| Masalah | Dampak | Solusi |
|---------|--------|--------|
| **Vercel tidak support persistent workers** | Queue (`queue:work`) tidak bisa berjalan di serverless | Pindahkan processing ke microservice terpisah |
| **Excel parsing berat di PHP** | Memory exhaustion pada file besar | Gunakan Python (mlxtend lebih optimal untuk Apriori) |
| **Synchronous processing timeout** | Sync request timeout di PHP default 30s | HTTP async dengan timeout 60s+ |
| **Arsitektur tightly coupled** | Laravel handle parsing + Apriori dalam 1 app | Separation of concerns: routing vs processing |

### Keuntungan Arsitektur Baru

✅ **Stateless**: Python service bisa scaled independently  
✅ **Async-friendly**: HTTP request model cocok untuk serverless  
✅ **Language-specific**: Python optimal untuk scientific computing (Apriori)  
✅ **Deployment flexibility**: Python bisa di Vercel, Digital Ocean, Render, etc.  
✅ **Error isolation**: Error di Python tidak crash Laravel app

---

## 📝 LANGKAH 0: Bersihkan Sisa Queue Implementation

### Tujuan
Menghapus semua artefak queue yang sudah tidak dipakai setelah migrasi ke Python.

### File yang Dihapus

#### 1. **`app/Jobs/ProcessTransactionUpload.php`**
**Status:** ❌ Dihapus  
**Alasan:** Job ini menghandle Excel parsing secara sync dalam queue worker — semua logic sudah dipindahkan ke Python

```php
// SEBELUM: Logika parsing PHP (DIHAPUS)
public function handle()
{
    // State machine parsing
    // Batch insert
    // Apriori calculation
    // ...semua pindah ke Python sekarang
}
```

### File yang Dibuat

#### 2. **`database/migrations/2026_08_31_000002_remove_queued_from_status_enum.php`**
**Status:** ✅ Dibuat  
**Alasan:** Migration sebelumnya menambahkan `'queued'` status. Karena tidak lagi pakai queue, kita revert ke 4 status asli.

**Content:**
```php
public function up(): void
{
    // Revert status enum: 'uploaded', 'processing', 'done', 'failed'
    // Hapus 'queued'
    DB::statement("ALTER TABLE analysis_runs MODIFY status 
        ENUM('uploaded', 'processing', 'done', 'failed') NOT NULL DEFAULT 'uploaded'");
}
```

**Catatan:** Migration ini PERLU dijalankan setelah deployment untuk update database schema.

### Environment & Config

#### 3. **`.env` — QUEUE_CONNECTION**
**Perubahan:**
```diff
- QUEUE_CONNECTION=database
+ QUEUE_CONNECTION=sync
```

**Alasan:** Tidak perlu database queue connection lagi. `sync` adalah mode default (no-op, cocok untuk local dev).

#### 4. **`app/Models/AnalysisRun.php` — Column Audit**
**Status:** ✅ Dicek, tidak ada perubahan  
**Kolom yang dipertahankan:**
- `total_baris_raw` — jumlah baris raw dari file (dari Python)
- `total_baris_clean` — setelah cleansing (dari Python)
- `total_faktur_unik` — unique invoices (dari Python)
- `total_produk_unik` — unique products (dari Python)
- `total_frequent_itemsets` — hasil Apriori (untuk nanti)
- `total_association_rules` — hasil Apriori (untuk nanti)

Kolom-kolom ini akan diisi oleh Python service saat response `/parse-excel`.

---

## 🐍 LANGKAH 1: Bangun Python FastAPI Service

### Tujuan
Membuat microservice terpisah yang handle Excel parsing dan Apriori analysis.

### Struktur Folder

```
python-service/                        ← Baru, sejajar dengan dashboard_ptsriayu/
├── api/
│   └── index.py                      ← FastAPI application
├── requirements.txt                  ← Python dependencies
└── vercel.json                       ← Konfigurasi Vercel serverless
```

### File yang Dibuat

#### 1. **`python-service/requirements.txt`**

```
fastapi
pandas
openpyxl
mlxtend
python-multipart
```

**Penjelasan dependencies:**
- **fastapi** — Web framework async Python yang ringan & cepat
- **pandas** — Data manipulation (filtering, cleansing, grouping)
- **openpyxl** — Library untuk read/write Excel (.xlsx/.xls)
- **mlxtend** — Machine learning algorithms (TransactionEncoder, apriori, association_rules)
- **python-multipart** — Support untuk multipart form data (file upload)

#### 2. **`python-service/vercel.json`**

```json
{
  "rewrites": [{ "source": "/(.*)", "destination": "/api/index" }]
}
```

**Penjelasan:** Konfigurasi Vercel agar semua request di-route ke `api/index.py` (konvensi serverless function).

#### 3. **`python-service/api/index.py`** — FastAPI Application

**Size:** ~180 lines  
**Endpoints:** 3 (health check + 2 main)

##### **Endpoint 1: Health Check**
```python
@app.get("/")
def health_check():
    return {"status": "ok", "service": "apriori-service"}
```
**Tujuan:** Check apakah service running  
**Response:** JSON status

##### **Endpoint 2: Parse Excel**
```python
@app.post("/parse-excel")
async def parse_excel(
    file: UploadFile = File(...),
    periode_awal: str = Form(...),
    periode_akhir: str = Form(...),
)
```

**Flow:**
1. **Read Excel file** → openpyxl load_workbook
2. **State machine parsing** → Extract nomor_faktur, tanggal, nama_barang per baris
3. **Filter by date range** → Hanya transaksi dalam periode_awal - periode_akhir
4. **Data cleansing** → Drop NA, remove duplicates
5. **Calculate summary** → Count total_baris_raw, total_baris_clean, unique_faktur, unique_produk
6. **Return JSON** → items array + summary stats

**Input:**
```
POST /parse-excel
Content-Type: multipart/form-data

file: <binary Excel file>
periode_awal: "2026-08-01"
periode_akhir: "2026-08-31"
```

**Output Success (200):**
```json
{
  "summary": {
    "total_baris_raw": 5000,
    "total_baris_clean": 4850,
    "total_faktur_unik": 500,
    "total_produk_unik": 120,
    "baris_duplikat_dihapus": 150
  },
  "items": [
    {
      "nomor_faktur": "INV-001",
      "tanggal": "2026-08-15",
      "nama_barang": "Produk A"
    },
    ...
  ]
}
```

**Output Error (422):**
```json
{
  "detail": "Tidak ditemukan transaksi pada rentang tanggal 2026-08-01 sampai 2026-08-31..."
}
```

##### **Endpoint 3: Analyze (Apriori)**
```python
@app.post("/analyze")
async def analyze(payload: dict)
```

**Input:**
```json
{
  "items": [
    {"nomor_faktur": "INV-001", "nama_barang": "Produk A"},
    ...
  ],
  "min_support": 0.10,
  "max_len": 2,
  "min_confidence": 0.60
}
```

**Flow:**
1. Group items by nomor_faktur → create basket
2. TransactionEncoder → one-hot encoding
3. Apriori algorithm → frequent itemsets
4. Association rules → confidence-based rules
5. Return results

**Output:**
```json
{
  "total_frequent_itemsets": 45,
  "total_association_rules": 12,
  "frequent_itemsets": [
    {
      "itemset": "Produk A, Produk B",
      "length": 2,
      "support": 0.35
    },
    ...
  ],
  "association_rules": [
    {
      "antecedent": "Produk A",
      "consequent": "Produk B",
      "support": 0.30,
      "confidence": 0.85,
      "lift": 1.42
    },
    ...
  ]
}
```

### Key Logic: State Machine Parsing

```python
# Algoritma:
# 1. Loop setiap row di worksheet
# 2. Jika cell[3] == "Nomor #" → set current_faktur = cell[6]
# 3. Jika cell[3] == "Tanggal" → set current_tanggal = cell[6]
# 4. Jika ada kode_item + nama_item + qty → append ke parsed_data
# 5. State machine track faktur/tanggal per blok (Accurate Online format)
```

**Keunggulan dibanding PHP:**
- Python pandas lebih cepat untuk data manipulation
- openpyxl tidak load seluruh file ke memory (streaming-friendly)
- mlxtend sudah optimized untuk Apriori (C-accelerated)

---

## 🔧 LANGKAH 2: Refactor UploadController

### Tujuan
Menghilangkan semua parsing logic di PHP, ganti dengan HTTP call ke Python API.

### File yang Diubah

#### 1. **`app/Http/Controllers/UploadController.php`**

**Imports — SEBELUM:**
```php
use App\Jobs\ProcessTransactionUpload;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Shared\Date;
```

**Imports — SESUDAH:**
```php
use Illuminate\Support\Facades\Http;
use App\Models\TransaksiItem;
```

**Alasan:** Tidak perlu queue job, Excel facade, atau direct spreadsheet manipulation lagi.

---

**Method `store()` — SEBELUM (60+ lines):**
```php
public function store(Request $request)
{
    // 1. Validate
    // 2. Save file to storage
    // 3. Create AnalysisRun with status='uploaded'
    // 4. Dispatch ProcessTransactionUpload job with 'queued' status
    // 5. Redirect dengan pesan "File masuk antrian"
}
```

**Method `store()` — SESUDAH (80+ lines but stateless):**
```php
public function store(Request $request)
{
    // 1. Validate form input (periode, file)
    // 2. Create AnalysisRun record dengan status='uploaded'
    // 3. Update status → 'processing'
    // 4. HTTP POST ke Python API (/parse-excel)
    //    - Attach file content
    //    - Send periode_awal, periode_akhir
    // 5. Parse response JSON
    // 6. Batch insert TransaksiItem (chunks of 500)
    // 7. Update AnalysisRun dengan summary + status='done'
    // 8. Redirect dengan success message
    // [catch] → Update status='failed', return error
}
```

**Key HTTP Call:**
```php
$response = Http::timeout(60)
    ->attach('file', file_get_contents($file->getRealPath()), $originalName)
    ->post(config('services.python_api.url') . '/parse-excel', [
        'periode_awal'  => $validated['periode_awal'],
        'periode_akhir' => $validated['periode_akhir'],
    ]);
```

#### 2. **`config/services.php`** — Tambahkan Python API Config

**Ditambahkan:**
```php
'python_api' => [
    'url' => env('PYTHON_API_URL', 'http://127.0.0.1:8001'),
],
```

**Tujuan:** Centralize config, mudah switch antara local/staging/production URL.

#### 3. **`.env` dan `.env.example`** — Tambahkan Python API URL

**Ditambahkan:**
```env
PYTHON_API_URL=http://127.0.0.1:8001
```

**Development:** `http://127.0.0.1:8001` (local Python service)  
**Production (Vercel):** Gunakan deployed Python service URL

---

## 🔄 Perubahan Flow Processing

### SEBELUM (Queue-based)

```
User Upload Excel
    ↓
Laravel Controller
  ├─ Validate form
  ├─ Save file to storage/uploads
  ├─ Create AnalysisRun (status='uploaded')
  └─ Dispatch ProcessTransactionUpload job (status='queued')
    ↓
Background Worker (php artisan queue:work)
  ├─ Read file dari storage
  ├─ PHP parsing (openpyxl belum tersedia)
  ├─ State machine extract items
  ├─ Batch insert ke DB
  └─ Update AnalysisRun (status='done')
    ↓
Dashboard reload → Show results
```

**Masalah:** Worker tidak bisa running di Vercel serverless.

---

### SESUDAH (Microservice-based)

```
User Upload Excel
    ↓
Laravel Controller (HTTP Client)
  ├─ Validate form
  ├─ Create AnalysisRun (status='uploaded')
  ├─ Update status='processing'
  └─ HTTP POST file ke Python API (/parse-excel)
    ↓
Python FastAPI Service
  ├─ Parse Excel dengan openpyxl
  ├─ State machine extract items
  ├─ Cleanse data (pandas)
  ├─ Calculate summary stats
  └─ Return JSON response
    ↓
Laravel Controller (Receive Response)
  ├─ Parse JSON (items + summary)
  ├─ Batch insert TransaksiItem (chunks 500)
  ├─ Update AnalysisRun (status='done', summary)
  └─ Redirect dengan success
    ↓
Dashboard reload → Show results
```

**Keuntungan:** Stateless, scalable, Vercel-compatible.

---

## ⚠️ ISSUE YANG DITEMUKAN: Timeout Error

### Error Message
```
Symfony\Component\ErrorHandler\Error\FatalError
Maximum execution time of 60 seconds exceeded
vendor/guzzlehttp/guzzle/src/Handler/CurlFactory.php:2524
```

### Penyebab Potential

| Penyebab | Probability | Solusi |
|----------|-------------|--------|
| **Python service belum running** | 🔴 HIGH | Jalankan: `uvicorn api.index:app --reload --port 8001` |
| **PHP execution time limit terlalu pendek** | 🟡 MEDIUM | Naik dari default 30s ke 120s+ |
| **HTTP timeout terlalu pendek** | 🟡 MEDIUM | Naik dari 60s ke 180s |
| **File Excel sangat besar (> 50k baris)** | 🟡 MEDIUM | Implement streaming parser atau chunk upload |
| **Database insert lambat** | 🟠 LOW | Optimize: naik batch size 500→1000 atau index optimization |

---

## 🔧 Solusi untuk Timeout

### Quick Fix (coba satu per satu)

#### 1️⃣ **Pastikan Python Service Running**
```bash
cd python-service
pip install -r requirements.txt
uvicorn api.index:app --reload --port 8001
```

Buka browser: `http://127.0.0.1:8001/` → harus return `{"status":"ok",...}`

#### 2️⃣ **Naik PHP Execution Time**

**File: `php.ini` (Windows)**
```ini
max_execution_time = 300  ; dari 30 detik → 5 menit
```

Atau dalam code:
```php
// Tambahkan di UploadController::store()
set_time_limit(300);
```

#### 3️⃣ **Naik HTTP Timeout**

**File: `app/Http/Controllers/UploadController.php`**
```php
$response = Http::timeout(180)  // dari 60 → 180 detik
    ->attach('file', ...)
    ->post(...);
```

#### 4️⃣ **Optimize Database Insert**

**Naik batch size:**
```php
foreach (array_chunk($rows, 1000) as $chunk) {  // 500 → 1000
    TransaksiItem::insert($chunk);
}
```

#### 5️⃣ **Test dengan File Kecil Dulu**

Jangan langsung pakai file 100k baris. Test dengan:
- Excel 1k baris → harus instant
- Excel 10k baris → harus < 5 detik
- Excel 50k baris → harus < 30 detik

Jika semua oke, maka timeout di file besar adalah wajar (need optimization).

---

## 📊 Performance Characteristics

### Expected Processing Time (Development)

| File Size | Rows | Parse | Cleanse | Insert | Total |
|-----------|------|-------|---------|--------|-------|
| 1 MB | ~2k | 0.5s | 0.2s | 0.3s | ~1s |
| 5 MB | ~10k | 2s | 0.5s | 1s | ~3.5s |
| 10 MB | ~20k | 4s | 1s | 2s | ~7s |
| 50 MB | ~100k | 20s | 5s | 10s | ~35s |

**Note:** Kecepatan bergantung pada:
- CPU (development vs production)
- Network latency (local vs remote)
- Database indexing
- Concurrent requests

---

## 📝 Migration Checklist

Sebelum production deployment:

- [ ] Run migration: `php artisan migrate` (revert status enum)
- [ ] Update `.env` dengan Python API URL yang benar
- [ ] Deploy Python service ke Vercel (atau hosting lain)
- [ ] Test upload dengan file kecil di production
- [ ] Monitor error logs di production
- [ ] Set up alerting untuk timeout issues
- [ ] Consider rate limiting jika traffic tinggi

---

## 🎓 Lessons Learned

### Apa yang Berhasil

✅ **Separation of concerns** — Parsing logic terpisah dari routing logic  
✅ **Language-appropriate** — Python lebih baik untuk scientific computing  
✅ **Async-friendly** — HTTP model cocok untuk serverless  
✅ **Independent scaling** — Python service bisa di-scale tanpa touch Laravel

### Apa yang Perlu Diperhatikan

⚠️ **Network latency** — HTTP call lebih lambat dari in-process (but negligible)  
⚠️ **Timeout management** — Perlu careful tuning timeout di multiple layers  
⚠️ **Error handling** — Python error harus di-communicate dengan jelas ke Laravel  
⚠️ **Deployment coordination** — Kedua service harus berjalan simultaneous  

### Improvements untuk Fase Selanjutnya

💡 **Streaming upload** — Untuk file sangat besar (> 100 MB)  
💡 **Job queue alternative** — Redis-based async jobs untuk non-blocking response  
💡 **Caching** — Cache hasil parsing untuk duplicate uploads  
💡 **Rate limiting** — Prevent abuse dari upload besar  
💡 **Monitoring** — Add logging/metrics untuk track performance  

---

## 📂 File Summary

### Deleted (1)
| File | Reason |
|------|--------|
| `app/Jobs/ProcessTransactionUpload.php` | Logic dipindahkan ke Python |

### Created (4)
| File | Purpose |
|------|---------|
| `python-service/api/index.py` | FastAPI application dengan endpoints parsing & Apriori |
| `python-service/requirements.txt` | Python dependencies |
| `python-service/vercel.json` | Vercel serverless config |
| `database/migrations/2026_08_31_000002_remove_queued_from_status_enum.php` | Revert status enum |

### Modified (4)
| File | Changes |
|------|---------|
| `app/Http/Controllers/UploadController.php` | Remove queue logic, add HTTP client to Python API |
| `config/services.php` | Add python_api configuration |
| `.env` | Add PYTHON_API_URL, change QUEUE_CONNECTION to sync |
| `.env.example` | Add PYTHON_API_URL template |

### Unchanged (Many)
- `resources/views/dashboard.blade.php` — Status flow masih sama
- `resources/views/layouts/navigation.blade.php` — UI unchanged
- `app/Models/AnalysisRun.php` — Columns preserved
- Database schema (kecuali migration baru)

---

## 🚀 Next Steps

1. **Verify Python service running** — Check `/` endpoint
2. **Test with small file** — Upload 1k baris Excel
3. **Debug timeout** — Follow Quick Fix steps di atas
4. **Test with production file** — Upload actual customer Excel
5. **Deploy to Vercel** — When ready for production

**Estimated time to resolve:** 30 min - 2 hours depending on root cause of timeout.

---

**Generated:** 31 Agustus 2026  
**Project:** PT Sriayu Citra Mandiri Dashboard  
**Version:** Post-Refactor v1.0
