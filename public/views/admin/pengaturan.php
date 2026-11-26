      <!-- ==================== 7. PENGATURAN VIEW ==================== -->
      <section class="admin-tab-pane" id="pane-pengaturan">
        <div class="admin-welcome-bar">
          <div>
            <h1 class="admin-welcome-heading">Pengaturan Sistem & Kredensial Admin</h1>
            <p style="color:#64748B;font-size:0.9rem;margin-top:4px;">Kelola profil administrator, ID login, serta keamanan kata sandi akun.</p>
          </div>
        </div>

        <div style="background:#ffffff;border:1px solid #E2E8F0;border-radius:16px;padding:28px;max-width:720px;box-shadow:0 1px 3px rgba(15,23,42,0.04);margin-bottom:24px;">
          <h2 style="font-size:1.15rem;font-weight:800;color:#0F172A;margin-bottom:18px;display:flex;align-items:center;gap:8px;">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color:#2563EB;"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            Profil Administrator & Wilayah
          </h2>
          
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:18px;">
            <div>
              <label style="display:block;font-size:0.8rem;font-weight:700;color:#64748B;margin-bottom:6px;">Nama Lengkap</label>
              <input type="text" id="cfg-admin-name" class="form-input" value="<?= $userName ?>" style="width:100%;box-sizing:border-box;">
            </div>
            <div>
              <label style="display:block;font-size:0.8rem;font-weight:700;color:#64748B;margin-bottom:6px;">Username / ID Admin (Login)</label>
              <input type="text" id="cfg-admin-username" class="form-input" value="<?= htmlspecialchars($_SESSION['user']['username'] ?? 'admin') ?>" style="width:100%;box-sizing:border-box;">
            </div>
          </div>

          <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:18px;">
            <div>
              <label style="display:block;font-size:0.8rem;font-weight:700;color:#64748B;margin-bottom:6px;">Kelurahan Penugasan</label>
              <input type="text" class="form-input" value="Kelurahan Pekayon" style="width:100%;box-sizing:border-box;" readonly>
            </div>
            <div>
              <label style="display:block;font-size:0.8rem;font-weight:700;color:#64748B;margin-bottom:6px;">Kecamatan / Kota</label>
              <input type="text" class="form-input" value="Pasar Rebo, Jakarta Timur" style="width:100%;box-sizing:border-box;" readonly>
            </div>
          </div>

          <div style="margin-top:20px;padding-top:16px;border-top:1px solid #F1F5F9;">
            <h3 style="font-size:1rem;font-weight:800;color:#0F172A;margin-bottom:14px;display:flex;align-items:center;gap:8px;">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color:#EA580C;"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
              Keamanan & Ganti Kata Sandi
            </h3>
            
            <div style="margin-bottom:14px;">
              <label style="display:block;font-size:0.8rem;font-weight:700;color:#DC2626;margin-bottom:6px;">Kata Sandi Saat Ini <span style="font-size:0.75rem;font-weight:400;color:#64748B;">(Wajib diisi untuk konfirmasi keamanan)</span></label>
              <input type="password" id="cfg-admin-cur-pass" class="form-input" placeholder="Masukkan password admin saat ini" style="width:100%;box-sizing:border-box;">
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:18px;">
              <div>
                <label style="display:block;font-size:0.8rem;font-weight:700;color:#64748B;margin-bottom:6px;">Kata Sandi Baru <span style="font-size:0.75rem;font-weight:400;">(Opsional, min. 6 karakter)</span></label>
                <input type="password" id="cfg-admin-new-pass" class="form-input" placeholder="Kosongkan jika tak ingin ubah sandi" style="width:100%;box-sizing:border-box;">
              </div>
              <div>
                <label style="display:block;font-size:0.8rem;font-weight:700;color:#64748B;margin-bottom:6px;">Ulangi Kata Sandi Baru</label>
                <input type="password" id="cfg-admin-cfm-pass" class="form-input" placeholder="Konfirmasi kata sandi baru" style="width:100%;box-sizing:border-box;">
              </div>
            </div>
          </div>

          <div style="margin-top:24px;border-top:1px solid #F1F5F9;padding-top:16px;display:flex;justify-content:flex-end;">
            <button type="button" id="btn-save-admin-cfg" class="admin-btn-primary" onclick="simpanPengaturanAdmin()">
              Simpan Perubahan Akun
            </button>
          </div>
        </div>
      </section>

