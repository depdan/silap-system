  <!-- Credit Header Mahasiswa Gunadarma -->
  <div style="background:linear-gradient(90deg,#F5F3FF,#EDE9FE);border-bottom:1px solid #DDD6FE;color:#5B21B6;font-size:0.75rem;font-weight:700;display:flex;align-items:center;justify-content:center;gap:8px;padding:6px 12px;letter-spacing:0.01em;">
    <img src="foto/gunadarma.png" alt="Logo Universitas Gunadarma" style="width:24px;height:24px;object-fit:contain;flex-shrink:0;">
    <span>Aplikasi ini dibuat oleh Mahasiswa Gunadarma</span>
  </div>
  <!-- Modern Figma Header Bar -->
  <header class="user-portal-topbar">
    <div class="user-topbar-brand">
      <img src="foto/silap.png" alt="Logo SILAP" class="user-topbar-logo">
      <div class="user-topbar-brand-text">
        <div class="user-topbar-title">SILAP PPSU</div>
        <div class="user-topbar-sub" id="u-topbar-sub"><?= $userName ?> · <?= ($userRole === 'admin') ? 'Administrator' : 'Petugas Lapangan' ?></div>
      </div>
    </div>
    <div class="user-topbar-actions">
      <div class="topbar-live-status" title="Status Sistem: Online Terhubung">
        <span class="live-pulse-emerald"></span>
        <span>Online</span>
      </div>
      <?php if ($userRole === 'admin'): ?>
      <a href="admin.php" class="user-pill-btn user-pill-btn--admin" title="Buka Admin Command Center">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/></svg>
        <span>Admin</span>
      </a>
      <?php endif; ?>
      <a href="javascript:void(0)" class="user-pill-btn user-pill-btn--logout" title="Keluar / Ganti Akun" onclick="logoutUser()">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
        <span>Keluar</span>
      </a>
    </div>
  </header>

  <!-- Field Officer Header & Shift Summary Card -->
  <div class="hero-officer-banner">
    <!-- Row 1: Greeting left, Role Badge right -->
    <div class="hero-header-top">
      <div class="hero-greeting-text"><?= ($userRole === 'admin') ? 'Pusat Operasional' : 'Selamat Bertugas' ?></div>
      <div class="hero-role-side-wrap">
        <div class="hero-role-pill-simple" id="hero-role-pill" style="cursor:default !important;pointer-events:none !important;user-select:none;" title="Role resmi petugas yang diatur oleh Administrator di Master Data">
          <span id="u-role-text"><?= ($userRole === 'admin') ? 'ADMINISTRATOR • KELURAHAN' : (!empty($userOfficialRegu) ? strtoupper(htmlspecialchars($userOfficialRegu . ' • ' . $userOfficialRole)) : ('MENUNGGU PENUGASAN • ' . strtoupper(htmlspecialchars($userOfficialRole)))) ?></span>
        </div>
      </div>
    </div>

    <!-- Row 2: Profile Info (Avatar + Full Name + Phone Badge + Kelurahan) -->
    <div class="hero-profile-row">
      <div class="hero-avatar-box" id="u-hero-avatar">
        <?= strtoupper(substr($userName, 0, 1)) ?>
      </div>
      <div class="hero-profile-info">
        <div class="hero-officer-name" id="u-greeting-name"><?= $userName ?></div>
        <div class="hero-sub-text" id="user-sub-officer">
          <span class="hero-phone-badge" id="u-hero-phone" title="Nomor telepon resmi petugas">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>
            <span id="u-phone-val"><?= htmlspecialchars($userPhone) ?></span>
          </span>
          <span class="hero-org-badge" id="u-org-val"><?= ($userRole === 'admin') ? '• Administrator Kelurahan • Kel. Pekayon' : '• Kel. Pekayon, Jakarta Timur' ?></span>
        </div>
      </div>
    </div>

    <!-- Clean White Shift Summary Dock (High Contrast for Outdoor Field Use) -->
    <div class="hero-field-dock">
      <div class="dock-item">
        <span class="dock-label">Regu</span>
        <span class="dock-value" id="brief-regu-text"><?= ($userRole === 'admin') ? 'Komando' : (!empty($userOfficialRegu) ? htmlspecialchars($userOfficialRegu) : '-') ?></span>
      </div>
      <div class="dock-item">
        <span class="dock-label">Wilayah</span>
        <span class="dock-value" id="u-header-badge"><?= ($userRole === 'admin') ? 'Semua RW' : htmlspecialchars(!empty($userOfficialRegu) ? ($userOfficialWilayah ?: '-') : '-') ?></span>
      </div>
      <div class="dock-item">
        <span class="dock-label">GPS</span>
        <span class="dock-value gps-status" id="brief-gps-coords">● Aktif</span>
      </div>
      <div class="dock-item">
        <span class="dock-label">Waktu</span>
        <span class="dock-value" id="u-header-clock">--:-- WIB</span>
      </div>
    </div>
  </div>

