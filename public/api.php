<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/koneksi.php';

$action = $_GET['action'] ?? $_POST['action'] ?? '';

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
                'foto'           => [
                    ['src' => $r['foto_sebelum'] ?? '', 'tahap' => 'Sebelum (0%)'],
                    ['src' => $r['foto_proses'] ?? '', 'tahap' => 'Proses (' . $r['progres'] . '%)'],
                    ['src' => $r['foto_sesudah'] ?? '', 'tahap' => 'Sesudah (100%)']
                ]
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
        foreach ($payload as $a) {
            $reguId = $a['reguId'] ?? ($a['regu_id'] ?? '');
            $reguName = $a['regu'] ?? '';
            if (empty($reguName)) {
                if ($reguId === 'REG-01') $reguName = 'Regu Jalan';
                elseif ($reguId === 'REG-02') $reguName = 'Regu Taman';
                elseif ($reguId === 'REG-03') $reguName = 'Regu Kantor';
                else $reguName = 'Belum Ditugaskan';
            }
            $stmt->execute([
                ':id'      => $a['id'] ?? ('AGT-' . uniqid()),
                ':nama'    => $a['nama'] ?? 'Petugas',
                ':hp'      => $a['hp'] ?? '081234567890',
                ':role'    => $a['role'] ?? 'Anggota Regu',
                ':regu_id' => $reguId,
                ':regu'    => $reguName,
                ':wilayah' => $a['wilayah'] ?? '10 RW'
            ]);
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
                'foto'           => [
                    ['src' => $r['foto_sebelum'] ?? '', 'tahap' => 'Sebelum (0%)'],
                    ['src' => $r['foto_proses'] ?? '', 'tahap' => 'Proses (' . $r['progres'] . '%)'],
                    ['src' => $r['foto_sesudah'] ?? '', 'tahap' => 'Sesudah (100%)']
                ]
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

echo json_encode(['success' => true, 'message' => 'SILAP API Operational']);

