  <!-- Left White Sidebar -->
  <aside class="admin-sidebar" id="admin-sidebar">
    <div class="admin-brand">
      <div class="admin-brand-left" style="display:flex;align-items:center;gap:10px;">
        <div class="admin-brand-icon" style="background:transparent;border:none;box-shadow:none;padding:0;">
          <img src="foto/silap.png" alt="Logo SILAP" class="admin-brand-img">
        </div>
        <div class="admin-brand-info">
          <span class="admin-brand-name">SILAP PPSU</span>
          <span class="admin-brand-sub admin-brand-sub-desktop">SISTEM LAPORAN PPSU</span>
          <span class="admin-brand-sub admin-brand-sub-mobile"><?= $userName ?> · <?= $userRole ?></span>
        </div>
      </div>
      <div class="admin-mobile-quick-actions">
        <span class="admin-mobile-online-pill" title="Status Sistem: Online Terhubung">
          <span class="admin-mobile-online-dot"></span>
          <span>Online</span>
        </span>
        <button type="button" class="admin-mobile-bell-btn" onclick="showToast('Antrean laporan telah diperbarui.', 'info')" aria-label="Notifikasi" title="Notifikasi">
          <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
            <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
          </svg>
          <span class="admin-notif-dot"></span>
        </button>
      </div>
    </div>

    <!-- 7 Admin Nav Items -->
    <nav class="admin-nav-menu">
      <!-- 1. Dashboard -->
      <button class="admin-nav-item active" id="nav-btn-dashboard" onclick="switchAdminTab('dashboard')">
        <svg class="admin-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <rect x="3" y="3" width="7" height="7" rx="1.5"></rect>
          <rect x="14" y="3" width="7" height="7" rx="1.5"></rect>
          <rect x="14" y="14" width="7" height="7" rx="1.5"></rect>
          <rect x="3" y="14" width="7" height="7" rx="1.5"></rect>
        </svg>
        <span>Dashboard</span>
      </button>

      <!-- 2. Tugas -->
      <button class="admin-nav-item" id="nav-btn-tugas" onclick="switchAdminTab('tugas')">
        <svg class="admin-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
          <polyline points="14 2 14 8 20 8"></polyline>
          <line x1="16" y1="13" x2="8" y2="13"></line>
          <line x1="16" y1="17" x2="8" y2="17"></line>
          <polyline points="10 9 9 9 8 9"></polyline>
        </svg>
        <span>Tugas</span>
      </button>

      <!-- 3. Verifikasi -->
      <button class="admin-nav-item" id="nav-btn-verifikasi" onclick="switchAdminTab('verifikasi')">
        <svg class="admin-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M9 11l3 3L22 4"></path>
          <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path>
        </svg>
        <span>Verifikasi</span>
        <span class="admin-nav-badge" id="admin-badge-verif">0</span>
      </button>

      <!-- 4. Rekap -->
      <button class="admin-nav-item" id="nav-btn-rekap" onclick="switchAdminTab('rekap')">
        <svg class="admin-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <line x1="18" y1="20" x2="18" y2="10"></line>
          <line x1="12" y1="20" x2="12" y2="4"></line>
          <line x1="6" y1="20" x2="6" y2="14"></line>
        </svg>
        <span>Rekap</span>
      </button>

      <!-- 5. Pengguna (Anggota) -->
      <button class="admin-nav-item" id="nav-btn-pengguna" onclick="switchAdminTab('pengguna')">
        <svg class="admin-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
          <circle cx="9" cy="7" r="4"></circle>
          <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
          <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
        </svg>
        <span>Pengguna</span>
      </button>

      <!-- 6. Regu -->
      <button class="admin-nav-item" id="nav-btn-regu" onclick="switchAdminTab('regu')">
        <svg class="admin-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
          <polyline points="9 12 11 14 15 10"></polyline>
        </svg>
        <span>Regu</span>
      </button>

      <!-- 7. Pengaturan -->
      <button class="admin-nav-item" id="nav-btn-pengaturan" onclick="switchAdminTab('pengaturan')">
        <svg class="admin-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="12" cy="12" r="3"></circle>
          <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
        </svg>
        <span>Pengaturan</span>
      </button>
    </nav>

    <!-- Sidebar Bottom Footer -->
    <div class="admin-sidebar-footer">
      <a href="user.php" class="admin-nav-item admin-footer-btn" style="text-decoration:none;">
        <svg class="admin-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
          <circle cx="9" cy="7" r="4"></circle>
          <polyline points="16 11 18 13 22 9"></polyline>
        </svg>
        <span>Beralih ke mode User</span>
      </a>

      <button type="button" class="admin-nav-item admin-footer-btn" onclick="logoutUser()">
        <svg class="admin-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
          <polyline points="16 17 21 12 16 7"></polyline>
          <line x1="21" y1="12" x2="9" y2="12"></line>
        </svg>
        <span>Keluar</span>
      </button>
    </div>
  </aside>

