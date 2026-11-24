<?php
require_once __DIR__ . '/../includes/auth.php';
wajib_peran('dosen');

$uid = pengguna_login()['id'];
$pdo = db();

// Daftar mahasiswa bimbingan beserta jumlah bimbingan tervalidasi dan bab disetujui
$st = $pdo->prepare("SELECT j.id, j.judul, u.nama,
        (SELECT COUNT(*) FROM logbook l WHERE l.judul_id = j.id AND l.status = 'tervalidasi') AS tervalidasi,
        (SELECT COUNT(DISTINCT b.bab) FROM bab_dokumen b WHERE b.judul_id = j.id AND b.status = 'disetujui') AS bab_ok
     FROM judul_skripsi j JOIN users u ON u.id = j.mahasiswa_id
     WHERE j.pembimbing_id = ? AND j.status = 'disetujui'
     ORDER BY u.nama");
$st->execute([$uid]);
$bimbingan = $st->fetchAll();

$st = $pdo->prepare("SELECT COUNT(*) FROM logbook l JOIN judul_skripsi j ON j.id = l.judul_id
                     WHERE j.pembimbing_id = ? AND l.status = 'menunggu_dosen'");
$st->execute([$uid]);
$logbookMenunggu = (int) $st->fetchColumn();

$jumlah = count($bimbingan);
$notifikasi = notifikasi_terbaru($uid, 5);

$judulHalaman = 'Dashboard Dosen Pembimbing';
require __DIR__ . '/../includes/header.php';
?>

<section class="grid-stat">
  <?= kartu_statistik('Kuota pembimbing', $jumlah . ' / ' . KUOTA_PEMBIMBING, 'Mahasiswa bimbingan aktif') ?>
  <?= kartu_statistik('Logbook menunggu validasi', $logbookMenunggu, 'Perlu ditinjau') ?>
  <?= kartu_statistik('Siap maju sidang', count(array_filter($bimbingan, fn($b) => (int) $b['tervalidasi'] >= MIN_BIMBINGAN)), 'Bimbingan sudah ' . MIN_BIMBINGAN . ' atau lebih') ?>
</section>

<section class="kartu">
  <h2 class="judul-kartu">Mahasiswa bimbingan</h2>
  <?php if ($bimbingan): ?>
    <div class="tabel-bungkus">
      <table class="tabel">
        <thead><tr><th>Mahasiswa</th><th>Judul</th><th>Bimbingan</th><th>Bab disetujui</th><th>Maju sidang</th></tr></thead>
        <tbody>
        <?php foreach ($bimbingan as $b): $siap = (int) $b['tervalidasi'] >= MIN_BIMBINGAN; ?>
          <tr>
            <td><?= e($b['nama']) ?></td>
            <td><?= e($b['judul']) ?></td>
            <td><?= (int) $b['tervalidasi'] ?> / <?= MIN_BIMBINGAN ?></td>
            <td><?= (int) $b['bab_ok'] ?> / 5</td>
            <td><?= $siap ? lencana('Memenuhi syarat', 'hijau') : lencana('Belum', 'abu') ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php else: ?>
    <p class="redup">Belum ada mahasiswa bimbingan.</p>
  <?php endif; ?>
</section>

<section class="kartu">
  <h2 class="judul-kartu">Notifikasi terbaru</h2>
  <?php if ($notifikasi): ?>
    <ul class="daftar-notif">
      <?php foreach ($notifikasi as $n): ?>
        <li><?= e($n['pesan']) ?><small><?= e(tanggal_id($n['created_at'], true)) ?></small></li>
      <?php endforeach; ?>
    </ul>
  <?php else: ?>
    <p class="redup">Belum ada notifikasi.</p>
  <?php endif; ?>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
