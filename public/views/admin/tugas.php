      <!-- ==================== 2. TUGAS VIEW ==================== -->
      <section class="admin-tab-pane <?= (isset($_GET['tab']) && $_GET['tab'] === 'tugas') ? 'active' : '' ?>" id="pane-tugas">
        <div class="admin-welcome-bar" style="flex-direction:column;align-items:flex-start;gap:12px;">
          <div style="display:flex;justify-content:space-between;align-items:center;width:100%;flex-wrap:wrap;gap:10px;">
            <div>
              <h1 class="admin-welcome-heading">Semua Tugas Lapangan</h1>
              <p style="color:#64748B;font-size:0.9rem;margin-top:4px;">Kelola dan saring seluruh tugas operasional prasarana wilayah kelurahan (10 RW & 3 Jenis Regu).</p>
            </div>
            <button type="button" onclick="hapusSemuaTugasAdmin()" style="padding:8px 14px;border-radius:10px;border:1.5px solid #FCA5A5;background:#FEF2F2;color:#DC2626;font-size:0.82rem;font-weight:700;cursor:pointer;white-space:nowrap;">
              Hapus Semua Tugas
            </button>
          </div>

          <!-- Filter Baris 0: Saring Status Pengerjaan -->
          <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;width:100%;margin-bottom:2px;">
            <span style="font-size:0.75rem;font-weight:700;color:#64748B;text-transform:uppercase;letter-spacing:0.5px;min-width:65px;">Status:</span>
            <div style="display:flex;gap:6px;flex-wrap:wrap;flex:1;" id="tugas-filter-status-container">
              <button type="button" class="rf-btn active" id="tf-all" onclick="filterTugasStatus('all', this)">Semua Status</button>
              <button type="button" class="rf-btn" id="tf-proses" onclick="filterTugasStatus('proses', this)">Dalam Penanganan</button>
              <button type="button" class="rf-btn" id="tf-selesai" onclick="filterTugasStatus('selesai', this)">Selesai</button>
              <button type="button" class="rf-btn" id="tf-belum" onclick="filterTugasStatus('belum', this)">Menunggu Verifikasi</button>
            </div>
          </div>

          <!-- Filter Baris 1: Saring Bidang Prasarana -->
          <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;width:100%;">
            <span style="font-size:0.75rem;font-weight:700;color:#64748B;text-transform:uppercase;letter-spacing:0.5px;min-width:65px;">Bidang:</span>
            <div style="display:flex;gap:6px;flex-wrap:wrap;flex:1;" id="tugas-filter-category-container">
              <button type="button" class="rf-btn active" onclick="filterTugasCategory('all', this)">Semua Bidang</button>
              <button type="button" class="rf-btn" onclick="filterTugasCategory('jalan', this)">Prasarana Jalan</button>
              <button type="button" class="rf-btn" onclick="filterTugasCategory('taman', this)">Taman & RTH</button>
              <button type="button" class="rf-btn" onclick="filterTugasCategory('saluran', this)">Saluran Air</button>
              <button type="button" class="rf-btn" onclick="filterTugasCategory('kebersihan', this)">Kebersihan</button>
              <button type="button" class="rf-btn" onclick="filterTugasCategory('kantor', this)">Sarana Kantor</button>
            </div>
          </div>

          <!-- Filter Baris 2: Saring Regu Lapangan (Dropdown) -->
          <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;width:100%;">
            <span style="font-size:0.75rem;font-weight:700;color:#64748B;text-transform:uppercase;letter-spacing:0.5px;min-width:65px;">Regu Tim:</span>
            <div id="tugas-filter-regu-container" style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
              <select id="tugas-filter-regu-select" onchange="filterTugasRegu(this.value)" class="form-select" style="min-width:260px;padding:8px 14px;border-radius:10px;border:1.5px solid #CBD5E1;font-size:0.86rem;font-weight:700;color:#0F172A;background:#FFFFFF;outline:none;cursor:pointer;box-shadow:0 1px 3px rgba(0,0,0,0.04);">
                <option value="all">Semua Regu (Semua Tim Lapangan)</option>
              </select>
            </div>
          </div>
        </div>

        <div class="recent-list-wrap" id="tugas-list-container" style="margin-top: 16px;">
          <!-- Rendered in script -->
        </div>
      </section>

