      <!-- ==================== 4. REKAP VIEW ==================== -->
      <section class="admin-tab-pane <?= (isset($_GET['tab']) && $_GET['tab'] === 'rekap') ? 'active' : '' ?>" id="pane-rekap">
        <div class="admin-welcome-bar">
          <div>
            <h1 class="admin-welcome-heading">Rekapitulasi Penanganan Prasarana</h1>
            <p style="color:#64748B;font-size:0.9rem;margin-top:4px;">Laporan kinerja operasional penanganan 5 bidang prasarana wilayah.</p>
          </div>
          <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
            <button type="button" class="btn-lihat-detail" onclick="openTambahSubjekModal()" style="background:#FFF7ED;border:1px solid #FFEDD5;color:#EA580C;font-weight:700;">
              + Tambah Subjek
            </button>
            <button type="button" class="admin-btn-primary" onclick="window.print()">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
              <span>Cetak Berkas</span>
            </button>
          </div>
        </div>

        <div class="admin-table-container">
          <div class="mobile-swipe-badge">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
            <span>Geser tabel ke samping untuk melihat seluruh kolom data</span>
          </div>
          <table class="admin-table">
            <thead>
              <tr>
                <th>Bidang Prasarana</th>
                <th>Total Masuk</th>
                <th>Selesai (100%)</th>
                <th>Dalam Proses</th>
                <th>Perlu Verifikasi</th>
                <th>Persentase Tuntas</th>
                <th style="text-align:right;">Aksi</th>
              </tr>
            </thead>
            <tbody id="rekap-table-body">
              <!-- Rendered in script -->
            </tbody>
          </table>
        </div>
      </section>

