      <!-- ==================== 1. DASHBOARD VIEW ==================== -->
      <section class="admin-tab-pane active" id="pane-dashboard">
        <!-- Human Greeting Bar -->
        <div class="admin-welcome-bar">
          <div class="admin-welcome-text">
            <div class="admin-welcome-date" id="dash-date">SELASA, 3 NOVEMBER 2026</div>
            <h1 class="admin-welcome-heading">Selamat datang, <span id="dash-user-name"><?= $firstName ?></span></h1>
          </div>
        </div>

        <!-- 6 KPI Stat Cards Grid (Natural, Human-Friendly Copy) -->
        <div class="admin-kpi-grid">
          <!-- 1. Total Laporan -->
          <div class="admin-kpi-card" onclick="openTugasWithStatus('all');" style="cursor:pointer;" title="Lihat semua tugas lapangan">
            <div class="kpi-icon-wrap kpi-slate">
              <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg>
            </div>
            <div class="kpi-body">
              <div class="kpi-label">Total Laporan</div>
              <div class="kpi-value" id="ds-total">10</div>
              <div class="kpi-meta">Laporan masuk bulan ini</div>
            </div>
          </div>

          <!-- 2. Dalam Penanganan -->
          <div class="admin-kpi-card" onclick="openTugasWithStatus('proses');" style="cursor:pointer;" title="Lihat tugas yang sedang dikerjakan">
            <div class="kpi-icon-wrap kpi-orange">
              <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
            </div>
            <div class="kpi-body">
              <div class="kpi-label">Dalam Penanganan</div>
              <div class="kpi-value" id="ds-proses">3</div>
              <div class="kpi-meta">Sedang dikerjakan di lapangan</div>
            </div>
          </div>

          <!-- 3. Selesai -->
          <div class="admin-kpi-card" onclick="openTugasWithStatus('selesai');" style="cursor:pointer;" title="Lihat tugas yang telah selesai 100%">
            <div class="kpi-icon-wrap kpi-blue">
              <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="8 12 11 15 16 9"></polyline></svg>
            </div>
            <div class="kpi-body">
              <div class="kpi-label">Selesai</div>
              <div class="kpi-value" id="ds-selesai">5</div>
              <div class="kpi-meta">Pekerjaan rampung 100%</div>
            </div>
          </div>

          <!-- 4. Menunggu Verifikasi -->
          <div class="admin-kpi-card" onclick="switchAdminTab('verifikasi');" style="cursor:pointer;">
            <div class="kpi-icon-wrap kpi-purple">
              <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 14 14"></polyline></svg>
            </div>
            <div class="kpi-body">
              <div class="kpi-label">Menunggu Verifikasi</div>
              <div class="kpi-value" id="ds-belum">2</div>
              <div class="kpi-meta">Menunggu tinjauan lurah</div>
            </div>
          </div>

          <!-- 5. Telah Disetujui -->
          <div class="admin-kpi-card" onclick="switchAdminTab('rekap');" style="cursor:pointer;">
            <div class="kpi-icon-wrap kpi-green">
              <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path><polyline points="9 12 11 14 15 10"></polyline></svg>
            </div>
            <div class="kpi-body">
              <div class="kpi-label">Telah Disetujui</div>
              <div class="kpi-value" id="ds-terverifikasi">5</div>
              <div class="kpi-meta">Laporan terverifikasi</div>
            </div>
          </div>

          <!-- 6. Petugas Lapangan -->
          <div class="admin-kpi-card" onclick="switchAdminTab('pengguna');" style="cursor:pointer;">
            <div class="kpi-icon-wrap kpi-red">
              <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
            </div>
            <div class="kpi-body">
              <div class="kpi-label">Petugas Lapangan</div>
              <div class="kpi-value" id="ds-petugas-cnt" style="display:flex;flex-direction:column;line-height:1.1;">
                <span id="ds-petugas-num">10</span>
                <span id="ds-petugas-text" style="font-size:0.92rem;font-weight:700;color:#0F172A;margin-top:1px;">Personel</span>
              </div>
              <div class="kpi-meta">2 regu aktif bertugas</div>
            </div>
          </div>
        </div>

        <!-- ==================== DUA KOLOM: STATISTIK TUGAS & KELOLA ANGGOTA REGU (DI ATAS) ==================== -->
        <div class="admin-dash-two-col">
          
          <!-- KOLOM 1: SEBARAN BIDANG TUGAS (DESAIN DATA-DRIVEN BERSIH) -->
          <div style="background:#ffffff;border:1px solid #E2E8F0;border-radius:14px;padding:20px 22px;display:flex;flex-direction:column;box-shadow:0 1px 2px rgba(15,23,42,0.03);">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:14px;">
              <div>
                <h3 style="font-size:0.95rem;font-weight:800;color:#0F172A;margin:0;">Sebaran Bidang Pekerjaan</h3>
                <p style="font-size:0.75rem;color:#64748B;margin:2px 0 0;">Distribusi 10 penanganan tugas aktif</p>
              </div>
              <a href="javascript:void(0)" onclick="switchAdminTab('rekap')" style="font-size:0.78rem;color:#FF5416;font-weight:700;text-decoration:none;display:inline-flex;align-items:center;gap:3px;padding-top:2px;">
                <span>Rekap Lengkap</span>
                <span>&rarr;</span>
              </a>
            </div>

            <div id="dash-stats-category-container" style="flex:1;display:flex;flex-direction:column;justify-content:center;">
              <!-- Dynamic Stacked Bar & Data Roster -->
            </div>
          </div>

          <!-- KOLOM 2: REGU LAPANGAN (TABEL ROSTER PROFESIONAL) -->
          <div style="background:#ffffff;border:1px solid #E2E8F0;border-radius:14px;padding:20px 22px;display:flex;flex-direction:column;box-shadow:0 1px 2px rgba(15,23,42,0.03);">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:14px;">
              <div>
                <h3 style="font-size:0.95rem;font-weight:800;color:#0F172A;margin:0;">Regu Lapangan Aktif</h3>
                <p style="font-size:0.75rem;color:#64748B;margin:2px 0 0;">Penanggung jawab & kekuatan personel wilayah</p>
              </div>
              <a href="javascript:void(0)" onclick="switchAdminTab('regu')" style="font-size:0.78rem;color:#FF5416;font-weight:700;text-decoration:none;display:inline-flex;align-items:center;gap:3px;padding-top:2px;">
                <span>Kelola Regu</span>
                <span>&rarr;</span>
              </a>
            </div>

            <!-- CONTAINER LIST REGU (ROSTER TABLE) -->
            <div id="dash-regu-cards-container" style="flex:1;display:flex;flex-direction:column;justify-content:center;">
              <!-- Dynamic Content -->
            </div>
          </div>

        </div>

        <!-- ==================== TO-DO LIST & EXPORT EXCEL SECTION (DI BAWAH) ==================== -->
        <section class="todo-section">
          <div class="todo-header">
            <div>
              <div class="todo-title">Daftar Tugas & Penugasan Lapangan PPSU</div>
              <div class="todo-desc">Pantau progres pekerjaan lapangan, rincian dokumentasi, dan unduh rekap data ke Excel berdasarkan pilihan periode waktu.</div>
            </div>

            <!-- ACTION BUTTONS: DOWNLOAD EXCEL DENGAN PILIHAN PERIODE -->
            <div class="action-btn-group" style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
              <div style="display:flex;align-items:center;gap:6px;background:#F8FAFC;border:1.5px solid #CBD5E1;padding:7px 11px;border-radius:10px;">
                <span style="font-size:0.75rem;font-weight:700;color:#64748B;">Periode:</span>
                <select id="todo-export-period-select" onchange="onAdminPeriodSelectChanged(this.value)" style="background:transparent;border:none;font-weight:800;font-size:0.8rem;color:#0F172A;cursor:pointer;outline:none;">
                  <option value="all" selected>Semua Periode</option>
                  <option value="today">Hari Ini</option>
                  <option value="yesterday">Kemarin</option>
                  <option value="2days">2 Hari Lalu</option>
                  <option value="3days">3 Hari Lalu</option>
                  <option value="7days">7 Hari</option>
                  <option value="14days">14 Hari</option>
                  <option value="30days">30 Hari</option>
                </select>
              </div>

              <button type="button" class="btn-export-excel" id="btn-admin-download-excel" onclick="downloadLaporanExcel()" title="Download rekap laporan ke format file Excel (.xlsx)">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                <span id="admin-export-btn-label">Download Excel (.xlsx)</span>
              </button>
            </div>
          </div>

          <!-- PILIHAN CHIPS PERIODE WAKTU (HARI INI, KEMARIN, 2 HARI, 3 HARI, 7 HARI, 14 HARI, 30 HARI) -->
          <div style="display:flex;align-items:center;gap:8px;margin-bottom:12px;padding:8px 12px;background:#F8FAFC;border:1px solid #E2E8F0;border-radius:10px;flex-wrap:wrap;">
            <span style="font-size:0.75rem;font-weight:800;color:#475569;display:inline-flex;align-items:center;gap:5px;">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
              Periode:
            </span>
            <div class="todo-time-chips" id="admin-time-chips" style="display:flex;gap:5px;flex-wrap:wrap;">
              <button type="button" class="time-chip-btn active" data-period="all" onclick="filterTodoByPeriod('all', this)">Semua</button>
              <button type="button" class="time-chip-btn" data-period="today" onclick="filterTodoByPeriod('today', this)">Hari Ini</button>
              <button type="button" class="time-chip-btn" data-period="yesterday" onclick="filterTodoByPeriod('yesterday', this)">Kemarin</button>
              <button type="button" class="time-chip-btn" data-period="2days" onclick="filterTodoByPeriod('2days', this)">2 Hari Lalu</button>
              <button type="button" class="time-chip-btn" data-period="3days" onclick="filterTodoByPeriod('3days', this)">3 Hari Lalu</button>
              <button type="button" class="time-chip-btn" data-period="7days" onclick="filterTodoByPeriod('7days', this)">7 Hari</button>
              <button type="button" class="time-chip-btn" data-period="14days" onclick="filterTodoByPeriod('14days', this)">14 Hari</button>
              <button type="button" class="time-chip-btn" data-period="30days" onclick="filterTodoByPeriod('30days', this)">30 Hari</button>
            </div>
          </div>

          <!-- FILTER STATUS TABS -->
          <div class="todo-filter-tabs">
            <button type="button" class="todo-tab-btn active" id="tb-all" onclick="filterTodoList('all', this)">Semua Tugas (<span id="todo-cnt-all">10</span>)</button>
            <button type="button" class="todo-tab-btn" id="tb-belum" onclick="filterTodoList('belum', this)">Perlu Dikerjakan (<span id="todo-cnt-belum">2</span>)</button>
            <button type="button" class="todo-tab-btn" id="tb-proses" onclick="filterTodoList('proses', this)">Sedang Dikerjakan (<span id="todo-cnt-proses">3</span>)</button>
            <button type="button" class="todo-tab-btn" id="tb-selesai" onclick="filterTodoList('selesai', this)">Sudah Selesai (<span id="todo-cnt-selesai">5</span>)</button>
          </div>

          <!-- SEARCH & RW FILTER -->
          <div style="display:flex;gap:10px;flex-wrap:wrap;">
            <div style="flex:1;min-width:240px;">
              <div class="search-input-wrap">
                <span class="search-input-icon">
                  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                </span>
                <input type="text" class="search-input-field" placeholder="Cari tugas berdasarkan nomor ID, jenis pekerjaan, jalan, RW, petugas..." oninput="searchTodoList(this.value)">
              </div>
            </div>
            <div style="width:160px;">
              <select id="todo-filter-rw-select" class="form-input" style="width:100%;height:44px;border-radius:12px;border:1.5px solid #CBD5E1;font-weight:700;font-size:0.85rem;" onchange="filterTodoByRw(this.value)">
                <option value="all">Semua RW (10 RW)</option>
                <option value="RW 01">RW 01</option>
                <option value="RW 02">RW 02</option>
                <option value="RW 03">RW 03</option>
                <option value="RW 04">RW 04</option>
                <option value="RW 05">RW 05</option>
                <option value="RW 06">RW 06</option>
                <option value="RW 07">RW 07</option>
                <option value="RW 08">RW 08</option>
                <option value="RW 09">RW 09</option>
                <option value="RW 10">RW 10</option>
              </select>
            </div>
          </div>

          <!-- DAFTAR TUGAS CONTAINER (FORMAT 100% IDENTIK DENGAN TAB TUGAS) -->
          <div class="recent-list-wrap" id="todo-list-container" style="margin-top:14px;">
            <!-- Dynamic Content -->
          </div>
        </section>
      </section>

