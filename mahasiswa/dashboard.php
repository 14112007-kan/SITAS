<?php
require_once __DIR__ . '/../includes/auth.php';
wajib_peran('mahasiswa');

$uid = pengguna_login()['id'];
$pdo = db();

// Judul terbaru milik mahasiswa ini
$st = $pdo->prepare('SELECT j.*, d.nama AS nama_pembimbing
                     FROM judul_skripsi j LEFT JOIN users d ON d.id = j.pembimbing_id
                     WHERE j.mahasiswa_id = ? ORDER BY j.id DESC LIMIT 1');
$st->execute([$uid]);
$judul = $st->fetch();

$tervalidasi = 0;
$belumSelesai = 0;
$sidang = null;

if ($judul) {
    $st = $pdo->prepare("SELECT COALESCE(SUM(status = 'tervalidasi'), 0) AS ok,
                                COALESCE(SUM(status <> 'tervalidasi'), 0) AS belum
                         FROM logbook WHERE judul_id = ?");
    $st->execute([$judul['id']]);
    $r = $st->fetch();
    $tervalidasi  = (int) $r['ok'];
    $belumSelesai = (int) $r['belum'];

    $st = $pdo->prepare('SELECT * FROM sidang WHERE judul_id = ? ORDER BY id DESC LIMIT 1');
    $st->execute([$judul['id']]);
    $sidang = $st->fetch();
}

// Tentukan tahap aktif dari lima tahap utama (PRD bagian 6)
$tahapNama = ['Pengajuan Judul', 'Persetujuan (ACC) Dosen', 'Sidang Proposal', 'Penelitian', 'Sidang Akhir'];
$tahap = 1;
$langkah = 'Ajukan judul skripsi beserta abstrak dan proposal awal (PDF).';

if ($judul && $judul['status'] === 'menunggu') {
    $tahap = 1;
    $langkah = 'Pengajuan judul sedang diverifikasi Admin. Mohon tunggu.';
} elseif ($judul && $judul['status'] === 'ditolak') {
    $tahap = 1;
    $langkah = 'Judul ditolak. Baca alasan penolakan lalu ajukan judul baru.';
} elseif ($judul && $judul['status'] === 'disetujui') {
    $tahap = 2;
    $langkah = $tervalidasi >= MIN_BIMBINGAN
        ? 'Bimbingan Anda sudah cukup. Menunggu persetujuan pembimbing untuk maju sidang.'
        : 'Catat bimbingan di logbook sampai ' . MIN_BIMBINGAN . ' bimbingan tervalidasi.';
    if ($sidang) {
        $tahap = ($sidang['jenis'] === 'akhir') ? 5 : (($sidang['status'] === 'dinilai') ? 4 : 3);
        $langkah = 'Pantau status pendaftaran dan jadwal sidang Anda.';
    }
}

$notifikasi = notifikasi_terbaru($uid, 5);
$judulHalaman = 'Dashboard Mahasiswa';
require __DIR__ . '/../includes/header.php';
?>

<section class="kartu">
  <h2 class="judul-kartu">Tahap skripsi Anda</h2>
  <ol class="stepper">
    <?php foreach ($tahapNama as $i => $nama):
        $no = $i + 1;
        $kelas = $no < $tahap ? 'selesai' : ($no === $tahap ? 'aktif' : '');
    ?>
      <li class="<?= $kelas ?>"><span class="bulat"><?= $no < $tahap ? '&#10003;' : $no ?></span><span><?= e($nama) ?></span></li>
    <?php endforeach; ?>
  </ol>
  <p class="langkah"><strong>Langkah berikutnya:</strong> <?= e($langkah) ?></p>
</section>

<section class="grid-stat">
  <?= kartu_statistik('Bimbingan tervalidasi', $tervalidasi . ' / ' . MIN_BIMBINGAN, 'Syarat minimal maju sidang') ?>
  <?= kartu_statistik('Catatan belum selesai', $belumSelesai, 'Menunggu dosen atau perlu revisi') ?>
  <?= kartu_statistik('Status pendaftaran sidang', $sidang ? 'Terdaftar' : 'Belum', $sidang ? 'Status: ' . str_replace('_', ' ', $sidang['status']) : 'Belum mendaftar') ?>
</section>

<div class="dua-kolom">
  <section class="kartu">
    <h2 class="judul-kartu">Judul skripsi</h2>
    <?php if ($judul): ?>
      <p class="judul-skripsi"><?= e($judul['judul']) ?></p>
      <dl class="rincian">
        <dt>Status</dt>
        <dd><?= lencana(ucfirst($judul['status']), $judul['status'] === 'disetujui' ? 'hijau' : ($judul['status'] === 'ditolak' ? 'merah' : 'kuning')) ?></dd>
        <dt>Pembimbing</dt><dd><?= e($judul['nama_pembimbing'] ?? 'Belum ditetapkan') ?></dd>
        <dt>Kemiripan</dt><dd><?= e(number_format((float) $judul['kemiripan'], 1)) ?>%</dd>
        <dt>Diajukan</dt><dd><?= e(tanggal_id($judul['diajukan_at'])) ?></dd>
      </dl>
      <?php if ($judul['status'] === 'ditolak' && $judul['alasan_penolakan']): ?>
        <div class="alert alert-galat">Alasan penolakan: <?= e($judul['alasan_penolakan']) ?></div>
      <?php endif; ?>
    <?php else: ?>
      <p class="redup">Anda belum mengajukan judul.</p>
    <?php endif; ?>

    <h3 class="sub-judul">Tindakan cepat</h3>
    <div class="aksi-cepat">
      <?php if (!$judul || $judul['status'] === 'ditolak'): ?>
        <a class="btn btn-garis" href="<?= e(url('mahasiswa/judul.php')) ?>">Ajukan judul</a>
      <?php else: ?>
        <button class="btn btn-garis" disabled>Ajukan judul</button>
      <?php endif; ?>

      <?php if ($judul && $judul['status'] === 'disetujui'): ?>
        <a class="btn btn-garis" href="<?= e(url('mahasiswa/logbook.php')) ?>">Isi logbook</a>
      <?php else: ?>
        <button class="btn btn-garis" disabled title="Judul harus disetujui terlebih dahulu">Isi logbook</button>
      <?php endif; ?>

      <?php if ($judul && $judul['status'] === 'disetujui' && $tervalidasi >= MIN_BIMBINGAN): ?>
        <a class="btn btn-garis" href="<?= e(url('mahasiswa/sidang.php')) ?>">Daftar sidang</a>
      <?php else: ?>
        <button class="btn btn-garis" disabled title="Diperlukan <?= MIN_BIMBINGAN ?> bimbingan tervalidasi">Daftar sidang</button>
      <?php endif; ?>
    </div>
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
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
