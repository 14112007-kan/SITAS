<?php
require_once __DIR__ . '/../includes/auth.php';
wajib_peran('dosen');

$uid = pengguna_login()['id'];
$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $sidangId = (int) ($_POST['sidang_id'] ?? 0);
    $status = (string) ($_POST['status'] ?? '');

    $st = $pdo->prepare("SELECT s.*, j.mahasiswa_id
                         FROM sidang s JOIN judul_skripsi j ON j.id = s.judul_id
                         WHERE s.id = ? AND s.penguji_id = ?");
    $st->execute([$sidangId, $uid]);
    $sidang = $st->fetch();

    if (!$sidang) {
        flash('galat', 'Revisi tidak ditemukan atau Anda bukan penguji yang ditetapkan.');
        redirect('dosen/revisi.php');
    }
    if (!in_array($status, ['perlu_diperiksa', 'disetujui'], true)) {
        flash('galat', 'Status revisi tidak dikenali.');
        redirect('dosen/revisi.php');
    }

    $st = $pdo->prepare('UPDATE sidang SET revisi_status = ? WHERE id = ?');
    $st->execute([$status, $sidangId]);
    kirim_notifikasi((int) $sidang['mahasiswa_id'],
        $status === 'disetujui' ? 'Revisi pasca-sidang Anda telah disetujui.' : 'Revisi Anda dikembalikan untuk diperiksa kembali.',
        'mahasiswa/revisi.php');
    audit_log($uid, 'VERIFIKASI_REVISI', 'Revisi sidang ID ' . $sidangId . ' berstatus ' . $status);
    flash('sukses', 'Status revisi berhasil diperbarui.');
    redirect('dosen/revisi.php');
}

$st = $pdo->prepare("SELECT s.*, j.judul, m.nama AS nama_mahasiswa
                     FROM sidang s JOIN judul_skripsi j ON j.id = s.judul_id
                     JOIN users m ON m.id = j.mahasiswa_id
                     WHERE s.penguji_id = ? AND s.status = 'dinilai'
                     ORDER BY s.revisi_status = 'perlu_diperiksa' DESC, s.id DESC");
$st->execute([$uid]);
$daftar = $st->fetchAll();

$judulHalaman = 'Revisi Pasca-Sidang';
require __DIR__ . '/../includes/header.php';
?>

<section class="kartu">
  <h2 class="judul-kartu">Verifikasi revisi mahasiswa</h2>
  <?php if ($daftar): ?>
    <div class="tabel-bungkus">
      <table class="tabel">
        <thead><tr><th>Mahasiswa</th><th>Judul</th><th>Status revisi</th><th>Berkas</th><th>Aksi</th></tr></thead>
        <tbody>
          <?php foreach ($daftar as $r): ?>
            <tr>
              <td><?= e($r['nama_mahasiswa']) ?></td>
              <td><?= e($r['judul']) ?></td>
              <td><?= lencana(str_replace('_', ' ', $r['revisi_status']), $r['revisi_status'] === 'disetujui' ? 'hijau' : 'kuning') ?></td>
              <td><?php if ($r['revisi_path']): ?><a href="<?= e(url($r['revisi_path'])) ?>" target="_blank" rel="noopener">Lihat revisi</a><?php else: ?><span class="redup">Belum diunggah</span><?php endif; ?></td>
              <td>
                <?php if ($r['revisi_path'] && $r['revisi_status'] !== 'disetujui'): ?>
                  <form method="post" class="aksi-baris">
                    <?= csrf_field() ?>
                    <input type="hidden" name="sidang_id" value="<?= (int) $r['id'] ?>">
                    <button type="submit" name="status" value="disetujui" class="btn btn-utama">Setujui</button>
                    <button type="submit" name="status" value="perlu_diperiksa" class="btn btn-bahaya">Minta revisi ulang</button>
                  </form>
                <?php else: ?>
                  <span class="redup"><?= $r['revisi_status'] === 'disetujui' ? 'Sudah disetujui' : '-' ?></span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php else: ?>
    <p class="redup">Belum ada revisi pasca-sidang.</p>
  <?php endif; ?>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
