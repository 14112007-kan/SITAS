<?php
// Kerangka halaman setelah login. Set $judulHalaman sebelum require file ini.
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/menu.php';

wajib_login();
$pengguna = pengguna_login();
$peran    = peran_aktif();
$menu     = menu_untuk((string) $peran);
$judulHalaman = $judulHalaman ?? 'Dashboard';
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($judulHalaman) ?> | <?= e(APP_NAME) ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= e(url('assets/css/style.css')) ?>">
</head>
<body>
<div class="layout">

  <aside class="sidebar" id="sidebar">
    <div class="brand">
      <span class="brand-logo">S</span>
      <div><strong><?= e(APP_NAME) ?></strong><small>Tugas Akhir & Skripsi</small></div>
    </div>

    <div class="peran-aktif">
      <small>Masuk sebagai</small>
      <strong><?= e(NAMA_PERAN[$peran] ?? '-') ?></strong>
      <?php if (count(daftar_peran()) > 1): ?>
        <a href="<?= e(url('pilih-peran.php')) ?>">Ganti peran</a>
      <?php endif; ?>
    </div>

    <nav class="menu">
      <?php foreach ($menu as [$label, $path, $tahap]):
          $siap  = $tahap <= TAHAP_SELESAI;
          $aktif = str_ends_with($_SERVER['SCRIPT_NAME'], '/' . $path);
      ?>
        <?php if ($siap): ?>
          <a href="<?= e(url($path)) ?>" class="<?= $aktif ? 'aktif' : '' ?>"><?= e($label) ?></a>
        <?php else: ?>
          <span class="nonaktif" title="Dibuat pada Tahap <?= (int) $tahap ?>"><?= e($label) ?><em>Tahap <?= (int) $tahap ?></em></span>
        <?php endif; ?>
      <?php endforeach; ?>
    </nav>

    <div class="sidebar-bawah">
      <div class="akun">
        <strong><?= e($pengguna['nama']) ?></strong>
        <small><?= e($pengguna['email']) ?></small>
      </div>
      <button type="button" class="btn btn-garis btn-penuh" id="btnKeluar">Keluar</button>
    </div>
  </aside>

  <div class="konten">
    <header class="topbar">
      <button type="button" class="btn-menu" id="btnMenu" aria-label="Buka menu">&#9776;</button>
      <h1><?= e($judulHalaman) ?></h1>
    </header>

    <main class="isi">
      <?php foreach (ambil_flash() as $f): ?>
        <div class="alert alert-<?= e($f['tipe']) ?>"><?= e($f['pesan']) ?></div>
      <?php endforeach; ?>
