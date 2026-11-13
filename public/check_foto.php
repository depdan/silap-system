<?php
require 'koneksi.php';
$stmt = $conn->query("SHOW COLUMNS FROM laporan");
while($r = $stmt->fetch(PDO::FETCH_ASSOC)){
    echo "col: {$r['Field']} ({$r['Type']})\n";
}
echo "--- SAMPLE DATA ---\n";
$stmt2 = $conn->query("SELECT id, no_pekerjaan, foto_sebelum, foto_proses, foto_selesai, foto FROM laporan LIMIT 3");
while($r = $stmt2->fetch(PDO::FETCH_ASSOC)){
    echo "ID: {$r['id']} | #{$r['no_pekerjaan']}\n";
    echo "  foto_sebelum: " . substr($r['foto_sebelum'] ?? 'null', 0, 50) . "\n";
    echo "  foto_proses: " . substr($r['foto_proses'] ?? 'null', 0, 50) . "\n";
    echo "  foto_selesai: " . substr($r['foto_selesai'] ?? 'null', 0, 50) . "\n";
    if (isset($r['foto'])) echo "  foto: " . substr($r['foto'] ?? 'null', 0, 50) . "\n";
}
unlink(__FILE__);

