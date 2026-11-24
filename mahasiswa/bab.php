<?php
require_once __DIR__ . '/../includes/auth.php';
wajib_peran('mahasiswa');

$uid = pengguna_login()['id'];
$pdo = db();

$st = $pdo->prepare("SELECT j.*, d.nama AS nama_pembimbing
                     FROM judul_skripsi j LEFT JOIN users d ON d.id = j.pembimbing_id
                     WHERE j.mahasiswa_id = ? AND j.status = 'disetujui'
                     ORDER BY j.id DESC LIMIT 1");
$st->execute([$uid]);
$judul = $st->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (!$judul) {
        flash('galat', 'Dokumen Bab hanya tersedia setelah judul disetujui.');
        redirect('mahasiswa/bab.php');
    }

    $bab = (int) ($_POST['bab'] ?? 0);
    if ($bab < 1 || $bab > 5) {
        flash('galat', 'Bab harus dipilih dari Bab 1 sampai Bab 5.');
        redirect('mahasiswa/bab.php');
    }

    $file = $_FILES['dokumen'] ?? null;
    if (!$file || $file['error'] !== UPLOAD_ERR_OK || empty($file['name'])) {
        flash('galat', 'Dokumen Bab wajib diunggah dalam format PDF.');
        redirect('mahasiswa/bab.php');
    }

    $tipe = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($tipe, $file['tmp_name']);
    finfo_close($tipe);
    if ($mime !== 'application/pdf' || $file['size'] > MAKS_UKURAN_UPLOAD) {
        flash('galat', 'Dokumen harus PDF dengan ukuran maksimal 5 MB.');
        redirect('mahasiswa/bab.php');
    }

    $st = $pdo->prepare('SELECT COALESCE(MAX(versi), 0) FROM bab_dokumen WHERE judul_id = ? AND bab = ?');
    $st->execute([$judul['id'], $bab]);
    $versi = (int) $st->fetchColumn() + 1;

    $namaFile = 'bab-' . $bab . '-v' . $versi . '-' . $uid . '-' . bin2hex(random_bytes(5)) . '.pdf';
    $tujuan = __DIR__ . '/../storage/bab/' . $namaFile;
    if (!is_dir(dirname($tujuan))) {
        mkdir(dirname($tujuan), 0775, true);
    }
    if (!move_uploaded_file($file['tmp_name'], $tujuan)) {
        flash('galat', 'Dokumen gagal disimpan.');
        redirect('mahasiswa/bab.php');
    }

    $st = $pdo->prepare('INSERT INTO bab_dokumen (judul_id, bab, versi, file_path, status)
                         VALUES (?, ?, ?, ?, "menunggu_dosen")');
    $st->execute([$judul['id'], $bab, $versi, 'storage/bab/' . $namaFile]);
    $babId = (int) $pdo->lastInsertId();

    kirim_notifikasi((int) $judul['pembimbing_id'], 'Mahasiswa mengunggah dokumen Bab ' . $bab . ' versi ' . $versi . '.', 'dosen/bab.php');
    audit_log($uid, 'UNGGAH_BAB', 'Bab ' . $bab . ' versi ' . $versi . ' ID ' . $babId);
    flash('sukses', 'Dokumen Bab ' . $bab . ' versi ' . $versi . ' berhasil dikirim.');
    redirect('mahasiswa/bab.php');
}

$dokumen = [];
if ($judul) {
    $st = $pdo->prepare('SELECT * FROM bab_dokumen WHERE judul_id = ? ORDER BY bab, versi DESC');
    $st->execute([$judul['id']]);
    $dokumen = $st->fetchAll();
}

$judulHalaman = 'Dokumen Bab';
require __DIR__ . '/../includes/header.php';
?>

<?php if (!$judul): ?>
  <section class="kartu"><p class="redup">Unggah dokumen Bab tersedia setelah judul disetujui.</p></section>
<?php else: ?>
  <div class="dua-kolom">
    <section class="kartu">
      <h2 class="judul-kartu">Unggah dokumen Bab</h2>
      <form method="post" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <label for="bab">Bab</label>
        <select id="bab" name="bab" required>
          <option value="">Pilih Bab</option>
          <?php for ($i = 1; $i <= 5; $i++): ?>
            <option value="<?= $i ?>">Bab <?= $i ?></option>
          <?php endfor; ?>
        </select>
        <label for="dokumen">File PDF (maksimal 5 MB)</label>
        <input type="file" id="dokumen" name="dokumen" accept="application/pdf" required>
        <button type="submit" class="btn btn-utama btn-penuh">Unggah dokumen</button>
      </form>
    </section>

    <section class="kartu">
      <h2 class="judul-kartu">Versi dokumen</h2>
      <?php if ($dokumen): ?>
        <div class="tabel-bungkus">
          <table class="tabel">
            <thead><tr><th>Bab</th><th>Versi</th><th>Status</th><th>Berkas</th></tr></thead>
            <tbody>
              <?php foreach ($dokumen as $d): ?>
                <tr>
                  <td>Bab <?= (int) $d['bab'] ?></td>
                  <td>Versi <?= (int) $d['versi'] ?></td>
                  <td><?= lencana(str_replace('_', ' ', $d['status']), $d['status'] === 'disetujui' ? 'hijau' : ($d['status'] === 'perlu_revisi' ? 'merah' : 'kuning')) ?></td>
                  <td><a href="<?= e(url($d['file_path'])) ?>" target="_blank" rel="noopener">Lihat</a></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php else: ?>
        <p class="redup">Belum ada dokumen Bab.</p>
      <?php endif; ?>
    </section>
  </div>
<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>
