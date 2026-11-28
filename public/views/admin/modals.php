<!-- ==================== MODAL 1: DETAIL LAPORAN ==================== -->
<div class="modal-overlay hidden" id="modal-detail" onclick="if(event.target === this) closeDetailModal()">
  <div class="modal-card" style="max-width: 740px;">
    <div class="modal-head">
      <div>
        <div style="display:flex;align-items:center;gap:6px;margin-bottom:4px;flex-wrap:wrap;">
          <span class="sc-tag-pill" id="m-kat-tag" style="background:#FFF7ED;color:#C2410C;border:1px solid #FFEDD5;font-weight:700;font-size:0.75rem;padding:3px 10px;border-radius:999px;">Prasarana</span>
          <span class="sc-tag-pill" id="m-id-tag" style="background:#F1F5F9;color:#FF5416;font-weight:800;font-size:0.75rem;padding:3px 10px;border-radius:999px;">#PKJ-000</span>
          <span class="badge-rw" id="m-rw-tag">RW 01</span>
          <span class="badge-minggu" id="m-minggu-tag">Minggu ke-4</span>
          <span class="badge-regu" id="m-regu-tag">Regu Jalan</span>
        </div>
        <h2 class="modal-title" id="m-title" style="margin-top: 4px;font-size:1.15rem;">Judul Laporan</h2>
        <p id="m-desc-subtitle" style="margin:4px 0 0 0;font-size:0.84rem;color:#475569;line-height:1.45;"></p>
      </div>
      <button class="btn-close-modal" type="button" onclick="closeDetailModal()" title="Tutup Modal" aria-label="Tutup">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
      </button>
    </div>
    
    <div class="modal-body">
      <!-- Petugas & Kontak Regu Bar -->
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;background:#F8FAFC;padding:12px 16px;border-radius:12px;border:1px solid #E2E8F0;flex-wrap:wrap;gap:8px;">
        <div style="font-size:0.85rem;color:#64748B;">
          Petugas: <strong id="m-petugas" style="color:#0F172A;">-</strong> (<span id="m-jml-petugas">4</span> orang) · <span id="m-tanggal">-</span>
          <div style="margin-top:4px;">
            No. Telepon / WA: <a href="javascript:void(0)" id="m-nohp-link" style="color:#0284C7;font-weight:800;text-decoration:none;">-</a>
          </div>
        </div>
        <span id="m-status-pill" class="badge-status badge-selesai"><span class="status-dot"></span>Selesai</span>
      </div>

      <!-- Lokasi Lapangan Lengkap & Patokan -->
      <div style="margin-bottom:14px;background:#FFFFFF;border:1px solid #E2E8F0;border-radius:10px;padding:12px 14px;">
        <div style="font-size:0.75rem;font-weight:700;color:#64748B;text-transform:uppercase;letter-spacing:0.04em;margin-bottom:4px;">Lokasi Pengerjaan yang Benar dan Jelas:</div>
        <div style="font-size:0.94rem;font-weight:800;color:#0F172A;" id="m-alamat">-</div>
        <div style="font-size:0.82rem;color:#475569;margin-top:3px;">Patokan: <span id="m-patokan" style="font-weight:600;">-</span></div>
        <div style="font-size:0.76rem;color:#0284C7;margin-top:4px;font-family:monospace;" id="m-coords">GPS: -</div>
      </div>

      <!-- Persentase Penyelesaian Pekerjaan -->
      <div style="margin-bottom:14px;">
        <div style="display:flex;justify-content:space-between;font-size:0.78rem;font-weight:700;color:#334155;margin-bottom:4px;">
          <span>Persentase Penyelesaian Pekerjaan:</span>
          <span id="m-progres-pct" style="color:#EA580C;font-size:0.88rem;font-weight:800;">100%</span>
        </div>
        <div style="background:#E2E8F0;height:8px;border-radius:4px;overflow:hidden;">
          <div id="m-progres-bar" style="background:#10B981;height:100%;width:100%;"></div>
        </div>
      </div>

      <!-- Catatan atau Kendala -->
      <div style="margin-bottom:16px;background:#FFFBEB;border:1px solid #FEF3C7;border-radius:10px;padding:12px 14px;">
        <div style="font-size:0.75rem;font-weight:700;color:#92400E;text-transform:uppercase;letter-spacing:0.04em;margin-bottom:4px;">Catatan atau Kendala Pengerjaan:</div>
        <p style="font-size:0.88rem;color:#78350F;line-height:1.6;margin:0;" id="m-kendala">-</p>
      </div>

      <!-- Bukti 3 Foto Lapangan (Sebelum, Proses, Sesudah) -->
      <div style="margin-bottom:16px;">
        <div style="font-size:0.75rem;font-weight:700;color:#64748B;text-transform:uppercase;letter-spacing:0.04em;margin-bottom:8px;">Bukti Foto Dokumentasi (Sebelum, Selama Proses, Sesudah):</div>
        <div id="m-photos-grid" style="display:grid;grid-template-columns:repeat(3, 1fr);gap:10px;">
          <!-- Photos rendered here -->
        </div>
      </div>

      <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;margin-top:16px;border-top:1px solid #E2E8F0;padding-top:14px;flex-wrap:wrap;">
        <button type="button" class="btn-modal-secondary" id="btn-delete-report-action" onclick="deleteCurrentReport()" style="color:#DC2626;border-color:#FCA5A5;background:#FEF2F2;">Hapus Tugas</button>
        <div style="display:flex;gap:10px;align-items:center;margin-left:auto;">
          <button type="button" class="btn-modal-secondary" onclick="closeDetailModal()">Tutup</button>
          <button type="button" class="btn-modal-primary" id="btn-verify-action" onclick="verifyCurrentReport()">Verifikasi & Setujui</button>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- ==================== MODAL 2: TAMBAH ANGGOTA ==================== -->
<div class="modal-overlay hidden" id="modal-tambah-anggota" onclick="if(event.target === this) closeTambahAnggotaModal()">
  <div class="modal-card" style="max-width: 540px;">
    <div class="modal-head">
      <div>
        <h2 class="modal-title" id="modal-agt-title">Tambah Anggota PPSU</h2>
        <p style="font-size:0.8rem;color:#64748B;margin-top:2px;">Masukkan biodata personel lapangan untuk penugasan wilayah.</p>
      </div>
      <button class="btn-close-modal" type="button" onclick="closeTambahAnggotaModal()" title="Tutup" aria-label="Tutup">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
      </button>
    </div>
    <div class="modal-body">
      <form id="form-tambah-anggota" onsubmit="handleSimpanAnggota(event)">
        <input type="hidden" id="inp-agt-edit-id" value="">
        <div style="margin-bottom:14px;">
          <label style="display:block;font-size:0.8rem;font-weight:700;color:#475467;margin-bottom:4px;">Nama Lengkap Petugas *</label>
          <input type="text" id="inp-agt-nama" class="form-input" style="width:100%;box-sizing:border-box;" placeholder="Contoh: Budi Prasetyo" required>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:14px;">
          <div>
            <label style="display:block;font-size:0.8rem;font-weight:700;color:#475467;margin-bottom:4px;">Role / Peran Petugas *</label>
            <select id="inp-agt-role" class="form-input" style="width:100%;box-sizing:border-box;">
              <option value="Anggota Regu">Anggota Regu</option>
              <option value="Ketua Regu">Ketua Regu</option>
            </select>
          </div>
          <div>
            <label style="display:block;font-size:0.8rem;font-weight:700;color:#475467;margin-bottom:4px;">No. HP / WA <span style="font-weight:500;color:#64748B;">(Wajib u/ Ketua)</span></label>
            <input type="text" id="inp-agt-hp" class="form-input" style="width:100%;box-sizing:border-box;" placeholder="0812-xxxx-xxxx">
          </div>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:18px;">
          <div>
            <label style="display:block;font-size:0.8rem;font-weight:700;color:#475467;margin-bottom:4px;">Bidang Penanganan</label>
            <select id="inp-agt-bidang" class="form-input" style="width:100%;box-sizing:border-box;">
              <option value="jalan">Prasarana Jalan</option>
              <option value="taman">Taman & RTH</option>
              <option value="saluran">Saluran Air</option>
              <option value="kebersihan">Kebersihan Lingkungan</option>
              <option value="kantor">Sarana Kantor Kelurahan</option>
            </select>
          </div>
          <div>
            <label style="display:block;font-size:0.8rem;font-weight:700;color:#475467;margin-bottom:4px;">Penempatan Regu</label>
            <select id="inp-agt-regu" class="form-input" style="width:100%;box-sizing:border-box;">
              <!-- Populated dynamically -->
            </select>
          </div>
        </div>

        <div id="agt-credentials-box" style="background:#FFF7ED;border:1px solid #FED7AA;border-radius:12px;padding:14px;margin-bottom:18px;">
          <div style="font-size:0.78rem;font-weight:800;color:#C2410C;margin-bottom:10px;display:flex;align-items:center;gap:6px;">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
            <span>Akun Login Ketua Regu (ID &amp; Password)</span>
          </div>
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
            <div>
              <label style="display:block;font-size:0.76rem;font-weight:700;color:#475467;margin-bottom:4px;">ID / Username Login</label>
              <input type="text" id="inp-agt-username" class="form-input" style="width:100%;box-sizing:border-box;background:#FFFFFF;" placeholder="Otomatis No. HP / ketik ID baru">
            </div>
            <div>
              <label style="display:block;font-size:0.76rem;font-weight:700;color:#475467;margin-bottom:4px;">Password Baru <span style="font-weight:500;color:#64748B;">(Min. 6)</span></label>
              <input type="password" id="inp-agt-password" class="form-input" style="width:100%;box-sizing:border-box;background:#FFFFFF;" placeholder="Kosongkan jika tak ubah">
            </div>
          </div>
          <div style="font-size:0.7rem;color:#9A3412;margin-top:6px;">Khusus Ketua Regu. Password bawaan jika baru dibuat: <strong>Ketua123</strong>.</div>
        </div>

        <div style="display:flex;justify-content:flex-end;gap:10px;border-top:1px solid #E2E8F0;padding-top:14px;">
          <button type="button" class="btn-modal-secondary" onclick="closeTambahAnggotaModal()">Batal</button>
          <button type="submit" id="btn-agt-submit" class="btn-modal-primary">Simpan Anggota</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ==================== MODAL 3: TAMBAH REGU ==================== -->
<div class="modal-overlay hidden" id="modal-tambah-regu" onclick="if(event.target === this) closeTambahReguModal()">
  <div class="modal-card" style="max-width: 580px;">
    <div class="modal-head">
      <div>
        <h2 class="modal-title">Buat Regu Baru</h2>
        <p style="font-size:0.8rem;color:#64748B;margin-top:2px;">Tentukan nama regu, ketua, dan pilih anggota yang dimasukkan.</p>
      </div>
      <button class="btn-close-modal" type="button" onclick="closeTambahReguModal()" title="Tutup" aria-label="Tutup">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
      </button>
    </div>
    <div class="modal-body">
      <form id="form-tambah-regu" onsubmit="handleSimpanRegu(event)">
        <div style="margin-bottom:14px;">
          <label style="display:block;font-size:0.8rem;font-weight:700;color:#475467;margin-bottom:4px;">Nama Regu *</label>
          <input type="text" id="inp-regu-nama" class="form-input" style="width:100%;box-sizing:border-box;" placeholder="Contoh: Regu 3 - Reaksi Cepat Banjir" required>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:14px;">
          <div>
            <label style="display:block;font-size:0.8rem;font-weight:700;color:#475467;margin-bottom:4px;">Wilayah / Zona Kerja *</label>
            <input type="text" id="inp-regu-wilayah" class="form-input" style="width:100%;box-sizing:border-box;" placeholder="Contoh: Pekayon & Kalisari" required>
          </div>
          <div>
            <label style="display:block;font-size:0.8rem;font-weight:700;color:#475467;margin-bottom:4px;">Nama Ketua Regu</label>
            <select id="inp-regu-ketua" class="form-input" style="width:100%;box-sizing:border-box;">
              <!-- Populated dynamically -->
            </select>
          </div>
        </div>

        <div style="margin-bottom:16px;">
          <label style="display:block;font-size:0.8rem;font-weight:700;color:#475467;margin-bottom:6px;">Pilih Anggota yang Dimasukkan ke Regu:</label>
          <div id="regu-anggota-checkboxes" style="max-height:160px;overflow-y:auto;border:1px solid #E2E8F0;border-radius:10px;padding:10px;background:#F8FAFC;display:flex;flex-direction:column;gap:8px;">
            <!-- Checkboxes injected dynamically -->
          </div>
        </div>

        <div style="display:flex;justify-content:flex-end;gap:10px;border-top:1px solid #E2E8F0;padding-top:14px;">
          <button type="button" class="btn-modal-secondary" onclick="closeTambahReguModal()">Batal</button>
          <button type="submit" class="btn-modal-primary">Buat Regu</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ==================== MODAL EDIT REGU ==================== -->
<div class="modal-overlay hidden" id="modal-edit-regu" onclick="if(event.target === this) closeEditReguModal()">
  <div class="modal-card" style="max-width: 580px;">
    <div class="modal-head">
      <div>
        <h2 class="modal-title">Edit Regu Lapangan</h2>
        <p style="font-size:0.8rem;color:#64748B;margin-top:2px;">Ubah nama regu, zona wilayah, ketua, dan personel tim.</p>
      </div>
      <button class="btn-close-modal" type="button" onclick="closeEditReguModal()" title="Tutup" aria-label="Tutup">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
      </button>
    </div>
    <div class="modal-body">
      <form id="form-edit-regu" onsubmit="handleUpdateRegu(event)">
        <input type="hidden" id="edit-regu-id">
        <div style="margin-bottom:14px;">
          <label style="display:block;font-size:0.8rem;font-weight:700;color:#475467;margin-bottom:4px;">Nama Regu *</label>
          <input type="text" id="edit-regu-nama" class="form-input" style="width:100%;box-sizing:border-box;" required>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:14px;">
          <div>
            <label style="display:block;font-size:0.8rem;font-weight:700;color:#475467;margin-bottom:4px;">Wilayah / Zona Kerja *</label>
            <input type="text" id="edit-regu-wilayah" class="form-input" style="width:100%;box-sizing:border-box;" required>
          </div>
          <div>
            <label style="display:block;font-size:0.8rem;font-weight:700;color:#475467;margin-bottom:4px;">Nama Ketua Regu</label>
            <select id="edit-regu-ketua" class="form-input" style="width:100%;box-sizing:border-box;" onchange="renderEditReguMembersUI(this.value)">
              <!-- Populated dynamically -->
            </select>
          </div>
        </div>

        <div style="margin-bottom:14px;">
          <label style="display:block;font-size:0.8rem;font-weight:700;color:#475467;margin-bottom:6px;">Personel dalam Regu ini:</label>
          <div id="edit-regu-current-members" style="min-height:48px;max-height:140px;overflow-y:auto;border:1px solid #CBD5E1;border-radius:10px;padding:8px 10px;background:#F8FAFC;display:flex;flex-wrap:wrap;gap:6px;align-items:center;">
            <!-- Rendered in script -->
          </div>
        </div>

        <div style="margin-bottom:16px;">
          <label style="display:block;font-size:0.8rem;font-weight:700;color:#475467;margin-bottom:6px;">Tambah / Pindahkan Personel ke Regu Ini:</label>
          <div style="display:flex;gap:8px;align-items:center;">
            <select id="edit-regu-select-unassigned" class="form-input" style="flex:1;box-sizing:border-box;">
              <!-- Populated dynamically -->
            </select>
            <button type="button" class="btn-modal-primary" onclick="tambahPersonelKeReguAktif()" style="white-space:nowrap;padding:7px 14px;background:#EA580C;color:#fff;border-radius:8px;border:none;font-weight:700;cursor:pointer;">
              + Masukkan
            </button>
          </div>
          <p style="font-size:0.75rem;color:#64748B;margin:4px 0 0;">*Pilih personel lalu klik "+ Masukkan" (atau langsung klik "Simpan Perubahan").</p>
        </div>

        <div style="display:flex;justify-content:space-between;align-items:center;border-top:1px solid #E2E8F0;padding-top:14px;">
          <button type="button" class="btn-modal-secondary" style="color:#DC2626;border-color:#FCA5A5;background:#FEF2F2;" onclick="hapusReguFromModal()">Hapus Regu</button>
          <div style="display:flex;gap:10px;">
            <button type="button" class="btn-modal-secondary" onclick="closeEditReguModal()">Batal</button>
            <button type="submit" class="btn-modal-primary">Simpan Perubahan</button>
          </div>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ==================== MODAL 4: POPUP KECIL PERSONEL DALAM REGU ==================== -->
<div class="modal-overlay hidden" id="modal-popup-personel-regu" onclick="if(event.target === this) closePopupPersonelRegu()">
  <div class="modal-card" style="max-width: 520px; border-radius: 14px; padding: 22px 24px; box-shadow: 0 16px 36px -10px rgba(15,23,42,0.18);">
    
    <!-- Modal Head: Human Professional -->
    <div style="display:flex;justify-content:space-between;align-items:flex-start;padding-bottom:14px;border-bottom:1px solid #E2E8F0;margin-bottom:14px;">
      <div>
        <div style="display:flex;align-items:center;gap:8px;">
          <h2 id="pop-regu-name" style="font-size:1.1rem;font-weight:800;color:#0F172A;margin:0;">Regu Jalan</h2>
          <span id="pop-regu-count-badge" style="background:#F1F5F9;color:#334155;font-size:0.72rem;font-weight:700;padding:2px 8px;border-radius:6px;">4 Personel</span>
        </div>
        <p id="pop-regu-sub" style="font-size:0.78rem;color:#64748B;margin:3px 0 0;">Ketua Tim: <strong id="pop-regu-ketua" style="color:#1E293B;">Ahmad Suryadi</strong> • <span id="pop-regu-wilayah">10 RW Pekayon</span></p>
      </div>
      <button type="button" class="btn-close-modal" onclick="closePopupPersonelRegu()" title="Tutup" aria-label="Tutup">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
      </button>
    </div>

    <!-- Modal Body: Daftar Anggota Tim -->
    <div>
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
        <span style="font-size:0.72rem;font-weight:700;color:#64748B;text-transform:uppercase;letter-spacing:0.5px;">Daftar Petugas Terdaftar</span>
        <span style="font-size:0.72rem;color:#059669;font-weight:600;">● Siap Penugasan Lapangan</span>
      </div>
      <div id="pop-regu-member-list" style="max-height:280px;overflow-y:auto;">
        <!-- Dynamic member table -->
      </div>
    </div>

    <!-- Modal Footer -->
    <div style="display:flex;gap:10px;justify-content:flex-end;border-top:1px solid #E2E8F0;padding-top:14px;align-items:center;margin-top:14px;">
      <button type="button" onclick="closePopupPersonelRegu()" class="btn-modal-secondary" style="padding:8px 16px;font-size:0.8rem;">
        Tutup
      </button>
      <button type="button" onclick="goToMenuReguDirect()" class="admin-btn-primary" style="padding:8px 16px;font-size:0.8rem;font-weight:700;display:inline-flex;align-items:center;gap:6px;cursor:pointer;border-radius:8px;">
        <span>Buka Pengaturan Regu</span>
        <span>&rarr;</span>
      </button>
    </div>

  </div>
</div>

<!-- ==================== MODAL EDIT / TAMBAH SUBJEK PRASARANA ==================== -->
<div class="modal-overlay hidden" id="modal-edit-subjek" onclick="if(event.target === this) closeEditSubjekModal()">
  <div class="modal-card" style="max-width: 480px; border-radius: 14px; padding: 22px 24px; box-shadow: 0 16px 36px -10px rgba(15,23,42,0.18);">
    <div class="modal-head" style="display:flex;justify-content:space-between;align-items:flex-start;padding-bottom:14px;border-bottom:1px solid #E2E8F0;margin-bottom:16px;">
      <div>
        <h2 class="modal-title" id="modal-subjek-title" style="font-size:1.15rem;font-weight:800;color:#0F172A;margin:0;">Edit Bidang Subjek Prasarana</h2>
        <p style="font-size:0.8rem;color:#64748B;margin:3px 0 0;">Ubah nama label tampilan dan warna badge bidang prasarana.</p>
      </div>
      <button class="btn-close-modal" type="button" onclick="closeEditSubjekModal()" title="Tutup" aria-label="Tutup">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
      </button>
    </div>
    <div class="modal-body">
      <form id="form-edit-subjek" onsubmit="handleSimpanSubjek(event)">
        <input type="hidden" id="subjek-key">
        <input type="hidden" id="subjek-is-new" value="0">
        
        <div style="margin-bottom:14px;">
          <label style="display:block;font-size:0.8rem;font-weight:700;color:#475467;margin-bottom:4px;">Nama Bidang / Subjek *</label>
          <input type="text" id="subjek-nama" class="form-input" style="width:100%;box-sizing:border-box;padding:8px 12px;border:1px solid #CBD5E1;border-radius:8px;" placeholder="Contoh: Prasarana Jalan & Jembatan" required>
        </div>

        <div style="margin-bottom:14px;">
          <label style="display:block;font-size:0.8rem;font-weight:700;color:#475467;margin-bottom:4px;">Warna Label Badge</label>
          <select id="subjek-warna" class="form-input" style="width:100%;box-sizing:border-box;padding:8px 12px;border:1px solid #CBD5E1;border-radius:8px;">
            <option value="orange">Oranye</option>
            <option value="green">Hijau</option>
            <option value="blue">Biru</option>
            <option value="yellow">Kuning</option>
            <option value="purple">Ungu</option>
            <option value="slate">Abu-abu</option>
            <option value="teal">Toska / Teal</option>
            <option value="rose">Merah Muda / Rose</option>
          </select>
        </div>

        <div style="display:flex;justify-content:flex-end;gap:10px;border-top:1px solid #E2E8F0;padding-top:14px;">
          <button type="button" class="btn-modal-secondary" onclick="closeEditSubjekModal()" style="padding:8px 16px;border-radius:8px;border:1px solid #CBD5E1;background:#fff;cursor:pointer;font-weight:600;color:#475467;">Batal</button>
          <button type="submit" class="btn-modal-primary" id="btn-save-subjek" style="padding:8px 18px;border-radius:8px;border:none;background:#EA580C;color:#fff;cursor:pointer;font-weight:700;">Simpan Perubahan</button>
        </div>
      </form>
    </div>
  </div>
</div>

