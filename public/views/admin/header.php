    <!-- Topbar -->
    <header class="admin-topbar">
      <div style="font-size:0.88rem;color:#64748B;font-weight:600;display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
        <span style="color:#0F172A;font-weight:800;">Command Center</span>
        <span style="color:#CBD5E1;">/</span>
        <span id="topbar-current-view" style="color:#FF5416;font-weight:700;">Dashboard</span>
        <span style="background:#F5F3FF;color:#5B21B6;border:1px solid #DDD6FE;font-size:0.76rem;font-weight:700;padding:4px 12px 4px 7px;border-radius:999px;display:inline-flex;align-items:center;gap:8px;">
          <img src="foto/gunadarma.png" alt="Logo Universitas Gunadarma" style="width:24px;height:24px;object-fit:contain;flex-shrink:0;">
          <span>Aplikasi ini dibuat oleh Mahasiswa Gunadarma</span>
        </span>
      </div>

      <div class="admin-topbar-actions">
        <button class="admin-topbar-bell" onclick="showToast('Antrean laporan telah diperbarui.', 'info')" title="Notifikasi">
          <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
            <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
          </svg>
          <span class="admin-notif-dot"></span>
        </button>

        <div class="admin-topbar-divider"></div>

        <div class="admin-user-capsule" onclick="switchAdminTab('pengaturan')" title="Buka Pengaturan Sistem & Profil" style="cursor:pointer;">
          <div class="admin-user-avatar" id="user-avatar"><?= $avatarText ?></div>
          <div class="admin-user-details">
            <span class="admin-user-name" id="user-name-display"><?= $userName ?></span>
            <span class="admin-user-role" id="navbar-role-tag"><?= $userRole ?></span>
          </div>
        </div>
      </div>
    </header>

