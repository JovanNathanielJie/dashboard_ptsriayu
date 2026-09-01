# 📋 Ringkasan Fase 3 — Atur Parameter & Proses Analisis Apriori

**Tanggal Completion:** 1 September 2026  
**Status:** ✅ FASE 3 SELESAI  
**Target:** Membangun alur parameter input Apriori dan proses analisis berdasarkan parameter tersebut

---

## 📁 File yang Dibuat

### 1. **AnalysisController** (NEW)
**Path:** `app/Http/Controllers/AnalysisController.php`  
**Size:** ~140 lines  
**Purpose:** Handle dua endpoint untuk parameter Apriori dan proses analisis

**Methods:**
- `parameter(AnalysisRun $run)` — GET /analysis/{run}/parameter
  - Tampilkan form atur parameter Apriori
  - Gunakan route model binding otomatis Laravel
  
- `process(Request $request, AnalysisRun $run)` — POST /analysis/{run}/process
  - Validasi input: min_support, max_len, min_confidence
  - Update AnalysisRun dengan parameter
  - Ambil transaksi items dari database
  - HTTP POST ke Python API `/analyze`
  - Hapus hasil lama (frequentItemsets + associationRules)
  - Insert hasil baru dengan chunking 500
  - Update AnalysisRun status='done' + summary
  - Redirect ke dashboard dengan flash message

### 2. **View: analysis/parameter.blade.php** (NEW)
**Path:** `resources/views/analysis/parameter.blade.php`  
**Size:** ~250 lines  
**Purpose:** Form untuk mengatur parameter Apriori sebelum menjalankan analisis

**Fitur:**
- Judul: "Atur Parameter & Jalankan Analisis"
- Summary card menampilkan: nama file, periode, total baris, faktur unik
- 3 input fields dengan penjelasan detail:
  - Min Support (0.01-1.00, default 0.10)
  - Max Len (1-5, default 2)
  - Min Confidence (0.01-1.00, default 0.60)
- Loading state dengan spinner saat submit
- Error alerts untuk validasi gagal
- Design tokens: font-display, bg-primary, bg-paper, Tailwind CSS

---

## 🔧 File yang Diubah

### 1. **routes/web.php**
**Changes:**
- ✅ Tambah import: `use App\Http\Controllers\AnalysisController;`
- ✅ Tambah 2 routes di middleware group `auth:role:admin_penjualan`:
  ```php
  Route::get('/analysis/{run}/parameter', [AnalysisController::class, 'parameter'])->name('analysis.parameter');
  Route::post('/analysis/{run}/process', [AnalysisController::class, 'process'])->name('analysis.process');
  ```

### 2. **app/Http/Controllers/UploadController.php**
**Changes:**
- ✅ Update redirect setelah upload sukses:
  - Dari: `redirect()->route('upload.create')`
  - Ke: `redirect()->route('analysis.parameter', $analysisRun)`
- ✅ Update success message untuk menginformasikan user bahwa langkah berikutnya adalah atur parameter

---

## 🔄 Alur Workflow Lengkap (Fase 1-3)

```
┌─────────────────────────┐
│  1. Upload Excel File   │ (Fase 2 - UploadController)
└────────────┬────────────┘
             │
             ├─ Validate file + periode
             ├─ HTTP POST to Python /parse-excel
             ├─ Insert transaksi_items (batch 500)
             ├─ Update AnalysisRun (summary data)
             │
             ↓
┌─────────────────────────────────┐
│  2. Atur Parameter Apriori      │ (Fase 3 - AnalysisController::parameter)
│     - Min Support               │
│     - Max Len                   │
│     - Min Confidence            │
└────────────┬────────────────────┘
             │
             │ (Form submit)
             ↓
┌─────────────────────────────────────┐
│  3. Proses Analisis Apriori         │ (Fase 3 - AnalysisController::process)
│     - HTTP POST to Python /analyze  │
│     - Insert frequent_itemsets      │
│     - Insert association_rules      │
│     - Update AnalysisRun status     │
└────────────┬────────────────────────┘
             │
             ↓
       ✅ SELESAI
       Redirect ke Dashboard
       Flash: "Ditemukan X frequent itemsets & Y rules"
```

---

## 📊 Data Flow: AnalysisController::process()

```
1. Validate Input
   ├─ min_support: numeric, 0.01-1
   ├─ max_len: integer, 1-5
   └─ min_confidence: numeric, 0.01-1

2. Update AnalysisRun
   └─ Set status='processing' + parameter values

3. Fetch TransaksiItems
   ├─ Query: nomor_faktur, nama_barang
   ├─ Convert to array
   └─ Error if empty: "Tidak ada data transaksi..."

4. HTTP POST /analyze
   ├─ Body: { items[], min_support, max_len, min_confidence }
   ├─ Timeout: 120s (Apriori bisa butuh lebih lama)
   └─ Error check: if failed, throw ValidationException

5. Delete Old Results
   ├─ $run->frequentItemsets()->delete()
   └─ $run->associationRules()->delete()

6. Insert Frequent Itemsets
   ├─ Loop result['frequent_itemsets']
   ├─ Build rows array with analysis_run_id
   └─ Batch insert (chunks 500)

7. Insert Association Rules
   ├─ Loop result['association_rules']
   ├─ Build rows array with analysis_run_id
   └─ Batch insert (chunks 500)

8. Update AnalysisRun
   ├─ total_frequent_itemsets
   ├─ total_association_rules
   └─ status='done'

9. Redirect & Flash Message
   └─ route('dashboard') + success message
```

---

## 🔐 Status Tracking Logic

**Kolom `status` pada `analysis_runs` sekarang support DUA tahap:**

| Tahap | Status | `total_frequent_itemsets` | Meaning |
|-------|--------|---------------------------|---------|
| **Upload** | 'done' | NULL | File sudah di-parse, transaksi tersimpan, belum Apriori |
| **Apriori** | 'done' | INT (>0) | Analisis Apriori sudah selesai |

**Cara membedakan:** Cek apakah `total_frequent_itemsets` NULL atau sudah ada nilai.

**Future use:** Di fase-fase berikutnya (Riwayat, Dashboard detail), bisa gunakan logic ini untuk menampilkan state yang berbeda.

---

## ✅ Checklist Pre-Testing

- [x] AnalysisController.php dibuat dengan validasi lengkap
- [x] Routes ditambahkan di web.php
- [x] View parameter.blade.php dibuat dengan design token yang sesuai
- [x] UploadController redirect diubah ke analysis.parameter
- [x] Python API /analyze endpoint sudah siap (dari Fase 2)
- [x] Models FrequentItemset & AssociationRule sudah ada
- [x] HTTP client konfigurasi dengan timeout 120s
- [x] Error handling untuk validasi dan Throwable

---

## 🧪 Manual Testing Steps (User)

**Prerequisites:**
1. ✅ Laravel dev server berjalan: `php artisan serve`
2. ✅ Python FastAPI service berjalan: `uvicorn api.index:app --reload --port 8001`
3. ✅ Database sudah berisi migration lengkap
4. ✅ Admin Penjualan sudah login

**Test Flow:**

1. **Upload File Excel**
   - Buka: http://localhost:8000/upload
   - Pilih file Excel format Accurate Online
   - Isi periode_awal, periode_akhir
   - Click "Upload"
   - ✅ Expect: Redirect ke `/analysis/{run}/parameter` dengan success flash

2. **Atur Parameter & Analisis**
   - Page sudah terbuka setelah upload
   - Lihat summary data (nama file, periode, jumlah baris)
   - Input parameter (atau pakai default):
     - Min Support: 0.10
     - Max Len: 2
     - Min Confidence: 0.60
   - Click "Jalankan Analisis"
   - ✅ Expect: Loading spinner muncul
   - ✅ Expect: Redirect ke dashboard dengan flash message
   - ✅ Expect: Dashboard menampilkan "Pola pembelian terdeteksi: X aturan dari Y itemset"

3. **Cek Database**
   - Buka database, table `frequent_itemsets`:
     - ✅ Row entries dengan analysis_run_id, itemset, length, support
   - Cek table `association_rules`:
     - ✅ Row entries dengan analysis_run_id, antecedent, consequent, support, confidence, lift
   - Cek `analysis_runs`:
     - ✅ total_frequent_itemsets & total_association_rules terisi

---

## 🎓 Noteworthy Implementation Details

1. **Route Model Binding:** `{run}` di route otomatis resolve ke AnalysisRun instance via Laravel's implicit binding
2. **Parameter Casting:** AnalysisRun model casting `min_support` & `min_confidence` ke decimal:4 untuk presisi
3. **Chunking Pattern:** Sama seperti Fase 2 (batch 500), untuk menghindari insert statement terlalu besar
4. **Error Isolation:** ValidationException untuk input validation, Throwable umum untuk runtime errors
5. **Cleanup Logic:** Delete old results sebelum insert baru, mencegah data lama menumpuk
6. **Status Invariant:** Status='done' berlaku untuk KEDUA tahap (upload + Apriori), distinguishable via NULL check pada total_frequent_itemsets

---

## 📝 Status Enum Clarity (IMPORTANT)

**Current `status` enum di database:** `'uploaded', 'processing', 'done', 'failed'`

**DO NOT:** Menambah value enum baru seperti 'analyzed' atau 'apriori_done'

**Instead:** Gunakan `total_frequent_itemsets` NULL/NOT NULL untuk membedakan tahap

**Why:** Lebih sederhana, menghindari migration tambahan, sudah built-in dengan model casting

---

## 🚀 Ready for Fase 4

Fase 3 siap untuk:
- ✅ Manual testing upload → parameter → analisis
- ✅ Integrasi ke Fase 4 (Hasil Analisis & Visualization)
- ✅ Fase 4 bisa extend dashboard untuk menampilkan frequent itemsets & rules dalam bentuk visual

---

**Next Action:** Tunggu user melakukan manual testing untuk memastikan flow lengkap berjalan, kemudian proceed ke Fase 4.
