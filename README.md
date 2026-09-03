# Dashboard Analisis Apriori — PT Sriayu Citra Mandiri

Sistem *dashboard* berbasis web untuk menganalisis pola pembelian produk (*Market Basket
Analysis*) menggunakan algoritma **Apriori**, dikembangkan sebagai bagian dari Tugas Akhir
Program Studi Sistem Informasi. Sistem ini mengolah data transaksi penjualan PT Sriayu Citra
Mandiri (distributor makanan ringan di Palembang) untuk menemukan kombinasi produk yang sering
dibeli bersamaan, sebagai dasar strategi *bundling* dan pengelolaan persediaan.

> **Status:** Fitur inti (6 *use case*) selesai diimplementasikan dan divalidasi manual.
> *Black-Box Testing* formal, *deployment*, dan manuskrip publikasi masih dalam pengerjaan.

---

## Daftar Isi

- [Ringkasan Arsitektur](#ringkasan-arsitektur)
- [Tech Stack](#tech-stack)
- [Fitur & Use Case](#fitur--use-case)
- [Struktur Database](#struktur-database)
- [Format Data Sumber](#format-data-sumber-excel)
- [Cara Menjalankan Secara Lokal](#cara-menjalankan-secara-lokal)
- [API Layanan Python](#api-layanan-python)
- [Role & Hak Akses](#role--hak-akses)
- [Sistem Desain (UI)](#sistem-desain-ui)
- [Hasil Validasi](#hasil-validasi)
- [Roadmap / Belum Dikerjakan](#roadmap--belum-dikerjakan)

---

## Ringkasan Arsitektur

Sistem terdiri atas dua layanan terpisah yang berkomunikasi melalui HTTP:

```
┌──────────────────────────┐        HTTP (JSON)        ┌──────────────────────────────┐
│   Laravel 12 (PHP)        │  ────────────────────────▶ │   FastAPI (Python)            │
│   • Autentikasi & RBAC     │                             │   • Parsing Excel mentah       │
│   • Orkestrasi upload      │  ◀────────────────────────  │   • Algoritma Apriori          │
│   • Penyimpanan MySQL      │                             │     (pandas + mlxtend)         │
└──────────────────────────┘                             └──────────────────────────────┘
```

Pemisahan ini disengaja: logika *parsing* Excel dan algoritma Apriori telah divalidasi ketat
di Python (melalui *notebook* eksperimen) sebelum diintegrasikan. Daripada menulis ulang
logika tersebut di PHP, Laravel cukup memanggil layanan Python melalui *HTTP client*
bawaan (`Illuminate\Support\Facades\Http`).

Seluruh proses (*parsing* maupun analisis) berjalan **sinkron** di dalam siklus
*request-response* biasa — sistem ini **tidak** menggunakan *queue*/*background job*.

---

## Tech Stack

| Komponen | Teknologi |
|---|---|
| Framework backend | Laravel 12 (PHP 8.2) |
| Autentikasi | Laravel Breeze (Blade + Tailwind) |
| Basis data | MySQL |
| Impor Excel | `maatwebsite/excel` `^3.1` |
| Layanan analisis | FastAPI (Python 3.10+), `pandas`, `openpyxl`, `mlxtend` |
| Visualisasi | Chart.js |
| Styling | Tailwind CSS dengan *design token* kustom |

---

## Fitur & Use Case

| # | Use Case | Role yang Berwenang | Route |
|---|---|---|---|
| 1 | Login | Semua role | `GET/POST /login` |
| 2 | Unggah Data Transaksi | Admin Penjualan | `GET/POST /upload` |
| 3 | Atur Parameter Apriori | Admin Penjualan | `GET /analysis/{run}/parameter` |
| 4 | Proses Analisis Apriori | Admin Penjualan | `POST /analysis/{run}/process` |
| 5 | Lihat Dashboard & Hasil Analisis | Semua role | `GET /dashboard` |
| 6 | Lihat Riwayat Hasil Analisis | Direktur Utama, Admin Penjualan | `GET /riwayat` |

### Unggah Data Transaksi

1. Admin Penjualan mengisi rentang periode (`periode_awal`, `periode_akhir`) dan mengunggah
   file Excel hasil ekspor Accurate Online.
2. Laravel meneruskan file ke layanan Python (`POST /parse-excel`) beserta periode yang dipilih.
3. Python melakukan *parsing*, penyaringan periode (*Data Selection*), dan pembersihan data
   (*Data Cleansing* — nilai kosong, *string* kosong, duplikat).
4. Hasil bersih dikembalikan sebagai JSON, disimpan Laravel ke tabel `transaksi_items`.
5. Pengguna diarahkan otomatis ke halaman **Atur Parameter**.

### Atur Parameter & Proses Analisis Apriori

1. Admin Penjualan menentukan `min_support`, `max_len`, `min_confidence` (nilai *default*:
   0.10 / 2 / 0.60).
2. Laravel mengirim seluruh data transaksi beserta parameter ke layanan Python (`POST /analyze`).
3. Python menjalankan algoritma Apriori (`mlxtend`), mengembalikan *frequent itemsets* dan
   *association rules*.
4. Hasil analisis **sebelumnya** (jika ada, dari parameter lama) dihapus terlebih dahulu
   sebelum hasil baru disimpan — mendukung *re-run* analisis dengan parameter berbeda tanpa
   data menumpuk.

### Dashboard & Interpretasi Otomatis

- Menampilkan ringkasan statistik, grafik 10 *association rules* dengan *lift* tertinggi
  (Chart.js), dan tabel data lengkap.
- Dilengkapi **interpretasi dan rekomendasi otomatis** dalam bentuk narasi untuk 10 aturan
  teratas, dengan tingkat rekomendasi berbeda berdasarkan kekuatan nilai *lift*.
- Tersedia filter periode (dropdown) untuk melihat hasil analisis spesifik atau gabungan
  seluruh periode.
- Data yang ditampilkan bersifat *system-wide* (bukan per-*user*) — seluruh *role* melihat
  data yang sama, hanya tombol aksi yang berbeda sesuai kewenangan.

### Riwayat Hasil Analisis

- Menampilkan seluruh riwayat analisis (termasuk yang gagal/belum diproses) dalam tabel
  berpaginasi.
- Tombol aksi menyesuaikan status tiap baris dan *role* pengguna yang login.
- Tombol "Lihat Detail" memanfaatkan ulang halaman Dashboard (bukan halaman terpisah).

---

## Struktur Database

```
users
└── role: enum('direktur_utama', 'admin_penjualan', 'admin_gudang')

analysis_runs                      (1 baris = 1 sesi upload + analisis)
├── user_id                        → FK users
├── nama_file_upload
├── periode_awal, periode_akhir
├── min_support, max_len, min_confidence
├── total_baris_raw, total_baris_clean
├── total_faktur_unik, total_produk_unik
├── total_frequent_itemsets, total_association_rules   (NULL sebelum Apriori dijalankan)
└── status: enum('uploaded', 'processing', 'done', 'failed')

transaksi_items
├── analysis_run_id                → FK analysis_runs
└── nomor_faktur, tanggal, nama_barang

frequent_itemsets
├── analysis_run_id                → FK analysis_runs
└── itemset (text), length, support

association_rules
├── analysis_run_id                → FK analysis_runs
└── antecedent, consequent, support, confidence, lift
```

> **Catatan desain:** kolom `status` pada `analysis_runs` merepresentasikan dua tahap
> (selesai unggah **atau** selesai analisis). Pembeda: kolom `total_frequent_itemsets`
> bernilai `NULL` jika Apriori belum pernah dijalankan pada *run* tersebut.

> Tabel `transaksi_items` sengaja tidak menyimpan kuantitas maupun harga — *Market Basket
> Analysis* hanya membutuhkan informasi keberadaan produk dalam suatu transaksi.

---

## Format Data Sumber (Excel)

File yang diunggah adalah hasil ekspor laporan **Accurate Online**, berbentuk blok berulang
per faktur — **bukan** tabel transaksi yang rapi. Setiap blok terdiri atas:

1. Baris label `Nomor #` (kolom D) dengan nilainya di kolom G
2. Baris label `Tanggal` (kolom D) dengan nilainya di kolom G
3. Baris-baris item: Kode Barang (kolom C), Nama Barang (kolom H), Kuantitas (kolom L)
4. Baris subtotal (Kode & Nama Barang kosong — dilewati otomatis)

Parsing dilakukan dengan pendekatan **state machine**: membaca baris demi baris sambil
menyimpan nomor faktur dan tanggal yang sedang aktif, lalu memvalidasi setiap baris item
berdasarkan kelengkapan data (termasuk memastikan kolom Kuantitas benar-benar numerik, untuk
membedakan baris item asli dari baris judul kolom tabel).

---

## Cara Menjalankan Secara Lokal

### Prasyarat

- PHP 8.2+, Composer 2.x
- Node.js 18+ & NPM
- Python 3.10+, pip
- MySQL 8.0+ (lokal maupun *cloud*, misalnya Clever Cloud)

### 1. Clone repository

```bash
git clone <url-repository-ini>
cd dashboard-apriori-sriayu
```

### 2. Setup Laravel

```bash
composer install
cp .env.example .env
php artisan key:generate
npm install && npm run build
```

Edit `.env`, isi kredensial database:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=nama_database
DB_USERNAME=root
DB_PASSWORD=

PYTHON_API_URL=http://127.0.0.1:8001
```

Jalankan migrasi & seeder (membuat 3 akun contoh, satu per *role*):

```bash
php artisan migrate
php artisan db:seed
```

### 3. Setup layanan Python

```bash
cd python-service
python -m venv venv
venv\Scripts\activate        # Windows
# source venv/bin/activate   # macOS/Linux

pip install -r requirements.txt
```

### 4. Jalankan kedua layanan (dua terminal terpisah, harus menyala bersamaan)

```bash
# Terminal 1 — dari root project Laravel
php artisan serve

# Terminal 2 — dari folder python-service
uvicorn api.index:app --reload --port 8001
```

Akses aplikasi di `http://127.0.0.1:8000`.

### Akun contoh (dari seeder)

| Role | Email | Password |
|---|---|---|
| Direktur Utama | `direktur_utama@sriayu.test` | `password` |
| Admin Penjualan | `admin_penjualan@sriayu.test` | `password` |
| Admin Gudang | `admin_gudang@sriayu.test` | `password` |

---

## API Layanan Python

Dokumentasi interaktif otomatis tersedia di `http://127.0.0.1:8001/docs` (Swagger UI) saat
layanan berjalan.

### `POST /parse-excel`

Membaca file Excel mentah, melakukan penyaringan periode dan pembersihan data.

**Request:** `multipart/form-data` — `file` (Excel), `periode_awal`, `periode_akhir` (format `YYYY-MM-DD`)

**Response:**
```json
{
  "summary": {
    "total_baris_raw": 2640,
    "total_baris_clean": 2616,
    "total_faktur_unik": 364,
    "total_produk_unik": 94,
    "baris_duplikat_dihapus": 24
  },
  "items": [
    { "nomor_faktur": "SI.2025.04.00003", "tanggal": "2025-04-14", "nama_barang": "..." }
  ]
}
```

### `POST /analyze`

Menjalankan algoritma Apriori terhadap data transaksi.

**Request (JSON):**
```json
{
  "items": [{ "nomor_faktur": "...", "nama_barang": "..." }],
  "min_support": 0.10,
  "max_len": 2,
  "min_confidence": 0.60
}
```

**Response:**
```json
{
  "total_frequent_itemsets": 77,
  "total_association_rules": 51,
  "frequent_itemsets": [{ "itemset": "...", "length": 2, "support": 0.148352 }],
  "association_rules": [{ "antecedent": "...", "consequent": "...", "support": 0.148352, "confidence": 0.729730, "lift": 2.825762 }]
}
```

---

## Role & Hak Akses

Sistem menerapkan *Role-Based Access Control* melalui *middleware* kustom `CheckRole`, yang
mendukung satu atau beberapa *role* sekaligus per *route* (dipisah koma):

```php
Route::middleware(['role:admin_penjualan'])->group(...);
Route::middleware(['role:direktur_utama,admin_penjualan'])->group(...);
```

Akses yang ditolak mengembalikan **HTTP 403** — pembatasan diterapkan di tingkat *route*,
bukan sekadar disembunyikan dari antarmuka. Telah diverifikasi bahwa mengakses *endpoint*
terbatas langsung melalui URL (melewati navigasi) tetap ditolak sesuai *role*.

| Role | Kewenangan |
|---|---|
| **Direktur Utama** | Melihat Dashboard & Riwayat (tanpa aksi ubah data) |
| **Admin Penjualan** | Seluruh fitur operasional (Unggah, Atur Parameter, Proses Analisis) + melihat Dashboard & Riwayat |
| **Admin Gudang** | Melihat Dashboard saja (tidak memiliki akses ke Riwayat maupun fitur operasional) |

---

## Sistem Desain (UI)

Palet warna dan tipografi kustom diterapkan konsisten di seluruh antarmuka (dikonfigurasi di
`tailwind.config.js`):

```js
colors: {
  ink:     '#16302B',  // teks & elemen gelap
  paper:   '#F7F5F0',  // latar utama
  primary: { DEFAULT: '#2F6F62', dark: '#234F45', light: '#DCEAE6' },
  accent:  '#E2A33D',  // aksi sekunder
  link:    '#C1584A',  // representasi "kekuatan hubungan antarproduk"
  muted:   '#8B9490',
},
fontFamily: {
  display: ['Fraunces', 'serif'],          // judul halaman
  sans:    ['Inter', 'sans-serif'],         // teks umum
  mono:    ['"IBM Plex Mono"', 'monospace'], // seluruh nilai numerik (support/confidence/lift)
}
```

---

## Hasil Validasi

Pengujian dilakukan menggunakan data sampel transaksi April 2025 (364 faktur), dengan hasil
identik antara *notebook* eksperimen Python dan keluaran akhir sistem:

| Metrik | Nilai |
|---|---|
| Baris transaksi setelah pembersihan | 2.616 (dari 2.640, 24 duplikat dihapus) |
| Faktur unik | 364 |
| Produk unik | 94 |
| *Frequent itemsets* (support ≥ 10%, maks. 2 produk) | 77 |
| *Association rules* (confidence ≥ 60%) | 51 |
| Rentang *lift* | 1,26 – 2,83 (seluruhnya menunjukkan hubungan positif) |

Konsistensi hasil re-*run* analisis dengan parameter berbeda juga telah diverifikasi (mis.
`min_support` 0.10 → 0.05 menghasilkan 179 *itemset* baru tanpa sisa data dari parameter
sebelumnya).

---

## Roadmap / Belum Dikerjakan

- [ ] *Black-Box Testing* formal (tabel skenario-input-output-hasil terstruktur)
- [ ] *Deployment* ke lingkungan produksi
- [ ] Penulisan manuskrip publikasi jurnal

---

## Lisensi

Proyek ini dikembangkan untuk keperluan akademik (Tugas Akhir Program Studi Sistem Informasi).
