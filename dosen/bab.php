<?php
require_once __DIR__ . '/../includes/auth.php';
wajib_peran('dosen');

$uid = pengguna_login()['id'];
$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $babId = (int) ($_POST['bab_id'] ?? 0);
    $status = (string) ($_POST['status'] ?? '');

    $st = $pdo->prepare("SELECT b.*, j.mahasiswa_id, j.pembimbing_id
                         FROM bab_dokumen b JOIN judul_skripsi j ON j.id = b.judul_id
                         WHERE b.id = ? AND j.pembimbing_id = ?");
    $st->execute([$babId, $uid]);
    $dokumen = $st->fetch();

    if (!$dokumen) {
        flash('galat', 'Dokumen tidak ditemukan atau bukan mahasiswa bimbingan Anda.');
        redirect('dosen/bab.php');
    }
    if (!in_array($status, ['disetujui', 'perlu_revisi'], true)) {
        flash('galat', 'Status dokumen tidak dikenali.');
        redirect('dosen/bab.php');
    }

    $st = $pdo->prepare('UPDATE bab_dokumen SET status = ? WHERE id = ?');
    $st->execute([$status, $babId]);

    $pesan = $status === 'disetujui'
        ? 'Dokumen Bab ' . $dokumen['bab'] . ' versi ' . $dokumen['versi'] . ' telah disetujui dosen.'
        : 'Dokumen Bab ' . $dokumen['bab'] . ' versi ' . $dokumen['versi'] . ' perlu revisi.';
    kirim_notifikasi((int) $dokumen['mahasiswa_id'], $pesan, 'mahasiswa/bab.php');
    audit_log($uid, 'REVIEW_BAB', 'Bab ' . $dokumen['bab'] . ' ID ' . $babId . ' berstatus ' . $status);
    flash('sukses', 'Status dokumen Bab berhasil diperbarui.');
    redirect('dosen/bab.php');
}

$st = $pdo->prepare("SELECT b.*, j.judul, u.nama AS nama_mahasiswa
                     FROM bab_dokumen b JOIN judul_skripsi j ON j.id = b.judul_id
                     JOIN users u ON u.id = j.mahasiswa_id
                     WHERE j.pembimbing_id = ?
                     ORDER BY b.status = 'menunggu_dosen' DESC, b.bab, b.versi DESC");
$st->execute([$uid]);
$dokumen = $st->fetchAll();

$judulHalaman = 'Review Bab';
require __DIR__ . '/../includes/header.php';
?>

<section class="kartu">
  <h2 class="judul-kartu">Dokumen Bab mahasiswa</h2>
  <?php if ($dokumen): ?>
    <div class="tabel-bungkus">
      <table class="tabel">
        <thead><tr><th>Mahasiswa</th><th>Dokumen</th><th>Status</th><th>Tindakan</th></tr></thead>
        <tbody>
          <?php foreach ($dokumen as $d): ?>
            <tr>
              <td><strong><?= e($d['nama_mahasiswa']) ?></strong><br><small class="redup"><?= e($d['judul']) ?></small></td>
              <td>Bab <?= (int) $d['bab'] ?> - Versi <?= (int) $d['versi'] ?><br><a href="<?= e(url($d['file_path'])) ?>" target="_blank" rel="noopener">Lihat PDF</a></td>
              <td><?= lencana(str_replace('_', ' ', $d['status']), $d['status'] === 'disetujui' ? 'hijau' : ($d['status'] === 'perlu_revisi' ? 'merah' : 'kuning')) ?></td>
              <td>
                <?php if ($d['status'] === 'menunggu_dosen'): ?>
                  <form method="post" class="aksi-baris">
                    <?= csrf_field() ?>
                    <input type="hidden" name="bab_id" value="<?= (int) $d['id'] ?>">
                    <button type="submit" name="status" value="disetujui" class="btn btn-utama">Setujui</button>
                    <button type="submit" name="status" value="perlu_revisi" class="btn btn-bahaya">Revisi</button>
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
    <p class="redup">Belum ada dokumen Bab dari mahasiswa.</p>
  <?php endif; ?>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
