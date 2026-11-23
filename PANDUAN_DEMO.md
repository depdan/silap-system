# 📘 PANDUAN SINGKAT DEMO & PENGUJIAN SILAP
**Sistem Informasi Laporan PPSU – Kelurahan Pekayon 2026**

---

## 🚀 1. Cara Menjalankan Aplikasi di Hari-H (MacBook)

Buka **Terminal** di MacBook Anda:

### Tab Terminal 1 (Server Laravel Lokal)
```bash
cd /Applications/MAMP/htdocs/silap-system-main
php artisan serve --port=8000
```
> *Biarkan tab ini tetap terbuka.*

### Tab Terminal 2 (Buat Link Online untuk HP)
Buka tab baru dengan menekan tombol **`⌘ (Command) + T`**, lalu ketik:
```bash
cloudflared tunnel --url http://127.0.0.1:8000
```
> *Tunggu 5 detik, salin alamat link `https://xxx.trycloudflare.com` yang muncul di terminal untuk dibuka di HP penguji/petugas.*

---

## 🌐 2. Alamat Akses & Kredensial Login

### A. Layar MacBook (Untuk Ditampilkan di Proyektor)
* **URL:** `http://127.0.0.1:8000/admin.php`
* **Username:** `admin`
* **Password:** `admin123`
* **Peran:** Administrator Kelurahan (Command Center, Verifikasi Tugas, Rekap Excel, Manajemen Regu).

### B. Layar Smartphone (Untuk Petugas Lapangan)
* **URL:** Buka link `https://xxx.trycloudflare.com` dari Terminal 2.
* **Password Semua Petugas:** `ketua123`
* **Pilihan Akun Login (Bisa pakai Username ATAU Nomor HP):**

| Tim / Regu | Wilayah & Jalan | Username | Nomor HP |
| :--- | :---: | :---: | :---: |
| **Tim 1 (RW 01)** | RW 01 - Jl. Lapan | `dedi` | `085780289445` |
| **Tim 2 (RW 02)** | RW 02 - Jl. Gandaria | `faisal` | `085780019427` |
| **Tim 3 (RW 03)** | RW 03 - Jl. Gandaria | `aris` | `085718497640` |
| **Tim 4 (RW 04)** | RW 04 - Jl. Buah | `mulyono` | `087775378975` |
| **Tim 6 (RW 06)** | RW 06 - Jl. Belly | `arief` | `081283585070` |
| **Tim 7 (RW 07)** | RW 07 - Jl. Tipar | `andi` | `081218065441` |
| **Tim 8 (RW 08)** | RW 08 - Jl. Sukamto | `joko` | `085715914802` |
| **Tim 9 (RW 09)** | RW 09 - Jl. Haji Balok | `zaenal` | `081282442445` |
| **Tim 10 (RW 10)** | RW 10 - Jl. Lewa | `herwin` | `08987488332` |
| **Tim Taman** | 10 RW (RTH) | `nemin` | `0895360379754` |
| **Tim Jalan Raya** | Jalan Protokol | `imam` | `087797251130` |
| **Tim Hilux** | Armada Mobil | `sugandi` | `0895350153068` |
| **Regu Kantor** | Kantor Kelurahan | `samsul` | `085781511533` |
| **Pamdal** | Kantor Kelurahan | `afif` | `081283580014` |
| *Akun Demo Umum* | Bebas | `ketua` | `081234567890` |

---

## 🎬 3. Skenario Alur Presentasi (Demo Flow)

1. **Buka Layar Proyektor (MacBook):**
   * Login sebagai `admin` di MacBook.
   * Tunjukkan dashboard ringkasan statistik RW 01 – RW 10 dan 14 tim yang terdaftar.

2. **Simulasi Laporan Baru dari HP (Petugas):**
   * Buka link Cloudflare di HP.
   * Login sebagai `dedi` (Ketua Tim 1).
   * Klik **Buat Laporan**.
   * Isi judul penugasan, pilih RW, lampirkan 3 foto (Sebelum 0%, Proses, Selesai 100%), lalu tekan **Kirim Laporan**.

3. **Tunjukkan Integrasi Real-Time ke Dosen/Penguji:**
   * Di browser MacBook (Admin), refresh halaman atau cek daftar verifikasi.
   * Laporan baru dari HP tadi **langsung muncul di MacBook** lengkap dengan foto, titik koordinat, dan status *"Perlu Verifikasi"*.

4. **Verifikasi & Ekspor Laporan:**
   * Di laptop, klik tombol **Verifikasi / Setujui Laporan**.
   * Buka menu Rekapitulasi & klik **Ekspor Excel (.xlsx)** untuk memperlihatkan laporan resmi siap cetak kelurahan.

5. **Tunjukkan Master Data Regu & Anggota:**
   * Buka menu **Manajemen Regu & Anggota**.
   * Tunjukkan 14 Tim dan 74 Anggota resmi Pekayon yang sudah terdistribusi rapi.

---

## 💡 4. Solusi Cepat (Troubleshooting)

* **Jika link di HP tidak bisa dibuka:**
  * Pastikan MacBook tetap terhubung ke internet (WiFi atau Hotspot HP).
  * Jika laptop sempat *sleep*, matikan proses tunnel di Terminal 2 dengan menekan `Ctrl + C`, lalu ketik ulang `cloudflared tunnel --url http://127.0.0.1:8000` untuk mendapatkan link baru.
* **Jika MAMP MySQL tidak aktif:**
  * Sistem memiliki *auto-fallback* ke basis data SQLite lokal (`silap.sqlite`), jadi aplikasi tetap berjalan normal tanpa error.
