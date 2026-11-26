      <!-- ==================== 3. VERIFIKASI VIEW ==================== -->
      <section class="admin-tab-pane <?= (isset($_GET['tab']) && $_GET['tab'] === 'verifikasi') ? 'active' : '' ?>" id="pane-verifikasi">
        <div class="admin-welcome-bar">
          <div>
            <h1 class="admin-welcome-heading">Antrean Verifikasi Laporan</h1>
            <p style="color:#64748B;font-size:0.9rem;margin-top:4px;">Tinjau foto lapangan sebelum laporan disahkan tuntas dan diverifikasi lurah.</p>
          </div>
        </div>

        <div id="verifikasi-list-container" style="display:flex;flex-direction:column;gap:14px;">
          <!-- Rendered in script -->
        </div>
      </section>

