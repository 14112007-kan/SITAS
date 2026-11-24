<?php
require_once __DIR__ . '/../includes/auth.php';
wajib_peran('dosen');

$uid = pengguna_login()['id'];
$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $sidangId = (int) ($_POST['sidang_id'] ?? 0);
    $setuju = ($_POST['aksi'] ?? '') === 'setujui';

    $st = $pdo->prepare("SELECT s.*, j.mahasiswa_id, j.pembimbing_id
                         FROM sidang s JOIN judul_skripsi j ON j.id = s.judul_id
                         WHERE s.id = ? AND j.pembimbing_id = ?");
    $st->execute([$sidangId, $uid]);
    $sidang = $st->fetch();

    if (!$sidang) {
        flash('galat', 'Pendaftaran sidang tidak ditemukan atau bukan mahasiswa bimbingan Anda.');
        redirect('dosen/maju-sidang.php');
    }

    $st = $pdo->prepare("SELECT COUNT(*) FROM logbook
                         WHERE judul_id = ? AND status = 'tervalidasi'");
    $st->execute([$sidang['judul_id']]);
    $tervalidasi = (int) $st->fetchColumn();

    if ($setuju && $tervalidasi < MIN_BIMBINGAN) {
        flash('galat', 'Mahasiswa belum mencapai ' . MIN_BIMBINGAN . ' bimbingan tervalidasi.');
        redirect('dosen/maju-sidang.php');
    }

    $st = $pdo->prepare('UPDATE sidang SET persetujuan_dosen = ? WHERE id = ?');
    $st->execute([$setuju ? 1 : 0, $sidangId]);

    kirim_notifikasi((int) $sidang['mahasiswa_id'],
        $setuju ? 'Pembimbing menyetujui Anda maju sidang.' : 'Pembimbing belum menyetujui Anda maju sidang.',
        'mahasiswa/sidang.php');
    audit_log($uid, $setuju ? 'SETUJUI_MAJU_SIDANG' : 'TOLAK_MAJU_SIDANG', 'Sidang ID ' . $sidangId);
    flash('sukses', 'Persetujuan maju sidang berhasil diperbarui.');
    redirect('dosen/maju-sidang.php');
}

$st = $pdo->prepare("SELECT s.*, j.id AS judul_id, j.judul, u.nama AS nama_mahasiswa,
                            (SELECT COUNT(*) FROM logbook l WHERE l.judul_id = j.id AND l.status = 'tervalidasi') AS tervalidasi
                     FROM sidang s JOIN judul_skripsi j ON j.id = s.judul_id
                     JOIN users u ON u.id = j.mahasiswa_id
                     WHERE j.pembimbing_id = ? AND s.jenis = 'proposal'
                     ORDER BY s.created_at DESC");
$st->execute([$uid]);
$daftar = $st->fetchAll();

$judulHalaman = 'Maju Sidang';
require __DIR__ . '/../includes/header.php';
?>

<section class="kartu">
  <h2 class="judul-kartu">Persetujuan mahasiswa maju sidang proposal</h2>
  <?php if ($daftar): ?>
    <div class="tabel-bungkus">
      <table class="tabel">
        <thead><tr><th>Mahasiswa</th><th>Judul</th><th>Bimbingan</th><th>Persetujuan</th><th>Aksi</th></tr></thead>
        <tbody>
          <?php foreach ($daftar as $s): ?>
            <tr>
              <td><?= e($s['nama_mahasiswa']) ?></td>
              <td><?= e($s['judul']) ?></td>
              <td><?= (int) $s['tervalidasi'] ?> / <?= MIN_BIMBINGAN ?></td>
              <td><?= lencana($s['persetujuan_dosen'] ? 'Disetujui' : 'Menunggu', $s['persetujuan_dosen'] ? 'hijau' : 'kuning') ?></td>
              <td>
                <?php if (!$s['persetujuan_dosen']): ?>
                  <form method="post" class="aksi-baris">
                    <?= csrf_field() ?>
                    <input type="hidden" name="sidang_id" value="<?= (int) $s['id'] ?>">
                    <button type="submit" name="aksi" value="setujui" class="btn btn-utama">Setujui</button>
                    <button type="submit" name="aksi" value="tolak" class="btn btn-bahaya">Belum siap</button>
                  </form>
                <?php else: ?>
                  <span class="redup">Sudah diproses</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php else: ?>
    <p class="redup">Belum ada mahasiswa yang mendaftar sidang proposal.</p>
  <?php endif; ?>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
