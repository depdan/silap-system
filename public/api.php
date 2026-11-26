<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized: Sesi login tidak valid.']);
    exit;
}

require_once __DIR__ . '/koneksi.php';

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$adminActions = ['delete_anggota', 'delete_report', 'delete_all_reports', 'save_all_regu', 'save_all_anggota', 'update_admin_credentials'];
if (in_array($action, $adminActions, true) && ($_SESSION['user']['role'] ?? '') !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Forbidden: Membutuhkan hak akses Administrator.']);
    exit;
}

function normalizePhone08($ph) {
    if (!$ph) return '';
    $clean = trim((string)$ph);
    $clean = str_replace(['-', ' '], '', $clean);
    if (strpos($clean, '+62') === 0) {
        $clean = '0' . substr($clean, 3);
    } elseif (strpos($clean, '62') === 0) {
        $clean = '0' . substr($clean, 2);
    }
    return $clean;
}

if ($action === 'get_reports') {
    try {
        $stmt = $conn->query("SELECT * FROM `laporan` ORDER BY `created_at` DESC");
        $rows = $stmt->fetchAll();
        $formatted = [];
        foreach ($rows as $r) {
            $formatted[] = [
                'id'             => $r['id'],
                'noPekerjaan'    => $r['no_pekerjaan'],
                'minggu'         => $r['minggu'],
                'rw'             => $r['rw'],
                'kat'            => $r['kat'],
                'jenisPekerjaan' => $r['jenis_pekerjaan'],
                'judul'          => $r['judul'],
                'targetMeter'    => $r['target_meter'] ?? '',
                'target_meter'   => $r['target_meter'] ?? '',
                'alamat'         => $r['alamat'],
                'lokasiDetail'   => $r['lokasi_detail'],
                'kendala'        => $r['kendala'],
                'petugas'        => $r['petugas'],
                'regu'           => $r['regu'],
                'noHp'           => normalizePhone08($r['no_hp'] ?? '081234567890'),
                'jumlahPetugas'  => (int)$r['jumlah_petugas'],
                'progres'        => (int)$r['progres'],
                'status'         => $r['status'],
                'tanggal'        => $r['tanggal'],
                'foto'           => array_values(array_filter([
                    !empty($r['foto_sebelum']) ? ['src' => $r['foto_sebelum'], 'tahap' => 'Sebelum (0%)'] : null,
                    !empty($r['foto_proses'])  ? ['src' => $r['foto_proses'],  'tahap' => 'Proses (50%)'] : null,
                    !empty($r['foto_sesudah']) ? ['src' => $r['foto_sesudah'], 'tahap' => 'Sesudah (100%)'] : null,
                ]))
            ];
        }
        echo json_encode(['success' => true, 'data' => $formatted]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

if ($action === 'save_all_reports' || $action === 'sync_reports') {
    $raw = file_get_contents('php://input');
    $payload = json_decode($raw, true);
    if (!is_array($payload)) {
        echo json_encode(['success' => false, 'error' => 'Invalid JSON']);
        exit;
    }

    try {
        $conn->beginTransaction();
        $stmt = $conn->prepare("
            INSERT INTO `laporan` (
                `id`, `no_pekerjaan`, `minggu`, `rw`, `kat`, `jenis_pekerjaan`, 
                `judul`, `target_meter`, `alamat`, `lokasi_detail`, `kendala`, `petugas`, 
                `regu`, `no_hp`, `jumlah_petugas`, `progres`, `status`, 
                `foto_sebelum`, `foto_proses`, `foto_sesudah`, `tanggal`
            ) VALUES (
                :id, :no_pekerjaan, :minggu, :rw, :kat, :jenis_pekerjaan, 
                :judul, :target_meter, :alamat, :lokasi_detail, :kendala, :petugas, 
                :regu, :no_hp, :jumlah_petugas, :progres, :status, 
                :foto_sebelum, :foto_proses, :foto_sesudah, :tanggal
            ) ON DUPLICATE KEY UPDATE 
                `no_pekerjaan` = VALUES(`no_pekerjaan`),
                `minggu` = VALUES(`minggu`),
                `rw` = VALUES(`rw`),
                `kat` = VALUES(`kat`),
                `jenis_pekerjaan` = VALUES(`jenis_pekerjaan`),
                `judul` = VALUES(`judul`),
                `target_meter` = VALUES(`target_meter`),
                `alamat` = VALUES(`alamat`),
                `lokasi_detail` = VALUES(`lokasi_detail`),
                `kendala` = VALUES(`kendala`),
                `petugas` = VALUES(`petugas`),
                `regu` = VALUES(`regu`),
                `no_hp` = VALUES(`no_hp`),
                `jumlah_petugas` = VALUES(`jumlah_petugas`),
                `progres` = VALUES(`progres`),
                `status` = VALUES(`status`),
                `foto_sebelum` = IF(VALUES(`foto_sebelum`) != '', VALUES(`foto_sebelum`), `foto_sebelum`),
                `foto_proses` = IF(VALUES(`foto_proses`) != '', VALUES(`foto_proses`), `foto_proses`),
                `foto_sesudah` = IF(VALUES(`foto_sesudah`) != '', VALUES(`foto_sesudah`), `foto_sesudah`),
                `tanggal` = VALUES(`tanggal`)
        ");

        foreach ($payload as $r) {
            $id = $r['id'] ?? $r['noPekerjaan'] ?? ('PKJ-' . uniqid());
            $f0 = isset($r['foto'][0]['src']) ? $r['foto'][0]['src'] : '';
            $f1 = isset($r['foto'][1]['src']) ? $r['foto'][1]['src'] : '';
            $f2 = isset($r['foto'][2]['src']) ? $r['foto'][2]['src'] : '';

            $stmt->execute([
                ':id'             => $id,
                ':no_pekerjaan'    => $r['noPekerjaan'] ?? $id,
                ':minggu'         => $r['minggu'] ?? 'Laporan Harian',
                ':rw'             => $r['rw'] ?? 'RW 01',
                ':kat'            => $r['kat'] ?? 'kebersihan',
                ':jenis_pekerjaan' => $r['jenisPekerjaan'] ?? ($r['judul'] ?? 'Pekerjaan Lapangan'),
                ':judul'          => $r['judul'] ?? ($r['jenisPekerjaan'] ?? 'Pekerjaan Lapangan'),
                ':target_meter'   => (string)($r['targetMeter'] ?? ($r['target_meter'] ?? ($r['panjangTarget'] ?? ''))),
                ':alamat'         => $r['alamat'] ?? 'Wilayah Kel. Pekayon',
                ':lokasi_detail'   => $r['lokasiDetail'] ?? ($r['lokasi_detail'] ?? ''),
                ':kendala'        => $r['kendala'] ?? ($r['deskripsi'] ?? ''),
                ':petugas'        => $r['petugas'] ?? 'Petugas Lapangan',
                ':regu'           => $r['regu'] ?? 'Regu 1',
                ':no_hp'          => $r['noHp'] ?? ($r['no_hp'] ?? '081234567890'),
                ':jumlah_petugas'  => (int)($r['jumlahPetugas'] ?? ($r['jumlah_petugas'] ?? 4)),
                ':progres'        => (int)($r['progres'] ?? 0),
                ':status'         => $r['status'] ?? 'belum',
                ':foto_sebelum'   => $f0,
                ':foto_proses'    => $f1,
                ':foto_sesudah'   => $f2,
                ':tanggal'        => $r['tanggal'] ?? date('d M Y, H:i')
            ]);
        }

        $conn->commit();
        echo json_encode(['success' => true, 'count' => count($payload)]);
    } catch (Exception $e) {
        if ($conn->inTransaction()) $conn->rollBack();
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

if ($action === 'delete_report') {
    $id = $_GET['id'] ?? ($_POST['id'] ?? '');
    if (!$id) {
        echo json_encode(['success' => false, 'error' => 'ID laporan wajib disertakan']);
        exit;
    }
    try {
        $del = $conn->prepare("DELETE FROM `laporan` WHERE `id` = ? OR `no_pekerjaan` = ?");
        $del->execute([$id, $id]);
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

if ($action === 'delete_all_reports') {
    try {
        $conn->exec("DELETE FROM `laporan`");
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

if ($action === 'get_anggota') {
    try {
        // Auto-sync: pastikan seluruh petugas dari tabel users masuk ke anggota
        $stmtUsers = $conn->query("SELECT id, nama, no_hp FROM `users` WHERE `role` = 'petugas'");
        $usersList = $stmtUsers->fetchAll(PDO::FETCH_ASSOC);

        $stmtAll = $conn->query("SELECT * FROM `anggota` ORDER BY `id` ASC");
        $allAgt = $stmtAll->fetchAll(PDO::FETCH_ASSOC);

        $existingNames = [];
        $maxNum = 0;
        foreach ($allAgt as $a) {
            $existingNames[strtolower(trim($a['nama']))] = true;
            if (preg_match('/AGT-(\d+)/', $a['id'], $m)) {
                $maxNum = max($maxNum, intval($m[1]));
            }
        }

        $inserted = false;
        foreach ($usersList as $u) {
            $uKey = strtolower(trim($u['nama']));
            if (!isset($existingNames[$uKey])) {
                $maxNum++;
                $newId = 'AGT-' . str_pad($maxNum, 3, '0', STR_PAD_LEFT);
                $ins = $conn->prepare("INSERT INTO `anggota` (`id`, `nama`, `hp`, `role`, `regu_id`, `regu`, `wilayah`) VALUES (?, ?, ?, 'Anggota Regu', '', 'Belum Ditugaskan', '10 RW')");
                $ins->execute([$newId, $u['nama'], $u['no_hp']]);
                $inserted = true;
            }
        }

        if ($inserted) {
            $stmt = $conn->query("SELECT * FROM `anggota` ORDER BY `id` ASC");
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $rows = $allAgt;
        }

        $formatted = [];
        foreach ($rows as $r) {
            $formatted[] = [
                'id'       => $r['id'],
                'nama'     => $r['nama'],
                'hp'       => normalizePhone08($r['hp'] ?? ''),
                'role'     => $r['role'],
                'reguId'   => $r['regu_id'] ?: '',
                'regu_id'  => $r['regu_id'] ?: '',
                'regu'     => $r['regu'] ?: 'Belum Ditugaskan',
                'wilayah'  => $r['wilayah'] ?: '10 RW',
                'status'   => 'aktif'
            ];
        }
        echo json_encode(['success' => true, 'data' => $formatted]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

if ($action === 'save_all_anggota') {
    $raw = file_get_contents('php://input');
    $payload = json_decode($raw, true);
    if (!is_array($payload)) {
        echo json_encode(['success' => false, 'error' => 'Invalid JSON']);
        exit;
    }
    try {
        $conn->beginTransaction();
        $reguMap = $conn->query("SELECT id, nama FROM `regu`")->fetchAll(PDO::FETCH_KEY_PAIR) ?: [];
        $reguWilMap = $conn->query("SELECT id, wilayah FROM `regu`")->fetchAll(PDO::FETCH_KEY_PAIR) ?: [];

        $stmt = $conn->prepare("
            INSERT INTO `anggota` (`id`, `nama`, `hp`, `role`, `regu_id`, `regu`, `wilayah`)
            VALUES (:id, :nama, :hp, :role, :regu_id, :regu, :wilayah)
            ON DUPLICATE KEY UPDATE
                `nama` = VALUES(`nama`),
                `hp` = VALUES(`hp`),
                `role` = VALUES(`role`),
                `regu_id` = VALUES(`regu_id`),
                `regu` = VALUES(`regu`),
                `wilayah` = VALUES(`wilayah`)
        ");

        $stmtFindUser = $conn->prepare("SELECT id, role, username FROM `users` WHERE `no_hp` = ? OR `username` = ? OR LOWER(TRIM(`nama`)) = LOWER(TRIM(?)) LIMIT 1");
        $stmtInsUser  = $conn->prepare("INSERT INTO `users` (`nama`, `no_hp`, `username`, `password`, `role`, `wilayah`) VALUES (?, ?, ?, ?, 'petugas', ?)");
        $stmtUpdUser  = $conn->prepare("UPDATE `users` SET `nama` = ?, `no_hp` = ?, `username` = ? WHERE `id` = ?");
        $defaultKetuaHash = null;

        foreach ($payload as $a) {
            $reguId = trim((string)($a['reguId'] ?? ($a['regu_id'] ?? '')));
            if ($reguId !== '' && isset($reguMap[$reguId])) {
                $reguName = $reguMap[$reguId];
                $wilVal = $reguWilMap[$reguId] ?? ($a['wilayah'] ?? '-');
            } else {
                $reguName = 'Belum Ditugaskan';
                $wilVal = '-';
            }
            $namaVal = trim((string)($a['nama'] ?? 'Petugas'));
            $hpVal = normalizePhone08($a['hp'] ?? '');
            $roleVal = $a['role'] ?? 'Anggota Regu';

            $stmt->execute([
                ':id'      => $a['id'] ?? ('AGT-' . uniqid()),
                ':nama'    => $namaVal,
                ':hp'      => $hpVal,
                ':role'    => $roleVal,
                ':regu_id' => $reguId,
                ':regu'    => $reguName,
                ':wilayah' => $wilVal
            ]);

            // Sinkronkan otomatis akun login untuk Ketua Regu (ID = No. WA, Password default = Ketua123)
            if ($roleVal === 'Ketua Regu' && $hpVal !== '') {
                $stmtFindUser->execute([$hpVal, $hpVal, $namaVal]);
                $uRow = $stmtFindUser->fetch(PDO::FETCH_ASSOC);
                if (!$uRow) {
                    if ($defaultKetuaHash === null) {
                        $defaultKetuaHash = password_hash('Ketua123', PASSWORD_DEFAULT);
                    }
                    $stmtInsUser->execute([$namaVal, $hpVal, $hpVal, $defaultKetuaHash, $wilVal]);
                } elseif (($uRow['role'] ?? '') !== 'admin') {
                    $newUname = preg_match('/^[0-9]+$/', (string)$uRow['username']) ? $hpVal : $uRow['username'];
                    $stmtUpdUser->execute([$namaVal, $hpVal, $newUname, $uRow['id']]);
                }
            }

            // Sinkronkan nama ketua regu ke tabel regu secara otomatis
            if ($roleVal === 'Ketua Regu' && $reguId !== '') {
                try {
                    $updReguKetua = $conn->prepare("UPDATE `regu` SET `ketua` = ?, `no_hp` = ? WHERE `id` = ?");
                    $updReguKetua->execute([$namaVal, $hpVal, $reguId]);
                } catch (Exception $e) {}
            }
        }

        // Hapus dari database anggota yang sudah tidak ada di payload
        if (!empty($payload)) {
            $keepIds = array_filter(array_column($payload, 'id'));
            if (!empty($keepIds)) {
                $placeholders = implode(',', array_fill(0, count($keepIds), '?'));
                $delStmt = $conn->prepare("DELETE FROM `anggota` WHERE `id` NOT IN ($placeholders)");
                $delStmt->execute(array_values($keepIds));
            }
        }
        $conn->commit();
        echo json_encode(['success' => true, 'count' => count($payload)]);
    } catch (Exception $e) {
        if ($conn->inTransaction()) $conn->rollBack();
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

if ($action === 'delete_anggota') {
    $id = $_GET['id'] ?? ($_POST['id'] ?? '');
    $nama = $_GET['nama'] ?? ($_POST['nama'] ?? '');
    if (!$id && !$nama) {
        echo json_encode(['success' => false, 'error' => 'ID atau Nama wajib disertakan']);
        exit;
    }
    try {
        $conn->beginTransaction();
        if ($id) {
            $delAgt = $conn->prepare("DELETE FROM `anggota` WHERE `id` = ?");
            $delAgt->execute([$id]);
        }
        if ($nama) {
            $delAgtName = $conn->prepare("DELETE FROM `anggota` WHERE LOWER(TRIM(`nama`)) = LOWER(TRIM(?))");
            $delAgtName->execute([$nama]);
            $delUser = $conn->prepare("DELETE FROM `users` WHERE LOWER(TRIM(`nama`)) = LOWER(TRIM(?)) AND `role` != 'admin'");
            $delUser->execute([$nama]);
            $updReg = $conn->prepare("UPDATE `regu` SET `ketua` = '' WHERE LOWER(TRIM(`ketua`)) = LOWER(TRIM(?))");
            $updReg->execute([$nama]);
        }
        $conn->commit();
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        if ($conn->inTransaction()) $conn->rollBack();
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

if ($action === 'get_regu') {
    try {
        $stmt = $conn->query("SELECT * FROM `regu` ORDER BY `id` ASC");
        $rows = $stmt->fetchAll();
        $formatted = [];
        foreach ($rows as $r) {
            $formatted[] = [
                'id'        => $r['id'],
                'nama'      => $r['nama'],
                'jenis'     => $r['jenis'],
                'ketua'     => $r['ketua'],
                'noHp'      => normalizePhone08($r['no_hp'] ?? ''),
                'no_hp'     => normalizePhone08($r['no_hp'] ?? ''),
                'wilayah'   => $r['wilayah'],
                'deskripsi' => $r['deskripsi']
            ];
        }
        echo json_encode(['success' => true, 'data' => $formatted]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

if ($action === 'save_all_regu') {
    $raw = file_get_contents('php://input');
    $payload = json_decode($raw, true);
    if (!is_array($payload)) {
        echo json_encode(['success' => false, 'error' => 'Invalid JSON']);
        exit;
    }
    try {
        $conn->beginTransaction();
        $stmt = $conn->prepare("
            INSERT INTO `regu` (`id`, `nama`, `jenis`, `ketua`, `no_hp`, `wilayah`, `deskripsi`)
            VALUES (:id, :nama, :jenis, :ketua, :no_hp, :wilayah, :deskripsi)
            ON DUPLICATE KEY UPDATE
                `nama` = VALUES(`nama`),
                `jenis` = VALUES(`jenis`),
                `ketua` = VALUES(`ketua`),
                `no_hp` = VALUES(`no_hp`),
                `wilayah` = VALUES(`wilayah`),
                `deskripsi` = VALUES(`deskripsi`)
        ");
        foreach ($payload as $r) {
            $stmt->execute([
                ':id'        => $r['id'] ?? ('REG-' . uniqid()),
                ':nama'      => $r['nama'] ?? 'Regu Lapangan',
                ':jenis'     => $r['jenis'] ?? 'Jalan',
                ':ketua'     => $r['ketua'] ?? 'Ketua Regu',
                ':no_hp'     => $r['noHp'] ?? ($r['no_hp'] ?? '081234567890'),
                ':wilayah'   => $r['wilayah'] ?? '10 RW (RW 01 - RW 10)',
                ':deskripsi' => $r['deskripsi'] ?? 'Operasional Regu Lapangan'
            ]);

            // Sinkronkan role anggota menjadi Ketua Regu jika ditunjuk sebagai ketua regu
            if (!empty($r['ketua']) && $r['ketua'] !== '-' && !empty($r['id'])) {
                try {
                    $updAgtRole = $conn->prepare("UPDATE `anggota` SET `role` = 'Ketua Regu' WHERE `regu_id` = ? AND LOWER(TRIM(`nama`)) = LOWER(TRIM(?))");
                    $updAgtRole->execute([$r['id'], $r['ketua']]);
                } catch (Exception $e) {}
            }
        }

        if (!empty($payload)) {
            $keepReguIds = array_filter(array_column($payload, 'id'));
            if (!empty($keepReguIds)) {
                $placeholders = implode(',', array_fill(0, count($keepReguIds), '?'));
                $delReguStmt = $conn->prepare("DELETE FROM `regu` WHERE `id` NOT IN ($placeholders)");
                $delReguStmt->execute(array_values($keepReguIds));
            }
        }
        $conn->commit();
        echo json_encode(['success' => true, 'count' => count($payload)]);
    } catch (Exception $e) {
        if ($conn->inTransaction()) $conn->rollBack();
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

if ($action === 'sync_realtime') {
    try {
        // 1. Sinkronisasi Petugas users -> anggota jika ada pendaftar baru
        $stmtUsers = $conn->query("SELECT id, nama, no_hp FROM `users` WHERE `role` = 'petugas'");
        $usersList = $stmtUsers->fetchAll(PDO::FETCH_ASSOC);

        $stmtAll = $conn->query("SELECT * FROM `anggota` ORDER BY `id` ASC");
        $allAgt = $stmtAll->fetchAll(PDO::FETCH_ASSOC);

        $existingNames = [];
        $maxNum = 0;
        foreach ($allAgt as $a) {
            $existingNames[strtolower(trim($a['nama']))] = true;
            if (preg_match('/AGT-(\d+)/', $a['id'], $m)) {
                $maxNum = max($maxNum, intval($m[1]));
            }
        }

        $inserted = false;
        foreach ($usersList as $u) {
            $uKey = strtolower(trim($u['nama']));
            if (!isset($existingNames[$uKey])) {
                $maxNum++;
                $newId = 'AGT-' . str_pad($maxNum, 3, '0', STR_PAD_LEFT);
                $ins = $conn->prepare("INSERT INTO `anggota` (`id`, `nama`, `hp`, `role`, `regu_id`, `regu`, `wilayah`) VALUES (?, ?, ?, 'Anggota Regu', '', 'Belum Ditugaskan', '10 RW')");
                $ins->execute([$newId, $u['nama'], $u['no_hp']]);
                $inserted = true;
            }
        }

        if ($inserted) {
            $stmt = $conn->query("SELECT * FROM `anggota` ORDER BY `id` ASC");
            $rowsAgt = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $rowsAgt = $allAgt;
        }

        $formattedAnggota = [];
        foreach ($rowsAgt as $r) {
            $formattedAnggota[] = [
                'id'       => $r['id'],
                'nama'     => $r['nama'],
                'hp'       => normalizePhone08($r['hp'] ?? ''),
                'role'     => $r['role'],
                'reguId'   => $r['regu_id'] ?: '',
                'regu_id'  => $r['regu_id'] ?: '',
                'regu'     => $r['regu'] ?: 'Belum Ditugaskan',
                'wilayah'  => $r['wilayah'] ?: '10 RW',
                'status'   => 'aktif'
            ];
        }

        // 2. Data Regu
        $stmtReg = $conn->query("SELECT * FROM `regu` ORDER BY `id` ASC");
        $rowsReg = $stmtReg->fetchAll(PDO::FETCH_ASSOC);
        $formattedRegu = [];
        foreach ($rowsReg as $r) {
            $rgId = $r['id'];
            $memberIds = [];
            foreach ($formattedAnggota as $ag) {
                if ($ag['reguId'] === $rgId || $ag['regu'] === $r['nama']) {
                    $memberIds[] = $ag['id'];
                }
            }
            $formattedRegu[] = [
                'id'         => $r['id'],
                'nama'       => $r['nama'],
                'jenis'      => $r['jenis'],
                'ketua'      => $r['ketua'],
                'noHp'       => normalizePhone08($r['no_hp'] ?? ''),
                'no_hp'      => normalizePhone08($r['no_hp'] ?? ''),
                'wilayah'    => $r['wilayah'],
                'deskripsi'  => $r['deskripsi'],
                'anggotaIds' => $memberIds
            ];
        }

        // 3. Data Laporan
        $stmtRep = $conn->query("SELECT * FROM `laporan` ORDER BY `created_at` DESC");
        $rowsRep = $stmtRep->fetchAll(PDO::FETCH_ASSOC);
        $formattedReports = [];
        foreach ($rowsRep as $r) {
            $formattedReports[] = [
                'id'             => $r['id'],
                'noPekerjaan'    => $r['no_pekerjaan'],
                'minggu'         => $r['minggu'],
                'rw'             => $r['rw'],
                'kat'            => $r['kat'],
                'jenisPekerjaan' => $r['jenis_pekerjaan'],
                'judul'          => $r['judul'],
                'targetMeter'    => $r['target_meter'] ?? '',
                'target_meter'   => $r['target_meter'] ?? '',
                'alamat'         => $r['alamat'],
                'lokasiDetail'   => $r['lokasi_detail'],
                'kendala'        => $r['kendala'],
                'petugas'        => $r['petugas'],
                'regu'           => $r['regu'],
                'noHp'           => normalizePhone08($r['no_hp'] ?? '081234567890'),
                'jumlahPetugas'  => (int)$r['jumlah_petugas'],
                'progres'        => (int)$r['progres'],
                'status'         => $r['status'],
                'tanggal'        => $r['tanggal'],
                'foto'           => array_values(array_filter([
                    !empty($r['foto_sebelum']) ? ['src' => $r['foto_sebelum'], 'tahap' => 'Sebelum (0%)'] : null,
                    !empty($r['foto_proses'])  ? ['src' => $r['foto_proses'],  'tahap' => 'Proses (50%)'] : null,
                    !empty($r['foto_sesudah']) ? ['src' => $r['foto_sesudah'], 'tahap' => 'Sesudah (100%)'] : null,
                ]))
            ];
        }

        echo json_encode([
            'success'     => true,
            'reports'     => $formattedReports,
            'anggota'     => $formattedAnggota,
            'regu'        => $formattedRegu,
            'server_time' => time()
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

if ($action === 'update_admin_credentials') {
    try {
        $raw = file_get_contents('php://input');
        $payload = json_decode($raw, true) ?: $_POST;

        $adminId = $_SESSION['user']['id'] ?? null;
        $sessionUsername = $_SESSION['user']['username'] ?? '';

        $stmt = $conn->prepare("SELECT * FROM `users` WHERE `role` = 'admin' AND (`id` = ? OR LOWER(`username`) = LOWER(?)) LIMIT 1");
        $stmt->execute([$adminId, $sessionUsername]);
        $adminRow = $stmt->fetch();

        if (!$adminRow) {
            echo json_encode(['success' => false, 'error' => 'Data akun administrator tidak ditemukan di sistem.']);
            exit;
        }

        $namaBaru = trim($payload['nama'] ?? $adminRow['nama']);
        $usernameBaru = strtolower(trim($payload['username'] ?? $adminRow['username']));
        $currentPass = (string)($payload['current_password'] ?? '');
        $newPass = (string)($payload['new_password'] ?? '');
        $confirmPass = (string)($payload['confirm_password'] ?? '');

        if (empty($currentPass)) {
            echo json_encode(['success' => false, 'error' => 'Masukkan kata sandi saat ini untuk memverifikasi perubahan!']);
            exit;
        }

        if (!password_verify($currentPass, $adminRow['password'])) {
            echo json_encode(['success' => false, 'error' => 'Kata sandi saat ini yang Anda masukkan salah!']);
            exit;
        }

        if ($usernameBaru !== strtolower($adminRow['username'])) {
            if (strlen($usernameBaru) < 3 || strlen($usernameBaru) > 30 || !preg_match('/^[a-z0-9_]+$/', $usernameBaru)) {
                echo json_encode(['success' => false, 'error' => 'Username baru harus 3-30 karakter, huruf kecil atau angka tanpa spasi!']);
                exit;
            }

            $chkU = $conn->prepare("SELECT `id` FROM `users` WHERE LOWER(`username`) = LOWER(?) AND `id` != ? LIMIT 1");
            $chkU->execute([$usernameBaru, $adminRow['id']]);
            if ($chkU->fetch()) {
                echo json_encode(['success' => false, 'error' => 'Username baru tersebut sudah digunakan oleh akun lain!']);
                exit;
            }
        }

        $finalHash = $adminRow['password'];
        if (!empty($newPass)) {
            if (strlen($newPass) < 6) {
                echo json_encode(['success' => false, 'error' => 'Kata sandi baru minimal 6 karakter!']);
                exit;
            }
            if ($newPass !== $confirmPass) {
                echo json_encode(['success' => false, 'error' => 'Konfirmasi kata sandi baru tidak sesuai!']);
                exit;
            }
            $finalHash = password_hash($newPass, PASSWORD_DEFAULT);
        }

        if (empty($namaBaru)) {
            $namaBaru = $adminRow['nama'];
        }

        $stmtUpd = $conn->prepare("UPDATE `users` SET `nama` = ?, `username` = ?, `password` = ? WHERE `id` = ?");
        $stmtUpd->execute([$namaBaru, $usernameBaru, $finalHash, $adminRow['id']]);

        $_SESSION['user']['nama'] = $namaBaru;
        $_SESSION['user']['username'] = $usernameBaru;

        echo json_encode([
            'success' => true,
            'message' => 'Kredensial administrator berhasil diperbarui! Silakan gunakan username & password baru saat login berikutnya.',
            'username' => $usernameBaru,
            'nama' => $namaBaru
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => 'Gagal memperbarui database: ' . $e->getMessage()]);
    }
    exit;
}

echo json_encode(['success' => true, 'message' => 'SILAP API Operational']);

