      <!-- ==================== 5. PENGGUNA (ANGGOTA) VIEW ==================== -->
      <section class="admin-tab-pane" id="pane-pengguna">
        <div class="admin-welcome-bar">
          <div>
            <h1 class="admin-welcome-heading">Daftar Personel PPSU</h1>
            <p style="color:#64748B;font-size:0.9rem;margin-top:4px;">Master data seluruh anggota petugas lapangan di wilayah kelurahan.</p>
          </div>
          <button type="button" class="admin-btn-primary" onclick="openTambahAnggotaModal()">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
            <span>Tambah Anggota Baru</span>
          </button>
        </div>

        <!-- Tampilan Tabel Desktop (Layar Lebar) -->
        <div class="admin-table-container desktop-only-table">
          <table class="admin-table">
            <thead>
              <tr>
                <th>Nama Lengkap</th>
                <th>Role / Peran</th>
                <th>No. Telepon / WA</th>
                <th style="text-align:right;">Aksi</th>
              </tr>
            </thead>
            <tbody id="anggota-table-body">
              <!-- Rendered in script -->
            </tbody>
          </table>
        </div>

        <!-- Tampilan Card Seragam & Pas di Layar HP (Mobile View) -->
        <div class="anggota-mobile-card-list mobile-only-cards" id="anggota-mobile-cards">
          <!-- Rendered in script -->
        </div>
      </section>

