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
        flash('galat', 'Judul skripsi belum disetujui sehingga logbook belum tersedia.');
        redirect('mahasiswa/logbook.php');
    }

    $tanggal = trim((string) ($_POST['tanggal'] ?? ''));
    $topik = trim((string) ($_POST['topik'] ?? ''));
    $uraian = trim((string) ($_POST['uraian'] ?? ''));
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal) || mb_strlen($topik) < 5) {
        flash('galat', 'Tanggal dan topik bimbingan wajib diisi dengan benar.');
        redirect('mahasiswa/logbook.php');
    }

    $st = $pdo->prepare('INSERT INTO logbook (judul_id, tanggal, topik, uraian, status)
                         VALUES (?, ?, ?, ?, "menunggu_dosen")');
    $st->execute([$judul['id'], $tanggal, $topik, $uraian]);
    $logId = (int) $pdo->lastInsertId();

    kirim_notifikasi((int) $judul['pembimbing_id'], 'Mahasiswa mengirim catatan logbook baru: ' . $topik . '.', 'dosen/logbook.php');
    audit_log($uid, 'TAMBAH_LOGBOOK', 'Menambah logbook ID ' . $logId);
    flash('sukses', 'Catatan bimbingan berhasil dikirim kepada dosen pembimbing.');
    redirect('mahasiswa/logbook.php');
}

$daftar = [];
if ($judul) {
    $st = $pdo->prepare('SELECT * FROM logbook WHERE judul_id = ? ORDER BY tanggal DESC, id DESC');
    $st->execute([$judul['id']]);
    $daftar = $st->fetchAll();
}

$tervalidasi = count(array_filter($daftar, fn($l) => $l['status'] === 'tervalidasi'));
$judulHalaman = 'Logbook Bimbingan';
require __DIR__ . '/../includes/header.php';
?>

<?php if (!$judul): ?>
  <section class="kartu"><p class="redup">Logbook tersedia setelah judul skripsi disetujui dan pembimbing ditetapkan.</p></section>
<?php else: ?>
  <section class="grid-stat">
    <?= kartu_statistik('Bimbingan tervalidasi', $tervalidasi . ' / ' . MIN_BIMBINGAN, 'Progres menuju sidang') ?>
    <?= kartu_statistik('Pembimbing', $judul['nama_pembimbing'] ?? '-', 'Dosen pembimbing aktif') ?>
  </section>

  <div class="dua-kolom">
    <section class="kartu">
      <h2 class="judul-kartu">Tambah catatan bimbingan</h2>
      <form method="post">
        <?= csrf_field() ?>
        <label for="tanggal">Tanggal bimbingan</label>
        <input type="date" id="tanggal" name="tanggal" required value="<?= date('Y-m-d') ?>">
        <label for="topik">Topik</label>
        <input type="text" id="topik" name="topik" required minlength="5" maxlength="200">
        <label for="uraian">Uraian bimbingan</label>
        <textarea id="uraian" name="uraian" required minlength="10"></textarea>
        <button type="submit" class="btn btn-utama btn-penuh">Kirim catatan</button>
      </form>
    </section>

    <section class="kartu">
      <h2 class="judul-kartu">Riwayat bimbingan</h2>
      <?php if ($daftar): ?>
        <div class="tabel-bungkus">
          <table class="tabel">
            <thead><tr><th>Tanggal</th><th>Topik</th><th>Status</th></tr></thead>
            <tbody>
              <?php foreach ($daftar as $l): ?>
                <tr>
                  <td><?= e(tanggal_id($l['tanggal'])) ?></td>
                  <td><strong><?= e($l['topik']) ?></strong><br><small class="redup"><?= e($l['uraian']) ?></small>
                    <?php if ($l['catatan_dosen']): ?><br><small>Catatan dosen: <?= e($l['catatan_dosen']) ?></small><?php endif; ?>
                  </td>
                  <td><?= lencana(str_replace('_', ' ', $l['status']), $l['status'] === 'tervalidasi' ? 'hijau' : ($l['status'] === 'perlu_revisi' ? 'merah' : 'kuning')) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php else: ?>
        <p class="redup">Belum ada catatan bimbingan.</p>
      <?php endif; ?>
    </section>
  </div>
<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>
