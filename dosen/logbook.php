<?php
require_once __DIR__ . '/../includes/auth.php';
wajib_peran('dosen');

$uid = pengguna_login()['id'];
$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $logId = (int) ($_POST['log_id'] ?? 0);
    $status = (string) ($_POST['status'] ?? '');
    $catatan = trim((string) ($_POST['catatan'] ?? ''));

    $st = $pdo->prepare("SELECT l.*, j.mahasiswa_id, j.pembimbing_id, u.nama AS nama_mahasiswa
                         FROM logbook l JOIN judul_skripsi j ON j.id = l.judul_id
                         JOIN users u ON u.id = j.mahasiswa_id
                         WHERE l.id = ? AND j.pembimbing_id = ?");
    $st->execute([$logId, $uid]);
    $log = $st->fetch();

    if (!$log) {
        flash('galat', 'Catatan bimbingan tidak ditemukan atau bukan mahasiswa bimbingan Anda.');
        redirect('dosen/logbook.php');
    }
    if (!in_array($status, ['tervalidasi', 'perlu_revisi'], true)) {
        flash('galat', 'Status validasi tidak dikenali.');
        redirect('dosen/logbook.php');
    }

    $st = $pdo->prepare('UPDATE logbook SET status = ?, catatan_dosen = ?, validated_at = NOW()
                         WHERE id = ?');
    $st->execute([$status, $catatan ?: null, $logId]);

    $pesan = $status === 'tervalidasi'
        ? 'Bimbingan "' . $log['topik'] . '" telah divalidasi dosen.'
        : 'Bimbingan "' . $log['topik'] . '" perlu revisi.';
    kirim_notifikasi((int) $log['mahasiswa_id'], $pesan, 'mahasiswa/logbook.php');
    audit_log($uid, 'VALIDASI_LOGBOOK', 'Logbook ID ' . $logId . ' berstatus ' . $status);
    flash('sukses', 'Status bimbingan berhasil diperbarui.');
    redirect('dosen/logbook.php');
}

$st = $pdo->prepare("SELECT l.*, j.judul, u.nama AS nama_mahasiswa
                     FROM logbook l JOIN judul_skripsi j ON j.id = l.judul_id
                     JOIN users u ON u.id = j.mahasiswa_id
                     WHERE j.pembimbing_id = ?
                     ORDER BY l.status = 'menunggu_dosen' DESC, l.tanggal DESC, l.id DESC");
$st->execute([$uid]);
$daftar = $st->fetchAll();

$judulHalaman = 'Validasi Logbook';
require __DIR__ . '/../includes/header.php';
?>

<section class="kartu">
  <h2 class="judul-kartu">Catatan bimbingan mahasiswa</h2>
  <?php if ($daftar): ?>
    <div class="tabel-bungkus">
      <table class="tabel">
        <thead><tr><th>Mahasiswa</th><th>Bimbingan</th><th>Status</th><th>Validasi</th></tr></thead>
        <tbody>
          <?php foreach ($daftar as $l): ?>
            <tr>
              <td><strong><?= e($l['nama_mahasiswa']) ?></strong><br><small class="redup"><?= e($l['judul']) ?></small></td>
              <td><strong><?= e($l['topik']) ?></strong><br><?= e(tanggal_id($l['tanggal'])) ?><br><small class="redup"><?= e($l['uraian']) ?></small></td>
              <td><?= lencana(str_replace('_', ' ', $l['status']), $l['status'] === 'tervalidasi' ? 'hijau' : ($l['status'] === 'perlu_revisi' ? 'merah' : 'kuning')) ?></td>
              <td>
                <?php if ($l['status'] === 'menunggu_dosen'): ?>
                  <details class="aksi-verifikasi">
                    <summary class="btn btn-garis">Proses</summary>
                    <div class="panel-verifikasi">
                      <form method="post">
                        <?= csrf_field() ?>
                        <input type="hidden" name="log_id" value="<?= (int) $l['id'] ?>">
                        <input type="hidden" name="status" value="tervalidasi">
                        <label for="catatan-ok-<?= (int) $l['id'] ?>">Catatan (opsional)</label>
                        <textarea id="catatan-ok-<?= (int) $l['id'] ?>" name="catatan"></textarea>
                        <button type="submit" class="btn btn-utama">Validasi</button>
                      </form>
                      <form method="post">
                        <?= csrf_field() ?>
                        <input type="hidden" name="log_id" value="<?= (int) $l['id'] ?>">
                        <input type="hidden" name="status" value="perlu_revisi">
                        <label for="catatan-revisi-<?= (int) $l['id'] ?>">Catatan revisi</label>
                        <textarea id="catatan-revisi-<?= (int) $l['id'] ?>" name="catatan" required></textarea>
                        <button type="submit" class="btn btn-bahaya">Minta revisi</button>
                      </form>
                    </div>
                  </details>
                <?php else: ?>
                  <small class="redup"><?= e($l['catatan_dosen'] ?: '-') ?></small>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php else: ?>
    <p class="redup">Belum ada catatan logbook mahasiswa.</p>
  <?php endif; ?>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
