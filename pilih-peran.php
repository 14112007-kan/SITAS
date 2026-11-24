<?php
// Halaman "Masuk sebagai" (FR-02): untuk akun dengan lebih dari satu peran.
require_once __DIR__ . '/includes/auth.php';
wajib_login();

$punyaPeran = daftar_peran();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $pilihan = $_POST['peran'] ?? '';

    if (in_array($pilihan, $punyaPeran, true)) {
        $_SESSION['peran_aktif'] = $pilihan;
        audit_log(pengguna_login()['id'], 'GANTI_PERAN', 'Masuk sebagai ' . NAMA_PERAN[$pilihan]);
        redirect(url_beranda_peran($pilihan));
    }
    flash('galat', 'Peran tidak valid.');
    redirect('pilih-peran.php');
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Pilih Peran | <?= e(APP_NAME) ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= e(url('assets/css/style.css')) ?>">
</head>
<body class="halaman-tamu">
  <div class="kotak-tamu">
    <div class="brand brand-besar">
      <span class="brand-logo">S</span>
      <div><strong><?= e(APP_NAME) ?></strong><small>Halo, <?= e(pengguna_login()['nama']) ?></small></div>
    </div>

    <h2>Masuk sebagai</h2>
    <p class="redup">Akun Anda memiliki lebih dari satu peran. Pilih peran yang ingin dipakai sekarang.</p>

    <?php foreach (ambil_flash() as $f): ?>
      <div class="alert alert-<?= e($f['tipe']) ?>"><?= e($f['pesan']) ?></div>
    <?php endforeach; ?>

    <form method="post" class="daftar-peran">
      <?= csrf_field() ?>
      <?php foreach ($punyaPeran as $p): ?>
        <button type="submit" name="peran" value="<?= e($p) ?>" class="btn btn-garis btn-penuh btn-peran">
          <?= e(NAMA_PERAN[$p]) ?>
        </button>
      <?php endforeach; ?>
    </form>
  </div>
</body>
</html>
