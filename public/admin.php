<?php
/**
 * SILAP – Admin Command Center PPSU DKI Jakarta
 * Protected PHP Dashboard (UI/UX 100% Identik Sesuai Persetujuan Kelurahan)
 */
session_start();
require_once __DIR__ . '/koneksi.php';

// Proteksi Sesi: Hanya Administrator yang berhak mengakses halaman ini
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: login.php?msg=unauthorized");
    exit;
}

$currentUser = $_SESSION['user'];
$userName = htmlspecialchars($currentUser['nama'] ?? 'Siti Aminah');
$userRole = 'Administrator';
$userWilayah = htmlspecialchars($currentUser['wilayah'] ?? 'jakpus');

// Generate Inisial Avatar
$nameParts = explode(' ', trim($userName));
$avatarText = count($nameParts) > 1 
    ? strtoupper(substr($nameParts[0], 0, 1) . substr($nameParts[1], 0, 1)) 
    : strtoupper(substr($nameParts[0], 0, 1));
$firstName = htmlspecialchars($nameParts[0]);

// Sinkronkan seluruh anggota & pengguna lapangan riil dari database
$formattedServerAnggota = [];
try {
    $stmtPetugas = $conn->query("SELECT id, nama, no_hp FROM users WHERE role = 'petugas'");
    $petugasUsers = $stmtPetugas ? $stmtPetugas->fetchAll(PDO::FETCH_ASSOC) : [];

    $stmtAllAgt = $conn->query("SELECT * FROM anggota ORDER BY id ASC");
    $serverAnggotaRows = $stmtAllAgt ? $stmtAllAgt->fetchAll(PDO::FETCH_ASSOC) : [];

    $existingLookup = [];
    $maxAgtNum = 0;
    foreach ($serverAnggotaRows as $a) {
        $existingLookup[strtolower(trim($a['nama']))] = true;
        if (preg_match('/AGT-(\d+)/', $a['id'], $m)) {
            $maxAgtNum = max($maxAgtNum, intval($m[1]));
        }
    }

    $inserted = false;
    foreach ($petugasUsers as $pu) {
        $pKey = strtolower(trim($pu['nama']));
        if (!isset($existingLookup[$pKey])) {
            $maxAgtNum++;
            $newId = 'AGT-' . str_pad($maxAgtNum, 3, '0', STR_PAD_LEFT);
            $insStmt = $conn->prepare("INSERT INTO anggota (id, nama, hp, role, regu_id, regu, wilayah) VALUES (?, ?, ?, 'Anggota Regu', '', 'Belum Ditugaskan', '10 RW')");
            $insStmt->execute([$newId, $pu['nama'], $pu['no_hp']]);
            $inserted = true;
        }
    }

    if ($inserted) {
        $stmtAllAgt = $conn->query("SELECT * FROM anggota ORDER BY id ASC");
        $serverAnggotaRows = $stmtAllAgt->fetchAll(PDO::FETCH_ASSOC);
    }

    foreach ($serverAnggotaRows as $sa) {
        $formattedServerAnggota[] = [
            'id'       => $sa['id'],
            'nama'     => $sa['nama'],
            'hp'       => str_replace('-', '', $sa['hp'] ?? ''),
            'role'     => $sa['role'],
            'reguId'   => $sa['regu_id'] ?: '',
            'regu_id'  => $sa['regu_id'] ?: '',
            'regu'     => $sa['regu'] ?: 'Belum Ditugaskan',
            'wilayah'  => $sa['wilayah'] ?: '10 RW',
            'status'   => 'aktif'
        ];
    }
} catch (Exception $e) {}

$formattedServerRegu = [];
try {
    $stmtRegu = $conn->query("SELECT * FROM regu ORDER BY id ASC");
    $serverReguRows = $stmtRegu ? $stmtRegu->fetchAll(PDO::FETCH_ASSOC) : [];
    $reguMembersMap = [];
    if (!empty($serverAnggotaRows)) {
        foreach ($serverAnggotaRows as $sa) {
            if (!empty($sa['regu_id'])) {
                $reguMembersMap[$sa['regu_id']][] = $sa['id'];
            }
        }
    }
    foreach ($serverReguRows as $sr) {
        $rId = $sr['id'];
        $kName = trim($sr['ketua'] ?? '');
        // Jika ketua di database regu kosong atau '-', otomatis isi dari anggota yang role-nya Ketua Regu
        if (empty($kName) || $kName === '-') {
            foreach ($serverAnggotaRows as $sa) {
                if ($sa['regu_id'] == $rId && $sa['role'] === 'Ketua Regu') {
                    $kName = $sa['nama'];
                    try {
                        $conn->prepare("UPDATE regu SET ketua = ? WHERE id = ?")->execute([$kName, $rId]);
                    } catch (Exception $e) {}
                    break;
                }
            }
        }
        $formattedServerRegu[] = [
            'id'         => $rId,
            'nama'       => $sr['nama'],
            'jenis'      => $sr['jenis'],
            'ketua'      => $kName,
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
  <title>SILAP – Admin Command Center PPSU DKI Jakarta</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@500;600;700;800;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="style.css?v=<?= time() ?>">
  <link rel="icon" type="image/png" href="foto/silap.png">
  <style>
    /* ==================== TO-DO LIST & EXPORT EXCEL SECTION ==================== */
    .todo-section {
      background: #ffffff;
      border: 1px solid #E2E8F0;
      border-radius: 16px;
      padding: 22px 24px;
      box-shadow: 0 1px 3px rgba(15, 23, 42, 0.03);
      display: flex;
      flex-direction: column;
      gap: 16px;
      margin-top: 24px;
    }
    .todo-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-wrap: wrap;
      gap: 12px;
    }
    .todo-title {
      font-size: 1.15rem;
      font-weight: 900;
      color: #0F172A;
      letter-spacing: -0.01em;
    }
    .todo-desc {
      font-size: 0.8rem;
      color: #64748B;
      margin-top: 2px;
    }
    .action-btn-group {
      display: flex;
      align-items: center;
      gap: 10px;
    }
    .btn-export-excel {
      background: #10B981;
      color: white;
      border: none;
      padding: 10px 18px;
      border-radius: 10px;
      font-weight: 800;
      font-size: 0.84rem;
      display: flex;
      align-items: center;
      gap: 7px;
      cursor: pointer;
      box-shadow: 0 4px 12px rgba(16, 185, 129, 0.22);
      transition: all 0.2s ease;
    }
    .btn-export-excel:hover {
      background: #059669;
      transform: translateY(-1px);
    }
    .btn-export-excel svg {
      width: 18px;
      height: 18px;
    }

    .time-chip-btn {
      white-space: nowrap;
      background: #FFFFFF;
      border: 1px solid #CBD5E1;
      border-radius: 6px;
      font-family: inherit;
      font-size: 0.74rem;
      font-weight: 700;
      color: #475569;
      padding: 5px 10px;
      cursor: pointer;
      transition: all 0.15s ease;
      flex-shrink: 0;
    }
    .time-chip-btn:hover {
      background: #F1F5F9;
      color: #0F172A;
      border-color: #94A3B8;
    }
    .time-chip-btn.active {
      background: #0284C7;
      border-color: #0284C7;
      color: #FFFFFF;
      font-weight: 800;
    }

    /* FILTER TABS */
    .todo-filter-tabs {
      display: flex;
      gap: 8px;
      border-bottom: 1px solid #F1F5F9;
      padding-bottom: 12px;
      flex-wrap: wrap;
    }
    .todo-tab-btn {
      background: #F8FAFC;
      border: 1px solid #E2E8F0;
      padding: 7px 14px;
      border-radius: 8px;
      font-size: 0.78rem;
      font-weight: 700;
      color: #64748B;
      cursor: pointer;
      transition: all 0.15s;
    }
    .todo-tab-btn:hover {
      background: #F1F5F9;
      color: #0F172A;
    }
    .todo-tab-btn.active {
      background: #FFF7ED;
      border-color: #FF5416;
      color: #FF5416;
    }

    /* TO-DO TASK CHECKLIST ITEMS */
    .todo-task-list {
      display: flex;
      flex-direction: column;
      gap: 10px;
    }
    .todo-task-item {
      display: flex;
      align-items: center;
      gap: 14px;
      padding: 14px 16px;
      border-radius: 12px;
      border: 1.5px solid #F1F5F9;
      background: #FAFAFA;
      transition: all 0.2s ease;
      cursor: pointer;
    }
    .todo-task-item:hover {
      background: #ffffff;
      border-color: #CBD5E1;
      box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
      transform: translateY(-1px);
    }
    .todo-check-icon {
      width: 24px;
      height: 24px;
      border-radius: 7px;
      border: 2px solid #CBD5E1;
      display: flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;
      transition: all 0.2s;
    }
    .todo-check-icon.checked {
      background: #10B981;
      border-color: #10B981;
      color: white;
    }
    .todo-check-icon.in-progress {
      background: #F59E0B;
      border-color: #F59E0B;
      color: white;
    }
    .todo-task-id {
      font-size: 0.74rem;
      font-weight: 800;
      color: #FF5416;
      background: #FFF7ED;
      padding: 4px 8px;
      border-radius: 6px;
      flex-shrink: 0;
      letter-spacing: 0.02em;
    }
    .todo-task-body {
      flex: 1;
      min-width: 0;
    }
    .todo-task-name {
      font-size: 0.88rem;
      font-weight: 800;
      color: #0F172A;
      margin-bottom: 2px;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }
    .todo-task-name.strikethrough {
      text-decoration: line-through;
      color: #94A3B8;
    }
    .todo-task-meta {
      font-size: 0.74rem;
      color: #64748B;
      display: flex;
      gap: 10px;
      align-items: center;
      flex-wrap: wrap;
    }
    .todo-badge-status {
      font-size: 0.72rem;
      font-weight: 800;
      padding: 4px 10px;
      border-radius: 999px;
      flex-shrink: 0;
    }
    .tbs-done { background: #ECFDF5; color: #047857; }
    .tbs-prog { background: #FEF3C7; color: #B45309; }
    .tbs-todo { background: #EFF6FF; color: #2563EB; }

    .btn-todo-detail {
      background: #F1F5F9;
      border: 1px solid #E2E8F0;
      padding: 6px 14px;
      border-radius: 8px;
      font-size: 0.75rem;
      font-weight: 700;
      color: #334155;
      cursor: pointer;
      transition: all 0.15s;
      flex-shrink: 0;
    }
    .btn-todo-detail:hover {
      background: #FF5416;
      color: white;
      border-color: #FF5416;
    }
    .admin-brand-sub-mobile {
      display: none;
    }
    @media (max-width: 1024px) {
      .admin-brand-sub-desktop {
        display: none;
      }
      .admin-brand-sub-mobile {
        display: block;
      }
    }
  </style>
</head>
<body style="background:#f8fafc;">

<div class="admin-view-container active" id="view-dashboard">
  
  <?php include __DIR__ . '/views/admin/sidebar.php'; ?>

  <!-- Main Content Area -->
  <main class="admin-main">
    <?php include __DIR__ . '/views/admin/header.php'; ?>

    <!-- Admin Body -->
    <div class="admin-body">
      <?php include __DIR__ . '/views/admin/dashboard.php'; ?>
      <?php include __DIR__ . '/views/admin/tugas.php'; ?>
      <?php include __DIR__ . '/views/admin/verifikasi.php'; ?>
      <?php include __DIR__ . '/views/admin/rekap.php'; ?>
      <?php include __DIR__ . '/views/admin/pengguna.php'; ?>
      <?php include __DIR__ . '/views/admin/regu.php'; ?>
      <?php include __DIR__ . '/views/admin/pengaturan.php'; ?>
    </div>
  </main>
</div>

<?php include __DIR__ . '/views/admin/modals.php'; ?>
<?php include __DIR__ . '/views/admin/bottom_nav.php'; ?>

<script src="exceljs.min.js"></script>
<script src="shared.js?v=<?= time() ?>"></script>
<script>
  // Mengambil sesi user aktif dari backend PHP
  const PHP_CURRENT_USER = <?= json_encode([
    'name'     => $currentUser['nama'],
    'username' => $currentUser['username'],
    'role'     => $currentUser['role'],
    'wilayah'  => $currentUser['wilayah'] ?? 'jakpus',
  ]) ?>;

  const SERVER_ANGGOTA_DATA = <?= json_encode($formattedServerAnggota) ?>;
  const SERVER_REGU_DATA = <?= json_encode($formattedServerRegu) ?>;

  const DEFAULT_KATEGORI_INFO = {
    jalan: { label: "Prasarana Jalan", color: "orange" },
    taman: { label: "Taman & RTH", color: "green" },
    saluran: { label: "Saluran Air", color: "blue" },
    kebersihan: { label: "Kebersihan", color: "yellow" },
    kantor: { label: "Sarana Kantor", color: "purple" }
  };

  function getKategoriDB() {
    try {
      const raw = localStorage.getItem("silap_kategori_db");
      if (raw) {
        const parsed = JSON.parse(raw);
        if (parsed && typeof parsed === "object" && Object.keys(parsed).length > 0) {
          return parsed;
        }
      }
    } catch(e) {}
    if (typeof KATEGORI_INFO !== "undefined" && KATEGORI_INFO) {
      return JSON.parse(JSON.stringify(KATEGORI_INFO));
    }
    return JSON.parse(JSON.stringify(DEFAULT_KATEGORI_INFO));
  }

  function saveKategoriDB(db) {
    try {
      localStorage.setItem("silap_kategori_db", JSON.stringify(db));
    } catch(e) {}
    if (typeof KATEGORI_INFO !== "undefined") {
      Object.keys(db).forEach(k => {
        KATEGORI_INFO[k] = db[k];
      });
    }
  }

  let allReports = [];
  let currentFilter = "all";
  let activeModalReport = null;
  let allAnggota = [];
  let allRegu = [];

  function initAdmin() {
    // Sinkronkan data profil dari PHP session
    const user = PHP_CURRENT_USER || (typeof getCurrentUser === "function" ? getCurrentUser() : { name: "Siti Aminah" });
    
    // User display
    const userDisplay = document.getElementById("user-name-display");
    if (userDisplay) userDisplay.textContent = user.name || "Siti Aminah";
    
    const dashName = document.getElementById("dash-user-name");
    if (dashName) dashName.textContent = (user.name || "Siti").split(" ")[0];

    const avatarEl = document.getElementById("user-avatar");
    if (avatarEl) {
      const parts = (user.name || "SA").trim().split(" ");
      avatarEl.textContent = parts.length > 1 ? (parts[0][0] + parts[1][0]).toUpperCase() : parts[0][0].toUpperCase();
    }

    // Natural Indonesian Date (Uppercase Sesuai UI)
    const now = new Date();
    const days = ["Minggu", "Senin", "Selasa", "Rabu", "Kamis", "Jumat", "Sabtu"];
    const months = ["Januari", "Februari", "Maret", "April", "Mei", "Juni", "Juli", "Agustus", "September", "Oktober", "November", "Desember"];
    const dateEl = document.getElementById("dash-date");
    if (dateEl) {
      dateEl.textContent = `${days[now.getDay()]}, ${now.getDate()} ${months[now.getMonth()]} ${now.getFullYear()}`.toUpperCase();
    }

    // Sinkronkan master regu dari database server ke client
    if (Array.isArray(SERVER_REGU_DATA) && SERVER_REGU_DATA.length > 0) {
      try {
        saveReguDB(SERVER_REGU_DATA, false);
      } catch(e) {}
    }

    // Sinkronkan master anggota dari database server ke client
    if (Array.isArray(SERVER_ANGGOTA_DATA) && SERVER_ANGGOTA_DATA.length > 0) {
      try {
        saveAnggotaDB(SERVER_ANGGOTA_DATA, false);
      } catch(e) {}
    }

    // Enforce integritas: 1 Ketua Regu per Regu & sinkronkan role anggota
    try {
      const curRegu = typeof getReguDB === "function" ? getReguDB() : [];
      const curAnggota = typeof getAnggotaDB === "function" ? getAnggotaDB() : [];
      let needSaveRegu = false;
      let needSaveAgt = false;

      curRegu.forEach(rg => {
        let kName = (rg.ketua || "").trim();
        // Jika nama ketua kosong atau '-', cari anggota di regu ini yang role-nya Ketua Regu
        if (!kName || kName === "-") {
          const km = curAnggota.find(a => (a.reguId === rg.id || (rg.anggotaIds && rg.anggotaIds.includes(a.id))) && a.role === "Ketua Regu");
          if (km) {
            rg.ketua = km.nama;
            kName = km.nama;
            needSaveRegu = true;
          }
        }

        if (kName && kName !== "-") {
          const kLower = kName.toLowerCase();
          curAnggota.forEach(a => {
            if (a.reguId === rg.id || (rg.anggotaIds && rg.anggotaIds.includes(a.id))) {
              const match = (a.nama || "").trim().toLowerCase() === kLower;
              if (match && a.role !== "Ketua Regu") {
                a.role = "Ketua Regu";
                needSaveAgt = true;
              } else if (!match && a.role === "Ketua Regu") {
                a.role = "Anggota Regu";
                needSaveAgt = true;
              }
            }
          });
        }
      });

      if (needSaveRegu) saveReguDB(curRegu);
      if (needSaveAgt) saveAnggotaDB(curAnggota);
    } catch(eSync) {}

    // Load Data & Inisialisasi Realtime Synchronizer
    refreshAdminData();

    // Event Listeners Realtime Sinkronisasi Laporan & Anggota
    window.addEventListener("silap_laporan_updated", () => refreshAdminData());
    window.addEventListener("silap_anggota_updated", (e) => {
      if (Array.isArray(e.detail)) {
        allAnggota = e.detail;
        try { renderAnggotaTable(allAnggota); } catch(err) {}
        try { renderReguCards(allRegu); } catch(err) {}
        try { renderDashboardReguTable(); } catch(err) {}
      }
    });
    window.addEventListener("silap_regu_updated", (e) => {
      if (Array.isArray(e.detail)) {
        allRegu = e.detail;
        try { renderReguCards(allRegu); } catch(err) {}
        try { renderDashboardReguTable(); } catch(err) {}
        try { renderTugasReguFilters(); } catch(err) {}
      }
    });
    window.addEventListener("storage", (e) => {
      if (!e.key || e.key === "silap_reports_db" || e.key === "silap_regu_data_v3" || e.key === "silap_anggota_data_v3") {
        refreshAdminData();
      }
    });

    try {
      if (typeof BroadcastChannel !== "undefined") {
        const bc = new BroadcastChannel("silap_sync_channel");
        bc.onmessage = () => refreshAdminData();
      }
    } catch(eBc) {}

    window.addEventListener("focus", () => refreshAdminData());
    setInterval(refreshAdminData, 3000);
    setupMobileBottomNavPin();

    const urlParams = new URLSearchParams(window.location.search);
    const reqTab = urlParams.get("tab");
    if (reqTab && typeof switchAdminTab === "function") {
      switchAdminTab(reqTab);
    }
  }

  // Kunci posisi Bottom Nav agar selalu tegak menempel bawah layar di iOS/Android saat scroll
  function setupMobileBottomNavPin() {
    const nav = document.getElementById("admin-bottom-nav");
    const sheet = document.getElementById("admin-mobile-menu-sheet");
    if (!nav) return;

    let rafId = null;
    const syncPin = () => {
      rafId = null;
      if (window.innerWidth > 1024) {
        nav.style.transform = "";
        return;
      }
      const vv = window.visualViewport;
      if (vv && (vv.scale > 1.02 || vv.height < window.innerHeight - 120)) {
        return;
      }
      const targetBottom = window.innerHeight;
      const curRect = nav.getBoundingClientRect();
      if (curRect.bottom < targetBottom - 2) {
        nav.style.transform = "translate3d(0, 0, 0)";
        const rawRect = nav.getBoundingClientRect();
        const delta = Math.round(targetBottom - rawRect.bottom);
        const shift = delta > 2 ? delta : 0;
        nav.style.transform = `translate3d(0, ${shift}px, 0)`;
        if (sheet) {
          sheet.style.transform = `translate3d(0, ${shift}px, 0)`;
        }
      } else if (curRect.bottom > targetBottom + 2) {
        nav.style.transform = "translate3d(0, 0, 0)";
        if (sheet) {
          sheet.style.transform = "translate3d(0, 0, 0)";
        }
      }
    };

    const scheduleSync = () => {
      if (rafId !== null) return;
      rafId = requestAnimationFrame(syncPin);
    };

    window.addEventListener("scroll", scheduleSync, { passive: true });
    window.addEventListener("resize", scheduleSync, { passive: true });
    window.addEventListener("focusout", () => setTimeout(scheduleSync, 120));
    if (window.visualViewport) {
      window.visualViewport.addEventListener("resize", scheduleSync, { passive: true });
      window.visualViewport.addEventListener("scroll", scheduleSync, { passive: true });
    }
  }

  // Sinkronisasi Komprehensif Admin Data (Laporan, Anggota, Regu)
  function refreshAdminData() {
    allReports = typeof getLaporanDB === "function" ? getLaporanDB() : [];
    allAnggota = typeof getAnggotaDB === "function" ? getAnggotaDB() : [];
    allRegu = typeof getReguDB === "function" ? getReguDB() : [];

    try { updateStats(); } catch(e) {}
    try { renderTodoList(allReports); } catch(e) {}
    try { renderTaskCategoryStats(); } catch(e) {}
    try { renderDashboardReguTable(); } catch(e) {}
    try { if (typeof applyTugasFilters === "function") applyTugasFilters(); else renderTugasList(allReports); } catch(e) {}
    try { renderVerifikasiList(allReports); } catch(e) {}
    try { renderRekapTable(allReports); } catch(e) {}
    try { renderTugasReguFilters(); } catch(e) {}
    try { renderAnggotaTable(allAnggota); } catch(e) {}
    try { renderReguCards(allRegu); } catch(e) {}
  }

  // Toggle Bottom Menu Action Sheet (Beralih ke Mode User & Keluar)
  function toggleAdminMobileMenu(forceState) {
    const sheet = document.getElementById("admin-mobile-menu-sheet");
    const backdrop = document.getElementById("admin-mobile-menu-backdrop");
    const menuBtn = document.getElementById("bnav-btn-menu");
    if (!sheet || !backdrop) return;
    const shouldOpen = typeof forceState === "boolean"
      ? forceState
      : !sheet.classList.contains("open");
    sheet.classList.toggle("open", shouldOpen);
    backdrop.classList.toggle("open", shouldOpen);
    if (menuBtn) {
      menuBtn.classList.toggle("active", shouldOpen);
    }
  }

  function toggleAdminMobileNav(forceState) {
    toggleAdminMobileMenu(forceState);
  }

  // Switch Admin Tabs (7 Views)
  function switchAdminTab(tabName) {
    toggleAdminMobileMenu(false);
    document.querySelectorAll(".admin-tab-pane").forEach(p => p.classList.remove("active"));
    document.querySelectorAll(".admin-nav-item").forEach(b => b.classList.remove("active"));
    document.querySelectorAll(".admin-bottom-nav-item").forEach(b => b.classList.remove("active"));

    const targetPane = document.getElementById("pane-" + tabName);
    const targetBtn = document.getElementById("nav-btn-" + tabName);
    const targetBottomBtn = document.getElementById("bnav-btn-" + tabName);

    if (targetPane) targetPane.classList.add("active");
    if (targetBtn) targetBtn.classList.add("active");
    if (targetBottomBtn) {
      targetBottomBtn.classList.add("active");
      try {
        targetBottomBtn.scrollIntoView({ behavior: "smooth", block: "nearest", inline: "center" });
      } catch(e) {}
    }

    const viewTitles = {
      dashboard: "Dashboard",
      tugas: "Daftar Tugas",
      verifikasi: "Verifikasi Laporan",
      rekap: "Rekapitulasi",
      pengguna: "Manajemen Pengguna",
      regu: "Manajemen Regu",
      pengaturan: "Pengaturan"
    };

    const titleEl = document.getElementById("topbar-current-view");
    if (titleEl) titleEl.textContent = viewTitles[tabName] || "Dashboard";

    // Scroll smoothly to top on mobile tab switch
    try { window.scrollTo({ top: 0, behavior: "smooth" }); } catch(e) {}

    // Re-render isi tab secara dinamis agar selalu tampil sempurna saat diklik
    refreshAdminData();
    try {
      if (tabName === "tugas") { renderTugasCategoryFilters(); renderTugasReguFilters(); applyTugasFilters(); }
    } catch(e) {
      console.error("Tab switch render error:", e);
    }
  }

  function updateStats() {
    allReports = getLaporanDB();
    const total = allReports.length;
    const selesai = allReports.filter(r => r.status === "selesai").length;
    const belum = allReports.filter(r => r.status === "belum" || (r.status !== "selesai" && parseInt(r.progres, 10) >= 100)).length;
    const proses = allReports.filter(r => r.status === "proses" && parseInt(r.progres, 10) < 100).length;

    document.getElementById("ds-total").textContent = total;
    document.getElementById("ds-selesai").textContent = selesai;
    document.getElementById("ds-proses").textContent = proses;
    document.getElementById("ds-belum").textContent = belum;
    document.getElementById("ds-terverifikasi").textContent = selesai;
    document.getElementById("admin-badge-verif").textContent = belum;
    const bBadge = document.getElementById("bnav-badge-verif");
    if (bBadge) {
      bBadge.textContent = belum;
      bBadge.style.display = belum > 0 ? "flex" : "none";
    }
    const cntAll = document.getElementById("cnt-all");
    if (cntAll) cntAll.textContent = total;

    // Update To-Do List Filter Tab Badges
    const tdAll = document.getElementById("todo-cnt-all");
    if (tdAll) tdAll.textContent = total;
    const tdBelum = document.getElementById("todo-cnt-belum");
    if (tdBelum) tdBelum.textContent = belum;
    const tdProses = document.getElementById("todo-cnt-proses");
    if (tdProses) tdProses.textContent = proses;
    const tdSelesai = document.getElementById("todo-cnt-selesai");
    if (tdSelesai) tdSelesai.textContent = selesai;

    const agtNum = document.getElementById("ds-petugas-num");
    if (agtNum) {
      agtNum.textContent = (allAnggota && allAnggota.length) ? allAnggota.length : 10;
    }
  }

  // ==================== TO-DO LIST & EXPORT ENGINE ====================
  let currentTodoFilter = "all";
  let currentTodoRwFilter = "all";
  let currentTodoSearchQuery = "";
  let isShowingAllTodos = false;

  function toggleShowAllTodos() {
    isShowingAllTodos = !isShowingAllTodos;
    applyTodoFilters();
  }

  const STANDAR_JENIS_PEKERJAAN = {
    "Pembersihan Jalan & Trotoar": "Sapu sampah kering, debu, dedaunan gugur, dan kotoran bahu jalan.",
    "Pembersihan Saluran Air & Got": "Keruk lumpur selokan, angkat rumput liar, dan lancarkan tali air.",
    "Perawatan Taman & Jalur Hijau": "Pemeliharaan tanaman hias, pot lingkungan, dan pembersihan area taman.",
    "Pemangkasan Pohon & Dahan Rendah": "Pangkas ranting dahan (maks. 2 meter) yang menghalangi jalan / kabel utilitas.",
    "Perbaikan Jalan & Trotoar Ringan": "Tambal lubang kecil, rapikan paving amblas, dan perbaiki kanstin lepas."
  };

  function getDeskripsiPekerjaan(lap) {
    if (!lap) return "";
    if (lap.jenisPekerjaan && STANDAR_JENIS_PEKERJAAN[lap.jenisPekerjaan]) {
      return STANDAR_JENIS_PEKERJAAN[lap.jenisPekerjaan];
    }
    for (const [key, desc] of Object.entries(STANDAR_JENIS_PEKERJAAN)) {
      if ((lap.judul || "").includes(key) || (lap.jenisPekerjaan || "").includes(key)) {
        return desc;
      }
    }
    return lap.deskripsi || "Penanganan dan pemeliharaan sarana prasarana lingkungan kelurahan.";
  }

  function renderTaskCardHtml(lap) {
    const ki = KATEGORI_INFO[lap.kat] || { label: "Prasarana", color: "orange" };
    const svgIcon = getKategoriIconSvg(lap.kat);
    const badgeCls = lap.status === "selesai" ? "badge-selesai" : lap.status === "proses" ? "badge-proses" : "badge-belum";
    const badgeTxt = lap.status === "selesai" ? "Selesai (100%)" : lap.status === "proses" ? "Dalam Proses (" + (lap.progres || 50) + "%)" : "Perlu Verifikasi";
    const judulTugas = lap.jenisPekerjaan || lap.judul || "Tugas Lapangan PPSU";
    const deskripsiStandar = getDeskripsiPekerjaan(lap);

    return `
      <div class="recent-item" onclick="openDetailModal('${lap.id}')">
        <div class="recent-item-icon kpi-${ki.color || 'orange'}">
          ${svgIcon}
        </div>
        <div class="recent-item-body">
          <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:6px;margin-bottom:6px;">
            <div style="display:flex;align-items:center;flex-wrap:wrap;gap:6px;">
              <span class="badge-rw">${lap.rw || 'RW 01'}</span>
              <span class="badge-minggu">${lap.minggu || 'Laporan Mingguan'}</span>
              <span class="badge-regu" onclick="event.stopPropagation(); filterTugasRegu('${lap.regu}')" style="cursor:pointer;" title="Klik untuk saring tugas regu ini">${lap.regu || 'Regu PPSU'}</span>
              <span style="font-size:0.75rem;font-weight:800;color:#FF5416;">#${lap.noPekerjaan || lap.id}</span>
            </div>
            <span class="badge-status ${badgeCls}"><span class="status-dot"></span>${badgeTxt}</span>
          </div>
          <div style="margin-bottom:6px;">
            <div class="recent-item-title">${judulTugas}</div>
            <div class="recent-item-desc">${deskripsiStandar}</div>
          </div>
          <div class="recent-item-meta" style="flex-wrap:wrap;gap:12px;">
            <span class="meta-segment"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:-2px;margin-right:4px;"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>${lap.alamat}</span>
            <span class="meta-segment"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:-2px;margin-right:4px;"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>${lap.petugas} (${lap.jumlahPetugas || 4} orang)</span>
            <span class="meta-segment"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:-2px;margin-right:4px;"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg><a href="tel:${lap.noHp || ''}" style="color:#0284C7;font-weight:700;text-decoration:none;" onclick="event.stopPropagation();">${lap.noHp || '-'}</a></span>
            <span class="meta-segment"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:-2px;margin-right:4px;"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>${lap.tanggal}</span>
            ${lap.kendala ? `<span class="meta-segment" style="color:#C2410C;"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:-2px;margin-right:4px;"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>${lap.kendala}</span>` : ''}
          </div>
        </div>
        <button type="button" class="btn-lihat-detail" onclick="event.stopPropagation(); openDetailModal('${lap.id}')">
          <span>Detail (${(lap.foto || []).filter(f => f && f.src && f.src.trim() !== '').length} Foto)</span>
        </button>
      </div>
    `;
  }

  function renderTodoList(list) {
    const container = document.getElementById("todo-list-container");
    if (!container) return;

    if (!list || list.length === 0) {
      container.innerHTML = '<div style="text-align:center;padding:2.5rem;color:#64748B;font-weight:700;">Tidak ada tugas pada kriteria filter ini.</div>';
      return;
    }

    // Default hanya 5 teratas, atau semua jika toggle
    const displayList = isShowingAllTodos ? list : list.slice(0, 5);
    const itemsHtml = displayList.map(lap => renderTaskCardHtml(lap)).join("");

    // Footer Tombol "Lihat Semua Tugas"
    let footerHtml = "";
    if (list.length > 5) {
      footerHtml = `
        <div style="display:flex;justify-content:space-between;align-items:center;padding:12px 6px 4px;border-top:1px solid #F1F5F9;margin-top:6px;flex-wrap:wrap;gap:8px;">
          <span style="font-size:0.75rem;color:#64748B;">
            Menampilkan <strong>${displayList.length}</strong> dari <strong>${list.length}</strong> tugas lapangan
          </span>
          <div style="display:flex;gap:8px;">
            <button type="button" class="todo-tab-btn" onclick="toggleShowAllTodos()" style="background:#FFF7ED;border-color:#FFEDD5;color:#FF5416;font-weight:800;">
              ${isShowingAllTodos ? '▲ Tampilkan 5 Teratas Saja' : `▼ Tampilkan Semua Tugas (${list.length})`}
            </button>
            <button type="button" class="todo-tab-btn" onclick="switchAdminTab('tugas')" style="font-weight:700;">
              Kelola di Tab Tugas &rarr;
            </button>
          </div>
        </div>
      `;
    }

    container.innerHTML = itemsHtml + footerHtml;
  }

  // ==================== STATISTIK BIDANG TUGAS (DONUT CHART & LEGENDA) ====================
  function renderTaskCategoryStats() {
    const container = document.getElementById("dash-stats-category-container");
    if (!container) return;

    const katDB = getKategoriDB();
    const counts = {};
    Object.keys(katDB).forEach(k => counts[k] = 0);
    allReports.forEach(r => {
      const k = r.kat || 'jalan';
      if (counts[k] !== undefined) counts[k]++;
      else if (counts.jalan !== undefined) counts.jalan++;
    });

    const total = allReports.length || 1;
    const colorMap = {
      orange: "#EA580C",
      green: "#16A34A",
      blue: "#2563EB",
      yellow: "#CA8A04",
      purple: "#9333EA",
      slate: "#475569",
      teal: "#0D9488",
      rose: "#E11D48"
    };

    const categories = Object.keys(katDB).map(k => {
      const info = katDB[k];
      return {
        key: k,
        label: info.label || k,
        color: colorMap[info.color] || "#64748B"
      };
    });

    categories.sort((a, b) => counts[b.key] - counts[a.key]);

    // Generate Conic Gradient untuk Donut Chart Bulat
    let gradientParts = [];
    let currentPct = 0;
    categories.forEach(c => {
      const cnt = counts[c.key];
      const pct = (cnt / total) * 100;
      if (pct > 0) {
        const nextPct = currentPct + pct;
        gradientParts.push(`${c.color} ${currentPct.toFixed(1)}% ${nextPct.toFixed(1)}%`);
        currentPct = nextPct;
      }
    });

    if (gradientParts.length === 0) {
      gradientParts.push("#E2E8F0 0% 100%");
    } else if (currentPct < 100) {
      gradientParts.push(`#E2E8F0 ${currentPct.toFixed(1)}% 100%`);
    }

    const conicBg = `conic-gradient(${gradientParts.join(", ")})`;

    // Legend Rows
    const rowsHtml = categories.map(c => {
      const cnt = counts[c.key];
      const pct = Math.round((cnt / total) * 100);
      return `
        <div class="dash-legend-row">
          <div class="dash-legend-left">
            <span class="dash-legend-dot" style="background:${c.color};"></span>
            <span class="dash-legend-name" title="${c.label}">${c.label}</span>
          </div>
          <div class="dash-legend-right">
            <span class="dash-legend-cnt">${cnt}</span>
            <span class="dash-legend-pct">${pct}%</span>
          </div>
        </div>
      `;
    }).join("");

    container.innerHTML = `
      <div class="dash-category-wrap">
        <!-- Donut Chart Bulat -->
        <div class="dash-donut-box" title="Total: ${allReports.length} Tugas">
          <div class="dash-donut-conic" style="background:${conicBg};">
            <div class="dash-donut-center">
              <strong class="dash-donut-val">${allReports.length}</strong>
              <span class="dash-donut-label">Tugas</span>
            </div>
          </div>
        </div>

        <!-- Legend Rincian -->
        <div class="dash-legend-box">
          ${rowsHtml}
        </div>
      </div>
    `;
  }

  function renderDashboardReguTable() {
    renderDashboardReguCards();
  }

  function renderDashboardReguCards() {
    const container = document.getElementById("dash-regu-cards-container");
    if (!container) return;

    if (!allRegu || allRegu.length === 0) {
      container.innerHTML = '<div style="text-align:center;padding:24px;color:#64748B;font-size:0.85rem;">Belum ada regu terdaftar.</div>';
      return;
    }

    const rowsHtml = allRegu.map(r => {
      const members = allAnggota.filter(a => a.reguId === r.id || (r.anggotaIds && r.anggotaIds.includes(a.id)));
      const count = members.length;

      return `
        <tr onclick="openPopupPersonelRegu('${r.id}')" style="cursor:pointer;border-bottom:1px solid #F1F5F9;transition:background 0.15s;" onmouseover="this.style.background='#F8FAFC';" onmouseout="this.style.background='transparent';">
          <td style="padding:10px 8px;">
            <div style="font-weight:700;color:#0F172A;font-size:0.84rem;">${r.nama}</div>
            <div style="font-size:0.72rem;color:#64748B;margin-top:1px;">${r.wilayah || 'Wilayah 10 RW'}</div>
          </td>
          <td style="padding:10px 8px;">
            <div style="font-weight:600;color:#1E293B;font-size:0.82rem;">${r.ketua || '-'}</div>
            <div style="font-size:0.7rem;color:#64748B;margin-top:1px;">${r.noHp || '-'}</div>
          </td>
          <td style="padding:10px 8px;text-align:center;">
            <span style="display:inline-block;padding:2px 8px;background:#F1F5F9;color:#334155;border-radius:4px;font-weight:700;font-size:0.74rem;">
              ${count} Personel
            </span>
          </td>
          <td style="padding:10px 8px;text-align:right;">
            <span style="color:#FF5416;font-weight:700;font-size:0.76rem;display:inline-flex;align-items:center;gap:3px;">
              Lihat &rarr;
            </span>
          </td>
        </tr>
      `;
    }).join("");

    const mobileCardsHtml = allRegu.map(r => {
      const members = allAnggota.filter(a => a.reguId === r.id || (r.anggotaIds && r.anggotaIds.includes(a.id)));
      const count = members.length;
      const initial = (r.nama || 'R').replace(/[^a-zA-Z0-9]/g, '').charAt(0).toUpperCase() || 'R';

      return `
        <div class="dash-regu-card-mobile" onclick="openPopupPersonelRegu('${r.id}')">
          <div class="dash-regu-card-head">
            <div class="dash-regu-card-title-group">
              <div class="dash-regu-avatar-badge">${initial}</div>
              <div>
                <div class="dash-regu-card-name">${r.nama}</div>
                <div class="dash-regu-card-zone">${r.wilayah || 'Wilayah 10 RW'}</div>
              </div>
            </div>
            <span class="dash-regu-pill-count">${count} Personel</span>
          </div>
          <div class="dash-regu-card-footer">
            <div class="dash-regu-pj-box">
              <span class="dash-regu-pj-label">Penanggung Jawab</span>
              <span class="dash-regu-pj-val">${r.ketua && r.ketua !== '-' ? r.ketua : '-'}${r.noHp && r.noHp !== '-' ? ` <span class="dash-regu-pj-phone">(${r.noHp})</span>` : ''}</span>
            </div>
            <span class="dash-regu-card-link-btn">
              <span>Lihat</span>
              <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"></polyline></svg>
            </span>
          </div>
        </div>
      `;
    }).join("");

    container.innerHTML = `
      <!-- Desktop Roster Table -->
      <div class="desktop-only-table">
        <table style="width:100%;border-collapse:collapse;font-size:0.82rem;">
          <thead>
            <tr style="border-bottom:1.5px solid #E2E8F0;color:#64748B;font-size:0.7rem;text-transform:uppercase;letter-spacing:0.5px;">
              <th style="padding:0 8px 8px;text-align:left;font-weight:700;">Regu</th>
              <th style="padding:0 8px 8px;text-align:left;font-weight:700;">Penanggung Jawab</th>
              <th style="padding:0 8px 8px;text-align:center;font-weight:700;">Kekuatan</th>
              <th style="padding:0 8px 8px;text-align:right;font-weight:700;">Anggota</th>
            </tr>
          </thead>
          <tbody>
            ${rowsHtml}
          </tbody>
        </table>
      </div>

      <!-- Mobile Touch Cards Stack -->
      <div class="mobile-only-cards dash-regu-cards-wrap">
        ${mobileCardsHtml}
      </div>
    `;
  }

  function openPopupPersonelRegu(reguId) {
    const regu = allRegu.find(r => r.id === reguId);
    if (!regu) return;

    const members = allAnggota.filter(a => a.reguId === regu.id || (regu.anggotaIds && regu.anggotaIds.includes(a.id)));

    const nameEl = document.getElementById("pop-regu-name");
    if (nameEl) nameEl.textContent = regu.nama;

    const countBadge = document.getElementById("pop-regu-count-badge");
    if (countBadge) countBadge.textContent = `${members.length} Personel`;

    const ketuaEl = document.getElementById("pop-regu-ketua");
    if (ketuaEl) ketuaEl.textContent = regu.ketua || '-';

    const wilEl = document.getElementById("pop-regu-wilayah");
    if (wilEl) wilEl.textContent = regu.wilayah || '10 RW Pekayon';

    const memberListEl = document.getElementById("pop-regu-member-list");
    if (memberListEl) {
      if (members.length === 0) {
        memberListEl.innerHTML = '<div style="text-align:center;padding:20px;color:#64748B;font-size:0.82rem;background:#F8FAFC;border-radius:8px;">Belum ada anggota terdaftar di regu ini.</div>';
      } else {
        const trs = members.map(a => {
          const isKetua = (a.nama.trim().toLowerCase() === (regu.ketua || '').trim().toLowerCase());
          return `
            <tr style="border-bottom:1px solid #F1F5F9;">
              <td style="padding:9px 10px;">
                <div style="display:flex;align-items:center;gap:6px;">
                  <strong style="color:#0F172A;font-size:0.83rem;">${a.nama}</strong>
                  ${isKetua ? '<span style="background:#FFF7ED;color:#C2410C;font-size:0.65rem;font-weight:800;padding:1px 6px;border-radius:4px;border:1px solid #FFEDD5;">Ketua</span>' : ''}
                </div>
              </td>
              <td style="padding:9px 10px;">
                <div style="font-size:0.8rem;color:#0F172A;font-weight:700;user-select:all;">${typeof formatPhone08 === 'function' ? formatPhone08(a.hp) : (a.hp || '-').replace(/-/g, '').replace(/^\+?62/, '0')}</div>
              </td>
              <td style="padding:9px 10px;text-align:right;">
                <span style="font-size:0.72rem;font-weight:700;color:#059669;">● Siap Tugas</span>
              </td>
            </tr>
          `;
        }).join("");

        memberListEl.innerHTML = `
          <table style="width:100%;border-collapse:collapse;font-size:0.8rem;border:1px solid #F1F5F9;border-radius:8px;overflow:hidden;">
            <thead style="background:#F8FAFC;border-bottom:1px solid #E2E8F0;">
              <tr>
                <th style="padding:8px 10px;text-align:left;color:#64748B;font-size:0.7rem;text-transform:uppercase;">Nama Petugas</th>
                <th style="padding:8px 10px;text-align:left;color:#64748B;font-size:0.7rem;text-transform:uppercase;">No. Telepon</th>
                <th style="padding:8px 10px;text-align:right;color:#64748B;font-size:0.7rem;text-transform:uppercase;">Status</th>
              </tr>
            </thead>
            <tbody>
              ${trs}
            </tbody>
          </table>
        `;
      }
    }

    const modal = document.getElementById("modal-popup-personel-regu");
    if (modal) modal.classList.remove("hidden");
  }

  function closePopupPersonelRegu() {
    const modal = document.getElementById("modal-popup-personel-regu");
    if (modal) modal.classList.add("hidden");
  }

  function goToMenuReguDirect() {
    closePopupPersonelRegu();
    switchAdminTab('regu');
    showToast("Membuka Manajemen Regu Lapangan.", "info");
  }

  function changeAnggotaReguDirect(agtId, newReguId) {
    const agt = allAnggota.find(a => a.id === agtId);
    if (!agt) return;

    const oldReguId = agt.reguId;
    const newReg = allRegu.find(r => r.id === newReguId);
    agt.reguId = newReguId;
    agt.regu = newReg ? newReg.nama : "Belum Ditugaskan";
    agt.wilayah = newReg ? (newReg.wilayah || "-") : "-";

    allRegu.forEach(rg => {
      if (!rg.anggotaIds) rg.anggotaIds = [];
      if (rg.id === oldReguId && rg.id !== newReguId) {
        rg.anggotaIds = rg.anggotaIds.filter(id => id !== agtId);
        if (rg.ketua && rg.ketua.trim().toLowerCase() === (agt.nama || "").trim().toLowerCase()) {
          rg.ketua = "";
        }
      }
      if (rg.id === newReguId && !rg.anggotaIds.includes(agtId)) {
        rg.anggotaIds.push(agtId);
      }
    });

    saveAnggotaDB(allAnggota);
    saveReguDB(allRegu);

    showToast(`Berhasil! ${agt.nama} dipindahkan ke ${newReg ? newReg.nama : 'Belum Ditugaskan'}.`, 'success');

    renderDashboardReguTable();
    renderAnggotaTable(allAnggota);
    renderReguCards(allRegu);
    updateStats();
  }

  function hapusAnggotaDirect(agtId) {
    const agt = allAnggota.find(a => a.id === agtId);
    if (!agt) return;
    if (!confirm(`Hapus ${agt.nama} dari daftar personel?`)) return;

    const deletedNama = agt.nama;
    allAnggota = allAnggota.filter(a => a.id !== agtId);
    allRegu.forEach(rg => {
      if (rg.anggotaIds) {
        rg.anggotaIds = rg.anggotaIds.filter(id => id !== agtId);
      }
      if (rg.ketua && rg.ketua.trim().toLowerCase() === (deletedNama || '').trim().toLowerCase()) {
        rg.ketua = '';
      }
    });

    saveAnggotaDB(allAnggota);
    saveReguDB(allRegu);

    // Kirim sinyal hapus akun ke server & realtime broadcast
    try {
      fetch(`api.php?action=delete_anggota&id=${encodeURIComponent(agtId)}&nama=${encodeURIComponent(deletedNama)}`, { method: "POST" });
      if (typeof BroadcastChannel !== "undefined") {
        const bc = new BroadcastChannel("silap_sync_channel");
        bc.postMessage({ type: "user_deleted", deletedNama: deletedNama, deletedId: agtId, timestamp: Date.now() });
      }
    } catch(e) {}

    showToast(`Personel ${deletedNama} telah dihapus dari sistem.`, 'info');
    renderDashboardReguTable();
    renderAnggotaTable(allAnggota);
    renderReguCards(allRegu);
    updateStats();
  }

  let currentTodoPeriodFilter = "all";

  function filterTodoList(status, btn) {
    currentTodoFilter = status;
    document.querySelectorAll(".todo-tab-btn").forEach(b => b.classList.remove("active"));
    if (btn) btn.classList.add("active");
    applyTodoFilters();
  }

  function filterTodoByPeriod(period, btn) {
    currentTodoPeriodFilter = period;
    document.querySelectorAll("#admin-time-chips .time-chip-btn").forEach(b => b.classList.remove("active"));
    if (btn) btn.classList.add("active");
    else {
      const bMatch = document.querySelector(`#admin-time-chips .time-chip-btn[data-period="${period}"]`);
      if (bMatch) bMatch.classList.add("active");
    }

    const sel = document.getElementById("todo-export-period-select");
    if (sel && sel.value !== period) sel.value = period;

    const labelMap = {
      "all": "Download Excel (.xlsx)",
      "today": "Download Excel (Hari Ini)",
      "yesterday": "Download Excel (Kemarin)",
      "2days": "Download Excel (2 Hari Lalu)",
      "3days": "Download Excel (3 Hari Lalu)",
      "7days": "Download Excel (7 Hari)",
      "14days": "Download Excel (14 Hari)",
      "30days": "Download Excel (30 Hari)"
    };
    const btnLabel = document.getElementById("admin-export-btn-label");
    if (btnLabel) btnLabel.textContent = labelMap[period] || "Download Excel (.xlsx)";

    applyTodoFilters();
  }

  function onAdminPeriodSelectChanged(val) {
    filterTodoByPeriod(val);
  }

  function filterTodoByRw(rw) {
    currentTodoRwFilter = rw;
    applyTodoFilters();
  }

  function searchTodoList(q) {
    currentTodoSearchQuery = (q || "").trim().toLowerCase();
    applyTodoFilters();
  }

  function applyTodoFilters() {
    let filtered = allReports;
    if (currentTodoFilter !== "all") {
      filtered = filtered.filter(r => r.status === currentTodoFilter);
    }
    if (currentTodoRwFilter !== "all") {
      filtered = filtered.filter(r => r.rw === currentTodoRwFilter);
    }
    if (currentTodoPeriodFilter !== "all" && typeof filterReportsByPeriod === "function") {
      filtered = filterReportsByPeriod(filtered, currentTodoPeriodFilter);
    }
    if (currentTodoSearchQuery) {
      filtered = filtered.filter(r =>
        (r.judul && r.judul.toLowerCase().includes(currentTodoSearchQuery)) ||
        (r.jenisPekerjaan && r.jenisPekerjaan.toLowerCase().includes(currentTodoSearchQuery)) ||
        (r.alamat && r.alamat.toLowerCase().includes(currentTodoSearchQuery)) ||
        (r.petugas && r.petugas.toLowerCase().includes(currentTodoSearchQuery)) ||
        (r.noPekerjaan && r.noPekerjaan.toLowerCase().includes(currentTodoSearchQuery)) ||
        (r.id && r.id.toLowerCase().includes(currentTodoSearchQuery))
      );
    }
    renderTodoList(filtered);
  }

  async function downloadLaporanExcel() {
    let reports = allReports && allReports.length ? allReports : (typeof getLaporanDB === "function" ? getLaporanDB() : []);
    
    // Terapkan filter periode yang aktif saat ini
    const activePeriod = currentTodoPeriodFilter || "all";
    if (activePeriod !== "all" && typeof filterReportsByPeriod === "function") {
      reports = filterReportsByPeriod(reports, activePeriod);
    }

    if (!reports || reports.length === 0) {
      showToast("Tidak ada data laporan untuk periode " + activePeriod + " untuk diunduh.", "warning");
      return;
    }

    if (typeof ExcelJS === "undefined") {
      showToast("Modul ExcelJS sedang dimuat, mohon coba sesaat lagi...", "warning");
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

    showToast("Sedang menyusun berkas Excel (.xlsx) untuk periode: " + (periodNames[activePeriod] || activePeriod) + "...", "info");

    try {
      const wb = new ExcelJS.Workbook();
      wb.creator = "SILAP Kelurahan Pekayon";
      wb.lastModifiedBy = (typeof PHP_CURRENT_USER !== "undefined" && PHP_CURRENT_USER.name) ? PHP_CURRENT_USER.name : "Administrator PPSU";
      wb.created = new Date();
      wb.modified = new Date();

      const ws = wb.addWorksheet("Rekap PPSU", {
        views: [{ state: "frozen", ySplit: 4, xSplit: 0 }],
        pageSetup: {
          orientation: "landscape",
          fitToWidth: 1,
          fitToHeight: 0,
          paperSize: 9
        }
      });

      // Row 1: Title
      ws.mergeCells("A1:O1");
      const t1 = ws.getCell("A1");
      t1.value = "REKAP LAPORAN OPERASIONAL PPSU – KELURAHAN PEKAYON";
      t1.font = { name: "Arial", size: 14, bold: true, color: { argb: "FFEA580C" } };
      t1.alignment = { vertical: "middle", horizontal: "left" };
      ws.getRow(1).height = 26;

      // Row 2: Subtitle
      ws.mergeCells("A2:O2");
      const t2 = ws.getCell("A2");
      t2.value = "Master Rekapitulasi Penanganan Prasarana Wilayah 10 RW dengan Dokumentasi Foto Lapangan.";
      t2.font = { name: "Arial", size: 10, italic: true, color: { argb: "FF64748B" } };
      t2.alignment = { vertical: "middle", horizontal: "left" };
      ws.getRow(2).height = 18;

      ws.getRow(3).height = 8;

      // Row 4: Headers
      const headers = [
        "Periode Minggu", "RW", "Kategori", "Jenis Pekerjaan",
        "Judul", "Target Penyelesaian (m)", "Petugas", "Regu", "No HP", "Jml Petugas",
        "Alamat", "Catatan / Kendala", "0%", "50%", "100%"
      ];
      const colWidths = [32, 9, 16, 32, 42, 22, 28, 14, 18, 13, 38, 42, 20, 20, 20];

      const headerRow = ws.getRow(4);
      headerRow.height = 32;
      headerRow.values = headers;

      headers.forEach((h, i) => {
        const colIdx = i + 1;
        const col = ws.getColumn(colIdx);
        col.width = colWidths[i] || 18;
        const cell = ws.getCell(4, colIdx);
        cell.font = { name: "Arial", size: 11, bold: true, color: { argb: "FFFFFFFF" } };
        cell.fill = {
          type: "pattern",
          pattern: "solid",
          fgColor: { argb: "FFEA580C" } // Warna Oranye Khas Website PPSU
        };
        cell.alignment = { vertical: "middle", horizontal: "center", wrapText: true };
        cell.border = {
          top: { style: "thin", color: { argb: "FFFDBA74" } },
          bottom: { style: "medium", color: { argb: "FFC2410C" } },
          left: { style: "thin", color: { argb: "FFFDBA74" } },
          right: { style: "thin", color: { argb: "FFFDBA74" } }
        };
      });

      ws.autoFilter = {
        from: { row: 4, column: 1 },
        to: { row: 4, column: headers.length }
      };

      // Helper date formatter DD MMM YYYY
      function formatTanggalExcel(tglStr) {
        if (!tglStr) return "-";
        if (/^\d{1,2}\s+[A-Za-z]{3}\s+\d{4}$/.test(String(tglStr).trim())) return String(tglStr).trim();
        const d = new Date(tglStr);
        if (!isNaN(d.getTime())) {
          const months = ["Jan", "Feb", "Mar", "Apr", "Mei", "Jun", "Jul", "Agu", "Sep", "Okt", "Nov", "Des"];
          const day = String(d.getDate()).padStart(2, "0");
          return `${day} ${months[d.getMonth()]} ${d.getFullYear()}`;
        }
        return String(tglStr);
      }

      // Populate Data Rows
      reports.forEach((r, rIdx) => {
        const rowNum = 5 + rIdx;
        const wsRow = ws.getRow(rowNum);
        wsRow.height = 72;

        const isEven = rIdx % 2 === 1;
        const bgArgb = isEven ? "FFFFF7ED" : "FFFFFFFF"; // Zebra striping warna website (Orange-50 & Putih)

        const prgVal = r.progres !== undefined ? Number(r.progres) : (r.status === "selesai" ? 100 : (r.status === "proses" ? 50 : 0));
        const statusLabel = r.status === "selesai" ? "Selesai" : (r.status === "proses" ? "Dalam Proses" : "Belum Dikerjakan");
        const katLabel = (KATEGORI_INFO && KATEGORI_INFO[r.kat] && KATEGORI_INFO[r.kat].label) ? KATEGORI_INFO[r.kat].label : (r.kat || "Prasarana");
        const rawTarget = r.targetMeter || r.target_meter || r.panjangTarget;
        const targetMtr = rawTarget ? (String(rawTarget).includes("m") ? String(rawTarget) : `${rawTarget} m`) : "-";

        const rowValues = [
          r.minggu || (r.tanggal ? `Laporan ${formatTanggalExcel(r.tanggal)}` : "Laporan Mingguan"),
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
          "-",
          "-",
          "-"
        ];
        wsRow.values = rowValues;

        // Styling cells
        for (let c = 1; c <= headers.length; c++) {
          const cell = ws.getCell(rowNum, c);
          cell.font = { name: "Arial", size: 10, color: { argb: "FF0F172A" } };
          cell.fill = {
            type: "pattern",
            pattern: "solid",
            fgColor: { argb: bgArgb }
          };
          cell.border = {
            top: { style: "thin", color: { argb: "FFFED7AA" } },
            bottom: { style: "thin", color: { argb: "FFFED7AA" } },
            left: { style: "thin", color: { argb: "FFFED7AA" } },
            right: { style: "thin", color: { argb: "FFFED7AA" } }
          };

          const isCenter = [2, 6, 8, 9, 10, 13, 14, 15].includes(c);
          cell.alignment = {
            vertical: "middle",
            horizontal: isCenter ? "center" : "left",
            wrapText: true
          };
        }

        // Embedding Photos for col 13 (0%), 14 (50%), 15 (100%)
        const tahapConfigs = [
          { stage: 0, col: 13, allowed: prgVal >= 0 && r.status !== "belum" || (r.foto && r.foto[0] && r.foto[0].src) },
          { stage: 50, col: 14, allowed: prgVal >= 50 },
          { stage: 100, col: 15, allowed: prgVal >= 100 || r.status === "selesai" }
        ];

        tahapConfigs.forEach((cfg, pIdx) => {
          if (!cfg.allowed) {
            ws.getCell(rowNum, cfg.col).value = "-";
            return;
          }

          let photoDataUrl = null;
          if (r.foto && r.foto[pIdx] && r.foto[pIdx].src && r.foto[pIdx].src.startsWith("data:image")) {
            photoDataUrl = r.foto[pIdx].src;
          } else if (typeof generateFieldPhoto === "function") {
            try {
              photoDataUrl = generateFieldPhoto(
                cfg.stage,
                `${katLabel} - ${r.jenisPekerjaan || r.judul || "Pekerjaan Lapangan"}`,
                r.alamat || "Kelurahan Pekayon",
                r.gpsLat || -6.338520,
                r.gpsLng || 106.865410,
                r.petugas || "Petugas PPSU"
              );
            } catch(ePhoto) {
              console.warn("Gagal membuat foto canvas:", ePhoto);
            }
          }

          if (photoDataUrl && photoDataUrl.startsWith("data:image")) {
            try {
              const ext = photoDataUrl.startsWith("data:image/jpeg") || photoDataUrl.startsWith("data:image/jpg") ? "jpeg" : "png";
              const cleanBase64 = photoDataUrl.replace(/^data:image\/[a-zA-Z]+;base64,/, "");
              const imageId = wb.addImage({
                base64: cleanBase64,
                extension: ext
              });
              ws.addImage(imageId, {
                tl: { col: cfg.col - 1 + 0.05, row: rowNum - 1 + 0.06 },
                ext: { width: 110, height: 62 }
              });
              ws.getCell(rowNum, cfg.col).value = "";
            } catch(errAdd) {
              console.warn("Gagal menyematkan foto ke worksheet:", errAdd);
              ws.getCell(rowNum, cfg.col).value = "-";
            }
          } else {
            ws.getCell(rowNum, cfg.col).value = "-";
          }
        });
      });

      const buffer = await wb.xlsx.writeBuffer();
      const blob = new Blob([buffer], { type: "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" });
      const url = URL.createObjectURL(blob);
      const link = document.createElement("a");
      const today = new Date().toISOString().slice(0, 10);
      const periodLabelMap = {
        "all": "Semua",
        "today": "Hari_Ini_" + today,
        "yesterday": "Kemarin",
        "2days": "2_Hari_Lalu",
        "3days": "3_Hari_Lalu",
        "7days": "7_Hari",
        "14days": "14_Hari",
        "30days": "30_Hari"
      };
      const periodTag = periodLabelMap[activePeriod] || activePeriod;
      link.setAttribute("href", url);
      link.setAttribute("download", `Rekap_Penanganan_PPSU_${periodTag}_dengan_Foto.xlsx`);
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
      URL.revokeObjectURL(url);

      showToast("Berhasil! Berkas Excel resmi (" + (periodNames[activePeriod] || "Periode Aktif") + ") telah diunduh.", "success");
    } catch(err) {
      console.error("downloadLaporanExcel error:", err);
      showToast("Terjadi kendala saat membuat file Excel: " + (err.message || err), "error");
    }
  }

  let currentRwFilter = "all";
  let currentMingguFilter = "all";
  let currentSearchQuery = "";

  function jumpToCategory(kat) {
    switchAdminTab('tugas');
    const btn = document.querySelector(`[onclick*="filterTugasCategory('${kat}'"]`);
    filterTugasCategory(kat, btn);
  }

  function jumpToRwFilter(rw) {
    const rwSelect = document.getElementById("filter-rw-select") || document.getElementById("todo-filter-rw-select");
    if (rwSelect) rwSelect.value = rw;
    if (typeof filterTodoByRw === "function") {
      filterTodoByRw(rw);
    } else {
      filterReportsByRw(rw);
    }
    const feed = document.querySelector(".dash-recent-section") || document.querySelector(".todo-filter-tabs");
    if (feed) feed.scrollIntoView({ behavior: "smooth", block: "start" });
  }

  function filterReports(status) {
    currentFilter = status;
    document.querySelectorAll(".rf-btn, .todo-tab-btn").forEach(btn => btn.classList.remove("active"));
    const activeBtn = document.getElementById("rf-" + status) || document.getElementById("tb-" + status);
    if (activeBtn) activeBtn.classList.add("active");
    applyAdminFilters();
  }

  function filterReportsByRw(rw) {
    currentRwFilter = rw;
    applyAdminFilters();
  }

  function filterReportsByMinggu(minggu) {
    currentMingguFilter = minggu;
    applyAdminFilters();
  }

  function searchReports(query) {
    currentSearchQuery = (query || "").trim().toLowerCase();
    applyAdminFilters();
  }

  function applyAdminFilters() {
    let filtered = allReports;

    if (currentFilter !== "all") {
      filtered = filtered.filter(r => r.status === currentFilter);
    }
    if (currentRwFilter !== "all") {
      filtered = filtered.filter(r => r.rw === currentRwFilter);
    }
    if (currentMingguFilter !== "all") {
      filtered = filtered.filter(r => r.minggu === currentMingguFilter);
    }
    if (currentSearchQuery) {
      filtered = filtered.filter(r =>
        (r.judul && r.judul.toLowerCase().includes(currentSearchQuery)) ||
        (r.jenisPekerjaan && r.jenisPekerjaan.toLowerCase().includes(currentSearchQuery)) ||
        (r.alamat && r.alamat.toLowerCase().includes(currentSearchQuery)) ||
        (r.lokasiDetail && r.lokasiDetail.toLowerCase().includes(currentSearchQuery)) ||
        (r.petugas && r.petugas.toLowerCase().includes(currentSearchQuery)) ||
        (r.regu && r.regu.toLowerCase().includes(currentSearchQuery)) ||
        (r.noPekerjaan && r.noPekerjaan.toLowerCase().includes(currentSearchQuery)) ||
        (r.id && r.id.toLowerCase().includes(currentSearchQuery)) ||
        (r.kendala && r.kendala.toLowerCase().includes(currentSearchQuery))
      );
    }

    renderReportsList(filtered);
    renderTugasList(filtered);
  }

  // Render Reports List
  function renderReportsList(list) {
    const container = document.getElementById("recent-list-container") || document.getElementById("todo-list-container");
    if (container) {
      if (!list || list.length === 0) {
        container.innerHTML = '<div style="text-align:center;padding:2.5rem;color:#64748B;">Tidak ada laporan pada kriteria filter ini.</div>';
      } else {
        container.innerHTML = list.map(lap => renderTaskCardHtml(lap)).join("");
      }
    }
  }

  // Render Tugas List
  function renderTugasList(list) {
    const container = document.getElementById("tugas-list-container");
    if (!container) return;
    renderReportsListToContainer(list, container);
  }

  let currentTugasStatusFilter = "all";
  let currentTugasKatFilter = "all";
  let currentTugasReguFilter = "all";

  function openTugasWithStatus(status) {
    switchAdminTab('tugas');
    filterTugasStatus(status);
  }

  function filterTugasStatus(status, btn) {
    currentTugasStatusFilter = status || "all";
    const container = document.getElementById("tugas-filter-status-container");
    if (container) {
      container.querySelectorAll(".rf-btn").forEach(b => {
        const isMatch = (currentTugasStatusFilter === 'all' && (b.id === 'tf-all' || b.textContent.includes('Semua'))) ||
                        (currentTugasStatusFilter === 'proses' && (b.id === 'tf-proses' || b.textContent.includes('Penanganan'))) ||
                        (currentTugasStatusFilter === 'selesai' && (b.id === 'tf-selesai' || b.textContent.includes('Selesai'))) ||
                        (currentTugasStatusFilter === 'belum' && (b.id === 'tf-belum' || b.textContent.includes('Verifikasi')));
        b.classList.toggle("active", Boolean(isMatch));
      });
    }
    applyTugasFilters();
  }

  function getDaftarReguFilter() {
    const set = new Set();
    // 1. Ambil seluruh regu dari data regu aktif (allRegu)
    (allRegu || []).forEach(rg => {
      if (rg.nama && rg.nama.trim()) set.add(rg.nama.trim());
    });
    // 2. Ambil seluruh regu yang ada pada data laporan (allReports)
    (allReports || []).forEach(r => {
      if (r.regu && r.regu.trim()) set.add(r.regu.trim());
    });
    // 3. Masukkan seluruh regu lengkap: Regu 1 s/d Regu 5, Regu Jalan, Regu Taman, Regu Kantor
    ["Regu 1", "Regu 2", "Regu 3", "Regu 4", "Regu 5", "Regu Jalan", "Regu Taman", "Regu Kantor"].forEach(rg => {
      set.add(rg);
    });

    // Urutkan dengan rapi
    return Array.from(set).sort((a, b) => {
      return a.localeCompare(b, undefined, { numeric: true, sensitivity: 'base' });
    });
  }

  function matchRegu(taskRegu, filterRegu) {
    if (!filterRegu || filterRegu === "all") return true;
    if (!taskRegu) return false;
    const t = taskRegu.toLowerCase().trim();
    const f = filterRegu.toLowerCase().trim();
    if (t === f) return true;
    if ((f.includes("jalan") || f === "regu 1") && (t.includes("jalan") || t === "regu 1")) return true;
    if ((f.includes("taman") || f === "regu 2") && (t.includes("taman") || t === "regu 2")) return true;
    if ((f.includes("kantor") || f === "regu 3") && (t.includes("kantor") || t === "regu 3")) return true;
    if (t.includes(f) || f.includes(t)) return true;
    return false;
  }

  function applyTugasFilters() {
    const filtered = allReports.filter(r => {
      const matchStatus = currentTugasStatusFilter === "all" || r.status === currentTugasStatusFilter;
      const matchKat = currentTugasKatFilter === "all" || r.kat === currentTugasKatFilter;
      const matchRg = matchRegu(r.regu, currentTugasReguFilter);
      return matchStatus && matchKat && matchRg;
    });
    renderTugasList(filtered);
    updateTugasFilterCounts();
  }

  function updateTugasFilterCounts() {
    const cAll = allReports.length;
    const cProses = allReports.filter(r => r.status === 'proses').length;
    const cSelesai = allReports.filter(r => r.status === 'selesai').length;
    const cBelum = allReports.filter(r => r.status === 'belum').length;
    const bAll = document.getElementById("tf-all");
    const bProses = document.getElementById("tf-proses");
    const bSelesai = document.getElementById("tf-selesai");
    const bBelum = document.getElementById("tf-belum");
    if (bAll) bAll.textContent = `Semua Status (${cAll})`;
    if (bProses) bProses.textContent = `Dalam Penanganan (${cProses})`;
    if (bSelesai) bSelesai.textContent = `Selesai (${cSelesai})`;
    if (bBelum) bBelum.textContent = `Menunggu Verifikasi (${cBelum})`;
  }

  function filterTugasCategory(kat, btn) {
    currentTugasKatFilter = kat;
    if (btn) {
      btn.parentElement.querySelectorAll(".rf-btn").forEach(b => b.classList.remove("active"));
      btn.classList.add("active");
    } else {
      const container = document.getElementById("tugas-filter-category-container");
      if (container) {
        container.querySelectorAll(".rf-btn").forEach(b => {
          const isMatch = (kat === 'all' && b.textContent === 'Semua Bidang') || (b.getAttribute("onclick") && b.getAttribute("onclick").includes(`'${kat}'`));
          b.classList.toggle("active", Boolean(isMatch));
        });
      }
    }
    applyTugasFilters();
  }

  function filterTugasRegu(regu) {
    currentTugasReguFilter = regu;
    const currentTab = document.querySelector(".admin-nav-item.active");
    if (!currentTab || currentTab.id !== "nav-btn-tugas") {
      switchAdminTab("tugas");
    }
    const select = document.getElementById("tugas-filter-regu-select");
    if (select && select.value !== regu) {
      select.value = regu;
    }
    applyTugasFilters();
  }

  function renderTugasReguFilters() {
    const container = document.getElementById("tugas-filter-regu-container");
    if (!container) return;
    const reguList = getDaftarReguFilter();

    let optionsHtml = `<option value="all" ${currentTugasReguFilter === 'all' ? 'selected' : ''}>Semua Regu (${allReports.length} Tugas)</option>`;
    reguList.forEach(rg => {
      const count = allReports.filter(r => matchRegu(r.regu, rg)).length;
      const isSelected = currentTugasReguFilter === rg ? 'selected' : '';
      optionsHtml += `<option value="${rg}" ${isSelected}>${rg} (${count} Tugas)</option>`;
    });

    container.innerHTML = `
      <select id="tugas-filter-regu-select" onchange="filterTugasRegu(this.value)" class="form-select" style="min-width:260px;padding:8px 14px;border-radius:10px;border:1.5px solid #CBD5E1;font-size:0.86rem;font-weight:700;color:#0F172A;background:#FFFFFF;outline:none;cursor:pointer;box-shadow:0 1px 3px rgba(0,0,0,0.04);">
        ${optionsHtml}
      </select>
    `;
  }

  function renderReportsListToContainer(list, container) {
    if (!list || list.length === 0) {
      container.innerHTML = '<div style="text-align:center;padding:2.5rem;color:#64748B;">Tidak ada tugas pada kategori ini.</div>';
      return;
    }
    container.innerHTML = list.map(lap => renderTaskCardHtml(lap)).join("");
  }

  // Render Verifikasi List
  function renderVerifikasiList(list) {
    const container = document.getElementById("verifikasi-list-container");
    if (!container) return;

    const pending = list.filter(r => r.status === "belum" || (r.status !== "selesai" && parseInt(r.progres, 10) >= 100));
    if (pending.length === 0) {
      container.innerHTML = '<div style="text-align:center;background:#FFFFFF;border:1px solid #E2E8F0;border-radius:14px;padding:3rem;color:#64748B;">Semua laporan telah diverifikasi dan disetujui.</div>';
      return;
    }

    container.innerHTML = pending.map(lap => {
      const ki = KATEGORI_INFO[lap.kat] || { label: "Prasarana" };
      return `
        <div style="background:#FFFFFF;border:1.5px solid #E2E8F0;border-radius:14px;padding:20px;display:flex;align-items:center;justify-content:space-between;gap:18px;flex-wrap:wrap;">
          <div style="flex:1;min-width:280px;">
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px;flex-wrap:wrap;">
              <span class="kat-pill ${ki.color || 'orange'}">${ki.label}</span>
              <span class="badge-rw">${lap.rw || 'RW 01'}</span>
              <span class="badge-minggu">${lap.minggu || 'Laporan Mingguan'}</span>
              <span class="badge-regu" onclick="event.stopPropagation(); filterTugasRegu('${lap.regu}')" style="cursor:pointer;" title="Klik untuk saring tugas regu ini">${lap.regu || 'Regu PPSU'}</span>
              <span style="font-size:0.78rem;font-weight:800;color:#FF5416;">#${lap.noPekerjaan || lap.id}</span>
            </div>
            <h3 style="font-size:1.02rem;font-weight:800;color:#0F172A;margin:0 0 4px 0;">${lap.jenisPekerjaan || lap.judul}</h3>
            <div style="font-size:0.80rem;color:#475569;margin-bottom:6px;line-height:1.45;">${getDeskripsiPekerjaan(lap)}</div>
            <div style="font-size:0.82rem;color:#64748B;line-height:1.5;">
              Lokasi: <strong>${lap.alamat}</strong> · Petugas: <strong>${lap.petugas} (${lap.jumlahPetugas || 4} orang)</strong> · Telp: <strong>${lap.noHp || '-'}</strong>
            </div>
            ${lap.kendala ? `<div style="margin-top:6px;font-size:0.78rem;color:#92400E;">Kendala: ${lap.kendala}</div>` : ''}
          </div>
          <div style="display:flex;gap:10px;">
            <button type="button" class="btn-lihat-detail" onclick="openDetailModal('${lap.id}')">Tinjau Foto (${(lap.foto || []).filter(f => f && f.src && f.src.trim() !== '').length}/3)</button>
            ${((parseInt(lap.progres, 10) || 0) >= 100 && (lap.foto || []).filter(f => f && f.src && f.src.trim() !== '').length >= 3)
              ? `<button type="button" class="admin-btn-primary" onclick="quickVerify('${lap.id}')">Setujui Sekarang</button>`
              : `<button type="button" class="admin-btn-primary" onclick="quickVerify('${lap.id}')" style="background:#94A3B8;border-color:#94A3B8;cursor:not-allowed;opacity:0.75;" title="Menunggu 3 foto & progres 100%">Belum Lengkap</button>`}
          </div>
        </div>
      `;
    }).join("");
  }

  function formatVerifiedTimestamp() {
    const now = new Date();
    const days = ["Minggu", "Senin", "Selasa", "Rabu", "Kamis", "Jumat", "Sabtu"];
    const months = ["Jan", "Feb", "Mar", "Apr", "Mei", "Jun", "Jul", "Agu", "Sep", "Okt", "Nov", "Des"];
    const hh = String(now.getHours()).padStart(2, "0");
    const mm = String(now.getMinutes()).padStart(2, "0");
    return `${days[now.getDay()]}, ${now.getDate()} ${months[now.getMonth()]} ${now.getFullYear()}, ${hh}:${mm}`;
  }

  function quickVerify(id) {
    const lap = allReports.find(r => r.id === id);
    if (!lap) return;
    const validPhotos = (lap.foto || []).filter(f => f && f.src && typeof f.src === "string" && f.src.trim() !== "" && f.src !== "null" && f.src !== "undefined");
    const curProg = parseInt(lap.progres, 10) || 0;
    if (curProg < 100 || validPhotos.length < 3) {
      showToast(`Belum bisa disetujui! Petugas harus mengirim 3 foto lengkap & progres 100% (Saat ini: ${validPhotos.length}/3 foto, ${curProg}%).`, "warning");
      return;
    }
    lap.status = "selesai";
    lap.progres = 100;
    lap.tanggal = formatVerifiedTimestamp();
    saveLaporanDB(allReports);
    updateStats();
    renderVerifikasiList(allReports);
    renderTodoList(allReports);
    renderTugasList(allReports);
    renderReportsList(allReports);
    renderTaskCategoryStats();
    renderRekapTable(allReports);
    showToast("Laporan #" + (lap.noPekerjaan || id) + " berhasil diverifikasi dan disahkan.", "success");
  }

  // Render Rekap Table (Kategori Prasarana: Jalan, Taman, Saluran, Kebersihan, Kantor)
  // Render Rekap Table (Kategori Prasarana: Jalan, Taman, Saluran, Kebersihan, Kantor & Custom)
  function renderRekapTable(list) {
    const tbody = document.getElementById("rekap-table-body");
    if (!tbody) return;

    const katDB = getKategoriDB();
    const kats = Object.keys(katDB);
    const defaultKeys = ["jalan", "taman", "saluran", "kebersihan", "kantor"];

    tbody.innerHTML = kats.map(k => {
      const ki = katDB[k] || { label: k, color: "orange" };
      const items = (list || []).filter(r => r.kat === k);
      const total = items.length;
      const selesai = items.filter(r => r.status === "selesai").length;
      const proses = items.filter(r => r.status === "proses").length;
      const belum = items.filter(r => r.status === "belum").length;
      const pct = total > 0 ? Math.round((selesai / total) * 100) : 0;
      const isDefault = defaultKeys.includes(k);

      return `
        <tr>
          <td>
            <span class="kat-pill ${ki.color || 'orange'}">${ki.label}</span>
          </td>
          <td><strong>${total}</strong></td>
          <td><span style="color:#16A34A;font-weight:700;">${selesai}</span></td>
          <td><span style="color:#EA580C;font-weight:700;">${proses}</span></td>
          <td><span style="color:#D97706;font-weight:700;">${belum}</span></td>
          <td>
            <div style="display:flex;align-items:center;gap:8px;">
              <div style="flex:1;background:#E2E8F0;height:6px;border-radius:4px;overflow:hidden;max-width:120px;">
                <div style="background:#10B981;height:100%;width:${pct}%;"></div>
              </div>
              <span style="font-size:0.8rem;font-weight:700;color:#0F172A;">${pct}%</span>
            </div>
          </td>
          <td style="text-align:right;">
            <div style="display:inline-flex;align-items:center;gap:6px;justify-content:flex-end;">
              <button type="button" class="btn-lihat-detail" onclick="jumpToCategory('${k}')" style="padding:4px 10px;font-size:0.75rem;" title="Lihat daftar tugas pada subjek ini">
                Lihat Tugas
              </button>
              <button type="button" class="btn-lihat-detail" onclick="openEditSubjekModal('${k}')" style="padding:4px 10px;font-size:0.75rem;background:#F8FAFC;border:1px solid #CBD5E1;color:#1E293B;" title="Edit nama & warna subjek">
                Edit
              </button>
              ${!isDefault ? `
              <button type="button" class="btn-lihat-detail" onclick="hapusSubjekDirect('${k}')" style="padding:4px 10px;font-size:0.75rem;background:#FEF2F2;border:1px solid #FEE2E2;color:#DC2626;" title="Hapus bidang subjek ini">
                Hapus
              </button>
              ` : ''}
            </div>
          </td>
        </tr>
      `;
    }).join("");
  }

  function renderTugasCategoryFilters() {
    const container = document.getElementById("tugas-filter-category-container");
    if (!container) return;
    const katDB = getKategoriDB();
    let html = `<button type="button" class="rf-btn active" onclick="filterTugasCategory('all', this)">Semua Bidang</button>`;
    Object.keys(katDB).forEach(k => {
      const info = katDB[k];
      html += `<button type="button" class="rf-btn" onclick="filterTugasCategory('${k}', this)">${info.label || k}</button>`;
    });
    container.innerHTML = html;
  }

  function openTambahSubjekModal() {
    const modal = document.getElementById("modal-edit-subjek");
    if (!modal) return;
    document.getElementById("modal-subjek-title").textContent = "Tambah Bidang Subjek Baru";
    document.getElementById("subjek-key").value = "";
    document.getElementById("subjek-is-new").value = "1";
    document.getElementById("subjek-nama").value = "";
    document.getElementById("subjek-warna").value = "orange";
    modal.classList.remove("hidden");
    setTimeout(() => {
      const input = document.getElementById("subjek-nama");
      if (input) input.focus();
    }, 50);
  }

  function openEditSubjekModal(key) {
    const modal = document.getElementById("modal-edit-subjek");
    if (!modal) return;
    const katDB = getKategoriDB();
    const item = katDB[key] || { label: key, color: "orange" };

    document.getElementById("modal-subjek-title").textContent = "Edit Bidang Subjek Prasarana";
    document.getElementById("subjek-key").value = key;
    document.getElementById("subjek-is-new").value = "0";
    document.getElementById("subjek-nama").value = item.label || "";
    document.getElementById("subjek-warna").value = item.color || "orange";
    modal.classList.remove("hidden");
    setTimeout(() => {
      const input = document.getElementById("subjek-nama");
      if (input) input.focus();
    }, 50);
  }

  function closeEditSubjekModal() {
    const modal = document.getElementById("modal-edit-subjek");
    if (modal) modal.classList.add("hidden");
  }

  function handleSimpanSubjek(e) {
    e.preventDefault();
    const keyInput = document.getElementById("subjek-key").value.trim();
    const isNew = document.getElementById("subjek-is-new").value === "1";
    const nama = document.getElementById("subjek-nama").value.trim();
    const warna = document.getElementById("subjek-warna").value;

    if (!nama) {
      showToast("Nama subjek wajib diisi.", "error");
      return;
    }

    const katDB = getKategoriDB();
    let finalKey = keyInput;

    if (isNew) {
      finalKey = "kat_" + Date.now();
      katDB[finalKey] = { label: nama, color: warna, tags: [] };
      showToast("Bidang subjek baru berhasil ditambahkan.", "success");
    } else {
      if (!katDB[finalKey]) katDB[finalKey] = {};
      katDB[finalKey].label = nama;
      katDB[finalKey].color = warna;
      showToast("Bidang subjek berhasil diperbarui.", "success");
    }

    saveKategoriDB(katDB);
    closeEditSubjekModal();
    renderRekapTable(allReports);
    renderTugasCategoryFilters();
    renderTaskCategoryStats();
    renderTugasList(allReports);
  }

  function hapusSubjekDirect(key) {
    if (!confirm("Apakah Anda yakin ingin menghapus bidang subjek ini?")) return;
    const katDB = getKategoriDB();
    delete katDB[key];
    saveKategoriDB(katDB);
    showToast("Bidang subjek berhasil dihapus.", "success");
    renderRekapTable(allReports);
    renderTugasCategoryFilters();
    renderTaskCategoryStats();
    renderTugasList(allReports);
  }

  function downloadRekapCSV() {
    downloadLaporanExcel();
  }

  async function simpanPengaturanAdmin() {
    const btn = document.getElementById("btn-save-admin-cfg");
    const nameInp = document.getElementById("cfg-admin-name");
    const userInp = document.getElementById("cfg-admin-username");
    const curPassInp = document.getElementById("cfg-admin-cur-pass");
    const newPassInp = document.getElementById("cfg-admin-new-pass");
    const cfmPassInp = document.getElementById("cfg-admin-cfm-pass");

    const nama = nameInp ? nameInp.value.trim() : "";
    const username = userInp ? userInp.value.trim() : "";
    const current_password = curPassInp ? curPassInp.value : "";
    const new_password = newPassInp ? newPassInp.value : "";
    const confirm_password = cfmPassInp ? cfmPassInp.value : "";

    if (!current_password) {
      showToast("Mohon masukkan kata sandi saat ini untuk konfirmasi keamanan!", "warning");
      if (curPassInp) curPassInp.focus();
      return;
    }

    if (new_password) {
      if (new_password.length < 6) {
        showToast("Kata sandi baru minimal 6 karakter!", "warning");
        if (newPassInp) newPassInp.focus();
        return;
      }
      if (new_password !== confirm_password) {
        showToast("Konfirmasi kata sandi baru tidak cocok!", "error");
        if (cfmPassInp) cfmPassInp.focus();
        return;
      }
    }

    if (btn) {
      btn.disabled = true;
      btn.textContent = "Menyimpan...";
    }

    try {
      const resp = await fetch("api.php?action=update_admin_credentials", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ nama, username, current_password, new_password, confirm_password })
      });
      const res = await resp.json();

      if (res.success) {
        if (nama) {
          const userDisplay = document.getElementById("user-name-display");
          if (userDisplay) userDisplay.textContent = nama;
          const dashName = document.getElementById("dash-user-name");
          if (dashName) dashName.textContent = nama.split(" ")[0];
          const avatarEl = document.getElementById("user-avatar");
          if (avatarEl) {
            const parts = nama.split(" ");
            avatarEl.textContent = parts.length > 1 ? (parts[0][0] + parts[1][0]).toUpperCase() : parts[0][0].toUpperCase();
          }
        }
        if (curPassInp) curPassInp.value = "";
        if (newPassInp) newPassInp.value = "";
        if (cfmPassInp) cfmPassInp.value = "";
        showToast(res.message || "Pengaturan profil & kredensial berhasil disimpan.", "success");
      } else {
        showToast(res.error || "Gagal memperbarui pengaturan.", "error");
      }
    } catch (e) {
      showToast("Terjadi kendala jaringan saat menghubungi server.", "error");
    } finally {
      if (btn) {
        btn.disabled = false;
        btn.textContent = "Simpan Perubahan Akun";
      }
    }
  }

  const STANDARD_ROLES = ["Ketua Regu", "Anggota Regu"];

  function changeAnggotaRoleDirect(agtId, newRole) {
    const agt = allAnggota.find(a => a.id === agtId);
    if (!agt) return;

    agt.role = (newRole === "Ketua Regu") ? "Ketua Regu" : "Anggota Regu";

    const reg = allRegu.find(r => r.id === agt.reguId || (r.anggotaIds && r.anggotaIds.includes(agt.id)));

    if (newRole === "Ketua Regu") {
      if (reg) {
        // Tetapkan sebagai ketua regu tunggal di regunya
        reg.ketua = agt.nama;
        if (agt.hp) reg.noHp = agt.hp;
        if (!reg.anggotaIds) reg.anggotaIds = [];
        if (!reg.anggotaIds.includes(agt.id)) reg.anggotaIds.push(agt.id);
        agt.reguId = reg.id;

        // 1 REGU HANYA BOLEH 1 KETUA REGU:
        // Turunkan seluruh anggota lainnya di regu ini menjadi 'Anggota Regu'
        allAnggota.forEach(other => {
          if (other.id !== agt.id && (other.reguId === reg.id || (reg.anggotaIds && reg.anggotaIds.includes(other.id)))) {
            other.role = "Anggota Regu";
          }
        });
        saveReguDB(allRegu);
      }
    } else {
      // Diturunkan menjadi Anggota Regu
      if (reg && reg.ketua && reg.ketua.trim().toLowerCase() === (agt.nama || "").trim().toLowerCase()) {
        reg.ketua = "";
        saveReguDB(allRegu);
      }
    }

    saveAnggotaDB(allAnggota);
    showToast(`Role ${agt.nama} berhasil diperbarui: ${agt.role}`, 'success');

    renderAnggotaTable(allAnggota);
    renderReguCards(allRegu);
    renderDashboardReguTable();
  }

  // Render Anggota PPSU Table & Mobile Cards
  function renderAnggotaTable(list) {
    const tbody = document.getElementById("anggota-table-body");
    const mobileContainer = document.getElementById("anggota-mobile-cards");

    const tableRows = [];
    const mobileCards = [];

    list.forEach(a => {
      const reg = allRegu.find(r => r.id === a.reguId || (r.anggotaIds && r.anggotaIds.includes(a.id)));
      let isKetua = false;
      if (reg && reg.ketua && reg.ketua.trim()) {
        isKetua = (reg.ketua.trim().toLowerCase() === (a.nama || "").trim().toLowerCase());
      } else {
        isKetua = (a.role === "Ketua Regu");
      }
      const currentRole = isKetua ? "Ketua Regu" : "Anggota Regu";
      a.role = currentRole;

      const roleSelectHtml = `
        <select onchange="changeAnggotaRoleDirect('${a.id}', this.value)" style="padding:6px 10px;border-radius:8px;border:1.5px solid ${isKetua ? '#FDBA74' : '#CBD5E1'};background:${isKetua ? '#FFF7ED' : '#F8FAFC'};color:${isKetua ? '#C2410C' : '#334155'};font-weight:${isKetua ? '700' : '600'};font-size:0.8rem;cursor:pointer;outline:none;height:38px;">
          <option value="Anggota Regu" ${currentRole === 'Anggota Regu' ? 'selected' : ''}>Anggota Regu</option>
          <option value="Ketua Regu" ${currentRole === 'Ketua Regu' ? 'selected' : ''}>Ketua Regu</option>
        </select>
      `;

      const cleanHp = typeof formatPhone08 === "function" ? formatPhone08(a.hp) : (a.hp || "").replace(/-/g, "").replace(/^\+?62/, "0").trim();
      const reguLabel = reg ? reg.nama : (a.regu && a.regu !== "Belum Ditugaskan" ? a.regu : "Belum Ditugaskan");
      const reguBadgeHtml = reg
        ? `<span style="display:inline-flex;align-items:center;padding:4px 10px;border-radius:999px;font-size:0.76rem;font-weight:700;background:#EFF6FF;color:#1D4ED8;border:1px solid #BFDBFE;">${reguLabel}</span>`
        : `<span style="display:inline-flex;align-items:center;padding:4px 10px;border-radius:999px;font-size:0.76rem;font-weight:600;background:#F1F5F9;color:#64748B;">Belum Ditugaskan</span>`;

      // 1. Desktop Table Row
      tableRows.push(`
        <tr>
          <td>
            <div style="display:flex;align-items:center;gap:10px;">
              <div style="width:36px;height:36px;border-radius:50%;background:#F1F5F9;display:flex;align-items:center;justify-content:center;font-weight:700;color:#0F172A;font-size:0.85rem;flex-shrink:0;">
                ${a.nama.charAt(0).toUpperCase()}
              </div>
              <strong style="color:#0F172A;font-size:0.9rem;">${a.nama}</strong>
            </div>
          </td>
          <td>${reguBadgeHtml}</td>
          <td>${roleSelectHtml}</td>
          <td>
            <span style="color:#0F172A;font-weight:700;font-size:0.86rem;user-select:all;cursor:text;letter-spacing:0.2px;">${cleanHp || "-"}</span>
          </td>
          <td style="text-align:right;white-space:nowrap;">
            <button type="button" onclick="openEditAnggotaModal('${a.id}')" title="Edit Anggota" style="border:1px solid #CBD5E1;background:#FFFFFF;color:#334155;border-radius:6px;cursor:pointer;font-size:0.78rem;font-weight:600;padding:4px 10px;margin-right:4px;">
              Edit
            </button>
            <button type="button" onclick="hapusAnggotaDirect('${a.id}')" title="Hapus Anggota" style="border:1px solid #FECACA;background:#FEF2F2;color:#DC2626;border-radius:6px;cursor:pointer;font-size:0.78rem;font-weight:600;padding:4px 10px;">
              Hapus
            </button>
          </td>
        </tr>
      `);

      // 2. Mobile Clean Uniform Card
      mobileCards.push(`
        <div class="pmc-card">
          <div class="pmc-left">
            <div class="pmc-avatar">${a.nama.charAt(0).toUpperCase()}</div>
            <div class="pmc-info">
              <div class="pmc-name" title="${a.nama}">${a.nama}</div>
              <div style="margin:3px 0 4px 0;">${reguBadgeHtml}</div>
              <div class="pmc-phone">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                <span>${cleanHp || "-"}</span>
              </div>
            </div>
          </div>
          <div class="pmc-right">
            ${roleSelectHtml}
            <button type="button" onclick="openEditAnggotaModal('${a.id}')" class="pmc-del-btn" style="background:#F8FAFC;border-color:#CBD5E1;color:#334155;" title="Edit Anggota" aria-label="Edit Anggota">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
            </button>
            <button type="button" onclick="hapusAnggotaDirect('${a.id}')" class="pmc-del-btn" title="Hapus Anggota" aria-label="Hapus Anggota">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M3 6h18M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
            </button>
          </div>
        </div>
      `);
    });

    if (tbody) tbody.innerHTML = tableRows.join("");
    if (mobileContainer) mobileContainer.innerHTML = mobileCards.join("");
  }

  // Render Regu Cards with Full CRUD
  function renderReguCards(reguList) {
    const container = document.getElementById("regu-cards-container");
    if (!container) return;

    if (!reguList || reguList.length === 0) {
      container.innerHTML = `
        <div style="grid-column:1/-1;text-align:center;padding:40px;background:#FFFFFF;border:1px dashed #CBD5E1;border-radius:14px;color:#64748B;">
          Belum ada regu lapangan yang terdaftar. Klik tombol <strong>+ Buat Regu Baru</strong> di atas untuk membuat regu.
        </div>
      `;
      return;
    }

    container.innerHTML = reguList.map(rg => {
      const members = allAnggota.filter(a => (rg.anggotaIds && rg.anggotaIds.includes(a.id)) || a.reguId === rg.id);
      // Deteksi nama ketua regu: dari rg.ketua ATAU anggota di regu ini yang role-nya "Ketua Regu"
      const ketuaMember = members.find(m => m.role === "Ketua Regu" || (rg.ketua && (m.nama || "").trim().toLowerCase() === rg.ketua.trim().toLowerCase()));
      const displayKetua = (rg.ketua && rg.ketua.trim() !== "-" && rg.ketua.trim() !== "") ? rg.ketua : (ketuaMember ? ketuaMember.nama : "-");
      if (displayKetua !== "-" && (!rg.ketua || rg.ketua.trim() === "-" || rg.ketua.trim() === "")) {
        rg.ketua = displayKetua;
      }

      return `
        <div class="regu-card">
          <div>
            <div class="regu-card-header" style="align-items:flex-start;">
              <div>
                <div class="regu-title">${rg.nama}</div>
                <div class="regu-subtitle" style="margin-top:2px;">Zona: ${rg.wilayah || 'Kelurahan Pekayon'}</div>
              </div>
              <div style="display:flex;align-items:center;gap:6px;">
                <span class="badge-status badge-selesai"><span class="status-dot"></span>${members.length} Personel</span>
                <button type="button" onclick="hapusReguDirect('${rg.id}')" title="Hapus Regu" style="border:1px solid #FECACA;background:#FEF2F2;border-radius:6px;padding:3px 8px;font-size:0.75rem;font-weight:600;color:#DC2626;cursor:pointer;">
                  Hapus
                </button>
              </div>
            </div>
            
            <div style="margin-top:12px;padding:10px 14px;background:#FFF7ED;border-radius:10px;border:1px solid #FFEDD5;font-size:0.85rem;display:flex;justify-content:space-between;align-items:center;">
              <div>
                <span style="color:#C2410C;font-weight:600;">Ketua Regu:</span>
                <strong style="color:#9A3412;margin-left:4px;">${displayKetua}</strong>
              </div>
            </div>

            <div style="margin-top:14px;">
              <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;">
                <div style="font-size:0.75rem;font-weight:700;color:#64748B;text-transform:uppercase;letter-spacing:0.04em;">Daftar Anggota Tim:</div>
                <span style="font-size:0.72rem;color:#94A3B8;">${members.length} Orang</span>
              </div>
              <div class="regu-members-list">
                ${members.length === 0 ? '<span style="font-size:0.78rem;color:#94A3B8;font-style:italic;">Belum ada anggota terdaftar</span>' : ''}
                ${members.map(m => {
                  const isKetua = (m.nama === displayKetua || m.role === "Ketua Regu");
                  return `
                  <span class="regu-member-chip" style="display:inline-flex;align-items:center;gap:6px;${isKetua ? 'background:#FFF7ED;border:1px solid #FED7AA;color:#C2410C;font-weight:700;' : ''}">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                    <span>${m.nama} ${isKetua ? '(Ketua)' : ''}</span>
                    <button type="button" onclick="keluarkanAnggotaDariRegu('${rg.id}', '${m.id}')" title="Keluarkan dari regu ini" style="border:none;background:none;color:#94A3B8;cursor:pointer;padding:0 2px;display:inline-flex;align-items:center;">
                      <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                    </button>
                  </span>
                `;
                }).join("")}
              </div>
            </div>
          </div>

          <div style="margin-top:16px;border-top:1px solid #F1F5F9;padding-top:12px;">
            <button type="button" class="btn-lihat-detail" onclick="openEditReguModal('${rg.id}')" style="width:100%;justify-content:center;background:#FFF7ED;border:1.5px solid #FED7AA;font-size:0.86rem;font-weight:700;color:#C2410C;padding:10px 14px;border-radius:10px;cursor:pointer;display:flex;align-items:center;gap:7px;box-shadow:0 1px 2px rgba(194,65,12,0.06);">
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M12 20h9"></path><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path></svg>
              <span>Kelola Regu & Anggota Tim</span>
            </button>
          </div>
        </div>
      `;
    }).join("");
  }

  // Modals & Forms Logic
  function openTambahAnggotaModal(preselectedReguId) {
    document.getElementById("form-tambah-anggota").reset();
    const editIdEl = document.getElementById("inp-agt-edit-id");
    if (editIdEl) editIdEl.value = "";
    const titleEl = document.getElementById("modal-agt-title");
    if (titleEl) titleEl.textContent = "Tambah Anggota PPSU";
    const btnEl = document.getElementById("btn-agt-submit");
    if (btnEl) btnEl.textContent = "Simpan Anggota";

    const reguSelect = document.getElementById("inp-agt-regu");
    if (reguSelect) {
      reguSelect.innerHTML = `<option value="">-- Belum Ditempatkan (Personel Bebas) --</option>` +
        allRegu.map(r => `<option value="${r.id}" ${r.id === preselectedReguId ? 'selected' : ''}>${r.nama}</option>`).join("");
      if (preselectedReguId) reguSelect.value = preselectedReguId;
    }
    document.getElementById("modal-tambah-anggota").classList.remove("hidden");
  }

  function openEditAnggotaModal(agtId) {
    const agt = allAnggota.find(a => a.id === agtId);
    if (!agt) return;

    const editIdEl = document.getElementById("inp-agt-edit-id");
    if (editIdEl) editIdEl.value = agt.id;
    const titleEl = document.getElementById("modal-agt-title");
    if (titleEl) titleEl.textContent = "Edit Data Anggota PPSU";
    const btnEl = document.getElementById("btn-agt-submit");
    if (btnEl) btnEl.textContent = "Perbarui Anggota";

    document.getElementById("inp-agt-nama").value = agt.nama || "";
    const roleEl = document.getElementById("inp-agt-role");
    if (roleEl) roleEl.value = agt.role === "Ketua Regu" ? "Ketua Regu" : "Anggota Regu";
    document.getElementById("inp-agt-hp").value = agt.hp || "";
    const bidangEl = document.getElementById("inp-agt-bidang");
    if (bidangEl && agt.bidang) bidangEl.value = agt.bidang;

    const currentRegu = allRegu.find(r => r.id === agt.reguId || (r.anggotaIds && r.anggotaIds.includes(agt.id)));
    const activeReguId = currentRegu ? currentRegu.id : (agt.reguId || "");
    const reguSelect = document.getElementById("inp-agt-regu");
    if (reguSelect) {
      reguSelect.innerHTML = `<option value="">-- Belum Ditempatkan (Personel Bebas) --</option>` +
        allRegu.map(r => `<option value="${r.id}" ${r.id === activeReguId ? 'selected' : ''}>${r.nama}</option>`).join("");
      reguSelect.value = activeReguId;
    }

    document.getElementById("modal-tambah-anggota").classList.remove("hidden");
  }

  function closeTambahAnggotaModal() {
    document.getElementById("modal-tambah-anggota").classList.add("hidden");
  }

  function handleSimpanAnggota(e) {
    e.preventDefault();
    const editIdEl = document.getElementById("inp-agt-edit-id");
    const editId = editIdEl ? editIdEl.value.trim() : "";
    const nama = document.getElementById("inp-agt-nama").value.trim();
    const roleEl = document.getElementById("inp-agt-role");
    const role = roleEl ? roleEl.value : "Anggota Regu";
    const hp = document.getElementById("inp-agt-hp").value.trim();
    const bidang = document.getElementById("inp-agt-bidang").value;
    const reguId = document.getElementById("inp-agt-regu").value;

    if (!nama) {
      showToast("Nama lengkap petugas wajib diisi!", "error");
      return;
    }

    if (role === "Ketua Regu" && !hp) {
      showToast("No. HP / WhatsApp wajib diisi untuk Ketua Regu sebagai ID Login!", "error");
      return;
    }

    let cleanHp = "";
    if (hp !== "") {
      if (hp.startsWith("+62") || hp.startsWith("62") || hp.startsWith("+")) {
        showToast('Format "+62" ditolak! Nomor telepon wajib diawali dengan "08" (contoh: 081234567890).', 'error');
        return;
      }

      cleanHp = hp.replace(/[- ]/g, "");
      if (!/^08[0-9]{8,13}$/.test(cleanHp)) {
        showToast('Nomor HP wajib diawali dengan "08" dan terdiri dari 10-15 angka!', 'error');
        return;
      }
    }

    // Validasi Anti-Duplikat & Bentrok Regu (Tolak jika nama/HP sudah terdaftar atau sudah jadi Anggota/Ketua di regu lain)
    const duplicatePerson = allAnggota.find(x => {
      if (editId && x.id === editId) return false;
      const sameName = (x.nama || "").trim().toLowerCase() === nama.toLowerCase();
      const xHp = (x.hp || "").replace(/[- ]/g, "");
      const sameHp = cleanHp !== "" && xHp !== "" && xHp === cleanHp;
      return sameName || sameHp;
    });

    if (duplicatePerson) {
      const dupRegu = allRegu.find(r => r.id === duplicatePerson.reguId || (r.anggotaIds && r.anggotaIds.includes(duplicatePerson.id)));
      if (dupRegu) {
        showToast(`Ditolak! "${duplicatePerson.nama}" sudah terdaftar sebagai ${duplicatePerson.role || "Anggota Regu"} di ${dupRegu.nama}.`, "error");
      } else {
        showToast(`Ditolak! Personel "${duplicatePerson.nama}"${cleanHp ? " / No. HP " + cleanHp : ""} sudah terdaftar di sistem.`, "error");
      }
      return;
    }

    const targetRegu = allRegu.find(r => r.id === reguId);

    // Tolak jika target regu sudah memiliki Ketua Regu yang berbeda
    if (targetRegu && role === "Ketua Regu") {
      const existingKetua = allAnggota.find(other => {
        if (editId && other.id === editId) return false;
        const inTarget = other.reguId === targetRegu.id || (targetRegu.anggotaIds && targetRegu.anggotaIds.includes(other.id));
        return inTarget && (other.role === "Ketua Regu" || (targetRegu.ketua && (other.nama || "").trim().toLowerCase() === targetRegu.ketua.trim().toLowerCase()));
      });
      if (existingKetua) {
        showToast(`Ditolak! ${targetRegu.nama} sudah memiliki Ketua Regu (${existingKetua.nama}).`, "error");
        return;
      }
    }

    if (editId) {
      const agt = allAnggota.find(a => a.id === editId);
      if (!agt) return;

      const oldName = agt.nama;
      const oldRegu = allRegu.find(r => r.id === agt.reguId || (r.anggotaIds && r.anggotaIds.includes(agt.id)));

      if (oldRegu && (!targetRegu || oldRegu.id !== targetRegu.id)) {
        if (Array.isArray(oldRegu.anggotaIds)) {
          oldRegu.anggotaIds = oldRegu.anggotaIds.filter(id => id !== agt.id);
        }
        if (oldRegu.ketua && oldRegu.ketua.trim().toLowerCase() === oldName.trim().toLowerCase()) {
          oldRegu.ketua = "-";
          oldRegu.noHp = "";
        }
      }

      agt.nama = nama;
      agt.role = role;
      agt.hp = cleanHp;
      agt.bidang = bidang;
      agt.reguId = reguId;
      agt.regu = targetRegu ? targetRegu.nama : "Belum Ditugaskan";
      agt.wilayah = targetRegu ? (targetRegu.wilayah || "-") : "-";

      if (targetRegu) {
        if (!targetRegu.anggotaIds) targetRegu.anggotaIds = [];
        if (!targetRegu.anggotaIds.includes(agt.id)) targetRegu.anggotaIds.push(agt.id);
        if (role === "Ketua Regu") {
          targetRegu.ketua = agt.nama;
          targetRegu.noHp = cleanHp;
        } else if (targetRegu.ketua && targetRegu.ketua.trim().toLowerCase() === oldName.trim().toLowerCase()) {
          targetRegu.ketua = "-";
        }
      }

      saveAnggotaDB(allAnggota);
      saveReguDB(allRegu);
      renderAnggotaTable(allAnggota);
      renderReguCards(allRegu);
      renderDashboardReguTable();
      updateStats();
      closeTambahAnggotaModal();
      document.getElementById("form-tambah-anggota").reset();
      showToast(`Data personel "${nama}" berhasil diperbarui.`, "success");
      return;
    }

    let maxAgtNum = 0;
    allAnggota.forEach(a => {
      const m = String(a.id || "").match(/AGT-(\d+)/);
      if (m) maxAgtNum = Math.max(maxAgtNum, parseInt(m[1], 10));
    });

    const newAgt = {
      id: "AGT-" + String(maxAgtNum + 1).padStart(3, "0"),
      nama,
      role,
      hp: cleanHp,
      bidang,
      reguId,
      regu: targetRegu ? targetRegu.nama : "Belum Ditugaskan",
      wilayah: targetRegu ? (targetRegu.wilayah || "-") : "-",
      status: "aktif"
    };

    allAnggota.push(newAgt);
    saveAnggotaDB(allAnggota);

    if (targetRegu) {
      if (!targetRegu.anggotaIds) targetRegu.anggotaIds = [];
      targetRegu.anggotaIds.push(newAgt.id);
      if (role === "Ketua Regu") {
        targetRegu.ketua = newAgt.nama;
        targetRegu.noHp = cleanHp;
      }
      saveReguDB(allRegu);
    }

    renderAnggotaTable(allAnggota);
    renderReguCards(allRegu);
    renderDashboardReguTable();
    updateStats();
    closeTambahAnggotaModal();
    document.getElementById("form-tambah-anggota").reset();
    const loginHint = role === "Ketua Regu" ? ` (Akun Login Aktif: ${cleanHp} / PW: Ketua123)` : "";
    showToast("Anggota " + nama + " berhasil ditambahkan ke " + (targetRegu ? targetRegu.nama : "Daftar Personel") + "." + loginHint, "success");
  }

  // CREATE REGU
  function openTambahReguModal() {
    const unassigned = allAnggota.filter(a => !a.reguId || a.reguId === "");
    const container = document.getElementById("regu-anggota-checkboxes");
    if (container) {
      if (unassigned.length === 0) {
        container.innerHTML = `<span style="font-size:0.78rem;color:#94A3B8;font-style:italic;">Semua personel sudah memiliki regu. Tambah personel baru di tab Pengguna.</span>`;
      } else {
        container.innerHTML = unassigned.map(a => `
          <label style="display:flex;align-items:center;gap:8px;font-size:0.82rem;color:#1E293B;cursor:pointer;padding:3px 0;">
            <input type="checkbox" name="agt_regu" value="${a.id}">
            <span><strong>${a.nama}</strong> (${a.role || 'Anggota'})</span>
          </label>
        `).join("");
      }
    }
    const ketuaSelect = document.getElementById("inp-regu-ketua");
    if (ketuaSelect) {
      ketuaSelect.innerHTML = `<option value="">-- Pilih Ketua Regu --</option>` +
        unassigned.map(a => `<option value="${a.nama}">${a.nama}</option>`).join("");
    }
    document.getElementById("modal-tambah-regu").classList.remove("hidden");
  }

  function closeTambahReguModal() {
    document.getElementById("modal-tambah-regu").classList.add("hidden");
  }

  function handleSimpanRegu(e) {
    e.preventDefault();
    const nama = document.getElementById("inp-regu-nama").value.trim();
    const wilayah = document.getElementById("inp-regu-wilayah").value.trim();
    const ketua = document.getElementById("inp-regu-ketua").value.trim();
    const checkedAgt = Array.from(document.querySelectorAll("input[name='agt_regu']:checked")).map(cb => cb.value);

    if (!nama || !wilayah) {
      showToast("Nama regu dan wilayah wajib diisi.", "error");
      return;
    }

    const newId = "REG-" + String(allRegu.length + 1).padStart(2, "0");
    const newRegu = {
      id: newId,
      nama: nama,
      jenis: nama.replace("Regu ", ""),
      wilayah: wilayah,
      ketua: ketua || (checkedAgt.length > 0 ? (allAnggota.find(a => a.id === checkedAgt[0]) || {}).nama : "-"),
      anggotaIds: checkedAgt
    };

    allRegu.push(newRegu);

    checkedAgt.forEach(agtId => {
      const a = allAnggota.find(x => x.id === agtId);
      if (a) {
        a.reguId = newId;
        a.regu = newRegu.nama;
        a.role = (newRegu.ketua && a.nama === newRegu.ketua) ? "Ketua Regu" : "Anggota Regu";
      }
    });

    saveReguDB(allRegu);
    saveAnggotaDB(allAnggota);

    renderReguCards(allRegu);
    renderDashboardReguTable();
    renderAnggotaTable(allAnggota);
    closeTambahReguModal();
    document.getElementById("form-tambah-regu").reset();
    showToast(`Regu "${nama}" berhasil dibuat.`, "success");
  }

  // UPDATE REGU
  let editingReguId = null;
  let tempEditingMemberIds = [];

  function openEditReguModal(reguId) {
    const reg = allRegu.find(r => r.id === reguId);
    if (!reg) return;
    editingReguId = reguId;

    document.getElementById("edit-regu-id").value = reg.id;
    document.getElementById("edit-regu-nama").value = reg.nama || "";
    document.getElementById("edit-regu-wilayah").value = reg.wilayah || "";

    tempEditingMemberIds = allAnggota
      .filter(a => (reg.anggotaIds && reg.anggotaIds.includes(a.id)) || a.reguId === reg.id)
      .map(a => a.id);

    let activeKetua = reg.ketua || "";
    if (!activeKetua) {
      const ketuaMember = allAnggota.find(a => tempEditingMemberIds.includes(a.id) && a.role === "Ketua Regu");
      if (ketuaMember) activeKetua = ketuaMember.nama;
    }

    renderEditReguMembersUI(activeKetua);

    document.getElementById("modal-edit-regu").classList.remove("hidden");
  }

  function renderEditReguMembersUI(selectedKetua) {
    const container = document.getElementById("edit-regu-current-members");
    const unassignedSelect = document.getElementById("edit-regu-select-unassigned");
    const ketuaSelect = document.getElementById("edit-regu-ketua");
    const currentKetua = selectedKetua !== undefined ? selectedKetua : (ketuaSelect ? ketuaSelect.value : "");

    // 1. Render Current Members Chips
    if (container) {
      if (tempEditingMemberIds.length === 0) {
        container.innerHTML = `<span style="font-size:0.78rem;color:#94A3B8;font-style:italic;">Belum ada anggota di regu ini. Pilih dari dropdown personel bebas di bawah.</span>`;
      } else {
        container.innerHTML = tempEditingMemberIds.map(id => {
          const a = allAnggota.find(x => x.id === id);
          if (!a) return "";
          const isKetua = a.nama === currentKetua;
          return `
            <span style="display:inline-flex;align-items:center;gap:6px;background:${isKetua ? '#FFF7ED' : '#FFFFFF'};border:1px solid ${isKetua ? '#FED7AA' : '#CBD5E1'};border-radius:999px;padding:4px 10px;font-size:0.8rem;color:${isKetua ? '#C2410C' : '#1E293B'};font-weight:600;">
              <span>${a.nama} ${isKetua ? '(Ketua)' : ''}</span>
              <button type="button" onclick="hapusPersonelDariEditRegu('${a.id}')" title="Keluarkan dari regu ini" style="border:none;background:none;color:#94A3B8;cursor:pointer;padding:0 2px;display:inline-flex;align-items:center;">
                <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
              </button>
            </span>
          `;
        }).join("");
      }
    }

    // 2. Render Available Personnel Dropdown (Unassigned first, plus personnel from other regus to move/add)
    if (unassignedSelect) {
      const availablePersonnel = allAnggota.filter(a => !tempEditingMemberIds.includes(a.id));
      const freePersonnel = availablePersonnel.filter(a => !a.reguId || a.reguId === "");
      const otherReguPersonnel = availablePersonnel.filter(a => a.reguId && a.reguId !== "" && a.reguId !== editingReguId);

      if (availablePersonnel.length === 0) {
        unassignedSelect.innerHTML = `<option value="">(Semua personel sudah ada di regu ini)</option>`;
        unassignedSelect.disabled = true;
      } else {
        unassignedSelect.disabled = false;
        let optsHtml = `<option value="">-- Pilih Nama Personel (${availablePersonnel.length} Tersedia) --</option>`;
        if (freePersonnel.length > 0) {
          optsHtml += `<optgroup label="Belum Punya Regu (${freePersonnel.length})">` +
            freePersonnel.map(a => {
              const hpStr = a.hp ? (typeof formatPhone08 === 'function' ? formatPhone08(a.hp) : a.hp) : 'Tanpa HP';
              return `<option value="${a.id}">${a.nama} - ${a.role || 'Anggota'} (${hpStr})</option>`;
            }).join("") + `</optgroup>`;
        }
        if (otherReguPersonnel.length > 0) {
          optsHtml += `<optgroup label="Sudah Terdaftar di Regu Lain (Terkunci)">` +
            otherReguPersonnel.map(a => {
              const regObj = allRegu.find(r => r.id === a.reguId);
              const regLabel = regObj ? regObj.nama : (a.regu || 'Regu Lain');
              return `<option value="${a.id}" disabled>${a.nama} — Sudah ${a.role || 'Anggota'} di ${regLabel}</option>`;
            }).join("") + `</optgroup>`;
        }
        unassignedSelect.innerHTML = optsHtml;
      }
    }

    // 3. Render Ketua Regu Options (from current members)
    if (ketuaSelect) {
      const memberObjs = tempEditingMemberIds.map(id => allAnggota.find(x => x.id === id)).filter(Boolean);
      ketuaSelect.innerHTML = `<option value="">-- Pilih Ketua Regu --</option>` +
        memberObjs.map(a => `<option value="${a.nama}" ${a.nama === currentKetua ? 'selected' : ''}>${a.nama}</option>`).join("");
      if (currentKetua) ketuaSelect.value = currentKetua;
    }
  }

  function tambahPersonelKeReguAktif() {
    const sel = document.getElementById("edit-regu-select-unassigned");
    if (!sel || !sel.value) return;
    const agtId = sel.value;
    const addedAgt = allAnggota.find(x => x.id === agtId);
    if (addedAgt && addedAgt.reguId && addedAgt.reguId !== "" && addedAgt.reguId !== editingReguId) {
      const regObj = allRegu.find(r => r.id === addedAgt.reguId);
      const regLabel = regObj ? regObj.nama : (addedAgt.regu || "Regu Lain");
      showToast(`Ditolak! ${addedAgt.nama} sudah menjadi ${addedAgt.role || "Anggota Regu"} di ${regLabel}. Keluarkan dari regu tersebut terlebih dahulu!`, "error");
      return;
    }
    if (!tempEditingMemberIds.includes(agtId)) {
      tempEditingMemberIds.push(agtId);
      const ketuaSelect = document.getElementById("edit-regu-ketua");
      let currentKetua = ketuaSelect ? ketuaSelect.value : "";
      if (!currentKetua && addedAgt && addedAgt.role === "Ketua Regu") {
        currentKetua = addedAgt.nama;
      }
      renderEditReguMembersUI(currentKetua);
    }
  }

  function hapusPersonelDariEditRegu(agtId) {
    const agt = allAnggota.find(x => x.id === agtId);
    const ketuaSelect = document.getElementById("edit-regu-ketua");
    let currentKetua = ketuaSelect ? ketuaSelect.value : "";
    if (agt && agt.nama === currentKetua) currentKetua = "";

    tempEditingMemberIds = tempEditingMemberIds.filter(id => id !== agtId);
    renderEditReguMembersUI(currentKetua);
  }

  function closeEditReguModal() {
    document.getElementById("modal-edit-regu").classList.add("hidden");
    editingReguId = null;
    tempEditingMemberIds = [];
  }

  function handleUpdateRegu(e) {
    e.preventDefault();
    if (!editingReguId) return;
    const reg = allRegu.find(r => r.id === editingReguId);
    if (!reg) return;

    // Jika user memilih personel di dropdown tapi langsung klik Simpan Perubahan tanpa klik "+ Masukkan"
    const pendingSel = document.getElementById("edit-regu-select-unassigned");
    if (pendingSel && pendingSel.value && !tempEditingMemberIds.includes(pendingSel.value)) {
      tempEditingMemberIds.push(pendingSel.value);
    }

    const nama = document.getElementById("edit-regu-nama").value.trim();
    const wilayah = document.getElementById("edit-regu-wilayah").value.trim();
    let ketua = document.getElementById("edit-regu-ketua").value.trim();

    if (!nama || !wilayah) {
      showToast("Nama regu dan wilayah wajib diisi.", "error");
      return;
    }

    // Jika dropdown ketua kosong, otomatis cari jika ada personel di tim ini yang role-nya Ketua Regu
    if (!ketua) {
      const km = allAnggota.find(a => tempEditingMemberIds.includes(a.id) && a.role === "Ketua Regu");
      if (km) ketua = km.nama;
    }

    reg.nama = nama;
    reg.wilayah = wilayah;
    reg.ketua = ketua;
    reg.anggotaIds = [...tempEditingMemberIds];

    if (ketua) {
      const ketuaAgt = allAnggota.find(a => (a.nama || "").trim().toLowerCase() === ketua.trim().toLowerCase());
      if (ketuaAgt && ketuaAgt.hp) {
        reg.noHp = ketuaAgt.hp;
      }
    }

    // Lepaskan anggota yang dipindahkan dari regu lama agar sinkron dua arah
    allRegu.forEach(otherReg => {
      if (otherReg.id !== reg.id && Array.isArray(otherReg.anggotaIds)) {
        otherReg.anggotaIds = otherReg.anggotaIds.filter(id => !tempEditingMemberIds.includes(id));
      }
    });

    allAnggota.forEach(a => {
      if (tempEditingMemberIds.includes(a.id)) {
        a.reguId = reg.id;
        a.regu = reg.nama;
        a.wilayah = reg.wilayah;
        // 1 Regu hanya boleh 1 Ketua Regu:
        if (ketua && (a.nama || "").trim().toLowerCase() === ketua.trim().toLowerCase()) {
          a.role = "Ketua Regu";
        } else if (ketua) {
          a.role = "Anggota Regu";
        }
      } else if (a.reguId === reg.id) {
        a.reguId = "";
        a.regu = "Belum Ditugaskan";
        a.wilayah = "-";
        if (a.role === "Ketua Regu") a.role = "Anggota Regu";
      }
    });

    saveReguDB(allRegu);
    saveAnggotaDB(allAnggota);

    renderReguCards(allRegu);
    renderDashboardReguTable();
    renderAnggotaTable(allAnggota);
    closeEditReguModal();
    showToast(`Perubahan Regu "${nama}" berhasil disimpan.`, "success");
  }

  function hapusReguFromModal() {
    if (!editingReguId) return;
    const regId = editingReguId;
    closeEditReguModal();
    hapusReguDirect(regId);
  }

  // DELETE REGU
  function hapusReguDirect(reguId) {
    const reg = allRegu.find(r => r.id === reguId);
    if (!reg) return;

    if (!confirm(`Hapus ${reg.nama} dari sistem? Personel di regu ini akan dialihkan ke status belum ber-regu.`)) return;

    allAnggota.forEach(a => {
      if (a.reguId === reguId || (reg.anggotaIds && reg.anggotaIds.includes(a.id))) {
        a.reguId = "";
        if (a.role === "Ketua Regu") a.role = "Anggota Regu";
      }
    });

    allRegu = allRegu.filter(r => r.id !== reguId);

    saveReguDB(allRegu);
    saveAnggotaDB(allAnggota);

    renderReguCards(allRegu);
    renderDashboardReguTable();
    renderAnggotaTable(allAnggota);
    showToast(`Regu ${reg.nama} telah dihapus.`, "info");
  }

  // REMOVE MEMBER FROM REGU
  function keluarkanAnggotaDariRegu(reguId, agtId) {
    const reg = allRegu.find(r => r.id === reguId);
    const agt = allAnggota.find(a => a.id === agtId);
    if (!reg || !agt) return;

    if (!confirm(`Keluarkan ${agt.nama} dari ${reg.nama}?`)) return;

    if (reg.anggotaIds) {
      reg.anggotaIds = reg.anggotaIds.filter(id => id !== agtId);
    }
    if (agt.reguId === reguId) {
      agt.reguId = "";
      if (agt.role === "Ketua Regu") agt.role = "Anggota Regu";
    }
    if (reg.ketua === agt.nama) {
      reg.ketua = "-";
    }

    saveReguDB(allRegu);
    saveAnggotaDB(allAnggota);

    renderReguCards(allRegu);
    renderDashboardReguTable();
    renderAnggotaTable(allAnggota);
    showToast(`${agt.nama} telah dikeluarkan dari ${reg.nama}.`, "info");
  }

  // Modal Detail Laporan
  function openDetailModal(id) {
    const lap = allReports.find(r => r.id === id);
    if (!lap) return;
    activeModalReport = lap;

    const ki = KATEGORI_INFO[lap.kat] || {};
    document.getElementById("m-kat-tag").textContent = ki.label || lap.kat;
    document.getElementById("m-id-tag").textContent = "#" + (lap.noPekerjaan || lap.id);
    
    const rwTag = document.getElementById("m-rw-tag");
    if (rwTag) rwTag.textContent = lap.rw || "RW 01";

    const mingguTag = document.getElementById("m-minggu-tag");
    if (mingguTag) mingguTag.textContent = lap.minggu || "Laporan Mingguan";

    const reguTag = document.getElementById("m-regu-tag");
    if (reguTag) reguTag.textContent = lap.regu || "Regu PPSU";

    document.getElementById("m-title").textContent = lap.judul || lap.jenisPekerjaan;
    const descSub = document.getElementById("m-desc-subtitle");
    if (descSub) descSub.textContent = getDeskripsiPekerjaan(lap);
    document.getElementById("m-petugas").textContent = lap.petugas;
    
    const jmlPet = document.getElementById("m-jml-petugas");
    if (jmlPet) jmlPet.textContent = lap.jumlahPetugas || 4;

    document.getElementById("m-tanggal").textContent = lap.tanggal;

    const noHpEl = document.getElementById("m-nohp-link");
    if (noHpEl) {
      noHpEl.textContent = lap.noHp || "-";
      noHpEl.href = lap.noHp ? "tel:" + lap.noHp.replace(/[^0-9]/g, "") : "javascript:void(0)";
    }

    document.getElementById("m-alamat").textContent = lap.alamat;
    
    const patokanEl = document.getElementById("m-patokan");
    if (patokanEl) patokanEl.textContent = lap.lokasiDetail || "-";

    const coordsEl = document.getElementById("m-coords");
    if (coordsEl) coordsEl.textContent = `GPS: Lat ${(lap.gpsLat || -6.338520).toFixed(6)}, Long ${(lap.gpsLng || 106.865410).toFixed(6)} (Akurat)`;

    const prg = lap.progres !== undefined ? lap.progres : (lap.status === "selesai" ? 100 : 50);
    const prgPct = document.getElementById("m-progres-pct");
    if (prgPct) prgPct.textContent = prg + "%";
    const prgBar = document.getElementById("m-progres-bar");
    if (prgBar) prgBar.style.width = prg + "%";

    const kendalaEl = document.getElementById("m-kendala");
    if (kendalaEl) kendalaEl.textContent = lap.kendala || lap.deskripsi || "Tidak ada kendala lapangan.";

    const badgeCls = lap.status === "selesai" ? "badge-selesai" : lap.status === "proses" ? "badge-proses" : "badge-belum";
    const badgeTxt = lap.status === "selesai" ? "Selesai (100%)" : lap.status === "proses" ? "Dalam Proses (" + prg + "%)" : "Perlu Verifikasi";
    const badgeEl = document.getElementById("m-status-pill");
    badgeEl.className = "badge-status " + badgeCls;
    badgeEl.innerHTML = `<span class="status-dot"></span>${badgeTxt}`;

    const photosGrid = document.getElementById("m-photos-grid");
    const rawPhotos = Array.isArray(lap.foto) ? lap.foto : [];
    const photoArr = rawPhotos.filter(f => f && f.src && typeof f.src === "string" && f.src.trim() !== "" && f.src !== "null" && f.src !== "undefined");

    if (photoArr.length === 0) {
      photosGrid.innerHTML = `<div style="grid-column:1/-1;padding:18px;text-align:center;color:#94A3B8;font-size:0.84rem;background:#F8FAFC;border:1.5px dashed #CBD5E1;border-radius:10px;">Belum ada bukti foto dokumentasi yang diunggah.</div>`;
    } else {
      photosGrid.style.gridTemplateColumns = photoArr.length === 1 ? "minmax(180px, 240px)" : `repeat(${Math.min(3, photoArr.length)}, 1fr)`;
      photosGrid.innerHTML = photoArr.map((f, pIdx) => {
        const validSrc = f.src;
        let labelText = f.tahap || f.name || (pIdx === 0 ? "Sebelum (0%)" : pIdx === 1 ? "Proses (50%)" : "Sesudah (100%)");
        labelText = labelText.replace("Proses (0%)", "Proses (50%)");
        return `
          <div style="border-radius:10px;overflow:hidden;border:1.5px solid #CBD5E1;box-shadow:0 2px 6px rgba(0,0,0,0.06);background:#FFFFFF;display:flex;flex-direction:column;">
            <div style="width:100%;height:140px;background:#0F172A;overflow:hidden;position:relative;">
              <img src="${validSrc}" alt="${labelText}" style="width:100%;height:100%;object-fit:cover;" onerror="this.onerror=null;this.src='foto/silap.png'">
            </div>
            <div style="padding:6px 8px;font-size:0.75rem;font-weight:700;color:#0F172A;background:#FFFFFF;border-top:1px solid #E2E8F0;text-align:center;">
              ${labelText}
            </div>
          </div>
        `;
      }).join("");
    }

    const btnVerify = document.getElementById("btn-verify-action");
    const isReadyToVerify = (prg >= 100 && photoArr.length >= 3);
    if (lap.status === "selesai") {
      btnVerify.textContent = "Sudah Terverifikasi (100%)";
      btnVerify.disabled = false;
      btnVerify.style.background = "#15803D";
      btnVerify.style.borderColor = "#15803D";
      btnVerify.style.cursor = "pointer";
      btnVerify.style.opacity = "1";
    } else if (!isReadyToVerify) {
      btnVerify.textContent = `Belum Lengkap (${photoArr.length}/3 Foto · ${prg}%)`;
      btnVerify.disabled = true;
      btnVerify.style.background = "#94A3B8";
      btnVerify.style.borderColor = "#94A3B8";
      btnVerify.style.cursor = "not-allowed";
      btnVerify.style.opacity = "0.75";
    } else {
      btnVerify.textContent = "Verifikasi & Setujui";
      btnVerify.disabled = false;
      btnVerify.style.background = "";
      btnVerify.style.borderColor = "";
      btnVerify.style.cursor = "pointer";
      btnVerify.style.opacity = "1";
    }

    document.getElementById("modal-detail").classList.remove("hidden");
  }

  function closeDetailModal() {
    document.getElementById("modal-detail").classList.add("hidden");
    activeModalReport = null;
  }

  window.addEventListener("keydown", (e) => {
    if (e.key === "Escape") {
      toggleAdminMobileMenu(false);
      closeDetailModal();
      closeTambahAnggotaModal();
      closeTambahReguModal();
    }
  });

  function verifyCurrentReport() {
    if (!activeModalReport) return;
    const repId = activeModalReport.noPekerjaan || activeModalReport.id;
    const validPhotos = (activeModalReport.foto || []).filter(f => f && f.src && typeof f.src === "string" && f.src.trim() !== "" && f.src !== "null" && f.src !== "undefined");
    const curProg = parseInt(activeModalReport.progres, 10) || 0;
    const alreadyVerified = (activeModalReport.status === "selesai" && curProg >= 100);

    if (!alreadyVerified && (curProg < 100 || validPhotos.length < 3)) {
      showToast(`Belum bisa diverifikasi! Petugas harus mengirim 3 foto lengkap & progres 100% (Saat ini: ${validPhotos.length}/3 foto, ${curProg}%).`, "warning");
      return;
    }

    activeModalReport.status = "selesai";
    activeModalReport.progres = 100;
    activeModalReport.tanggal = formatVerifiedTimestamp();

    saveLaporanDB(allReports);
    updateStats();
    filterReports(currentFilter);
    renderVerifikasiList(allReports);
    renderRekapTable(allReports);
    closeDetailModal();
    showToast(
      alreadyVerified
        ? `Laporan #${repId} sudah berstatus Terverifikasi (100%).`
        : `Laporan #${repId} berhasil diverifikasi dan disetujui.`,
      "success"
    );
  }

  function deleteCurrentReport() {
    if (!activeModalReport) return;
    const repId = activeModalReport.id;
    const repCode = activeModalReport.noPekerjaan || repId;
    if (!confirm(`Hapus tugas #${repCode} secara permanen dari sistem?`)) return;

    allReports = allReports.filter(r => String(r.id) !== String(repId));
    saveLaporanDB(allReports);

    try {
      fetch(`api.php?action=delete_report&id=${encodeURIComponent(repId)}`, { method: "POST" });
    } catch(e) {}

    closeDetailModal();
    refreshAdminData();
    showToast(`Tugas #${repCode} berhasil dihapus.`, "info");
  }

  function hapusSemuaTugasAdmin() {
    if (!confirm("Yakin ingin menghapus SEMUA tugas/laporan lapangan? Data yang dihapus tidak dapat dikembalikan.")) return;
    allReports = [];
    saveLaporanDB([]);
    try {
      localStorage.setItem(STORAGE_KEY_REPORTS, "[]");
      fetch("api.php?action=delete_all_reports", { method: "POST" });
    } catch(e) {}
    refreshAdminData();
    showToast("Seluruh data tugas lapangan berhasil dikosongkan.", "success");
  }

  // Handle Logout Sesi PHP & LocalStorage
  function logoutUser() {
    try {
      localStorage.removeItem("silap_user_session");
      localStorage.removeItem("jawara_user_session");
    } catch(e) {}
    window.location.href = "logout.php";
  }

  document.addEventListener("DOMContentLoaded", () => {
    initAdmin();
    <?php if (isset($_GET['tab'])): ?>
    switchAdminTab("<?= htmlspecialchars($_GET['tab']) ?>");
    <?php endif; ?>
  });
</script>
</body>
</html>
