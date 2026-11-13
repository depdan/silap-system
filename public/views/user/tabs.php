  <!-- Segmented Tab Navigation -->
  <div class="user-tabs-bar">
    <button type="button" class="user-tab-item active" id="tab-btn-form" onclick="switchUserTab('form')">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M12 20h9"></path><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path></svg>
      <span>Input Laporan</span>
    </button>
    <button type="button" class="user-tab-item" id="tab-btn-history" onclick="switchUserTab('history')">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg>
      <span>Riwayat Log (<span id="user-hist-count">0</span>)</span>
    </button>
  </div>

