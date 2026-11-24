<?php
require_once __DIR__ . '/../includes/auth.php';
wajib_peran('admin');

$pdo = db();
$hitung = fn(string $sql): int => (int) $pdo->query($sql)->fetchColumn();

$judulMenunggu  = $hitung("SELECT COUNT(*) FROM judul_skripsi WHERE status = 'menunggu'");
$berkasMenunggu = $hitung("SELECT COUNT(*) FROM sidang WHERE status = 'didaftarkan'");
$butuhPenguji   = $hitung("SELECT COUNT(*) FROM sidang WHERE status = 'berkas_lengkap'");
$suratMenunggu  = $hitung("SELECT COUNT(*) FROM surat WHERE status = 'menunggu'");
$naskahMasuk    = $hitung("SELECT COUNT(*) FROM naskah_final WHERE status = 'terkirim_admin'");
$totalMhs       = $hitung("SELECT COUNT(*) FROM user_roles WHERE role = 'mahasiswa'");

$aktivitas = $pdo->query("SELECT a.waktu, a.aksi, a.keterangan, u.nama
                          FROM audit_log a LEFT JOIN users u ON u.id = a.user_id
                          ORDER BY a.id DESC LIMIT 6")->fetchAll();

$judulHalaman = 'Dashboard Admin';
require __DIR__ . '/../includes/header.php';
?>

<section class="grid-stat">
  <?= kartu_statistik('Judul menunggu verifikasi', $judulMenunggu, 'Antrean Verifikasi Judul') ?>
  <?= kartu_statistik('Berkas sidang menunggu', $berkasMenunggu, 'Perlu diperiksa') ?>
  <?= kartu_statistik('Sidang menunggu penguji', $butuhPenguji, 'Berkas sudah lengkap') ?>
  <?= kartu_statistik('Surat menunggu terbit', $suratMenunggu, 'Antrean Surat') ?>
  <?= kartu_statistik('Naskah final masuk', $naskahMasuk, 'Menunggu pengarsipan') ?>
  <?= kartu_statistik('Total mahasiswa', $totalMhs, 'Terdaftar di sistem') ?>
</section>

<section class="kartu">
  <h2 class="judul-kartu">Aktivitas terbaru</h2>
  <?php if ($aktivitas): ?>
    <div class="tabel-bungkus">
      <table class="tabel">
        <thead><tr><th>Waktu</th><th>Pelaku</th><th>Aksi</th><th>Keterangan</th></tr></thead>
        <tbody>
        <?php foreach ($aktivitas as $a): ?>
          <tr>
            <td><?= e(tanggal_id($a['waktu'], true)) ?></td>
            <td><?= e($a['nama'] ?? 'Sistem') ?></td>
            <td><?= lencana($a['aksi'], 'biru') ?></td>
            <td><?= e($a['keterangan']) ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php else: ?>
    <p class="redup">Belum ada aktivitas.</p>
  <?php endif; ?>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
