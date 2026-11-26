<?php
/**
 * SILAP – Sistem Informasi Laporan PPSU
 * Halaman Autentikasi Dinamis (Login & Registrasi)
 */
session_start();
require_once __DIR__ . '/koneksi.php';

// Jika sudah login, langsung arahkan sesuai peran (role)
if (isset($_SESSION['user'])) {
    if ($_SESSION['user']['role'] === 'admin') {
        header("Location: admin.php");
        exit;
    } else {
        header("Location: user.php");
        exit;
    }
}

$error_login = '';
$error_register = '';
$success_register = '';

// Deteksi jika ada pesan dari redirect (misal: setelah logout)
$msg_param = $_GET['msg'] ?? '';
$info_banner = '';
if ($msg_param === 'logout_berhasil') {
    $info_banner = 'Anda telah berhasil keluar dari sistem.';
} elseif ($msg_param === 'harus_login') {
    $info_banner = 'Sesi Anda telah berakhir. Silakan masuk kembali.';
} elseif ($msg_param === 'unauthorized') {
    $info_banner = 'Halaman tersebut membutuhkan hak akses Administrator.';
} elseif ($msg_param === 'deleted') {
    $info_banner = 'Akun Anda telah dinonaktifkan atau dihapus oleh Administrator.';
}

// ==========================================================
// HANDLE AJAX / API REQUESTS (JSON)
// ==========================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $is_ajax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') 
               || (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false)
               || isset($_POST['ajax']);

    $action = $_POST['action'] ?? 'login';

    // --------------------------------------------------------
    // 1. PROSES LOGIN
    // --------------------------------------------------------
    if ($action === 'login') {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $normalizedPhone = str_replace(['-', ' '], '', $username);
        if (strpos($normalizedPhone, '+62') === 0) {
            $normalizedPhone = '0' . substr($normalizedPhone, 3);
        } elseif (strpos($normalizedPhone, '62') === 0) {
            $normalizedPhone = '0' . substr($normalizedPhone, 2);
        }

        if (empty($username) || empty($password)) {
            $err_msg = 'Mohon masukkan nomor WA / username dan kata sandi!';
            if ($is_ajax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => $err_msg]);
                exit;
            }
            $error_login = $err_msg;
        } else {
            // Cari pengguna berdasarkan username ATAU nomor telepon (termasuk normalisasi WA 08xx)
            $stmt = $conn->prepare("SELECT * FROM users WHERE LOWER(username) = LOWER(?) OR no_hp = ? OR no_hp = ? LIMIT 1");
            $stmt->execute([$username, $username, $normalizedPhone]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                // Wajib: selain Admin, hanya Ketua Regu aktif yang diperbolehkan login
                if ($user['role'] !== 'admin') {
                    $chkKetua = $conn->prepare("SELECT id FROM anggota WHERE (LOWER(TRIM(nama)) = LOWER(TRIM(?)) OR hp = ?) AND role = 'Ketua Regu' LIMIT 1");
                    $chkKetua->execute([$user['nama'], $user['no_hp']]);
                    $isKetua = (bool)$chkKetua->fetch();
                    if (!$isKetua) {
                        $chkRegu = $conn->prepare("SELECT id FROM regu WHERE LOWER(TRIM(ketua)) = LOWER(TRIM(?)) OR no_hp = ? LIMIT 1");
                        $chkRegu->execute([$user['nama'], $user['no_hp']]);
                        $isKetua = (bool)$chkRegu->fetch();
                    }
                    if (!$isKetua) {
                        $err_msg = 'Akses ditolak: Hanya Ketua Regu yang diizinkan masuk ke sistem!';
                        if ($is_ajax) {
                            header('Content-Type: application/json');
                            echo json_encode(['success' => false, 'message' => $err_msg]);
                            exit;
                        }
                        $error_login = $err_msg;
                        $user = null;
                    }
                }
            }

            if ($user && password_verify($password, $user['password'])) {
                // Login Berhasil -> Simpan Sesi PHP
                $_SESSION['user'] = [
                    'id'       => $user['id'],
                    'nama'     => $user['nama'],
                    'username' => $user['username'],
                    'no_hp'    => $user['no_hp'],
                    'role'     => $user['role'],
                    'wilayah'  => $user['wilayah'] ?? 'jakpus',
                ];

                $redirect_url = ($user['role'] === 'admin') ? 'admin.php' : 'user.php';

                if ($is_ajax) {
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success'  => true,
                        'message'  => 'Login berhasil! Mengalihkan...',
                        'redirect' => $redirect_url,
                        'user'     => $_SESSION['user']
                    ]);
                    exit;
                }

                header("Location: " . $redirect_url);
                exit;
            } else {
                if (empty($error_login)) {
                    $err_msg = 'Nomor WA / username atau kata sandi tidak cocok!';
                    if ($is_ajax) {
                        header('Content-Type: application/json');
                        echo json_encode(['success' => false, 'message' => $err_msg]);
                        exit;
                    }
                    $error_login = $err_msg;
                }
            }
        }
    }

    // --------------------------------------------------------
    // 2. PROSES REGISTRASI AKUN BARU
    // --------------------------------------------------------
    elseif ($action === 'register') {
        $name     = trim($_POST['name'] ?? '');
        $username = strtolower(trim($_POST['username'] ?? ''));
        $rawPhone = trim($_POST['phone'] ?? '');
        $phone    = str_replace(['-', ' '], '', $rawPhone);
        $password = $_POST['password'] ?? '';
        $confirm  = $_POST['confirm_password'] ?? '';

        $reg_err = '';

        if (empty($name) || empty($username) || empty($rawPhone) || empty($password) || empty($confirm)) {
            $reg_err = 'Mohon lengkapi semua data pendaftaran (termasuk nama pengguna/username)!';
        } else {
            $chkKetuaReg = $conn->prepare("SELECT id FROM anggota WHERE hp = ? AND role = 'Ketua Regu' LIMIT 1");
            $chkKetuaReg->execute([$phone]);
            $isRegisteredKetua = (bool)$chkKetuaReg->fetch();
            if (!$isRegisteredKetua) {
                $chkReguMaster = $conn->prepare("SELECT id FROM regu WHERE no_hp = ? LIMIT 1");
                $chkReguMaster->execute([$phone]);
                $isRegisteredKetua = (bool)$chkReguMaster->fetch();
            }
            if (!$isRegisteredKetua) {
                $reg_err = 'Pendaftaran ditolak: Hanya nomor WA Ketua Regu terdaftar yang dapat memiliki akun login!';
            }
        }

        if (!empty($reg_err)) {
            // already handled
        } elseif (strpos($rawPhone, '+62') === 0 || strpos($rawPhone, '62') === 0 || strpos($rawPhone, '+') === 0) {
            $reg_err = 'Format "+62" ditolak! Nomor telepon wajib diawali dengan "08" (contoh: 081234567890).';
        } elseif (!preg_match('/^08[0-9]{8,13}$/', $phone)) {
            $reg_err = 'Nomor telepon wajib diawali dengan "08" dan berisi 10-15 digit angka (contoh: 081234567890)!';
        } elseif (strlen($username) < 3 || strlen($username) > 30 || !preg_match('/^[a-z0-9_]+$/', $username)) {
            $reg_err = 'Nama pengguna (username) harus 3-30 karakter, huruf kecil atau angka tanpa spasi!';
        } elseif (strlen($password) < 6) {
            $reg_err = 'Kata sandi minimal 6 karakter!';
        } elseif (preg_match('/[A-Z]/', $password)) {
            $reg_err = 'Kata sandi harus huruf kecil semua (tidak boleh huruf besar/kapital)!';
        } elseif (!preg_match('/[a-z]/', $password)) {
            $reg_err = 'Kata sandi harus mengandung kombinasi huruf kecil!';
        } elseif (!preg_match('/[0-9]/', $password)) {
            $reg_err = 'Kata sandi wajib menyertakan kombinasi angka (0-9)!';
        } elseif ($password !== $confirm) {
            $reg_err = 'Kata sandi dan konfirmasi kata sandi tidak cocok!';
        } else {
            // Cek apakah username sudah dipakai
            $stmtUser = $conn->prepare("SELECT id FROM users WHERE LOWER(username) = LOWER(?) LIMIT 1");
            $stmtUser->execute([$username]);
            if ($stmtUser->fetch()) {
                $reg_err = "Nama pengguna (username) \"{$username}\" sudah digunakan! Silakan pilih nama lain.";
            } else {
                // Cek apakah nomor telepon sudah pernah terdaftar
                $stmtPhone = $conn->prepare("SELECT id FROM users WHERE no_hp = ? LIMIT 1");
                $stmtPhone->execute([$phone]);
                if ($stmtPhone->fetch()) {
                    $reg_err = 'Nomor telepon tersebut sudah terdaftar di sistem!';
                } else {
                    // Enkripsi kata sandi
                    $hash = password_hash($password, PASSWORD_DEFAULT);

                    $insert = $conn->prepare("INSERT INTO users (nama, no_hp, username, password, role, wilayah) VALUES (?, ?, ?, ?, 'petugas', 'jakpus')");
                    $success = $insert->execute([$name, $phone, $username, $hash]);

                    if ($success) {
                        try {
                            $stmtAllAgt = $conn->query("SELECT id FROM anggota ORDER BY id ASC");
                            $maxNum = 0;
                            while ($rAgt = $stmtAllAgt->fetch(PDO::FETCH_ASSOC)) {
                                if (preg_match('/AGT-(\d+)/', $rAgt['id'], $m)) {
                                    $maxNum = max($maxNum, intval($m[1]));
                                }
                            }
                            $maxNum++;
                            $newAgtId = 'AGT-' . str_pad($maxNum, 3, '0', STR_PAD_LEFT);
                            $stmtInsAgt = $conn->prepare("INSERT INTO anggota (id, nama, hp, role, regu_id, regu, wilayah) VALUES (?, ?, ?, 'Anggota Regu', '', 'Belum Ditugaskan', '10 RW') ON DUPLICATE KEY UPDATE nama = VALUES(nama)");
                            $stmtInsAgt->execute([$newAgtId, $name, $phone]);
                        } catch (Exception $e) {}

                        $success_msg = "Pendaftaran akun \"{$username}\" ({$name}) berhasil! Silakan masuk.";
                        if ($is_ajax) {
                            header('Content-Type: application/json');
                            echo json_encode([
                                'success'  => true,
                                'message'  => $success_msg,
                                'username' => $username,
                                'phone'    => $phone
                            ]);
                            exit;
                        }
                        $success_register = $success_msg;
                    } else {
                        $reg_err = 'Gagal menyimpan pendaftaran, silakan coba lagi.';
                    }
                }
            }
        }

        if (!empty($reg_err)) {
            if ($is_ajax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => $reg_err]);
                exit;
            }
            $error_register = $reg_err;
        }
    }

    // --------------------------------------------------------
    // 3. PROSES RESET PASSWORD MANDIRI (VERIFIKASI NO. HP)
    // --------------------------------------------------------
    elseif ($action === 'reset_password') {
        $username = trim($_POST['username'] ?? '');
        $phone    = trim($_POST['phone'] ?? '');
        $new_pwd  = $_POST['password'] ?? '';
        $confirm  = $_POST['confirm_password'] ?? '';

        $reset_err = '';

        if (empty($username) || empty($phone) || empty($new_pwd) || empty($confirm)) {
            $reset_err = 'Mohon lengkapi semua kolom verifikasi & password baru!';
        } elseif (strlen($new_pwd) < 6) {
            $reset_err = 'Kata sandi baru minimal 6 karakter!';
        } elseif (preg_match('/[A-Z]/', $new_pwd)) {
            $reset_err = 'Kata sandi harus huruf kecil semua (tanpa huruf besar/kapital)!';
        } elseif (!preg_match('/[a-z]/', $new_pwd)) {
            $reset_err = 'Kata sandi harus mengandung kombinasi huruf kecil!';
        } elseif (!preg_match('/[0-9]/', $new_pwd)) {
            $reset_err = 'Kata sandi wajib menyertakan kombinasi angka (0-9)!';
        } elseif ($new_pwd !== $confirm) {
            $reset_err = 'Kata sandi dan konfirmasi kata sandi baru tidak cocok!';
        } else {
            // Cek apakah username dan nomor hp cocok di database
            $stmt = $conn->prepare("SELECT id, nama, role FROM users WHERE (LOWER(username) = LOWER(?) OR no_hp = ?) AND no_hp = ? LIMIT 1");
            $stmt->execute([$username, $username, $phone]);
            $targetUser = $stmt->fetch();

            if (!$targetUser) {
                $reset_err = 'Kombinasi Username dan Nomor HP tidak cocok atau tidak terdaftar!';
            } elseif ($targetUser['role'] === 'admin') {
                $reset_err = 'Akun Administrator tidak dapat di-reset mandiri. Hubungi Koordinator/Kelurahan!';
            } else {
                $hash = password_hash($new_pwd, PASSWORD_DEFAULT);
                $update = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                $success = $update->execute([$hash, $targetUser['id']]);

                if ($success) {
                    if ($is_ajax) {
                        header('Content-Type: application/json');
                        echo json_encode([
                            'success' => true,
                            'message' => 'Kata sandi berhasil diperbarui! Silakan masuk dengan kata sandi baru Anda.'
                        ]);
                        exit;
                    }
                    $info_banner = 'Kata sandi berhasil direset! Silakan login.';
                } else {
                    $reset_err = 'Gagal memperbarui kata sandi. Silakan coba lagi.';
                }
            }
        }

        if (!empty($reset_err)) {
            if ($is_ajax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => $reset_err]);
                exit;
            }
            $error_login = $reset_err;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
  <title>SILAP – Masuk Sistem Laporan PPSU</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@500;600;700;800;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="style.css?v=<?= time() ?>">
  <link rel="icon" type="image/png" href="foto/silap.png">
</head>
<body class="login-page-body">

<div class="login-wrapper">
  <div class="login-card-container" id="auth-container">
    
    <!-- LEFT BRIEFING ART PANEL (TETAP SAMA UNTUK LOGIN MAUPUN REGISTER) -->
    <div class="login-art-panel" id="art-panel">
      <div class="art-glow-backdrop"></div>

      <div class="art-content">
        <!-- Logo & Branding SILAP -->
        <div class="art-brand-header">
          <img src="foto/silap.png" alt="Logo SILAP" class="art-brand-logo">
          <div>
            <div class="art-brand-title">SILAP</div>
            <div class="art-brand-sub">Sistem Laporan PPSU • Pekayon</div>
            <div style="margin-top:6px;display:inline-flex;align-items:center;gap:8px;background:rgba(255,255,255,0.2);border:1px solid rgba(255,255,255,0.38);color:#FFFFFF;font-size:0.74rem;font-weight:700;padding:4px 12px 4px 6px;border-radius:999px;backdrop-filter:blur(4px);line-height:1.2;">
              <img src="foto/gunadarma.png" alt="Logo Universitas Gunadarma" style="width:24px;height:24px;object-fit:contain;background:#FFFFFF;border-radius:50%;padding:2px;flex-shrink:0;box-shadow:0 1px 3px rgba(0,0,0,0.12);">
              <span>Aplikasi ini dibuat oleh Mahasiswa Gunadarma</span>
            </div>
          </div>
        </div>

        <!-- Human-friendly Briefing Checklist Card -->
        <div class="human-checklist-card">
          <div class="checklist-badge">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
            <span>PANDUAN LAPORAN REGU</span>
          </div>
          <h2 class="checklist-title">Hal Penting Sebelum Kirim Laporan</h2>
          <p class="checklist-desc">Pastikan 6 informasi wajib berikut sudah lengkap dan jelas agar laporan cepat diverifikasi pihak kelurahan:</p>

          <div class="checklist-items-wrap">
            <div class="checklist-row">
              <span class="chk-num">1</span>
              <div class="chk-info">
                <strong>Nomor Pekerjaan</strong>
                <span>Nomor penugasan resmi dari kelurahan</span>
              </div>
            </div>

            <div class="checklist-row">
              <span class="chk-num">2</span>
              <div class="chk-info">
                <strong>Tanggal & Waktu</strong>
                <span>Waktu riil pelaksanaan kegiatan</span>
              </div>
            </div>

            <div class="checklist-row">
              <span class="chk-num">3</span>
              <div class="chk-info">
                <strong>Jenis Pekerjaan</strong>
                <span>Jalan, saluran got, taman, sampah</span>
              </div>
            </div>

            <div class="checklist-row">
              <span class="chk-num">4</span>
              <div class="chk-info">
                <strong>Alamat Lokasi Lengkap</strong>
                <span>Nama jalan, RT/RW, dan patokan lokasi</span>
              </div>
            </div>

            <div class="checklist-row">
              <span class="chk-num">5</span>
              <div class="chk-info">
                <strong>Foto Dokumentasi</strong>
                <span>Bukti foto: sebelum, saat kerja, & selesai</span>
              </div>
            </div>

            <div class="checklist-row">
              <span class="chk-num">6</span>
              <div class="chk-info">
                <strong>Persentase Penyelesaian</strong>
                <span>Progres capaian fisik dari 0% hingga 100%</span>
              </div>
            </div>
          </div>
        </div>

        <div class="art-footer-meta">
          <span>Laporan yang rapi dan lengkap mempercepat persetujuan dan validasi tugas regu Anda.</span>
        </div>
      </div>
    </div>

    <!-- RIGHT FORM PANEL: LOGIN (DEFAULT) -->
    <div class="login-form-panel" id="login-panel">
      <div>
        <div class="login-brand-header-modern">
          <img src="foto/silap.png" alt="Logo SILAP" class="login-brand-svg-logo">
          <div>
            <h3 class="login-brand-title">SILAP</h3>
            <span class="login-brand-tagline">Sistem Laporan PPSU Kelurahan</span>
            <div style="margin-top:6px;display:inline-flex;align-items:center;gap:8px;background:#F5F3FF;border:1px solid #DDD6FE;padding:4px 11px 4px 6px;border-radius:10px;font-size:0.71rem;font-weight:700;color:#5B21B6;line-height:1.25;">
              <img src="foto/gunadarma.png" alt="Logo Universitas Gunadarma" style="width:26px;height:26px;object-fit:contain;flex-shrink:0;">
              <span>Aplikasi ini dibuat oleh<br>Mahasiswa Gunadarma</span>
            </div>
          </div>
        </div>

        <!-- PEMBERITAHUAN BANNER (KHUSUS MOBILE) -->
        <div class="mobile-alert-banner" onclick="openMobileNotifModal()">
          <span class="mab-badge">PENTING</span>
          <span class="mab-text">Panduan Laporan: 6 Hal Wajib Dicek</span>
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"></polyline></svg>
        </div>

        <div class="login-title-bar-wrap">
          <h2 class="login-title">Masuk ke Sistem</h2>
          <p class="login-sub">Masukkan username dan kata sandi akun Anda untuk mengakses portal SILAP.</p>
        </div>

        <?php if (!empty($info_banner)): ?>
        <div style="background:#eff6ff;border:1px solid #bfdbfe;color:#1e40af;padding:9px 12px;border-radius:8px;font-size:0.82rem;font-weight:600;margin-bottom:12px;">
          <?= htmlspecialchars($info_banner) ?>
        </div>
        <?php endif; ?>

        <form id="login-form" class="login-inputs-form" method="POST" action="login.php">
          <input type="hidden" name="action" value="login">

          <!-- Username Field -->
          <div class="input-field-group">
            <label class="input-label" for="login-username">Username / No. Telepon</label>
            <div class="input-control-wrap">
              <svg class="input-icon-svg" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                <circle cx="12" cy="7" r="4"></circle>
              </svg>
              <input type="text" name="username" id="login-username" class="modern-input" value="" placeholder="Masukkan username atau no. telepon" required autocomplete="username">
            </div>
          </div>

          <!-- Password Field -->
          <div class="input-field-group">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
              <label class="input-label" for="login-password" style="margin-bottom: 0;">Kata Sandi</label>
              <button type="button" onclick="openResetModal()" style="background: none; border: none; padding: 0; font-size: 0.78rem; font-weight: 700; color: #ea580c; cursor: pointer; font-family: inherit;">Lupa kata sandi?</button>
            </div>
            <div class="input-control-wrap">
              <svg class="input-icon-svg" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
              </svg>
              <input type="password" name="password" id="login-password" class="modern-input" value="" placeholder="Masukkan kata sandi" required autocomplete="current-password">
              <button type="button" class="btn-toggle-pwd-modern" id="btn-toggle-pwd" title="Tampilkan/sembunyikan kata sandi">
                <svg id="pwd-icon-eye" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                  <circle cx="12" cy="12" r="3"></circle>
                </svg>
              </button>
            </div>
          </div>

          <div class="login-alert-box <?= empty($error_login) ? 'hidden' : '' ?>" id="login-error">
            <?= htmlspecialchars($error_login ?: 'Username atau kata sandi tidak sesuai!') ?>
          </div>

          <!-- Submit Button -->
          <button type="submit" class="btn-login-cta" id="btn-submit-login">
            <span id="btn-login-text">Masuk ke Sistem SILAP</span>
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
              <line x1="5" y1="12" x2="19" y2="12"></line>
              <polyline points="12 5 19 12 12 19"></polyline>
            </svg>
          </button>

          <!-- Register prompt di paling bawah -->
          <div class="login-register-prompt">
            <span>Jika belum punya akun?</span>
            <button type="button" class="btn-register-link" onclick="switchToRegister()">Register dulu</button>
          </div>
        </form>
      </div>

      <div class="login-panel-footer">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
        <span>Butuh bantuan akun? Hubungi Admin / Kasi Ekbang Kelurahan.</span>
      </div>
    </div>

    <!-- RIGHT FORM PANEL: REGISTER (MODE REGISTER) -->
    <div class="register-form-panel" id="register-panel">
      <div>
        <div class="login-brand-header-modern">
          <img src="foto/silap.png" alt="Logo SILAP" class="login-brand-svg-logo">
          <div>
            <h3 class="login-brand-title">SILAP</h3>
            <span class="login-brand-tagline">Daftar Akun Baru</span>
          </div>
        </div>

        <div class="login-title-bar-wrap">
          <h2 class="login-title">Registrasi Akun</h2>
          <p class="login-sub">Buat nama pengguna dan kata sandi untuk pendaftaran akun.</p>
        </div>

        <form id="register-form" class="login-inputs-form" method="POST" action="login.php" onsubmit="handleAjaxRegister(event)">
          <input type="hidden" name="action" value="register">

          <!-- 1. Nama Lengkap -->
          <div class="input-field-group">
            <label class="input-label" for="reg-name">Nama Lengkap</label>
            <div class="input-control-wrap">
              <svg class="input-icon-svg" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                <circle cx="12" cy="7" r="4"></circle>
              </svg>
              <input type="text" name="name" id="reg-name" class="modern-input" placeholder="Masukkan nama lengkap Anda" required autocomplete="name">
            </div>
          </div>

          <!-- 2. Nama Pengguna (Username Login) -->
          <div class="input-field-group">
            <label class="input-label" for="reg-username">Nama Pengguna (Username Login)</label>
            <div class="input-control-wrap">
              <svg class="input-icon-svg" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                <circle cx="8.5" cy="7" r="4"></circle>
                <line x1="20" y1="8" x2="20" y2="14"></line>
                <line x1="23" y1="11" x2="17" y2="11"></line>
              </svg>
              <input type="text" name="username" id="reg-username" class="modern-input" placeholder="huruf kecil/angka tanpa spasi (contoh: budi12)" required autocomplete="username">
            </div>
          </div>

          <!-- 3. Nomor Telepon -->
          <div class="input-field-group">
            <label class="input-label" for="reg-phone">Nomor Telepon / WhatsApp</label>
            <div class="input-control-wrap">
              <svg class="input-icon-svg" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
              </svg>
              <input type="tel" name="phone" id="reg-phone" class="modern-input" placeholder="Contoh: 081234567890" required autocomplete="tel">
            </div>
          </div>

          <!-- 4. Kata Sandi -->
          <div class="input-field-group">
            <label class="input-label" for="reg-password">Kata Sandi</label>
            <div class="input-control-wrap">
              <svg class="input-icon-svg" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
              </svg>
              <input type="password" name="password" id="reg-password" class="modern-input" placeholder="huruf kecil & angka (contoh: petugas123)" required autocomplete="new-password">
              <button type="button" class="btn-toggle-pwd-modern" id="btn-toggle-reg-pwd" title="Tampilkan/sembunyikan kata sandi">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                  <circle cx="12" cy="12" r="3"></circle>
                </svg>
              </button>
            </div>

            <!-- Petunjuk Format Kata Sandi Real-time -->
            <div class="pwd-rules-hint" id="reg-pwd-rules">
              <div class="pwd-rules-header">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
                <span>Format kata sandi yang disyaratkan:</span>
              </div>
              <div class="pwd-rules-list">
                <span class="pwd-rule-item" id="rule-lower">
                  <span class="rule-icon">○</span> Huruf kecil semua
                </span>
                <span class="pwd-rule-item" id="rule-number">
                  <span class="rule-icon">○</span> Wajib ada angka (0-9)
                </span>
                <span class="pwd-rule-item" id="rule-minlen">
                  <span class="rule-icon">○</span> Min. 6 karakter
                </span>
              </div>
              <div class="pwd-rules-warning" id="pwd-upper-warning">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
                <span>Huruf besar terdeteksi! Mohon gunakan huruf kecil saja.</span>
              </div>
            </div>
          </div>

          <!-- 4. Konfirmasi Kata Sandi -->
          <div class="input-field-group">
            <label class="input-label" for="reg-confirm-password">Konfirmasi Kata Sandi</label>
            <div class="input-control-wrap">
              <svg class="input-icon-svg" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
              </svg>
              <input type="password" name="confirm_password" id="reg-confirm-password" class="modern-input" placeholder="Ulangi kata sandi Anda" required autocomplete="new-password">
              <button type="button" class="btn-toggle-pwd-modern" id="btn-toggle-reg-confirm-pwd" title="Tampilkan/sembunyikan konfirmasi kata sandi">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                  <circle cx="12" cy="12" r="3"></circle>
                </svg>
              </button>
            </div>
          </div>

          <div class="login-alert-box <?= empty($error_register) ? 'hidden' : '' ?>" id="register-error" style="margin-bottom: 8px;">
            <?= htmlspecialchars($error_register ?: '') ?>
          </div>

          <!-- Submit Button -->
          <button type="submit" class="btn-login-cta" id="btn-submit-reg">
            <span>Daftar Akun SILAP</span>
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
              <line x1="5" y1="12" x2="19" y2="12"></line>
              <polyline points="12 5 19 12 12 19"></polyline>
            </svg>
          </button>

          <!-- Prompt Kembali ke Login -->
          <div class="login-register-prompt">
            <span>Sudah punya akun?</span>
            <button type="button" class="btn-register-link" onclick="switchToLogin()">Masuk di sini</button>
          </div>
        </form>
      </div>

      <div class="login-panel-footer">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
        <span>Data registrasi otomatis divalidasi oleh Kasi Ekbang Kelurahan.</span>
      </div>
    </div>

  </div>
</div>

<!-- MODAL RESET PASSWORD MANDIRI (VERIFIKASI NO. HP) -->
<div class="modal-backdrop-register hidden" id="reset-password-modal" onclick="handleResetBackdropClick(event)">
  <div class="modal-card-register" style="max-width: 440px;">
    <!-- Header -->
    <div class="register-header" style="margin-bottom: 16px;">
      <div class="register-header-brand">
        <div style="width: 40px; height: 40px; border-radius: 10px; background: #FFF7ED; display: flex; align-items: center; justify-content: center; color: #ea580c; border: 1.5px solid #FFEDD5; flex-shrink: 0;">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
            <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
          </svg>
        </div>
        <div>
          <h3 class="register-title" style="font-size: 1.05rem;">Reset Kata Sandi</h3>
          <p class="register-subtitle">Cocokkan akun & nomor telepon terdaftar</p>
        </div>
      </div>
      <button type="button" class="btn-close-register" onclick="closeResetModal()" title="Tutup" aria-label="Tutup">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
      </button>
    </div>

    <!-- Alert Box -->
    <div class="login-alert-box hidden" id="reset-modal-error" style="margin-bottom: 14px;"></div>
    <div class="login-alert-box hidden" id="reset-modal-success" style="margin-bottom: 14px; background: #ECFDF5; border-color: #A7F3D0; color: #065F46;"></div>

    <!-- Form -->
    <form id="reset-password-form" onsubmit="handleAjaxReset(event)">
      <div class="input-field-group" style="margin-bottom: 12px;">
        <label class="input-label" for="reset-username">Username / ID Akun</label>
        <div class="input-control-wrap">
          <svg class="input-icon-svg" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
            <circle cx="12" cy="7" r="4"></circle>
          </svg>
          <input type="text" id="reset-username" class="modern-input" placeholder="Masukkan username akun Anda" required autocomplete="username">
        </div>
      </div>

      <div class="input-field-group" style="margin-bottom: 12px;">
        <label class="input-label" for="reset-phone">Nomor HP Terdaftar</label>
        <div class="input-control-wrap">
          <svg class="input-icon-svg" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
          </svg>
          <input type="tel" id="reset-phone" class="modern-input" placeholder="Contoh: 081234567890" required autocomplete="tel">
        </div>
      </div>

      <div class="input-field-group" style="margin-bottom: 12px;">
        <label class="input-label" for="reset-new-password">Kata Sandi Baru</label>
        <div class="input-control-wrap">
          <svg class="input-icon-svg" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
            <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
          </svg>
          <input type="password" id="reset-new-password" class="modern-input" placeholder="huruf kecil & angka (min. 6 karakter)" required autocomplete="new-password">
        </div>
      </div>

      <div class="input-field-group" style="margin-bottom: 18px;">
        <label class="input-label" for="reset-confirm-password">Ulangi Kata Sandi Baru</label>
        <div class="input-control-wrap">
          <svg class="input-icon-svg" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
            <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
          </svg>
          <input type="password" id="reset-confirm-password" class="modern-input" placeholder="Ketik ulang kata sandi baru" required autocomplete="new-password">
        </div>
      </div>

      <button type="submit" class="btn-login-cta" id="btn-submit-reset" style="margin-top: 4px;">
        <span>Simpan Kata Sandi Baru</span>
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
          <polyline points="20 6 9 17 4 12"></polyline>
        </svg>
      </button>
    </form>
  </div>
</div>

<!-- MODAL PANDUAN LAPORAN (6 HAL WAJIB DICEK - SESUAI DESKTOP) -->
<div class="mobile-notif-backdrop" id="mobile-notif-modal" onclick="handleBackdropClick(event)">
  <div class="mobile-notif-sheet">
    
    <!-- Modal Header -->
    <div style="padding:16px 20px;background:#C2410C;color:#ffffff;display:flex;align-items:center;justify-content:space-between;border-radius:24px 24px 0 0;">
      <div style="display:flex;align-items:center;gap:10px;">
        <div style="width:36px;height:36px;border-radius:10px;background:rgba(255,255,255,0.2);display:flex;align-items:center;justify-content:center;color:#FFFFFF;flex-shrink:0;">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
        </div>
        <div>
          <h4 style="margin:0;font-size:0.98rem;font-weight:800;color:#FFFFFF;line-height:1.2;">Panduan Laporan Regu</h4>
          <p style="margin:2px 0 0 0;font-size:0.75rem;color:rgba(255,255,255,0.92);">6 Hal Wajib Dicek Sebelum Kirim Laporan</p>
        </div>
      </div>
      <button type="button" onclick="closeMobileNotifModal()" aria-label="Tutup" style="background:rgba(255,255,255,0.2);border:none;width:34px;height:34px;border-radius:50%;color:#FFFFFF;display:flex;align-items:center;justify-content:center;cursor:pointer;transition:background 0.15s ease;">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
      </button>
    </div>

    <!-- Modal Content: Human-Friendly Checklist Identik Desktop -->
    <div style="padding:16px 18px;overflow-y:auto;background:#F8FAFC;max-height:calc(85vh - 130px);box-sizing:border-box;">
      <div class="human-checklist-card" style="box-shadow:none;border:1px solid #E2E8F0;padding:16px 14px;background:#FFFFFF;border-radius:16px;">
        <div class="checklist-badge">
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
          <span>PANDUAN LAPORAN REGU</span>
        </div>
        <h2 class="checklist-title" style="font-size:1.05rem;font-weight:800;color:#0F172A;margin:4px 0 4px 0;">Hal Penting Sebelum Kirim Laporan</h2>
        <p class="checklist-desc" style="font-size:0.78rem;color:#64748B;margin:0 0 12px 0;line-height:1.45;">Pastikan 6 informasi wajib berikut sudah lengkap dan jelas agar laporan cepat diverifikasi pihak kelurahan:</p>

        <div class="checklist-items-wrap" style="display:flex;flex-direction:column;gap:8px;">
          <div class="checklist-row">
            <span class="chk-num">1</span>
            <div class="chk-info">
              <strong>Nomor Pekerjaan</strong>
              <span>Nomor penugasan resmi dari kelurahan</span>
            </div>
          </div>

          <div class="checklist-row">
            <span class="chk-num">2</span>
            <div class="chk-info">
              <strong>Tanggal & Waktu</strong>
              <span>Waktu riil pelaksanaan kegiatan</span>
            </div>
          </div>

          <div class="checklist-row">
            <span class="chk-num">3</span>
            <div class="chk-info">
              <strong>Jenis Pekerjaan</strong>
              <span>Jalan, saluran got, taman, sampah</span>
            </div>
          </div>

          <div class="checklist-row">
            <span class="chk-num">4</span>
            <div class="chk-info">
              <strong>Alamat Lokasi Lengkap</strong>
              <span>Nama jalan, RT/RW, dan patokan lokasi</span>
            </div>
          </div>

          <div class="checklist-row">
            <span class="chk-num">5</span>
            <div class="chk-info">
              <strong>Foto Dokumentasi</strong>
              <span>Bukti foto: sebelum, saat kerja, & selesai</span>
            </div>
          </div>

          <div class="checklist-row">
            <span class="chk-num">6</span>
            <div class="chk-info">
              <strong>Persentase Penyelesaian</strong>
              <span>Progres capaian fisik dari 0% hingga 100%</span>
            </div>
          </div>
        </div>
      </div>

      <div style="margin-top:12px;background:#FFF7ED;border:1px solid #FED7AA;border-radius:12px;padding:10px 14px;display:flex;align-items:flex-start;gap:8px;">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color:#EA580C;flex-shrink:0;margin-top:1px;"><path d="M12 2a7 7 0 0 0-7 7c0 2.38 1.19 4.47 3 5.74V17a2 2 0 0 0 2 2h4a2 2 0 0 0 2-2v-2.26c1.81-1.27 3-3.36 3-5.74a7 7 0 0 0-7-7z"></path><line x1="9" y1="21" x2="15" y2="21"></line></svg>
        <span style="font-size:0.76rem;color:#9A3412;line-height:1.45;font-weight:600;">Laporan yang rapi dan lengkap mempercepat persetujuan dan validasi tugas regu Anda.</span>
      </div>
    </div>

    <!-- Modal Footer -->
    <div style="padding:12px 18px 18px 18px;background:#FFFFFF;border-top:1px solid #E2E8F0;">
      <button type="button" onclick="closeMobileNotifModal()" style="width:100%;background:#C2410C;color:#FFFFFF;border:none;border-radius:12px;padding:12px;font-size:0.9rem;font-weight:800;display:flex;align-items:center;justify-content:center;gap:8px;cursor:pointer;box-shadow:0 4px 12px rgba(194,65,12,0.25);transition:background 0.15s ease;">
        <span>Siap, Saya Mengerti</span>
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"></polyline></svg>
      </button>
    </div>

  </div>
</div>

<script src="shared.js?v=<?= time() ?>"></script>
<script>
  const authContainer = document.getElementById("auth-container");
  const uInp = document.getElementById("login-username");
  const pInp = document.getElementById("login-password");
  const loginForm = document.getElementById("login-form");
  const btnTogglePwd = document.getElementById("btn-toggle-pwd");
  const errEl = document.getElementById("login-error");
  const btnText = document.getElementById("btn-login-text");

  // Mode Tampilan (Desktop & Mobile)
  function switchToRegister() {
    if (authContainer) authContainer.classList.add("mode-register");
  }

  function switchToLogin() {
    if (authContainer) authContainer.classList.remove("mode-register");
  }

  // Auto-switch jika URL memiliki hashtag #register
  if (window.location.hash === "#register" || window.location.search.indexOf("mode=register") !== -1) {
    switchToRegister();
  }

  // Nama Pengguna diisi mandiri oleh pengguna secara independen
  const regUserInp = document.getElementById("reg-username");

  // ==========================================================
  // REAL-TIME PASSWORD INDICATOR (Huruf kecil semua & angka)
  // ==========================================================
  const regPwdInp = document.getElementById("reg-password");
  const ruleLower = document.getElementById("rule-lower");
  const ruleNumber = document.getElementById("rule-number");
  const ruleMinlen = document.getElementById("rule-minlen");
  const pwdUpperWarning = document.getElementById("pwd-upper-warning");
  const regPwdRulesBox = document.getElementById("reg-pwd-rules");

  if (regPwdInp) {
    regPwdInp.addEventListener("input", () => {
      const val = regPwdInp.value;
      const hasUpper = /[A-Z]/.test(val);
      const hasLower = /[a-z]/.test(val);
      const hasNumber = /[0-9]/.test(val);
      const hasMinlen = val.length >= 6;
      const isAllLower = hasLower && !hasUpper;

      // Peringatan huruf besar terdeteksi
      if (hasUpper) {
        if (pwdUpperWarning) pwdUpperWarning.classList.add("visible");
        if (regPwdRulesBox) regPwdRulesBox.classList.add("has-upper-error");
        if (ruleLower) {
          ruleLower.className = "pwd-rule-item invalid";
          const icon = ruleLower.querySelector(".rule-icon");
          if (icon) icon.textContent = "✕";
        }
      } else {
        if (pwdUpperWarning) pwdUpperWarning.classList.remove("visible");
        if (regPwdRulesBox) regPwdRulesBox.classList.remove("has-upper-error");
        if (ruleLower) {
          if (isAllLower) {
            ruleLower.className = "pwd-rule-item valid";
            const icon = ruleLower.querySelector(".rule-icon");
            if (icon) icon.textContent = "✓";
          } else {
            ruleLower.className = "pwd-rule-item";
            const icon = ruleLower.querySelector(".rule-icon");
            if (icon) icon.textContent = "○";
          }
        }
      }

      // Cek angka
      if (ruleNumber) {
        if (hasNumber) {
          ruleNumber.className = "pwd-rule-item valid";
          const icon = ruleNumber.querySelector(".rule-icon");
          if (icon) icon.textContent = "✓";
        } else {
          ruleNumber.className = "pwd-rule-item";
          const icon = ruleNumber.querySelector(".rule-icon");
          if (icon) icon.textContent = "○";
        }
      }

      // Cek panjang minimal 6
      if (ruleMinlen) {
        if (hasMinlen) {
          ruleMinlen.className = "pwd-rule-item valid";
          const icon = ruleMinlen.querySelector(".rule-icon");
          if (icon) icon.textContent = "✓";
        } else {
          ruleMinlen.className = "pwd-rule-item";
          const icon = ruleMinlen.querySelector(".rule-icon");
          if (icon) icon.textContent = "○";
        }
      }
    });
  }

  // Toggle Visibility Password Register
  const btnToggleRegPwd = document.getElementById("btn-toggle-reg-pwd");
  if (btnToggleRegPwd && regPwdInp) {
    let show = false;
    btnToggleRegPwd.addEventListener("click", () => {
      show = !show;
      regPwdInp.type = show ? "text" : "password";
      btnToggleRegPwd.innerHTML = show
        ? `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>`
        : `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>`;
    });
  }

  // Toggle Visibility Confirm Password Register
  const btnToggleRegConfPwd = document.getElementById("btn-toggle-reg-confirm-pwd");
  const regConfPwdInp = document.getElementById("reg-confirm-password");
  if (btnToggleRegConfPwd && regConfPwdInp) {
    let show = false;
    btnToggleRegConfPwd.addEventListener("click", () => {
      show = !show;
      regConfPwdInp.type = show ? "text" : "password";
      btnToggleRegConfPwd.innerHTML = show
        ? `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>`
        : `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>`;
    });
  }

  // Toggle Visibility Password Login
  if (btnTogglePwd && pInp) {
    let showPwd = false;
    btnTogglePwd.addEventListener("click", () => {
      showPwd = !showPwd;
      pInp.type = showPwd ? "text" : "password";
      btnTogglePwd.innerHTML = showPwd
        ? `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>`
        : `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>`;
    });
  }

  // ==========================================================
  // AJAX LOGIN SUBMISSION
  // ==========================================================
  if (loginForm) {
    loginForm.addEventListener("submit", async (e) => {
      e.preventDefault();
      const uname = (uInp.value || "").trim();
      const pwd = pInp.value;
      const btnSubmit = document.getElementById("btn-submit-login");

      if (errEl) errEl.classList.add("hidden");

      if (!uname || !pwd) {
        if (errEl) {
          errEl.textContent = "Mohon lengkapi username dan kata sandi!";
          errEl.classList.remove("hidden");
        }
        return;
      }

      if (btnSubmit) btnSubmit.disabled = true;
      if (btnText) btnText.textContent = "Memverifikasi...";

      try {
        const formData = new FormData();
        formData.append("action", "login");
        formData.append("username", uname);
        formData.append("password", pwd);
        formData.append("ajax", "1");

        const res = await fetch("login.php", {
          method: "POST",
          headers: { "X-Requested-With": "XMLHttpRequest" },
          body: formData
        });

        const data = await res.json();

        if (data.success) {
          // Sinkronkan sesi ke LocalStorage untuk kompatibilitas shared.js
          if (data.user) {
            try {
              localStorage.setItem("silap_user_session", JSON.stringify(data.user));
              localStorage.setItem("jawara_user_session", JSON.stringify(data.user));
            } catch(e) {}
          }
          if (btnText) btnText.textContent = "Masuk Berhasil...";
          window.location.href = data.redirect || "admin.php";
        } else {
          if (btnSubmit) btnSubmit.disabled = false;
          if (btnText) btnText.textContent = "Masuk ke Sistem SILAP";
          if (errEl) {
            errEl.textContent = data.message || "Username atau kata sandi tidak cocok!";
            errEl.classList.remove("hidden");
          }
        }
      } catch (err) {
        // Fallback jika fetch bermasalah, kirim form normal
        loginForm.submit();
      }
    });
  }

  // ==========================================================
  // AJAX REGISTER SUBMISSION
  // ==========================================================
  async function handleAjaxRegister(e) {
    e.preventDefault();
    const nameVal = (document.getElementById("reg-name").value || "").trim();
    const usernameVal = (document.getElementById("reg-username").value || "").trim().toLowerCase();
    const rawPhoneVal = (document.getElementById("reg-phone").value || "").trim();
    const pwdVal = document.getElementById("reg-password").value;
    const confirmPwdVal = document.getElementById("reg-confirm-password").value;
    const regErrEl = document.getElementById("register-error");
    const btnSubmitReg = document.getElementById("btn-submit-reg");

    if (regErrEl) regErrEl.classList.add("hidden");

    if (!nameVal || !usernameVal || !rawPhoneVal || !pwdVal || !confirmPwdVal) {
      if (regErrEl) {
        regErrEl.textContent = "Mohon lengkapi semua data pendaftaran (termasuk username)!";
        regErrEl.classList.remove("hidden");
      }
      return;
    }

    if (rawPhoneVal.startsWith("+62") || rawPhoneVal.startsWith("62") || rawPhoneVal.startsWith("+")) {
      if (regErrEl) {
        regErrEl.textContent = 'Format "+62" ditolak! Nomor telepon wajib diawali dengan "08" (contoh: 081234567890).';
        regErrEl.classList.remove("hidden");
      }
      return;
    }

    const phoneVal = rawPhoneVal.replace(/[- ]/g, "");
    if (!/^08[0-9]{8,13}$/.test(phoneVal)) {
      if (regErrEl) {
        regErrEl.textContent = 'Nomor telepon wajib diawali dengan "08" dan terdiri dari 10-15 digit angka!';
        regErrEl.classList.remove("hidden");
      }
      return;
    }

    if (usernameVal.length < 3 || usernameVal.length > 30 || !/^[a-z0-9_]+$/.test(usernameVal)) {
      if (regErrEl) {
        regErrEl.textContent = "Nama pengguna (username) harus 3-30 karakter, huruf kecil atau angka tanpa spasi!";
        regErrEl.classList.remove("hidden");
      }
      return;
    }

    if (pwdVal.length < 6) {
      if (regErrEl) {
        regErrEl.textContent = "Kata sandi minimal 6 karakter!";
        regErrEl.classList.remove("hidden");
      }
      return;
    }

    if (/[A-Z]/.test(pwdVal)) {
      if (regErrEl) {
        regErrEl.textContent = "Kata sandi harus huruf kecil semua (tidak boleh huruf besar/kapital)!";
        regErrEl.classList.remove("hidden");
      }
      return;
    }

    if (!/[a-z]/.test(pwdVal)) {
      if (regErrEl) {
        regErrEl.textContent = "Kata sandi harus mengandung huruf kecil!";
        regErrEl.classList.remove("hidden");
      }
      return;
    }

    if (!/[0-9]/.test(pwdVal)) {
      if (regErrEl) {
        regErrEl.textContent = "Kata sandi wajib menyertakan kombinasi angka (0-9)!";
        regErrEl.classList.remove("hidden");
      }
      return;
    }

    if (pwdVal !== confirmPwdVal) {
      if (regErrEl) {
        regErrEl.textContent = "Kata sandi dan konfirmasi kata sandi tidak cocok!";
        regErrEl.classList.remove("hidden");
      }
      return;
    }

    if (btnSubmitReg) {
      btnSubmitReg.disabled = true;
      btnSubmitReg.querySelector("span").textContent = "Mendaftarkan...";
    }

    try {
      const formData = new FormData();
      formData.append("action", "register");
      formData.append("name", nameVal);
      formData.append("username", usernameVal);
      formData.append("phone", phoneVal);
      formData.append("password", pwdVal);
      formData.append("confirm_password", confirmPwdVal);
      formData.append("ajax", "1");

      const res = await fetch("login.php", {
        method: "POST",
        headers: { "X-Requested-With": "XMLHttpRequest" },
        body: formData
      });

      const data = await res.json();

      if (data.success) {
        // Isi otomatis input login untuk mempermudah user
        if (uInp && pInp) {
          uInp.value = data.username || usernameVal;
          pInp.value = pwdVal;
        }

        if (btnSubmitReg) {
          btnSubmitReg.disabled = false;
          btnSubmitReg.querySelector("span").textContent = "Daftar Akun SILAP";
        }

        const namaUser = document.getElementById("reg-name") ? document.getElementById("reg-name").value.trim() : "";
        const userDisplay = data.username || usernameVal;
        const msgHtml = `Pendaftaran akun <strong style="color:#0F172A;font-weight:700;">"${userDisplay}"</strong> ${namaUser ? `(${namaUser}) ` : ""}berhasil!<br><span style="display:inline-block;margin-top:8px;font-size:0.84rem;color:#64748B;">Akun telah aktif di sistem. Silakan klik tombol di bawah untuk langsung masuk.</span>`;

        showAppAlert({
          title: "Pendaftaran Berhasil!",
          message: msgHtml,
          type: "success",
          btnText: "Lanjut Masuk",
          onConfirm: () => {
            switchToLogin();
          }
        });
      } else {
        if (btnSubmitReg) {
          btnSubmitReg.disabled = false;
          btnSubmitReg.querySelector("span").textContent = "Daftar Akun SILAP";
        }
        if (regErrEl) {
          regErrEl.textContent = data.message || "Pendaftaran gagal!";
          regErrEl.classList.remove("hidden");
        }
      }
    } catch(err) {
      // Fallback submit biasa jika ada error jaringan
      document.getElementById("register-form").submit();
    }
  }

  // ==========================================================
  // RESET PASSWORD MODAL LOGIC & AJAX HANDLER
  // ==========================================================
  function openResetModal() {
    const modal = document.getElementById("reset-password-modal");
    if (modal) {
      modal.classList.remove("hidden");
      document.body.style.overflow = "hidden";
      const uVal = (document.getElementById("login-username")?.value || "").trim();
      const rUserInp = document.getElementById("reset-username");
      if (rUserInp && uVal) {
        rUserInp.value = uVal;
      }
    }
  }

  function closeResetModal() {
    const modal = document.getElementById("reset-password-modal");
    if (modal) {
      modal.classList.add("hidden");
      document.body.style.overflow = "";
      const errEl = document.getElementById("reset-modal-error");
      const succEl = document.getElementById("reset-modal-success");
      if (errEl) errEl.classList.add("hidden");
      if (succEl) succEl.classList.add("hidden");
    }
  }

  function handleResetBackdropClick(e) {
    if (e.target && e.target.id === "reset-password-modal") {
      closeResetModal();
    }
  }

  async function handleAjaxReset(e) {
    e.preventDefault();
    const uVal = (document.getElementById("reset-username").value || "").trim();
    const phoneVal = (document.getElementById("reset-phone").value || "").trim();
    const pwdVal = document.getElementById("reset-new-password").value;
    const confirmVal = document.getElementById("reset-confirm-password").value;

    const errEl = document.getElementById("reset-modal-error");
    const succEl = document.getElementById("reset-modal-success");
    const btnSubmit = document.getElementById("btn-submit-reset");

    if (errEl) errEl.classList.add("hidden");
    if (succEl) succEl.classList.add("hidden");

    if (!uVal || !phoneVal || !pwdVal || !confirmVal) {
      if (errEl) {
        errEl.textContent = "Mohon lengkapi semua kolom!";
        errEl.classList.remove("hidden");
      }
      return;
    }

    if (pwdVal.length < 6) {
      if (errEl) {
        errEl.textContent = "Kata sandi minimal 6 karakter!";
        errEl.classList.remove("hidden");
      }
      return;
    }

    if (/[A-Z]/.test(pwdVal)) {
      if (errEl) {
        errEl.textContent = "Kata sandi harus huruf kecil semua (tanpa huruf besar/kapital)!";
        errEl.classList.remove("hidden");
      }
      return;
    }

    if (!/[a-z]/.test(pwdVal)) {
      if (errEl) {
        errEl.textContent = "Kata sandi harus mengandung kombinasi huruf kecil!";
        errEl.classList.remove("hidden");
      }
      return;
    }

    if (!/[0-9]/.test(pwdVal)) {
      if (errEl) {
        errEl.textContent = "Kata sandi wajib menyertakan kombinasi angka (0-9)!";
        errEl.classList.remove("hidden");
      }
      return;
    }

    if (pwdVal !== confirmVal) {
      if (errEl) {
        errEl.textContent = "Konfirmasi kata sandi tidak cocok!";
        errEl.classList.remove("hidden");
      }
      return;
    }

    if (btnSubmit) {
      btnSubmit.disabled = true;
      btnSubmit.querySelector("span").textContent = "Memverifikasi...";
    }

    try {
      const formData = new FormData();
      formData.append("action", "reset_password");
      formData.append("username", uVal);
      formData.append("phone", phoneVal);
      formData.append("password", pwdVal);
      formData.append("confirm_password", confirmVal);
      formData.append("ajax", "1");

      const res = await fetch("login.php", {
        method: "POST",
        headers: { "X-Requested-With": "XMLHttpRequest" },
        body: formData
      });

      const data = await res.json();

      if (btnSubmit) {
        btnSubmit.disabled = false;
        btnSubmit.querySelector("span").textContent = "Simpan Kata Sandi Baru";
      }

      if (data.success) {
        if (succEl) {
          succEl.textContent = data.message || "Kata sandi berhasil diubah!";
          succEl.classList.remove("hidden");
        }

        // Isi form login dengan password baru otomatis
        const loginU = document.getElementById("login-username");
        const loginP = document.getElementById("login-password");
        if (loginU) loginU.value = uVal;
        if (loginP) loginP.value = pwdVal;

        setTimeout(() => {
          closeResetModal();
        }, 1500);
      } else {
        if (errEl) {
          errEl.textContent = data.message || "Gagal mereset kata sandi.";
          errEl.classList.remove("hidden");
        }
      }
    } catch(err) {
      if (btnSubmit) {
        btnSubmit.disabled = false;
        btnSubmit.querySelector("span").textContent = "Simpan Kata Sandi Baru";
      }
      if (errEl) {
        errEl.textContent = "Terjadi kesalahan koneksi atau server.";
        errEl.classList.remove("hidden");
      }
    }
  }

  // Mobile Notification Modal Logic (Panduan Laporan 6 Checklist Wajib)
  function openMobileNotifModal() {
    const modal = document.getElementById("mobile-notif-modal");
    if (modal) {
      modal.classList.add("active");
      document.body.style.overflow = "hidden";
    }
  }

  function closeMobileNotifModal() {
    const modal = document.getElementById("mobile-notif-modal");
    if (modal) {
      modal.classList.remove("active");
      document.body.style.overflow = "";
    }
  }

  function handleBackdropClick(e) {
    if (e.target && e.target.id === "mobile-notif-modal") {
      closeMobileNotifModal();
    }
  }

  // Tampilkan otomatis panduan 6 poin saat web mobile dibuka
  document.addEventListener("DOMContentLoaded", function() {
    if (window.innerWidth <= 860) {
      setTimeout(() => {
        openMobileNotifModal();
      }, 350);
    }
  });
</script>
</body>
</html>
