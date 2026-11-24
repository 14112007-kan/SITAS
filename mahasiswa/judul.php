<?php
require_once __DIR__ . '/../includes/auth.php';
wajib_peran('mahasiswa');

$uid = pengguna_login()['id'];
$pdo = db();

$st = $pdo->prepare('SELECT j.*, d.nama AS nama_pembimbing
                     FROM judul_skripsi j LEFT JOIN users d ON d.id = j.pembimbing_id
                     WHERE j.mahasiswa_id = ? ORDER BY j.id DESC LIMIT 1');
$st->execute([$uid]);
$judul = $st->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    if ($judul && in_array($judul['status'], ['menunggu', 'disetujui'], true)) {
        flash('galat', 'Pengajuan baru tidak dapat dibuat selama judul masih menunggu atau telah disetujui.');
        redirect('mahasiswa/judul.php');
    }

    $judulBaru = trim((string) ($_POST['judul'] ?? ''));
    $abstrak = trim((string) ($_POST['abstrak'] ?? ''));
    $bidang = trim((string) ($_POST['bidang_minat'] ?? ''));

    if (mb_strlen($judulBaru) < 10 || mb_strlen($abstrak) < 20 || $bidang === '') {
        flash('galat', 'Lengkapi judul, abstrak, dan bidang minat dengan benar.');
        redirect('mahasiswa/judul.php');
    }

    if (empty($_FILES['proposal']['name'])) {
        flash('galat', 'Proposal awal wajib dilampirkan dalam format PDF.');
        redirect('mahasiswa/judul.php');
    }

    $file = $_FILES['proposal'];
    $tipe = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($tipe, $file['tmp_name']);
    finfo_close($tipe);

    if ($file['error'] !== UPLOAD_ERR_OK) {
        flash('galat', 'Proposal gagal diunggah.');
        redirect('mahasiswa/judul.php');
    }
    if ($mime !== 'application/pdf') {
        flash('galat', 'Berkas proposal harus berformat PDF.');
        redirect('mahasiswa/judul.php');
    }
    if ($file['size'] > MAKS_UKURAN_UPLOAD) {
        flash('galat', 'Ukuran proposal maksimal 5 MB.');
        redirect('mahasiswa/judul.php');
    }

    $namaFile = 'proposal-' . $uid . '-' . bin2hex(random_bytes(8)) . '.pdf';
    $tujuan = __DIR__ . '/../storage/proposal/' . $namaFile;
    if (!is_dir(dirname($tujuan))) {
        mkdir(dirname($tujuan), 0775, true);
    }
    if (!move_uploaded_file($file['tmp_name'], $tujuan)) {
        flash('galat', 'Proposal tidak berhasil disimpan.');
        redirect('mahasiswa/judul.php');
    }

    $st = $pdo->prepare('INSERT INTO judul_skripsi
                         (mahasiswa_id, judul, abstrak, bidang_minat, proposal_path, status)
                         VALUES (?, ?, ?, ?, ?, "menunggu")');
    $st->execute([$uid, $judulBaru, $abstrak, $bidang, 'storage/proposal/' . $namaFile]);

    $judulId = (int) $pdo->lastInsertId();
    $adminIds = $pdo->query("SELECT u.id FROM users u
                             JOIN user_roles r ON r.user_id = u.id
                             WHERE r.role = 'admin' AND u.aktif = 1")->fetchAll();
    foreach ($adminIds as $admin) {
        kirim_notifikasi((int) $admin['id'], 'Ada pengajuan judul baru yang menunggu verifikasi.', 'admin/judul.php');
    }
    audit_log($uid, 'AJUKAN_JUDUL', 'Mengajukan judul ID ' . $judulId);
    flash('sukses', 'Pengajuan judul berhasil dikirim dan menunggu verifikasi Admin.');
    redirect('mahasiswa/judul.php');
}

$judulHalaman = 'Pengajuan Judul';
require __DIR__ . '/../includes/header.php';
?>

<div class="dua-kolom">
  <section class="kartu">
    <h2 class="judul-kartu">Status pengajuan terbaru</h2>
    <?php if ($judul): ?>
      <p class="judul-skripsi"><?= e($judul['judul']) ?></p>
      <dl class="rincian">
        <dt>Status</dt>
        <dd><?= lencana(str_replace('_', ' ', ucfirst($judul['status'])), $judul['status'] === 'disetujui' ? 'hijau' : ($judul['status'] === 'ditolak' ? 'merah' : 'kuning')) ?></dd>
        <dt>Bidang minat</dt><dd><?= e($judul['bidang_minat'] ?: '-') ?></dd>
        <dt>Pembimbing</dt><dd><?= e($judul['nama_pembimbing'] ?? 'Belum ditetapkan') ?></dd>
        <dt>Diajukan</dt><dd><?= e(tanggal_id($judul['diajukan_at'], true)) ?></dd>
        <?php if ($judul['proposal_path']): ?>
          <dt>Proposal</dt><dd><a href="<?= e(url($judul['proposal_path'])) ?>" target="_blank" rel="noopener">Lihat PDF</a></dd>
        <?php endif; ?>
      </dl>
      <?php if ($judul['status'] === 'ditolak' && $judul['alasan_penolakan']): ?>
        <div class="alert alert-galat">Alasan penolakan: <?= e($judul['alasan_penolakan']) ?></div>
      <?php endif; ?>
    <?php else: ?>
      <p class="redup">Belum ada pengajuan judul.</p>
    <?php endif; ?>
  </section>

  <section class="kartu">
    <h2 class="judul-kartu">Ajukan judul baru</h2>
    <?php if ($judul && in_array($judul['status'], ['menunggu', 'disetujui'], true)): ?>
      <p class="redup">Form pengajuan tersedia setelah judul ditolak atau diputuskan kembali oleh Admin.</p>
    <?php else: ?>
      <form method="post" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <label for="judul">Judul skripsi</label>
        <input type="text" id="judul" name="judul" required minlength="10" maxlength="255">

        <label for="bidang_minat">Bidang minat</label>
        <input type="text" id="bidang_minat" name="bidang_minat" required maxlength="100" placeholder="Contoh: Machine Learning">

        <label for="abstrak">Abstrak singkat</label>
        <textarea id="abstrak" name="abstrak" required minlength="20"></textarea>

        <label for="proposal">Proposal awal (PDF, maksimal 5 MB)</label>
        <input type="file" id="proposal" name="proposal" accept="application/pdf" required>

        <button type="submit" class="btn btn-utama btn-penuh">Kirim pengajuan</button>
      </form>
    <?php endif; ?>
  </section>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
