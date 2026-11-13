  <!-- User History Panel (Tab 2) -->
  <div class="user-history-panel" id="user-history-panel">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;gap:8px;flex-wrap:wrap;">
      <div>
        <h3 style="font-size:1.08rem;font-weight:800;color:#0F172A;margin:0;">Riwayat Pengerjaan Regu</h3>
        <div style="font-size:0.75rem;color:#64748B;margin-top:2px;">Laporan terpisah per-regu &amp; periode penugasan</div>
      </div>
      <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
        <button type="button" class="btn-export-excel-user" id="btn-user-export-excel" onclick="downloadUserLaporanExcel()" title="Download rekap laporan ke format file Excel (.xlsx)">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
          <span id="btn-user-excel-label">Export Excel</span>
        </button>
        <button type="button" class="user-pill-btn primary" onclick="switchUserTab('form')">+ Buat Laporan</button>
      </div>
    </div>

    <!-- Filter Bar Khusus Regu & Periode Waktu -->
    <div class="riwayat-filter-bar">
      <!-- Pilihan Regu (Terpisah Per-Regu) -->
      <div class="riwayat-regu-select-row">
        <div style="display:flex;align-items:center;gap:6px;">
          <span style="font-size:0.75rem;font-weight:700;color:#64748B;">Regu Tampil:</span>
          <select id="hist-filter-regu" class="regu-filter-select" onchange="onHistReguFilterChanged(this.value)">
            <option value="SEMUA" selected>Semua Regu PPSU</option>
            <option value="Regu 1">Regu 1</option>
            <option value="Regu 2">Regu 2</option>
            <option value="Regu 3">Regu 3</option>
            <option value="Regu 4">Regu 4</option>
            <option value="Regu 5">Regu 5</option>
            <option value="Regu 6">Regu 6</option>
            <option value="Regu 7">Regu 7</option>
            <option value="Regu 8">Regu 8</option>
            <option value="Regu 9">Regu 9</option>
            <option value="Regu 10">Regu 10</option>
            <option value="Regu Jalan">Regu Jalan</option>
            <option value="Regu Taman">Regu Taman</option>
            <option value="Regu Kantor">Regu Kantor</option>
          </select>
        </div>
        <span id="hist-filtered-count-badge" style="font-size:0.72rem;font-weight:800;color:#0369A1;background:#E0F2FE;padding:2px 8px;border-radius:6px;">Memuat...</span>
      </div>

      <!-- Kategori Periode Waktu (Hari Ini, Kemarin, 2 Hari, 3 Hari, 7 Hari, 14 Hari, 30 Hari) -->
      <div class="riwayat-time-chips" id="riwayat-time-chips">
        <button type="button" class="time-chip-btn active" data-period="all" onclick="setHistoryPeriodFilter('all')">Semua</button>
        <button type="button" class="time-chip-btn" data-period="today" onclick="setHistoryPeriodFilter('today')">Hari Ini</button>
        <button type="button" class="time-chip-btn" data-period="yesterday" onclick="setHistoryPeriodFilter('yesterday')">Kemarin</button>
        <button type="button" class="time-chip-btn" data-period="2days" onclick="setHistoryPeriodFilter('2days')">2 Hari Lalu</button>
        <button type="button" class="time-chip-btn" data-period="3days" onclick="setHistoryPeriodFilter('3days')">3 Hari Lalu</button>
        <button type="button" class="time-chip-btn" data-period="7days" onclick="setHistoryPeriodFilter('7days')">7 Hari</button>
        <button type="button" class="time-chip-btn" data-period="14days" onclick="setHistoryPeriodFilter('14days')">14 Hari</button>
        <button type="button" class="time-chip-btn" data-period="30days" onclick="setHistoryPeriodFilter('30days')">30 Hari</button>
      </div>
    </div>

    <div id="user-history-list">
      <!-- Rendered dynamically -->
    </div>
  </div>
