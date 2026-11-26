      <!-- ==================== 6. REGU VIEW ==================== -->
      <section class="admin-tab-pane <?= (isset($_GET['tab']) && $_GET['tab'] === 'regu') ? 'active' : '' ?>" id="pane-regu">
        <div class="admin-welcome-bar">
          <div>
            <h1 class="admin-welcome-heading">Manajemen Regu Lapangan</h1>
            <p style="color:#64748B;font-size:0.9rem;margin-top:4px;">Pengelompokan tim kerja, ketua regu, dan anggota yang ditugaskan ke wilayah.</p>
          </div>
          <button type="button" class="admin-btn-primary" onclick="openTambahReguModal()">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
            <span>Buat Regu Baru</span>
          </button>
        </div>

        <div class="regu-grid" id="regu-cards-container">
          <!-- Rendered in script -->
        </div>
      </section>

