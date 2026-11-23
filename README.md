<p align="center">
  <img src="public/foto/silap.png" alt="Logo SILAP Kelurahan Pekayon" width="220">
</p>

<h1 align="center">SILAP – Sistem Informasi Laporan PPSU Kelurahan Pekayon</h1>

<p align="center">
  <b>Platform Digital Monitoring &amp; Verifikasi Operasional Kerja Harian PPSU Terpadu</b><br>
  Kelurahan Pekayon, Kecamatan Pasar Rebo, Kota Administrasi Jakarta Timur
</p>

<p align="center">
  <img src="https://img.shields.io/badge/PHP-8.2%2B-777BB4?style=flat-square&logo=php&logoColor=white" alt="PHP">
  <img src="https://img.shields.io/badge/Laravel-Framework-FF2D20?style=flat-square&logo=laravel&logoColor=white" alt="Laravel">
  <img src="https://img.shields.io/badge/Database-MySQL%20%2F%20SQLite-4479A1?style=flat-square&logo=mysql&logoColor=white" alt="MySQL">
  <img src="https://img.shields.io/badge/Maps-Leaflet.js-199900?style=flat-square&logo=leaflet&logoColor=white" alt="Leaflet">
  <img src="https://img.shields.io/badge/Wilayah-10%20RW%20Pekayon-EA580C?style=flat-square" alt="10 RW Pekayon">
</p>

---

## 📌 Ringkasan Sistem

**SILAP** (Sistem Laporan PPSU) adalah sistem informasi operasional berbasis web yang dirancang untuk mendigitalkan, memvalidasi, serta mendokumentasikan pelaporan kegiatan kerja harian Petugas Penanganan Prasarana dan Sarana Umum (PPSU) di wilayah Kelurahan Pekayon, Jakarta Timur. 

Aplikasi ini menggantikan pelaporan manual berbasis chat/kertas dengan integrasi titik koordinat GPS riil, dokumentasi foto bertahap, matriks verifikasi oleh pihak kelurahan, serta sistem rekap data otomatis.

---

## 🛠️ Tumpukan Teknologi

* **Backend & Framework:** PHP 8.2+ dengan Laravel Framework.
* **Basis Data:** MariaDB / MySQL (didukung fallback SQLite untuk pengujian cepat).
* **Frontend & Tampilan:** Blade Templates, CSS3 custom design token, JavaScript modern modular.
* **Peta & Geospasial:** Leaflet.js terintegrasi dengan reverse-geocoding OpenStreetMap untuk deteksi alamat dan koordinat GPS presisi petugas lapangan.
* **Ekspor & Utilitas:** ExcelJS engine untuk kompilasi rekapitulasi pekerjaan ke format lembar kerja Excel (`.xlsx`).
* **Manajemen Dependensi:** Composer 2.x.

---

## 👥 Peran Pengguna (Role & Akses)

Hak akses dan alur kerja aplikasi dibagi ke dalam 2 peran utama yang terisolasi:

1. **Petugas Lapangan (PPSU / Ketua Regu)**
   * Mengirimkan laporan pekerjaan berdasarkan nomor penugasan resmi kelurahan.
   * Melampirkan 3 tahap bukti foto dokumentasi: **Sebelum (0%)**, **Proses Kerja**, dan **Selesai (100%)**.
   * Pengambilan koordinat GPS otomatis dan penandaan lokasi kerja di peta digital.
   * Melihat riwayat status laporan (Belum Diverifikasi, Dalam Proses, Selesai Disetujui).
   * Meninjau absensi dan penugasan anggota regu kerja.

2. **Administrator Kelurahan (Seksi Ekbang / Komando)**
   * **Command Center Dashboard:** Memantau ringkasan statistik capaian, distribusi tugas per RW (RW 01 – RW 10), dan progres penanganan harian.
   * **Verifikasi Laporan:** Memvalidasi bukti fisik foto dan keterangan lokasi dari petugas regu.
   * **Rekapitulasi & Ekspor:** Melakukan penyaringan (filter) berdasarkan tanggal, kategori prasarana, status, serta mengunduh rekap berformat Excel resmi.
   * **Manajemen Data Master:** Mengatur pembagian kelompok regu (Regu Jalan, Taman, Saluran Air, Sarana Kantor), alokasi anggota, serta administrasi akun pengguna.

---

## 📋 Kebutuhan Sistem

* **PHP:** Versi 8.2 atau lebih tinggi (ekstensi aktif: `pdo_mysql`, `mbstring`, `openssl`, `curl`).
* **Web Server:** Apache (XAMPP) atau server lokal bawaan PHP Artisan.
* **Database:** MySQL / MariaDB Server (Port default `3306`).
* **Composer:** Versi 2.x.

---

## 🚀 Panduan Instalasi Lokal

### 1. Salin Repositori
```bash
git clone https://github.com/depdan/silap-system.git
cd silap-system
```

### 2. Pasang Dependensi
```bash
composer install
```

### 3. Konfigurasi Environment
Salin berkas konfigurasi dasar:
```bash
cp .env.example .env
```
Buka file `.env` dan sesuaikan parameter koneksi basis data:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=silap_db
DB_USERNAME=root
DB_PASSWORD=
```

Buat kunci aplikasi baru:
```bash
php artisan key:generate
```

### 4. Penyiapan Basis Data
Buat database bernama `silap_db` di MySQL/phpMyAdmin:
```sql
CREATE DATABASE silap_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Impor struktur tabel dan data awal dari berkas SQL yang disediakan:
* Buka **phpMyAdmin** -> Pilih `silap_db` -> Pilih tab **Import** -> Pilih file `database/database.sql` -> Klik **Go/Kirim**.

---

## 💻 Menjalankan Aplikasi

Aplikasi dapat dijalankan melalui salah satu dari dua cara berikut:

### Opsi A: Menggunakan Laravel Artisan (Direkomendasikan)
Jalankan server pengembangan:
```bash
php artisan serve
```
Akses aplikasi melalui peramban:  
👉 **http://127.0.0.1:8000**

### Opsi B: Menggunakan XAMPP Apache
Jika proyek diletakkan langsung di dalam folder `C:/xampp/htdocs/silap-laravel`, pastikan Apache dan MySQL aktif pada panel kontrol XAMPP, kemudian buka:  
👉 **http://localhost/silap-laravel/** *(Otomatis diarahkan ke portal login)*

---

## 🔐 Kredensial Akun Awal (Demo / Pengujian)

| Peran Akun | Username | Kata Sandi | Keterangan |
| :--- | :--- | :--- | :--- |
| **Administrator** | `admin` | `admin123` | Akses penuh dashboard kendali kelurahan |
| **Petugas Lapangan** | `ketua` | `ketua123` | Akses form pelaporan & log penugasan regu |

> *Catatan: Pengguna baru juga dapat melakukan pendaftaran mandiri (registrasi) langsung melalui form pendaftaran di antarmuka halaman login.*

---

## 📂 Struktur Direktori Proyek

```text
├── app/                  # Controller, Model, dan Kernel logika Laravel
├── bootstrap/            # Inisialisasi awal framework & routing
├── config/               # Seluruh konfigurasi modul aplikasi
├── database/
│   ├── database.sql      # Skema master & data default tabel SILAP
│   └── migrations/       # Riwayat skema migrasi database
├── public/
│   ├── css/ & style.css  # Desain antarmuka & token CSS SILAP
│   ├── js/ & shared.js   # Modul logika sinkronisasi data & interaktivitas
│   ├── foto/             # Aset grafis, ikon aplikasi, dan logo instansi
│   ├── views/            # Komponen visual antarmuka (partials)
│   ├── api.php           # REST gateway sinkronisasi pelaporan
│   └── index.php         # Berkas pembuka utama aplikasi
├── resources/
│   └── views/            # Template Blade terstruktur (Admin, User, Auth)
├── routes/
│   └── web.php           # Routing alur portal dan otentikasi
└── storage/              # Penyimpanan session, template cache, dan log
```

---

## 🔒 Tata Kelola & Keamanan Berkas

* Berkas konfigurasi pribadi `.env`, kredensial server, data cache, dan session runtime terisolasi dari pelacakan Git melalui ketentuan `.gitignore`.
* Jangan melakukan *force commit* pada berkas runtime di folder `storage/` maupun `vendor/`.
* Selalu perbarui template `.env.example` apabila terdapat penambahan parameter lingkungan baru.

---

## 📄 Lisensi
Hak cipta proyek dikembangkan untuk kebutuhan operasional lingkungan Kelurahan Pekayon, Pasar Rebo, Jakarta Timur.

