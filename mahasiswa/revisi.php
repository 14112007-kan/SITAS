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

$sidang = null;
$naskah = null;
if ($judul) {
    $st = $pdo->prepare("SELECT s.*, n.nilai_akhir, n.catatan_revisi
                         FROM sidang s LEFT JOIN sidang_nilai n ON n.sidang_id = s.id
                         WHERE s.judul_id = ? AND s.status = 'dinilai'
                         ORDER BY s.id DESC LIMIT 1");
    $st->execute([$judul['id']]);
    $sidang = $st->fetch();

    $st = $pdo->prepare('SELECT * FROM naskah_final WHERE judul_id = ? ORDER BY id DESC LIMIT 1');
    $st->execute([$judul['id']]);
    $naskah = $st->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (!$judul || !$sidang) {
        flash('galat', 'Sidang belum dinilai sehingga revisi belum tersedia.');
        redirect('mahasiswa/revisi.php');
    }

    $jenis = (string) ($_POST['jenis'] ?? 'revisi');
    $file = $_FILES['dokumen'] ?? null;
    if (!$file || $file['error'] !== UPLOAD_ERR_OK || empty($file['name'])) {
        flash('galat', 'Dokumen wajib diunggah dalam format PDF.');
        redirect('mahasiswa/revisi.php');
    }

    $tipe = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($tipe, $file['tmp_name']);
    finfo_close($tipe);
    if ($mime !== 'application/pdf' || $file['size'] > MAKS_UKURAN_UPLOAD) {
        flash('galat', 'Dokumen harus PDF dengan ukuran maksimal 5 MB.');
        redirect('mahasiswa/revisi.php');
    }

    $nama = ($jenis === 'naskah' ? 'naskah-final-' : 'revisi-') . $uid . '-' . bin2hex(random_bytes(6)) . '.pdf';
    $folder = $jenis === 'naskah' ? 'naskah' : 'proposal';
    $tujuan = __DIR__ . '/../storage/' . $folder . '/' . $nama;
    if (!is_dir(dirname($tujuan))) {
        mkdir(dirname($tujuan), 0775, true);
    }
    if (!move_uploaded_file($file['tmp_name'], $tujuan)) {
        flash('galat', 'Dokumen gagal disimpan.');
        redirect('mahasiswa/revisi.php');
    }

    if ($jenis === 'naskah') {
        $st = $pdo->prepare("INSERT INTO naskah_final (judul_id, file_path, status)
                             VALUES (?, ?, 'terkirim_admin')");
        $st->execute([$judul['id'], 'storage/naskah/' . $nama]);
        $adminIds = $pdo->query("SELECT u.id FROM users u JOIN user_roles r ON r.user_id = u.id
                                 WHERE r.role = 'admin' AND u.aktif = 1")->fetchAll();
        foreach ($adminIds as $admin) {
            kirim_notifikasi((int) $admin['id'], 'Ada naskah final baru yang perlu diarsipkan.', 'admin/naskah.php');
        }
        audit_log($uid, 'KIRIM_NASKAH_FINAL', 'Mengirim naskah final untuk judul ID ' . $judul['id']);
        flash('sukses', 'Naskah final berhasil dikirim kepada Admin.');
    } else {
        $st = $pdo->prepare("UPDATE sidang SET revisi_status = 'perlu_diperiksa', revisi_path = ? WHERE id = ?");
        $st->execute(['storage/proposal/' . $nama, $sidang['id']]);
        kirim_notifikasi((int) $sidang['penguji_id'], 'Mahasiswa mengunggah revisi pasca-sidang.', 'dosen/revisi.php');
        audit_log($uid, 'UNGGAH_REVISI', 'Mengunggah revisi sidang ID ' . $sidang['id']);
        flash('sukses', 'Revisi berhasil dikirim kepada penguji.');
    }
    redirect('mahasiswa/revisi.php');
}

$judulHalaman = 'Revisi & Naskah Final';
require __DIR__ . '/../includes/header.php';
?>

<section class="dua-kolom">
  <section class="kartu">
    <h2 class="judul-kartu">Hasil sidang dan revisi</h2>
    <?php if ($sidang): ?>
      <dl class="rincian">
        <dt>Nilai akhir</dt><dd><strong><?= $sidang['nilai_akhir'] !== null ? number_format((float) $sidang['nilai_akhir'], 2) : '-' ?></strong></dd>
        <dt>Status revisi</dt><dd><?= lencana(str_replace('_', ' ', $sidang['revisi_status']), $sidang['revisi_status'] === 'disetujui' ? 'hijau' : 'kuning') ?></dd>
      </dl>
      <?php if ($sidang['catatan_revisi']): ?>
        <div class="alert alert-info">Catatan penguji: <?= e($sidang['catatan_revisi']) ?></div>
      <?php endif; ?>

      <?php if ($sidang['revisi_status'] !== 'disetujui'): ?>
        <form method="post" enctype="multipart/form-data">
          <?= csrf_field() ?>
          <input type="hidden" name="jenis" value="revisi">
          <label for="dokumen-revisi">Unggah revisi (PDF, maks. 5 MB)</label>
          <input type="file" id="dokumen-revisi" name="dokumen" accept="application/pdf" required>
          <button type="submit" class="btn btn-utama btn-penuh">Kirim revisi</button>
        </form>
      <?php endif; ?>
    <?php else: ?>
      <p class="redup">Belum ada sidang yang dinilai.</p>
    <?php endif; ?>
  </section>

  <section class="kartu">
    <h2 class="judul-kartu">Naskah final</h2>
    <?php if ($naskah): ?>
      <dl class="rincian">
        <dt>Status</dt><dd><?= lencana(str_replace('_', ' ', $naskah['status']), $naskah['status'] === 'diarsipkan' ? 'hijau' : 'kuning') ?></dd>
        <dt>Akses</dt><dd><?= e($naskah['akses'] ?: 'Belum ditetapkan') ?></dd>
        <dt>Dikirim</dt><dd><?= e(tanggal_id($naskah['created_at'], true)) ?></dd>
      </dl>
      <a class="btn btn-garis" href="<?= e(url($naskah['file_path'])) ?>" target="_blank" rel="noopener">Lihat naskah</a>
    <?php elseif ($sidang && $sidang['revisi_status'] === 'disetujui'): ?>
      <form method="post" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <input type="hidden" name="jenis" value="naskah">
        <label for="dokumen-naskah">Unggah naskah final (PDF, maks. 5 MB)</label>
        <input type="file" id="dokumen-naskah" name="dokumen" accept="application/pdf" required>
        <button type="submit" class="btn btn-utama btn-penuh">Kirim naskah final</button>
      </form>
    <?php else: ?>
      <p class="redup">Naskah final dapat dikirim setelah revisi disetujui penguji.</p>
    <?php endif; ?>
  </section>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
