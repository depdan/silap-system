-- ==========================================================
-- SILAP – Sistem Informasi Laporan PPSU DKI Jakarta
-- Skrip Database MySQL (Import di phpMyAdmin / XAMPP)
-- ==========================================================

CREATE DATABASE IF NOT EXISTS `silap_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `silap_db`;

-- ----------------------------------------------------------
-- 1. Tabel Pengguna (users)
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nama` VARCHAR(100) NOT NULL,
  `no_hp` VARCHAR(25) NOT NULL UNIQUE,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('admin', 'petugas') NOT NULL DEFAULT 'petugas',
  `wilayah` VARCHAR(50) DEFAULT 'jakpus',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- 2. Tabel Master Regu PPSU (Regu Jalan, Regu Taman, Regu Kantor)
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `regu` (
  `id` VARCHAR(20) PRIMARY KEY,
  `nama` VARCHAR(100) NOT NULL,
  `jenis` ENUM('Jalan', 'Taman', 'Kantor') NOT NULL,
  `ketua` VARCHAR(100) NOT NULL,
  `no_hp` VARCHAR(25) NOT NULL,
  `wilayah` VARCHAR(100) DEFAULT '10 RW (RW 01 - RW 10)',
  `deskripsi` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- 2b. Tabel Anggota PPSU
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `anggota` (
  `id` VARCHAR(50) PRIMARY KEY,
  `nama` VARCHAR(100) NOT NULL,
  `hp` VARCHAR(25),
  `role` VARCHAR(50) DEFAULT 'Anggota Regu',
  `regu_id` VARCHAR(20) DEFAULT '',
  `regu` VARCHAR(100) DEFAULT 'Belum Ditugaskan',
  `wilayah` VARCHAR(100) DEFAULT '10 RW',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- 3. Tabel Laporan Mingguan PPSU (Sesuai Revisi Klien)
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `laporan` (
  `id` VARCHAR(50) PRIMARY KEY,
  `no_pekerjaan` VARCHAR(50) NOT NULL,
  `minggu` VARCHAR(50) NOT NULL,
  `rw` VARCHAR(10) NOT NULL,
  `kat` ENUM('jalan', 'taman', 'saluran', 'kebersihan', 'kantor') NOT NULL,
  `jenis_pekerjaan` VARCHAR(100) NOT NULL,
  `judul` VARCHAR(255) NOT NULL,
  `alamat` TEXT NOT NULL,
  `lokasi_detail` TEXT,
  `kendala` TEXT,
  `petugas` VARCHAR(100) NOT NULL,
  `regu` VARCHAR(50) NOT NULL,
  `no_hp` VARCHAR(25) NOT NULL,
  `jumlah_petugas` INT DEFAULT 4,
  `progres` INT DEFAULT 100,
  `status` ENUM('selesai', 'proses', 'belum') DEFAULT 'selesai',
  `foto_sebelum` LONGTEXT,
  `foto_proses` LONGTEXT,
  `foto_sesudah` LONGTEXT,
  `tanggal` VARCHAR(50) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- Akun Awal Default
-- ----------------------------------------------------------
INSERT INTO `users` (`id`, `nama`, `no_hp`, `username`, `password`, `role`, `wilayah`) VALUES
(1, 'Siti Aminah', '081299990001', 'admin', '$2y$12$C.havgqZjcNyh7PkTASUuumyK6L14Bm4GJFOX57ry0Ku7iy3JX.se', 'admin', 'jakpus'),
(2, 'Budi Santoso', '081234567890', 'ketua', '$2y$12$IWOWYu.IIHz8VINZT98ATuqATAXATupDyJjJxYvNk..6gkIT0/kL.', 'petugas', 'jakpus')
ON DUPLICATE KEY UPDATE `id`=`id`;

-- ----------------------------------------------------------
-- 3 Jenis Regu Awal
-- ----------------------------------------------------------
INSERT INTO `regu` (`id`, `nama`, `jenis`, `ketua`, `no_hp`, `wilayah`, `deskripsi`) VALUES
('REG-01', 'Regu Jalan', 'Jalan', 'Ahmad Suryadi', '0812-8899-0011', '10 RW (RW 01 - RW 10)', 'Penanganan Jalan rusak (ringan), trotoar, marka jalan, kanstin, dan saluran drainase jalan.'),
('REG-02', 'Regu Taman', 'Taman', 'Slamet Riyadi', '0817-9988-1122', '10 RW (RW 01 - RW 10)', 'Pemangkasan pohon rendah, pembersihan taman RTH, pemotongan rumput liar, dan penghijauan.'),
('REG-03', 'Regu Kantor', 'Kantor', 'Hartono Prasetyo', '0815-4433-2211', 'Kantor Kelurahan & 10 Pos RW', 'Pemeliharaan sarana kantor kelurahan, inventaris logistik regu, pos RW, dan fasilitas umum.')
ON DUPLICATE KEY UPDATE `id`=`id`;

