<?php
/**
 * SILAP Mobile – Portal Lapor Pekerjaan PPSU (PHP Session Protected)
 */
session_start();
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");
require_once __DIR__ . '/koneksi.php';

// Geocoding Proxy (Reverse Geocoding OpenStreetMap via legitimate server User-Agent)
if (isset($_GET['action']) && $_GET['action'] === 'geocode') {
    header('Content-Type: application/json; charset=utf-8');
    $lat = filter_input(INPUT_GET, 'lat', FILTER_VALIDATE_FLOAT);
    $lng = filter_input(INPUT_GET, 'lng', FILTER_VALIDATE_FLOAT);
    if ($lat === false || $lng === false) {
        echo json_encode(['success' => false, 'message' => 'Invalid coordinates']);
        exit;
    }
    $opts = [
        'http' => [
            'method' => 'GET',
            'header' => "User-Agent: KelurahanPekayonPPSU/1.0 (contact: kelurahan@jakarta.go.id)\r\nAccept-Language: id\r\n",
            'timeout' => 4
        ]
    ];
    $context = stream_context_create($opts);
    $res = @file_get_contents("https://nominatim.openstreetmap.org/reverse?format=json&lat={$lat}&lon={$lng}&zoom=18&addressdetails=1", false, $context);
    if ($res) {
        echo $res;
        exit;
    }
    echo json_encode(['success' => false]);
    exit;
}

// Cek apakah user sudah login
if (!isset($_SESSION['user'])) {
    header("Location: login.php?msg=harus_login");
    exit;
}

$currentUser = $_SESSION['user'];
$userName = htmlspecialchars($currentUser['nama'] ?? 'Budi Santoso');
$userRole = htmlspecialchars($currentUser['role'] ?? 'petugas');
$userWilayah = htmlspecialchars($currentUser['wilayah'] ?? 'jaktim');
$userBadge = ($userRole === 'admin') ? 'Admin' : 'RW 01';

$userPhone = str_replace(['-', ' '], '', $currentUser['no_hp'] ?? '');
if (empty($userPhone)) {
    try {
        $phStmt = $conn->prepare("SELECT hp FROM anggota WHERE LOWER(TRIM(nama)) = LOWER(TRIM(?)) LIMIT 1");
        $phStmt->execute([$currentUser['nama'] ?? '']);
        $phRow = $phStmt->fetch(PDO::FETCH_ASSOC);
        if ($phRow && !empty($phRow['hp'])) {
            $userPhone = str_replace(['-', ' '], '', $phRow['hp']);
        }
    } catch (Exception $e) {}
}
if (empty($userPhone)) {
    $userPhone = '081234567890';
}
if (strpos($userPhone, '+62') === 0) {
    $userPhone = '0' . substr($userPhone, 3);
} elseif (strpos($userPhone, '62') === 0) {
    $userPhone = '0' . substr($userPhone, 2);
}

// Cek apakah akun pengguna masih terdaftar aktif atau telah dihapus oleh Admin
$isAccountDeleted = false;
if ($userRole !== 'admin') {
    try {
        $chkUser = $conn->prepare("SELECT id FROM users WHERE id = ? LIMIT 1");
        $chkUser->execute([$currentUser['id'] ?? 0]);
        if (!$chkUser->fetch()) {
            $isAccountDeleted = true;
        } else {
            $chkAgt = $conn->prepare("SELECT id FROM anggota WHERE LOWER(TRIM(nama)) = LOWER(TRIM(?)) LIMIT 1");
            $chkAgt->execute([$currentUser['nama'] ?? '']);
            if (!$chkAgt->fetch()) {
                $isAccountDeleted = true;
            }
        }
    } catch (Exception $e) {}
}

// Cek data resmi penugasan regu dari tabel master anggota di database
$userOfficialRole = 'Anggota Regu';
$userOfficialRegu = '';
$userOfficialReguId = '';
$userOfficialWilayah = ($userRole === 'admin') ? 'Semua RW' : '-';

try {
    if ($userRole === 'admin') {
        $userOfficialRole = 'Administrator';
        $userOfficialRegu = 'Pusat Komando';
        $userOfficialReguId = 'KOMANDO';
        $userOfficialWilayah = 'Semua RW';
        $userBadge = 'Admin';
    } else {
        $cleanHp = preg_replace('/\D/', '', $currentUser['no_hp'] ?? '');
        $uStmt = $conn->prepare("SELECT role, regu_id, regu, wilayah FROM anggota WHERE LOWER(TRIM(nama)) = LOWER(TRIM(?)) OR (hp != '' AND REPLACE(REPLACE(REPLACE(hp, '-', ''), '+', ''), ' ', '') = ?) LIMIT 1");
        $uStmt->execute([$currentUser['nama'] ?? '', $cleanHp]);
        $uRow = $uStmt->fetch(PDO::FETCH_ASSOC);
        if ($uRow) {
            $userOfficialRole = $uRow['role'] ?: 'Anggota Regu';
            if (!empty($uRow['regu']) && $uRow['regu'] !== 'Belum Ditugaskan') {
                $userOfficialRegu = $uRow['regu'];
            }
            $userOfficialReguId = $uRow['regu_id'] ?: '';
            $userOfficialWilayah = ($userOfficialRegu !== '') ? ($uRow['wilayah'] ?: '-') : '-';
        }

        // Cek juga jika tercatat sebagai ketua di tabel regu
        $rStmt = $conn->prepare("SELECT id, nama, wilayah FROM regu WHERE LOWER(TRIM(ketua)) = LOWER(TRIM(?)) LIMIT 1");
        $rStmt->execute([$currentUser['nama'] ?? '']);
        $rRow = $rStmt->fetch(PDO::FETCH_ASSOC);
        if ($rRow) {
            $userOfficialRole = 'Ketua Regu';
            $userOfficialRegu = $rRow['nama'];
            $userOfficialReguId = $rRow['id'];
            if (!empty($rRow['wilayah'])) $userOfficialWilayah = $rRow['wilayah'];
        }
    }

    $formattedServerAnggota = [];
    $stmtAllAgt = $conn->query("SELECT * FROM anggota ORDER BY id ASC");
    $serverAnggotaRows = $stmtAllAgt ? $stmtAllAgt->fetchAll(PDO::FETCH_ASSOC) : [];
    foreach ($serverAnggotaRows as $sa) {
        $hasSaRegu = !empty($sa['regu']) && $sa['regu'] !== 'Belum Ditugaskan';
        $formattedServerAnggota[] = [
            'id'       => $sa['id'],
            'nama'     => $sa['nama'],
            'hp'       => str_replace('-', '', $sa['hp'] ?? ''),
            'role'     => $sa['role'],
            'reguId'   => $sa['regu_id'] ?: '',
            'regu_id'  => $sa['regu_id'] ?: '',
            'regu'     => $sa['regu'] ?: 'Belum Ditugaskan',
            'wilayah'  => $hasSaRegu ? ($sa['wilayah'] ?: '-') : '-',
            'status'   => 'aktif'
        ];
    }

    $formattedServerRegu = [];
    $stmtRegu = $conn->query("SELECT * FROM regu ORDER BY id ASC");
    $serverReguRows = $stmtRegu ? $stmtRegu->fetchAll(PDO::FETCH_ASSOC) : [];
    $reguMembersMap = [];
    foreach ($serverAnggotaRows as $sa) {
        if (!empty($sa['regu_id'])) {
            $reguMembersMap[$sa['regu_id']][] = $sa['id'];
        }
    }
    foreach ($serverReguRows as $sr) {
        $rId = $sr['id'];
        $formattedServerRegu[] = [
            'id'         => $rId,
            'nama'       => $sr['nama'],
            'jenis'      => $sr['jenis'],
            'ketua'      => $sr['ketua'],
            'noHp'       => $sr['no_hp'],
            'no_hp'      => $sr['no_hp'],
            'wilayah'    => $sr['wilayah'],
            'deskripsi'  => $sr['deskripsi'],
            'anggotaIds' => $reguMembersMap[$rId] ?? []
        ];
    }
} catch (Exception $e) {}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
  <title>SILAP Mobile – Portal Lapor Pekerjaan PPSU Pekayon</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@500;600;700;800;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="style.css?v=<?= time() ?>">
  <link rel="icon" type="image/png" href="foto/silap.png">
  <!-- Leaflet Map CSS & JS -->
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
  <style>
    /* ==========================================================================
       SILAP FIGMA DESIGN SYSTEM — HUMAN-FRIENDLY PPSU REPORTING PORTAL
       Crafted with Precision Spacing, Soft Squircle Cards, Layered Figma Shadows &
       Civic Authority Palette: PPSU Jakarta Terracotta Orange, Slate, Soft Canvas
       ========================================================================== */

    :root {
      /* Palette Tokens (Maintained Exact Brand DNA) */
      --color-brand-primary: #C2410C;
      --color-brand-action:  #EA580C;
      --color-brand-hover:   #9A3412;
      --color-brand-light:   #FFF7ED;
      --color-brand-tint:    #FFEDD5;
      --color-brand-border:  #FED7AA;

      /* Slate Neutral Hierarchy (Max 3 visible text contrast tiers) */
      --color-slate-900: #0F172A;
      --color-slate-800: #1E293B;
      --color-slate-700: #334155;
      --color-slate-600: #475569;
      --color-slate-500: #64748B;
      --color-slate-400: #94A3B8;
      --color-slate-300: #CBD5E1;
      --color-slate-200: #E2E8F0;
      --color-slate-100: #F1F5F9;
      --color-slate-50:  #F8FAFC;

      /* Semantic Accents */
      --color-emerald-700: #047857;
      --color-emerald-600: #059669;
      --color-emerald-100: #D1FAE5;
      --color-emerald-50:  #ECFDF5;

      --color-blue-700: #0369A1;
      --color-blue-600: #0284C7;
      --color-blue-100: #E0F2FE;
      --color-blue-50:  #F0F9FF;

      --color-amber-700: #B45309;
      --color-amber-600: #D97706;
      --color-amber-100: #FEF3C7;
      --color-amber-50:  #FFFBEB;

      /* Backward Compatibility Token Aliases */
      --gov-orange-800: #9A3412;
      --gov-orange-700: #C2410C;
      --gov-orange-600: #EA580C;
      --gov-orange-100: #FFEDD5;
      --gov-orange-50:  #FFF7ED;
      --gov-slate-900:  #0F172A;
      --gov-slate-800:  #1E293B;
      --gov-slate-700:  #334155;
      --gov-slate-600:  #475569;
      --gov-slate-400:  #94A3B8;
      --gov-slate-200:  #E2E8F0;
      --gov-emerald-600:#059669;
      --gov-blue-700:   #0369A1;
      --gov-blue-600:   #0284C7;
      --gov-blue-100:   #E0F2FE;
      --gov-amber-600:  #D97706;

      --font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
      --canvas-bg: #F4F6F9;

      /* Figma Layered Shadows (Soft, Multi-Stop Ambient) */
      --shadow-xs: 0 1px 2px rgba(15, 23, 42, 0.04);
      --shadow-sm: 0 1px 3px rgba(15, 23, 42, 0.05), 0 1px 2px rgba(15, 23, 42, 0.03);
      --shadow-card: 0 2px 6px -1px rgba(15, 23, 42, 0.04), 0 10px 24px -4px rgba(15, 23, 42, 0.06);
      --shadow-brand: 0 4px 16px -1px rgba(194, 65, 12, 0.28);
      --shadow-dropdown: 0 12px 32px -4px rgba(15, 23, 42, 0.12);

      /* Ergonomic Figma Radii */
      --radius-sm: 8px;
      --radius-md: 12px;
      --radius-lg: 16px;
      --radius-xl: 20px;
      --radius-pill: 9999px;

      --focus-ring: 0 0 0 3px rgba(194, 65, 12, 0.18);
    }

    /* Base Body Canvas */
    *, *::before, *::after { box-sizing: border-box; }

    html {
      margin: 0;
      padding: 0;
      height: auto;
      overflow-x: clip;
      overflow-y: auto;
      -webkit-overflow-scrolling: touch;
    }

    body {
      margin: 0;
      padding: 0;
      min-height: 100vh;
      height: auto;
      background: #EDF2F7;
      font-family: var(--font-family);
      color: var(--color-slate-800);
      -webkit-font-smoothing: antialiased;
      -moz-osx-font-smoothing: grayscale;
      line-height: 1.5;
      overflow-x: clip;
      overflow-y: visible;
      touch-action: pan-y;
      -webkit-overflow-scrolling: touch;
    }

    /* Full-height centered canvas so content never hugs left edge on desktop */
    .user-portal-page {
      display: flex;
      flex-direction: column;
      align-items: center;
      min-height: 100vh;
      background: #EDF2F7;
      padding: 0;
    }

    /* Focused, Figma-Crafted Centered Mobile Canvas */
    .user-portal-wrapper {
      width: 100%;
      max-width: 540px;
      margin: 0 auto;
      padding: 14px 14px env(safe-area-inset-bottom, 88px) 14px;
      display: flex;
      flex-direction: column;
      align-items: stretch;
      box-sizing: border-box;
    }

    /* All Box Cards in Wrapper: 100% Equal Width, Flush, and Rata */
    .user-portal-topbar,
    .hero-officer-banner,
    .user-tabs-bar,
    .user-main-card,
    .user-phone-card {
      width: 100% !important;
      max-width: 100% !important;
      box-sizing: border-box !important;
      margin-left: 0 !important;
      margin-right: 0 !important;
    }

    /* ==========================================================
       1. TOPBAR NAVIGATION
       ========================================================== */
    .user-portal-topbar {
      width: 100% !important;
      max-width: 100% !important;
      box-sizing: border-box !important;
      background: #FFFFFF;
      border: 1px solid var(--color-slate-200);
      border-radius: 14px;
      padding: 10px 14px;
      margin-bottom: 10px;
      box-shadow: var(--shadow-sm);
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 10px;
    }

    .user-topbar-brand {
      display: flex;
      align-items: center;
      gap: 10px;
      min-width: 0;
      flex: 1 1 auto;
    }

    .user-topbar-brand-text {
      min-width: 0;
      flex: 1 1 auto;
      overflow: visible;
    }

    .user-topbar-logo {
      width: 34px;
      height: 34px;
      object-fit: contain;
      border-radius: var(--radius-sm);
      flex-shrink: 0;
    }

    .user-topbar-title {
      font-size: 0.94rem;
      font-weight: 800;
      color: var(--color-slate-900);
      letter-spacing: -0.02em;
      line-height: 1.2;
      white-space: nowrap;
    }

    .user-topbar-sub {
      font-size: 0.72rem;
      font-weight: 700;
      color: var(--color-slate-600);
      line-height: 1.25;
      white-space: normal;
      overflow: visible;
      word-break: break-word;
    }

    .user-topbar-actions {
      display: flex;
      align-items: center;
      gap: 6px;
      flex-shrink: 0;
    }

    .topbar-live-status {
      display: inline-flex !important;
      align-items: center !important;
      gap: 6px !important;
      background: var(--color-emerald-50) !important;
      border: 1px solid var(--color-emerald-100) !important;
      padding: 5px 10px !important;
      border-radius: var(--radius-pill) !important;
      font-size: 0.72rem !important;
      font-weight: 700 !important;
      color: var(--color-emerald-700) !important;
    }

    .live-pulse-emerald {
      width: 7px !important;
      height: 7px !important;
      border-radius: 50% !important;
      background: var(--color-emerald-600) !important;
      box-shadow: 0 0 0 0 rgba(5, 150, 105, 0.7) !important;
      animation: pulseEmerald 2s infinite !important;
    }

    @keyframes pulseEmerald {
      0% { box-shadow: 0 0 0 0 rgba(5, 150, 105, 0.7); }
      70% { box-shadow: 0 0 0 6px rgba(5, 150, 105, 0); }
      100% { box-shadow: 0 0 0 0 rgba(5, 150, 105, 0); }
    }

    .user-pill-btn {
      background: var(--color-slate-50) !important;
      border: 1px solid var(--color-slate-300) !important;
      border-radius: var(--radius-pill) !important;
      padding: 6px 12px !important;
      font-size: 0.78rem !important;
      font-weight: 700 !important;
      color: var(--color-slate-700) !important;
      display: inline-flex !important;
      align-items: center !important;
      gap: 5px !important;
      text-decoration: none !important;
      cursor: pointer !important;
      transition: all 0.15s ease !important;
    }

    .user-pill-btn:hover {
      background: #FFFFFF !important;
      border-color: var(--color-slate-400) !important;
      color: var(--color-slate-900) !important;
      transform: translateY(-1px) !important;
    }

    .user-pill-btn--admin {
      background: #FFF7ED !important;
      border-color: #FED7AA !important;
      color: #C2410C !important;
    }

    .user-pill-btn--logout {
      background: #FEF2F2 !important;
      border-color: #FECACA !important;
      color: #DC2626 !important;
    }

    .user-pill-btn.primary {
      background: var(--color-brand-primary) !important;
      border-color: var(--color-brand-primary) !important;
      color: #FFFFFF !important;
    }

    .user-pill-btn.primary:hover {
      background: var(--color-brand-hover) !important;
    }

    .btn-export-excel-user {
      background: #10B981 !important;
      color: #FFFFFF !important;
      border: 1px solid #059669 !important;
      border-radius: var(--radius-pill) !important;
      padding: 6px 12px !important;
      font-size: 0.78rem !important;
      font-weight: 700 !important;
      display: inline-flex !important;
      align-items: center !important;
      gap: 5px !important;
      cursor: pointer !important;
      transition: all 0.15s ease !important;
      box-shadow: 0 2px 6px rgba(16, 185, 129, 0.2) !important;
    }
    .btn-export-excel-user:hover {
      background: #059669 !important;
      transform: translateY(-1px) !important;
    }

    /* Hero banner: clean, mobile-tight */
    .hero-officer-banner {
      background: linear-gradient(135deg, #C2410C 0%, #EA580C 100%);
      border-radius: 14px;
      padding: 14px 14px 12px 14px;
      color: #FFFFFF;
      box-shadow: 0 4px 14px rgba(194, 65, 12, 0.22);
      margin-bottom: 10px;
      overflow: hidden;
      box-sizing: border-box;
    }

    .hero-header-top {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 6px;
      flex-wrap: wrap;
      margin-bottom: 9px;
    }

    .hero-profile-row {
      display: flex;
      align-items: center;
      gap: 12px;
      margin-bottom: 12px;
    }

    .hero-avatar-box {
      width: 44px;
      height: 44px;
      border-radius: 12px;
      background: #FFFFFF;
      color: #C2410C;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.25rem;
      font-weight: 800;
      flex-shrink: 0;
      box-shadow: 0 2px 6px rgba(0,0,0,0.12);
    }

    .hero-profile-info {
      flex: 1;
      min-width: 0;
    }

    .hero-greeting-text {
      font-size: 0.72rem;
      font-weight: 800;
      color: rgba(255,255,255,0.92);
      text-transform: uppercase;
      letter-spacing: 0.05em;
      line-height: 1;
    }

    .hero-officer-name {
      font-size: 1.15rem;
      font-weight: 800;
      color: #FFFFFF;
      line-height: 1.25;
      letter-spacing: -0.01em;
      word-break: break-word;
      white-space: normal;
    }

    .hero-sub-text {
      font-size: 0.75rem;
      color: rgba(255,255,255,0.92);
      font-weight: 500;
      margin-top: 4px;
      display: flex;
      align-items: center;
      flex-wrap: wrap;
      gap: 6px;
      line-height: 1.4;
    }

    .hero-phone-badge {
      display: inline-flex;
      align-items: center;
      gap: 4px;
      background: rgba(255, 255, 255, 0.22);
      border: 1px solid rgba(255, 255, 255, 0.45);
      border-radius: 6px;
      padding: 1px 7px;
      font-weight: 700;
      color: #FFFFFF;
      font-size: 0.75rem;
      letter-spacing: 0.02em;
      user-select: all;
    }

    .hero-org-badge {
      color: rgba(255, 255, 255, 0.88);
      font-size: 0.73rem;
    }

    /* Field Dock: equal 4-column, no overflow */
    .hero-field-dock {
      background: #FFFFFF;
      border-radius: 11px;
      padding: 8px 6px;
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      align-items: center;
      gap: 0;
      box-shadow: 0 2px 6px rgba(0,0,0,0.07);
    }

    .dock-item {
      display: flex;
      flex-direction: column;
      align-items: center;
      text-align: center;
      min-width: 0;
      padding: 3px 2px;
    }

    .dock-item:not(:last-child) {
      border-right: 1px solid #F1F5F9;
    }

    .dock-label {
      font-size: 0.6rem;
      font-weight: 700;
      color: #64748B;
      text-transform: uppercase;
      letter-spacing: 0.04em;
      margin-bottom: 2px;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
      max-width: 100%;
    }

    .dock-value {
      font-size: 0.76rem;
      font-weight: 800;
      color: #0F172A;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
      max-width: 100%;
    }

    .dock-value.gps-status {
      color: #047857;
    }

    /* ==========================================================
       3. SEGMENTED TABS
       ========================================================== */
    .user-tabs-bar {
      width: 100% !important;
      max-width: 100% !important;
      box-sizing: border-box !important;
      background: var(--color-slate-200);
      padding: 4px;
      border-radius: 14px;
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 4px;
      margin: 0 0 12px 0 !important;
    }

    .user-tab-item {
      padding: 9px 12px;
      font-family: inherit;
      font-size: 0.84rem;
      font-weight: 700;
      color: var(--color-slate-600);
      background: transparent;
      border: none;
      border-radius: var(--radius-md);
      cursor: pointer;
      transition: all 0.18s cubic-bezier(0.4, 0, 0.2, 1);
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 7px;
    }

    .user-tab-item:hover { color: var(--color-slate-900); }

    .user-tab-item.active {
      background: #FFFFFF;
      color: var(--color-brand-primary);
      font-weight: 800;
      box-shadow: 0 2px 8px rgba(15,23,42,0.08);
    }

    /* ==========================================================
       4. MAIN FORM CARD
       ========================================================== */
    .user-phone-card,
    .user-main-card {
      width: 100%;
      background: #FFFFFF;
      border: 1px solid var(--color-slate-200);
      border-radius: var(--radius-xl);
      box-shadow: var(--shadow-card);
      overflow: hidden;
    }

    .user-card-body {
      padding: 18px 16px;
      background: #FFFFFF;
    }

    /* Sleek Stepper Header */
    .user-wizard-progress {
      display: flex !important;
      align-items: center !important;
      justify-content: space-between !important;
      margin-bottom: 16px !important;
      padding: 10px 14px !important;
      background: var(--color-slate-50) !important;
      border: 1px solid var(--color-slate-200) !important;
      border-radius: var(--radius-md) !important;
    }

    .wizard-step-node {
      display: flex !important;
      align-items: center !important;
      gap: 10px !important;
      cursor: pointer !important;
      background: none !important;
      border: none !important;
      padding: 3px !important;
      font-family: inherit !important;
      text-align: left !important;
      transition: all 0.18s ease !important;
    }

    .node-circle {
      width: 28px !important;
      height: 28px !important;
      border-radius: 50% !important;
      background: var(--color-slate-200) !important;
      color: var(--color-slate-600) !important;
      font-size: 0.8rem !important;
      font-weight: 800 !important;
      display: flex !important;
      align-items: center !important;
      justify-content: center !important;
      flex-shrink: 0 !important;
      transition: all 0.18s ease !important;
    }

    .wizard-step-node.active .node-circle {
      background: var(--color-brand-primary) !important;
      color: #FFFFFF !important;
      box-shadow: 0 0 0 3px var(--color-brand-tint) !important;
    }

    .wizard-step-node.completed .node-circle {
      background: var(--color-emerald-600) !important;
      color: #FFFFFF !important;
    }

    .node-text {
      display: flex !important;
      flex-direction: column !important;
    }

    .node-sub {
      font-size: 0.65rem !important;
      font-weight: 700 !important;
      color: var(--color-slate-500) !important;
      text-transform: uppercase !important;
      letter-spacing: 0.04em !important;
    }

    .node-title {
      font-size: 0.82rem !important;
      font-weight: 800 !important;
      color: var(--color-slate-600) !important;
      line-height: 1.2 !important;
    }

    .wizard-step-node.active .node-title {
      color: var(--color-slate-900) !important;
    }

    .wizard-step-line {
      flex: 1 !important;
      height: 2px !important;
      background: var(--color-slate-300) !important;
      margin: 0 12px !important;
      position: relative !important;
      border-radius: var(--radius-pill) !important;
    }

    .wizard-step-line.step2-active::after {
      content: "" !important;
      position: absolute !important;
      inset: 0 !important;
      background: var(--color-emerald-600) !important;
      border-radius: var(--radius-pill) !important;
    }

    /* Auto Save Draft Ribbon */
    .draft-status-bar {
      display: flex !important;
      align-items: center !important;
      justify-content: space-between !important;
      margin-bottom: 16px !important;
      padding: 7px 12px !important;
      background: var(--color-slate-50) !important;
      border: 1px solid var(--color-slate-200) !important;
      border-radius: var(--radius-sm) !important;
      font-size: 0.74rem !important;
      color: var(--color-slate-600) !important;
      font-weight: 600 !important;
    }

    .draft-pill-saved {
      display: inline-flex !important;
      align-items: center !important;
      gap: 5px !important;
      color: var(--color-emerald-700) !important;
      font-weight: 700 !important;
      background: var(--color-emerald-50) !important;
      padding: 2px 8px !important;
      border-radius: var(--radius-pill) !important;
      border: 1px solid var(--color-emerald-100) !important;
    }

    /* ==========================================================
       5. SECTION CARDS
       ========================================================== */
    .figma-section-card,
    .job-id-custom-card,
    .u-step-group {
      background: #FFFFFF;
      border: 1px solid var(--color-slate-200);
      border-radius: var(--radius-lg);
      padding: 16px;
      margin-bottom: 14px;
      box-shadow: var(--shadow-sm);
      transition: border-color 0.15s ease;
    }

    .figma-section-card:hover,
    .job-id-custom-card:hover,
    .u-step-group:hover {
      border-color: var(--color-slate-300);
    }

    .section-header-row {
      display: flex !important;
      align-items: center !important;
      gap: 10px !important;
      margin-bottom: 14px !important;
      padding-bottom: 10px !important;
      border-bottom: 1px solid var(--color-slate-100) !important;
    }

    .section-header-icon {
      width: 32px !important;
      height: 32px !important;
      border-radius: var(--radius-sm) !important;
      background: var(--color-brand-light) !important;
      color: var(--color-brand-primary) !important;
      display: flex !important;
      align-items: center !important;
      justify-content: center !important;
      flex-shrink: 0 !important;
    }

    .section-header-text {
      flex: 1 !important;
      min-width: 0 !important;
    }

    .section-title {
      font-size: 0.94rem !important;
      font-weight: 800 !important;
      color: var(--color-slate-900) !important;
      line-height: 1.25 !important;
      margin: 0 !important;
    }

    .section-sub {
      font-size: 0.74rem !important;
      color: var(--color-slate-500) !important;
      font-weight: 500 !important;
      margin-top: 1px !important;
    }

    .field-label {
      display: flex !important;
      align-items: center !important;
      justify-content: space-between !important;
      font-size: 0.82rem !important;
      font-weight: 700 !important;
      color: var(--color-slate-700) !important;
      margin-bottom: 7px !important;
    }

    .field-tag {
      font-size: 0.7rem !important;
      font-weight: 700 !important;
      color: var(--color-slate-500) !important;
    }

    /* Backward compatibility for original labels */
    .u-step-label,
    .job-id-custom-label {
      display: block !important;
      font-size: 0.86rem !important;
      font-weight: 800 !important;
      color: var(--color-slate-900) !important;
      margin-bottom: 8px !important;
      letter-spacing: -0.01em !important;
    }

    .u-step-label span.opt {
      font-size: 0.75rem !important;
      font-weight: 500 !important;
      color: var(--color-slate-500) !important;
    }

    .badge-harian {
      background: var(--color-emerald-50) !important;
      color: var(--color-emerald-700) !important;
      border: 1px solid var(--color-emerald-100) !important;
      font-size: 0.72rem !important;
      font-weight: 700 !important;
      padding: 3px 8px !important;
      border-radius: 6px !important;
      display: inline-flex !important;
      align-items: center !important;
    }

    .badge-status {
      border-radius: 6px !important;
    }

    /* Dokumentasi Riwayat Foto Kotak Terpadu */
    .riwayat-photo-item {
      width: 100% !important;
      box-sizing: border-box !important;
      border-radius: 8px !important;
      overflow: hidden !important;
      border: 1.5px solid var(--color-slate-300) !important;
      background: #FFFFFF !important;
      display: flex !important;
      flex-direction: column !important;
      box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05) !important;
      padding: 0 !important;
      margin: 0 !important;
    }

    .uh-thumb-grid {
      width: 100% !important;
      max-width: 100% !important;
      height: 84px !important;
      object-fit: cover !important;
      display: block !important;
      border: none !important;
      border-radius: 0 !important;
      margin: 0 !important;
      padding: 0 !important;
      flex-shrink: 0 !important;
    }

    .riwayat-photo-label {
      font-size: 0.68rem !important;
      font-weight: 700 !important;
      color: var(--color-slate-700) !important;
      padding: 5px 3px !important;
      text-align: center !important;
      background: #FFFFFF !important;
      white-space: nowrap !important;
      overflow: hidden !important;
      text-overflow: ellipsis !important;
      border-top: 1px solid var(--color-slate-200) !important;
      line-height: 1.25 !important;
    }

    /* ==========================================================
       6. 10 REGU PPSU BUTTONS (FIGMA INTERACTIVE CHIPS)
       ========================================================== */
    .regu-pill-grid {
      display: grid !important;
      grid-template-columns: repeat(5, 1fr) !important;
      gap: 6px !important;
      margin-top: 6px !important;
    }

    .btn-regu-pill {
      background: var(--color-slate-50) !important;
      border: 1px solid var(--color-slate-200) !important;
      border-radius: var(--radius-md) !important;
      padding: 9px 4px !important;
      font-family: inherit !important;
      font-size: 0.8rem !important;
      font-weight: 700 !important;
      color: var(--color-slate-700) !important;
      cursor: pointer !important;
      text-align: center !important;
      transition: all 0.15s cubic-bezier(0.4, 0, 0.2, 1) !important;
      white-space: nowrap !important;
    }

    .btn-regu-pill:hover {
      background: #FFFFFF !important;
      border-color: var(--color-slate-300) !important;
      color: var(--color-slate-900) !important;
      transform: translateY(-1px) !important;
    }

    .btn-regu-pill.active {
      background: var(--color-brand-primary) !important;
      border-color: var(--color-brand-primary) !important;
      color: #FFFFFF !important;
      font-weight: 800 !important;
      box-shadow: var(--shadow-brand) !important;
    }

    .regu-selected-display {
      background: #FFF7ED !important;
      border: 1px solid #FED7AA !important;
      border-radius: 12px !important;
      padding: 9px 12px !important;
      margin-top: 8px !important;
      display: flex !important;
      align-items: center !important;
      justify-content: space-between !important;
      gap: 10px !important;
      box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04) !important;
      transition: all 0.2s ease !important;
    }
    .regu-selected-display.is-assigned {
      background: #ECFDF5 !important;
      border-color: #A7F3D0 !important;
    }
    .regu-display-info {
      display: flex !important;
      align-items: center !important;
      gap: 9px !important;
      min-width: 0 !important;
      flex: 1 !important;
    }
    .regu-display-icon {
      width: 34px !important;
      height: 34px !important;
      border-radius: 9px !important;
      background: #FFEDD5 !important;
      color: #C2410C !important;
      display: flex !important;
      align-items: center !important;
      justify-content: center !important;
      flex-shrink: 0 !important;
    }
    .regu-selected-display.is-assigned .regu-display-icon {
      background: #D1FAE5 !important;
      color: #047857 !important;
    }
    .regu-display-texts {
      display: flex !important;
      flex-direction: column !important;
      min-width: 0 !important;
      line-height: 1.25 !important;
    }
    .regu-display-lbl {
      font-size: 0.65rem !important;
      font-weight: 700 !important;
      color: #9A3412 !important;
      text-transform: uppercase !important;
      letter-spacing: 0.05em !important;
      margin-bottom: 2px !important;
    }
    .regu-selected-display.is-assigned .regu-display-lbl {
      color: #065F46 !important;
    }
    .regu-display-val {
      font-size: 0.88rem !important;
      font-weight: 800 !important;
      color: #C2410C !important;
      white-space: nowrap !important;
      overflow: hidden !important;
      text-overflow: ellipsis !important;
    }
    .regu-selected-display.is-assigned .regu-display-val {
      color: #047857 !important;
    }
    .regu-display-badge {
      background: #FEF3C7 !important;
      color: #B45309 !important;
      border: 1px solid #FDE68A !important;
      font-size: 0.72rem !important;
      font-weight: 800 !important;
      padding: 4px 10px !important;
      border-radius: 20px !important;
      white-space: nowrap !important;
      flex-shrink: 0 !important;
      display: inline-flex !important;
      align-items: center !important;
      gap: 4px !important;
    }
    .regu-display-badge.active {
      background: #ECFDF5 !important;
      color: #047857 !important;
      border-color: #A7F3D0 !important;
    }
    .field-tag-required {
      color: #DC2626 !important;
      font-size: 0.72rem !important;
      font-weight: 700 !important;
      white-space: nowrap !important;
      flex-shrink: 0 !important;
    }
    .uh-card-title {
      font-size: 0.96rem !important;
      font-weight: 800 !important;
      color: var(--gov-slate-900) !important;
      margin: 2px 0 !important;
      line-height: 1.35 !important;
      word-break: keep-all !important;
      overflow-wrap: normal !important;
    }
    .uh-badge-row {
      display: flex !important;
      align-items: center !important;
      gap: 5px !important;
      flex-wrap: nowrap !important;
      overflow-x: auto !important;
      -webkit-overflow-scrolling: touch !important;
      scrollbar-width: none !important;
      margin-bottom: 6px !important;
      max-width: 100% !important;
    }
    .uh-badge-row::-webkit-scrollbar {
      display: none !important;
    }
    .uh-badge-row > * {
      flex-shrink: 0 !important;
      white-space: nowrap !important;
    }

    .job-id-custom-hint {
      font-size: 0.73rem !important;
      color: var(--color-slate-500) !important;
      margin-top: 8px !important;
      line-height: 1.4 !important;
    }

    /* ==========================================================
       7. HARI & TUGAS SEGMENTED BUTTONS
       ========================================================== */
    .harian-day-grid {
      display: grid !important;
      grid-template-columns: repeat(5, 1fr) !important;
      gap: 6px !important;
      margin-top: 6px !important;
    }

    .tugas-day-grid {
      display: flex !important;
      overflow-x: auto !important;
      -webkit-overflow-scrolling: touch !important;
      gap: 6px !important;
      margin-top: 6px !important;
      padding-bottom: 4px !important;
      scrollbar-width: none !important;
      width: 100% !important;
      box-sizing: border-box !important;
    }
    .tugas-day-grid::-webkit-scrollbar {
      display: none !important;
    }

    .btn-day-pill {
      background: var(--color-slate-50) !important;
      border: 1px solid var(--color-slate-200) !important;
      border-radius: var(--radius-md) !important;
      padding: 9px 4px !important;
      font-family: inherit !important;
      font-size: 0.8rem !important;
      font-weight: 700 !important;
      color: var(--color-slate-700) !important;
      cursor: pointer !important;
      text-align: center !important;
      white-space: nowrap !important;
      transition: all 0.15s ease !important;
    }

    .btn-tugas-pill {
      background: var(--color-slate-50) !important;
      border: 1px solid var(--color-slate-200) !important;
      border-radius: var(--radius-md) !important;
      padding: 8px 12px !important;
      font-family: inherit !important;
      font-size: 0.8rem !important;
      font-weight: 700 !important;
      color: var(--color-slate-700) !important;
      cursor: pointer !important;
      text-align: center !important;
      white-space: nowrap !important;
      flex: 0 0 auto !important;
      min-width: 68px !important;
      transition: all 0.15s ease !important;
    }
    .btn-tugas-pill span {
      white-space: nowrap !important;
    }

    .btn-day-pill:hover,
    .btn-tugas-pill:hover {
      background: #FFFFFF !important;
      border-color: var(--color-slate-300) !important;
    }

    .btn-day-pill.active {
      background: var(--color-brand-primary) !important;
      border-color: var(--color-brand-primary) !important;
      color: #FFFFFF !important;
      font-weight: 800 !important;
      box-shadow: var(--shadow-brand) !important;
    }

    .btn-tugas-pill.active {
      background: var(--color-blue-700) !important;
      border-color: var(--color-blue-700) !important;
      color: #FFFFFF !important;
      font-weight: 800 !important;
      box-shadow: 0 4px 12px rgba(3, 105, 161, 0.22) !important;
    }

    /* ==========================================================
       8. 5 JENIS PEKERJAAN (FIGMA CARD TILES)
       ========================================================== */
    .job-cards-vertical {
      display: flex !important;
      flex-direction: column !important;
      gap: 9px !important;
      margin-top: 6px !important;
    }

    .job-card-selectable {
      background: #FFFFFF !important;
      border: 1.5px solid var(--color-slate-200) !important;
      border-radius: var(--radius-md) !important;
      padding: 12px 14px !important;
      display: flex !important;
      align-items: center !important;
      gap: 12px !important;
      cursor: pointer !important;
      transition: all 0.18s cubic-bezier(0.4, 0, 0.2, 1) !important;
      text-align: left !important;
      position: relative !important;
      box-shadow: var(--shadow-xs) !important;
    }

    .job-card-selectable:hover {
      border-color: var(--color-slate-300) !important;
      background: var(--color-slate-50) !important;
      transform: translateY(-1px) !important;
    }

    .job-card-selectable.active {
      background: var(--color-brand-light) !important;
      border-color: var(--color-brand-primary) !important;
      box-shadow: 0 2px 10px rgba(194, 65, 12, 0.12) !important;
    }

    .job-card-icon {
      width: 40px !important;
      height: 40px !important;
      border-radius: var(--radius-md) !important;
      background: var(--color-slate-100) !important;
      color: var(--color-slate-700) !important;
      display: flex !important;
      align-items: center !important;
      justify-content: center !important;
      flex-shrink: 0 !important;
      transition: all 0.18s ease !important;
    }

    .job-card-selectable.active .job-card-icon {
      background: var(--color-brand-primary) !important;
      color: #FFFFFF !important;
    }

    .job-card-text {
      flex: 1 !important;
      min-width: 0 !important;
    }

    .job-card-title {
      font-size: 0.88rem !important;
      font-weight: 800 !important;
      color: var(--color-slate-900) !important;
      line-height: 1.3 !important;
      margin-bottom: 2px !important;
    }

    .job-card-selectable.active .job-card-title {
      color: #7C2D12 !important;
    }

    .job-card-desc {
      font-size: 0.74rem !important;
      color: var(--color-slate-500) !important;
      line-height: 1.35 !important;
      font-weight: 500 !important;
    }

    .job-card-check {
      width: 22px !important;
      height: 22px !important;
      border-radius: 50% !important;
      border: 2px solid var(--color-slate-300) !important;
      display: flex !important;
      align-items: center !important;
      justify-content: center !important;
      font-size: 0.72rem !important;
      color: transparent !important;
      flex-shrink: 0 !important;
      transition: all 0.15s ease !important;
    }

    .job-card-selectable.active .job-card-check {
      background: var(--color-brand-primary) !important;
      border-color: var(--color-brand-primary) !important;
      color: #FFFFFF !important;
      font-weight: 800 !important;
    }

    /* ==========================================================
       9. INPUT FIELDS & LOCATION BOX
       ========================================================== */
    .u-input-field,
    .u-textarea {
      width: 100% !important;
      background: #FFFFFF !important;
      border: 1.5px solid var(--color-slate-300) !important;
      border-radius: var(--radius-md) !important;
      padding: 10px 13px !important;
      font-family: inherit !important;
      font-size: 0.88rem !important;
      font-weight: 600 !important;
      color: var(--color-slate-900) !important;
      outline: none !important;
      transition: all 0.15s ease !important;
      box-sizing: border-box !important;
    }

    .u-input-field:focus,
    .u-textarea:focus {
      border-color: var(--color-brand-primary) !important;
      box-shadow: var(--focus-ring) !important;
      background: #FFFFFF !important;
    }

    .u-textarea {
      min-height: 85px !important;
      resize: vertical !important;
      line-height: 1.45 !important;
    }

    .location-box-card {
      background: #F8FAFC !important;
      border: 1.5px solid var(--color-slate-200) !important;
      border-radius: 12px !important;
      padding: 12px !important;
      display: flex !important;
      flex-direction: column !important;
      align-items: stretch !important;
      justify-content: flex-start !important;
      gap: 10px !important;
      box-sizing: border-box !important;
      width: 100% !important;
      overflow: hidden !important;
    }

    .loc-sub {
      font-size: 0.68rem !important;
      font-weight: 700 !important;
      color: var(--color-slate-500) !important;
      text-transform: uppercase !important;
      letter-spacing: 0.04em !important;
    }

    .loc-main {
      font-size: 0.84rem !important;
      font-weight: 800 !important;
      color: var(--color-slate-900) !important;
      margin-top: 1px !important;
      line-height: 1.35 !important;
    }

    .loc-coords-sub {
      font-size: 0.72rem !important;
      font-weight: 700 !important;
      color: var(--color-emerald-700) !important;
      margin-top: 3px !important;
      display: flex !important;
      align-items: center !important;
      gap: 4px !important;
    }

    .loc-actions-col {
      display: flex !important;
      flex-direction: column !important;
      gap: 6px !important;
      flex-shrink: 0 !important;
    }

    .btn-loc-change {
      background: #FFFFFF !important;
      border: 1px solid var(--color-slate-300) !important;
      border-radius: var(--radius-sm) !important;
      padding: 6px 11px !important;
      font-family: inherit !important;
      font-size: 0.74rem !important;
      font-weight: 700 !important;
      color: var(--color-slate-700) !important;
      cursor: pointer !important;
      display: inline-flex !important;
      align-items: center !important;
      justify-content: center !important;
      gap: 4px !important;
      white-space: nowrap !important;
      transition: all 0.15s ease !important;
    }

    .btn-loc-change:hover {
      background: var(--color-slate-100) !important;
      color: var(--color-slate-900) !important;
      border-color: var(--color-slate-400) !important;
    }

    .btn-loc-map {
      background: var(--color-emerald-50) !important;
      border: 1px solid var(--color-emerald-600) !important;
      border-radius: var(--radius-sm) !important;
      padding: 5px 10px !important;
      font-family: inherit !important;
      font-size: 0.72rem !important;
      font-weight: 700 !important;
      color: var(--color-emerald-700) !important;
      display: inline-flex !important;
      align-items: center !important;
      justify-content: center !important;
      gap: 4px !important;
      text-decoration: none !important;
      white-space: nowrap !important;
      transition: all 0.15s ease !important;
    }

    .btn-loc-map:hover {
      background: var(--color-emerald-100) !important;
    }

    .btn-loc-reset {
      background: var(--color-slate-100) !important;
      border: 1px solid var(--color-slate-300) !important;
      border-radius: var(--radius-sm) !important;
      padding: 5px 9px !important;
      font-family: inherit !important;
      font-size: 0.72rem !important;
      font-weight: 700 !important;
      color: var(--color-slate-700) !important;
      cursor: pointer !important;
      display: inline-flex !important;
      align-items: center !important;
      justify-content: center !important;
      gap: 4px !important;
      white-space: nowrap !important;
      transition: all 0.15s ease !important;
    }

    .btn-loc-reset:hover {
      background: var(--color-slate-200) !important;
      color: var(--color-slate-900) !important;
    }

    .gps-dot-pulse {
      display: inline-block !important;
      color: #16A34A !important;
      font-size: 0.85rem !important;
      line-height: 1 !important;
      animation: gpsDotPulse 1.8s infinite ease-in-out !important;
    }

    @keyframes gpsDotPulse {
      0%, 100% { opacity: 1; transform: scale(1); }
      50% { opacity: 0.35; transform: scale(1.3); }
    }

    @keyframes pinRipplePulse {
      0% { transform: scale(0.85); opacity: 0.9; }
      100% { transform: scale(2.3); opacity: 0; }
    }

    .leaflet-control-zoom {
      border: 1px solid #CBD5E1 !important;
      box-shadow: 0 1px 4px rgba(0,0,0,0.12) !important;
      border-radius: 8px !important;
      overflow: hidden !important;
    }
    .leaflet-control-zoom-in, .leaflet-control-zoom-out {
      width: 28px !important;
      height: 28px !important;
      line-height: 28px !important;
      font-size: 14px !important;
      color: #334155 !important;
    }

    /* ==========================================================
       10. ACTION BUTTONS & STEP NAVIGATION (FIGMA CTA)
       ========================================================== */
    .btn-step-next {
      width: 100% !important;
      min-height: 52px !important;
      background: linear-gradient(135deg, var(--color-brand-primary) 0%, var(--color-brand-action) 100%) !important;
      color: #FFFFFF !important;
      border: none !important;
      border-radius: var(--radius-lg) !important;
      padding: 13px 20px !important;
      font-family: inherit !important;
      cursor: pointer !important;
      box-shadow: var(--shadow-brand) !important;
      display: flex !important;
      align-items: center !important;
      justify-content: space-between !important;
      gap: 12px !important;
      transition: all 0.18s cubic-bezier(0.4, 0, 0.2, 1) !important;
      margin-top: 16px !important;
    }

    .btn-step-next:hover {
      background: linear-gradient(135deg, var(--color-brand-hover) 0%, var(--color-brand-primary) 100%) !important;
      transform: translateY(-1px) !important;
      box-shadow: 0 6px 18px rgba(194, 65, 12, 0.35) !important;
    }

    .btn-step-next:active {
      transform: scale(0.98) !important;
    }

    .stepper-actions-row {
      display: flex !important;
      gap: 12px !important;
      align-items: center !important;
      margin-top: 16px !important;
    }

    .btn-step-back {
      background: #FFFFFF !important;
      border: 1.5px solid var(--color-slate-300) !important;
      color: var(--color-slate-700) !important;
      border-radius: var(--radius-lg) !important;
      padding: 13px 18px !important;
      font-family: inherit !important;
      font-size: 0.9rem !important;
      font-weight: 800 !important;
      cursor: pointer !important;
      display: inline-flex !important;
      align-items: center !important;
      justify-content: center !important;
      gap: 6px !important;
      transition: all 0.15s ease !important;
      white-space: nowrap !important;
      min-height: 50px !important;
    }

    .btn-step-back:hover {
      background: var(--color-slate-50) !important;
      color: var(--color-slate-900) !important;
      border-color: var(--color-slate-400) !important;
    }

    .btn-step-back:active {
      transform: scale(0.98) !important;
    }

    .btn-step-draft {
      background: #FFFBEB !important;
      border: 1.5px solid #FDE68A !important;
      color: #B45309 !important;
      border-radius: var(--radius-lg) !important;
      padding: 13px 18px !important;
      font-family: inherit !important;
      font-size: 0.9rem !important;
      font-weight: 800 !important;
      cursor: pointer !important;
      display: inline-flex !important;
      align-items: center !important;
      justify-content: center !important;
      gap: 6px !important;
      transition: all 0.15s ease !important;
      white-space: nowrap !important;
      min-height: 50px !important;
    }

    .btn-step-draft:hover {
      background: #FEF3C7 !important;
      color: #92400E !important;
      border-color: #F59E0B !important;
    }

    .btn-step-draft:active {
      transform: scale(0.98) !important;
    }

    .btn-kirim-laporan {
      flex: 1 !important;
      min-height: 50px !important;
      background: linear-gradient(135deg, var(--color-emerald-700) 0%, var(--color-emerald-600) 100%) !important;
      color: #FFFFFF !important;
      border: none !important;
      border-radius: var(--radius-lg) !important;
      padding: 13px 18px !important;
      font-family: inherit !important;
      font-size: 0.94rem !important;
      font-weight: 800 !important;
      cursor: pointer !important;
      display: inline-flex !important;
      align-items: center !important;
      justify-content: center !important;
      gap: 8px !important;
      box-shadow: 0 4px 14px rgba(4, 120, 87, 0.28) !important;
      transition: all 0.18s ease !important;
    }

    .btn-kirim-laporan:hover {
      background: linear-gradient(135deg, #03684A 0%, var(--color-emerald-700) 100%) !important;
      transform: translateY(-1px) !important;
    }

    .btn-kirim-laporan:active {
      transform: scale(0.98) !important;
    }

    /* ==========================================================
       11. STEP 2: PROGRES & PHOTO DOCUMENTATION FRAMES
       ========================================================== */
    .quick-progress-pills {
      display: grid !important;
      grid-template-columns: repeat(3, 1fr) !important;
      gap: 8px !important;
      margin-bottom: 12px !important;
    }

    .btn-prog-pill {
      background: #FFFFFF !important;
      border: 1.5px solid var(--color-slate-300) !important;
      border-radius: var(--radius-md) !important;
      padding: 10px 6px !important;
      font-family: inherit !important;
      font-size: 0.8rem !important;
      font-weight: 700 !important;
      color: var(--color-slate-700) !important;
      cursor: pointer !important;
      text-align: center !important;
      transition: all 0.15s ease !important;
    }

    .btn-prog-pill:hover {
      background: var(--color-slate-50) !important;
    }

    .btn-prog-pill.active {
      background: var(--color-brand-primary) !important;
      border-color: var(--color-brand-primary) !important;
      color: #FFFFFF !important;
      font-weight: 800 !important;
      box-shadow: var(--shadow-brand) !important;
    }

    .btn-prog-pill.locked {
      opacity: 0.45 !important;
      cursor: not-allowed !important;
      background: var(--color-slate-100) !important;
      border-color: var(--color-slate-200) !important;
      color: var(--color-slate-400) !important;
    }

    .u-range-wrap {
      display: flex !important;
      align-items: center !important;
      gap: 12px !important;
      margin-top: 8px !important;
    }

    .u-range-slider {
      flex: 1 !important;
      accent-color: var(--color-brand-primary) !important;
      height: 6px !important;
      border-radius: var(--radius-pill) !important;
      background: var(--color-slate-200) !important;
    }

    .u-range-badge {
      font-size: 0.88rem !important;
      font-weight: 800 !important;
      color: var(--color-brand-primary) !important;
      background: var(--color-brand-tint) !important;
      padding: 3px 10px !important;
      border-radius: var(--radius-pill) !important;
      min-width: 44px !important;
      text-align: center !important;
    }

    /* 3 Photo Inspection Slots */
    .photo-slot-grid {
      display: grid !important;
      grid-template-columns: repeat(3, 1fr) !important;
      gap: 10px !important;
      margin-top: 10px !important;
    }

    @media (max-width: 480px) {
      .photo-slot-grid {
        gap: 6px !important;
      }
    }

    .photo-slot-card {
      min-height: 140px !important;
      border: 2px dashed var(--color-slate-300) !important;
      border-radius: var(--radius-lg) !important;
      background: var(--color-slate-50) !important;
      padding: 14px 10px !important;
      display: flex !important;
      flex-direction: column !important;
      align-items: center !important;
      justify-content: center !important;
      text-align: center !important;
      cursor: pointer !important;
      transition: all 0.18s ease !important;
      position: relative !important;
      overflow: hidden !important;
    }

    .photo-slot-card:hover {
      border-color: var(--color-brand-primary) !important;
      background: var(--color-brand-light) !important;
    }

    .photo-slot-card.has-photo {
      border-style: solid !important;
      border-color: var(--color-emerald-600) !important;
      background: #FFFFFF !important;
      padding: 0 !important;
    }

    .photo-slot-card.has-photo img {
      width: 100% !important;
      height: 100% !important;
      object-fit: cover !important;
      border-radius: calc(var(--radius-lg) - 2px) !important;
    }

    .photo-camera-icon {
      color: var(--color-brand-primary) !important;
      margin-bottom: 6px !important;
    }

    .photo-slot-title {
      font-size: 0.82rem !important;
      font-weight: 800 !important;
      color: var(--color-slate-900) !important;
    }

    .photo-slot-sub {
      font-size: 0.7rem !important;
      color: var(--color-slate-500) !important;
      margin-top: 2px !important;
    }

    .photo-stage-pill {
      font-size: 0.66rem !important;
      font-weight: 700 !important;
      padding: 2px 7px !important;
      border-radius: 4px !important;
      margin-top: 6px !important;
    }

    .photo-stage-pill.ready {
      background: var(--color-blue-50) !important;
      color: var(--color-blue-700) !important;
      border: 1px solid var(--color-blue-100) !important;
    }

    .photo-stage-pill.pending {
      background: var(--color-slate-100) !important;
      color: var(--color-slate-500) !important;
      border: 1px solid var(--color-slate-200) !important;
    }

    .photo-slot-card.locked {
      background: var(--color-slate-100) !important;
      border-color: var(--color-slate-200) !important;
      cursor: not-allowed !important;
      opacity: 0.6 !important;
    }

    .photo-slot-card.locked:hover {
      border-color: var(--color-slate-200) !important;
      background: var(--color-slate-100) !important;
    }

    .btn-snap-quick {
      background: #FFFFFF !important;
      border: 1.5px solid var(--color-slate-300) !important;
      border-radius: var(--radius-md) !important;
      padding: 9px 14px !important;
      font-family: inherit !important;
      font-size: 0.8rem !important;
      font-weight: 700 !important;
      color: var(--color-slate-700) !important;
      cursor: pointer !important;
      display: inline-flex !important;
      align-items: center !important;
      gap: 6px !important;
      transition: all 0.15s ease !important;
    }

    .btn-snap-quick:hover {
      background: var(--color-slate-50) !important;
      color: var(--color-slate-900) !important;
    }

    /* Sesi Notice Banner */
    .sesi-notice-banner {
      border-radius: var(--radius-md) !important;
      padding: 13px 15px !important;
      margin-bottom: 16px !important;
      text-align: left !important;
    }

    .sesi-notice-banner.initial {
      background: var(--color-blue-50) !important;
      border: 1.5px solid var(--color-blue-100) !important;
    }

    .sesi-notice-banner.resume {
      background: var(--color-brand-light) !important;
      border: 1.5px solid var(--color-brand-border) !important;
    }

    .sesi-badge-tag {
      font-size: 0.7rem !important;
      font-weight: 800 !important;
      padding: 2px 7px !important;
      border-radius: 4px !important;
      margin-bottom: 4px !important;
      display: inline-block !important;
    }

    .sesi-notice-banner.initial .sesi-badge-tag {
      background: var(--color-blue-100) !important;
      color: var(--color-blue-700) !important;
    }

    .sesi-notice-banner.resume .sesi-badge-tag {
      background: var(--color-brand-tint) !important;
      color: var(--color-brand-primary) !important;
    }

    .sesi-title {
      font-size: 0.92rem !important;
      font-weight: 800 !important;
      margin: 0 0 3px 0 !important;
    }

    .sesi-notice-banner.initial .sesi-title { color: var(--color-blue-700) !important; }
    .sesi-notice-banner.resume .sesi-title { color: #9A3412 !important; }

    .sesi-desc {
      font-size: 0.76rem !important;
      line-height: 1.4 !important;
      margin: 0 !important;
    }

    .sesi-notice-banner.initial .sesi-desc { color: var(--color-blue-600) !important; }
    .sesi-notice-banner.resume .sesi-desc { color: var(--color-brand-action) !important; }

    /* ==========================================================
       12. TAB 2: RIWAYAT PEKERJAAN REGU
       ========================================================== */
    .user-history-panel {
      width: 100% !important;
      max-width: 100% !important;
      display: none !important;
    }
    .user-history-panel.show {
      display: block !important;
    }

    .user-history-card {
      background: #FFFFFF !important;
      border: 1px solid var(--color-slate-200) !important;
      border-radius: var(--radius-lg) !important;
      padding: 16px 18px !important;
      margin-bottom: 12px !important;
      box-shadow: var(--shadow-sm) !important;
      transition: all 0.18s ease !important;
      box-sizing: border-box !important;
    }

    .user-history-card:hover {
      border-color: var(--color-slate-300) !important;
      box-shadow: var(--shadow-card) !important;
    }

    .riwayat-action-card {
      background: var(--color-brand-light) !important;
      border: 1.5px solid var(--color-brand-border) !important;
      border-radius: var(--radius-md) !important;
      padding: 13px 15px !important;
      margin: 12px 0 !important;
    }

    .riwayat-action-top {
      display: flex !important;
      align-items: center !important;
      gap: 8px !important;
      margin-bottom: 4px !important;
    }

    .riwayat-pulse-dot {
      width: 8px !important;
      height: 8px !important;
      border-radius: 50% !important;
      background: var(--color-brand-primary) !important;
      box-shadow: 0 0 0 0 rgba(194, 65, 12, 0.7) !important;
      animation: pulseBrand 1.5s infinite !important;
      flex-shrink: 0 !important;
    }

    @keyframes pulseBrand {
      0% { box-shadow: 0 0 0 0 rgba(194, 65, 12, 0.7); }
      70% { box-shadow: 0 0 0 6px rgba(194, 65, 12, 0); }
      100% { box-shadow: 0 0 0 0 rgba(194, 65, 12, 0); }
    }

    .riwayat-action-title {
      font-size: 0.84rem !important;
      font-weight: 800 !important;
      color: #7C2D12 !important;
    }

    .riwayat-action-desc {
      font-size: 0.76rem !important;
      color: #9A3412 !important;
      margin-bottom: 10px !important;
      line-height: 1.4 !important;
    }

    .btn-selesaikan-pekerjaan {
      width: 100% !important;
      background: var(--color-brand-primary) !important;
      color: #FFFFFF !important;
      border: none !important;
      border-radius: var(--radius-md) !important;
      padding: 10px 14px !important;
      font-family: inherit !important;
      font-size: 0.84rem !important;
      font-weight: 800 !important;
      cursor: pointer !important;
      display: inline-flex !important;
      align-items: center !important;
      justify-content: center !important;
      gap: 6px !important;
      box-shadow: var(--shadow-brand) !important;
      transition: all 0.15s ease !important;
    }

    .btn-selesaikan-pekerjaan:hover {
      background: var(--color-brand-hover) !important;
    }

    /* Error Shake Animation */
    .input-error-pulse {
      border-color: #EF4444 !important;
      box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.25) !important;
      animation: shakeError 0.3s ease-in-out !important;
    }
    @keyframes shakeError {
      0%, 100% { transform: translateX(0); }
      25% { transform: translateX(-4px); }
      75% { transform: translateX(4px); }
    }

    /* ==========================================================
       FORM LAYOUT UTILITIES & ADAPTIVE CONTAINERS
       ========================================================== */
    .hero-profile-col {
      display: flex;
      align-items: center;
      gap: 11px;
      flex: 1;
      min-width: 0;
    }

    .officer-contact-grid {
      display: grid !important;
      grid-template-columns: 1fr 1fr !important;
      gap: 10px !important;
      margin-bottom: 12px !important;
      align-items: end !important;
    }
    .officer-contact-grid .field-label {
      white-space: nowrap !important;
      font-size: 0.8rem !important;
      min-height: 20px !important;
      margin-bottom: 6px !important;
    }
    .officer-contact-grid .field-label span {
      white-space: nowrap !important;
    }

    .form-field-group {
      margin-top: 14px;
    }

    .form-field-item {
      margin-bottom: 12px;
    }

    /* ==========================================================
       ROLE BADGE & TOGGLE (SIMPLE WHITE PILL DI SAMPING KANAN)
       ========================================================== */
    .hero-role-side-wrap {
      min-width: 0;
      max-width: 100%;
      display: flex;
      align-items: center;
      flex: 0 1 auto;
      margin-left: auto;
    }
    .hero-role-pill-simple {
      background: #FFFFFF !important;
      border: 1px solid rgba(255, 255, 255, 0.95) !important;
      color: #C2410C !important;
      border-radius: 8px !important;
      padding: 4px 10px !important;
      display: inline-flex !important;
      align-items: center !important;
      gap: 5px !important;
      cursor: default !important;
      pointer-events: none !important;
      user-select: none !important;
      box-shadow: 0 2px 6px rgba(0, 0, 0, 0.12) !important;
      font-family: inherit !important;
      font-size: clamp(0.66rem, 2.4vw, 0.75rem) !important;
      font-weight: 800 !important;
      letter-spacing: 0.02em !important;
      text-transform: uppercase !important;
      white-space: normal !important;
      word-break: break-word !important;
      overflow-wrap: anywhere !important;
      max-width: 100% !important;
      text-align: right !important;
      line-height: 1.25 !important;
    }

    /* ==========================================================
       AUTOMATIC CALENDAR WIDGET
       ========================================================== */
    .auto-calendar-box {
      background: #FFFFFF;
      border: 1.5px solid #CBD5E1;
      border-radius: 12px;
      padding: 12px 14px;
      box-shadow: 0 1px 3px rgba(0,0,0,0.04);
      margin-top: 6px;
      transition: border-color 0.2s;
    }
    .auto-calendar-box:focus-within {
      border-color: #EA580C;
    }
    .calendar-display-row {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 10px;
    }
    .calendar-icon-wrap {
      width: 40px;
      height: 40px;
      border-radius: 10px;
      background: #FFF7ED;
      color: #EA580C;
      display: flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;
      border: 1px solid #FFEDD5;
    }
    .calendar-date-info {
      flex: 1;
      min-width: 0;
    }
    .calendar-hari-label {
      font-size: 0.98rem;
      font-weight: 800;
      color: #0F172A;
      line-height: 1.25;
    }
    .calendar-tgl-label {
      font-size: 0.78rem;
      font-weight: 600;
      color: #64748B;
      margin-top: 2px;
      white-space: nowrap !important;
    }
    .calendar-action-wrap {
      position: relative;
      flex-shrink: 0;
    }
    .btn-ganti-tanggal {
      display: inline-flex;
      align-items: center;
      gap: 5px;
      background: #F8FAFC;
      border: 1px solid #CBD5E1;
      color: #334155;
      font-size: 0.75rem;
      font-weight: 700;
      padding: 7px 11px;
      border-radius: 8px;
      cursor: pointer;
      white-space: nowrap !important;
      flex-shrink: 0 !important;
      transition: all 0.15s ease;
    }
    .btn-ganti-tanggal:hover {
      background: #EFF6FF;
      border-color: #93C5FD;
      color: #1D4ED8;
    }
    .native-calendar-inp {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      opacity: 0;
      cursor: pointer;
    }
    .calendar-quick-bar {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 8px;
      margin-top: 10px;
      padding-top: 9px;
      border-top: 1px dashed #E2E8F0;
      font-size: 0.72rem;
      flex-wrap: nowrap !important;
    }
    .btn-cal-snap {
      background: transparent;
      border: none;
      color: #0284C7;
      font-family: inherit;
      font-size: 0.74rem;
      font-weight: 700;
      cursor: pointer;
      padding: 0;
      display: inline-flex;
      align-items: center;
      gap: 4px;
      white-space: nowrap !important;
    }
    .btn-cal-snap:hover {
      text-decoration: underline;
    }
    .cal-dot-today {
      color: #10B981;
      font-size: 0.75rem;
    }
    .badge-cal-status {
      font-size: 0.68rem;
      font-weight: 700;
      background: #ECFDF5;
      color: #059669;
      padding: 2px 7px;
      border-radius: 5px;
      border: 1px solid #A7F3D0;
      white-space: nowrap !important;
      flex-shrink: 0 !important;
    }

    /* ==========================================================
       TASK LOCK & COMPLETED BUTTONS
       ========================================================== */
    .btn-tugas-pill.tugas-disabled-done {
      background: #ECFDF5 !important;
      border: 1.5px solid #A7F3D0 !important;
      color: #065F46 !important;
      cursor: pointer !important;
      opacity: 0.95 !important;
      display: flex !important;
      flex-direction: column !important;
      align-items: center !important;
      justify-content: center !important;
      gap: 2px !important;
      box-shadow: none !important;
      position: relative;
    }
    .btn-tugas-pill.tugas-in-progress {
      background: #FFFBEB !important;
      border: 1.5px solid #FCD34D !important;
      color: #B45309 !important;
      display: flex !important;
      flex-direction: column !important;
      align-items: center !important;
      justify-content: center !important;
      gap: 2px !important;
      cursor: pointer !important;
    }
    .btn-tugas-pill.tugas-in-progress.active {
      background: var(--color-blue-700) !important;
      border-color: var(--color-blue-700) !important;
      color: #FFFFFF !important;
    }
    .btn-tugas-pill.tugas-locked {
      background: #F8FAFC !important;
      border: 1.5px solid #E2E8F0 !important;
      color: #94A3B8 !important;
      opacity: 0.55 !important;
      cursor: not-allowed !important;
      pointer-events: auto !important;
    }
    .tugas-done-tag {
      font-size: 0.6rem !important;
      background: #059669 !important;
      color: #FFFFFF !important;
      padding: 1px 5px !important;
      border-radius: 4px !important;
      font-weight: 800 !important;
      line-height: 1.1 !important;
      letter-spacing: 0.02em;
    }
    .all-tasks-banner {
      background: #ECFDF5;
      border: 1.5px solid #A7F3D0;
      border-radius: 10px;
      padding: 10px 12px;
      margin-bottom: 8px;
      color: #065F46;
      display: flex;
      align-items: center;
      gap: 9px;
      animation: fadeIn 0.3s ease;
    }

    /* ==========================================================
       ATTENDANCE / ABSENSI CHECKLIST DROPDOWN
       ========================================================== */
    .absen-dropdown-trigger {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 8px;
      background: #FFFFFF;
      border: 1.5px solid #CBD5E1;
      border-radius: 10px;
      padding: 10px 12px;
      cursor: pointer;
      transition: all 0.2s ease;
    }
    .absen-dropdown-trigger:hover {
      border-color: #EA580C;
    }
    .absen-user-icon {
      width: 32px;
      height: 32px;
      border-radius: 8px;
      background: #FFF7ED;
      color: #EA580C;
      display: flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;
    }
    .absen-chevron {
      color: #64748B;
      transition: transform 0.2s ease;
      flex-shrink: 0;
    }
    .absen-chevron.open {
      transform: rotate(180deg);
    }
    .absen-dropdown-panel {
      background: #F8FAFC;
      border: 1.5px solid #E2E8F0;
      border-top: none;
      border-radius: 0 0 10px 10px;
      padding: 10px 12px;
      margin-top: -3px;
    }
    .absen-panel-tip {
      font-size: 0.72rem;
      font-weight: 700;
      color: #64748B;
      margin-bottom: 8px;
    }
    .absen-checklist-grid {
      display: flex;
      flex-direction: column;
      gap: 6px;
      max-height: 220px;
      overflow-y: auto;
      padding-right: 4px;
    }
    .absen-item {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 8px;
      background: #FFFFFF;
      border: 1px solid #E2E8F0;
      border-radius: 8px;
      padding: 8px 10px;
      cursor: pointer;
      transition: all 0.15s ease;
    }
    .absen-item:hover {
      border-color: #CBD5E1;
      background: #F8FAFC;
    }
    .absen-item.checked {
      background: #F0FDF4;
      border-color: #BBF7D0;
    }
    .absen-chk {
      width: 17px;
      height: 17px;
      accent-color: #16A34A;
      cursor: pointer;
      flex-shrink: 0;
    }
    .absen-member-info {
      flex: 1;
      min-width: 0;
    }
    .absen-name {
      display: block;
      font-size: 0.82rem;
      font-weight: 700;
      color: #0F172A;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }
    .absen-role {
      display: block;
      font-size: 0.68rem;
      color: #64748B;
    }
    .absen-status-pill {
      font-size: 0.65rem;
      font-weight: 800;
      padding: 2px 7px;
      border-radius: 5px;
      flex-shrink: 0;
    }
    .absen-status-pill.hadir {
      background: #DCFCE7;
      color: #15803D;
    }
    .absen-status-pill.absen {
      background: #F1F5F9;
      color: #64748B;
    }
    .absen-add-row {
      display: flex;
      align-items: center;
      gap: 6px;
      margin-top: 9px;
      padding-top: 8px;
      border-top: 1px dashed #E2E8F0;
    }
    .btn-tambah-anggota {
      display: inline-flex;
      align-items: center;
      gap: 4px;
      background: #EA580C;
      color: #FFFFFF;
      border: none;
      padding: 7px 11px;
      border-radius: 8px;
      font-family: inherit;
      font-size: 0.74rem;
      font-weight: 700;
      cursor: pointer;
      white-space: nowrap;
      flex-shrink: 0;
    }
    .btn-tambah-anggota:hover {
      background: #C2410C;
    }

    /* ==========================================================
       MODAL KONFIRMASI PENGIRIMAN LAPORAN (FINAL & TIDAK BISA DIEDIT)
       ========================================================== */
    .confirm-modal-overlay {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(15, 23, 42, 0.75);
      backdrop-filter: blur(5px);
      z-index: 999999;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 16px;
      box-sizing: border-box;
      animation: fadeIn 0.2s ease;
    }
    .confirm-modal-card {
      background: #FFFFFF;
      border-radius: 16px;
      max-width: 460px;
      width: 100%;
      padding: 20px;
      box-shadow: 0 20px 40px rgba(0,0,0,0.25);
      border: 1px solid #CBD5E1;
      max-height: 92vh;
      overflow-y: auto;
      box-sizing: border-box;
    }
    .confirm-modal-header {
      display: flex;
      align-items: flex-start;
      gap: 12px;
      margin-bottom: 14px;
    }
    .confirm-warn-icon {
      width: 44px;
      height: 44px;
      border-radius: 12px;
      background: #FEF2F2;
      color: #DC2626;
      display: flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;
      border: 1.5px solid #FECACA;
    }
    .confirm-title {
      font-size: 1.05rem;
      font-weight: 800;
      color: #0F172A;
      margin: 0;
      line-height: 1.25;
    }
    .confirm-subtitle {
      font-size: 0.75rem;
      color: #64748B;
      margin: 3px 0 0 0;
    }
    .confirm-close-btn {
      width: 32px;
      height: 32px;
      min-width: 32px;
      min-height: 32px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      border-radius: 50%;
      border: 1px solid #E2E8F0;
      background: #F8FAFC;
      color: #64748B;
      cursor: pointer;
      transition: all 0.15s ease;
      padding: 0;
      box-sizing: border-box;
    }
    .confirm-close-btn:hover {
      background: #F1F5F9;
      color: #0F172A;
      border-color: #CBD5E1;
    }
    .confirm-close-btn svg {
      width: 15px;
      height: 15px;
      stroke: currentColor;
      stroke-width: 2.5;
      stroke-linecap: round;
      stroke-linejoin: round;
      pointer-events: none;
    }
    .confirm-alert-box {
      background: #FFF1F2;
      border: 1.5px solid #FECDD3;
      border-radius: 10px;
      padding: 10px 12px;
      color: #9F1239;
      margin-bottom: 12px;
    }
    .confirm-recap-box {
      background: #F8FAFC;
      border: 1px solid #E2E8F0;
      border-radius: 10px;
      padding: 10px 12px;
      display: flex;
      flex-direction: column;
      gap: 7px;
      margin-bottom: 12px;
    }
    .recap-row {
      display: flex;
      justify-content: space-between;
      gap: 10px;
      font-size: 0.76rem;
      line-height: 1.35;
    }
    .recap-lbl {
      color: #64748B;
      font-weight: 600;
      flex-shrink: 0;
    }
    .recap-val {
      color: #0F172A;
      font-weight: 700;
      text-align: right;
      word-break: break-word;
    }
    .recap-val.highlight {
      color: #C2410C;
      font-weight: 800;
      background: #FFEDD5;
      padding: 1px 6px;
      border-radius: 4px;
    }
    .confirm-checkbox-label {
      display: flex;
      align-items: flex-start;
      gap: 8px;
      font-size: 0.74rem;
      color: #334155;
      font-weight: 600;
      line-height: 1.35;
      cursor: pointer;
      margin-bottom: 16px;
      background: #F1F5F9;
      padding: 8px 10px;
      border-radius: 8px;
      border: 1px solid #E2E8F0;
    }
    .confirm-checkbox-label input {
      margin-top: 2px;
      accent-color: #EA580C;
    }
    .confirm-modal-actions {
      display: flex;
      gap: 10px;
    }
    .btn-confirm-cancel {
      flex: 1;
      min-height: 44px;
      border-radius: 10px;
      border: 1.5px solid #CBD5E1;
      background: #FFFFFF;
      color: #475569;
      font-family: inherit;
      font-size: 0.82rem;
      font-weight: 700;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 6px;
      transition: all 0.15s ease;
    }
    .btn-confirm-cancel:hover {
      background: #F1F5F9;
    }
    .btn-confirm-submit {
      flex: 1.5;
      min-height: 44px;
      border-radius: 10px;
      border: none;
      background: linear-gradient(135deg, #EA580C 0%, #C2410C 100%);
      color: #FFFFFF;
      font-family: inherit;
      font-size: 0.84rem;
      font-weight: 800;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 6px;
      box-shadow: 0 4px 12px rgba(234, 88, 12, 0.35);
      transition: all 0.15s ease;
    }
    .btn-confirm-submit:hover {
      background: linear-gradient(135deg, #C2410C 0%, #9A3412 100%);
      transform: translateY(-1px);
    }

    /* ==========================================================
       RIWAYAT PEREGU & PERIODE FILTER
       ========================================================== */
    .riwayat-filter-bar {
      background: #FFFFFF;
      border: 1px solid #E2E8F0;
      border-radius: 12px;
      padding: 10px 12px;
      margin-bottom: 12px;
      box-shadow: 0 1px 3px rgba(0,0,0,0.03);
    }
    .riwayat-regu-select-row {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 8px;
      margin-bottom: 8px;
    }
    .regu-filter-select {
      background: #FFF7ED;
      border: 1.5px solid #FFEDD5;
      color: #C2410C;
      font-family: inherit;
      font-size: 0.78rem;
      font-weight: 800;
      padding: 5px 10px;
      border-radius: 8px;
      cursor: pointer;
    }
    .riwayat-time-chips {
      display: flex;
      gap: 5px;
      overflow-x: auto;
      padding-bottom: 4px;
      scrollbar-width: none;
    }
    .riwayat-time-chips::-webkit-scrollbar {
      display: none;
    }
    .time-chip-btn {
      white-space: nowrap;
      background: #F1F5F9;
      border: 1px solid #E2E8F0;
      border-radius: 6px;
      font-family: inherit;
      font-size: 0.7rem;
      font-weight: 700;
      color: #475569;
      padding: 4px 9px;
      cursor: pointer;
      transition: all 0.15s ease;
      flex-shrink: 0;
    }
    .time-chip-btn:hover {
      background: #E2E8F0;
    }
    .time-chip-btn.active {
      background: #0284C7;
      border-color: #0284C7;
      color: #FFFFFF;
      font-weight: 800;
    }
    .badge-time-chip {
      font-size: 0.7rem;
      font-weight: 800;
      padding: 2px 7px;
      border-radius: 6px;
      display: inline-flex;
      align-items: center;
      gap: 3px;
    }
    .badge-time-chip.today {
      background: #DCFCE7;
      color: #15803D;
      border: 1px solid #BBF7D0;
    }
    .badge-time-chip.yesterday {
      background: #E0F2FE;
      color: #0369A1;
      border: 1px solid #BAE6FD;
    }
    .badge-time-chip.past {
      background: #FEF3C7;
      color: #92400E;
    }
    /* ==========================================================
       COMPREHENSIVE MULTI-TIER MOBILE RESPONSIVE ENGINE
       Seamless Layout Optimization for 320px - 540px Devices
       ========================================================== */

    /* ----- TIER 1: ALL MOBILE DEVICES (≤ 540px) ----- */
    @media (max-width: 540px) {
      input:not([type="checkbox"]):not([type="radio"]),
      select,
      textarea {
        font-size: 16px !important;
      }

      .user-portal-wrapper {
        padding: clamp(8px, 2.5vw, 12px) clamp(8px, 2.5vw, 12px) calc(env(safe-area-inset-bottom, 0px) + 76px) clamp(8px, 2.5vw, 12px) !important;
      }

      .user-portal-topbar {
        display: flex !important;
        flex-direction: row !important;
        align-items: center !important;
        justify-content: space-between !important;
        padding: 8px 10px !important;
        border-radius: 14px !important;
        margin-bottom: 9px !important;
        gap: 8px !important;
      }
      .user-topbar-brand {
        display: flex !important;
        align-items: center !important;
        gap: 8px !important;
        min-width: 0 !important;
        flex: 1 1 auto !important;
      }
      .user-topbar-logo {
        width: 32px !important;
        height: 32px !important;
        flex-shrink: 0 !important;
      }
      .user-topbar-brand-text {
        min-width: 0 !important;
        flex: 1 1 auto !important;
        overflow: visible !important;
      }
      .user-topbar-title {
        font-size: 0.92rem !important;
        font-weight: 800 !important;
        white-space: nowrap !important;
        overflow: visible !important;
        line-height: 1.15 !important;
      }
      .user-topbar-sub {
        font-size: 0.72rem !important;
        font-weight: 700 !important;
        color: #475569 !important;
        line-height: 1.2 !important;
        white-space: normal !important;
        overflow: visible !important;
        text-overflow: clip !important;
        word-break: break-word !important;
      }
      .user-topbar-actions {
        display: flex !important;
        align-items: center !important;
        justify-content: flex-end !important;
        gap: 4px !important;
        flex-shrink: 0 !important;
        border-top: none !important;
        padding-top: 0 !important;
      }
      .topbar-live-status {
        padding: 3px 6px !important;
        font-size: 0.68rem !important;
        gap: 4px !important;
        flex-shrink: 0 !important;
      }
      .user-pill-btn {
        padding: 4px 8px !important;
        font-size: 0.72rem !important;
        gap: 4px !important;
        min-height: 28px !important;
        flex-shrink: 0 !important;
      }

      .hero-officer-banner {
        padding: 11px 11px 9px 11px !important;
        border-radius: 12px !important;
        margin-bottom: 7px !important;
      }
      .hero-profile-row {
        gap: 9px !important;
        margin-bottom: 8px !important;
      }
      .hero-avatar-box {
        width: 38px !important;
        height: 38px !important;
        border-radius: 10px !important;
        font-size: 1.05rem !important;
      }
      .hero-officer-name { font-size: 1.05rem !important; white-space: normal !important; word-break: break-word !important; }
      .hero-sub-text { font-size: 0.72rem !important; white-space: normal !important; }
      .hero-greeting-text { font-size: 0.68rem !important; }

      .hero-role-pill-simple {
        padding: 4px 9px !important;
        font-size: 0.72rem !important;
        border-radius: 7px !important;
      }

      .hero-field-dock {
        padding: 7px 5px !important;
        border-radius: 9px !important;
      }
      .dock-label { font-size: 0.58rem !important; }
      .dock-value { font-size: 0.72rem !important; }

      .user-tabs-bar {
        padding: 3px !important;
        border-radius: 11px !important;
        margin: 0 0 8px 0 !important;
      }
      .user-tab-item {
        padding: 8px 8px !important;
        font-size: 0.78rem !important;
        min-height: 38px !important;
        border-radius: 9px !important;
        gap: 5px !important;
      }

      .user-main-card {
        border-radius: 13px !important;
      }
      .user-card-body {
        padding: 10px 9px !important;
      }

      .user-wizard-progress {
        padding: 7px 9px !important;
        margin-bottom: 8px !important;
        border-radius: 9px !important;
      }
      .node-circle {
        width: 24px !important;
        height: 24px !important;
        font-size: 0.75rem !important;
      }
      .node-title { font-size: 0.76rem !important; }
      .node-sub { font-size: 0.58rem !important; }
      .wizard-step-line {
        margin: 0 8px !important;
      }

      .draft-status-bar {
        padding: 5px 8px !important;
        font-size: 0.68rem !important;
        margin-bottom: 8px !important;
        border-radius: 8px !important;
      }

      .figma-section-card,
      .job-id-custom-card,
      .u-step-group {
        padding: 11px 10px !important;
        border-radius: 11px !important;
        margin-bottom: 8px !important;
      }
      .section-header-row {
        margin-bottom: 8px !important;
        padding-bottom: 6px !important;
        gap: 8px !important;
      }
      .section-header-icon {
        width: 26px !important;
        height: 26px !important;
        border-radius: 6px !important;
      }
      .section-title { font-size: 0.86rem !important; }
      .section-sub { font-size: 0.68rem !important; }
      .field-label { font-size: 0.76rem !important; margin-bottom: 5px !important; }

      .form-field-group {
        margin-top: 10px !important;
      }
      .form-field-item {
        margin-bottom: 10px !important;
      }

      .auto-calendar-box {
        padding: 9px 10px !important;
        border-radius: 10px !important;
        margin-top: 5px !important;
      }
      .calendar-icon-wrap {
        width: 34px !important;
        height: 34px !important;
        border-radius: 8px !important;
      }
      .calendar-display-row {
        gap: 6px !important;
      }
      .calendar-icon-wrap {
        width: 32px !important;
        height: 32px !important;
        border-radius: 8px !important;
      }
      .calendar-hari-label { font-size: 0.86rem !important; }
      .calendar-tgl-label { font-size: 0.7rem !important; white-space: nowrap !important; }
      .btn-ganti-tanggal {
        padding: 4px 7px !important;
        font-size: 0.67rem !important;
        gap: 4px !important;
      }
      .calendar-quick-bar {
        margin-top: 7px !important;
        padding-top: 6px !important;
        font-size: 0.67rem !important;
        gap: 6px !important;
      }
      .badge-cal-status {
        font-size: 0.64rem !important;
        padding: 2px 5px !important;
      }

      .regu-selected-display {
        padding: 6px 10px !important;
        border-radius: 9px !important;
      }

      .regu-pill-grid,
      .harian-day-grid {
        grid-template-columns: repeat(5, 1fr) !important;
        gap: 5px !important;
        margin-top: 5px !important;
      }
      .tugas-day-grid {
        display: flex !important;
        overflow-x: auto !important;
        -webkit-overflow-scrolling: touch !important;
        gap: 6px !important;
        margin-top: 5px !important;
        padding-bottom: 4px !important;
        scrollbar-width: none !important;
        width: 100% !important;
        box-sizing: border-box !important;
      }
      .tugas-day-grid::-webkit-scrollbar {
        display: none !important;
      }
      .btn-regu-pill,
      .btn-day-pill {
        min-height: 40px !important;
        font-size: 0.75rem !important;
        padding: 6px 2px !important;
        border-radius: 8px !important;
      }
      .btn-tugas-pill {
        flex: 0 0 auto !important;
        min-width: 68px !important;
        min-height: 40px !important;
        font-size: 0.75rem !important;
        padding: 6px 10px !important;
        border-radius: 8px !important;
        white-space: nowrap !important;
      }
      .tugas-done-tag {
        font-size: 0.56rem !important;
        padding: 1px 4px !important;
      }

      .job-cards-vertical {
        gap: 6px !important;
        margin-top: 5px !important;
      }
      .job-card-selectable {
        padding: 9px 10px !important;
        gap: 9px !important;
        border-radius: 10px !important;
        min-height: 48px !important;
      }
      .job-card-icon {
        width: 32px !important;
        height: 32px !important;
        border-radius: 8px !important;
      }
      .job-card-title { font-size: 0.82rem !important; }
      .job-card-desc { font-size: 0.68rem !important; line-height: 1.3 !important; }

      .u-input-field,
      .u-textarea {
        min-height: 44px !important;
        font-size: 16px !important;
        padding: 8px 11px !important;
        border-radius: 9px !important;
      }
      .u-textarea {
        min-height: 75px !important;
      }

      .absen-dropdown-trigger {
        padding: 8px 10px !important;
        border-radius: 9px !important;
      }
      .absen-user-icon {
        width: 28px !important;
        height: 28px !important;
      }
      #absen-trigger-title { font-size: 0.82rem !important; }
      #absen-trigger-sub { font-size: 0.67rem !important; }
      .absen-dropdown-panel {
        padding: 8px 9px !important;
      }
      .absen-item {
        padding: 6px 8px !important;
      }
      .absen-name { font-size: 0.78rem !important; }

      .location-box-card {
        padding: 9px 9px !important;
        border-radius: 10px !important;
        gap: 8px !important;
      }
      .loc-sub { font-size: 0.62rem !important; }
      .loc-main { font-size: 0.78rem !important; }
      .btn-loc-change {
        padding: 5px 8px !important;
        font-size: 0.68rem !important;
      }
      #pekayon-live-map {
        height: 165px !important;
      }

      .btn-step-next {
        min-height: 48px !important;
        padding: 11px 14px !important;
        border-radius: 11px !important;
        margin-top: 10px !important;
      }

      .stepper-actions-row {
        gap: 8px !important;
        margin-top: 10px !important;
      }
      .btn-step-back {
        min-height: 46px !important;
        padding: 9px 13px !important;
        font-size: 0.82rem !important;
        border-radius: 10px !important;
      }
      .btn-kirim-laporan {
        min-height: 46px !important;
        padding: 9px 13px !important;
        font-size: 0.84rem !important;
        border-radius: 10px !important;
      }

      .quick-progress-pills {
        gap: 5px !important;
        margin-bottom: 8px !important;
      }
      .btn-prog-pill {
        min-height: 38px !important;
        font-size: 0.74rem !important;
        padding: 6px 2px !important;
        border-radius: 8px !important;
      }

      .photo-slot-grid {
        grid-template-columns: repeat(3, 1fr) !important;
        gap: 6px !important;
        margin-top: 7px !important;
      }
      .photo-slot-card {
        min-height: 108px !important;
        padding: 7px 3px !important;
        border-radius: 9px !important;
      }
      .photo-camera-icon {
        width: 24px !important;
        height: 24px !important;
        margin-bottom: 4px !important;
      }
      .photo-slot-title { font-size: 0.7rem !important; line-height: 1.2 !important; }
      .photo-slot-sub { font-size: 0.6rem !important; line-height: 1.2 !important; margin-top: 2px !important; }
      .photo-stage-pill {
        font-size: 0.56rem !important;
        padding: 2px 4px !important;
        margin-top: 3px !important;
      }

      .sesi-notice-banner {
        padding: 10px 11px !important;
        margin-bottom: 8px !important;
        border-radius: 10px !important;
      }
      .sesi-title { font-size: 0.86rem !important; }
      .sesi-desc { font-size: 0.72rem !important; }

      .user-history-card {
        padding: 12px 10px !important;
        margin-bottom: 9px !important;
        border-radius: 11px !important;
      }
      .riwayat-filter-bar {
        padding: 8px 10px !important;
        margin-bottom: 9px !important;
        border-radius: 10px !important;
      }

      /* Camera HUD on Mobile */
      .camera-live-hud {
        bottom: clamp(100px, 15vh, 128px) !important;
        left: 14px !important;
        right: 14px !important;
      }
      .cam-huge-time {
        font-size: clamp(2.3rem, 7.5vw, 3rem) !important;
      }
      .cam-yellow-divider {
        height: 38px !important;
        width: 3px !important;
      }
      .cam-date { font-size: 1rem !important; }
      .cam-day { font-size: 0.88rem !important; }
      .cam-loc-line {
        font-size: clamp(0.76rem, 2.4vw, 0.88rem) !important;
        line-height: 1.3 !important;
      }
      .cam-loc-coords {
        font-size: clamp(0.66rem, 2.1vw, 0.74rem) !important;
      }
      .camera-bottom-bar {
        padding: 12px 20px clamp(18px, 4vh, 28px) !important;
        gap: 20px !important;
      }
      .cam-shutter-btn {
        width: 68px !important;
        height: 68px !important;
      }

      /* Confirm Modal on Mobile */
      .confirm-modal-overlay {
        padding: 10px !important;
      }
      .confirm-modal-card {
        padding: 14px 12px !important;
        border-radius: 13px !important;
        max-height: 94vh !important;
      }
      .confirm-modal-header {
        margin-bottom: 10px !important;
        gap: 10px !important;
      }
      .confirm-warn-icon {
        width: 36px !important;
        height: 36px !important;
        border-radius: 10px !important;
      }
      .confirm-title { font-size: 0.94rem !important; }
      .confirm-subtitle { font-size: 0.7rem !important; }
      .confirm-alert-box {
        padding: 8px 10px !important;
        margin-bottom: 9px !important;
        font-size: 0.72rem !important;
      }
      .confirm-recap-box {
        padding: 8px 10px !important;
        gap: 6px !important;
        margin-bottom: 9px !important;
      }
      .recap-row { font-size: 0.72rem !important; }
      .confirm-checkbox-label {
        padding: 7px 9px !important;
        font-size: 0.7rem !important;
        margin-bottom: 10px !important;
      }
      .confirm-modal-actions {
        gap: 8px !important;
      }
      .btn-confirm-cancel,
      .btn-confirm-submit {
        min-height: 42px !important;
        font-size: 0.78rem !important;
        padding: 8px 10px !important;
      }
    }

    /* ----- TIER 2: STANDARD MOBILE (≤ 412px, e.g. iPhone 13/14/15/16, Galaxy S21/S22) ----- */
    @media (max-width: 412px) {
      .hero-header-top {
        flex-wrap: wrap !important;
        gap: 6px !important;
      }
      .hero-role-side-wrap {
        max-width: 100% !important;
      }
      .hero-profile-row {
        gap: 8px !important;
      }
      .hero-officer-name {
        font-size: 1.05rem !important;
        white-space: normal !important;
        word-break: break-word !important;
      }
      .hero-role-pill-simple {
        padding: 4px 8px !important;
        font-size: 0.7rem !important;
        white-space: normal !important;
        max-width: 100% !important;
        text-align: right !important;
      }
      .officer-contact-grid {
        gap: 8px !important;
      }
      .regu-pill-grid,
      .harian-day-grid {
        gap: 4px !important;
      }
      .btn-regu-pill,
      .btn-day-pill {
        font-size: 0.72rem !important;
        padding: 5px 1px !important;
      }
      .btn-tugas-pill {
        flex: 0 0 auto !important;
        font-size: 0.72rem !important;
        min-width: 64px !important;
        padding: 6px 9px !important;
      }
      .photo-slot-card {
        min-height: 104px !important;
      }
      .photo-slot-title {
        font-size: 0.68rem !important;
      }
    }

    /* ----- TIER 3: COMPACT MOBILE (≤ 380px, e.g. iPhone SE, Galaxy A01/A10/A12, Redmi) ----- */
    @media (max-width: 380px) {
      .user-portal-wrapper {
        padding: 6px 6px calc(env(safe-area-inset-bottom, 0px) + 72px) 6px !important;
      }

      .user-card-body {
        padding: 8px 7px !important;
      }
      .figma-section-card,
      .job-id-custom-card,
      .u-step-group {
        padding: 10px 8px !important;
        margin-bottom: 7px !important;
      }

      /* Transform 4-column dock into clean, balanced 2x2 grid */
      .hero-field-dock {
        grid-template-columns: repeat(2, 1fr) !important;
        gap: 5px 6px !important;
        padding: 7px 8px !important;
      }
      .dock-item {
        padding: 4px 6px !important;
        background: #F8FAFC !important;
        border-radius: 7px !important;
      }
      .dock-item:not(:last-child) {
        border-right: none !important;
      }
      .dock-label { font-size: 0.6rem !important; }
      .dock-value { font-size: 0.76rem !important; }

      /* Keep Contact inputs in 2 columns like iPhone with compact gap */
      .officer-contact-grid {
        grid-template-columns: 1fr 1fr !important;
        gap: 6px !important;
      }
      .officer-contact-grid .field-label {
        font-size: 0.74rem !important;
      }

      .btn-regu-pill,
      .btn-day-pill {
        min-height: 38px !important;
        font-size: 0.7rem !important;
        padding: 5px 0 !important;
      }
      .btn-tugas-pill {
        flex: 0 0 auto !important;
        min-height: 38px !important;
        font-size: 0.72rem !important;
        min-width: 62px !important;
        padding: 5px 8px !important;
      }
      .tugas-done-tag {
        font-size: 0.52rem !important;
        padding: 1px 3px !important;
      }

      .quick-progress-pills {
        display: flex !important;
        overflow-x: auto !important;
        flex-wrap: nowrap !important;
        -webkit-overflow-scrolling: touch !important;
        scrollbar-width: none !important;
        gap: 6px !important;
        padding-bottom: 2px !important;
      }
      .quick-progress-pills::-webkit-scrollbar {
        display: none !important;
      }
      .btn-prog-pill {
        flex: 0 0 auto !important;
        min-width: 86px !important;
        white-space: nowrap !important;
      }

      .photo-slot-grid {
        display: flex !important;
        overflow-x: auto !important;
        flex-wrap: nowrap !important;
        -webkit-overflow-scrolling: touch !important;
        scrollbar-width: none !important;
        gap: 6px !important;
        padding-bottom: 4px !important;
      }
      .photo-slot-grid::-webkit-scrollbar {
        display: none !important;
      }
      .photo-slot-card {
        flex: 0 0 102px !important;
        min-width: 102px !important;
        min-height: 100px !important;
        padding: 6px 4px !important;
      }
      .photo-camera-icon {
        width: 20px !important;
        height: 20px !important;
        margin-bottom: 3px !important;
      }
      .photo-slot-title {
        font-size: 0.65rem !important;
        line-height: 1.15 !important;
      }
      .photo-slot-sub {
        font-size: 0.55rem !important;
        line-height: 1.15 !important;
      }
      .photo-stage-pill {
        font-size: 0.54rem !important;
        padding: 1px 3px !important;
      }

      /* Stack stepper actions on compact screens */
      .stepper-actions-row {
        flex-direction: column-reverse !important;
        gap: 7px !important;
      }
      .btn-step-back {
        width: 100% !important;
        min-height: 44px !important;
        justify-content: center !important;
      }
      .btn-kirim-laporan {
        width: 100% !important;
        min-height: 48px !important;
        justify-content: center !important;
      }

      #pekayon-live-map {
        height: 145px !important;
      }

      .confirm-modal-actions {
        flex-direction: column-reverse !important;
        gap: 6px !important;
      }
      .btn-confirm-cancel,
      .btn-confirm-submit {
        width: 100% !important;
      }
    }

    /* ----- TIER 4: EXTRA NARROW MOBILE (≤ 340px) ----- */
    @media (max-width: 340px) {
      .hero-profile-row {
        flex-wrap: wrap !important;
        gap: 6px !important;
      }
      .hero-profile-col {
        flex: 1 1 100% !important;
      }
      .hero-role-side-wrap {
        width: 100% !important;
        margin-left: 0 !important;
      }
      .hero-role-pill-simple {
        width: 100% !important;
        justify-content: center !important;
        padding: 5px 8px !important;
      }
      .btn-regu-pill,
      .btn-day-pill,
      .btn-tugas-pill {
        font-size: 0.67rem !important;
      }
      .officer-contact-grid {
        grid-template-columns: 1fr !important;
        gap: 6px !important;
      }
      .btn-prog-pill {
        font-size: 0.68rem !important;
        padding: 5px 1px !important;
      }
    }
    <?php if (isset($_GET['tab']) && $_GET['tab'] === 'history'): ?>
      #user-phone-form-view { display: none !important; }
      #user-history-panel { display: block !important; }
      #tab-btn-form { background: transparent !important; color: #64748B !important; }
      #tab-btn-history { background: #FF5500 !important; color: #FFF !important; }
    <?php endif; ?>
    <?php if (isset($_GET['step']) && $_GET['step'] === '2'): ?>
      #step-container-1 { display: none !important; }
      #step-container-2 { display: block !important; }
      #wizard-node-1 .node-circle { background: #10B981 !important; color: #FFF !important; }
      #wizard-node-2 .node-circle { background: #FF5500 !important; color: #FFF !important; }
      #wizard-line { background: #10B981 !important; }
    <?php endif; ?>
  </style>
</head>
<body>

<div class="user-portal-page">
<div class="user-portal-wrapper">
  
  <?php include __DIR__ . '/views/user/header.php'; ?>

  <?php include __DIR__ . '/views/user/tabs.php'; ?>

  <?php include __DIR__ . '/views/user/form_lapor.php'; ?>

  <?php include __DIR__ . '/views/user/riwayat.php'; ?>

</div><!-- /.user-portal-wrapper -->
</div><!-- /.user-portal-page -->

<?php include __DIR__ . '/views/user/modals.php'; ?>

<script src="exceljs.min.js"></script>
<script src="shared.js?v=<?= time() ?>"></script>
<script>
  const PHP_USER = <?= json_encode([
    'name'     => $userName,
    'username' => $currentUser['username'] ?? '',
    'role'     => $currentUser['role'] ?? 'petugas',
    'official_role' => $userOfficialRole,
    'official_regu' => $userOfficialRegu,
    'official_regu_id' => $userOfficialReguId,
    'official_wilayah' => $userOfficialWilayah,
    'wilayah'  => $currentUser['wilayah'] ?? 'jaktim',
    'no_hp'    => $userPhone,
    'is_deleted' => $isAccountDeleted,
  ]) ?>;

  const DRAFT_STORAGE_KEY = "silap_user_report_draft_v4";

  const PEKAYON_CENTER = { lat: -6.338520, lng: 106.865410 };
  const PEKAYON_RW_DATA = {
    "RW 01": { road: "Jl. Lapan", patokan: "Wilayah RW 01 - Jl. Lapan", lat: -6.335800, lng: 106.863500 },
    "RW 02": { road: "Jl. Gandaria", patokan: "Wilayah RW 02 - Jl. Gandaria", lat: -6.333800, lng: 106.868200 },
    "RW 03": { road: "Jl. Gandaria", patokan: "Wilayah RW 03 - Jl. Gandaria", lat: -6.335200, lng: 106.869000 },
    "RW 04": { road: "Jl. Buah", patokan: "Wilayah RW 04 - Jl. Buah", lat: -6.337500, lng: 106.866000 },
    "RW 05": { road: "Jl. Raya Bogor", patokan: "Wilayah RW 05 - Jl. Raya Bogor", lat: -6.343200, lng: 106.868000 },
    "RW 06": { road: "Jl. Belly", patokan: "Wilayah RW 06 - Jl. Belly", lat: -6.341500, lng: 106.863800 },
    "RW 07": { road: "Jl. Tipar", patokan: "Wilayah RW 07 - Jl. Tipar", lat: -6.339200, lng: 106.866500 },
    "RW 08": { road: "Jl. Sukamto", patokan: "Wilayah RW 08 - Jl. Sukamto", lat: -6.336800, lng: 106.861200 },
    "RW 09": { road: "Jl. Haji Balok", patokan: "Wilayah RW 09 - Jl. Haji Balok", lat: -6.331800, lng: 106.863200 },
    "RW 10": { road: "Jl. Lewa", patokan: "Wilayah RW 10 - Jl. Lewa", lat: -6.334000, lng: 106.869800 }
  };

  // Form State
  let currentWizardStep = 1;
  let activeEditingReportId = null; // Terisi jika melanjutkan laporan lama (0% -> 50% -> 100%)
  let mobileSelectedHari = "Selasa";
  let mobileSelectedTugas = "Tugas 1";
  let mobileSelectedJob = "Pembersihan Jalan & Trotoar";
  let mobileSelectedKat = "kebersihan";
  let mobilePhotoSebelum = null;
  let mobilePhotoProses = null;
  let mobilePhotoSesudah = null;
  let mobileRegu = "Regu Jalan";
  let mobileRw = "";
  let mobileAddress = "";
  let mobileProgres = 0;
  let userReports = [];
  let currentCalendarDateStr = "";
  let mobileOfficerRole = "Ketua Regu";
  let teamMembers = [];

  function findActiveReport(list, idOrNoPekerjaan) {
    if (!idOrNoPekerjaan || !Array.isArray(list)) return null;
    const target = String(idOrNoPekerjaan).trim();
    return list.find(r => 
      (r.id !== undefined && String(r.id).trim() === target) ||
      (r.noPekerjaan && String(r.noPekerjaan).trim() === target)
    ) || null;
  }

  // Kamera & GPS Live TimeStamp State
  let activeCameraSlot = "sebelum";
  let cameraStream = null;
  let currentCameraFacing = "environment"; // Kamera belakang bawaan
  let isCameraMirrored = false; // Status cermin kamera
  let liveCoords = { lat: -6.335500, lng: 106.864200, accuracy: 5 };
  let hudTimer = null;
  let watchGpsId = null;
  let pekayonMap = null;
  let pekayonMarker = null;

  const SERVER_ANGGOTA_DATA = <?= json_encode($formattedServerAnggota) ?>;
  const SERVER_REGU_DATA = <?= json_encode($formattedServerRegu) ?>;

  const currentUser = PHP_USER || getCurrentUser();

  // Sinkronkan data master server ke client
  if (Array.isArray(SERVER_REGU_DATA) && SERVER_REGU_DATA.length > 0) {
    try { saveReguDB(SERVER_REGU_DATA, false); } catch(e) {}
  }
  if (Array.isArray(SERVER_ANGGOTA_DATA) && SERVER_ANGGOTA_DATA.length > 0) {
    try { saveAnggotaDB(SERVER_ANGGOTA_DATA, false); } catch(e) {}
  }

  // ==========================================================
  // PENGAWASAN STATUS AKUN PETUGAS (JIKA DIHAPUS ADMIN)
  // ==========================================================
  let isAccountDeletedModalActive = false;
  function showAccountDeletedAlert(customName) {
    if (isAccountDeletedModalActive) return;
    isAccountDeletedModalActive = true;

    const modal = document.getElementById("modal-account-deleted");
    const nameEl = document.getElementById("deleted-user-name");
    const myName = customName || (currentUser && (currentUser.name || currentUser.nama) ? (currentUser.name || currentUser.nama) : "Petugas");
    if (nameEl) nameEl.textContent = `"${myName}"`;
    if (modal) modal.style.display = "flex";

    // Kunci seluruh aksi interaksi aplikasi
    document.body.style.overflow = "hidden";
    const submitBtn = document.getElementById("btn-submit-laporan");
    if (submitBtn) {
      submitBtn.disabled = true;
      submitBtn.style.opacity = "0.5";
      submitBtn.style.pointerEvents = "none";
    }

    try {
      if (typeof navigator !== "undefined" && navigator.vibrate) {
        navigator.vibrate([200, 100, 200]);
      }
    } catch(eVib) {}
  }

  function checkCurrentAccountValidity() {
    if (currentUser && (currentUser.role === 'admin' || currentUser.is_admin)) return;
    if (currentUser && currentUser.is_deleted) {
      showAccountDeletedAlert();
      return;
    }
    const listAgt = typeof getAnggotaDB === "function" ? getAnggotaDB() : [];
    if (!Array.isArray(listAgt) || listAgt.length === 0) return;
    const currentName = (currentUser && (currentUser.name || currentUser.nama) ? (currentUser.name || currentUser.nama) : "").trim().toLowerCase();
    const currentHp = (currentUser && (currentUser.no_hp || currentUser.hp) ? (currentUser.no_hp || currentUser.hp) : "").replace(/\D/g, "");

    const found = listAgt.find(a => {
      const aName = (a.nama || "").trim().toLowerCase();
      const aHp = (a.hp || "").replace(/\D/g, "");
      return (currentName && aName === currentName) || (currentHp && aHp && aHp === currentHp);
    });

    if (!found) {
      showAccountDeletedAlert();
    }
  }

  // ==========================================================
  // 1. ROLE RESMI & REGU PETUGAS (TERKUNCI DARI ADMIN/DATABASE)
  // Role & Penempatan Regu sepenuhnya dikontrol Admin via Database
  // ==========================================================
  function initOfficerRole() {
    if (currentUser && (currentUser.role === 'admin' || currentUser.is_admin)) {
      mobileOfficerRole = "Administrator";
      mobileRegu = "Pusat Komando";
      const roleTextEl = document.getElementById("u-role-text");
      if (roleTextEl) roleTextEl.textContent = "ADMINISTRATOR • KELURAHAN";
      const phoneEl = document.getElementById("u-phone-val");
      if (phoneEl) {
        const ph = currentUser.no_hp || currentUser.hp || "";
        if (ph) phoneEl.textContent = typeof formatPhone08 === "function" ? formatPhone08(ph) : ph.replace(/[- ]/g, "").replace(/^\+?62/, "0");
      }
      const orgEl = document.getElementById("u-org-val");
      if (orgEl) orgEl.textContent = "• Administrator Kelurahan • Kel. Pekayon";
      const topSub = document.getElementById("u-topbar-sub");
      if (topSub) topSub.textContent = (currentUser.name || currentUser.nama || "Admin") + " · Administrator";
      const briefRegu = document.getElementById("brief-regu-text");
      if (briefRegu) briefRegu.textContent = "Komando";
      const dispRegu = document.getElementById("u-display-regu-name");
      if (dispRegu) dispRegu.textContent = "Pusat Komando (Admin)";
      const badgeEl = document.getElementById("u-header-badge");
      if (badgeEl) badgeEl.textContent = "Semua RW";
      return;
    }

    const listAgt = typeof getAnggotaDB === "function" ? getAnggotaDB() : [];
    const listRegu = typeof getReguDB === "function" ? getReguDB() : [];
    const currentName = (currentUser && (currentUser.name || currentUser.nama) ? (currentUser.name || currentUser.nama) : "").trim().toLowerCase();
    const currentHp = (currentUser && (currentUser.no_hp || currentUser.hp) ? (currentUser.no_hp || currentUser.hp) : "").replace(/\D/g, "");

    // 1. Cocokkan profil petugas dengan data personil Master Anggota
    let found = listAgt.find(a => {
      const aName = (a.nama || "").trim().toLowerCase();
      const aHp = (a.hp || "").replace(/\D/g, "");
      return aName === currentName || (currentHp && aHp && aHp === currentHp);
    });

    let assignedRole = (currentUser && currentUser.official_role) ? currentUser.official_role : "Anggota Regu";
    let assignedRegu = (currentUser && currentUser.official_regu) ? currentUser.official_regu : "";
    let assignedWilayah = "-";

    // Cek apakah tercatat sebagai ketua di daftar regu
    const regMatch = listRegu.find(r => (r.ketua || "").trim().toLowerCase() === currentName);
    if (regMatch) {
      assignedRole = "Ketua Regu";
      assignedRegu = regMatch.nama;
      assignedWilayah = regMatch.wilayah || "-";
    }

    if (found) {
      if (assignedRole === "Ketua Regu") {
        found.role = "Ketua Regu";
        if (assignedRegu) found.regu = assignedRegu;
      } else {
        assignedRole = found.role || assignedRole;
      }
      if (found.regu && found.regu !== "Belum Ditugaskan" && found.regu.trim() !== "") {
        assignedRegu = found.regu;
      } else if (found.reguId || found.regu_id) {
        const rId = found.reguId || found.regu_id;
        const rg = listRegu.find(r => r.id === rId);
        if (rg) {
          assignedRegu = rg.nama;
          assignedWilayah = rg.wilayah || "-";
        } else {
          assignedRegu = "";
        }
      } else {
        assignedRegu = "";
      }

      // Deteksi regu dari anggotaIds atau pencocokan ketua di list regu
      if (!assignedRegu || assignedRegu === "Belum Ditugaskan") {
        const memberRegu = listRegu.find(r => (r.anggotaIds && r.anggotaIds.includes(found.id)) || (r.ketua && (r.ketua || "").trim().toLowerCase() === currentName));
        if (memberRegu) {
          assignedRegu = memberRegu.nama;
          assignedWilayah = memberRegu.wilayah || "-";
          if (memberRegu.ketua && (memberRegu.ketua || "").trim().toLowerCase() === currentName) {
            assignedRole = "Ketua Regu";
            found.role = "Ketua Regu";
          }
          found.regu = assignedRegu;
          found.reguId = memberRegu.id;
        }
      }

      if (assignedRegu && found.wilayah && found.wilayah !== "10 RW" && found.wilayah !== "-") {
        assignedWilayah = found.wilayah;
      }
    }

    mobileOfficerRole = assignedRole;
    mobileRegu = assignedRegu;

    const hasRegu = (assignedRegu && assignedRegu !== "Belum Ditugaskan" && assignedRegu.trim() !== "");
    if (!hasRegu) {
      assignedWilayah = "-";
    }

    // Tampilkan Role & Regu Gabungan yang Terkunci (Read-Only)
    const roleTextEl = document.getElementById("u-role-text");
    if (roleTextEl) {
      roleTextEl.textContent = hasRegu
        ? `${assignedRegu.toUpperCase()} • ${assignedRole.toUpperCase()}`
        : `MENUNGGU PENUGASAN • ${assignedRole.toUpperCase()}`;
    }
    const phoneEl = document.getElementById("u-phone-val");
    if (phoneEl) {
      const ph = (found && found.hp) ? found.hp : (currentUser ? (currentUser.no_hp || currentUser.hp || "") : "");
      if (ph) {
        phoneEl.textContent = typeof formatPhone08 === "function" ? formatPhone08(ph) : ph.replace(/[- ]/g, "").replace(/^\+?62/, "0");
      }
    }
    const orgEl = document.getElementById("u-org-val");
    if (orgEl) {
      orgEl.textContent = hasRegu
        ? `• ${assignedRegu} • Kel. Pekayon`
        : `• Menunggu Penugasan • Kel. Pekayon`;
    }
    const topSub = document.getElementById("u-topbar-sub");
    if (topSub) {
      topSub.textContent = (currentUser ? (currentUser.name || currentUser.nama) : "Petugas") + " · " + (hasRegu ? assignedRegu : "Petugas Lapangan");
    }
    const briefRegu = document.getElementById("brief-regu-text");
    if (briefRegu) briefRegu.textContent = hasRegu ? assignedRegu : "-";

    const dispRegu = document.getElementById("u-display-regu-name");
    if (dispRegu) dispRegu.textContent = hasRegu ? assignedRegu : "Belum Ditugaskan";

    const dispBadge = document.getElementById("u-display-regu-badge");
    const dispCard = document.querySelector(".regu-selected-display");
    if (dispBadge) {
      if (hasRegu) {
        dispBadge.textContent = "Regu Aktif";
        dispBadge.className = "regu-display-badge active";
        if (dispCard) { dispCard.classList.add("is-assigned"); dispCard.classList.remove("is-pending"); }
      } else {
        dispBadge.textContent = "Menunggu Admin";
        dispBadge.className = "regu-display-badge pending";
        if (dispCard) { dispCard.classList.add("is-pending"); dispCard.classList.remove("is-assigned"); }
      }
    }

    const inpRegu = document.getElementById("u-regu-inp");
    if (inpRegu) inpRegu.value = hasRegu ? assignedRegu : "";

    const badgeEl = document.getElementById("u-header-badge");
    if (badgeEl) badgeEl.textContent = assignedWilayah || "-";

    if (!window._userHistFilterInitialized) {
      window._userHistFilterInitialized = true;
      histFilterRegu = hasRegu ? assignedRegu : "__UNASSIGNED__";
      const filterReguSelect = document.getElementById("hist-filter-regu");
      if (filterReguSelect && hasRegu) {
        filterReguSelect.value = assignedRegu;
      }
    }

    const recapRole = document.getElementById("modal-recap-regu");
    if (recapRole) {
      recapRole.textContent = hasRegu
        ? `${assignedRegu} • ${assignedRole}`
        : `Belum Ada Regu • ${assignedRole}`;
    }
  }

  function toggleOfficerRole() {
    // Role digabungkan dan dikunci oleh Master Data Admin (tidak dapat diubah mandiri)
    showToast("Role penugasan dikelola resmi oleh Administrator Kelurahan.", "info");
    return false;
  }

  // ==========================================================
  // 2. KALENDER OTOMATIS HARI KERJA
  // ==========================================================
  const ID_DAYS = ["Minggu", "Senin", "Selasa", "Rabu", "Kamis", "Jumat", "Sabtu"];
  const ID_MONTHS = ["Januari", "Februari", "Maret", "April", "Mei", "Juni", "Juli", "Agustus", "September", "Oktober", "November", "Desember"];

  function initAutomaticCalendar() {
    const now = new Date();
    const yyyy = now.getFullYear();
    const mm = String(now.getMonth() + 1).padStart(2, "0");
    const dd = String(now.getDate()).padStart(2, "0");
    const todayIso = `${yyyy}-${mm}-${dd}`;
    
    const calInp = document.getElementById("u-auto-calendar-inp");
    if (calInp) calInp.value = todayIso;
    onCalendarDateChanged(todayIso);
  }

  function onCalendarDateChanged(isoDate) {
    if (!isoDate) {
      snapCalendarToToday();
      return;
    }
    currentCalendarDateStr = isoDate;
    const parts = isoDate.split("-");
    const dateObj = new Date(parseInt(parts[0], 10), parseInt(parts[1], 10) - 1, parseInt(parts[2], 10));
    const dayName = ID_DAYS[dateObj.getDay()];
    const tglFormatted = `${dateObj.getDate()} ${ID_MONTHS[dateObj.getMonth()]} ${dateObj.getFullYear()}`;

    mobileSelectedHari = dayName;
    const hiddenInp = document.getElementById("u-hari-select");
    if (hiddenInp) hiddenInp.value = dayName;

    const hiddenIso = document.getElementById("u-tanggal-iso-inp");
    if (hiddenIso) hiddenIso.value = isoDate;

    const hariText = document.getElementById("cal-hari-text");
    if (hariText) hariText.textContent = dayName;

    const tglText = document.getElementById("cal-tgl-text");
    if (tglText) tglText.textContent = tglFormatted;

    const todayBrief = document.getElementById("cal-today-brief");
    if (todayBrief) todayBrief.textContent = dayName;

    const displayHariTugas = document.getElementById("u-display-tugas-hari");
    if (displayHariTugas) displayHariTugas.textContent = dayName;

    // Periksa dan kunci tugas yang sudah selesai untuk hari/tanggal ini
    updateTaskProgressStates();
    triggerDraftAutoSave();
  }

  function snapCalendarToToday() {
    const now = new Date();
    const yyyy = now.getFullYear();
    const mm = String(now.getMonth() + 1).padStart(2, "0");
    const dd = String(now.getDate()).padStart(2, "0");
    const todayIso = `${yyyy}-${mm}-${dd}`;
    const calInp = document.getElementById("u-auto-calendar-inp");
    if (calInp) calInp.value = todayIso;
    onCalendarDateChanged(todayIso);
    showToast("Jadwal kalender kembali ke Hari Ini (" + ID_DAYS[now.getDay()] + ")", "info");
  }

  // ==========================================================
  // 3. TARGET PENUGASAN (TUGAS 1 - 5) DISABLE OTOMATIS JIKA SELESAI
  // ==========================================================
  function updateTaskProgressStates() {
    const list = getLaporanDB();
    userReports = list;

    // Cari laporan milik regu ini pada hari/tanggal yang dipilih
    const completedTasks = new Set();
    const inProgressTasks = new Map();
    const targetDay = mobileSelectedHari;
    const targetDateStr = currentCalendarDateStr;

    const hasAssignedRegu = mobileRegu && mobileRegu !== "Belum Ditugaskan" && mobileRegu.trim() !== "";
    const moShorts = ["Jan","Feb","Mar","Apr","Mei","Jun","Jul","Agu","Sep","Okt","Nov","Des"];

    list.forEach(r => {
      if (!hasAssignedRegu) return;
      const matchRegu = (r.regu || "").toLowerCase() === mobileRegu.toLowerCase();
      if (!matchRegu) return;

      // Cek apakah tanggal/jadwal cocok dengan tanggal kalender aktif
      let matchDate = false;
      if (r.isoDate && targetDateStr) {
        matchDate = (r.isoDate === targetDateStr);
      } else if (r.tanggal && targetDateStr) {
        const parts = targetDateStr.split("-");
        if (parts.length === 3) {
          const dNum = parseInt(parts[2], 10);
          const moShort = moShorts[parseInt(parts[1], 10) - 1] || "";
          if (r.tanggal.indexOf(dNum + " " + moShort + " " + parts[0]) !== -1 || r.tanggal === targetDateStr) {
            matchDate = true;
          }
        }
      }

      if (matchDate && r.tugasKe) {
        const prog = parseInt(r.progres, 10) || 0;
        if (r.status === 'selesai' || prog >= 100) {
          completedTasks.add(r.tugasKe);
        } else {
          if (!inProgressTasks.has(r.tugasKe)) {
            inProgressTasks.set(r.tugasKe, r);
          }
        }
      }
    });

    let firstAvailableTask = null;
    let completedCount = 0;

    for (let i = 1; i <= 5; i++) {
      const tName = "Tugas " + i;
      const btn = document.getElementById("btn-tugas-" + i);
      if (!btn) continue;

      const isCompleted = completedTasks.has(tName);
      const inProg = inProgressTasks.get(tName);
      // Validasi Berurutan: Tugas i terbuka HANYA jika i === 1 ATAU Tugas sebelumnya (i-1) sudah selesai 100%
      const isPrevCompleted = (i === 1) || completedTasks.has("Tugas " + (i - 1));

      if (isCompleted) {
        completedCount++;
        btn.disabled = false;
        btn.classList.add("tugas-disabled-done");
        btn.classList.remove("active", "tugas-in-progress", "tugas-locked");
        btn.title = `${tName} sudah selesai (100% Tuntas). Klik untuk melihat detail`;
        btn.innerHTML = `<span>Tugas&nbsp;${i}</span><span class="tugas-done-tag">Selesai</span>`;
      } else if (!isPrevCompleted) {
        // Terkunci ketat: tugas sebelumnya belum diselesaikan
        btn.disabled = false;
        btn.classList.add("tugas-locked");
        btn.classList.remove("active", "tugas-in-progress", "tugas-disabled-done");
        btn.title = `Tugas ${i} terkunci! Selesaikan Tugas ${i - 1} terlebih dahulu`;
        btn.innerHTML = `<span>Tugas&nbsp;${i}</span>`;
      } else if (inProg) {
        btn.disabled = false;
        btn.classList.remove("tugas-disabled-done", "tugas-locked");
        btn.classList.add("tugas-in-progress");
        const progVal = parseInt(inProg.progres, 10) || 0;
        btn.title = `${tName} sedang berjalan (${progVal}%). Klik untuk melanjutkan`;
        btn.innerHTML = `<span>Tugas&nbsp;${i}</span><span class="tugas-done-tag" style="background:#FEF3C7;color:#D97706;border:1px solid #FDE68A">${progVal}%</span>`;
        if (!firstAvailableTask) {
          firstAvailableTask = tName;
        }
      } else {
        btn.disabled = false;
        btn.classList.remove("active", "tugas-in-progress", "tugas-disabled-done", "tugas-locked");
        btn.title = `Pilih ${tName}`;
        btn.innerHTML = `<span>Tugas&nbsp;${i}</span>`;
        if (!firstAvailableTask) {
          firstAvailableTask = tName;
        }
      }
    }

    const bannerAllDone = document.getElementById("all-tasks-completed-banner");
    const tugasSubTag = document.getElementById("u-display-tugas-sub");

    if (completedCount >= 5) {
      if (bannerAllDone) bannerAllDone.style.display = "flex";
      if (tugasSubTag) {
        tugasSubTag.textContent = "5 dari 5 Selesai (100% Tuntas)";
        tugasSubTag.style.background = "#DCFCE7";
        tugasSubTag.style.color = "#15803D";
        tugasSubTag.style.border = "1px solid #BBF7D0";
      }
    } else {
      if (bannerAllDone) bannerAllDone.style.display = "none";
      if (tugasSubTag) {
        const num = (mobileSelectedTugas || "Tugas 1").replace(/\D/g, "") || "1";
        tugasSubTag.textContent = `Tugas ${num} dari 5`;
        tugasSubTag.style.background = "";
        tugasSubTag.style.color = "";
        tugasSubTag.style.border = "";
      }
    }

    // Jika tugas aktif saat ini ternyata terkunci, otomatis alihkan ke tugas pertama yang tersedia
    const currentNum = parseInt((mobileSelectedTugas || "Tugas 1").replace(/\D/g, ""), 10) || 1;
    if (currentNum > 1 && !completedTasks.has("Tugas " + (currentNum - 1))) {
      selectTugasKe(firstAvailableTask || "Tugas 1");
      return;
    }

    // Pastikan button tugas aktif memiliki styling active
    document.querySelectorAll(".btn-tugas-pill").forEach(b => {
      if (b.dataset.tugas === mobileSelectedTugas && !b.classList.contains("tugas-locked")) {
        b.classList.add("active");
      } else {
        b.classList.remove("active");
      }
    });
  }

  function selectTugasKe(tugas, skipAutoResume = false) {
    const taskNum = parseInt(tugas.replace(/\D/g, ""), 10) || 1;
    const btn = Array.from(document.querySelectorAll(".btn-tugas-pill")).find(b => b.dataset.tugas === tugas);

    // KUNCI BERURUTAN: Tugas N terkunci jika Tugas (N - 1) belum 100% selesai
    if (taskNum > 1) {
      const list = getLaporanDB();
      const targetDateStr = currentCalendarDateStr;
      const hasAssignedRegu = mobileRegu && mobileRegu !== "Belum Ditugaskan" && mobileRegu.trim() !== "";
      const prevTaskName = "Tugas " + (taskNum - 1);
      const moShorts = ["Jan","Feb","Mar","Apr","Mei","Jun","Jul","Agu","Sep","Okt","Nov","Des"];

      const prevDone = list.some(r => {
        let matchOwner = false;
        if (hasAssignedRegu) {
          matchOwner = (r.regu || "").toLowerCase() === mobileRegu.toLowerCase();
        } else if (currentUser && (currentUser.name || currentUser.nama)) {
          const uName = (currentUser.name || currentUser.nama).trim().toLowerCase();
          matchOwner = (r.petugas || "").toLowerCase().indexOf(uName) !== -1;
        }
        if (!matchOwner) return false;

        let matchDate = false;
        if (r.isoDate && targetDateStr) {
          matchDate = (r.isoDate === targetDateStr);
        } else if (r.tanggal && targetDateStr) {
          const parts = targetDateStr.split("-");
          if (parts.length === 3) {
            const dNum = parseInt(parts[2], 10);
            const moShort = moShorts[parseInt(parts[1], 10) - 1] || "";
            if (r.tanggal.indexOf(dNum + " " + moShort + " " + parts[0]) !== -1 || r.tanggal === targetDateStr) matchDate = true;
          }
        } else {
          matchDate = true;
        }

        const prog = parseInt(r.progres, 10) || 0;
        return matchDate && r.tugasKe === prevTaskName && (r.status === 'selesai' || prog >= 100);
      });

      if (!prevDone) {
        showToast(`Tugas ${taskNum} Terkunci! Harap selesaikan Tugas ${taskNum - 1} terlebih dahulu agar berurutan.`, "warning");
        return;
      }
    }

    mobileSelectedTugas = tugas;
    const inp = document.getElementById("u-tugas-ke-select");
    if (inp) inp.value = tugas;

    const badge = document.getElementById("u-display-tugas-badge");
    if (badge) badge.textContent = tugas;

    const num = tugas.replace(/\D/g, "") || "1";
    const sub = document.getElementById("u-display-tugas-sub");
    if (sub) sub.textContent = `Tugas ${num} dari 5`;

    document.querySelectorAll(".btn-tugas-pill").forEach(b => {
      if (b.dataset.tugas === tugas && !b.classList.contains("tugas-locked")) {
        b.classList.add("active");
      } else {
        b.classList.remove("active");
      }
    });

    if (!skipAutoResume) {
      const list = getLaporanDB();
      const targetDay = mobileSelectedHari;
      const targetDateStr = currentCalendarDateStr;
      
      const hasAssignedRegu = mobileRegu && mobileRegu !== "Belum Ditugaskan" && mobileRegu.trim() !== "";
      const moShorts = ["Jan","Feb","Mar","Apr","Mei","Jun","Jul","Agu","Sep","Okt","Nov","Des"];
      const existingActive = hasAssignedRegu ? list.find(r => {
        const matchRegu = (r.regu || "").toLowerCase() === mobileRegu.toLowerCase();
        const matchTugas = r.tugasKe === tugas;
        let matchDate = false;
        if (r.isoDate && targetDateStr) {
          matchDate = (r.isoDate === targetDateStr);
        } else if (r.tanggal && targetDateStr) {
          const parts = targetDateStr.split("-");
          if (parts.length === 3) {
            const dNum = parseInt(parts[2], 10);
            const moShort = moShorts[parseInt(parts[1], 10) - 1] || "";
            if (r.tanggal.indexOf(dNum + " " + moShort + " " + parts[0]) !== -1 || r.tanggal === targetDateStr) matchDate = true;
          }
        }
        const isNotDone = r.status !== 'selesai' && (parseInt(r.progres, 10) < 100);
        return matchRegu && matchTugas && matchDate && isNotDone;
      }) : null;

      if (existingActive) {
        resumeReportFromHistory(existingActive.id || existingActive.noPekerjaan);
      } else {
        // Jika tugas baru yang belum dimulai: bersihkan seluruh state foto & ID aktif
        activeEditingReportId = null;
        localStorage.removeItem("silap_active_task_id");
        mobilePhotoSebelum = null;
        mobilePhotoProses = null;
        mobilePhotoSesudah = null;
        mobileProgres = 0;
        ["sebelum", "proses", "sesudah"].forEach(slot => {
          const card = document.getElementById("slot-" + slot);
          if (card) {
            card.className = "photo-slot-card";
            card.classList.remove("has-photo", "locked");
            card.innerHTML = "";
          }
        });
        const catInp = document.getElementById("u-catatan-inp");
        if (catInp) catInp.value = "";
        updateSesiState();
      }
    }

    triggerDraftAutoSave();
  }

  // ==========================================================
  // 4. DAFTAR ABSENSI PERSONEL REGU (DROPDOWN LIST CEKLIS)
  // ==========================================================
  function getTeamMembersStorageKey() {
    return "silap_team_members_" + (mobileRegu || "Regu 1").replace(/\s+/g, "_");
  }

  function loadTeamMembers() {
    const listAgt = typeof getAnggotaDB === "function" ? getAnggotaDB() : [];
    const listRegu = typeof getReguDB === "function" ? getReguDB() : [];

    // Jika belum punya regu resmi
    if (!mobileRegu || mobileRegu === "Belum Ditugaskan" || mobileRegu.trim() === "") {
      teamMembers = [
        { id: "m0", name: (currentUser ? (currentUser.name || currentUser.nama) : "Petugas"), role: mobileOfficerRole, present: true }
      ];
      renderAttendanceChecklist();
      return;
    }

    // Cari regu yang cocok
    const reg = listRegu.find(r => r.nama && r.nama.toLowerCase() === mobileRegu.toLowerCase());
    const regId = reg ? reg.id : null;

    // Filter anggota yang benar-benar ada di regu ini
    const membersInRegu = listAgt.filter(a => {
      if (regId && (a.reguId === regId || a.regu_id === regId)) return true;
      if (a.regu && a.regu.toLowerCase() === mobileRegu.toLowerCase()) return true;
      if (reg && reg.anggotaIds && reg.anggotaIds.includes(a.id)) return true;
      return false;
    });

    if (membersInRegu.length > 0) {
      teamMembers = membersInRegu.map((a, idx) => ({
        id: a.id || ("agt-" + idx),
        name: a.nama,
        role: a.role || "Anggota Regu",
        present: true
      }));
    } else {
      teamMembers = [
        { id: "m0", name: (currentUser ? (currentUser.name || currentUser.nama) : "Petugas"), role: mobileOfficerRole, present: true }
      ];
    }
    renderAttendanceChecklist();
  }

  function saveTeamMembers() {
    try {
      localStorage.setItem(getTeamMembersStorageKey(), JSON.stringify(teamMembers));
    } catch(e) {}
  }

  function renderAttendanceChecklist() {
    const grid = document.getElementById("absen-checklist-grid");
    if (!grid) return;

    grid.innerHTML = teamMembers.map(m => `
      <label class="absen-item ${m.present ? 'checked' : ''}">
        <input type="checkbox" class="absen-chk" ${m.present ? 'checked' : ''} onchange="toggleMemberPresent('${m.id}')">
        <div class="absen-member-info">
          <span class="absen-name">${m.name}</span>
          <span class="absen-role">${m.role}</span>
        </div>
        <span class="absen-status-pill ${m.present ? 'hadir' : 'absen'}">${m.present ? 'Hadir' : 'Tidak Hadir'}</span>
      </label>
    `).join("");

    const presentMembers = teamMembers.filter(m => m.present);
    const count = presentMembers.length;

    // Update hidden input jumlah
    const jmlInp = document.getElementById("u-jml-petugas-inp");
    if (jmlInp) jmlInp.value = count;

    // Update badge & labels
    const badge = document.getElementById("absen-count-badge");
    if (badge) {
      badge.textContent = `${count} Orang Hadir`;
      if (count < 1) {
        badge.style.background = "transparent";
        badge.style.color = "#DC2626";
        badge.style.border = "none";
        badge.style.fontWeight = "700";
      } else {
        badge.style.background = "#ECFDF5";
        badge.style.color = "#065F46";
        badge.style.border = "1px solid #A7F3D0";
        badge.style.fontWeight = "800";
      }
    }

    const titleEl = document.getElementById("absen-trigger-title");
    if (titleEl) titleEl.textContent = `${count} Personel Regu Hadir`;

    const subEl = document.getElementById("absen-trigger-sub");
    if (subEl) {
      subEl.textContent = presentMembers.length > 0
        ? presentMembers.map(m => m.name).join(", ")
        : "Belum ada anggota yang dicentang hadir!";
    }

    // Nama petugas utama pelapor
    const petugasInp = document.getElementById("u-petugas-inp");
    if (petugasInp && (!petugasInp.value || petugasInp.value === "Budi Santoso")) {
      const ketua = teamMembers.find(m => m.role === "Ketua Regu" && m.present) || presentMembers[0];
      if (ketua) petugasInp.value = ketua.name;
    }

    triggerDraftAutoSave();
  }

  function toggleMemberPresent(memberId) {
    const mem = teamMembers.find(m => m.id === memberId);
    if (mem) {
      mem.present = !mem.present;
      saveTeamMembers();
      renderAttendanceChecklist();
    }
  }

  function addNewMemberToChecklist() {
    const inp = document.getElementById("new-member-name-inp");
    if (!inp) return;
    const name = inp.value.trim();
    if (!name || name.length < 2) {
      showToast("Ketik nama anggota yang valid terlebih dahulu!", "warning");
      inp.focus();
      return;
    }

    const newMem = {
      id: "mem_" + Date.now(),
      name: name,
      role: "Anggota Regu",
      present: true
    };
    teamMembers.push(newMem);
    saveTeamMembers();
    renderAttendanceChecklist();
    inp.value = "";
    showToast(`Anggota "${name}" berhasil ditambahkan & dicentang hadir!`, "success");
  }

  function toggleAbsenDropdown() {
    const panel = document.getElementById("absen-dropdown-panel");
    const chevron = document.getElementById("absen-chevron");
    if (!panel) return;
    const isHidden = panel.style.display === "none";
    panel.style.display = isHidden ? "block" : "none";
    if (chevron) {
      if (isHidden) chevron.classList.add("open");
      else chevron.classList.remove("open");
    }
  }

  // ==========================================================
  // INITIAL VIEW BOOTSTRAP
  // ==========================================================
  function initUserView() {
    const greet = document.getElementById("u-greeting-name");
    const currentFullname = currentUser ? (currentUser.nama || currentUser.name || "Budi Santoso") : "Budi Santoso";
    if (greet) greet.textContent = currentFullname;

    const avBox = document.getElementById("u-hero-avatar");
    if (avBox && currentFullname) {
      avBox.textContent = currentFullname.trim().charAt(0).toUpperCase();
    }

    const badge = document.getElementById("u-header-badge");
    if (badge) badge.textContent = currentUser && currentUser.role === "admin" ? "Admin" : mobileRw;

    const offSub = document.getElementById("u-topbar-sub");
    if (offSub) offSub.textContent = (currentUser ? (currentUser.name || currentUser.nama) : "Budi Santoso") + (currentUser && currentUser.role === "admin" ? " (Admin)" : " · Petugas Lapangan");

    const phoneEl = document.getElementById("u-phone-val");
    if (phoneEl && currentUser && (currentUser.no_hp || currentUser.hp)) {
      const rawHp = currentUser.no_hp || currentUser.hp;
      phoneEl.textContent = typeof formatPhone08 === "function" ? formatPhone08(rawHp) : rawHp.replace(/[- ]/g, "").replace(/^\+?62/, "0");
    }

    initOfficerRole();
    initAutomaticCalendar();
    loadTeamMembers();
    renderAttendanceChecklist();

    generateNextJobNumber();

    // Form identitas petugas pelapor (otomatis sesuai data akun pengguna)
    const petInp = document.getElementById("u-petugas-inp");
    if (petInp && (!petInp.value || petInp.value.trim() === "")) {
      petInp.value = (currentUser && (currentUser.name || currentUser.nama)) ? (currentUser.name || currentUser.nama) : "Budi Santoso";
    }

    const hpInp = document.getElementById("u-nohp-inp");
    if (hpInp && (!hpInp.value || hpInp.value.trim() === "")) {
      hpInp.value = (currentUser && (currentUser.no_hp || currentUser.hp)) ? (currentUser.no_hp || currentUser.hp) : "081234567890";
    }

    function updateClock() {
      const now = new Date();
      const clockEl = document.getElementById("u-header-clock");
      if (clockEl) clockEl.textContent = String(now.getHours()).padStart(2, "0") + ":" + String(now.getMinutes()).padStart(2, "0") + " WIB";
    }
    updateClock();
    setInterval(updateClock, 30000);

    updateLocationDisplay();
    initPekayonMap();
    initLiveGpsWatcher();

    userReports = getLaporanDB();
    const hasResumed = checkAndAutoResumeActiveReport();
    if (!hasResumed) {
      loadFormDraft();
    }

    updateTaskProgressStates();
    renderUserHistory();
    updateSesiState();

    // Listeners Realtime Sinkronisasi Dua Arah (Admin <-> User)
    const refreshUserReports = () => {
      userReports = getLaporanDB();
      try { checkCurrentAccountValidity(); } catch(e) {}
      try { initOfficerRole(); } catch(e) {}
      try { updateTaskProgressStates(); } catch(e) {}
      try { renderUserHistory(); } catch(e) {}
      try {
        if (!activeEditingReportId && !mobilePhotoSebelum && !mobilePhotoProses && !mobilePhotoSesudah) {
          checkAndAutoResumeActiveReport(true);
        } else if (activeEditingReportId) {
          const activeLap = findActiveReport(userReports, activeEditingReportId);
          if (activeLap) updateSesiState(activeLap);
        }
      } catch(e) {}
    };

    const handleAnggotaSync = () => {
      try { checkCurrentAccountValidity(); } catch(e) {}
      try { initOfficerRole(); } catch(e) {}
      try { loadTeamMembers(); } catch(e) {}
      try { renderAttendanceChecklist(); } catch(e) {}
    };

    // Validasi akun aktif saat halaman dibuka
    checkCurrentAccountValidity();
    setInterval(checkCurrentAccountValidity, 3000);

    window.addEventListener("silap_laporan_updated", refreshUserReports);
    window.addEventListener("silap_anggota_updated", handleAnggotaSync);
    window.addEventListener("silap_regu_updated", handleAnggotaSync);
    window.addEventListener("storage", (e) => {
      if (!e.key || e.key === "silap_reports_db") refreshUserReports();
      if (e.key === "silap_master_anggota_v1" || e.key === "silap_master_regu_v1" || e.key === "silap_anggota_data_v3") handleAnggotaSync();
    });

    try {
      if (typeof BroadcastChannel !== "undefined") {
        const bc = new BroadcastChannel("silap_sync_channel");
        bc.onmessage = (ev) => {
          if (ev && ev.data) {
            if (ev.data.type === "user_deleted") {
              const myName = (currentUser.name || currentUser.nama || "").trim().toLowerCase();
              if (ev.data.deletedNama && ev.data.deletedNama.trim().toLowerCase() === myName) {
                showAccountDeletedAlert(ev.data.deletedNama);
                return;
              }
            }
            if (ev.data.type === "anggota_updated" || ev.data.type === "regu_updated") {
              handleAnggotaSync();
            }
          }
          refreshUserReports();
        };
      }
    } catch(eBc) {}

    window.addEventListener("focus", () => {
      refreshUserReports();
      handleAnggotaSync();
    });

    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get("tab") === "history" && typeof switchUserTab === "function") {
      switchUserTab("history");
    }
    if (urlParams.get("step") === "2" && typeof goToStep === "function") {
      goToStep(2);
    }
  }

  // ==========================================================
  // VALIDASI LANGKAH 1 (LOKASI & PERSONEL)
  // Wajib Diisi: Wilayah RW, Nama Jalan, dan Patokan Lokasi
  // ==========================================================
  function validateStep1(showFeedback = true) {
    const rwInp = document.getElementById("u-rw-select");
    const alamatInp = document.getElementById("u-custom-address-inp");
    const patokanInp = document.getElementById("u-patokan-inp");
    const petInp = document.getElementById("u-petugas-inp");
    const hpInp = document.getElementById("u-nohp-inp");

    const rwVal = rwInp ? rwInp.value.trim() : "";
    if (!rwVal || rwVal === "-- Pilih Wilayah RW --") {
      if (showFeedback) {
        showToast("Harap pilih Wilayah RW (Pekayon) pengerjaan terlebih dahulu!", "warning");
        if (rwInp) {
          rwInp.style.borderColor = "#DC2626";
          rwInp.style.boxShadow = "0 0 0 3px rgba(220, 38, 38, 0.15)";
          rwInp.focus();
          rwInp.scrollIntoView({ behavior: "smooth", block: "center" });
        }
      }
      return false;
    }

    const alamat = alamatInp ? alamatInp.value.trim() : "";
    if (!alamat) {
      if (showFeedback) {
        showToast("Harap isi Nama Jalan / Lokasi pengerjaan terlebih dahulu!", "warning");
        if (alamatInp) {
          alamatInp.style.borderColor = "#DC2626";
          alamatInp.style.boxShadow = "0 0 0 3px rgba(220, 38, 38, 0.15)";
          alamatInp.focus();
          alamatInp.scrollIntoView({ behavior: "smooth", block: "center" });
        }
      }
      return false;
    }

    const patokan = patokanInp ? patokanInp.value.trim() : "";
    if (!patokan) {
      if (showFeedback) {
        showToast("Harap isi Patokan Lokasi detail terlebih dahulu!", "warning");
        if (patokanInp) {
          patokanInp.style.borderColor = "#DC2626";
          patokanInp.style.boxShadow = "0 0 0 3px rgba(220, 38, 38, 0.15)";
          patokanInp.focus();
          patokanInp.scrollIntoView({ behavior: "smooth", block: "center" });
        }
      }
      return false;
    }

    const targetInp = document.getElementById("u-target-meter-inp");
    const targetMeterVal = targetInp ? targetInp.value.trim() : "";
    if (!targetMeterVal || isNaN(Number(targetMeterVal)) || Number(targetMeterVal) <= 0) {
      if (showFeedback) {
        showToast("Harap masukkan Target Penyelesaian (panjang target dalam meter) yang valid!", "warning");
        if (targetInp) {
          targetInp.style.borderColor = "#DC2626";
          targetInp.style.boxShadow = "0 0 0 3px rgba(220, 38, 38, 0.15)";
          targetInp.focus();
          targetInp.scrollIntoView({ behavior: "smooth", block: "center" });
        }
      }
      return false;
    }

    let petugas = petInp ? petInp.value.trim() : "";
    if (!petugas) {
      petugas = (currentUser && (currentUser.name || currentUser.nama)) ? (currentUser.name || currentUser.nama) : "Budi Santoso";
      if (petInp) petInp.value = petugas;
    }

    let noHp = hpInp ? hpInp.value.trim() : "";
    if (!noHp) {
      noHp = (currentUser && (currentUser.no_hp || currentUser.hp)) ? (currentUser.no_hp || currentUser.hp) : "081234567890";
      if (hpInp) hpInp.value = noHp;
    }

    // Reset error styling
    [rwInp, alamatInp, patokanInp, petInp, hpInp].forEach(el => {
      if (el) {
        el.style.borderColor = "";
        el.style.boxShadow = "";
      }
    });

    // Set nilai global setelah validasi lolos
    mobileRw = rwVal;
    mobileAddress = alamat;

    const jmlInp = document.getElementById("u-jml-petugas-inp");
    let jml = jmlInp ? parseInt(jmlInp.value, 10) : 4;
    if (!jml || jml < 1) {
      jml = 4;
      if (jmlInp) jmlInp.value = 4;
    }

    updateLocationDisplay();
    return true;
  }

  // ==========================================================
  // WIZARD STEPPER & NAVIGATION (STEP 1 <-> STEP 2)
  // Data tetap tersimpan aman saat kembali (tidak ter-reset)
  // ==========================================================
  function goToStep(step) {
    if (step === 2) {
      if (!validateStep1()) return;
      updateLocationDisplay();
      updateSesiState();
    }

    currentWizardStep = step;
    saveFormDraft();

    const step1El = document.getElementById("step-container-1");
    const step2El = document.getElementById("step-container-2");
    const node1 = document.getElementById("wizard-node-1");
    const node2 = document.getElementById("wizard-node-2");
    const line = document.getElementById("wizard-line");

    if (step === 1) {
      if (step1El) step1El.style.display = "block";
      if (step2El) step2El.style.display = "none";
      if (node1) { node1.classList.add("active"); node1.classList.remove("completed"); }
      if (node2) node2.classList.remove("active");
      if (line) line.classList.remove("step2-active");
      setTimeout(() => { if (pekayonMap) pekayonMap.invalidateSize(); }, 250);
    } else {
      if (step1El) step1El.style.display = "none";
      if (step2El) step2El.style.display = "block";
      if (node1) { node1.classList.remove("active"); node1.classList.add("completed"); }
      if (node2) node2.classList.add("active");
      if (line) line.classList.add("step2-active");
      updateSesiState();
    }

    // Scroll mulus ke bagian atas kartu
    const card = document.getElementById("user-phone-form-view");
    if (card) card.scrollIntoView({ behavior: "smooth", block: "start" });
  }

  // ==========================================================
  // 1. PILIH HARI KERJA (SENIN - JUMAT)
  // ==========================================================
  function selectHariKerja(hari) {
    mobileSelectedHari = hari;
    const hiddenInp = document.getElementById("u-hari-select");
    if (hiddenInp) hiddenInp.value = hari;

    const pillBadge = document.getElementById("u-display-hari-pill");
    if (pillBadge) pillBadge.textContent = hari;

    const displayHariTugas = document.getElementById("u-display-tugas-hari");
    if (displayHariTugas) displayHariTugas.textContent = hari;

    document.querySelectorAll(".btn-day-pill").forEach(btn => {
      if (btn.dataset.day === hari) {
        btn.classList.add("active");
      } else {
        btn.classList.remove("active");
      }
    });

    triggerDraftAutoSave();
  }


  // ==========================================================
  // 3. PILIH JENIS PEKERJAAN (5 PILIHAN DENGAN KETERANGAN)
  // ==========================================================
  function selectJobCard(cardEl) {
    document.querySelectorAll(".job-card-selectable").forEach(c => c.classList.remove("active"));
    cardEl.classList.add("active");

    mobileSelectedJob = cardEl.dataset.job || "Pembersihan Jalan & Trotoar";
    mobileSelectedKat = cardEl.dataset.kat || "kebersihan";

    const jobInp = document.getElementById("selected-job-val");
    const katInp = document.getElementById("selected-kat-val");
    if (jobInp) jobInp.value = mobileSelectedJob;
    if (katInp) katInp.value = mobileSelectedKat;

    triggerDraftAutoSave();
  }

  // Helper format judul ramah pengguna (contoh: Laporan Selasa (27 Okt 2026) • Tugas 1)
  function getLaporanHumanTitle(lap) {
    if (!lap) return "Laporan Lapangan";
    if (lap.minggu && typeof lap.minggu === "string" && lap.minggu.startsWith("Laporan ")) {
      return lap.minggu.replace(/\s•\s/g, " •\u00A0").replace(/\sTugas\s/g, " Tugas\u00A0");
    }
    const hari = lap.hari || mobileSelectedHari || (document.getElementById("cal-hari-text") ? document.getElementById("cal-hari-text").textContent.trim() : "Selasa");
    let tgl = lap.tanggalIso || lap.isoDate || lap.tanggal || "";
    if (tgl.includes("-")) {
      const parts = tgl.split("T")[0].split("-");
      if (parts.length === 3) {
        const monthNames = ["Jan", "Feb", "Mar", "Apr", "Mei", "Jun", "Jul", "Agu", "Sep", "Okt", "Nov", "Des"];
        const mIdx = parseInt(parts[1], 10) - 1;
        tgl = `${parseInt(parts[2], 10)} ${monthNames[mIdx] || parts[1]} ${parts[0]}`;
      }
    } else if (tgl.includes(",")) {
      tgl = tgl.split(",")[0].trim();
    } else if (document.getElementById("cal-tgl-text")) {
      tgl = document.getElementById("cal-tgl-text").textContent.trim().replace("Oktober", "Okt").replace("November", "Nov").replace("Desember", "Des");
    } else {
      tgl = "27 Okt 2026";
    }
    const rawTugas = lap.tugasKe || mobileSelectedTugas || "Tugas 1";
    const num = String(rawTugas).replace(/\D/g, "") || "1";
    return `Laporan ${hari} (${tgl}) •\u00A0Tugas\u00A0${num}`;
  }

  // ==========================================================
  // MANAJEMEN SESI FORM LAPORAN (SESI 1 AWAL VS SESI LANJUTAN)
  // Sesi 1: Awal 0% (hanya foto Sebelum).
  // Sesi Lanjutan: Selesaikan Pekerjaan (buka foto Proses & Sesudah).
  // ==========================================================
  // ==========================================================
  // MANAJEMEN SESI FORM LAPORAN (FLEKSIBEL & RESPONSIF)
  // Memungkinkan petugas memilih 0%, 50%, atau 100% dan membuka semua slot foto
  // ==========================================================
  function getStageStatus(activeLap) {
    if (!activeEditingReportId || !activeLap) return { hasSubmitted0: false, hasSubmitted50: false };
    const validPhotos = (activeLap.foto || []).filter(f => f && f.src && typeof f.src === "string" && f.src.trim() !== "" && f.src !== "null" && f.src !== "undefined");
    const prog = parseInt(activeLap.progres, 10) || 0;
    const hasSubmitted0 = validPhotos.length >= 1;
    const hasSubmitted50 = (prog >= 50 && validPhotos.length >= 2);
    return { hasSubmitted0, hasSubmitted50 };
  }

  function updateSesiState(forcedReport) {
    const list = getLaporanDB();
    const activeLap = forcedReport || (activeEditingReportId ? findActiveReport(list, activeEditingReportId) : null);

    // Status resmi pengiriman bertahap ke sistem (wajib cek foto valid + progres):
    const { hasSubmitted0, hasSubmitted50 } = getStageStatus(activeLap);

    const isResume = !!activeEditingReportId;
    const sesiNoticeEl = document.getElementById("sesi-notice-container");
    const p0 = document.getElementById("pill-prog-0");
    const p50 = document.getElementById("pill-prog-50");
    const p100 = document.getElementById("pill-prog-100");
    const range = document.getElementById("u-progres-range");
    const cardSebelum = document.getElementById("slot-sebelum");
    const cardProses = document.getElementById("slot-proses");
    const cardSesudah = document.getElementById("slot-sesudah");
    const btnSubmitText = document.getElementById("btn-submit-text");

    // Sinkronkan mobileProgres sesuai status laporan resmi
    if (hasSubmitted50) {
      mobileProgres = 100;
    } else if (hasSubmitted0) {
      mobileProgres = 50;
    } else {
      mobileProgres = 0;
    }

    if (sesiNoticeEl) {
      sesiNoticeEl.className = "sesi-notice-banner initial";
      const tahapBadge = !hasSubmitted0 ? "Tahap 1: Mulai Bekerja (0%)" : (!hasSubmitted50 ? "Tahap 2: Proses Pengerjaan (50%)" : "Tahap 3: Penyelesaian Tuntas (100%)");
      sesiNoticeEl.innerHTML = `
        <div class="sesi-badge-tag">${tahapBadge}</div>
        <div class="sesi-title">Dokumentasi Foto Lapangan Bertahap</div>
        <div class="sesi-desc">Foto 0% lalu Kirim untuk membuka 50%. Foto 50% lalu Kirim untuk membuka 100%. Peringatan konfirmasi muncul saat pengiriman final 100% ke Admin.</div>
      `;
    }

    if (range) {
      range.disabled = true;
      range.value = mobileProgres;
    }
    const badge = document.getElementById("u-progres-badge");
    if (badge) badge.textContent = mobileProgres + "%";

    if (p0) {
      p0.classList.toggle("locked", false);
      p0.classList.toggle("active", mobileProgres === 0);
      p0.title = "Tahap 0% (Mulai Bekerja)";
    }
    if (p50) {
      p50.classList.toggle("locked", !hasSubmitted0);
      p50.classList.toggle("active", mobileProgres === 50);
      p50.title = hasSubmitted0 ? "Tahap 50% (Sedang Dikerjakan)" : "Terkunci: Kirim Foto 0% Dulu";
    }
    if (p100) {
      p100.classList.toggle("locked", !hasSubmitted50);
      p100.classList.toggle("active", mobileProgres === 100);
      p100.title = hasSubmitted50 ? "Tahap 100% (Selesai Tuntas)" : "Terkunci: Kirim Foto 50% Dulu";
    }

    // ==========================================================
    // LOGIKA URUTAN FOTO BERTAHAP (SEKUENSIAL & TERKUNCI)
    // 1. Foto Sebelum (0%): Terbuka awal (wajib diambil)
    // 2. Foto Proses (50%): Terbuka HANYA jika Foto 0% sudah DIKIRIM ke sistem
    // 3. Foto Sesudah (100%): Terbuka HANYA jika Foto 50% sudah DIKIRIM ke sistem
    // ==========================================================

    // Slot 1: Sebelum (0%)
    if (cardSebelum) {
      if (mobilePhotoSebelum) {
        cardSebelum.classList.add("has-photo");
        cardSebelum.classList.remove("locked");
        cardSebelum.onclick = () => openCameraModal("sebelum");
      } else {
        cardSebelum.className = "photo-slot-card";
        cardSebelum.classList.remove("locked", "has-photo");
        cardSebelum.onclick = () => openCameraModal("sebelum");
        cardSebelum.innerHTML = `
          <svg class="photo-camera-icon" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"></path><circle cx="12" cy="13" r="4"></circle></svg>
          <div class="photo-slot-title">1. Sebelum (0%)</div>
          <div class="photo-slot-sub">Kondisi awal lokasi</div>
          <span class="photo-stage-pill ready">Wajib Awal</span>
        `;
      }
    }

    // Slot 2: Proses Kerja (50%)
    if (cardProses) {
      if (mobilePhotoProses) {
        cardProses.classList.add("has-photo");
        cardProses.classList.remove("locked");
        cardProses.onclick = () => openCameraModal("proses");
      } else if (hasSubmitted0) {
        cardProses.className = "photo-slot-card";
        cardProses.classList.remove("locked", "has-photo");
        cardProses.onclick = () => openCameraModal("proses");
        cardProses.innerHTML = `
          <svg class="photo-camera-icon" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"></path><circle cx="12" cy="13" r="4"></circle></svg>
          <div class="photo-slot-title">2. Proses Kerja (50%)</div>
          <div class="photo-slot-sub">Saat pengerjaan</div>
          <span class="photo-stage-pill ready">Siap Diambil</span>
        `;
      } else {
        cardProses.className = "photo-slot-card locked";
        cardProses.classList.remove("has-photo");
        cardProses.onclick = () => {
          showToast("Kirim Laporan Awal (0%) terlebih dahulu untuk membuka tahap 50%!", "warning");
        };
        cardProses.innerHTML = `
          <svg class="photo-camera-icon" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
          <div class="photo-slot-title">2. Proses Kerja (50%)</div>
          <div class="photo-slot-sub">Saat pengerjaan</div>
          <span class="photo-stage-pill pending">Terkunci (Kirim 0% Dulu)</span>
        `;
      }
    }

    // Slot 3: Sesudah (100%)
    if (cardSesudah) {
      if (mobilePhotoSesudah) {
        cardSesudah.classList.add("has-photo");
        cardSesudah.classList.remove("locked");
        cardSesudah.onclick = () => openCameraModal("sesudah");
      } else if (hasSubmitted50) {
        cardSesudah.className = "photo-slot-card";
        cardSesudah.classList.remove("locked", "has-photo");
        cardSesudah.onclick = () => openCameraModal("sesudah");
        cardSesudah.innerHTML = `
          <svg class="photo-camera-icon" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"></path><circle cx="12" cy="13" r="4"></circle></svg>
          <div class="photo-slot-title">3. Sesudah (100%)</div>
          <div class="photo-slot-sub">Hasil akhir tuntas</div>
          <span class="photo-stage-pill ready">Siap Diambil</span>
        `;
      } else {
        const lockMsg = !hasSubmitted0 
          ? "Kirim Laporan Awal (0%) dan Progres (50%) terlebih dahulu!" 
          : "Kirim Progres Lapangan (50%) terlebih dahulu untuk membuka tahap 100%!";
        const lockPill = !hasSubmitted0 ? "Terkunci (Kirim 0% Dulu)" : "Terkunci (Kirim 50% Dulu)";
        cardSesudah.className = "photo-slot-card locked";
        cardSesudah.classList.remove("has-photo");
        cardSesudah.onclick = () => {
          showToast(lockMsg, "warning");
        };
        cardSesudah.innerHTML = `
          <svg class="photo-camera-icon" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
          <div class="photo-slot-title">3. Sesudah (100%)</div>
          <div class="photo-slot-sub">Hasil akhir tuntas</div>
          <span class="photo-stage-pill pending">${lockPill}</span>
        `;
      }
    }

    if (btnSubmitText) {
      if (hasSubmitted50) {
        btnSubmitText.textContent = "Kirim Laporan Tuntas (100% Selesai – 3 Foto Lengkap)";
      } else if (hasSubmitted0) {
        btnSubmitText.textContent = "Kirim Progres Lapangan (Progres 50% – Foto Proses)";
      } else {
        btnSubmitText.textContent = "Kirim Laporan Awal (Mulai 0% – Foto Sebelum)";
      }
    }
  }

  // ==========================================================
  // 5. PROGRESS SHORTCUT PILLS & DYNAMIC BUTTON TEXT
  // ==========================================================
  function setQuickProgres(val) {
    val = parseInt(val, 10);
    const list = getLaporanDB();
    const activeLap = activeEditingReportId ? findActiveReport(list, activeEditingReportId) : null;
    const { hasSubmitted0, hasSubmitted50 } = getStageStatus(activeLap);

    if (val >= 50 && !hasSubmitted0) {
      showToast("Kirim Laporan Awal (0%) terlebih dahulu untuk membuka tahap 50%!", "warning");
      return;
    }
    if (val >= 100 && !hasSubmitted50) {
      showToast("Kirim Progres Lapangan (50%) terlebih dahulu untuk membuka tahap 100%!", "warning");
      return;
    }
    mobileProgres = val;
    const slider = document.getElementById("u-progres-range");
    if (slider) {
      slider.value = val;
    }
    handleProgresSlider(val);
  }

  function handleProgresSlider(val) {
    val = parseInt(val, 10);
    const list = getLaporanDB();
    const activeLap = activeEditingReportId ? findActiveReport(list, activeEditingReportId) : null;
    const { hasSubmitted0, hasSubmitted50 } = getStageStatus(activeLap);

    if (val >= 50 && !hasSubmitted0) {
      showToast("Kirim Laporan Awal (0%) terlebih dahulu untuk membuka tahap 50%!", "warning");
      val = 0;
    } else if (val >= 100 && !hasSubmitted50) {
      showToast("Kirim Progres Lapangan (50%) terlebih dahulu untuk membuka tahap 100%!", "warning");
      val = hasSubmitted0 ? 50 : 0;
    }
    mobileProgres = val;
    const slider = document.getElementById("u-progres-range");
    if (slider) slider.value = mobileProgres;

    const badge = document.getElementById("u-progres-badge");
    if (badge) badge.textContent = mobileProgres + "%";

    // Update highlight tombol shortcut
    document.querySelectorAll(".btn-prog-pill").forEach(p => p.classList.remove("active"));
    if (mobileProgres === 0) {
      const p0 = document.getElementById("pill-prog-0");
      if (p0) p0.classList.add("active");
    } else if (mobileProgres === 50) {
      const p50 = document.getElementById("pill-prog-50");
      if (p50) p50.classList.add("active");
    } else if (mobileProgres === 100) {
      const p100 = document.getElementById("pill-prog-100");
      if (p100) p100.classList.add("active");
    }

    const btnText = document.getElementById("btn-submit-text");
    if (btnText) {
      if (hasSubmitted50) {
        btnText.textContent = "Kirim Laporan Tuntas (100% Selesai – 3 Foto Lengkap)";
      } else if (hasSubmitted0) {
        btnText.textContent = "Kirim Progres Lapangan (Progres 50% – Foto Proses)";
      } else {
        btnText.textContent = "Kirim Laporan Awal (Mulai 0% – Foto Sebelum)";
      }
    }

    triggerDraftAutoSave();
  }

  function cancelResumeReport() {
    localStorage.removeItem("silap_active_task_id");
    clearFormDraft();
    resetFormFields();
    updateSesiState();
    goToStep(1);
    showToast("Kembali ke mode buat laporan baru dari Langkah 1.", "info");
  }

  function resetFormFields() {
    localStorage.removeItem("silap_active_task_id");
    activeEditingReportId = null;
    const rwSel = document.getElementById("u-rw-select");
    if (rwSel) rwSel.value = "";
    const addrInp = document.getElementById("u-custom-address-inp");
    if (addrInp) addrInp.value = "";
    const patokanInp = document.getElementById("u-patokan-inp");
    if (patokanInp) patokanInp.value = "";
    const targetInp = document.getElementById("u-target-meter-inp");
    if (targetInp) targetInp.value = "";
    mobileRw = "";
    mobileAddress = "";
    const catInp = document.getElementById("u-catatan-inp");
    if (catInp) catInp.value = "";
    mobilePhotoSebelum = null;
    mobilePhotoProses = null;
    mobilePhotoSesudah = null;
    mobileProgres = 0;
    selectTugasKe("Tugas 1");

    ["sebelum", "proses", "sesudah"].forEach(slot => {
      const card = document.getElementById("slot-" + slot);
      if (card) {
        card.className = "photo-slot-card";
      }
    });

    updateLocationDisplay();
    updateSesiState();
  }

  // ==========================================================
  // PILIHAN REGU KERJA PPSU
  // ==========================================================
  function selectReguPill(reguName) {
    mobileRegu = reguName;
    const inp = document.getElementById("u-regu-inp");
    if (inp) inp.value = reguName;

    const disp = document.getElementById("u-display-regu-name");
    if (disp) disp.textContent = reguName;

    const briefRegu = document.getElementById("brief-regu-text");
    if (briefRegu) briefRegu.textContent = reguName;

    const filterReguSelect = document.getElementById("hist-filter-regu");
    if (filterReguSelect) filterReguSelect.value = reguName;
    histFilterRegu = reguName;

    if (typeof loadTeamMembers === "function") loadTeamMembers();
    if (typeof renderAttendanceChecklist === "function") renderAttendanceChecklist();
    if (typeof updateTaskProgressStates === "function") updateTaskProgressStates();

    document.querySelectorAll(".btn-regu-pill").forEach(btn => {
      if (btn.dataset.regu === reguName) {
        btn.classList.add("active");
      } else {
        btn.classList.remove("active");
      }
    });

    triggerDraftAutoSave();
  }

  function onCustomReguTyped() {
    const inp = document.getElementById("u-regu-inp");
    const val = inp ? inp.value.trim() : "";
    mobileRegu = val || "Regu 1";

    const disp = document.getElementById("u-display-regu-name");
    if (disp) disp.textContent = mobileRegu;

    const briefRegu = document.getElementById("brief-regu-text");
    if (briefRegu) briefRegu.textContent = mobileRegu;

    document.querySelectorAll(".btn-regu-pill").forEach(btn => {
      if (btn.dataset.regu.toLowerCase() === val.toLowerCase()) {
        btn.classList.add("active");
      } else {
        btn.classList.remove("active");
      }
    });
  }

  function generateNextJobNumber() {
    if (activeEditingReportId) return activeEditingReportId;
    const list = getLaporanDB();
    const nextNum = String(list.length + 1).padStart(3, "0");
    return "PKJ-" + nextNum;
  }

  // Helper: Dapatkan lokasi dan koordinat efektif yang sinkron dengan form pengerjaan
  function getEffectiveLocation() {
    const rwSel = document.getElementById("u-rw-select");
    const addrInp = document.getElementById("u-custom-address-inp");
    const rw = rwSel && rwSel.value ? rwSel.value : mobileRw;
    const jalan = addrInp && addrInp.value.trim() ? addrInp.value.trim() : "";

    let fullAddress = "";
    let line1 = "";
    let line2 = "";

    if (jalan && rw) {
      fullAddress = `${jalan}, ${rw}, Kel. Pekayon, Jakarta Timur`;
      line1 = `${jalan},`;
      line2 = `${rw}, Kel. Pekayon, Jakarta Timur`;
    } else if (jalan) {
      fullAddress = `${jalan}, Kel. Pekayon, Jakarta Timur`;
      line1 = `${jalan},`;
      line2 = "Kel. Pekayon, Jakarta Timur";
    } else if (rw) {
      fullAddress = `${rw}, Kel. Pekayon, Jakarta Timur`;
      line1 = `${rw},`;
      line2 = "Kel. Pekayon, Jakarta Timur";
    } else if (mobileAddress && mobileAddress !== "Pilih RW & ketik lokasi pengerjaan...") {
      fullAddress = mobileAddress;
      const parts = mobileAddress.split(",");
      line1 = (parts[0] ? parts[0].trim() : mobileAddress) + ",";
      line2 = parts.length > 1 ? parts.slice(1).join(", ").trim() : "Kel. Pekayon, Jakarta Timur";
    } else {
      const detected = typeof detectGpsLocationName === "function" ? detectGpsLocationName(liveCoords.lat, liveCoords.lng) : null;
      fullAddress = window.liveGpsAddress || detected || "Jl. Raya Kalisari No. 12, RW 01, Kel. Pekayon, Jakarta Timur";
      const parts = fullAddress.split(",");
      line1 = (parts[0] ? parts[0].trim() : fullAddress) + ",";
      line2 = parts.length > 1 ? parts.slice(1).join(", ").trim() : "Kel. Pekayon, Jakarta Timur";
    }

    const lat = (liveCoords && liveCoords.lat !== undefined) ? liveCoords.lat : -6.335500;
    const lng = (liveCoords && liveCoords.lng !== undefined) ? liveCoords.lng : 106.864200;
    const accuracy = (liveCoords && liveCoords.accuracy !== undefined) ? liveCoords.accuracy : 5;

    return { fullAddress, line1, line2, lat, lng, accuracy };
  }

  function updateLocationDisplay() {
    const loc = getEffectiveLocation();
    mobileAddress = loc.fullAddress;

    const disp = document.getElementById("u-display-location");
    if (disp) disp.textContent = loc.fullAddress;

    const coordsTextEl = document.getElementById("u-coords-text");
    if (coordsTextEl) {
      const latStr = (loc.lat >= 0 ? "+" : "") + loc.lat.toFixed(6);
      const lngStr = (loc.lng >= 0 ? "+" : "") + loc.lng.toFixed(6);
      coordsTextEl.textContent = `Lat ${latStr}, Long ${lngStr} (Akurat ±${loc.accuracy}m)`;
    }

    const mapBtn = document.getElementById("btn-view-map");
    if (mapBtn) {
      mapBtn.href = `https://www.google.com/maps?q=${loc.lat},${loc.lng}`;
    }

    const rwSel = document.getElementById("u-rw-select");
    mobileRw = rwSel && rwSel.value ? rwSel.value : mobileRw;
    const badge = document.getElementById("u-header-badge");
    if (badge) badge.textContent = (currentUser && currentUser.role === "admin" ? "Admin" : (mobileRw || "RW 01"));

    updateLiveHudClock();
  }

  function switchUserTab(tab) {
    const formView = document.getElementById("user-phone-form-view");
    const histView = document.getElementById("user-history-panel");
    const btnF = document.getElementById("tab-btn-form");
    const btnH = document.getElementById("tab-btn-history");

    if (tab === "history") {
      if (formView) formView.style.display = "none";
      if (histView) histView.classList.add("show");
      if (btnF) btnF.classList.remove("active");
      if (btnH) btnH.classList.add("active");
      renderUserHistory();
    } else {
      if (formView) formView.style.display = "block";
      if (histView) histView.classList.remove("show");
      if (btnF) btnF.classList.add("active");
      if (btnH) btnH.classList.remove("active");
      
      // Jika ada laporan aktif yang belum selesai 100%, otomatis langsung ke Langkah 2 (Progres & Foto)
      if (activeEditingReportId) {
        goToStep(2);
      } else {
        const resumed = checkAndAutoResumeActiveReport();
        if (!resumed) generateNextJobNumber();
      }
      setTimeout(() => { if (pekayonMap) pekayonMap.invalidateSize(); }, 250);
    }
  }

  // ==========================================================
  // PER-SLOT PHOTO DRAFT HELPERS (ANTI-RESET HP KENTANG / LOGOUT)
  // ==========================================================
  function getPhotoDraftKey(slot) {
    const owner = (mobileRegu && mobileRegu !== "Belum Ditugaskan" ? mobileRegu : (currentUser ? (currentUser.username || currentUser.nama || "user") : "user")).replace(/[^a-z0-9]/gi, "_").toLowerCase();
    const tgl = (currentCalendarDateStr || "today").replace(/[^a-z0-9-]/gi, "");
    const tgs = (mobileSelectedTugas || "Tugas 1").replace(/[^a-z0-9]/gi, "_").toLowerCase();
    return `silap_p_${owner}_${tgl}_${tgs}_${slot}`;
  }

  function savePhotoSlotDraft(slot, src, ts) {
    if (!src) return;
    try {
      localStorage.setItem(getPhotoDraftKey(slot), JSON.stringify({ src, ts: ts || "Tersimpan", savedAt: Date.now() }));
    } catch(e) {}
  }

  function loadPhotoSlotDraft(slot) {
    try {
      const raw = localStorage.getItem(getPhotoDraftKey(slot));
      if (!raw) return null;
      const parsed = JSON.parse(raw);
      return (parsed && parsed.src) ? parsed : null;
    } catch(e) { return null; }
  }

  function clearAllPhotoSlotDrafts() {
    ["sebelum", "proses", "sesudah"].forEach(s => {
      try { localStorage.removeItem(getPhotoDraftKey(s)); } catch(e) {}
    });
  }

  function findActiveTodayReportForUser(list) {
    if (!Array.isArray(list) || list.length === 0) return null;
    const hasAssignedRegu = mobileRegu && mobileRegu !== "Belum Ditugaskan" && mobileRegu.trim() !== "";
    const uName = currentUser ? (currentUser.name || currentUser.nama || "").trim().toLowerCase() : "";
    const targetDateStr = currentCalendarDateStr;
    const moShorts = ["Jan","Feb","Mar","Apr","Mei","Jun","Jul","Agu","Sep","Okt","Nov","Des"];

    return list.find(r => {
      let matchOwner = false;
      if (hasAssignedRegu) {
        matchOwner = (r.regu || "").toLowerCase() === mobileRegu.toLowerCase();
      } else if (uName) {
        matchOwner = (r.petugas || "").toLowerCase().indexOf(uName) !== -1;
      }
      if (!matchOwner) return false;

      let matchDate = false;
      if (r.isoDate && targetDateStr) {
        matchDate = (r.isoDate === targetDateStr);
      } else if (r.tanggal && targetDateStr) {
        const parts = targetDateStr.split("-");
        if (parts.length === 3) {
          const dNum = parseInt(parts[2], 10);
          const moShort = moShorts[parseInt(parts[1], 10) - 1] || "";
          if (r.tanggal.indexOf(dNum + " " + moShort + " " + parts[0]) !== -1 || r.tanggal === targetDateStr) matchDate = true;
        }
      } else {
        matchDate = true;
      }
      const prog = parseInt(r.progres, 10) || 0;
      return matchDate && r.status !== "selesai" && prog < 100;
    }) || null;
  }

  // ==========================================================
  // AUTO-RESUME LAPORAN AKTIF (SESI AWAL 0% MENUJU 50% / 100%)
  // ==========================================================
  function checkAndAutoResumeActiveReport(silent = false) {
    const list = getLaporanDB();
    const activeId = localStorage.getItem("silap_active_task_id");
    let target = activeId ? findActiveReport(list, activeId) : null;

    if (!target || parseInt(target.progres, 10) >= 100 || target.status === "selesai") {
      target = findActiveTodayReportForUser(list);
    }

    if (target && (parseInt(target.progres, 10) < 100 && target.status !== "selesai")) {
      resumeReportFromHistory(target.id || target.noPekerjaan, silent);
      // Gabungkan foto draft lokal (jika sempat memotret foto 0%/50%/100% sebelum keluar/refresh sebelum klik Kirim)
      try {
        const rawDraft = localStorage.getItem(DRAFT_STORAGE_KEY);
        if (rawDraft) {
          const d = JSON.parse(rawDraft);
          if (d && (!d.activeEditingReportId || String(d.activeEditingReportId) === String(target.id || target.noPekerjaan))) {
            if (!mobilePhotoSebelum && d.photoSebelum) setMobilePhotoSlot("sebelum", d.photoSebelum, "Draft");
            if (!mobilePhotoProses && d.photoProses) setMobilePhotoSlot("proses", d.photoProses, "Draft");
            if (!mobilePhotoSesudah && d.photoSesudah) setMobilePhotoSlot("sesudah", d.photoSesudah, "Draft");
          }
        }
      } catch(eDraftMerge) {}
      ["sebelum", "proses", "sesudah"].forEach(slot => {
        const hasVar = slot === "sebelum" ? mobilePhotoSebelum : (slot === "proses" ? mobilePhotoProses : mobilePhotoSesudah);
        if (!hasVar) {
          const pDraft = loadPhotoSlotDraft(slot);
          if (pDraft && pDraft.src) setMobilePhotoSlot(slot, pDraft.src, pDraft.ts || "Draft");
        }
      });
      return true;
    }
    if (activeId) localStorage.removeItem("silap_active_task_id");
    return false;
  }

  // ==========================================================
  // AUTO-SAVE DRAFT & STATE PRESERVATION
  // Menjaga data agar tidak hilang saat back atau refresh
  // ==========================================================
  function triggerDraftAutoSave() {
    saveFormDraft();
    const stPill = document.getElementById("draft-pill-status");
    if (stPill) {
      stPill.style.opacity = "1";
      stPill.innerHTML = '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg> Tersimpan';
    }
  }

  function saveFormDraft() {
    const baseDraft = {
      step: currentWizardStep,
      activeEditingReportId: activeEditingReportId,
      regu: mobileRegu,
      hari: mobileSelectedHari,
      tugasKe: mobileSelectedTugas,
      job: mobileSelectedJob,
      kat: mobileSelectedKat,
      petugas: document.getElementById("u-petugas-inp") ? document.getElementById("u-petugas-inp").value : "",
      noHp: document.getElementById("u-nohp-inp") ? document.getElementById("u-nohp-inp").value : "",
      jmlPetugas: document.getElementById("u-jml-petugas-inp") ? document.getElementById("u-jml-petugas-inp").value : 4,
      rw: document.getElementById("u-rw-select") ? document.getElementById("u-rw-select").value : "RW 01",
      lat: liveCoords.lat,
      lng: liveCoords.lng,
      jalan: document.getElementById("u-custom-address-inp") ? document.getElementById("u-custom-address-inp").value : "",
      patokan: document.getElementById("u-patokan-inp") ? document.getElementById("u-patokan-inp").value : "",
      targetMeter: document.getElementById("u-target-meter-inp") ? document.getElementById("u-target-meter-inp").value : "",
      progres: mobileProgres,
      catatan: document.getElementById("u-catatan-inp") ? document.getElementById("u-catatan-inp").value : ""
    };
    try {
      localStorage.setItem(DRAFT_STORAGE_KEY, JSON.stringify({
        ...baseDraft,
        photoSebelum: mobilePhotoSebelum || "",
        photoProses: mobilePhotoProses || "",
        photoSesudah: mobilePhotoSesudah || ""
      }));
    } catch(eQuota) {
      // Fallback jika kuota penuh: simpan metadata saja (foto tetap aman di key per-slot)
      try {
        localStorage.setItem(DRAFT_STORAGE_KEY, JSON.stringify(baseDraft));
      } catch(e2) {}
    }
  }

  function manualSaveDraftClick() {
    saveFormDraft();
    const stPill = document.getElementById("draft-pill-status");
    if (stPill) {
      stPill.style.opacity = "1";
      stPill.innerHTML = '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg> Draf Tersimpan';
    }
    showToast("Draf laporan & foto berhasil disimpan! Anda dapat melanjutkannya kapan saja.", "success");
  }

  function loadFormDraft() {
    try {
      // Bersihkan cache draft lama jika ada
      try {
        localStorage.removeItem("silap_user_report_draft_v1");
        localStorage.removeItem("silap_user_report_draft_v2");
        localStorage.removeItem("silap_user_report_draft_v3");
      } catch(e) {}

      const raw = localStorage.getItem(DRAFT_STORAGE_KEY);
      if (!raw) return;
      const d = JSON.parse(raw);
      if (!d) return;

      // Jika tersimpan draft lama yang masih berisi Kalisari No. 12 atau dummy preset bawaan, buang segera
      if (d.jalan && (d.jalan.includes("Kalisari No. 12") || d.jalan.includes("Margonda") || d.jalan.includes("Depok") || d.jalan.includes("Cikini") || d.jalan.includes("Menteng") || (d.patokan && (d.patokan.includes("Menteng") || d.patokan.includes("Pos RW 01"))))) {
        clearFormDraft();
        return;
      }

      if (d.activeEditingReportId) activeEditingReportId = d.activeEditingReportId;
      if (d.regu) selectReguPill(d.regu);
      if (d.hari) selectHariKerja(d.hari);
      if (d.tugasKe) selectTugasKe(d.tugasKe, true);

      if (d.job) {
        mobileSelectedJob = d.job;
        mobileSelectedKat = d.kat || "kebersihan";
        const c = Array.from(document.querySelectorAll(".job-card-selectable")).find(el => el.dataset.job === d.job);
        if (c) selectJobCard(c);
      }

      if (d.petugas && document.getElementById("u-petugas-inp")) document.getElementById("u-petugas-inp").value = d.petugas;
      if (d.noHp && document.getElementById("u-nohp-inp")) document.getElementById("u-nohp-inp").value = d.noHp;
      if (d.jmlPetugas && document.getElementById("u-jml-petugas-inp")) document.getElementById("u-jml-petugas-inp").value = d.jmlPetugas;
      const isDummyAddress = (d.jalan === "Jl. Raya Kalisari No. 12" && d.patokan === "Dekat Kantor Kelurahan Pekayon / Pos RW 01");
      if (!isDummyAddress) {
        if (d.rw && document.getElementById("u-rw-select")) {
          document.getElementById("u-rw-select").value = d.rw;
          mobileRw = d.rw;
        }
        if (d.jalan && document.getElementById("u-custom-address-inp")) document.getElementById("u-custom-address-inp").value = d.jalan;
        if (d.patokan && document.getElementById("u-patokan-inp")) document.getElementById("u-patokan-inp").value = d.patokan;
      }
      if (d.targetMeter && document.getElementById("u-target-meter-inp")) document.getElementById("u-target-meter-inp").value = d.targetMeter;
      if (d.catatan && document.getElementById("u-catatan-inp")) document.getElementById("u-catatan-inp").value = d.catatan;

      if (d.lat && d.lng) {
        setMapPosition(d.lat, d.lng, "Draft Presisi");
      }

      if (d.photoSebelum) {
        setMobilePhotoSlot("sebelum", d.photoSebelum, "Draft");
      } else {
        const p0 = loadPhotoSlotDraft("sebelum");
        if (p0 && p0.src) setMobilePhotoSlot("sebelum", p0.src, p0.ts || "Draft");
      }
      if (d.photoProses) {
        setMobilePhotoSlot("proses", d.photoProses, "Draft");
      } else {
        const p50 = loadPhotoSlotDraft("proses");
        if (p50 && p50.src) setMobilePhotoSlot("proses", p50.src, p50.ts || "Draft");
      }
      if (d.photoSesudah) {
        setMobilePhotoSlot("sesudah", d.photoSesudah, "Draft");
      } else {
        const p100 = loadPhotoSlotDraft("sesudah");
        if (p100 && p100.src) setMobilePhotoSlot("sesudah", p100.src, p100.ts || "Draft");
      }

      if (d.progres !== undefined) setQuickProgres(d.progres);
      updateLocationDisplay();

      // Jika sudah masuk Langkah 2 atau ada foto tersimpan, langsung buka Langkah 2
      if (d.step === 2 || mobilePhotoSebelum || mobilePhotoProses || mobilePhotoSesudah) {
        goToStep(2);
      }
    } catch(e) {}
  }

  function clearFormDraft() {
    activeEditingReportId = null;
    clearAllPhotoSlotDrafts();
    selectReguPill("Regu 1");
    selectTugasKe("Tugas 1");
    try {
      localStorage.removeItem(DRAFT_STORAGE_KEY);
      localStorage.removeItem("silap_user_report_draft_v1");
    } catch(e) {}
  }

  // ==========================================================
  // FITUR LANJUTKAN PROGRES DARI RIWAYAT
  // (Mengisi progres 0% -> 50% -> 100% selang beberapa jam)
  // ==========================================================
  function resumeReportFromHistory(id, silent = false) {
    const list = getLaporanDB();
    const lap = findActiveReport(list, id);
    if (!lap) return;

    activeEditingReportId = lap.id || lap.noPekerjaan;
    localStorage.setItem("silap_active_task_id", activeEditingReportId);

    if (lap.regu) {
      selectReguPill(lap.regu);
    }
    if (lap.tugasKe) {
      selectTugasKe(lap.tugasKe, true);
    }
    if (lap.minggu) {
      ["Senin", "Selasa", "Rabu", "Kamis", "Jumat"].forEach(h => {
        if (lap.minggu.includes(h)) selectHariKerja(h);
      });
    }

    // Pulihkan data laporan ke form
    if (lap.petugas && document.getElementById("u-petugas-inp")) document.getElementById("u-petugas-inp").value = lap.petugas;
    if (lap.rw && document.getElementById("u-rw-select")) document.getElementById("u-rw-select").value = lap.rw;
    if (lap.alamat && document.getElementById("u-custom-address-inp")) {
      const jalanOnly = lap.alamat.split(",")[0].trim();
      document.getElementById("u-custom-address-inp").value = jalanOnly;
    }
    if (lap.lokasiDetail && document.getElementById("u-patokan-inp")) document.getElementById("u-patokan-inp").value = lap.lokasiDetail;
    if ((lap.targetMeter || lap.target_meter) && document.getElementById("u-target-meter-inp")) document.getElementById("u-target-meter-inp").value = lap.targetMeter || lap.target_meter;
    if (lap.noHp && document.getElementById("u-nohp-inp")) document.getElementById("u-nohp-inp").value = lap.noHp;
    if (lap.jumlahPetugas && document.getElementById("u-jml-petugas-inp")) document.getElementById("u-jml-petugas-inp").value = lap.jumlahPetugas;
    if (lap.kendala && document.getElementById("u-catatan-inp")) document.getElementById("u-catatan-inp").value = lap.kendala;

    // Cari kartu pekerjaan yang sesuai
    if (lap.jenisPekerjaan) {
      mobileSelectedJob = lap.jenisPekerjaan;
      mobileSelectedKat = lap.kat || "kebersihan";
      const card = Array.from(document.querySelectorAll(".job-card-selectable")).find(el => el.dataset.job === lap.jenisPekerjaan);
      if (card) selectJobCard(card);
    }

    // Pulihkan foto yang sudah ada
    if (lap.foto && Array.isArray(lap.foto)) {
      lap.foto.forEach((f, idx) => {
        if (!f || !f.src || !f.src.trim()) return;
        const tahap = (f.tahap || "").toLowerCase();
        if (idx === 0 || tahap.includes("sebelum") || tahap.includes("0%")) {
          setMobilePhotoSlot("sebelum", f.src, f.timestamp || "Tersimpan");
        } else if (idx === 1 || tahap.includes("proses") || tahap.includes("50%")) {
          setMobilePhotoSlot("proses", f.src, f.timestamp || "Tersimpan");
        } else if (idx === 2 || tahap.includes("sesudah") || tahap.includes("100%")) {
          setMobilePhotoSlot("sesudah", f.src, f.timestamp || "Tersimpan");
        }
      });
    }

    // Naikkan target progres (misal dari 0% ke 50%, atau 50% ke 100%)
    const curProg = parseInt(lap.progres, 10) || 0;
    const nextProg = curProg < 50 ? 50 : 100;
    setQuickProgres(nextProg);

    updateLocationDisplay();

    // Buka tab form di Langkah 2 (Dokumentasi & Progres)
    switchUserTab("form");
    goToStep(2);
    updateSesiState(lap);
    if (!silent) {
      if (curProg < 50) {
        showToast("Sesi Lanjutan aktif: " + getLaporanHumanTitle(lap) + ". Silakan ambil Foto Proses Kerja (50%)!", "info");
      } else {
        showToast("Sesi Lanjutan aktif: " + getLaporanHumanTitle(lap) + ". Silakan ambil Foto Sesudah (100%) untuk menuntaskan laporan!", "info");
      }
    }
  }

  function closeRetakeConfirmModal() {
    const m = document.getElementById("modal-confirm-retake-photo");
    if (m) m.style.display = "none";
  }

  // ==========================================================
  // MODUL KAMERA GPS BERTANDA AIR (LIVE VIEWFINDER - 3 SLOT)
  // ==========================================================
  async function openCameraModal(slot, forceRetake = false) {
    const list = getLaporanDB();
    const activeLap = activeEditingReportId ? findActiveReport(list, activeEditingReportId) : null;
    const hasSubmitted0 = !!(activeEditingReportId && activeLap && activeLap.foto && activeLap.foto.length >= 1);
    const hasSubmitted50 = !!(activeEditingReportId && activeLap && (parseInt(activeLap.progres, 10) >= 50 || activeLap.status === "proses" || activeLap.status === "selesai") && activeLap.foto && activeLap.foto.length >= 2);

    if (slot === "proses" && !hasSubmitted0) {
      showToast("Kirim Laporan Awal (0%) terlebih dahulu untuk membuka tahap 50%!", "warning");
      return;
    }
    if (slot === "sesudah") {
      if (!hasSubmitted0) {
        showToast("Kirim Laporan Awal (0%) dan Progres (50%) terlebih dahulu!", "warning");
        return;
      }
      if (!hasSubmitted50) {
        showToast("Kirim Progres Lapangan (50%) terlebih dahulu untuk membuka tahap 100%!", "warning");
        return;
      }
    }

    // Cegah kamera langsung terbuka jika foto pada slot ini sudah ada (konfirmasi Ya/Tidak)
    const hasExistingPhoto = (slot === "sebelum" && !!mobilePhotoSebelum) || (slot === "proses" && !!mobilePhotoProses) || (slot === "sesudah" && !!mobilePhotoSesudah);
    if (hasExistingPhoto && !forceRetake) {
      const m = document.getElementById("modal-confirm-retake-photo");
      const desc = document.getElementById("retake-confirm-desc");
      const btnYes = document.getElementById("btn-confirm-retake-yes");
      const stageLabel = slot === "sebelum" ? "Sebelum (0%)" : slot === "proses" ? "Proses Kerja (50%)" : "Sesudah (100%)";
      if (desc) {
        desc.innerHTML = `Foto tahap <strong>${stageLabel}</strong> sudah tersimpan. Apakah Anda yakin ingin mengambil foto ulang?`;
      }
      if (btnYes) {
        btnYes.onclick = () => {
          closeRetakeConfirmModal();
          openCameraModal(slot, true);
        };
      }
      if (m) {
        m.style.display = "flex";
        return;
      }
    }
    activeCameraSlot = slot;
    const modal = document.getElementById("camera-modal");
    const label = document.getElementById("cam-slot-label");
    const slotTitle = slot === "sebelum" ? "1. Foto Sebelum (0%)" : slot === "proses" ? "2. Foto Selama Proses (0-50%)" : "3. Foto Sesudah (100%)";
    const tagText = slot === "sebelum" ? "FOTO SEBELUM (0%)" : slot === "proses" ? "PROSES PENGERJAAN" : "FOTO SESUDAH (100%)";

    if (label) label.textContent = slotTitle;
    const tag = document.getElementById("hud-badge-tag");
    if (tag) tag.textContent = "PPSU DKI JAKARTA • " + tagText;

    modal.classList.remove("hidden");
    document.body.style.overflow = "hidden";

    updateLiveHudClock();
    if (hudTimer) clearInterval(hudTimer);
    hudTimer = setInterval(updateLiveHudClock, 1000);

    await startCameraStream(currentCameraFacing);
  }

  function closeCameraModal() {
    stopCameraStream();
    if (hudTimer) {
      clearInterval(hudTimer);
      hudTimer = null;
    }
    const modal = document.getElementById("camera-modal");
    if (modal) modal.classList.add("hidden");
    document.body.style.overflow = "";
  }

  function toggleCameraMirror() {
    isCameraMirrored = !isCameraMirrored;
    const video = document.getElementById("camera-video-feed");
    if (video) {
      video.style.transform = isCameraMirrored ? "scaleX(-1)" : "none";
    }
    const btn = document.getElementById("cam-btn-mirror");
    if (btn) {
      btn.style.background = isCameraMirrored ? "rgba(255, 84, 22, 0.6)" : "rgba(255, 255, 255, 0.18)";
      btn.style.borderColor = isCameraMirrored ? "#FF5416" : "rgba(255, 255, 255, 0.25)";
    }
    showToast(isCameraMirrored ? "Mode Cermin (Mirror) Aktif" : "Mode Cermin Nonaktif (Normal)", "info");
  }

  async function startCameraStream(facing) {
    const loading = document.getElementById("camera-loading-notice");
    if (loading) {
      loading.classList.remove("hidden");
      loading.innerHTML = '<div class="cam-spinner"></div><span>Mengaktifkan kamera & sinyal GPS...</span>';
    }

    stopCameraStream();

    const video = document.getElementById("camera-video-feed");
    try {
      if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        throw new Error("Kamera WebRTC tidak didukung browser ini.");
      }

      const constraints = {
        video: {
          facingMode: facing ? { ideal: facing } : "environment",
          width: { ideal: 1920, min: 640 },
          height: { ideal: 1080, min: 480 }
        },
        audio: false
      };

      cameraStream = await navigator.mediaDevices.getUserMedia(constraints);
      video.srcObject = cameraStream;
      // Terapkan efek cermin jika mode cermin diaktifkan
      video.style.transform = isCameraMirrored ? "scaleX(-1)" : "none";
      await video.play();

      if (loading) loading.classList.add("hidden");
    } catch (err) {
      console.warn("Kamera langsung tidak dapat diakses:", err);
      if (loading) {
        loading.innerHTML = '<div style="padding:24px;text-align:center;max-width:320px;">' +
          '<div style="font-size:1.05rem;font-weight:800;margin-bottom:8px;color:#F8FAFC;">Kamera Lapangan Siap</div>' +
          '<div style="font-size:0.8rem;color:#94A3B8;line-height:1.5;margin-bottom:16px;">Gunakan kamera ponsel langsung untuk mengambil dokumentasi lapangan:</div>' +
          '<div style="display:flex;justify-content:center;">' +
          '<button type="button" class="user-pill-btn primary" onclick="document.getElementById(\'camera-native-input\').click()" style="margin:0 auto;display:inline-flex;padding:11px 22px;font-weight:800;gap:8px;">' +
          '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"></path><circle cx="12" cy="13" r="4"></circle></svg>' +
          'Buka Kamera HP Langsung</button>' +
          '</div></div>';
      }
    }
  }

  function stopCameraStream() {
    if (cameraStream) {
      cameraStream.getTracks().forEach(track => track.stop());
      cameraStream = null;
    }
  }

  async function switchCameraFacing() {
    currentCameraFacing = currentCameraFacing === "environment" ? "user" : "environment";
    await startCameraStream(currentCameraFacing);
  }

  function captureLivePhoto() {
    const video = document.getElementById("camera-video-feed");
    const flash = document.getElementById("camera-flash-overlay");

    if (flash) {
      flash.classList.add("flashing");
      setTimeout(() => flash.classList.remove("flashing"), 260);
    }

    const now = new Date();
    const DAYS_ID = ["Minggu", "Senin", "Selasa", "Rabu", "Kamis", "Jumat", "Sabtu"];
    const dayStr = DAYS_ID[now.getDay()];
    const dateStr = String(now.getDate()).padStart(2, "0") + "/" + String(now.getMonth() + 1).padStart(2, "0") + "/" + now.getFullYear();
    const timeStr = String(now.getHours()).padStart(2, "0") + ":" + String(now.getMinutes()).padStart(2, "0");

    const stageTag = activeCameraSlot === "sebelum" ? "FOTO SEBELUM (0%)" : activeCameraSlot === "proses" ? "PROSES PENGERJAAN" : "FOTO SESUDAH (100%)";
    const loc = getEffectiveLocation();
    const officer = ((currentUser && currentUser.name) ? currentUser.name : "Ahmad Suryadi") + " (PPSU Pekayon)";
    const katInfo = KATEGORI_INFO[mobileSelectedKat] || { label: mobileSelectedJob };

    let photoDataUrl;
    if (video && video.videoWidth > 0 && typeof applyWatermarkToImage === "function") {
      try {
        photoDataUrl = applyWatermarkToImage(video, {
          now: now,
          time: timeStr,
          date: dateStr,
          day: dayStr,
          lat: loc.lat,
          lng: loc.lng,
          accuracy: loc.accuracy,
          alamat: loc.fullAddress,
          addressLine1: loc.line1,
          addressLine2: loc.line2,
          badge: "PPSU DKI JAKARTA • " + stageTag,
          officer: officer,
          flipHorizontal: isCameraMirrored
        });
      } catch (err) {
        console.warn("Watermark video error, fallback ke canvas:", err);
        const progressVal = activeCameraSlot === "sebelum" ? 0 : activeCameraSlot === "proses" ? 50 : 100;
        photoDataUrl = generateFieldPhoto(progressVal, katInfo.label, loc.fullAddress, loc.lat, loc.lng, officer);
      }
    } else {
      const progressVal = activeCameraSlot === "sebelum" ? 0 : activeCameraSlot === "proses" ? 50 : 100;
      photoDataUrl = generateFieldPhoto(progressVal, katInfo.label, loc.fullAddress, loc.lat, loc.lng, officer);
    }

    if (navigator.vibrate) {
      try { navigator.vibrate(50); } catch(e) {}
    }

    setMobilePhotoSlot(activeCameraSlot, photoDataUrl, timeStr);
    closeCameraModal();
    showToast("Foto " + stageTag + " bertanda air GPS berhasil diambil!", "success");
    triggerDraftAutoSave();
  }

  function pickFromGallery() {
    showToast("Unggah foto dari galeri dinonaktifkan demi integritas pelaporan. Petugas wajib mengambil foto kamera langsung di lapangan!", "warning");
  }

  function handleFileSelect(event, type) {
    const targetType = type || activeCameraSlot || "sebelum";
    const inputEl = event && event.target ? event.target : null;
    const file = inputEl && inputEl.files ? inputEl.files[0] : null;
    if (!file) return;

    // Tampilkan loading notice di viewfinder modal
    const loading = document.getElementById("camera-loading-notice");
    if (loading) {
      loading.classList.remove("hidden");
      loading.innerHTML = '<div class="cam-spinner"></div><span style="color:#FFF;font-weight:700;">Memproses foto &amp; stempel watermark GPS...</span>';
    }

    const reader = new FileReader();
    reader.onerror = function(err) {
      console.error("FileReader error:", err);
      if (loading) loading.classList.add("hidden");
      showToast("Gagal membaca file foto dari galeri!", "error");
      if (inputEl) inputEl.value = "";
    };

    reader.onload = function(e) {
      const img = new Image();
      img.onerror = function(err) {
        console.error("Image load error:", err);
        if (loading) loading.classList.add("hidden");
        showToast("Format gambar tidak didukung atau file rusak!", "error");
        if (inputEl) inputEl.value = "";
      };

      img.onload = function() {
        try {
          const now = new Date();
          const DAYS_ID = ["Minggu", "Senin", "Selasa", "Rabu", "Kamis", "Jumat", "Sabtu"];
          const dayStr = DAYS_ID[now.getDay()];
          const dateStr = String(now.getDate()).padStart(2, "0") + "/" + String(now.getMonth() + 1).padStart(2, "0") + "/" + now.getFullYear();
          const timeStr = String(now.getHours()).padStart(2, "0") + ":" + String(now.getMinutes()).padStart(2, "0");
          const stageTag = targetType === "sebelum" ? "FOTO SEBELUM (0%)" : targetType === "proses" ? "PROSES PENGERJAAN" : "FOTO SESUDAH (100%)";
          
          const loc = getEffectiveLocation();
          const officer = ((currentUser && currentUser.name) ? currentUser.name : "Ahmad Suryadi") + " (PPSU Pekayon)";

          let photoDataUrl;
          if (typeof applyWatermarkToImage === "function") {
            photoDataUrl = applyWatermarkToImage(img, {
              now: now,
              time: timeStr,
              date: dateStr,
              day: dayStr,
              lat: loc.lat,
              lng: loc.lng,
              accuracy: loc.accuracy,
              alamat: loc.fullAddress,
              addressLine1: loc.line1,
              addressLine2: loc.line2,
              badge: "PPSU DKI JAKARTA • " + stageTag,
              officer: officer,
              flipHorizontal: false
            });
          } else {
            photoDataUrl = e.target.result;
          }

          setMobilePhotoSlot(targetType, photoDataUrl, timeStr);
          closeCameraModal();
          showToast("Foto kamera bertanda air GPS berhasil disimpan!", "success");
        } catch (procErr) {
          console.error("Gagal memproses watermark foto:", procErr);
          const timeFallback = String(new Date().getHours()).padStart(2, "0") + ":" + String(new Date().getMinutes()).padStart(2, "0");
          setMobilePhotoSlot(targetType, e.target.result, timeFallback);
          closeCameraModal();
          showToast("Foto tersimpan (mode kompatibilitas)", "info");
        } finally {
          if (loading) loading.classList.add("hidden");
          if (inputEl) inputEl.value = "";
        }
      };

      img.src = e.target.result;
    };

    reader.readAsDataURL(file);
  }

  function generateSimulatedCurrentPhoto() {
    const slot = activeCameraSlot || "sebelum";
    const now = new Date();
    const ts = now.toLocaleTimeString("id-ID", { hour: "2-digit", minute: "2-digit" });
    const officer = ((currentUser && currentUser.name) ? currentUser.name : "Ahmad Suryadi") + " (PPSU Pekayon)";
    const katInfo = KATEGORI_INFO[mobileSelectedKat] || { label: mobileSelectedJob };
    const loc = getEffectiveLocation();
    const progressVal = slot === "sebelum" ? 0 : slot === "proses" ? 50 : 100;
    const photo = generateFieldPhoto(progressVal, katInfo.label, loc.fullAddress, loc.lat, loc.lng, officer);
    setMobilePhotoSlot(slot, photo, ts);
    closeCameraModal();
    showToast("Foto simulasi GPS berhasil dibuat!", "success");
    triggerDraftAutoSave();
  }

  function setMobilePhotoSlot(type, src, ts) {
    if (type === "sebelum") {
      mobilePhotoSebelum = src;
    } else if (type === "proses") {
      mobilePhotoProses = src;
      if (mobileProgres < 50) {
        mobileProgres = 50;
        const slider = document.getElementById("u-progres-range");
        if (slider) slider.value = 50;
        handleProgresSlider(50);
      }
    } else if (type === "sesudah") {
      mobilePhotoSesudah = src;
      mobileProgres = 100;
      const slider = document.getElementById("u-progres-range");
      if (slider) slider.value = 100;
      handleProgresSlider(100);
    }

    const card = document.getElementById("slot-" + type);
    const labelTitle = type === "sebelum" ? "Sebelum (0%)" : type === "proses" ? "Proses Kerja (50%)" : "Sesudah (100%)";
    if (card) {
      card.classList.add("has-photo");
      card.classList.remove("locked");
      card.onclick = () => openCameraModal(type);
      card.innerHTML = '<img src="' + src + '" class="photo-slot-img" alt="Foto ' + type + '">' +
                       '<div class="photo-slot-badge">' + labelTitle + ' • ' + ts + '</div>';
    }
    savePhotoSlotDraft(type, src, ts);
    updateSesiState();
    triggerDraftAutoSave();
  }

  // Deteksi cerdas nama lokasi aktual GPS (anti-salah lokasi: deteksi Depok vs Pekayon)
  function detectGpsLocationName(lat, lng) {
    if (!lat || !lng) return null;
    // Deteksi Area Kota Depok (Margonda Raya, Beji, Pondok Cina, dsk)
    if (lat <= -6.345 && lat >= -6.440 && lng >= 106.770 && lng <= 106.880) {
      if (lat <= -6.360 && lat >= -6.380 && lng >= 106.820 && lng <= 106.845) {
        return "Jalan Raya Margonda, Pondok Cina, Beji, Depok";
      }
      return "Jalan Raya Margonda, Beji, Kota Depok, Jawa Barat";
    }
    // Deteksi Area Pekayon / Pasar Rebo
    if (lat <= -6.320 && lat >= -6.348 && lng >= 106.850 && lng <= 106.880) {
      return "Jl. Lapan, RW 01, Kel. Pekayon, Jakarta Timur";
    }
    return null;
  }

  function updateLiveHudClock() {
    const now = new Date();
    const DAYS_ID = ["Minggu", "Senin", "Selasa", "Rabu", "Kamis", "Jumat", "Sabtu"];
    const dayStr = DAYS_ID[now.getDay()];
    const dateStr = String(now.getDate()).padStart(2, "0") + "/" + String(now.getMonth() + 1).padStart(2, "0") + "/" + now.getFullYear();
    const timeStr = String(now.getHours()).padStart(2, "0") + ":" + String(now.getMinutes()).padStart(2, "0");

    const elTime = document.getElementById("hud-time");
    const elDate = document.getElementById("hud-date");
    const elDay = document.getElementById("hud-day");
    const elAddr1 = document.getElementById("hud-addr-1");
    const elAddr2 = document.getElementById("hud-addr-2");
    const elCoords = document.getElementById("hud-coords");

    if (elTime) elTime.textContent = timeStr;
    if (elDate) elDate.textContent = dateStr;
    if (elDay) elDay.textContent = dayStr;

    // Sinkronkan alamat dan koordinat live dengan form & GPS
    const loc = getEffectiveLocation();
    if (elAddr1) elAddr1.textContent = loc.line1;
    if (elAddr2) elAddr2.textContent = loc.line2;

    if (elCoords) {
      const latStr = (loc.lat >= 0 ? "+" : "") + loc.lat.toFixed(6);
      const lngStr = (loc.lng >= 0 ? "+" : "") + loc.lng.toFixed(6);
      elCoords.textContent = `GPS: Lat ${latStr}, Long ${lngStr} (±${loc.accuracy}m)`;

      const briefGps = document.getElementById("brief-gps-coords");
      if (briefGps) {
        briefGps.textContent = "● Terhubung";
      }
    }
  }

  // ==========================================================
  // PEKAYON INTERACTIVE LEAFLET MAP & GPS ENGINE
  // ==========================================================
  function initPekayonMap() {
    if (typeof L === "undefined") {
      console.warn("Leaflet map library belum termuat dari CDN.");
      return;
    }
    const mapEl = document.getElementById("pekayon-live-map");
    if (!mapEl || pekayonMap) return;

    const initialLat = liveCoords.lat || -6.335500;
    const initialLng = liveCoords.lng || 106.864200;

    try {
      pekayonMap = L.map("pekayon-live-map", {
        center: [initialLat, initialLng],
        zoom: 16,
        zoomControl: true,
        attributionControl: false,
        dragging: !L.Browser.mobile,
        tap: false
      });

      L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
        maxZoom: 19,
        subdomains: ["a", "b", "c"]
      }).addTo(pekayonMap);

      // Pin GPS terkunci secara penuh (anti-manipulasi laporan)
      const customPinIcon = L.divIcon({
        className: "custom-pekayon-pin",
        html: '<div style="background:#C2410C;width:18px;height:18px;border-radius:50%;border:3px solid #FFFFFF;box-shadow:0 0 12px rgba(194,65,12,0.9),0 2px 6px rgba(0,0,0,0.4);position:relative;cursor:not-allowed;"><div style="position:absolute;inset:-7px;border-radius:50%;border:2.5px solid #EA580C;opacity:0.7;animation:pinRipplePulse 2s infinite ease-out;pointer-events:none;"></div></div>',
        iconSize: [24, 24],
        iconAnchor: [12, 12]
      });

      pekayonMarker = L.marker([initialLat, initialLng], {
        icon: customPinIcon,
        draggable: false,
        interactive: false
      }).addTo(pekayonMap);

      pekayonMap.on("click", function(e) {
        /* terkunci */
      });

      setTimeout(() => {
        if (pekayonMap) pekayonMap.invalidateSize();
      }, 400);
    } catch(err) {
      console.warn("Inisialisasi Leaflet gagal:", err);
    }
  }

  function getDistanceInKm(lat1, lon1, lat2, lon2) {
    const R = 6371;
    const dLat = (lat2 - lat1) * Math.PI / 180;
    const dLon = (lon2 - lon1) * Math.PI / 180;
    const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
              Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
              Math.sin(dLon/2) * Math.sin(dLon/2);
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
    return R * c;
  }

  function setMapPosition(lat, lng, accuracyText) {
    liveCoords.lat = lat;
    liveCoords.lng = lng;

    if (accuracyText && accuracyText.includes("±")) {
      const parsed = parseInt(accuracyText.replace(/[^0-9]/g, ""));
      if (!isNaN(parsed) && parsed > 0) liveCoords.accuracy = parsed;
    } else if (!liveCoords.accuracy || liveCoords.accuracy <= 1) {
      liveCoords.accuracy = 5;
    }

    const detected = detectGpsLocationName(lat, lng);
    if (detected) {
      window.liveGpsAddress = detected;
      mobileAddress = detected;
    }

    if (pekayonMap && pekayonMarker) {
      pekayonMarker.setLatLng([lat, lng]);
      pekayonMap.setView([lat, lng], pekayonMap.getZoom() || 16, { animate: true });
    }

    const latStr = (lat >= 0 ? "+" : "") + lat.toFixed(6);
    const lngStr = (lng >= 0 ? "+" : "") + lng.toFixed(6);
    const coordsTextEl = document.getElementById("u-coords-text");
    if (coordsTextEl) {
      const accDisplay = accuracyText || `Akurat (±${liveCoords.accuracy}m)`;
      coordsTextEl.textContent = `Lat ${latStr}, Long ${lngStr} (${accDisplay})`;
    }

    const mapBtn = document.getElementById("btn-view-map");
    if (mapBtn) {
      mapBtn.href = `https://www.google.com/maps?q=${lat},${lng}`;
    }

    // Perbarui HUD camera watermark
    const elCoords = document.getElementById("hud-coords");
    if (elCoords) {
      elCoords.textContent = `GPS: Lat ${latStr}, Long ${lngStr} (±${liveCoords.accuracy}m)`;
    }

    updateLocationDisplay();
  }

  function onManualCoordinateSelected(lat, lng, accuracyText) {
    const accLabel = accuracyText || `Presisi Peta (±${liveCoords.accuracy || 5}m)`;
    setMapPosition(lat, lng, accLabel);
    // CATATAN: Peta & Google Maps hanya untuk pengujian & pembacaan titik koordinat (lat/long).
    // Alamat jalan dan patokan sepenuhnya murni input manual pengguna.
    updateLiveHudClock();
    triggerDraftAutoSave();
  }

  function onRwSelectChange() {
    const rwSel = document.getElementById("u-rw-select");
    if (rwSel) {
      rwSel.style.borderColor = "";
      rwSel.style.boxShadow = "";
    }
    const rw = rwSel ? rwSel.value : "RW 01";
    if (!rw) return;
    mobileRw = rw;

    const rwPreset = PEKAYON_RW_DATA[rw] || PEKAYON_RW_DATA["RW 01"];
    if (rwPreset) {
      const addrInp = document.getElementById("u-custom-address-inp");
      const patokanInp = document.getElementById("u-patokan-inp");

      if (addrInp && !addrInp.value) {
        addrInp.placeholder = `Ketik nama jalan di area ${rw} (contoh: ${rwPreset.road})`;
      }
      if (patokanInp && !patokanInp.value) {
        patokanInp.placeholder = `Ketik patokan detail di area ${rw} (contoh: ${rwPreset.patokan})`;
      }

      setMapPosition(rwPreset.lat, rwPreset.lng, `Presisi ${rw} (Kel. Pekayon)`);
    }

    updateLocationDisplay();
    triggerDraftAutoSave();
  }

  // Reverse geocode dinamis agar teks peta, watermark foto, dan HUD kamera
  // selalu mengikuti posisi GPS nyata petugas secara real-time
  async function reverseGeocodePekayon(lat, lng) {
    // 1. Cek instan deteksi koordinat lokal (Depok / Pekayon)
    const instantLocal = detectGpsLocationName(lat, lng);
    if (instantLocal) {
      window.liveGpsAddress = instantLocal;
      mobileAddress = instantLocal;
      const disp = document.getElementById("u-display-location");
      if (disp) disp.textContent = instantLocal;
      updateLiveHudClock();
    }

    // 2. Fetch data OSM reverse geocoding via proxy internal user.php
    try {
      let resolvedRoad = "";
      let resolvedVillage = "";
      let resolvedCity = "";

      const controller = new AbortController();
      const timeoutId = setTimeout(() => controller.abort(), 4000);
      const res = await fetch(`user.php?action=geocode&lat=${lat}&lng=${lng}`, {
        signal: controller.signal
      });
      clearTimeout(timeoutId);

      if (res.ok) {
        const data = await res.json();
        if (data && data.address) {
          resolvedRoad = data.address.road || data.address.pedestrian || data.address.suburb || "";
          resolvedVillage = data.address.village || data.address.suburb || data.address.city_district || "";
          resolvedCity = data.address.city || data.address.county || data.address.state || "";
        }
      }

      // Fallback ke BigDataCloud client API jika proxy lokal gagal
      if (!resolvedCity) {
        try {
          const bdcRes = await fetch(`https://api.bigdatacloud.net/data/reverse-geocode-client?latitude=${lat}&longitude=${lng}&localityLanguage=id`);
          if (bdcRes.ok) {
            const bdcData = await bdcRes.json();
            if (bdcData) {
              resolvedVillage = bdcData.locality || "";
              resolvedCity = bdcData.city || bdcData.principalSubdivision || "";
            }
          }
        } catch(be) {}
      }

      if (resolvedRoad || resolvedVillage || resolvedCity) {
        let parts = [];
        if (resolvedRoad) parts.push(resolvedRoad);
        if (resolvedVillage) parts.push(resolvedVillage);
        if (resolvedCity) parts.push(resolvedCity);

        const fullGpsAddr = parts.join(", ");
        if (fullGpsAddr) {
          window.liveGpsAddress = fullGpsAddr;
          mobileAddress = fullGpsAddr;
          const disp = document.getElementById("u-display-location");
          if (disp) disp.textContent = fullGpsAddr;
          updateLiveHudClock();
        }
      }
    } catch(e) {
      if (instantLocal) {
        window.liveGpsAddress = instantLocal;
        mobileAddress = instantLocal;
      }
    }
  }

  async function refreshCurrentGPS() {
    const btn   = document.getElementById("btn-gps-sync");
    const label = document.getElementById("btn-gps-sync-label");
    const alertBox  = document.getElementById("gps-distance-alert");
    const alertText = document.getElementById("gps-distance-text");

    if (label) label.textContent = "Mencari...";
    if (btn) btn.disabled = true;
    showToast("Membaca sinyal GPS satelit perangkat...", "info");

    if ("geolocation" in navigator) {
      navigator.geolocation.getCurrentPosition(
        async pos => {
          const devLat = pos.coords.latitude;
          const devLng = pos.coords.longitude;
          const devAcc = Math.round(pos.coords.accuracy || 99999);

          setMapPosition(devLat, devLng, `Akurat ±${devAcc}m`);
          await reverseGeocodePekayon(devLat, devLng);

          const distToPekayon = getDistanceInKm(devLat, devLng, PEKAYON_CENTER.lat, PEKAYON_CENTER.lng);
          if (distToPekayon > 3.5) {
            if (alertBox) alertBox.style.display = "flex";
            if (alertText) alertText.textContent =
              `Perangkat Anda terdeteksi ~${distToPekayon.toFixed(1)} km dari Pekayon. Koordinat GPS dikunci sesuai posisi aktual.`;
          } else {
            if (alertBox) alertBox.style.display = "none";
            showToast(`GPS terkunci ±${devAcc}m – area Pekayon`, "success");
          }

          updateLiveHudClock();
          if (label) label.textContent = "Refresh GPS";
          if (btn) btn.disabled = false;
        },
        err => {
          console.warn("GPS error:", err.message);
          const rwSel = document.getElementById("u-rw-select");
          const curRw = rwSel && rwSel.value ? rwSel.value : (mobileRw || "RW 01");
          const rwData = PEKAYON_RW_DATA[curRw] || PEKAYON_RW_DATA["RW 01"];
          setMapPosition(rwData.lat, rwData.lng, `Presisi ${curRw} Pekayon`);
          window.liveGpsAddress = `${rwData.road}, ${curRw}, Kel. Pekayon`;
          mobileAddress = rwData.road;
          const disp = document.getElementById("u-display-location");
          if (disp) disp.textContent = window.liveGpsAddress;
          updateLiveHudClock();
          if (label) label.textContent = "Refresh GPS";
          if (btn) btn.disabled = false;
          showToast(`Menggunakan koordinat presisi ${curRw} Pekayon.`, "info");
        },
        { enableHighAccuracy: true, timeout: 8000, maximumAge: 10000 }
      );
    } else {
      showToast("Browser tidak mendukung geolokasi GPS.", "warning");
      if (label) label.textContent = "Refresh GPS";
      if (btn) btn.disabled = false;
    }
  }

  function initLiveGpsWatcher() {
    // Langsung baca GPS nyata saat halaman dibuka – peta ikut titik pengguna
    if ("geolocation" in navigator) {
      navigator.geolocation.getCurrentPosition(
        async pos => {
          const devLat = pos.coords.latitude;
          const devLng = pos.coords.longitude;
          const devAcc = Math.round(pos.coords.accuracy || 99999);
          setMapPosition(devLat, devLng, `Akurat ±${devAcc}m`);
          await reverseGeocodePekayon(devLat, devLng);
          updateLiveHudClock();
        },
        err => {
          // Fallback ke default RW 01 Pekayon jika GPS tidak tersedia
          console.warn("GPS startup:", err.message);
          const rwSel = document.getElementById("u-rw-select");
          const curRw = rwSel && rwSel.value ? rwSel.value : (mobileRw || "RW 01");
          const rwData = PEKAYON_RW_DATA[curRw] || PEKAYON_RW_DATA["RW 01"];
          setMapPosition(rwData.lat, rwData.lng, `Presisi ${curRw} Pekayon`);
          window.liveGpsAddress = `${rwData.road}, ${curRw}, Kel. Pekayon`;
          mobileAddress = rwData.road;
          const disp = document.getElementById("u-display-location");
          if (disp) disp.textContent = window.liveGpsAddress;
          updateLiveHudClock();
        },
        { enableHighAccuracy: false, timeout: 6000, maximumAge: 30000 }
      );
    } else {
      // Tanpa GPS: tampilkan pusat RW 01 Pekayon
      const rwData = PEKAYON_RW_DATA["RW 01"];
      setMapPosition(rwData.lat, rwData.lng, "Presisi RW 01 Pekayon");
      window.liveGpsAddress = `${rwData.road}, RW 01, Kel. Pekayon`;
      mobileAddress = rwData.road;
      const disp = document.getElementById("u-display-location");
      if (disp) disp.textContent = window.liveGpsAddress;
    }
  }

  function generateSimulatedPhotoSebelum() {
    const now = new Date();
    const ts = now.toLocaleTimeString("id-ID", { hour: "2-digit", minute: "2-digit" });
    const officer = (currentUser ? currentUser.name : "Budi Santoso") + " (PPSU Pekayon)";
    const katInfo = KATEGORI_INFO[mobileSelectedKat] || { label: mobileSelectedJob };

    const loc = getEffectiveLocation();
    const activeAddr = loc.fullAddress;

    const pSebelum = generateFieldPhoto(0, katInfo.label, activeAddr, liveCoords.lat, liveCoords.lng, officer);
    setMobilePhotoSlot("sebelum", pSebelum, ts);
    showToast("Foto kondisi Sebelum (0%) bertanda air GPS berhasil dibuat!", "success");
    triggerDraftAutoSave();
  }

  function generateSimulatedProsesSesudah() {
    if (!mobilePhotoSebelum) {
      showToast("Ambil Foto 1 (Sebelum 0%) terlebih dahulu!", "warning");
      return;
    }
    const now = new Date();
    const ts = now.toLocaleTimeString("id-ID", { hour: "2-digit", minute: "2-digit" });
    const officer = (currentUser ? currentUser.name : "Budi Santoso") + " (PPSU Pekayon)";
    const katInfo = KATEGORI_INFO[mobileSelectedKat] || { label: mobileSelectedJob };

    const loc = getEffectiveLocation();
    const activeAddr = loc.fullAddress;

    const pProses = generateFieldPhoto(50, katInfo.label, activeAddr, liveCoords.lat, liveCoords.lng, officer);
    const pSesudah = generateFieldPhoto(100, katInfo.label, activeAddr, liveCoords.lat, liveCoords.lng, officer);

    setMobilePhotoSlot("proses", pProses, ts);
    setMobilePhotoSlot("sesudah", pSesudah, ts);
    showToast("Foto Proses (50%) & Sesudah (100%) bertanda air GPS berhasil dibuat!", "success");
    triggerDraftAutoSave();
  }

  function generateSimulated3Photos() {
    generateSimulatedPhotoSebelum();
    generateSimulatedProsesSesudah();
  }

  // ==========================================================
  // 5. POP-UP KONFIRMASI PENGIRIMAN & SUBMIT BERTAHAP
  // Logika Progressive:
  // - Foto 0% -> Kirim untuk membuka 50%
  // - Foto 50% -> Kirim untuk membuka 100%
  // - Foto 100% -> Baru muncul peringatan konfirmasi sebelum kirim final
  // ==========================================================
  function requestSubmitConfirmation() {
    // Validasi Langkah 1
    if (!validateStep1()) {
      goToStep(1);
      return;
    }

    const list = getLaporanDB();
    const activeLap = activeEditingReportId ? findActiveReport(list, activeEditingReportId) : null;
    const hasSubmitted0 = !!(activeEditingReportId && activeLap && activeLap.foto && activeLap.foto.length >= 1);
    const hasSubmitted50 = !!(activeEditingReportId && activeLap && (parseInt(activeLap.progres, 10) >= 50 || activeLap.status === "proses" || activeLap.status === "selesai") && activeLap.foto && activeLap.foto.length >= 2);

    // 1. TAHAP AWAL (0%): Belum kirim 0% -> Kirim Laporan Awal
    if (!hasSubmitted0) {
      if (!mobilePhotoSebelum) {
        showToast("Wajib mengambil Foto 1 (Sebelum 0%) terlebih dahulu sebelum mengirim laporan awal!", "warning");
        const c1 = document.getElementById("slot-sebelum");
        if (c1) c1.scrollIntoView({ behavior: "smooth", block: "center" });
        return;
      }
      submitMobileLaporan();
      return;
    }

    // 2. TAHAP PROGRES (50%): Sudah kirim 0%, belum kirim 50% -> Kirim Progres Lapangan
    if (!hasSubmitted50) {
      if (!mobilePhotoProses) {
        showToast("Wajib mengambil Foto 2 (Proses Kerja 50%) terlebih dahulu sebelum mengirim progres lapangan!", "warning");
        const c2 = document.getElementById("slot-proses");
        if (c2) c2.scrollIntoView({ behavior: "smooth", block: "center" });
        return;
      }
      submitMobileLaporan();
      return;
    }

    // 3. TAHAP TUNTAS (100%): Sudah kirim 50% -> Munculkan peringatan konfirmasi sebelum kirim final ke Admin
    if (!mobilePhotoSesudah) {
      showToast("Wajib mengambil Foto 3 (Sesudah 100%) terlebih dahulu untuk menyelesaikan laporan!", "warning");
      const c3 = document.getElementById("slot-sesudah");
      if (c3) c3.scrollIntoView({ behavior: "smooth", block: "center" });
      return;
    }

    const modal = document.getElementById("modal-confirm-submit");
    if (!modal) {
      submitMobileLaporan();
      return;
    }

    // Ambil data terkini untuk rangkuman modal
    const tugasTxt = `${mobileSelectedTugas || 'Tugas 1'} - ${mobileSelectedJob}`;
    const now = new Date();
    const tglTxt = `${mobileSelectedHari}, ${now.getDate()} ${ID_MONTHS[now.getMonth()]} ${now.getFullYear()}`;
    const reguTxt = `${mobileRegu} • ${mobileOfficerRole}`;
    const alamatVal = (document.getElementById("u-custom-address-inp") ? document.getElementById("u-custom-address-inp").value.trim() : "") || mobileAddress;
    const patokanVal = (document.getElementById("u-patokan-inp") ? document.getElementById("u-patokan-inp").value.trim() : "") || "-";
    const presentCount = (document.getElementById("u-jml-petugas-inp") ? document.getElementById("u-jml-petugas-inp").value : "4");
    const presentList = teamMembers.filter(m => m.present).map(m => m.name).join(", ");

    const sEl = document.getElementById("modal-recap-stage");
    if (sEl) sEl.textContent = "Penuntasan Pekerjaan (100% Selesai - 3 Foto Lengkap)";

    const tEl = document.getElementById("modal-recap-tugas");
    if (tEl) tEl.textContent = tugasTxt;

    const jEl = document.getElementById("modal-recap-jadwal");
    if (jEl) jEl.textContent = tglTxt;

    const rEl = document.getElementById("modal-recap-regu");
    if (rEl) rEl.textContent = reguTxt;

    const lEl = document.getElementById("modal-recap-lokasi");
    if (lEl) lEl.textContent = `${mobileRw} - ${alamatVal}`;

    const pEl = document.getElementById("modal-recap-patokan");
    if (pEl) pEl.textContent = patokanVal;

    const persEl = document.getElementById("modal-recap-personel");
    if (persEl) persEl.textContent = `${presentCount} Orang Hadir (${presentList || 'Petugas Regu'})`;

    const chk = document.getElementById("chk-confirm-agree");
    if (chk) chk.checked = true;

    // Tampilkan modal peringatan konfirmasi
    modal.style.display = "flex";
  }

  function closeConfirmModal() {
    const modal = document.getElementById("modal-confirm-submit");
    if (modal) modal.style.display = "none";
  }

  function executeSubmitConfirmed() {
    const chk = document.getElementById("chk-confirm-agree");
    if (chk && !chk.checked) {
      showToast("Harap centang konfirmasi bahwa seluruh data dan 3 foto sudah sesuai!", "warning");
      return;
    }
    closeConfirmModal();
    submitMobileLaporan();
  }

  // ==========================================================
  // SUBMIT LAPORAN HARIAN (1 SESI AWAL 0% -> SESI LANJUTAN SELESAI)
  // ==========================================================
  async function submitMobileLaporan() {
    // Validasi Langkah 1
    if (!validateStep1()) {
      goToStep(1);
      return;
    }

    const list = getLaporanDB();
    const activeLap = activeEditingReportId ? findActiveReport(list, activeEditingReportId) : null;
    const { hasSubmitted0, hasSubmitted50 } = getStageStatus(activeLap);

    const noHp = document.getElementById("u-nohp-inp") ? document.getElementById("u-nohp-inp").value.trim() : "081234567890";
    const jmlPetugas = document.getElementById("u-jml-petugas-inp") ? parseInt(document.getElementById("u-jml-petugas-inp").value, 10) || 4 : 4;
    const patokanVal = document.getElementById("u-patokan-inp") ? document.getElementById("u-patokan-inp").value.trim() : "";
    const targetMeterVal = (document.getElementById("u-target-meter-inp") && document.getElementById("u-target-meter-inp").value.trim()) ? document.getElementById("u-target-meter-inp").value.trim() : "";
    const catatan = document.getElementById("u-catatan-inp") ? document.getElementById("u-catatan-inp").value.trim() : "";

    const now = new Date();
    const tgl = now.toLocaleDateString("id-ID", { day: "numeric", month: "short", year: "numeric" }) + ", " + String(now.getHours()).padStart(2, "0") + ":" + String(now.getMinutes()).padStart(2, "0");
    const officer = (document.getElementById("u-petugas-inp") && document.getElementById("u-petugas-inp").value.trim()) ? document.getElementById("u-petugas-inp").value.trim() : (currentUser ? currentUser.name : "Budi Santoso");
    const periodeHarian = `Laporan ${mobileSelectedHari} (${tgl.split(",")[0]}) • ${mobileSelectedTugas || 'Tugas 1'}`;

    const loc = getEffectiveLocation();
    const activeAddr = loc.fullAddress;

    // -------------------------------------------------------------
    // TAHAP 1: KIRIM LAPORAN AWAL (0%) -> MEMBUKA TAHAP 50%
    // -------------------------------------------------------------
    if (!hasSubmitted0) {
      if (!mobilePhotoSebelum) {
        showToast("Wajib mengambil Foto 1 (Sebelum 0%) terlebih dahulu sebelum mengirim!", "warning");
        const c1 = document.getElementById("slot-sebelum");
        if (c1) c1.scrollIntoView({ behavior: "smooth", block: "center" });
        return;
      }
      const reportId = generateNextJobNumber();
      const presentNames = teamMembers.filter(m => m.present).map(m => m.name).join(", ");
      
      const fotoList = [
        { src: mobilePhotoSebelum, timestamp: tgl, name: "sebelum_" + reportId + ".jpg", tahap: "Sebelum (0%)" }
      ];

      const reportData = {
        id: reportId,
        noPekerjaan: reportId,
        minggu: periodeHarian,
        rw: mobileRw,
        kat: mobileSelectedKat,
        tugasKe: mobileSelectedTugas || "Tugas 1",
        jenisPekerjaan: mobileSelectedJob,
        judul: (mobileSelectedTugas || "Tugas 1") + ": " + mobileSelectedJob + " - " + activeAddr,
        targetMeter: targetMeterVal,
        deskripsi: catatan || ("Penanganan " + mobileSelectedJob.toLowerCase() + " di " + activeAddr + "."),
        alamat: activeAddr,
        lokasiDetail: patokanVal || ("Lokasi pengerjaan di area " + mobileRw),
        kendala: catatan || "Pekerjaan awal dimulai di lapangan.",
        petugas: officer,
        regu: mobileRegu || (document.getElementById("u-regu-inp") ? document.getElementById("u-regu-inp").value.trim() : "Regu 1"),
        role: mobileOfficerRole,
        noHp: noHp,
        jumlahPetugas: jmlPetugas,
        anggotaHadir: presentNames,
        wilayah: currentUser ? currentUser.wilayah : "jaktim",
        kelurahan: "Kel. Pekayon",
        status: "proses",
        progres: 0,
        tanggal: tgl,
        isoDate: currentCalendarDateStr || (now.toISOString().split("T")[0]),
        gpsLat: loc.lat,
        gpsLng: loc.lng,
        foto: fotoList
      };

      list.unshift(reportData);
      await saveLaporanDB(list);
      userReports = list;

      localStorage.setItem("silap_active_task_id", reportId);
      activeEditingReportId = reportId;
      mobileProgres = 50;

      showToast("Laporan Awal (0%) berhasil dikirim! Tahap 50% sekarang terbuka. Silakan ambil Foto Proses Kerja!", "success");
      updateTaskProgressStates();
      renderUserHistory();

      // Langsung bertahan di Langkah 2 (Formulir Progres & Foto)
      switchUserTab("form");
      goToStep(2);
      updateSesiState(reportData);
      setTimeout(() => {
        const c2 = document.getElementById("slot-proses");
        if (c2) c2.scrollIntoView({ behavior: "smooth", block: "center" });
      }, 150);
      return;
    }

    // -------------------------------------------------------------
    // TAHAP 2: KIRIM PROGRES LAPANGAN (50%) -> MEMBUKA TAHAP 100%
    // -------------------------------------------------------------
    if (!hasSubmitted50) {
      if (!mobilePhotoProses) {
        showToast("Wajib mengambil Foto 2 (Proses Kerja 50%) terlebih dahulu sebelum mengirim!", "warning");
        const c2 = document.getElementById("slot-proses");
        if (c2) c2.scrollIntoView({ behavior: "smooth", block: "center" });
        return;
      }
      let existingIndex = -1;
      if (activeEditingReportId) {
        existingIndex = list.findIndex(r => String(r.id) === String(activeEditingReportId) || String(r.noPekerjaan) === String(activeEditingReportId));
      }
      if (existingIndex === -1) {
        existingIndex = list.findIndex(r => r.tugasKe === (mobileSelectedTugas || "Tugas 1") && r.status !== 'selesai');
      }

      const reportId = existingIndex !== -1 ? (list[existingIndex].id || list[existingIndex].noPekerjaan) : generateNextJobNumber();
      const oldLap = existingIndex !== -1 ? list[existingIndex] : {};
      const presentNames = teamMembers.filter(m => m.present).map(m => m.name).join(", ");

      const fotoList = [
        (oldLap.foto && oldLap.foto[0] && oldLap.foto[0].src) ? oldLap.foto[0] : { src: mobilePhotoSebelum, timestamp: tgl, name: "sebelum_" + reportId + ".jpg", tahap: "Sebelum (0%)" },
        { src: mobilePhotoProses, timestamp: tgl, name: "proses_" + reportId + ".jpg", tahap: "Proses (50%)" }
      ];

      const updatedReport = {
        ...oldLap,
        id: oldLap.id || reportId,
        noPekerjaan: oldLap.noPekerjaan || reportId,
        minggu: oldLap.minggu || periodeHarian,
        rw: mobileRw || oldLap.rw || "RW 01",
        kat: mobileSelectedKat || oldLap.kat || "kebersihan",
        tugasKe: mobileSelectedTugas || oldLap.tugasKe || "Tugas 1",
        jenisPekerjaan: mobileSelectedJob || oldLap.jenisPekerjaan,
        judul: (mobileSelectedTugas || oldLap.tugasKe || "Tugas 1") + ": " + (mobileSelectedJob || oldLap.jenisPekerjaan) + " - " + activeAddr,
        targetMeter: targetMeterVal || oldLap.targetMeter || oldLap.target_meter || "",
        deskripsi: catatan || oldLap.deskripsi || ("Penanganan " + (mobileSelectedJob || "").toLowerCase() + " di " + activeAddr + "."),
        alamat: activeAddr,
        lokasiDetail: patokanVal || oldLap.lokasiDetail || ("Lokasi pengerjaan di area " + mobileRw),
        petugas: officer,
        regu: mobileRegu || oldLap.regu || "Regu 1",
        role: mobileOfficerRole,
        noHp: noHp,
        jumlahPetugas: jmlPetugas,
        anggotaHadir: presentNames || oldLap.anggotaHadir,
        wilayah: currentUser ? currentUser.wilayah : "jaktim",
        kelurahan: "Kel. Pekayon",
        status: "proses",
        progres: 50,
        tanggal: tgl,
        isoDate: currentCalendarDateStr || (now.toISOString().split("T")[0]),
        gpsLat: loc.lat,
        gpsLng: loc.lng,
        kendala: catatan || oldLap.kendala || "Pekerjaan sedang berlangsung di lapangan.",
        foto: fotoList
      };

      if (existingIndex !== -1) {
        list[existingIndex] = updatedReport;
      } else {
        list.unshift(updatedReport);
      }
      await saveLaporanDB(list);
      userReports = list;

      activeEditingReportId = updatedReport.id || updatedReport.noPekerjaan;
      localStorage.setItem("silap_active_task_id", activeEditingReportId);
      mobileProgres = 100;

      showToast("Progres Lapangan (50%) berhasil dikirim! Tahap 100% sekarang terbuka. Silakan ambil Foto Sesudah!", "success");
      updateTaskProgressStates();
      renderUserHistory();

      // Langsung bertahan di Langkah 2
      switchUserTab("form");
      goToStep(2);
      updateSesiState(updatedReport);
      setTimeout(() => {
        const c3 = document.getElementById("slot-sesudah");
        if (c3) c3.scrollIntoView({ behavior: "smooth", block: "center" });
      }, 150);
      return;
    }

    // -------------------------------------------------------------
    // TAHAP 3: KIRIM LAPORAN TUNTAS (100% SELESAI)
    // -------------------------------------------------------------
    {
      if (!mobilePhotoSesudah) {
        showToast("Wajib mengambil Foto 3 (Sesudah 100%) terlebih dahulu untuk menyelesaikan laporan!", "warning");
        const c3 = document.getElementById("slot-sesudah");
        if (c3) c3.scrollIntoView({ behavior: "smooth", block: "center" });
        return;
      }
      let existingIndex = -1;
      if (activeEditingReportId) {
        existingIndex = list.findIndex(r => String(r.id) === String(activeEditingReportId) || String(r.noPekerjaan) === String(activeEditingReportId));
      }
      if (existingIndex === -1) {
        existingIndex = list.findIndex(r => r.tugasKe === (mobileSelectedTugas || "Tugas 1") && r.status !== 'selesai');
      }

      const reportId = existingIndex !== -1 ? (list[existingIndex].id || list[existingIndex].noPekerjaan) : generateNextJobNumber();
      const oldLap = existingIndex !== -1 ? list[existingIndex] : {};
      const presentNames = teamMembers.filter(m => m.present).map(m => m.name).join(", ");

      const fotoList = [
        (oldLap.foto && oldLap.foto[0] && oldLap.foto[0].src) ? oldLap.foto[0] : { src: mobilePhotoSebelum, timestamp: tgl, name: "sebelum_" + reportId + ".jpg", tahap: "Sebelum (0%)" },
        (oldLap.foto && oldLap.foto[1] && oldLap.foto[1].src) ? oldLap.foto[1] : { src: mobilePhotoProses, timestamp: tgl, name: "proses_" + reportId + ".jpg", tahap: "Proses (50%)" },
        { src: mobilePhotoSesudah, timestamp: tgl, name: "sesudah_" + reportId + ".jpg", tahap: "Sesudah (100%)" }
      ];

      const updatedReport = {
        ...oldLap,
        id: oldLap.id || reportId,
        noPekerjaan: oldLap.noPekerjaan || reportId,
        minggu: oldLap.minggu || periodeHarian,
        rw: mobileRw || oldLap.rw || "RW 01",
        kat: mobileSelectedKat || oldLap.kat || "kebersihan",
        tugasKe: mobileSelectedTugas || oldLap.tugasKe || "Tugas 1",
        jenisPekerjaan: mobileSelectedJob || oldLap.jenisPekerjaan,
        judul: (mobileSelectedTugas || oldLap.tugasKe || "Tugas 1") + ": " + (mobileSelectedJob || oldLap.jenisPekerjaan) + " - " + activeAddr,
        targetMeter: targetMeterVal || oldLap.targetMeter || oldLap.target_meter || "",
        deskripsi: catatan || oldLap.deskripsi || ("Penanganan " + (mobileSelectedJob || "").toLowerCase() + " di " + activeAddr + "."),
        alamat: activeAddr,
        lokasiDetail: patokanVal || oldLap.lokasiDetail || ("Lokasi pengerjaan di area " + mobileRw),
        petugas: officer,
        regu: mobileRegu || oldLap.regu || "Regu 1",
        role: mobileOfficerRole,
        noHp: noHp,
        jumlahPetugas: jmlPetugas,
        anggotaHadir: presentNames || oldLap.anggotaHadir,
        wilayah: currentUser ? currentUser.wilayah : "jaktim",
        kelurahan: "Kel. Pekayon",
        status: "belum",
        progres: 100,
        tanggal: tgl,
        isoDate: currentCalendarDateStr || (now.toISOString().split("T")[0]),
        gpsLat: loc.lat,
        gpsLng: loc.lng,
        kendala: catatan || oldLap.kendala || "Pekerjaan tuntas diselesaikan dengan hasil bersih dan rapi.",
        foto: fotoList
      };

      if (existingIndex !== -1) {
        list[existingIndex] = updatedReport;
      } else {
        list.unshift(updatedReport);
      }
      await saveLaporanDB(list);
      userReports = list;

      localStorage.removeItem("silap_active_task_id");
      activeEditingReportId = null;
      mobileProgres = 0;

      showToast("Laporan #" + (updatedReport.noPekerjaan || reportId) + " (" + (updatedReport.tugasKe || "Tugas 1") + ") berhasil dikirim (100%) & masuk antrean Verifikasi Admin!", "success");
      clearFormDraft();
      resetFormFields();
      updateTaskProgressStates();
      renderUserHistory();
      goToStep(1);
      switchUserTab("history");
    }
  }

  // ==========================================================
  // 6. RIWAYAT LAPORAN DIPISAHKAN PER-REGU & PERIODE WAKTU
  // ==========================================================
  let histFilterRegu = "SEMUA";
  let histFilterPeriod = "all";

  function onHistReguFilterChanged(val) {
    histFilterRegu = val;
    renderUserHistory();
  }

  function setHistoryPeriodFilter(period) {
    histFilterPeriod = period;
    document.querySelectorAll(".time-chip-btn").forEach(btn => {
      if (btn.dataset.period === period) {
        btn.classList.add("active");
      } else {
        btn.classList.remove("active");
      }
    });

    const periodLabelMap = {
      "all": "Export Excel",
      "today": "Export Excel (Hari Ini)",
      "yesterday": "Export Excel (Kemarin)",
      "2days": "Export Excel (2 Hari)",
      "3days": "Export Excel (3 Hari)",
      "7days": "Export Excel (7 Hari)",
      "14days": "Export Excel (14 Hari)",
      "30days": "Export Excel (30 Hari)"
    };
    const btnLabel = document.getElementById("btn-user-excel-label");
    if (btnLabel) btnLabel.textContent = periodLabelMap[period] || "Export Excel";

    renderUserHistory();
  }

  async function downloadUserLaporanExcel() {
    let list = getLaporanDB();
    if (!list || list.length === 0) {
      showToast("Belum ada data laporan untuk diexport.", "warning");
      return;
    }

    if (typeof ExcelJS === "undefined") {
      showToast("Modul ExcelJS sedang dimuat, mohon coba sesaat lagi...", "warning");
      return;
    }

    // Filter per-regu
    const activeReguFilter = histFilterRegu || "SEMUA";
    if (activeReguFilter !== "SEMUA") {
      list = list.filter(lap => (lap.regu || "Regu 1").toLowerCase() === activeReguFilter.toLowerCase());
    }

    // Filter per-periode
    const activePeriod = histFilterPeriod || "all";
    if (activePeriod !== "all" && typeof filterReportsByPeriod === "function") {
      list = filterReportsByPeriod(list, activePeriod);
    }

    if (!list || list.length === 0) {
      showToast("Tidak ada laporan pada periode yang dipilih untuk diexport.", "warning");
      return;
    }

    const periodNames = {
      "all": "Semua Periode",
      "today": "Hari Ini",
      "yesterday": "Kemarin",
      "2days": "2 Hari Lalu",
      "3days": "3 Hari Lalu",
      "7days": "7 Hari Terakhir",
      "14days": "14 Hari Terakhir",
      "30days": "30 Hari Terakhir"
    };

    showToast("Sedang menyusun berkas Excel (" + (periodNames[activePeriod] || "Periode Aktif") + ")...", "info");

    try {
      const wb = new ExcelJS.Workbook();
      wb.creator = "SILAP Mobile Kelurahan Pekayon";
      wb.lastModifiedBy = currentUser ? (currentUser.name || currentUser.nama) : "Petugas PPSU";
      wb.created = new Date();

      const ws = wb.addWorksheet("Riwayat PPSU", {
        views: [{ state: "frozen", ySplit: 4, xSplit: 0 }],
        pageSetup: { orientation: "landscape", fitToWidth: 1, fitToHeight: 0 }
      });

      // Row 1: Title
      ws.mergeCells("A1:M1");
      const t1 = ws.getCell("A1");
      t1.value = "REKAP LAPORAN PENANGANAN PPSU – KELURAHAN PEKAYON";
      t1.font = { name: "Arial", size: 13, bold: true, color: { argb: "FFEA580C" } };
      t1.alignment = { vertical: "middle", horizontal: "left" };
      ws.getRow(1).height = 24;

      // Row 2: Subtitle
      ws.mergeCells("A2:M2");
      const t2 = ws.getCell("A2");
      t2.value = "Periode: " + (periodNames[activePeriod] || "Semua") + " | Regu: " + (activeReguFilter === "SEMUA" ? "Semua Regu PPSU" : activeReguFilter) + " | Diunduh: " + new Date().toLocaleString("id-ID");
      t2.font = { name: "Arial", size: 9.5, italic: true, color: { argb: "FF64748B" } };
      t2.alignment = { vertical: "middle", horizontal: "left" };
      ws.getRow(2).height = 18;
      ws.getRow(3).height = 8;

      // Row 4: Headers
      const headers = [
        "Periode", "RW", "Kategori", "Jenis Pekerjaan",
        "Judul Laporan", "Target Penyelesaian (m)", "Petugas", "Regu", "No HP", "Jml Anggota",
        "Alamat Lokasi", "Catatan Kendala", "Waktu Terdata"
      ];
      const colWidths = [30, 9, 15, 28, 38, 22, 25, 14, 16, 12, 35, 35, 20];

      const headerRow = ws.getRow(4);
      headerRow.height = 30;
      headerRow.values = headers;

      headers.forEach((h, i) => {
        const colIdx = i + 1;
        ws.getColumn(colIdx).width = colWidths[i] || 18;
        const cell = ws.getCell(4, colIdx);
        cell.font = { name: "Arial", size: 10.5, bold: true, color: { argb: "FFFFFFFF" } };
        cell.fill = { type: "pattern", pattern: "solid", fgColor: { argb: "FFEA580C" } };
        cell.alignment = { vertical: "middle", horizontal: "center", wrapText: true };
        cell.border = {
          top: { style: "thin", color: { argb: "FFFDBA74" } },
          bottom: { style: "medium", color: { argb: "FFC2410C" } },
          left: { style: "thin", color: { argb: "FFFDBA74" } },
          right: { style: "thin", color: { argb: "FFFDBA74" } }
        };
      });

      // Data Rows
      list.forEach((r, idx) => {
        const rowNum = 5 + idx;
        const wsRow = ws.getRow(rowNum);
        wsRow.height = 28;
        const isEven = idx % 2 === 1;
        const bgArgb = isEven ? "FFFFF7ED" : "FFFFFFFF";
        const katLabel = (typeof KATEGORI_INFO !== 'undefined' && KATEGORI_INFO[r.kat] && KATEGORI_INFO[r.kat].label) ? KATEGORI_INFO[r.kat].label : (r.kat || "Prasarana");
        const rawTarget = r.targetMeter || r.target_meter || r.panjangTarget;
        const targetMtr = rawTarget ? (String(rawTarget).includes("m") ? String(rawTarget) : `${rawTarget} m`) : "-";

        wsRow.values = [
          r.minggu || "-",
          r.rw || "RW 01",
          katLabel,
          r.jenisPekerjaan || r.judul || "-",
          r.judul || r.jenisPekerjaan || "-",
          targetMtr,
          r.petugas || "-",
          r.regu || "-",
          r.noHp || "-",
          r.jumlahPetugas || 4,
          r.alamat || "-",
          r.kendala || r.deskripsi || "Tidak ada kendala.",
          r.tanggal || "-"
        ];

        for (let c = 1; c <= headers.length; c++) {
          const cell = ws.getCell(rowNum, c);
          cell.font = { name: "Arial", size: 9.5, color: { argb: "FF0F172A" } };
          cell.fill = { type: "pattern", pattern: "solid", fgColor: { argb: bgArgb } };
          cell.border = {
            top: { style: "thin", color: { argb: "FFFED7AA" } },
            bottom: { style: "thin", color: { argb: "FFFED7AA" } },
            left: { style: "thin", color: { argb: "FFFED7AA" } },
            right: { style: "thin", color: { argb: "FFFED7AA" } }
          };
          const isCenter = [2, 6, 8, 9, 10, 13].includes(c);
          cell.alignment = { vertical: "middle", horizontal: isCenter ? "center" : "left", wrapText: true };
        }
      });

      const buffer = await wb.xlsx.writeBuffer();
      const blob = new Blob([buffer], { type: "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" });
      const url = URL.createObjectURL(blob);
      const link = document.createElement("a");
      const today = new Date().toISOString().slice(0, 10);
      const periodTagMap = {
        "all": "Semua",
        "today": "Hari_Ini_" + today,
        "yesterday": "Kemarin",
        "2days": "2_Hari_Lalu",
        "3days": "3_Hari_Lalu",
        "7days": "7_Hari",
        "14days": "14_Hari",
        "30days": "30_Hari"
      };
      const periodTag = periodTagMap[activePeriod] || activePeriod;
      link.setAttribute("href", url);
      link.setAttribute("download", `Rekap_PPSU_Mobile_${periodTag}.xlsx`);
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
      URL.revokeObjectURL(url);

      showToast("Berhasil! Berkas Excel (" + (periodNames[activePeriod] || "Periode Aktif") + ") telah diunduh.", "success");
    } catch(err) {
      console.error("downloadUserLaporanExcel error:", err);
      showToast("Gagal membuat file Excel: " + (err.message || err), "error");
    }
  }

  function parseReportDate(lap) {
    if (!lap) return new Date();
    if (lap.isoDate) {
      const p = lap.isoDate.split("-");
      if (p.length === 3) return new Date(parseInt(p[0], 10), parseInt(p[1], 10) - 1, parseInt(p[2], 10));
    }
    if (lap.tanggal) {
      const indoMo = {
        "jan": 0, "januari": 0,
        "feb": 1, "februari": 1,
        "mar": 2, "maret": 2,
        "apr": 3, "april": 3,
        "mei": 4,
        "jun": 5, "juni": 5,
        "jul": 6, "juli": 6,
        "agu": 7, "agustus": 7, "ags": 7,
        "sep": 8, "september": 8,
        "okt": 9, "oktober": 9,
        "nov": 10, "november": 10,
        "des": 11, "desember": 11
      };
      const m = lap.tanggal.match(/(\d{1,2})\s+([a-zA-Z]+)\s+(\d{4})/);
      if (m) {
        const d = parseInt(m[1], 10);
        const mo = indoMo[m[2].toLowerCase()] !== undefined ? indoMo[m[2].toLowerCase()] : 9;
        const y = parseInt(m[3], 10);
        return new Date(y, mo, d);
      }
    }
    return new Date();
  }

  function getDaysDifference(targetDate) {
    const now = new Date();
    const d1 = new Date(now.getFullYear(), now.getMonth(), now.getDate());
    const d2 = new Date(targetDate.getFullYear(), targetDate.getMonth(), targetDate.getDate());
    const diffTime = d1 - d2;
    return Math.floor(diffTime / (1000 * 60 * 60 * 24));
  }

  function renderUserHistory() {
    const listEl = document.getElementById("user-history-list");
    const countEl = document.getElementById("user-hist-count");
    const badgeFiltered = document.getElementById("hist-filtered-count-badge");
    if (!listEl) return;

    userReports = getLaporanDB();
    const isAdminUser = currentUser && (currentUser.role === 'admin' || currentUser.is_admin);
    const currentName = (currentUser && (currentUser.name || currentUser.nama) ? (currentUser.name || currentUser.nama) : "").trim().toLowerCase();
    const hasAssignedRegu = mobileRegu && mobileRegu !== "Belum Ditugaskan" && mobileRegu.trim() !== "";

    if (countEl) {
      if (isAdminUser) {
        countEl.textContent = userReports.length;
      } else if (hasAssignedRegu) {
        countEl.textContent = userReports.filter(l => (l.regu || "").toLowerCase() === mobileRegu.toLowerCase()).length;
      } else {
        countEl.textContent = currentName ? userReports.filter(l => (l.petugas || "").trim().toLowerCase() === currentName).length : 0;
      }
    }

    // Filter 1: Per-Regu (agar laporan tidak tercampur dengan regu lain)
    const activeReguFilter = histFilterRegu || (isAdminUser ? "SEMUA" : (hasAssignedRegu ? mobileRegu : "__UNASSIGNED__"));
    let filtered = userReports.filter(lap => {
      if (activeReguFilter === "__UNASSIGNED__") {
        return currentName !== "" && (lap.petugas || "").trim().toLowerCase() === currentName;
      }
      if (activeReguFilter === "SEMUA") return true;
      return (lap.regu || "").toLowerCase() === activeReguFilter.toLowerCase();
    });

    // Filter 2: Periode Waktu (Hari ini, kemarin, 2 hari, 3 hari, 7 hari, 14 hari, 30 hari)
    filtered = filtered.filter(lap => {
      const repDate = parseReportDate(lap);
      const diffDays = getDaysDifference(repDate);
      lap._diffDays = diffDays;

      if (histFilterPeriod === "all") return true;
      if (histFilterPeriod === "today") return diffDays === 0;
      if (histFilterPeriod === "yesterday") return diffDays === 1;
      if (histFilterPeriod === "2days") return diffDays === 2;
      if (histFilterPeriod === "3days") return diffDays === 3;
      if (histFilterPeriod === "7days") return diffDays >= 0 && diffDays <= 7;
      if (histFilterPeriod === "14days") return diffDays >= 0 && diffDays <= 14;
      if (histFilterPeriod === "30days") return diffDays >= 0 && diffDays <= 30;
      return true;
    });

    if (badgeFiltered) {
      badgeFiltered.textContent = `${filtered.length} Laporan`;
    }

    if (!filtered.length) {
      listEl.innerHTML = `
        <div style="text-align:center;padding:2.5rem 1rem;background:#FFFFFF;border-radius:12px;border:1px dashed #CBD5E1;color:#64748B;">
          <div style="margin-bottom:8px;"><svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color:#94A3B8;"><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"></path><rect x="8" y="2" width="8" height="4" rx="1" ry="1"></rect></svg></div>
          <div style="font-weight:700;color:#334155;">Tidak Ada Laporan untuk Filter Ini</div>
          <div style="font-size:0.75rem;margin-top:3px;">Belum ada riwayat ${activeReguFilter !== 'SEMUA' ? activeReguFilter : 'seluruh regu'} pada periode yang dipilih.</div>
        </div>
      `;
      return;
    }

    listEl.innerHTML = filtered.map(lap => {
      const katMap = (typeof KATEGORI_INFO !== 'undefined' ? KATEGORI_INFO : (window.KATEGORI_INFO || {}));
      const ki = katMap[lap.kat] || { label: "Prasarana", color: "orange" };
      const badgeCls = lap.status === "selesai" ? "badge-selesai" : lap.status === "proses" ? "badge-proses" : "badge-belum";
      const badgeTxt = lap.status === "selesai" ? "Selesai (100%)" : lap.status === "proses" ? "Dalam Proses (" + (lap.progres || 50) + "%)" : "Mulai (0%)";

      // Relative Time Badge
      const diffDays = lap._diffDays !== undefined ? lap._diffDays : getDaysDifference(parseReportDate(lap));
      let relativeBadgeHtml = "";
      if (diffDays === 0) {
        relativeBadgeHtml = '<span class="badge-time-chip today">Hari Ini</span>';
      } else if (diffDays === 1) {
        relativeBadgeHtml = '<span class="badge-time-chip yesterday">Kemarin</span>';
      } else if (diffDays === 2) {
        relativeBadgeHtml = '<span class="badge-time-chip past">2 Hari Lalu</span>';
      } else if (diffDays === 3) {
        relativeBadgeHtml = '<span class="badge-time-chip past">3 Hari Lalu</span>';
      } else if (diffDays > 3) {
        relativeBadgeHtml = `<span class="badge-time-chip past">${diffDays} Hari Lalu</span>`;
      } else {
        relativeBadgeHtml = '<span class="badge-time-chip today">Hari Ini</span>';
      }

      const photoList = (lap.foto || []).filter(f => f && f.src && typeof f.src === "string" && f.src.trim() !== "" && f.src !== "null" && f.src !== "undefined");
      const photoCount = Math.min(3, Math.max(1, photoList.length));
      const gridTemplate = photoCount === 1 ? 'repeat(auto-fit, minmax(110px, 140px))' : `repeat(${photoCount}, 1fr)`;
      const justifyGrid = photoCount === 1 ? 'justify-content:center;' : '';

      const photosHtml = photoList.length > 0 ? photoList.map((f, pIdx) => {
        const validSrc = f.src;
        const cleanLabel = (f.tahap || f.name || 'Dokumentasi').replace('Proses (0%)', 'Proses (50%)');
        return `
          <div class="riwayat-photo-item">
            <img src="${validSrc}" class="uh-thumb-grid" alt="Foto Dokumentasi" onerror="this.onerror=null;this.src='foto/silap.png'">
            <div class="riwayat-photo-label">${cleanLabel}</div>
          </div>
        `;
      }).join("") : '<div style="font-size:0.75rem;color:#94A3B8;padding:8px 0;grid-column:1/-1;text-align:center;">Belum ada dokumentasi foto.</div>';

      // Tombol / Callout Selesaikan Pekerjaan Ini jika progres belum 100%, atau info Menunggu Verifikasi jika sudah 100% tapi belum disahkan Admin
      const resumeBtnHtml = (parseInt(lap.progres, 10) || 0) < 100 ? `
        <div class="riwayat-action-card">
          <div class="riwayat-action-top">
            <span class="riwayat-pulse-dot"></span>
            <span class="riwayat-action-title">Pekerjaan Lapangan Aktif (${lap.progres || 0}%)</span>
          </div>
          <div class="riwayat-action-desc">Lanjutkan tugas ini untuk mengambil foto tahapan kerja &amp; dokumentasi hasil akhir (100% tuntas).</div>
          <button type="button" class="btn-selesaikan-pekerjaan" onclick="resumeReportFromHistory('${lap.id}')">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
            <span>Lanjutkan & Selesaikan Pekerjaan</span>
          </button>
        </div>
      ` : (lap.status !== "selesai" ? `
        <div class="riwayat-action-card" style="background:#FFFBEB;border-color:#FDE68A;">
          <div class="riwayat-action-top">
            <span class="riwayat-pulse-dot" style="background:#D97706;"></span>
            <span class="riwayat-action-title" style="color:#B45309;">Menunggu Verifikasi Admin (100%)</span>
          </div>
          <div class="riwayat-action-desc" style="color:#92400E;">3 foto dokumentasi lengkap telah dikirim. Menunggu peninjauan &amp; persetujuan Admin.</div>
        </div>
      ` : '');

      const friendlyCardTitle = getLaporanHumanTitle(lap);

      return `
        <div class="user-history-card">
          <div class="uh-header" style="display:flex;align-items:flex-start;justify-content:space-between;gap:10px;margin-bottom:10px;">
            <div style="flex:1;min-width:0;">
              <div class="uh-badge-row">
                ${relativeBadgeHtml}
                <span class="badge-rw" style="border-radius:6px !important;">${lap.rw || 'RW 01'}</span>
                <span style="font-size:0.72rem;font-weight:800;color:var(--gov-orange-800);background:var(--gov-orange-100);padding:2px 8px;border-radius:6px;">${lap.regu || 'Regu 1'}</span>
                <span style="font-size:0.72rem;font-weight:800;color:var(--gov-blue-700);background:var(--gov-blue-100);padding:2px 8px;border-radius:6px;">${lap.tugasKe || 'Tugas 1'}</span>
              </div>
              <h4 class="uh-card-title">${friendlyCardTitle}</h4>
              <div style="font-size:0.82rem;font-weight:700;color:var(--color-brand-primary);margin-top:2px;">${lap.jenisPekerjaan || lap.judul}</div>
            </div>
            <span class="badge-status ${badgeCls}" style="border-radius:6px !important;flex-shrink:0;"><span class="status-dot"></span>${badgeTxt}</span>
          </div>

          <div style="font-size:0.82rem;color:var(--gov-slate-600);margin-bottom:12px;line-height:1.6;display:flex;flex-direction:column;gap:3px;">
            <div><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:inline;vertical-align:-1px;margin-right:4px;"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg><strong>Alamat:</strong> ${lap.alamat}</div>
            ${lap.lokasiDetail ? `<div><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:inline;vertical-align:-1px;margin-right:4px;"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg><strong>Patokan:</strong> ${lap.lokasiDetail}</div>` : ''}
            <div><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:inline;vertical-align:-1px;margin-right:4px;"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg><strong>Petugas:</strong> ${lap.petugas} (${lap.jumlahPetugas || 4} orang hadir) · <strong>Telp:</strong> <a href="tel:${lap.noHp || ''}" style="color:var(--gov-orange-700);font-weight:700;text-decoration:none;">${lap.noHp || '-'}</a></div>
            <div><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:inline;vertical-align:-1px;margin-right:4px;"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg><strong>Waktu Terdata:</strong> ${lap.tanggal}</div>
          </div>

          <!-- Progress Bar -->
          <div style="margin-bottom:10px;">
            <div style="display:flex;justify-content:space-between;font-size:0.74rem;font-weight:700;color:var(--gov-slate-700);margin-bottom:4px;">
              <span>Persentase Capaian Tugas:</span>
              <span style="color:var(--gov-orange-700);font-weight:800;">${lap.progres || 0}%</span>
            </div>
            <div style="background:var(--gov-slate-200);height:8px;border-radius:var(--radius-pill);overflow:hidden;">
              <div style="background:${(lap.progres >= 100 ? 'var(--gov-emerald-600)' : lap.progres >= 50 ? 'var(--gov-amber-600)' : 'var(--gov-blue-600)')};height:100%;width:${lap.progres || 0}%;transition:width 0.3s ease;"></div>
            </div>
          </div>

          ${resumeBtnHtml}

          ${lap.kendala ? `
            <div style="background:var(--gov-amber-50);border:1px solid var(--gov-amber-100);border-radius:var(--radius-sm);padding:8px 12px;margin:10px 0;font-size:0.78rem;color:var(--gov-amber-700);display:flex;gap:6px;align-items:flex-start;">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink:0;margin-top:2px;"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
              <div><strong>Catatan Lapangan:</strong> ${lap.kendala}</div>
            </div>
          ` : ''}

          <div style="font-size:0.72rem;font-weight:800;color:var(--gov-slate-500);text-transform:uppercase;letter-spacing:0.04em;margin:12px 0 6px 0;">Dokumentasi Foto Satelit / GPS:</div>
          <div style="display:grid;grid-template-columns:${gridTemplate};gap:8px;width:100%;box-sizing:border-box;margin-top:6px;${justifyGrid}">${photosHtml}</div>
        </div>
      `;
    }).join("");
  }

  function logoutUser() {
    try {
      localStorage.removeItem("silap_user_session");
      localStorage.removeItem("jawara_user_session");
    } catch(e) {}
    window.location.href = "logout.php";
  }

  document.addEventListener("DOMContentLoaded", initUserView);
</script>
</body>
</html>
