<?php
/**
 * NEXUS SMARTPASS - BACKEND ENDPOINT API (PHP)
 * Menerima request HTTP POST dari ESP32 saat kartu di-tap
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type');

// Ambil payload JSON dari ESP32
$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true);

// Fallback jika dikirim lewat $_POST atau $_GET biasa
$uid = strtoupper(trim($data['uid'] ?? $_POST['uid'] ?? $_GET['uid'] ?? ''));
$deviceId = trim($data['device_id'] ?? $_POST['device_id'] ?? 'DEVICE-01');

if (empty($uid)) {
    echo json_encode([
        'status'  => 'error',
        'message' => 'Parameter UID tidak ditemukan'
    ]);
    exit;
}

// =========================================================================
// 1. DATA MASTER MEMBER / PENGGUNA (Mode Cepat Tanpa Sentuh Database Dulu)
// =========================================================================
// Masukkan kode UID kartu RFID kamu di sini untuk uji coba:
$masterMember = [
    'A1B2C3D4' => ['nama' => 'Alex Turner',    'role' => 'Lab Lead',      'nim' => 'LAB-001'],
    '12345678' => ['nama' => 'Budi Pratama',   'role' => 'Researcher',    'nim' => 'LAB-002'],
    '87654321' => ['nama' => 'Siti Nurhaliza', 'role' => 'Lab Assistant', 'nim' => 'LAB-003'],
    // Tambahkan UID kartu kamu yang terbaca di Serial Monitor Arduino
];

// Opsi: Jika ingin langsung cek Database MySQL (uncomment jika siap pakai DB)
/*
require_once __DIR__ . '/koneksi.php';
try {
    $stmt = $conn->prepare("SELECT nama, role FROM users WHERE rfid_uid = :uid LIMIT 1");
    $stmt->execute([':uid' => $uid]);
    $userDB = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($userDB) {
        $masterMember[$uid] = ['nama' => $userDB['nama'], 'role' => $userDB['role']];
    }
} catch (Exception $e) {}
*/

$currentTime = date('Y-m-d H:i:s');
$jamMenit    = date('H:i');

// =========================================================================
// 2. LOGIKA VERIFIKASI KARTU
// =========================================================================
if (isset($masterMember[$uid])) {
    $member = $masterMember[$uid];
    $response = [
        'status'    => 'success',
        'uid'       => $uid,
        'nama'      => $member['nama'],
        'role'      => $member['role'] ?? 'Member',
        'waktu'     => $jamMenit,
        'timestamp' => $currentTime,
        'device_id' => $deviceId,
        'message'   => 'Akses Diterima'
    ];
} else {
    // Kartu baru / belum terdaftar
    $response = [
        'status'    => 'unregistered',
        'uid'       => $uid,
        'nama'      => 'Unknown Card',
        'waktu'     => $jamMenit,
        'timestamp' => $currentTime,
        'device_id' => $deviceId,
        'message'   => 'Kartu Belum Terdaftar'
    ];
}

// =========================================================================
// 3. SIMPAN LOG AKTIVITAS TAP TERBARU (Untuk Live Monitoring Web)
// =========================================================================
$logFile = __DIR__ . '/rfid_recent_log.json';
$existingLogs = [];
if (file_exists($logFile)) {
    $existingLogs = json_decode(file_get_contents($logFile), true) ?? [];
}
// Simpan 30 aktivitas tap terakhir
array_unshift($existingLogs, $response);
$existingLogs = array_slice($existingLogs, 0, 30);
file_put_contents($logFile, json_encode($existingLogs, JSON_PRETTY_PRINT));

// Kirim response JSON ke ESP32
echo json_encode($response);
