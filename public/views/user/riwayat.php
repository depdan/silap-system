  <!-- User History Panel (Tab 2) -->
  <div class="user-history-panel" id="user-history-panel">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;gap:8px;flex-wrap:wrap;">
      <div>
        <h3 style="font-size:1.08rem;font-weight:800;color:#0F172A;margin:0;">Riwayat Pengerjaan Regu</h3>
        <div style="font-size:0.75rem;color:#64748B;margin-top:2px;">Laporan terpisah per-regu &amp; periode penugasan</div>
      </div>
      <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
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
            <?php if ($userRole !== 'admin' && empty($userOfficialRegu)): ?>
              <option value="__UNASSIGNED__" selected>Laporan Saya (Belum Ber-Regu)</option>
            <?php else: ?>
              <option value="SEMUA" <?= ($userRole === 'admin') ? 'selected' : '' ?>>Semua Regu PPSU</option>
            <?php endif; ?>
            <?php if (!empty($formattedServerRegu)): ?>
              <?php foreach ($formattedServerRegu as $srOpt): ?>
                <?php $isSelectedRegu = ($userRole !== 'admin' && !empty($userOfficialRegu) && strcasecmp($userOfficialRegu, $srOpt['nama']) === 0); ?>
                <option value="<?= htmlspecialchars($srOpt['nama']) ?>" <?= $isSelectedRegu ? 'selected' : '' ?>><?= htmlspecialchars($srOpt['nama']) ?></option>
              <?php endforeach; ?>
            <?php endif; ?>
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
