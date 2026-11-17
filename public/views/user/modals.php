<!-- ==================== KAMERA GPS TIMESTAMP VIEWFINDER ==================== -->
<div id="camera-modal" class="camera-modal-overlay hidden">
  <div class="camera-modal-content">
    <div class="camera-flash-overlay" id="camera-flash-overlay"></div>

    <!-- Top Bar: Judul, Putar Kamera, Tutup -->
    <div class="camera-top-bar">
      <div class="camera-slot-badge" id="cam-slot-label">Foto Sebelum</div>
      <div class="camera-top-actions">
        <button type="button" class="cam-btn-icon" id="cam-btn-mirror" onclick="toggleCameraMirror()" title="Mode Cermin (Mirror On / Off)">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3v18"/><path d="m16 7 4 4-4 4"/><path d="m8 7-4 4 4 4"/></svg>
        </button>
        <button type="button" class="cam-btn-icon" id="cam-btn-switch" onclick="switchCameraFacing()" title="Putar Kamera Depan / Belakang">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 10c0-4.418-3.582-8-8-8s-8 3.582-8 8c0 2.21.895 4.21 2.343 5.657L4 18h6v-6l-2.343 2.343A5.98 5.98 0 0 1 6 10c0-3.314 2.686-6 6-6s6 2.686 6 6-2.686 6-6 6a5.99 5.99 0 0 1-4.243-1.757l-1.414 1.414A7.963 7.963 0 0 0 12 20c4.418 0 8-3.582 8-8z"/></svg>
        </button>
        <button type="button" class="cam-btn-icon" onclick="closeCameraModal()" title="Tutup Kamera">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
        </button>
      </div>
    </div>

    <!-- Area Video Kamera & HUD Live GPS -->
    <div class="camera-viewport-wrap">
      <video id="camera-video-feed" autoplay playsinline muted></video>
      <div id="camera-loading-notice" class="camera-loading">
        <div class="cam-spinner"></div>
        <span>Mengaktifkan kamera & sinyal GPS...</span>
      </div>
      <div class="camera-grid-lines">
        <div class="cam-corner tl"></div>
        <div class="cam-corner tr"></div>
        <div class="cam-corner bl"></div>
        <div class="cam-corner br"></div>
      </div>

      <!-- Live Watermark HUD di atas layar video (Sesuai referensi gambar) -->
      <div class="camera-live-hud">
        <div class="cam-badge-tag" id="hud-badge-tag">PPSU DKI JAKARTA • FOTO SEBELUM (0%)</div>
        <div class="cam-time-block">
          <div class="cam-huge-time" id="hud-time">17:42</div>
          <div class="cam-yellow-divider"></div>
          <div class="cam-date-day-col">
            <div class="cam-date" id="hud-date">23/10/2026</div>
            <div class="cam-day" id="hud-day">Jumat</div>
          </div>
        </div>
        <div class="cam-location-lines">
          <div class="cam-loc-line" id="hud-addr-1">Lokasi Belum Ditentukan</div>
          <div class="cam-loc-line" id="hud-addr-2">Kelurahan Pekayon, Pasar Rebo, Jakarta Timur</div>
          <div class="cam-loc-coords" id="hud-coords">GPS: Lat -6.338520, Long 106.865410 (±5m)</div>
        </div>
      </div>
    </div>

    <!-- Tombol Aksi Bawah: Hanya Shutter Jepret Kamera Lapangan -->
    <div class="camera-bottom-bar" style="justify-content:center;">
      <input type="file" id="camera-native-input" accept="image/*" capture="environment" style="display:none;" onchange="handleFileSelect(event)">
      <button type="button" class="cam-shutter-btn" id="cam-shutter-btn" onclick="captureLivePhoto()" title="Jepret Foto Kamera Lapangan">
        <div class="shutter-inner"></div>
      </button>
    </div>
  </div>
</div>

<!-- ==================== POPUP MODAL KONFIRMASI PENGIRIMAN LAPORAN ==================== -->
<div id="modal-confirm-submit" class="confirm-modal-overlay" style="display:none;">
  <div class="confirm-modal-card">
    <div class="confirm-modal-header">
      <div class="confirm-warn-icon">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
      </div>
      <div style="flex:1;">
        <h3 class="confirm-title">Konfirmasi Pengiriman Laporan</h3>
        <p class="confirm-subtitle">Periksa kelengkapan data sebelum dikirim ke sistem</p>
      </div>
      <button type="button" class="confirm-close-btn" onclick="closeConfirmModal()" title="Tutup" aria-label="Tutup">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
      </button>
    </div>

    <!-- Alert Box Penegasan Tidak Bisa Diedit -->
    <div class="confirm-alert-box">
      <div style="font-weight:800;font-size:0.8rem;display:flex;align-items:center;gap:6px;margin-bottom:4px;">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        <span>PENTING: LAPORAN TIDAK DAPAT DIEDIT SETELAH DIKIRIM!</span>
      </div>
      <div style="font-size:0.75rem;line-height:1.45;opacity:0.95;">
        Setelah tombol kirim ditekan, seluruh data penugasan, foto GPS, dan absensi personel langsung tersimpan permanen di database dan tidak dapat diubah/diedit kembali.
      </div>
    </div>

    <!-- Rangkuman Data Pengerjaan -->
    <div class="confirm-recap-box">
      <div class="recap-row">
        <span class="recap-lbl">Tahap Laporan:</span>
        <span class="recap-val highlight" id="modal-recap-stage">Laporan Awal (0%)</span>
      </div>
      <div class="recap-row">
        <span class="recap-lbl">Target Tugas:</span>
        <span class="recap-val" id="modal-recap-tugas">Tugas 1 - Pembersihan Jalan &amp; Trotoar</span>
      </div>
      <div class="recap-row">
        <span class="recap-lbl">Jadwal Hari:</span>
        <span class="recap-val" id="modal-recap-jadwal">Selasa, 27 Okt 2026</span>
      </div>
      <div class="recap-row">
        <span class="recap-lbl">Regu &amp; Role:</span>
        <span class="recap-val" id="modal-recap-regu">Regu 1 • Ketua Regu</span>
      </div>
      <div class="recap-row">
        <span class="recap-lbl">Lokasi Pengerjaan:</span>
        <span class="recap-val" id="modal-recap-lokasi">-</span>
      </div>
      <div class="recap-row">
        <span class="recap-lbl">Patokan Titik:</span>
        <span class="recap-val" id="modal-recap-patokan">-</span>
      </div>
      <div class="recap-row">
        <span class="recap-lbl">Personel Hadir:</span>
        <span class="recap-val" id="modal-recap-personel">4 Orang Hadir</span>
      </div>
    </div>

    <!-- Checkbox Kepastian & Penegasan -->
    <label class="confirm-checkbox-label">
      <input type="checkbox" id="chk-confirm-agree" checked>
      <span>Saya telah memeriksa dan memastikan data lokasi pengerjaan, foto dokumentasi satelit/GPS, dan absensi anggota sudah benar dan akurat.</span>
    </label>

    <!-- Tombol Aksi -->
    <div class="confirm-modal-actions">
      <button type="button" class="btn-confirm-cancel" onclick="closeConfirmModal()">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
        <span>Periksa Kembali</span>
      </button>
      <button type="button" class="btn-confirm-submit" id="btn-final-submit" onclick="executeSubmitConfirmed()">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
        <span>Ya, Yakin &amp; Kirim Sekarang</span>
      </button>
    </div>
  </div>
</div>

<!-- ==================== POPUP PERINGATAN AKUN DIHAPUS ADMIN ==================== -->
<div id="modal-account-deleted" class="confirm-modal-overlay" style="display:none;z-index:999999;background:rgba(15,23,42,0.85);backdrop-filter:blur(8px);">
  <div class="confirm-modal-card" style="border:2px solid #EF4444;box-shadow:0 25px 50px -12px rgba(220,38,38,0.35);text-align:center;padding:28px 24px;max-width:440px;">
    <div style="width:64px;height:64px;border-radius:50%;background:#FEE2E2;border:2px solid #FCA5A5;color:#DC2626;display:inline-flex;align-items:center;justify-content:center;margin:0 auto 16px auto;">
      <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
    </div>
    
    <h3 style="font-size:1.15rem;font-weight:800;color:#0F172A;margin:0 0 6px 0;">Akses Akun Dinonaktifkan</h3>
    <p style="font-size:0.84rem;color:#475467;margin:0 0 16px 0;line-height:1.5;">
      Akun petugas <strong id="deleted-user-name" style="color:#DC2626;"></strong> telah dinonaktifkan atau dihapus dari sistem oleh <strong>Administrator Kelurahan</strong>.
    </p>

    <div style="background:#FEF2F2;border:1px solid #FECACA;border-radius:10px;padding:12px 14px;text-align:left;margin-bottom:20px;">
      <div style="display:flex;gap:8px;align-items:flex-start;">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#DC2626" stroke-width="2" style="flex-shrink:0;margin-top:2px;"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
        <div style="font-size:0.78rem;color:#991B1B;line-height:1.45;">
          <strong>Pemberitahuan Sistem:</strong> Sesi kerja Anda telah dihentikan. Anda tidak lagi dapat mengisi atau mengirimkan laporan lapangan PPSU.
        </div>
      </div>
    </div>

    <button type="button" onclick="window.location.href='logout.php'" style="width:100%;padding:12px;background:#DC2626;color:#FFFFFF;border:none;border-radius:10px;font-weight:700;font-size:0.9rem;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;gap:8px;box-shadow:0 4px 12px rgba(220,38,38,0.25);">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
      <span>Keluar ke Halaman Login</span>
    </button>
  </div>
</div>
