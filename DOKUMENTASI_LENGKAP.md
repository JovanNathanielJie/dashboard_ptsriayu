# 📋 DOKUMENTASI LENGKAP - DASHBOARD ANALISIS ASOSIASI PT SRI AYU

**Versi**: 1.0  
**Tanggal**: 2 September 2026  
**Status**: Production Ready (Fase 5)

---

## 📑 DAFTAR ISI

1. [Overview Aplikasi](#overview-aplikasi)
2. [Teknologi & Dependency](#teknologi--dependency)
3. [Struktur Proyek](#struktur-proyek)
4. [Database Schema](#database-schema)
5. [Autentikasi & Role-Based Access Control](#autentikasi--role-based-access-control)
6. [Fitur-Fitur Utama](#fitur-fitur-utama)
7. [Alur Kerja Aplikasi](#alur-kerja-aplikasi)
8. [Model & Relationships](#model--relationships)
9. [Controller & Business Logic](#controller--business-logic)
10. [View & Template](#view--template)
11. [Routing](#routing)
12. [Queue Processing](#queue-processing)
13. [Interpretasi & Rekomendasi Logic](#interpretasi--rekomendasi-logic)
14. [API & Response Format](#api--response-format)
15. [Error Handling & Validation](#error-handling--validation)
16. [Deployment & Configuration](#deployment--configuration)

---

## 1. OVERVIEW APLIKASI

### 1.1 Deskripsi Umum

**Dashboard Analisis Asosiasi PT Sri Ayu** adalah aplikasi web berbasis Laravel 12 yang dirancang untuk menganalisis pola pembelian pelanggan menggunakan algoritma Apriori (Market Basket Analysis). Aplikasi ini memungkinkan:

- **Admin Penjualan**: Upload data transaksi Excel, mengatur parameter analisis, menjalankan proses Apriori
- **Direktur Utama**: Melihat hasil analisis, interpretasi, dan rekomendasi strategis
- **Admin Gudang**: Melihat dashboard overview saja

### 1.2 Tujuan Bisnis

Mengidentifikasi kombinasi produk yang sering dibeli bersama untuk:
- Strategi bundling (paket produk)
- Penempatan produk di toko
- Strategi cross-selling dan promosi
- Optimalisasi inventory

### 1.3 User Personas & Roles

| Role | Deskripsi | Akses |
|------|-----------|-------|
| **admin_penjualan** | Tim penjualan yang mengelola upload data dan analisis | Upload, Parameter, Process, Dashboard, Riwayat, Interpretasi |
| **direktur_utama** | Direktur yang melihat insights dan membuat keputusan | Dashboard, Riwayat, Interpretasi (Read-only) |
| **admin_gudang** | Admin gudang yang melihat info umum | Dashboard Overview saja |

---

## 2. TEKNOLOGI & DEPENDENCY

### 2.1 Tech Stack

```
Frontend:
- Blade Template Engine (Laravel)
- Tailwind CSS (Styling)
- Alpine.js (Interactivity)
- Chart.js (Data Visualization)

Backend:
- Laravel 12 (PHP Framework)
- MySQL/MariaDB (Database)
- Laravel Queue (Background Processing)
- Maatwebsite Excel (Import/Export)

Other:
- Python 3.x + Apriori (Background service)
- Composer (PHP Package Manager)
- NPM (Node Package Manager)
```

### 2.2 Key Dependencies (composer.json)

```php
{
    "require": {
        "laravel/framework": "^12.0",
        "laravel/breeze": "^2.0",
        "maatwebsite/excel": "^3.1",
        "phpoffice/phpspreadsheet": "^1.x"
    }
}
```

### 2.3 Environment Configuration

```env
APP_NAME=Dashboard
APP_ENV=production
APP_DEBUG=false
APP_URL=https://dashboard.ptsr.id (Production)

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=dashboard_ptsriayu
DB_USERNAME=root
DB_PASSWORD=***

QUEUE_CONNECTION=database
MAIL_MAILER=smtp
```

---

## 3. STRUKTUR PROYEK

### 3.1 Directory Structure

```
dashboard_ptsriayu/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── DashboardController.php      (Main dashboard logic + interpretasi)
│   │   │   ├── UploadController.php         (File upload & AnalysisRun creation)
│   │   │   ├── AnalysisController.php       (Parameter & Process management)
│   │   │   ├── RiwayatController.php        (History view)
│   │   │   └── ProfileController.php        (User profile)
│   │   ├── Middleware/
│   │   │   ├── CheckRole.php                (Role-based access control)
│   │   │   ├── Authenticate.php             (Login required)
│   │   │   └── VerifyCsrfToken.php          (CSRF protection)
│   │   └── Requests/
│   │       └── StoreUploadRequest.php       (Validation rules)
│   ├── Models/
│   │   ├── User.php                         (User model + role methods)
│   │   ├── AnalysisRun.php                  (Analysis run metadata)
│   │   ├── TransaksiItem.php                (Transaction item details)
│   │   ├── FrequentItemset.php              (Frequent itemsets)
│   │   └── AssociationRule.php              (Association rules)
│   ├── Jobs/
│   │   └── ProcessTransactionUpload.php     (Queue job for file processing)
│   └── Providers/
│       └── AppServiceProvider.php           (Service providers)
│
├── routes/
│   ├── web.php                              (Web routes)
│   └── auth.php                             (Authentication routes)
│
├── resources/
│   ├── views/
│   │   ├── dashboard.blade.php              (Main dashboard template)
│   │   ├── upload/
│   │   │   └── create.blade.php             (Upload form)
│   │   ├── analysis/
│   │   │   └── parameter.blade.php          (Parameter form)
│   │   ├── riwayat/
│   │   │   └── index.blade.php              (History table)
│   │   ├── layouts/
│   │   │   ├── app.blade.php                (Main layout wrapper)
│   │   │   └── navigation.blade.php         (Sidebar + topbar)
│   │   └── auth/                            (Login/Register pages)
│   ├── css/
│   │   └── app.css                          (Custom CSS)
│   └── js/
│       ├── app.js                           (Alpine.js + app JS)
│       └── bootstrap.js                     (Bootstrap)
│
├── database/
│   ├── migrations/
│   │   ├── 0001_01_01_000000_create_users_table.php
│   │   ├── 2026_08_30_131110_create_analysis_runs_table.php
│   │   ├── 2026_08_30_131111_create_transaksi_items_table.php
│   │   ├── 2026_08_30_131112_create_frequent_itemsets_table.php
│   │   ├── 2026_08_30_131113_create_association_rules_table.php
│   │   └── 2026_08_31_000001_alter_analysis_runs_status_enum.php
│   ├── factories/
│   │   └── UserFactory.php
│   └── seeders/
│       └── DatabaseSeeder.php
│
├── python-service/
│   ├── api/
│   │   └── index.py                         (Apriori algorithm)
│   └── requirements.txt                     (Python dependencies)
│
├── config/
│   ├── app.php                              (App config)
│   ├── database.php                         (DB config)
│   ├── queue.php                            (Queue config)
│   └── filesystems.php                      (File storage config)
│
├── storage/
│   ├── app/uploads/                         (Uploaded Excel files)
│   ├── logs/                                (Application logs)
│   └── framework/                           (Cache)
│
├── public/
│   ├── index.php                            (Entry point)
│   └── build/                               (Compiled assets)
│
├── bootstrap/
│   ├── app.php                              (Bootstrap app container)
│   └── providers.php                        (Register service providers)
│
├── tests/                                   (Unit/Feature tests)
│
├── .env                                     (Environment variables)
├── .env.example                             (Example env)
├── composer.json                            (PHP dependencies)
├── package.json                             (JS dependencies)
├── vite.config.js                           (Build tool config)
├── tailwind.config.js                       (Tailwind CSS config)
├── phpunit.xml                              (Testing config)
└── artisan                                  (Laravel CLI)
```

---

## 4. DATABASE SCHEMA

### 4.1 Users Table

```sql
CREATE TABLE users (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin_penjualan', 'direktur_utama', 'admin_gudang') NOT NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

**Role Mapping**:
- `admin_penjualan`: Sales team (full access)
- `direktur_utama`: Director (read-only analysis)
- `admin_gudang`: Warehouse staff (dashboard only)

### 4.2 Analysis Runs Table

```sql
CREATE TABLE analysis_runs (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    nama_file_upload VARCHAR(255) NOT NULL,
    periode_awal DATE NOT NULL,
    periode_akhir DATE NOT NULL,
    
    -- File Statistics
    total_baris_raw INT,
    total_baris_clean INT,
    
    -- Analysis Results
    total_faktur_unik INT,
    total_produk_unik INT,
    total_frequent_itemsets INT,
    total_association_rules INT,
    
    -- Parameters
    min_support DECIMAL(5,4),
    max_len INT,
    min_confidence DECIMAL(5,4),
    
    -- Status Tracking
    status ENUM('queued', 'processing', 'done', 'failed') DEFAULT 'queued',
    
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id)
);
```

**Status Flow**:
```
queued → processing → done (or failed)
```

### 4.3 Transaksi Items Table

```sql
CREATE TABLE transaksi_items (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    analysis_run_id BIGINT UNSIGNED NOT NULL,
    faktur_no VARCHAR(50),
    tgl_transaksi DATE,
    item_name VARCHAR(255),
    qty INT,
    harga_satuan DECIMAL(10,2),
    total_harga DECIMAL(12,2),
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    
    FOREIGN KEY (analysis_run_id) REFERENCES analysis_runs(id)
);
```

### 4.4 Frequent Itemsets Table

```sql
CREATE TABLE frequent_itemsets (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    analysis_run_id BIGINT UNSIGNED NOT NULL,
    itemset VARCHAR(500),          -- JSON or comma-separated product names
    support DECIMAL(10,6),         -- Decimal 0-1 (multiply by 100 for percentage)
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    
    FOREIGN KEY (analysis_run_id) REFERENCES analysis_runs(id)
);
```

### 4.5 Association Rules Table

```sql
CREATE TABLE association_rules (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    analysis_run_id BIGINT UNSIGNED NOT NULL,
    antecedent VARCHAR(500),       -- Items on left side (A)
    consequent VARCHAR(500),       -- Items on right side (B)
    support DECIMAL(10,6),         -- P(A ∪ B)
    confidence DECIMAL(10,6),      -- P(B|A)
    lift DECIMAL(10,6),            -- confidence / P(B)
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    
    FOREIGN KEY (analysis_run_id) REFERENCES analysis_runs(id)
);
```

**Note**: support, confidence, lift dipenyimpan sebagai desimal (0-1), kemudian dikalikan 100 saat ditampilkan.

### 4.6 Relationships Diagram

```
User (1)
  ↓ (has many)
  AnalysisRun (many)
    ↓ (has many)
    TransaksiItem (many)
    
    ↓ (has many)
    FrequentItemset (many)
    
    ↓ (has many)
    AssociationRule (many)
```

---

## 5. AUTENTIKASI & ROLE-BASED ACCESS CONTROL

### 5.1 Authentication Flow

```
User Input Credentials
    ↓
Laravel Auth (Breeze)
    ↓
Store session in DB (sessions table)
    ↓
Middleware: Authenticate (check session valid)
    ↓
Request allowed / Redirect to login
```

### 5.2 Middleware: CheckRole

**File**: `app/Http/Middleware/CheckRole.php`

```php
public function handle(Request $request, Closure $next, string ...$roles): Response
{
    $user = $request->user();
    
    if (! $user) {
        abort(401);  // Unauthorized
    }
    
    // Support single role: role:admin_penjualan
    // Support multiple roles: role:direktur_utama,admin_penjualan
    $allowedRoles = [];
    foreach ($roles as $roleStr) {
        $splitRoles = array_map('trim', explode(',', $roleStr));
        $allowedRoles = array_merge($allowedRoles, $splitRoles);
    }
    $allowedRoles = array_filter($allowedRoles, fn ($role) => $role !== '');
    
    if ($allowedRoles === []) {
        abort(403, 'Role tidak diizinkan untuk mengakses halaman ini.');
    }
    
    if (! in_array($user->role, $allowedRoles, true)) {
        abort(403, 'Anda tidak memiliki akses ke halaman ini.');
    }
    
    return $next($request);
}
```

**Usage dalam Routes**:

```php
// Single role
Route::get('/upload', [UploadController::class, 'create'])
    ->middleware('role:admin_penjualan');

// Multiple roles (comma-separated)
Route::get('/riwayat', [RiwayatController::class, 'index'])
    ->middleware('role:direktur_utama,admin_penjualan');
```

### 5.3 User Model Methods

**File**: `app/Models/User.php`

```php
public function isDirekturUtama(): bool
{
    return $this->role === 'direktur_utama';
}

public function isAdminPenjualan(): bool
{
    return $this->role === 'admin_penjualan';
}

public function isAdminGudang(): bool
{
    return $this->role === 'admin_gudang';
}
```

**Usage dalam Blade**:

```blade
@if (Auth::user()->isAdminPenjualan())
    <!-- Show upload button -->
@endif
```

### 5.4 Protected Routes

```php
// Protected by auth + verified middleware
Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

// Protected by admin_penjualan role
Route::middleware(['auth', 'role:admin_penjualan'])->group(function () {
    Route::get('/upload', [UploadController::class, 'create'])->name('upload.create');
    Route::post('/upload', [UploadController::class, 'store'])->name('upload.store');
    Route::get('/analysis/{run}/parameter', [AnalysisController::class, 'parameter'])->name('analysis.parameter');
    Route::post('/analysis/{run}/process', [AnalysisController::class, 'process'])->name('analysis.process');
});

// Protected by direktur_utama OR admin_penjualan
Route::get('/riwayat', [RiwayatController::class, 'index'])
    ->middleware(['auth', 'role:direktur_utama,admin_penjualan'])
    ->name('riwayat.index');
```

---

## 6. FITUR-FITUR UTAMA

### 6.1 Dashboard Overview

**URL**: `/dashboard`  
**Method**: `GET`  
**Access**: All authenticated users  
**Controller**: `DashboardController@index`

#### Fitur:

1. **Statistics Cards** (Statistik Global)
   - Total Analisis: Jumlah run yang sudah selesai
   - Transaksi Diproses: Total baris dari semua run
   - Periode Aktif: Tahun dari periode terbaru atau "Semua Periode"
   - Status Sistem: "Normal" jika ada data, "Belum ada data" jika kosong

2. **Activity Metrics** (Ringkasan Aktivitas)
   - Data Transaksi Masuk: Persentase baris clean vs raw
   - Pola Pembelian Terdeteksi: Persentase rules vs itemsets
   - Ketersediaan Data Gudang: Persentase produk unik vs faktur unik
   - SELALU menggunakan run terbaru ($actualLatestRun), tidak terpengaruh dropdown

3. **Top 10 Association Rules**
   - Tabel berisi 10 rules dengan lift tertinggi
   - Kolom: Antecedent → Consequent, Support, Confidence, Lift
   - Dropdown filter: "Semua Periode" atau pilih periode spesifik
   - Mode ALL: Mengambil rules dari SEMUA run, grouped by antecedent-consequent
   - Mode Spesifik: Mengambil rules hanya dari run terpilih

4. **Interpretasi & Rekomendasi** (Baru - Fase 6)
   - Grid 2 kolom card untuk setiap dari 10 rules
   - Setiap card: Judul rule, Paragraf interpretasi, Paragraf rekomendasi
   - Pewarnaan border kiri berdasarkan lift strength
   - Sub-judul menampilkan periode konteks (WAJIB)

5. **Menu Cepat** (Quick Links)
   - Unggah Data Transaksi (Admin Penjualan saja)
   - Dashboard Overview
   - Riwayat Hasil Analisis (Admin Penjualan + Direktur Utama)
   - Profil Pengguna

#### Parameter Periode:

```php
// Dropdown untuk select periode
$selectedRunId = $request->get('run_id', 'all');  // Default: 'all'

// Variable untuk context
$selectedRun;              // Run yang dipilih dari dropdown (null jika 'all')
$actualLatestRun;          // SELALU run paling baru (independen dropdown)
$labelPeriodeInterpretasi; // Label konteks untuk interpretasi section
```

---

### 6.2 Upload Data Transaksi

**URL**: `/upload`  
**Method**: `GET` (form), `POST` (submit)  
**Access**: admin_penjualan saja  
**Controller**: `UploadController`

#### Flow:

```
1. GET /upload
   ↓
   Display form (periode_awal, periode_akhir, file upload)
   
2. POST /upload
   ↓
   Validate input (StoreUploadRequest)
   ↓
   Store file to storage/app/uploads/
   ↓
   Create AnalysisRun record (status = 'queued')
   ↓
   Dispatch ProcessTransactionUpload job to queue
   ↓
   Redirect to /dashboard with success message
   
3. Queue Worker (Background)
   ↓
   Pick job from database queue
   ↓
   Update status to 'processing'
   ↓
   Read Excel file (Maatwebsite Excel)
   ↓
   Validate data (date range, required fields)
   ↓
   Insert to transaksi_items table (batch insert)
   ↓
   Update AnalysisRun metadata
   ↓
   Set status to 'done'
```

#### Validation Rules:

```php
class StoreUploadRequest extends FormRequest
{
    public function rules()
    {
        return [
            'periode_awal' => 'required|date|date_format:Y-m-d',
            'periode_akhir' => 'required|date|date_format:Y-m-d|after:periode_awal',
            'excel_file' => 'required|file|mimes:xlsx,xls|max:51200',  // 50MB max
        ];
    }
}
```

#### Expected Excel Format:

```
Column A: Faktur No
Column B: Tanggal Transaksi (YYYY-MM-DD)
Column C: Item Name
Column D: Qty
Column E: Harga Satuan
Column F: Total Harga
```

---

### 6.3 Atur Parameter Analisis

**URL**: `/analysis/{run}/parameter`  
**Method**: `GET` (form), `POST` (update)  
**Access**: admin_penjualan saja  
**Controller**: `AnalysisController@parameter`

#### Parameter:

```php
// Dikirim via form
min_support: float (0-1)         // Minimum support threshold
min_confidence: float (0-1)      // Minimum confidence threshold
max_len: int (2-10)              // Maximum itemset length
```

#### Default Values:

```php
$defaults = [
    'min_support' => 0.01,       // 1%
    'min_confidence' => 0.5,     // 50%
    'max_len' => 3,
];
```

---

### 6.4 Jalankan Proses Analisis

**URL**: `/analysis/{run}/process`  
**Method**: `POST`  
**Access**: admin_penjualan saja  
**Controller**: `AnalysisController@process`

#### Flow:

```
POST /analysis/{run}/process
   ↓
   Validate (run exists, user is owner or admin)
   ↓
   Call Python service (Apriori algorithm)
   ↓
   Receive: frequent_itemsets, association_rules
   ↓
   Insert to database tables
   ↓
   Update AnalysisRun:
     - total_frequent_itemsets
     - total_association_rules
   ↓
   Redirect with success message
```

#### Python Service Communication:

```python
# POST http://python-service/api/analyze
payload = {
    "transaksi_items": [...],
    "min_support": 0.01,
    "min_confidence": 0.5,
    "max_len": 3
}

response = {
    "frequent_itemsets": [
        {"itemset": ["produk A", "produk B"], "support": 0.05},
        ...
    ],
    "association_rules": [
        {
            "antecedent": "produk A",
            "consequent": "produk B",
            "support": 0.03,
            "confidence": 0.75,
            "lift": 2.5
        },
        ...
    ]
}
```

---

### 6.5 Riwayat Hasil Analisis

**URL**: `/riwayat`  
**Method**: `GET`  
**Access**: direktur_utama, admin_penjualan  
**Controller**: `RiwayatController@index`

#### Features:

```
1. Tabel dengan 9 kolom:
   - No: Nomor urut (pagination-aware)
   - Nama File: Nama file upload
   - Periode: Format "d M Y - d M Y" (e.g., "1 Apr 2025 - 30 Apr 2025")
   - Diunggah Oleh: User name (dari relasi)
   - Tanggal: Format "d M Y H:i" (created_at)
   - Status: Badge dengan warna berbeda
   - Faktur/Baris: "X faktur / Y baris" atau "-"
   - Hasil Analisis: "X itemset, Y rules" atau "Belum dianalisis"
   - Aksi: Tombol sesuai kondisi (role & status dependent)

2. Status Badge:
   - status='done' + total_frequent_itemsets terisi  → Hijau "Selesai Dianalisis"
   - status='done' + total_frequent_itemsets NULL   → Kuning "Menunggu Analisis"
   - status='processing'                            → Accent "Sedang Diproses"
   - status='failed'                                → Link Color "Gagal"
   - status='queued'                                → Slate "Antrian"

3. Kolom Aksi (Conditional):
   IF total_frequent_itemsets terisi:
     - SEMUA role: Tombol "Lihat Detail" → /dashboard?run_id={id}
     - HANYA admin_penjualan: Tombol "Jalankan Ulang" → /analysis/{id}/parameter
   ELIF status != 'failed' && total_frequent_itemsets NULL:
     - HANYA admin_penjualan: Tombol "Atur Parameter" → /analysis/{id}/parameter
     - direktur_utama: Tidak ada tombol
   ELSE (failed):
     - KEDUA role: Tidak ada tombol

4. Pagination:
   - Per page: 15 rows
   - Display links: {{ $runs->links() }}
   - Database query: AnalysisRun::with('user')->latest()->paginate(15)
```

---

### 6.6 Interpretasi & Rekomendasi (Feature Baru)

**Location**: Dashboard → Section "Interpretasi & Rekomendasi"  
**Access**: All authenticated users (read-only)  
**Controller Method**: `DashboardController@buatInterpretasi()` (private)

#### Logic:

```php
private function buatInterpretasi(array $rule): array
{
    $antecedent = $rule['antecedent'];
    $consequent = $rule['consequent'];
    $support = $rule['support'] * 100;      // Convert to percentage
    $confidence = $rule['confidence'] * 100;
    $lift = $rule['lift'];
    
    // --- INTERPRETASI ---
    // Format: Satu paragraf mengalir menjelaskan support & confidence
    $interpretasi = sprintf(
        'Kombinasi produk %s dan %s memiliki nilai support sebesar %.2f%%, 
        artinya %.2f%% dari seluruh transaksi pada periode ini mengandung kedua produk 
        tersebut secara bersamaan. Nilai confidence sebesar %.2f%% menunjukkan bahwa 
        dari transaksi yang mengandung %s, sebesar %.2f%% di antaranya juga mengandung %s.',
        $antecedent, $consequent, $support, $support,
        $confidence, $antecedent, $confidence, $consequent
    );
    
    // --- REKOMENDASI ---
    // Logika bertingkat berdasarkan nilai lift
    if ($lift > 2) {
        // KUAT - Hubungan asosiasi sangat signifikan
        $rekomendasi = sprintf(
            'Dengan nilai lift sebesar %.3f (jauh di atas 1), kombinasi ini menunjukkan 
            hubungan asosiasi yang KUAT. Sangat direkomendasikan sebagai kandidat utama 
            strategi bundling atau penempatan produk berdekatan.',
            $lift
        );
    } elseif ($lift > 1 && $lift <= 2) {
        // SEDANG - Hubungan asosiasi moderate
        $rekomendasi = sprintf(
            'Dengan nilai lift sebesar %.3f (di atas 1), kombinasi ini menunjukkan 
            hubungan asosiasi positif dengan kekuatan SEDANG. Dapat dipertimbangkan 
            sebagai kandidat strategi bundling atau promosi, meski keterkaitannya 
            tidak sekuat kombinasi lain dengan nilai lift lebih tinggi.',
            $lift
        );
    } else {
        // TIDAK SIGNIFIKAN - Lift <= 1
        $rekomendasi = sprintf(
            'Dengan nilai lift sebesar %.3f (tidak lebih besar dari 1), kombinasi ini 
            TIDAK menunjukkan hubungan asosiasi yang signifikan. Kombinasi ini TIDAK 
            direkomendasikan sebagai dasar strategi bundling.',
            $lift
        );
    }
    
    return [
        'interpretasi' => $interpretasi,
        'rekomendasi' => $rekomendasi,
    ];
}
```

#### Display Template:

```blade
<!-- Section: Interpretasi & Rekomendasi -->
<div class="mt-8">
    <h3 class="text-2xl font-bold">Interpretasi & Rekomendasi</h3>
    <p class="text-sm text-slate-600">
        Berdasarkan 10 aturan asosiasi dengan nilai lift tertinggi pada periode:
        <span class="font-semibold">{{ $labelPeriodeInterpretasi }}</span>
    </p>
    
    <!-- Grid 2 kolom card -->
    <div class="grid gap-6 md:grid-cols-2">
        @foreach ($topRules as $rule)
            <div class="rounded-xl border border-slate-200 bg-white p-6 
                        border-l-4 {{ 
                            $rule['lift'] > 2 ? 'border-l-[#C1584A]' : 
                            ($rule['lift'] > 1 ? 'border-l-[#E2A33D]' : 'border-l-[#8B9490]')
                        }}">
                
                <!-- Judul: Rule Label + Badge Lift -->
                <div class="flex justify-between mb-4">
                    <h4 class="font-mono text-sm font-semibold">
                        {{ $rule['antecedent'] }} →<br/>{{ $rule['consequent'] }}
                    </h4>
                    <span class="bg-slate-100 px-3 py-1 rounded-full text-xs font-mono">
                        Lift: {{ number_format($rule['lift'], 3) }}
                    </span>
                </div>
                
                <!-- Interpretasi paragraph -->
                <p class="text-sm text-slate-700 mb-4">
                    {{ $rule['interpretasi'] }}
                </p>
                
                <!-- Rekomendasi paragraph (dengan background styling) -->
                <div class="rounded-lg p-4 {{ 
                    $rule['lift'] > 2 ? 'bg-[#FEF5F3] text-slate-900' :
                    ($rule['lift'] > 1 ? 'bg-[#FFFBF0] text-slate-900' : 'bg-[#F5F7F6] text-slate-600')
                }}">
                    <p class="text-sm">{{ $rule['rekomendasi'] }}</p>
                </div>
            </div>
        @endforeach
    </div>
</div>
```

#### Styling by Lift Strength:

| Lift Range | Border Color | Background | Text Color |
|-----------|--------------|-----------|-----------|
| > 2 | #C1584A (Link) | #FEF5F3 | text-slate-900 |
| > 1 & ≤ 2 | #E2A33D (Accent) | #FFFBF0 | text-slate-900 |
| ≤ 1 | #8B9490 (Muted) | #F5F7F6 | text-slate-600 |

---

## 7. ALUR KERJA APLIKASI

### 7.1 User Journey - Admin Penjualan

```
┌─────────────────────────────────────────────────────────────┐
│ 1. LOGIN                                                     │
│    URL: /login                                               │
│    Input: Email, Password                                    │
│    Output: Session created, Redirect to /dashboard           │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ 2. DASHBOARD (Overview)                                      │
│    URL: GET /dashboard                                       │
│    Display:                                                  │
│    - Statistics cards (Total Analisis, Transaksi, dll)      │
│    - Activity Metrics (dari run terbaru)                     │
│    - Top 10 Association Rules table                          │
│    - Interpretasi & Rekomendasi cards                        │
│    - Menu Cepat                                              │
│    - Dropdown: Select periode atau "Semua Periode"          │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ 3. UPLOAD DATA                                               │
│    URL: GET /upload → Show form                              │
│    Form fields:                                              │
│    - Periode Awal (date picker)                              │
│    - Periode Akhir (date picker)                             │
│    - File Upload (Excel only)                                │
│    Submit: POST /upload                                      │
│    ↓                                                         │
│    Validation:                                               │
│    - Periode valid (end > start)                             │
│    - File exists & is Excel                                  │
│    ↓                                                         │
│    Create AnalysisRun:                                       │
│    - status = 'queued'                                       │
│    - Store file to storage/app/uploads/{id}.xlsx             │
│    ↓                                                         │
│    Dispatch ProcessTransactionUpload job to queue            │
│    ↓                                                         │
│    Success message: "File sedang diproses dalam antrian..."  │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ 4. QUEUE WORKER (Background)                                 │
│    php artisan queue:work database                           │
│    ↓                                                         │
│    Pick ProcessTransactionUpload job                         │
│    ↓                                                         │
│    Update AnalysisRun status = 'processing'                  │
│    ↓                                                         │
│    Read Excel file:                                          │
│    - Parse rows                                              │
│    - Validate date in range                                  │
│    - Extract: faktur_no, tgl, item_name, qty, harga, total   │
│    ↓                                                         │
│    Batch insert to transaksi_items (500 rows per batch)      │
│    ↓                                                         │
│    Update AnalysisRun:                                       │
│    - total_baris_raw: count uploaded rows                    │
│    - total_baris_clean: count valid rows                     │
│    - total_faktur_unik: distinct faktur_no                   │
│    - total_produk_unik: distinct item_name                   │
│    - status = 'done'                                         │
│    ↓                                                         │
│    Job completed successfully                                │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ 5. ATUR PARAMETER ANALISIS                                   │
│    URL: GET /analysis/{run_id}/parameter                     │
│    Display:                                                  │
│    - Form dengan pre-filled defaults:                        │
│      * min_support: 0.01 (1%)                                │
│      * min_confidence: 0.5 (50%)                             │
│      * max_len: 3                                            │
│    Submit: POST /analysis/{run_id}/parameter                 │
│    ↓                                                         │
│    Validation & Save parameters                              │
│    ↓                                                         │
│    Option: "Jalankan Analisis" button                        │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ 6. JALANKAN PROSES APRIORI                                   │
│    URL: POST /analysis/{run_id}/process                      │
│    ↓                                                         │
│    Fetch transaksi_items untuk run ini                       │
│    ↓                                                         │
│    Call Python service:                                      │
│    POST http://python-service/api/analyze                    │
│    Payload:                                                  │
│    {                                                         │
│        "transactions": [...transaksi_items...],              │
│        "min_support": 0.01,                                  │
│        "min_confidence": 0.5,                                │
│        "max_len": 3                                          │
│    }                                                         │
│    ↓                                                         │
│    Receive response:                                         │
│    {                                                         │
│        "frequent_itemsets": [...],                           │
│        "association_rules": [...]                            │
│    }                                                         │
│    ↓                                                         │
│    Insert ke DB:                                             │
│    - frequent_itemsets table                                 │
│    - association_rules table                                 │
│    ↓                                                         │
│    Update AnalysisRun:                                       │
│    - total_frequent_itemsets                                 │
│    - total_association_rules                                 │
│    ↓                                                         │
│    Success: "Analisis selesai!"                              │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ 7. LIHAT HASIL DI DASHBOARD                                  │
│    URL: GET /dashboard?run_id={run_id}                       │
│    Display:                                                  │
│    - Tabel association rules (top 10 by lift)                │
│    - Interpretasi & Rekomendasi cards                        │
│    - Activity metrics updated                                │
└─────────────────────────────────────────────────────────────┘
```

### 7.2 User Journey - Direktur Utama

```
┌─────────────────────────────────────────────────────────────┐
│ 1. LOGIN                                                     │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ 2. DASHBOARD (View-only)                                     │
│    - Lihat statistics, metrics, rules                        │
│    - Lihat interpretasi & rekomendasi                        │
│    - TIDAK bisa upload, TIDAK bisa atur parameter            │
│    - Dropdown periode: tersedia & bisa digunakan              │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ 3. RIWAYAT HASIL ANALISIS                                    │
│    URL: GET /riwayat                                         │
│    View:                                                     │
│    - Tabel semua analysis runs                               │
│    - Lihat "Lihat Detail" button untuk setiap run            │
│    - TIDAK ada tombol "Jalankan Ulang" atau "Atur Parameter" │
│    - Klik "Lihat Detail" → View dashboard untuk run itu      │
└─────────────────────────────────────────────────────────────┘
```

### 7.3 User Journey - Admin Gudang

```
┌─────────────────────────────────────────────────────────────┐
│ 1. LOGIN                                                     │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ 2. DASHBOARD (Overview Only)                                 │
│    - Lihat statistics cards                                  │
│    - Lihat activity metrics                                  │
│    - TIDAK bisa lihat association rules details              │
│    - TIDAK ada menu untuk upload, parameter, analisis        │
│    - Menu Riwayat TIDAK visible                              │
│                                                              │
│    Role restriction:                                         │
│    - Cannot access /upload (403 Forbidden)                   │
│    - Cannot access /analysis/{id}/parameter (403)            │
│    - Cannot access /riwayat (403)                            │
└─────────────────────────────────────────────────────────────┘
```

---

## 8. MODEL & RELATIONSHIPS

### 8.1 User Model

**File**: `app/Models/User.php`

```php
class User extends Authenticatable
{
    use HasFactory, Notifiable;
    
    protected $fillable = ['name', 'email', 'password', 'role'];
    
    // Relationships
    public function analysisRuns(): HasMany
    {
        return $this->hasMany(AnalysisRun::class);
    }
    
    // Role check methods
    public function isDirekturUtama(): bool
    {
        return $this->role === 'direktur_utama';
    }
    
    public function isAdminPenjualan(): bool
    {
        return $this->role === 'admin_penjualan';
    }
    
    public function isAdminGudang(): bool
    {
        return $this->role === 'admin_gudang';
    }
}
```

### 8.2 AnalysisRun Model

**File**: `app/Models/AnalysisRun.php`

```php
class AnalysisRun extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'user_id', 'nama_file_upload', 'periode_awal', 'periode_akhir',
        'min_support', 'max_len', 'min_confidence',
        'total_baris_raw', 'total_baris_clean', 'total_faktur_unik',
        'total_produk_unik', 'total_frequent_itemsets', 
        'total_association_rules', 'status'
    ];
    
    protected $casts = [
        'periode_awal' => 'date',
        'periode_akhir' => 'date',
        'min_support' => 'decimal:4',
        'min_confidence' => 'decimal:4',
    ];
    
    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    
    public function transaksiItems(): HasMany
    {
        return $this->hasMany(TransaksiItem::class);
    }
    
    public function frequentItemsets(): HasMany
    {
        return $this->hasMany(FrequentItemset::class);
    }
    
    public function associationRules(): HasMany
    {
        return $this->hasMany(AssociationRule::class);
    }
}
```

### 8.3 TransaksiItem Model

**File**: `app/Models/TransaksiItem.php`

```php
class TransaksiItem extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'analysis_run_id', 'faktur_no', 'tgl_transaksi',
        'item_name', 'qty', 'harga_satuan', 'total_harga'
    ];
    
    protected $casts = [
        'tgl_transaksi' => 'date',
        'harga_satuan' => 'decimal:2',
        'total_harga' => 'decimal:2',
    ];
    
    public function analysisRun(): BelongsTo
    {
        return $this->belongsTo(AnalysisRun::class);
    }
}
```

### 8.4 AssociationRule Model

**File**: `app/Models/AssociationRule.php`

```php
class AssociationRule extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'analysis_run_id', 'antecedent', 'consequent',
        'support', 'confidence', 'lift'
    ];
    
    protected $casts = [
        'support' => 'decimal:6',
        'confidence' => 'decimal:6',
        'lift' => 'decimal:6',
    ];
    
    public function analysisRun(): BelongsTo
    {
        return $this->belongsTo(AnalysisRun::class);
    }
}
```

### 8.5 Relationship Tree

```
User
  ├── analysisRuns (hasMany)
  │     ├── transaksiItems (hasMany)
  │     ├── frequentItemsets (hasMany)
  │     └── associationRules (hasMany)

AnalysisRun
  ├── user (belongsTo)
  ├── transaksiItems (hasMany)
  ├── frequentItemsets (hasMany)
  └── associationRules (hasMany)

TransaksiItem
  └── analysisRun (belongsTo)

FrequentItemset
  └── analysisRun (belongsTo)

AssociationRule
  └── analysisRun (belongsTo)
```

---

## 9. CONTROLLER & BUSINESS LOGIC

### 9.1 DashboardController

**File**: `app/Http/Controllers/DashboardController.php`

#### Method: index()

```php
public function index(Request $request)
{
    // 1. Fetch semua runs yang sudah selesai
    $allRuns = AnalysisRun::where('status', 'done')
        ->whereNotNull('total_frequent_itemsets')
        ->latest()
        ->get();
    
    // 2. Tangkap parameter periode dari dropdown
    $selectedRunId = $request->get('run_id', 'all');
    
    // $selectedRun: Run yang dipilih (null jika 'all')
    $selectedRun = ($selectedRunId !== 'all')
        ? $allRuns->firstWhere('id', $selectedRunId)
        : null;
    
    // $actualLatestRun: SELALU run paling baru (independent dari dropdown)
    $actualLatestRun = $allRuns->first();
    
    // 3. Hitung statistik global
    $totalAnalisis = $allRuns->count();
    
    $totalTransaksi = AnalysisRun::where('status', 'done')
        ->whereNotNull('total_frequent_itemsets')
        ->withCount('transaksiItems')
        ->get()
        ->sum('transaksi_items_count');
    
    $periodeAktif = $selectedRun
        ? ($selectedRun->periode_akhir?->year ?? now()->year)
        : 'Semua Periode';
    
    $statusSistem = $totalAnalisis > 0 ? 'Normal' : 'Belum ada data';
    
    // 4. Activity Metrics (SELALU pakai run terbaru)
    $metricRun = $actualLatestRun;
    
    $activityMetrics = [
        [
            'label' => 'Data transaksi masuk',
            'value' => $metricRun && $metricRun->total_baris_raw
                ? min(100, max(0, (int) round((($metricRun->total_baris_clean ?? 0) / $metricRun->total_baris_raw) * 100)))
                : 0,
            'detail' => $metricRun && $metricRun->total_baris_raw
                ? (($metricRun->total_baris_clean ?? 0) . ' dari ' . $metricRun->total_baris_raw . ' baris valid')
                : 'Belum ada data unggah',
            'color' => 'bg-[#A1582F]',
        ],
        // ... metrics lainnya
    ];
    
    // 5. Query Top 10 Association Rules
    if ($selectedRunId === 'all') {
        // MODE GABUNGAN: Group by antecedent-consequent, take highest lift per group
        $topRules = AssociationRule::whereIn('analysis_run_id', $allRuns->pluck('id'))
            ->get()
            ->groupBy(fn ($rule) => $rule->antecedent . '|' . $rule->consequent)
            ->map(fn ($group) => $group->sortByDesc('lift')->first())
            ->sortByDesc('lift')
            ->take(10)
            ->map(function ($rule) {
                return [
                    'label' => $rule->antecedent . ' → ' . $rule->consequent,
                    'lift' => (float) $rule->lift,
                    'antecedent' => $rule->antecedent,
                    'consequent' => $rule->consequent,
                    'support' => (float) $rule->support,
                    'confidence' => (float) $rule->confidence,
                ];
            })
            ->values()
            ->toArray();
    } else {
        // MODE PERIODE SPESIFIK
        $topRules = [];
        if ($selectedRun) {
            $topRules = $selectedRun->associationRules()
                ->orderBy('lift', 'desc')
                ->limit(10)
                ->get()
                ->map(function ($rule) {
                    return [
                        'label' => $rule->antecedent . ' → ' . $rule->consequent,
                        'lift' => (float) $rule->lift,
                        'antecedent' => $rule->antecedent,
                        'consequent' => $rule->consequent,
                        'support' => (float) $rule->support,
                        'confidence' => (float) $rule->confidence,
                    ];
                })
                ->toArray();
        }
    }
    
    // 6. Tambahkan interpretasi ke setiap rule
    foreach ($topRules as $key => $rule) {
        $interpretasiData = $this->buatInterpretasi($rule);
        $topRules[$key]['interpretasi'] = $interpretasiData['interpretasi'];
        $topRules[$key]['rekomendasi'] = $interpretasiData['rekomendasi'];
    }
    
    // 7. Label konteks periode
    $labelPeriodeInterpretasi = $selectedRun
        ? $selectedRun->nama_file_upload . ' (' . ($selectedRun->periode_awal?->format('d M Y') ?? '-')
          . ' - ' . ($selectedRun->periode_akhir?->format('d M Y') ?? '-') . ')'
        : 'Semua Periode (Gabungan)';
    
    // 8. Return view
    return view('dashboard', compact(
        'allRuns',
        'selectedRun',
        'actualLatestRun',
        'selectedRunId',
        'totalAnalisis',
        'totalTransaksi',
        'periodeAktif',
        'statusSistem',
        'activityMetrics',
        'topRules',
        'labelPeriodeInterpretasi'
    ));
}
```

#### Method: buatInterpretasi() (Private)

```php
private function buatInterpretasi(array $rule): array
{
    $antecedent = $rule['antecedent'];
    $consequent = $rule['consequent'];
    $support = $rule['support'] * 100;
    $confidence = $rule['confidence'] * 100;
    $lift = $rule['lift'];
    
    // Interpretasi: Satu paragraf menjelaskan support & confidence
    $interpretasi = sprintf(
        'Kombinasi produk %s dan %s memiliki nilai support sebesar %.2f%%, '
        . 'artinya %.2f%% dari seluruh transaksi pada periode ini mengandung kedua produk '
        . 'tersebut secara bersamaan. Nilai confidence sebesar %.2f%% menunjukkan bahwa '
        . 'dari transaksi yang mengandung %s, sebesar %.2f%% di antaranya juga mengandung %s.',
        $antecedent, $consequent, $support, $support,
        $confidence, $antecedent, $confidence, $consequent
    );
    
    // Rekomendasi: Bertingkat berdasarkan lift
    if ($lift > 2) {
        $rekomendasi = sprintf(
            'Dengan nilai lift sebesar %.3f (jauh di atas 1), kombinasi ini menunjukkan '
            . 'hubungan asosiasi yang KUAT. Sangat direkomendasikan sebagai kandidat utama '
            . 'strategi bundling atau penempatan produk berdekatan.',
            $lift
        );
    } elseif ($lift > 1 && $lift <= 2) {
        $rekomendasi = sprintf(
            'Dengan nilai lift sebesar %.3f (di atas 1), kombinasi ini menunjukkan '
            . 'hubungan asosiasi positif dengan kekuatan SEDANG. Dapat dipertimbangkan '
            . 'sebagai kandidat strategi bundling atau promosi, meski keterkaitannya '
            . 'tidak sekuat kombinasi lain dengan nilai lift lebih tinggi.',
            $lift
        );
    } else {
        $rekomendasi = sprintf(
            'Dengan nilai lift sebesar %.3f (tidak lebih besar dari 1), kombinasi ini '
            . 'TIDAK menunjukkan hubungan asosiasi yang signifikan. Kombinasi ini TIDAK '
            . 'direkomendasikan sebagai dasar strategi bundling.',
            $lift
        );
    }
    
    return [
        'interpretasi' => $interpretasi,
        'rekomendasi' => $rekomendasi,
    ];
}
```

### 9.2 UploadController

**File**: `app/Http/Controllers/UploadController.php`

#### Method: create() & store()

```php
public function create()
{
    return view('upload.create');
}

public function store(StoreUploadRequest $request)
{
    $validated = $request->validated();
    
    // Store file
    $filePath = $request->file('excel_file')->store('uploads');
    
    // Create AnalysisRun record
    $run = AnalysisRun::create([
        'user_id' => Auth::id(),
        'nama_file_upload' => $request->file('excel_file')->getClientOriginalName(),
        'periode_awal' => $validated['periode_awal'],
        'periode_akhir' => $validated['periode_akhir'],
        'status' => 'queued',
    ]);
    
    // Dispatch queue job
    ProcessTransactionUpload::dispatch($run, $filePath);
    
    return redirect()->route('dashboard')
        ->with('success', 'File sedang diproses dalam antrian. Silakan refresh halaman beberapa saat.');
}
```

### 9.3 RiwayatController

**File**: `app/Http/Controllers/RiwayatController.php`

```php
public function index(Request $request)
{
    // Ambil SEMUA analysis runs (tidak hanya status='done')
    // Load relasi user, urutkan terbaru, pagination 15 per halaman
    $runs = AnalysisRun::with('user')
        ->latest()
        ->paginate(15);
    
    return view('riwayat.index', compact('runs'));
}
```

---

## 10. VIEW & TEMPLATE

### 10.1 Layout Structure

```
layouts/app.blade.php
├── CSS/JS includes (Tailwind, Alpine, Chart.js)
├── X-app-layout (main wrapper)
│   ├── Navigation (topbar + sidebar)
│   └── @yield('content')
│
└── Specific pages:
    ├── dashboard.blade.php
    ├── upload/create.blade.php
    ├── analysis/parameter.blade.php
    ├── riwayat/index.blade.php
    └── auth/login.blade.php
```

### 10.2 Dashboard Template Key Sections

**File**: `resources/views/dashboard.blade.php`

```blade
<!-- 1. Banner Selamat Datang -->
<div class="mb-8 rounded-3xl border border-[#F0D7A7] bg-gradient-to-r ...">
    <h3>Halo, {{ Auth::user()->name }}.</h3>
    <a href="{{ route('upload.create') }}"> Unggah Data </a>
    <a href="{{ route('analysis.parameter', $actualLatestRun) }}"> Ubah Parameter Terbaru </a>
</div>

<!-- 2. Statistics Grid (4 kolom) -->
<div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
    <div>Total Analisis: {{ $totalAnalisis }}</div>
    <div>Transaksi Diproses: {{ number_format($totalTransaksi) }}</div>
    <div>Periode Aktif: {{ $periodeAktif }}</div>
    <div>Status Sistem: {{ $statusSistem }}</div>
</div>

<!-- 3. Association Rules Section -->
@if (count($topRules) > 0)
    <div class="rounded-2xl border border-slate-200 bg-white p-6">
        <h4>Aturan Asosiasi Teratas {{ $selectedRunId === 'all' ? '(Semua Periode)' : '(Top 10)' }}</h4>
        
        <!-- Dropdown Filter -->
        <form method="GET" action="{{ route('dashboard') }}">
            <select name="run_id" onchange="this.form.submit()">
                <option value="all" {{ $selectedRunId === 'all' ? 'selected' : '' }}>
                    🌐 Semua Periode (Gabungan)
                </option>
                @foreach ($allRuns as $run)
                    <option value="{{ $run->id }}">{{ $run->nama_file_upload }}</option>
                @endforeach
            </select>
        </form>
        
        <!-- Chart -->
        <canvas id="rulesChart"></canvas>
        
        <!-- Table -->
        <table>
            @foreach ($topRules as $rule)
                <tr>
                    <td>{{ $rule['antecedent'] }} → {{ $rule['consequent'] }}</td>
                    <td>{{ number_format($rule['support'] * 100, 2) }}%</td>
                    <td>{{ number_format($rule['confidence'] * 100, 2) }}%</td>
                    <td>{{ number_format($rule['lift'], 3) }}</td>
                </tr>
            @endforeach
        </table>
    </div>
    
    <!-- 4. Interpretasi & Rekomendasi Section (NEW) -->
    <div class="mt-8">
        <h3 class="text-2xl font-bold">Interpretasi & Rekomendasi</h3>
        <p class="text-sm text-slate-600">
            Berdasarkan 10 aturan asosiasi dengan nilai lift tertinggi pada periode:
            <span class="font-semibold">{{ $labelPeriodeInterpretasi }}</span>
        </p>
        
        <div class="grid gap-6 md:grid-cols-2">
            @foreach ($topRules as $rule)
                @php
                    $borderColor = $rule['lift'] > 2 ? 'border-l-[#C1584A]' :
                                   ($rule['lift'] > 1 ? 'border-l-[#E2A33D]' : 'border-l-[#8B9490]');
                @endphp
                <div class="rounded-xl border border-slate-200 p-6 border-l-4 {{ $borderColor }}">
                    <div class="flex justify-between mb-4">
                        <h4 class="font-mono text-sm">{{ $rule['antecedent'] }} → {{ $rule['consequent'] }}</h4>
                        <span class="bg-slate-100 px-3 py-1 rounded-full font-mono text-xs">
                            Lift: {{ number_format($rule['lift'], 3) }}
                        </span>
                    </div>
                    
                    <p class="text-sm text-slate-700 mb-4">{{ $rule['interpretasi'] }}</p>
                    
                    <div class="rounded-lg p-4 {{ $rule['lift'] > 2 ? 'bg-[#FEF5F3]' : 'bg-[#FFFBF0]' }}">
                        <p class="text-sm">{{ $rule['rekomendasi'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endif

<!-- 5. Activity Metrics & Quick Menu -->
<div class="grid gap-6 lg:grid-cols-[1.5fr_0.9fr]">
    <!-- Ringkasan Aktivitas -->
    <div>
        @foreach ($activityMetrics as $metric)
            <div class="mb-4">
                <div class="flex justify-between text-sm">
                    <span>{{ $metric['label'] }}</span>
                    <span>{{ $metric['value'] }}%</span>
                </div>
                <div class="h-2.5 bg-slate-100 rounded-full overflow-hidden">
                    <div class="h-full {{ $metric['color'] }}" style="width: {{ $metric['value'] }}%"></div>
                </div>
            </div>
        @endforeach
    </div>
    
    <!-- Menu Cepat -->
    <div>
        <h4>Menu Cepat</h4>
        <a href="{{ route('upload.create') }}"> Unggah Data </a>
        <a href="{{ route('dashboard') }}"> Dashboard Overview </a>
        @if (Auth::user()->isDirekturUtama() || Auth::user()->isAdminPenjualan())
            <a href="{{ route('riwayat.index') }}"> Riwayat Hasil Analisis </a>
        @endif
        <a href="{{ route('profile.edit') }}"> Profil Pengguna </a>
    </div>
</div>
```

### 10.3 Riwayat Template

**File**: `resources/views/riwayat/index.blade.php`

```blade
<x-app-layout>
    <h1>Riwayat Hasil Analisis</h1>
    
    @if ($runs->total() > 0)
        <table class="w-full">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Nama File</th>
                    <th>Periode</th>
                    <th>Diunggah Oleh</th>
                    <th>Tanggal</th>
                    <th>Status</th>
                    <th>Faktur/Baris</th>
                    <th>Hasil Analisis</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($runs as $index => $run)
                    @php
                        $no = ($runs->currentPage() - 1) * $runs->perPage() + $index + 1;
                        
                        if ($run->status === 'done' && $run->total_frequent_itemsets) {
                            $statusBadge = ['class' => 'bg-emerald-100 text-emerald-800', 'text' => 'Selesai Dianalisis'];
                        } elseif ($run->status === 'done' && !$run->total_frequent_itemsets) {
                            $statusBadge = ['class' => 'bg-[#FFF3E0] text-[#E68D28]', 'text' => 'Menunggu Analisis'];
                        } elseif ($run->status === 'processing') {
                            $statusBadge = ['class' => 'bg-[#F5E6D3] text-[#C1584A]', 'text' => 'Sedang Diproses'];
                        } elseif ($run->status === 'failed') {
                            $statusBadge = ['class' => 'bg-[#FADBD8] text-[#C1584A]', 'text' => 'Gagal'];
                        }
                    @endphp
                    <tr>
                        <td>{{ $no }}</td>
                        <td>{{ $run->nama_file_upload }}</td>
                        <td>{{ $run->periode_awal->format('d M Y') }} - {{ $run->periode_akhir->format('d M Y') }}</td>
                        <td>{{ $run->user->name }}</td>
                        <td>{{ $run->created_at->format('d M Y H:i') }}</td>
                        <td><span class="inline-block rounded-full px-3 py-1 text-xs {{ $statusBadge['class'] }}">{{ $statusBadge['text'] }}</span></td>
                        <td class="font-mono">{{ $run->total_faktur_unik }} faktur / {{ $run->total_baris_clean }} baris</td>
                        <td class="font-mono">{{ $run->total_frequent_itemsets ?? 'Belum dianalisis' }} itemset, {{ $run->total_association_rules ?? '-' }} rules</td>
                        <td>
                            @if ($run->total_frequent_itemsets)
                                <a href="{{ route('dashboard', ['run_id' => $run->id]) }}">👁️ Lihat Detail</a>
                                @if (Auth::user()->isAdminPenjualan())
                                    <a href="{{ route('analysis.parameter', $run) }}">🔄 Jalankan Ulang</a>
                                @endif
                            @elseif ($run->status !== 'failed')
                                @if (Auth::user()->isAdminPenjualan())
                                    <a href="{{ route('analysis.parameter', $run) }}">⚙️ Atur Parameter</a>
                                @else
                                    -
                                @endif
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        
        <!-- Pagination -->
        {{ $runs->links() }}
    @else
        <div class="rounded-2xl border border-amber-200 bg-amber-50 p-8 text-center">
            <p class="text-lg font-semibold text-amber-900">📊 Belum ada riwayat analisis</p>
            <p class="text-sm text-amber-800">Admin Penjualan dapat memulai dengan mengunggah data transaksi baru.</p>
            @if (Auth::user()->isAdminPenjualan())
                <a href="{{ route('upload.create') }}" class="mt-4 inline-block bg-[#F4C76F] px-5 py-3 rounded-xl">
                    + Unggah Data Transaksi
                </a>
            @endif
        </div>
    @endif
</x-app-layout>
```

---

## 11. ROUTING

**File**: `routes/web.php`

```php
<?php

use App\Http\Controllers\AnalysisController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RiwayatController;
use App\Http\Controllers\UploadController;
use Illuminate\Support\Facades\Route;

// Redirect root to login
Route::get('/', function () {
    return redirect()->route('login');
});

// Dashboard (all authenticated users)
Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

// Riwayat (direktur_utama + admin_penjualan)
Route::get('/riwayat', [RiwayatController::class, 'index'])
    ->middleware(['auth', 'role:direktur_utama,admin_penjualan'])
    ->name('riwayat.index');

// Admin Penjualan routes (upload, parameter, process)
Route::middleware(['auth', 'role:admin_penjualan'])->group(function () {
    Route::get('/upload', [UploadController::class, 'create'])->name('upload.create');
    Route::post('/upload', [UploadController::class, 'store'])->name('upload.store');
    Route::get('/analysis/{run}/parameter', [AnalysisController::class, 'parameter'])->name('analysis.parameter');
    Route::post('/analysis/{run}/process', [AnalysisController::class, 'process'])->name('analysis.process');
});

// Profile routes (all authenticated users)
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
```

---

## 12. QUEUE PROCESSING

### 12.1 ProcessTransactionUpload Job

**File**: `app/Jobs/ProcessTransactionUpload.php`

```php
class ProcessTransactionUpload implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    
    public function __construct(
        public AnalysisRun $run,
        public string $filePath
    ) {}
    
    public function handle()
    {
        try {
            // 1. Update status to processing
            $this->run->update(['status' => 'processing']);
            
            // 2. Read Excel file
            $data = Excel::toCollection(new TransactionImport, $this->filePath);
            
            // 3. Process rows
            $totalBaris = 0;
            $totalBarisClean = 0;
            $chunk = [];
            
            foreach ($data[0] as $row) {
                $totalBaris++;
                
                // Validate
                if (!$this->validateRow($row)) continue;
                
                $totalBarisClean++;
                $chunk[] = [
                    'analysis_run_id' => $this->run->id,
                    'faktur_no' => $row['faktur_no'],
                    'tgl_transaksi' => $row['tgl_transaksi'],
                    'item_name' => $row['item_name'],
                    'qty' => $row['qty'],
                    'harga_satuan' => $row['harga_satuan'],
                    'total_harga' => $row['total_harga'],
                    'created_at' => now(),
                ];
                
                // Batch insert every 500 rows
                if (count($chunk) >= 500) {
                    TransaksiItem::insert($chunk);
                    $chunk = [];
                }
            }
            
            // Insert remaining rows
            if (!empty($chunk)) {
                TransaksiItem::insert($chunk);
            }
            
            // 4. Calculate metadata
            $fakturUnik = TransaksiItem::where('analysis_run_id', $this->run->id)
                ->distinct('faktur_no')
                ->count('faktur_no');
            
            $produkUnik = TransaksiItem::where('analysis_run_id', $this->run->id)
                ->distinct('item_name')
                ->count('item_name');
            
            // 5. Update AnalysisRun
            $this->run->update([
                'total_baris_raw' => $totalBaris,
                'total_baris_clean' => $totalBarisClean,
                'total_faktur_unik' => $fakturUnik,
                'total_produk_unik' => $produkUnik,
                'status' => 'done',
            ]);
            
        } catch (Exception $e) {
            $this->run->update(['status' => 'failed']);
            throw $e;
        }
    }
    
    private function validateRow($row): bool
    {
        if (empty($row['faktur_no']) || empty($row['item_name'])) {
            return false;
        }
        
        $tgl = Carbon::parse($row['tgl_transaksi']);
        if ($tgl < $this->run->periode_awal || $tgl > $this->run->periode_akhir) {
            return false;
        }
        
        return true;
    }
}
```

### 12.2 Queue Configuration

**File**: `config/queue.php`

```php
'default' => env('QUEUE_CONNECTION', 'database'),

'connections' => [
    'database' => [
        'driver' => 'database',
        'table' => 'jobs',
        'queue' => 'default',
        'retry_after' => 90,
        'after_commit' => false,
    ],
    // ... other drivers
],
```

### 12.3 Running Queue Worker

```bash
# Start queue worker
php artisan queue:work database --tries=3 --sleep=5 --timeout=0

# Parameters:
# --tries=3: Retry job up to 3 times if error
# --sleep=5: Sleep 5 seconds between job checks
# --timeout=0: No timeout (let job complete naturally)
```

---

## 13. INTERPRETASI & REKOMENDASI LOGIC

### 13.1 Metrics Explanation

**Support**: Frekuensi co-occurrence dari antecedent DAN consequent

```
support(A, B) = count(transactions containing both A and B) / total transactions

Contoh:
- "Nasi Kuning" & "Sambel Matah" muncul bersama 30 kali dari 1000 transaksi
- support = 30 / 1000 = 0.03 = 3%

Interpretasi: 3% dari semua transaksi mengandung kedua produk bersamaan
```

**Confidence**: Probabilitas consequent DIBERIKAN antecedent

```
confidence(A → B) = count(A and B) / count(A)

Contoh:
- "Nasi Kuning" muncul 100 kali
- "Nasi Kuning" & "Sambel Matah" muncul 30 kali
- confidence = 30 / 100 = 0.30 = 30%

Interpretasi: Dari setiap 100 pembeli Nasi Kuning, 30 di antaranya juga beli Sambel Matah
```

**Lift**: Rasio confidence terhadap baseline

```
lift(A → B) = confidence(A → B) / support(B)

Contoh:
- confidence("Nasi Kuning" → "Sambel Matah") = 0.30
- support("Sambel Matah") = 0.05 (5% dari semua transaksi)
- lift = 0.30 / 0.05 = 6.0

Interpretasi: Pembeli Nasi Kuning 6 kali LEBIH MUNGKIN membeli Sambel Matah
dibanding pembeli random
```

### 13.2 Lift Strength Classification

| Range | Strength | Interpretation | Recommendation |
|-------|----------|-----------------|-----------------|
| > 2 | KUAT (Strong) | Sangat kuat hubungan asosiasi | Candidate untuk bundling utama |
| > 1 & ≤ 2 | SEDANG (Moderate) | Positif tapi moderate | Pertimbangan dalam promosi |
| ≤ 1 | TIDAK SIGNIFIKAN (Weak) | Tidak ada hubungan real | Abaikan untuk bundling |

### 13.3 Text Generation Examples

**Lift = 6.0 (KUAT)**

```
Interpretasi:
"Kombinasi produk Nasi Kuning dan Sambel Matah memiliki nilai support sebesar 3.00%, 
artinya 3.00% dari seluruh transaksi pada periode ini mengandung kedua produk tersebut 
secara bersamaan. Nilai confidence sebesar 30.00% menunjukkan bahwa dari transaksi yang 
mengandung Nasi Kuning, sebesar 30.00% di antaranya juga mengandung Sambel Matah."

Rekomendasi:
"Dengan nilai lift sebesar 6.000 (jauh di atas 1), kombinasi ini menunjukkan hubungan 
asosiasi yang KUAT. Sangat direkomendasikan sebagai kandidat utama strategi bundling 
atau penempatan produk berdekatan."
```

**Lift = 1.5 (SEDANG)**

```
Interpretasi:
"Kombinasi produk Nasi Kuning dan Minuman mempunyai nilai support sebesar 8.00%, 
artinya 8.00% dari seluruh transaksi pada periode ini mengandung kedua produk tersebut 
secara bersamaan. Nilai confidence sebesar 15.00% menunjukkan bahwa dari transaksi yang 
mengandung Nasi Kuning, sebesar 15.00% di antaranya juga mengandung Minuman."

Rekomendasi:
"Dengan nilai lift sebesar 1.500 (di atas 1), kombinasi ini menunjukkan hubungan 
asosiasi positif dengan kekuatan SEDANG. Dapat dipertimbangkan sebagai kandidat strategi 
bundling atau promosi, meski keterkaitannya tidak sekuat kombinasi lain dengan nilai 
lift lebih tinggi."
```

**Lift = 0.8 (TIDAK SIGNIFIKAN)**

```
Interpretasi:
"Kombinasi produk Nasi Kuning dan Teh Tarik memiliki nilai support sebesar 2.00%, 
artinya 2.00% dari seluruh transaksi pada periode ini mengandung kedua produk tersebut 
secara bersamaan. Nilai confidence sebesar 4.00% menunjukkan bahwa dari transaksi yang 
mengandung Nasi Kuning, sebesar 4.00% di antaranya juga mengandung Teh Tarik."

Rekomendasi:
"Dengan nilai lift sebesar 0.800 (tidak lebih besar dari 1), kombinasi ini TIDAK 
menunjukkan hubungan asosiasi yang signifikan. Kombinasi ini TIDAK direkomendasikan 
sebagai dasar strategi bundling."
```

---

## 14. API & RESPONSE FORMAT

### 14.1 Python Service API

**Endpoint**: `POST /api/analyze`

**Request**:

```json
{
    "transactions": [
        {
            "faktur_no": "INV001",
            "tgl_transaksi": "2025-04-01",
            "items": ["Nasi Kuning", "Sambel Matah", "Minuman"]
        },
        {
            "faktur_no": "INV002",
            "tgl_transaksi": "2025-04-01",
            "items": ["Nasi Kuning", "Teh Tarik"]
        }
    ],
    "min_support": 0.01,
    "min_confidence": 0.5,
    "max_len": 3
}
```

**Response** (200 OK):

```json
{
    "status": "success",
    "data": {
        "frequent_itemsets": [
            {
                "itemset": ["Nasi Kuning"],
                "support": 0.50
            },
            {
                "itemset": ["Sambel Matah"],
                "support": 0.30
            },
            {
                "itemset": ["Nasi Kuning", "Sambel Matah"],
                "support": 0.25
            }
        ],
        "association_rules": [
            {
                "antecedent": "Nasi Kuning",
                "consequent": "Sambel Matah",
                "support": 0.25,
                "confidence": 0.50,
                "lift": 1.67
            }
        ]
    }
}
```

### 14.2 Laravel API Responses

**Success Upload**:

```json
{
    "status": "success",
    "message": "File sedang diproses dalam antrian. Silakan refresh halaman beberapa saat.",
    "redirect": "/dashboard"
}
```

**Error Validation**:

```json
{
    "status": "error",
    "message": "Validation failed",
    "errors": {
        "excel_file": ["The file must be a file of type: xlsx, xls."],
        "periode_akhir": ["The periode akhir must be a date after periode awal."]
    }
}
```

---

## 15. ERROR HANDLING & VALIDATION

### 15.1 Validation Rules

**Upload Form** (`StoreUploadRequest`):

```php
public function rules()
{
    return [
        'periode_awal' => 'required|date|date_format:Y-m-d',
        'periode_akhir' => 'required|date|date_format:Y-m-d|after:periode_awal',
        'excel_file' => 'required|file|mimes:xlsx,xls|max:51200',  // 50MB
    ];
}

public function messages()
{
    return [
        'periode_akhir.after' => 'Periode akhir harus lebih besar dari periode awal.',
        'excel_file.mimes' => 'File harus berformat Excel (.xlsx atau .xls).',
        'excel_file.max' => 'File tidak boleh lebih dari 50MB.',
    ];
}
```

### 15.2 Authorization Checks

```php
// In controller methods
if ($run->user_id !== Auth::id() && !Auth::user()->isDirekturUtama()) {
    abort(403, 'Anda tidak berwenang mengakses resource ini.');
}
```

### 15.3 Error Responses

**403 Forbidden** (Role mismatch):

```
Anda tidak memiliki akses ke halaman ini.
```

**404 Not Found** (Resource tidak ada):

```
Halaman yang Anda cari tidak ditemukan.
```

**422 Unprocessable Entity** (Validation error):

```json
{
    "message": "The given data was invalid.",
    "errors": {
        "field_name": ["Error message"]
    }
}
```

### 15.4 Job Error Handling

```php
// In ProcessTransactionUpload job
try {
    // Process file
    // ...
} catch (Exception $e) {
    // Log error
    Log::error('Transaction upload failed', [
        'run_id' => $this->run->id,
        'error' => $e->getMessage(),
    ]);
    
    // Update status
    $this->run->update(['status' => 'failed']);
    
    // Rethrow to mark job as failed
    throw $e;
}
```

---

## 16. DEPLOYMENT & CONFIGURATION

### 16.1 Environment Setup

```env
# .env production
APP_NAME=Dashboard
APP_ENV=production
APP_DEBUG=false
APP_URL=https://dashboard.ptsr.id

DB_CONNECTION=mysql
DB_HOST=db.production.com
DB_PORT=3306
DB_DATABASE=dashboard_prod
DB_USERNAME=prod_user
DB_PASSWORD=***

QUEUE_CONNECTION=database
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
```

### 16.2 Initial Setup Commands

```bash
# Install dependencies
composer install

# Generate app key
php artisan key:generate

# Run migrations
php artisan migrate

# Create admin user
php artisan tinker
> User::create(['name' => 'Admin', 'email' => 'admin@ptsr.id', 'password' => Hash::make('password'), 'role' => 'admin_penjualan'])

# Cache config
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Start queue worker (in separate terminal)
php artisan queue:work database --tries=3 --sleep=5 --timeout=0
```

### 16.3 Database Setup

```bash
# Create database
CREATE DATABASE dashboard_ptsriayu CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

# Run migrations
php artisan migrate

# Seed data (optional)
php artisan db:seed
```

### 16.4 Cron Job (Optional - For scheduled tasks)

```bash
# In /etc/cron.d/ or crontab
* * * * * cd /path/to/dashboard && php artisan schedule:run >> /dev/null 2>&1
```

---

## KESIMPULAN

Aplikasi Dashboard Analisis Asosiasi PT Sri Ayu adalah sistem yang komprehensif untuk market basket analysis dengan fitur-fitur:

1. **Manajemen Data**: Upload Excel, validasi, batch processing via queue
2. **Analisis Apriori**: Integrasi Python untuk frequent itemset & association rules
3. **Visualisasi**: Tabel, chart, interpretasi narasi
4. **Role-based Access**: Tiga role dengan permission berbeda
5. **Queue Processing**: Background job untuk file besar
6. **Rekomendasi Otomatis**: Interpretasi & rekomendasi berdasarkan lift strength

Setiap komponen dirancang dengan prinsip:
- **Scalability**: Queue processing untuk file besar
- **Security**: Role-based access control
- **Usability**: Friendly UI dengan Blade templates
- **Maintainability**: Clean code dengan separation of concerns

---

**Dokumentasi disusun untuk keperluan analisis lanjutan dengan Claude AI**  
**Versi: 1.0 | Tanggal: 2 September 2026**
