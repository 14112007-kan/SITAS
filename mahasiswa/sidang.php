<?php
require_once __DIR__ . '/../includes/auth.php';
wajib_peran('mahasiswa');

$uid = pengguna_login()['id'];
$pdo = db();

$st = $pdo->prepare("SELECT j.* FROM judul_skripsi j
                     WHERE j.mahasiswa_id = ? AND j.status = 'disetujui'
                     ORDER BY j.id DESC LIMIT 1");
$st->execute([$uid]);
$judul = $st->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (!$judul) {
        flash('galat', 'Judul skripsi Anda belum disetujui.');
        redirect('mahasiswa/sidang.php');
    }

    $st = $pdo->prepare("SELECT COUNT(*) FROM logbook
                         WHERE judul_id = ? AND status = 'tervalidasi'");
    $st->execute([$judul['id']]);
    $tervalidasi = (int) $st->fetchColumn();

    if ($tervalidasi < MIN_BIMBINGAN) {
        flash('galat', 'Pendaftaran diblokir. Diperlukan minimal ' . MIN_BIMBINGAN . ' bimbingan tervalidasi.');
        redirect('mahasiswa/sidang.php');
    }

    $st = $pdo->prepare("SELECT id FROM sidang
                         WHERE judul_id = ? AND jenis = 'proposal'
                         ORDER BY id DESC LIMIT 1");
    $st->execute([$judul['id']]);
    $sidangAda = $st->fetch();

    if ($sidangAda) {
        flash('info', 'Pendaftaran sidang proposal Anda sudah ada.');
        redirect('mahasiswa/sidang.php');
    }

    $st = $pdo->prepare("INSERT INTO sidang
                         (judul_id, jenis, persetujuan_dosen, status,
                          chk_transkrip, chk_surat_penelitian, chk_naskah,
                          chk_formulir, chk_pinjaman)
                         VALUES (?, 'proposal', 0, 'didaftarkan', 0, 0, 0, 0, 0)");
    $st->execute([$judul['id']]);
    $sidangId = (int) $pdo->lastInsertId();

    kirim_notifikasi((int) $judul['pembimbing_id'], 'Mahasiswa mendaftar sidang proposal dan menunggu persetujuan Anda.', 'dosen/maju-sidang.php');
    audit_log($uid, 'DAFTAR_SIDANG', 'Mendaftarkan sidang proposal ID ' . $sidangId);
    flash('sukses', 'Pendaftaran sidang proposal berhasil dibuat.');
    redirect('mahasiswa/sidang.php');
}

$sidang = null;
$bimbingan = 0;
if ($judul) {
    $st = $pdo->prepare("SELECT COUNT(*) FROM logbook
                         WHERE judul_id = ? AND status = 'tervalidasi'");
    $st->execute([$judul['id']]);
    $bimbingan = (int) $st->fetchColumn();

    $st = $pdo->prepare("SELECT * FROM sidang
                         WHERE judul_id = ? AND jenis = 'proposal'
                         ORDER BY id DESC LIMIT 1");
    $st->execute([$judul['id']]);
    $sidang = $st->fetch();
}

$judulHalaman = 'Sidang Proposal';
require __DIR__ . '/../includes/header.php';
?>

<section class="grid-stat">
  <?= kartu_statistik('Bimbingan tervalidasi', $bimbingan . ' / ' . MIN_BIMBINGAN, 'Syarat pendaftaran sidang') ?>
  <?= kartu_statistik('Status sidang', $sidang ? str_replace('_', ' ', $sidang['status']) : 'Belum terdaftar', $sidang ? 'Proposal' : 'Belum mendaftar') ?>
  <?= kartu_statistik('Persetujuan pembimbing', $sidang && $sidang['persetujuan_dosen'] ? 'Disetujui' : 'Menunggu', 'Diperlukan sebelum berkas admin') ?>
</section>

<section class="kartu">
  <h2 class="judul-kartu">Pendaftaran sidang proposal</h2>
  <?php if (!$judul): ?>
    <p class="redup">Judul skripsi Anda belum disetujui.</p>
  <?php elseif ($sidang): ?>
    <dl class="rincian">
      <dt>Status berkas</dt><dd><?= lencana(str_replace('_', ' ', $sidang['status']), $sidang['status'] === 'berkas_lengkap' ? 'hijau' : 'kuning') ?></dd>
      <dt>Pembimbing</dt><dd><?= lencana($sidang['persetujuan_dosen'] ? 'Sudah menyetujui' : 'Belum menyetujui', $sidang['persetujuan_dosen'] ? 'hijau' : 'kuning') ?></dd>
      <dt>Jadwal</dt><dd><?= e($sidang['jadwal'] ? tanggal_id($sidang['jadwal'], true) : 'Belum dijadwalkan') ?></dd>
    </dl>
  <?php elseif ($bimbingan < MIN_BIMBINGAN): ?>
    <p class="redup">Belum memenuhi minimal <?= MIN_BIMBINGAN ?> bimbingan tervalidasi.</p>
  <?php else: ?>
    <p>Silakan daftarkan sidang proposal setelah persyaratan bimbingan terpenuhi.</p>
    <form method="post">
      <?= csrf_field() ?>
      <button type="submit" class="btn btn-utama">Daftar sidang proposal</button>
    </form>
  <?php endif; ?>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
