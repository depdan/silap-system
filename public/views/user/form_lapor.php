  <!-- Main Focused Form Card (Figma Style) -->
  <div class="user-main-card" id="user-phone-form-view">
    <div class="user-card-body">
      
      <!-- Wizard Step Indicator Header -->
      <div class="user-wizard-progress">
        <button type="button" class="wizard-step-node active" id="wizard-node-1" onclick="goToStep(1)">
          <div class="node-circle" id="node-circle-1">1</div>
          <div class="node-text">
            <span class="node-sub">Tahap 1</span>
            <span class="node-title">Info & Lokasi</span>
          </div>
        </button>
        <div class="wizard-step-line" id="wizard-line"></div>
        <button type="button" class="wizard-step-node" id="wizard-node-2" onclick="goToStep(2)">
          <div class="node-circle" id="node-circle-2">2</div>
          <div class="node-text">
            <span class="node-sub">Tahap 2</span>
            <span class="node-title">Progres & Foto</span>
          </div>
        </button>
      </div>

      <!-- Auto-Save Draft Status Indicator -->
      <div class="draft-status-bar">
        <span>Pengisian laporan lapangan terpandu</span>
        <span class="draft-pill-saved" id="draft-pill-status">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
          Draf otomatis tersimpan
        </span>
      </div>

      <form id="mobile-lapor-form" onsubmit="return false;">

        <!-- ==================== LANGKAH 1: INFORMASI & LOKASI ==================== -->
        <div id="step-container-1">
          
          <!-- Bagian 1: Regu & Jadwal Kerja -->
          <div class="figma-section-card">
            <div class="section-header-row">
              <div class="section-header-icon">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
              </div>
              <div class="section-header-text">
                <h3 class="section-title">Regu &amp; Jadwal Kerja</h3>
                <div class="section-sub">Pilih regu dan jadwal pengerjaan hari ini</div>
              </div>
              <span class="badge-harian">Harian</span>
            </div>
            
            <!-- Tampilan Ringkas Regu Petugas Aktif -->
            <div class="regu-selected-display <?= !empty($userOfficialRegu) ? 'is-assigned' : 'is-pending' ?>">
              <div class="regu-display-info">
                <div class="regu-display-icon">
                  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                </div>
                <div class="regu-display-texts">
                  <span class="regu-display-lbl">Regu Bertugas</span>
                  <span id="u-display-regu-name" class="regu-display-val"><?= !empty($userOfficialRegu) ? htmlspecialchars($userOfficialRegu) : 'Belum Ditugaskan' ?></span>
                </div>
              </div>
              <span class="regu-display-badge <?= !empty($userOfficialRegu) ? 'active' : 'pending' ?>" id="u-display-regu-badge">
                <?= !empty($userOfficialRegu) ? 'Regu Aktif' : 'Menunggu Admin' ?>
              </span>
            </div>
            <input type="hidden" id="u-regu-inp" value="<?= !empty($userOfficialRegu) ? htmlspecialchars($userOfficialRegu) : '' ?>">

            <!-- Kalender Otomatis Jadwal Kerja (Deteksi Hari Ini Otomatis) -->
            <div class="form-field-group">
              <div class="field-label">
                <span>Hari &amp; Tanggal Kerja</span>
                <span class="field-tag" style="background:#ECFDF5;color:#059669;border:1px solid #A7F3D0;font-weight:800;white-space:nowrap;font-size:0.68rem;padding:2px 6px;flex-shrink:0;">Kalender Otomatis</span>
              </div>
              <div class="auto-calendar-box">
                <div class="calendar-display-row">
                  <div class="calendar-icon-wrap">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                  </div>
                  <div class="calendar-date-info">
                    <div class="calendar-hari-label" id="cal-hari-text">Selasa</div>
                    <div class="calendar-tgl-label" id="cal-tgl-text">27 Oktober 2026</div>
                  </div>
                  <div class="calendar-action-wrap">
                    <label for="u-auto-calendar-inp" class="btn-ganti-tanggal" title="Buka Kalender Pemilihan Tanggal">
                      <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line></svg>
                      <span>Pilih Tanggal</span>
                    </label>
                    <input type="date" id="u-auto-calendar-inp" class="native-calendar-inp" onchange="onCalendarDateChanged(this.value)">
                  </div>
                </div>
                <div class="calendar-quick-bar">
                  <button type="button" class="btn-cal-snap" onclick="snapCalendarToToday()">
                    <span class="cal-dot-today">●</span> Jadwal Hari Ini&nbsp;(<span id="cal-today-brief">Selasa</span>)
                  </button>
                  <span id="cal-status-badge" class="badge-cal-status">Terjadwal Otomatis</span>
                </div>
              </div>
              <input type="hidden" id="u-hari-select" value="Selasa">
              <input type="hidden" id="u-tanggal-iso-inp" value="">
              <div id="u-display-hari-pill" style="display:none;">Selasa</div>
            </div>

            <!-- Target Pengerjaan Tugas Ke- (1 - 5) - Disable Otomatis Jika Selesai -->
            <div class="form-field-group">
              <div class="field-label">
                <span>Target Penugasan Harian</span>
                <span class="field-tag" id="u-display-tugas-sub" style="white-space:nowrap;">Tugas 1 dari 5</span>
              </div>

              <!-- Banner Notifikasi Jika 5 Tugas Hari Ini Selesai Semua -->
              <div id="all-tasks-completed-banner" class="all-tasks-banner" style="display:none;">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="color:#10B981;"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                <div style="flex:1;">
                  <div style="font-weight:800;font-size:0.84rem;">Target Harian 5 Tugas Selesai Semua!</div>
                  <div style="font-size:0.72rem;margin-top:1px;opacity:0.9;">Seluruh penugasan untuk hari ini telah berhasil dilaksanakan dan tuntas 100%.</div>
                </div>
              </div>

              <div class="tugas-day-grid" id="tugas-day-grid">
                <button type="button" class="btn-tugas-pill active" id="btn-tugas-1" data-tugas="Tugas 1" onclick="selectTugasKe('Tugas 1')">
                  <span>Tugas&nbsp;1</span>
                </button>
                <button type="button" class="btn-tugas-pill" id="btn-tugas-2" data-tugas="Tugas 2" onclick="selectTugasKe('Tugas 2')">
                  <span>Tugas&nbsp;2</span>
                </button>
                <button type="button" class="btn-tugas-pill" id="btn-tugas-3" data-tugas="Tugas 3" onclick="selectTugasKe('Tugas 3')">
                  <span>Tugas&nbsp;3</span>
                </button>
                <button type="button" class="btn-tugas-pill" id="btn-tugas-4" data-tugas="Tugas 4" onclick="selectTugasKe('Tugas 4')">
                  <span>Tugas&nbsp;4</span>
                </button>
                <button type="button" class="btn-tugas-pill" id="btn-tugas-5" data-tugas="Tugas 5" onclick="selectTugasKe('Tugas 5')">
                  <span>Tugas&nbsp;5</span>
                </button>
              </div>
              <input type="hidden" id="u-tugas-ke-select" value="Tugas 1">
              <span id="u-display-tugas-badge" style="display:none;">Tugas 1</span>
              <span id="u-display-tugas-hari" style="display:none;">Selasa</span>
            </div>
          </div>

          <!-- Bagian 2: Jenis Pekerjaan -->
          <div class="figma-section-card">
            <div class="section-header-row">
              <div class="section-header-icon" style="background:#FFF7ED;color:#C2410C;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>
              </div>
              <div class="section-header-text">
                <h3 class="section-title">Jenis Pekerjaan Lapangan</h3>
                <div class="section-sub">Pilih bidang tugas yang dilaksanakan</div>
              </div>
            </div>

            <div class="job-cards-vertical" id="job-cards-vertical">
              
              <!-- Pilihan 1: Pembersihan Jalan & Trotoar -->
              <div class="job-card-selectable active" data-job="Pembersihan Jalan & Trotoar" data-kat="kebersihan" onclick="selectJobCard(this)">
                <div class="job-card-icon" style="background:#FFF7ED;color:#EA580C;">
                  <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 21h18"/><path d="M19 21v-4a2 2 0 0 0-2-2H7a2 2 0 0 0-2 2v4"/><path d="M12 3v12"/></svg>
                </div>
                <div class="job-card-text">
                  <div class="job-card-title">Pembersihan Jalan &amp; Trotoar</div>
                  <div class="job-card-desc">Sapu sampah kering, debu, dedaunan gugur, dan kotoran bahu jalan.</div>
                </div>
                <div class="job-card-check">✓</div>
              </div>

              <!-- Pilihan 2: Pembersihan Saluran Air & Got -->
              <div class="job-card-selectable" data-job="Pembersihan Saluran Air & Got" data-kat="saluran" onclick="selectJobCard(this)">
                <div class="job-card-icon" style="background:#F0F9FF;color:#0284C7;">
                  <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 12c1.5-1.5 3.5-1.5 5 0s3.5 1.5 5 0 3.5-1.5 5 0 3.5-1.5 5 0"/><path d="M2 17c1.5-1.5 3.5-1.5 5 0s3.5 1.5 5 0 3.5-1.5 5 0 3.5-1.5 5 0"/></svg>
                </div>
                <div class="job-card-text">
                  <div class="job-card-title">Pembersihan Saluran Air &amp; Got</div>
                  <div class="job-card-desc">Keruk lumpur selokan, angkat rumput liar, dan lancarkan tali air.</div>
                </div>
                <div class="job-card-check">✓</div>
              </div>

              <!-- Pilihan 3: Perawatan Taman & Jalur Hijau -->
              <div class="job-card-selectable" data-job="Perawatan Taman & Jalur Hijau" data-kat="taman" onclick="selectJobCard(this)">
                <div class="job-card-icon" style="background:#ECFDF5;color:#059669;">
                  <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22v-7"/><path d="M17 14c2.2 0 4-1.8 4-4 0-1.8-1.2-3.3-2.8-3.8C18 3.9 15.3 2 12 2S6 3.9 5.8 6.2C4.2 6.7 3 8.2 3 10c0 2.2 1.8 4 4 4"/></svg>
                </div>
                <div class="job-card-text">
                  <div class="job-card-title">Perawatan Taman &amp; Jalur Hijau</div>
                  <div class="job-card-desc">Pemeliharaan tanaman hias, pot lingkungan, dan pembersihan area taman.</div>
                </div>
                <div class="job-card-check">✓</div>
              </div>

              <!-- Pilihan 4: Pemangkasan Pohon & Dahan Rendah -->
              <div class="job-card-selectable" data-job="Pemangkasan Pohon & Dahan Rendah" data-kat="taman" onclick="selectJobCard(this)">
                <div class="job-card-icon" style="background:#FFFBEB;color:#D97706;">
                  <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M7 20h10"/><path d="M10 20l4-16"/><path d="M14 8l5 2"/><path d="M6 13l4-2"/></svg>
                </div>
                <div class="job-card-text">
                  <div class="job-card-title">Pemangkasan Pohon &amp; Dahan Rendah</div>
                  <div class="job-card-desc">Pangkas ranting dahan (maks. 2 meter) yang menghalangi jalan / kabel utilitas.</div>
                </div>
                <div class="job-card-check">✓</div>
              </div>

              <!-- Pilihan 5: Perbaikan Jalan & Trotoar Ringan -->
              <div class="job-card-selectable" data-job="Perbaikan Jalan & Trotoar Ringan" data-kat="jalan" onclick="selectJobCard(this)">
                <div class="job-card-icon" style="background:#F1F5F9;color:#475569;">
                  <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19L9 5"/><path d="M20 19L15 5"/><line x1="12" y1="7" x2="12" y2="9"/><line x1="12" y1="13" x2="12" y2="15"/></svg>
                </div>
                <div class="job-card-text">
                  <div class="job-card-title">Perbaikan Jalan &amp; Trotoar Ringan</div>
                  <div class="job-card-desc">Tambal lubang kecil, rapikan paving amblas, dan perbaiki kanstin lepas.</div>
                </div>
                <div class="job-card-check">✓</div>
              </div>

            </div>
            <input type="hidden" id="selected-job-val" value="Pembersihan Jalan & Trotoar">
            <input type="hidden" id="selected-kat-val" value="kebersihan">
          </div>

          <!-- Bagian Tambahan: Target Penyelesaian (Panjang Target Pengerjaan dalam Meter) -->
          <div class="figma-section-card">
            <div class="section-header-row">
              <div class="section-header-icon" style="background:#ECFDF5;color:#059669;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 12h20"/><path d="M6 8v8"/><path d="M18 8v8"/><path d="M10 10v4"/><path d="M14 10v4"/></svg>
              </div>
              <div class="section-header-text">
                <h3 class="section-title">Target Penyelesaian (m)</h3>
                <div class="section-sub">Panjang target pengerjaan fisik dalam satuan Meter</div>
              </div>
            </div>

            <div class="form-field-item" style="margin-bottom:0;">
              <div class="field-label">
                <span>Panjang Target Pengerjaan (Meter)&nbsp;<span style="color:#DC2626;font-weight:800;">*</span></span>
                <span class="field-tag-required">Wajib Diisi</span>
              </div>
              <div style="position:relative; display:flex; align-items:center;">
                <input type="number" id="u-target-meter-inp" class="u-input-field" placeholder="Ketik target meter (contoh: 100)" value="" min="1" step="any" style="padding-right: 68px; font-weight:700; font-size:1rem;" oninput="triggerDraftAutoSave(); this.style.borderColor=''; this.style.boxShadow='';" required>
                <span style="position:absolute; right:12px; font-weight:800; color:#059669; font-size:12px; pointer-events:none; background:#ECFDF5; padding:3px 8px; border-radius:6px; border:1px solid #A7F3D0;">Meter</span>
              </div>
              <div style="font-size:0.75rem; color:#64748B; margin-top:6px; line-height:1.4;">Target estimasi panjang atau bentangan fisik penanganan lapangan (dalam satuan Meter).</div>
            </div>
          </div>

          <!-- Bagian 3: Detail Lokasi & Tim Personel -->
          <div class="figma-section-card">
            <div class="section-header-row">
              <div class="section-header-icon" style="background:#F0F9FF;color:#0369A1;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
              </div>
              <div class="section-header-text">
                <h3 class="section-title">Lokasi &amp; Personel</h3>
                <div class="section-sub">Detail titik pengerjaan dan kontak regu</div>
              </div>
            </div>

            <!-- Wilayah RW (Wajib Diisi) -->
            <div class="form-field-item">
              <div class="field-label">
                <span>Wilayah RW (Pekayon)&nbsp;<span style="color:#DC2626;font-weight:800;">*</span></span>
                <span class="field-tag-required">Wajib Diisi</span>
              </div>
              <select id="u-rw-select" class="u-input-field" onchange="onRwSelectChange(); this.style.borderColor=''; this.style.boxShadow='';" required>
                <option value="" disabled selected>-- Pilih Wilayah RW --</option>
                <option value="RW 01">RW 01 - Jl. Lapan</option>
                <option value="RW 02">RW 02 - Jl. Gandaria</option>
                <option value="RW 03">RW 03 - Jl. Gandaria</option>
                <option value="RW 04">RW 04 - Jl. Buah</option>
                <option value="RW 05">RW 05 - Jl. Raya Bogor</option>
                <option value="RW 06">RW 06 - Jl. Belly</option>
                <option value="RW 07">RW 07 - Jl. Tipar</option>
                <option value="RW 08">RW 08 - Jl. Sukamto</option>
                <option value="RW 09">RW 09 - Jl. Haji Balok</option>
                <option value="RW 10">RW 10 - Jl. Lewa</option>
              </select>
            </div>

            <!-- Alamat Jalan (Wajib Diisi Penuh) -->
            <div class="form-field-item">
              <div class="field-label">
                <span>Nama Jalan / Lokasi&nbsp;<span style="color:#DC2626;font-weight:800;">*</span></span>
                <span class="field-tag-required">Wajib Diisi</span>
              </div>
              <input type="text" id="u-custom-address-inp" class="u-input-field" placeholder="Ketik nama jalan / lokasi pengerjaan (contoh: Jl. Madrasah No. 5)..." value="" oninput="updateLocationDisplay(); triggerDraftAutoSave(); this.style.borderColor=''; this.style.boxShadow='';" required>
            </div>

            <!-- Patokan Lokasi (Wajib Diisi Penuh) -->
            <div class="form-field-item">
              <div class="field-label">
                <span>Patokan Lokasi&nbsp;<span style="color:#DC2626;font-weight:800;">*</span></span>
                <span class="field-tag-required">Wajib Diisi</span>
              </div>
              <input type="text" id="u-patokan-inp" class="u-input-field" placeholder="Ketik patokan detail (contoh: Trotoar samping pos kamling RW 07)..." value="" oninput="triggerDraftAutoSave(); this.style.borderColor=''; this.style.boxShadow='';" required>
            </div>

            <!-- Kontak WhatsApp Regu & Nama Petugas Pelapor -->
            <div class="officer-contact-grid">
              <div>
                <div class="field-label">
                  <span>Pelapor / Ketua&nbsp;<span style="color:#DC2626;font-weight:800;">*</span></span>
                </div>
                <input type="text" id="u-petugas-inp" class="u-input-field" placeholder="Ketik nama petugas pelapor..." value="<?= htmlspecialchars($userName ?? 'Budi Santoso') ?>" oninput="triggerDraftAutoSave();" required>
              </div>
              <div>
                <div class="field-label">
                  <span>No. WhatsApp&nbsp;<span style="color:#DC2626;font-weight:800;">*</span></span>
                </div>
                <input type="tel" id="u-nohp-inp" class="u-input-field" inputmode="numeric" pattern="[0-9]*" maxlength="15" placeholder="Contoh: 081234567890" value="<?= htmlspecialchars($userPhone ?? '081234567890') ?>" oninput="this.value = this.value.replace(/\D/g, ''); triggerDraftAutoSave();" required>
              </div>
            </div>

            <!-- Jumlah Personel Dropdown List Ceklis & Absensi Kehadiran Anggota -->
            <div class="form-field-item">
              <div class="field-label">
                <span>Daftar Personel &amp; Absensi Kehadiran <span style="color:#DC2626;font-weight:800;">*</span></span>
                <span class="field-tag" id="absen-count-badge" style="background:#ECFDF5;color:#065F46;border:1px solid #A7F3D0;font-weight:800;">4 Orang Hadir</span>
              </div>
              
              <!-- Trigger Dropdown Absensi -->
              <div class="absen-dropdown-trigger" id="absen-dropdown-btn" onclick="toggleAbsenDropdown()">
                <div style="display:flex;align-items:center;gap:9px;flex:1;min-width:0;">
                  <div class="absen-user-icon">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                  </div>
                  <div style="flex:1;min-width:0;">
                    <div style="font-size:0.88rem;font-weight:800;color:#0F172A;" id="absen-trigger-title">4 Personel Regu Hadir</div>
                    <div style="font-size:0.72rem;color:#64748B;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" id="absen-trigger-sub">Budi Santoso (Ketua), Ahmad Suryadi, Joko Prasetyo, Dani Irawan</div>
                  </div>
                </div>
                <div class="absen-chevron" id="absen-chevron">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
                </div>
              </div>

              <!-- Panel Ceklis Absensi Anggota -->
              <div class="absen-dropdown-panel" id="absen-dropdown-panel" style="display:none;">
                <div class="absen-panel-tip">
                  <span>Centang nama anggota regu yang hadir bekerja hari ini:</span>
                </div>
                <div class="absen-checklist-grid" id="absen-checklist-grid">
                  <!-- Diisi secara dinamis via JS -->
                </div>
                <!-- Baris Tambah Anggota Baru -->
                <div class="absen-add-row">
                  <input type="text" id="new-member-name-inp" class="u-input-field" placeholder="Ketik nama anggota tambahan..." style="min-height:36px;font-size:0.8rem;padding:6px 10px;" onkeydown="if(event.key==='Enter'){event.preventDefault();addNewMemberToChecklist();}">
                  <button type="button" class="btn-tambah-anggota" onclick="addNewMemberToChecklist()">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                    <span>Tambah</span>
                  </button>
                </div>
              </div>

              <input type="hidden" id="u-jml-petugas-inp" value="4">
            </div>

            <!-- Live GPS & Interactive Map Box -->
            <div class="location-box-card">
              <!-- Header Row: Label & Refresh Button -->
              <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:8px;width:100%;box-sizing:border-box;">
                <div style="flex:1;min-width:0;">
                  <div style="display:flex;align-items:center;gap:6px;margin-bottom:4px;flex-wrap:wrap;">
                    <span style="font-size:0.68rem;font-weight:800;color:var(--color-slate-600);text-transform:uppercase;letter-spacing:0.04em;">Titik GPS Lapangan</span>
                    <span style="font-size:0.62rem;background:#E2E8F0;color:#334155;padding:1px 6px;border-radius:4px;font-weight:700;">Uji Koordinat</span>
                  </div>
                  <div class="loc-main" id="u-display-location" style="font-size:0.85rem;font-weight:800;color:var(--color-slate-900);line-height:1.35;white-space:normal;word-break:break-word;">Pilih RW &amp; ketik lokasi pengerjaan...</div>
                  <div class="loc-coords-sub" id="u-display-coords" style="font-size:0.72rem;font-weight:700;color:var(--color-emerald-700);margin-top:3px;display:flex;align-items:center;gap:4px;">
                    <span class="gps-dot-pulse">●</span>
                    <span id="u-coords-text">Lat -6.335500, Long +106.864200 (Presisi RW 01 Pekayon)</span>
                  </div>
                </div>
                <button type="button" class="btn-loc-change" id="btn-gps-sync" onclick="refreshCurrentGPS()" title="Uji koordinat dari sensor satelit perangkat" style="flex-shrink:0;margin-top:2px;">
                  <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg>
                  <span id="btn-gps-sync-label">Refresh GPS</span>
                </button>
              </div>

              <!-- Leaflet Map Container -->
              <div style="position:relative;width:100%;border-radius:10px;overflow:hidden;border:1px solid var(--color-slate-200);box-shadow:0 1px 3px rgba(0,0,0,0.06);">
                <div id="pekayon-live-map" style="width:100%;height:185px;background:#F1F5F9;z-index:1;"></div>
                <div style="position:absolute;bottom:6px;left:8px;z-index:400;background:rgba(15,23,42,0.8);backdrop-filter:blur(4px);color:#fff;padding:3px 8px;border-radius:6px;font-size:0.66rem;font-weight:600;display:flex;align-items:center;gap:5px;pointer-events:none;">
                  <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="#94A3B8" stroke-width="2.5"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                  <span>Pin GPS terkunci – tidak dapat digeser</span>
                </div>
                <a href="https://www.google.com/maps?q=-6.335500,106.864200" target="_blank" class="map-external-link" id="btn-view-map" title="Buka dan uji titik di Google Maps" style="position:absolute;top:6px;right:8px;z-index:400;background:#FFFFFF;color:var(--color-slate-800);border:1px solid #CBD5E1;box-shadow:0 2px 4px rgba(0,0,0,0.1);padding:4px 8px;border-radius:6px;font-size:0.68rem;font-weight:700;display:flex;align-items:center;gap:4px;text-decoration:none;">
                  <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="1 6 1 22 8 18 16 22 23 18 23 2 16 6 8 2 1 6"></polygon></svg>
                  <span>Google Maps</span>
                </a>
              </div>

              <!-- GPS Distance / ISP Gateway Notice if detected outside Pekayon -->
              <div id="gps-distance-alert" style="display:none;padding:7px 10px;border-radius:8px;background:#FEF3C7;border:1px solid #FCD34D;color:#92400E;font-size:0.73rem;font-weight:600;line-height:1.35;align-items:flex-start;gap:6px;">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="flex-shrink:0;margin-top:1px;"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                <span id="gps-distance-text">Info Uji: Koordinat tersinkron untuk pengujian. Alamat pengerjaan tetap mengikuti input manual.</span>
              </div>
            </div>
          </div>

          <!-- Tombol Lanjut ke Langkah 2 -->
          <button type="button" class="btn-step-next" onclick="goToStep(2)">
            <div style="display:flex;flex-direction:column;align-items:flex-start;line-height:1.25;flex:1;">
              <span style="font-size:0.96rem;font-weight:800;letter-spacing:0.2px;">Lanjut ke Foto Dokumentasi</span>
              <span style="font-size:0.74rem;font-weight:600;opacity:0.9;margin-top:2px;">Langkah 2: Ambil foto bertanda air GPS</span>
            </div>
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="flex-shrink:0;"><polyline points="9 18 15 12 9 6"/></svg>
          </button>
        </div>

        <!-- ==================== LANGKAH 2: PROGRES & DOKUMENTASI FOTO ==================== -->
        <div id="step-container-2" style="display:none;">

          <!-- Sesi Notice Banner -->
          <div id="sesi-notice-container" class="sesi-notice-banner initial">
            <div class="sesi-badge-tag">Laporan Awal (0%)</div>
            <div class="sesi-title">Foto Kondisi Awal Sebelum Bekerja</div>
            <div class="sesi-desc">Cukup ambil foto kondisi <strong>Sebelum (0%)</strong>, lalu kirim laporan awal. Foto proses &amp; hasil akhir diselesaikan di sesi lanjutan setelah pekerjaan selesai.</div>
          </div>

          <!-- Persentase Penyelesaian Pekerjaan -->
          <div class="figma-section-card">
            <div class="section-header-row">
              <div class="section-header-icon" style="background:#FFF7ED;color:#C2410C;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
              </div>
              <div class="section-header-text">
                <h3 class="section-title">Persentase Penyelesaian</h3>
                <div class="section-sub">Tahapan kemajuan pekerjaan di lapangan</div>
              </div>
            </div>
            
            <!-- Quick Progress Shortcut Buttons -->
            <div class="quick-progress-pills">
              <button type="button" class="btn-prog-pill active" id="pill-prog-0" onclick="setQuickProgres(0)">
                0% Mulai
              </button>
              <button type="button" class="btn-prog-pill locked" id="pill-prog-50" onclick="setQuickProgres(50)" title="Terkunci pada Sesi Awal 0%">
                50% Proses
              </button>
              <button type="button" class="btn-prog-pill locked" id="pill-prog-100" onclick="setQuickProgres(100)" title="Terkunci pada Sesi Awal 0%">
                100% Selesai
              </button>
            </div>

            <div class="u-range-wrap">
              <input type="range" id="u-progres-range" class="u-range-slider" min="0" max="100" step="5" value="0" disabled oninput="handleProgresSlider(this.value)">
              <span class="u-range-badge" id="u-progres-badge">0%</span>
            </div>
          </div>

          <!-- Foto Dokumentasi Pekerjaan -->
          <div class="figma-section-card">
            <div class="section-header-row">
              <div class="section-header-icon" style="background:#ECFDF5;color:#059669;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"></path><circle cx="12" cy="13" r="4"></circle></svg>
              </div>
              <div class="section-header-text">
                <h3 class="section-title">Dokumentasi Foto Lapangan</h3>
                <div class="section-sub">Dilengkapi stempel watermark waktu &amp; GPS</div>
              </div>
            </div>

            <div class="photo-slot-grid">
              
              <!-- 1. Foto Sebelum (0%) -->
              <div class="photo-slot-card" id="slot-sebelum" onclick="openCameraModal('sebelum')">
                <svg class="photo-camera-icon" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                  <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"></path>
                  <circle cx="12" cy="13" r="4"></circle>
                </svg>
                <div class="photo-slot-title">1. Sebelum (0%)</div>
                <div class="photo-slot-sub">Kondisi awal lokasi</div>
                <span class="photo-stage-pill ready">Wajib Awal</span>
              </div>

              <!-- 2. Foto Proses (Saat Pengerjaan) -->
              <div class="photo-slot-card locked" id="slot-proses" onclick="openCameraModal('proses')">
                <svg class="photo-camera-icon" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                  <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                  <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                </svg>
                <div class="photo-slot-title">2. Proses Kerja (50%)</div>
                <div class="photo-slot-sub">Saat pengerjaan</div>
                <span class="photo-stage-pill pending">Terkunci (Kirim 0% Dulu)</span>
              </div>

              <!-- 3. Foto Sesudah (100%) -->
              <div class="photo-slot-card locked" id="slot-sesudah" onclick="openCameraModal('sesudah')">
                <svg class="photo-camera-icon" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                  <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                  <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                </svg>
                <div class="photo-slot-title">3. Sesudah (100%)</div>
                <div class="photo-slot-sub">Hasil akhir tuntas</div>
                <span class="photo-stage-pill pending">Terkunci (Kirim 50% Dulu)</span>
              </div>
            </div>
          </div>

          <!-- Catatan atau Kendala -->
          <div class="figma-section-card">
            <div class="section-header-row">
              <div class="section-header-icon" style="background:#F1F5F9;color:#475569;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg>
              </div>
              <div class="section-header-text">
                <h3 class="section-title">Catatan atau Kendala <span style="font-size:0.75rem;font-weight:500;color:#64748B;">(Opsional)</span></h3>
                <div class="section-sub">Keterangan tambahan situasi lapangan jika ada hambatan</div>
              </div>
            </div>
            <textarea id="u-catatan-inp" class="u-textarea" placeholder="Tuliskan kendala di lapangan jika ada (misal: cuaca hujan lebat, material pengangkut kurang, lalu lintas padat, dsb.)..." oninput="triggerDraftAutoSave()"></textarea>
          </div>

          <!-- Navigasi Langkah 2: Kembali & Kirim Laporan -->
          <div class="stepper-actions-row">
            <button type="button" class="btn-step-back" onclick="goToStep(1)" title="Kembali ke Langkah 1 tanpa menghapus isian">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"/></svg>
              <span>Kembali</span>
            </button>
            <button type="button" class="btn-kirim-laporan" id="btn-submit-mobile-lapor" onclick="requestSubmitConfirmation()">
              <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
              <span id="btn-submit-text">Kirim Laporan Awal (Mulai 0% – Foto Sebelum)</span>
            </button>
          </div>
        </div>

      </form>
    </div>
  </div>
