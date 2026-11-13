      <!-- ==================== 7. PENGATURAN VIEW ==================== -->
      <section class="admin-tab-pane" id="pane-pengaturan">
        <div class="admin-welcome-bar">
          <div>
            <h1 class="admin-welcome-heading">Pengaturan Sistem & Profil Kelurahan</h1>
            <p style="color:#64748B;font-size:0.9rem;margin-top:4px;">Konfigurasi akun administrator dan informasi wilayah penugasan.</p>
          </div>
        </div>

        <div style="background:#ffffff;border:1px solid #E2E8F0;border-radius:16px;padding:28px;max-width:720px;box-shadow:0 1px 3px rgba(15,23,42,0.04);">
          <h2 style="font-size:1.15rem;font-weight:800;color:#0F172A;margin-bottom:18px;">Informasi Akun Administrator</h2>
          
          <div style="margin-bottom:18px;">
            <label style="display:block;font-size:0.8rem;font-weight:700;color:#64748B;margin-bottom:6px;">Nama Lengkap</label>
            <input type="text" id="cfg-admin-name" class="form-input" value="<?= $userName ?>" style="width:100%;box-sizing:border-box;">
          </div>

          <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:18px;">
            <div>
              <label style="display:block;font-size:0.8rem;font-weight:700;color:#64748B;margin-bottom:6px;">Kelurahan Penugasan</label>
              <input type="text" class="form-input" value="Kelurahan Pekayon" style="width:100%;box-sizing:border-box;">
            </div>
            <div>
              <label style="display:block;font-size:0.8rem;font-weight:700;color:#64748B;margin-bottom:6px;">Kecamatan / Kota</label>
              <input type="text" class="form-input" value="Pasar Rebo, Jakarta Timur" style="width:100%;box-sizing:border-box;">
            </div>
          </div>

          <div style="margin-top:24px;border-top:1px solid #F1F5F9;padding-top:16px;display:flex;justify-content:flex-end;">
            <button type="button" class="admin-btn-primary" onclick="simpanPengaturanAdmin()">
              Simpan Perubahan
            </button>
          </div>
        </div>
      </section>

