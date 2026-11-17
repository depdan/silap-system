<?php
/**
 * SILAP – Sistem Informasi Laporan PPSU
 * Database Connection Module (Dikonfigurasi Khusus untuk MAMP di macOS)
 * 
 * Pengaturan MAMP Default:
 * - Host: 127.0.0.1
 * - Port: 8889
 * - Socket: /Applications/MAMP/tmp/mysql/mysql.sock
 * - Database: silap_db
 * - Username: root
 * - Password: root
 */

$db_name = 'silap_db';
$conn = null;
$db_type = 'mysql';

// Daftar opsi koneksi prioritas (XAMPP 3306 -> MySQL -> MAMP)
$connection_attempts = [
    // 1. MySQL Standar XAMPP Port 3306 (user root / tanpa password)
    [
        'dsn'  => "mysql:host=127.0.0.1;port=3306;dbname={$db_name};charset=utf8mb4",
        'user' => 'root',
        'pass' => '',
        'desc' => 'XAMPP MySQL Port 3306 (pass kosong)'
    ],
    // 2. MySQL Standar Port 3306 (user root / pass root)
    [
        'dsn'  => "mysql:host=127.0.0.1;port=3306;dbname={$db_name};charset=utf8mb4",
        'user' => 'root',
        'pass' => 'root',
        'desc' => 'MySQL Port 3306 (pass root)'
    ]
];

// Tambahkan opsi MAMP hanya jika dijalankan di macOS
if (PHP_OS_FAMILY === 'Darwin') {
    $connection_attempts[] = [
        'dsn'  => "mysql:host=127.0.0.1;port=8889;dbname={$db_name};charset=utf8mb4",
        'user' => 'root',
        'pass' => 'root',
        'desc' => 'MAMP Port 8889'
    ];
    $connection_attempts[] = [
        'dsn'  => "mysql:unix_socket=/Applications/MAMP/tmp/mysql/mysql.sock;dbname={$db_name};charset=utf8mb4",
        'user' => 'root',
        'pass' => 'root',
        'desc' => 'MAMP Unix Socket'
    ];
}

// Coba hubungkan ke MySQL MAMP
foreach ($connection_attempts as $attempt) {
    try {
        $conn = new PDO($attempt['dsn'], $attempt['user'], $attempt['pass'], [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_TIMEOUT            => 1,
        ]);
        $db_type = 'mysql (' . $attempt['desc'] . ')';
        break;
    } catch (PDOException $e) {
        // Lanjutkan ke opsi berikutnya
        continue;
    }
}

// Jika MAMP MySQL belum berjalan, gunakan fallback SQLite lokal
if (!$conn) {
    $db_type = 'sqlite';
    $sqlite_file = __DIR__ . '/silap.sqlite';
    try {
        $conn = new PDO("sqlite:" . $sqlite_file);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        initSQLiteSchema($conn);
    } catch (PDOException $sqle) {
        die("Koneksi Database Gagal: " . $sqle->getMessage());
    }
}

/**
 * Inisialisasi tabel jika menggunakan SQLite lokal
 */
function initSQLiteSchema($pdo) {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            nama TEXT NOT NULL,
            no_hp TEXT UNIQUE NOT NULL,
            username TEXT UNIQUE NOT NULL,
            password TEXT NOT NULL,
            role TEXT NOT NULL DEFAULT 'petugas',
            wilayah TEXT DEFAULT 'jakpus',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS regu (
            id TEXT PRIMARY KEY,
            nama TEXT NOT NULL,
            jenis TEXT NOT NULL,
            ketua TEXT NOT NULL,
            no_hp TEXT NOT NULL,
            wilayah TEXT DEFAULT '10 RW (RW 01 - RW 10)',
            deskripsi TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS anggota (
            id TEXT PRIMARY KEY,
            nama TEXT NOT NULL,
            hp TEXT,
            role TEXT DEFAULT 'Anggota Regu',
            regu_id TEXT DEFAULT '',
            regu TEXT DEFAULT 'Belum Ditugaskan',
            wilayah TEXT DEFAULT '10 RW',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS laporan (
            id TEXT PRIMARY KEY,
            no_pekerjaan TEXT NOT NULL,
            minggu TEXT NOT NULL,
            rw TEXT NOT NULL,
            kat TEXT NOT NULL,
            jenis_pekerjaan TEXT NOT NULL,
            judul TEXT NOT NULL,
            alamat TEXT NOT NULL,
            lokasi_detail TEXT,
            kendala TEXT,
            petugas TEXT NOT NULL,
            regu TEXT NOT NULL,
            no_hp TEXT NOT NULL,
            jumlah_petugas INTEGER DEFAULT 4,
            progres INTEGER DEFAULT 100,
            status TEXT DEFAULT 'selesai',
            foto_sebelum TEXT,
            foto_proses TEXT,
            foto_sesudah TEXT,
            tanggal TEXT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );
    ");

    $count = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    if ($count == 0) {
        $adminHash = password_hash('admin123', PASSWORD_DEFAULT);
        $ketuaHash = password_hash('ketua123', PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("INSERT INTO users (nama, no_hp, username, password, role, wilayah) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute(['Siti Aminah', '081299990001', 'admin', $adminHash, 'admin', 'jakpus']);
        $stmt->execute(['Budi Santoso', '081234567890', 'ketua', $ketuaHash, 'petugas', 'jakpus']);
    }

    $countRegu = $pdo->query("SELECT COUNT(*) FROM regu")->fetchColumn();
    if ($countRegu == 0) {
        $stmtRegu = $pdo->prepare("INSERT INTO regu (id, nama, jenis, ketua, no_hp, wilayah, deskripsi) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmtRegu->execute(['REG-01', 'Regu Jalan', 'Jalan', 'Ahmad Suryadi', '0812-8899-0011', '10 RW (RW 01 - RW 10)', 'Penanganan Jalan rusak (ringan), trotoar, marka jalan, kanstin, dan saluran drainase jalan.']);
        $stmtRegu->execute(['REG-02', 'Regu Taman', 'Taman', 'Slamet Riyadi', '0817-9988-1122', '10 RW (RW 01 - RW 10)', 'Pemangkasan pohon rendah, pembersihan taman RTH, pemotongan rumput liar, dan penghijauan.']);
        $stmtRegu->execute(['REG-03', 'Regu Kantor', 'Kantor', 'Hartono Prasetyo', '0815-4433-2211', 'Kantor Kelurahan & 10 Pos RW', 'Pemeliharaan sarana kantor kelurahan, inventaris logistik regu, pos RW, dan fasilitas umum.']);
    }
}
