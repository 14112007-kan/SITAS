    </main>
  </div>
</div>

<!-- Dialog konfirmasi keluar (FR-03) -->
<dialog id="dialogKeluar">
  <form method="post" action="<?= e(url('logout.php')) ?>">
    <?= csrf_field() ?>
    <h3>Keluar dari akun?</h3>
    <p>Sesi Anda akan diakhiri dan Anda kembali ke halaman login.</p>
    <div class="dialog-aksi">
      <button type="button" class="btn btn-garis" id="btnBatalKeluar">Batal</button>
      <button type="submit" class="btn btn-bahaya">Keluar</button>
    </div>
  </form>
</dialog>

<script src="<?= e(url('assets/js/app.js')) ?>"></script>
</body>
</html>
