<?php
require_once __DIR__ . '/../includes/auth.php';
wajib_peran('admin');

$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $naskahId = (int) ($_POST['naskah_id'] ?? 0);
    $aksi = (string) ($_POST['aksi'] ?? '');
    $akses = (string) ($_POST['akses'] ?? 'internal');

    $st = $pdo->prepare('SELECT n.*, j.mahasiswa_id, j.judul
                         FROM naskah_final n JOIN judul_skripsi j ON j.id = n.judul_id
                         WHERE n.id = ?');
    $st->execute([$naskahId]);
    $naskah = $st->fetch();

    if (!$naskah) {
        flash('galat', 'Naskah final tidak ditemukan.');
        redirect('admin/naskah.php');
    }

    if ($aksi === 'arsipkan') {
        if (!in_array($akses, ['publik', 'internal', 'ditunda'], true)) {
            flash('galat', 'Pilihan akses tidak dikenali.');
            redirect('admin/naskah.php');
        }
        $st = $pdo->prepare("UPDATE naskah_final
                             SET status = 'diarsipkan', akses = ?, diarsipkan_at = NOW()
                             WHERE id = ?");
        $st->execute([$akses, $naskahId]);
        kirim_notifikasi((int) $naskah['mahasiswa_id'], 'Naskah final telah diarsipkan dengan akses ' . $akses . '.', 'mahasiswa/revisi.php');
        audit_log(pengguna_login()['id'], 'ARSIP_NASKAH_FINAL', 'Mengarsipkan naskah final ID ' . $naskahId);
        flash('sukses', 'Naskah final berhasil diarsipkan.');
    }

    redirect('admin/naskah.php');
}

$st = $pdo->query("SELECT n.*, j.judul, u.nama AS nama_mahasiswa, u.nim_nidn
                   FROM naskah_final n JOIN judul_skripsi j ON j.id = n.judul_id
                   JOIN users u ON u.id = j.mahasiswa_id
                   ORDER BY n.status = 'terkirim_admin' DESC, n.created_at DESC");
$daftar = $st->fetchAll();

$judulHalaman = 'Naskah Final';
require __DIR__ . '/../includes/header.php';
?>

<section class="kartu">
  <h2 class="judul-kartu">Antrean naskah final</h2>
  <?php if ($daftar): ?>
    <div class="tabel-bungkus">
      <table class="tabel">
        <thead><tr><th>Mahasiswa</th><th>Judul</th><th>Tanggal kirim</th><th>Status / Akses</th><th>Aksi</th></tr></thead>
        <tbody>
          <?php foreach ($daftar as $n): ?>
            <tr>
              <td><strong><?= e($n['nama_mahasiswa']) ?></strong><br><small class="redup"><?= e($n['nim_nidn'] ?: '-') ?></small></td>
              <td><?= e($n['judul']) ?></td>
              <td><?= e(tanggal_id($n['created_at'], true)) ?></td>
              <td><?= lencana(str_replace('_', ' ', $n['status']), $n['status'] === 'diarsipkan' ? 'hijau' : 'kuning') ?><br><small><?= e($n['akses'] ?: 'Belum ditetapkan') ?></small></td>
              <td>
                <a class="btn btn-garis" href="<?= e(url($n['file_path'])) ?>" target="_blank" rel="noopener">Lihat PDF</a>
                <?php if ($n['status'] === 'terkirim_admin'): ?>
                  <form method="post" class="aksi-baris" style="margin-top:8px">
                    <?= csrf_field() ?>
                    <input type="hidden" name="naskah_id" value="<?= (int) $n['id'] ?>">
                    <select name="akses" aria-label="Opsi akses">
                      <option value="publik">Publik</option>
                      <option value="internal">Internal</option>
                      <option value="ditunda">Ditunda</option>
                    </select>
                    <button type="submit" name="aksi" value="arsipkan" class="btn btn-utama">Arsipkan</button>
                  </form>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php else: ?>
    <p class="redup">Belum ada naskah final.</p>
  <?php endif; ?>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
