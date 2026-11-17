<!-- Menu Popup Sheet (Khusus Mode User & Keluar) -->
<div class="admin-mobile-menu-backdrop" id="admin-mobile-menu-backdrop" onclick="toggleAdminMobileMenu(false)"></div>
<div class="admin-mobile-menu-sheet" id="admin-mobile-menu-sheet" role="dialog" aria-label="Menu Akun Admin">
  <div class="admin-sheet-header">
    <div>
      <div class="admin-sheet-user-title"><?= $userName ?></div>
      <div class="admin-sheet-user-sub"><?= $userRole ?> · Kel. Pekayon</div>
    </div>
    <button type="button" class="admin-sheet-close" onclick="toggleAdminMobileMenu(false)" aria-label="Tutup Menu">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
    </button>
  </div>
  <a href="user.php" class="admin-sheet-action">
    <span class="admin-sheet-action-icon">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><polyline points="16 11 18 13 22 9"></polyline></svg>
    </span>
    <span>
      <span>Beralih ke mode User</span>
      <span class="admin-sheet-action-sub">Masuk ke tampilan portal petugas lapangan</span>
    </span>
  </a>
  <button type="button" class="admin-sheet-action admin-sheet-action--danger" onclick="logoutUser()">
    <span class="admin-sheet-action-icon">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
    </span>
    <span>
      <span>Keluar</span>
      <span class="admin-sheet-action-sub">Akhiri sesi Administrator saat ini</span>
    </span>
  </button>
</div>

<!-- Android/iOS Fixed Bottom Navigation Bar (7 Fitur Utama + Menu) -->
<nav class="admin-mobile-bottom-nav" id="admin-bottom-nav" aria-label="Navigasi Cepat Ponsel">
  <div class="admin-bottom-nav-track">
  <button type="button" class="admin-bottom-nav-item active" id="bnav-btn-dashboard" onclick="switchAdminTab('dashboard')" aria-label="Dashboard">
    <svg class="bnav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1.5"></rect><rect x="14" y="3" width="7" height="7" rx="1.5"></rect><rect x="14" y="14" width="7" height="7" rx="1.5"></rect><rect x="3" y="14" width="7" height="7" rx="1.5"></rect></svg>
    <span class="bnav-label">Dashboard</span>
  </button>
  <button type="button" class="admin-bottom-nav-item" id="bnav-btn-tugas" onclick="switchAdminTab('tugas')" aria-label="Tugas">
    <svg class="bnav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg>
    <span class="bnav-label">Tugas</span>
  </button>
  <button type="button" class="admin-bottom-nav-item" id="bnav-btn-verifikasi" onclick="switchAdminTab('verifikasi')" aria-label="Verifikasi">
    <div class="bnav-icon-wrap">
      <svg class="bnav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 11l3 3L22 4"></path><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path></svg>
      <span class="bnav-badge" id="bnav-badge-verif" style="display:none;">0</span>
    </div>
    <span class="bnav-label">Verifikasi</span>
  </button>
  <button type="button" class="admin-bottom-nav-item" id="bnav-btn-rekap" onclick="switchAdminTab('rekap')" aria-label="Rekap">
    <svg class="bnav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line></svg>
    <span class="bnav-label">Rekap</span>
  </button>
  <button type="button" class="admin-bottom-nav-item" id="bnav-btn-pengguna" onclick="switchAdminTab('pengguna')" aria-label="Pengguna">
    <svg class="bnav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
    <span class="bnav-label">Pengguna</span>
  </button>
  <button type="button" class="admin-bottom-nav-item" id="bnav-btn-regu" onclick="switchAdminTab('regu')" aria-label="Regu">
    <svg class="bnav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path><polyline points="9 12 11 14 15 10"></polyline></svg>
    <span class="bnav-label">Regu</span>
  </button>
  <button type="button" class="admin-bottom-nav-item" id="bnav-btn-pengaturan" onclick="switchAdminTab('pengaturan')" aria-label="Pengaturan">
    <svg class="bnav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
    <span class="bnav-label">Pengaturan</span>
  </button>
  <button type="button" class="admin-bottom-nav-item" id="bnav-btn-menu" onclick="toggleAdminMobileMenu()" aria-label="Menu Akun">
    <svg class="bnav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="4" y1="12" x2="20" y2="12"></line><line x1="4" y1="6" x2="20" y2="6"></line><line x1="4" y1="18" x2="20" y2="18"></line></svg>
    <span class="bnav-label">Menu</span>
  </button>
  </div>
</nav>

