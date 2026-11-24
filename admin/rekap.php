<?php
require_once __DIR__ . '/../includes/auth.php';
wajib_peran('admin');

$pdo = db();

$mahasiswa = (int) $pdo->query("SELECT COUNT(*) FROM user_roles WHERE role = 'mahasiswa'")->fetchColumn();
$dosen = (int) $pdo->query("SELECT COUNT(*) FROM user_roles WHERE role = 'dosen'")->fetchColumn();
$admin = (int) $pdo->query("SELECT COUNT(*) FROM user_roles WHERE role = 'admin'")->fetchColumn();
$judulMenunggu = (int) $pdo->query("SELECT COUNT(*) FROM judul_skripsi WHERE status = 'menunggu'")->fetchColumn();
$sidangAktif = (int) $pdo->query("SELECT COUNT(*) FROM sidang WHERE status IN ('didaftarkan','berkas_lengkap','terjadwal','dinilai')")->fetchColumn();
$naskahMasuk = (int) $pdo->query("SELECT COUNT(*) FROM naskah_final WHERE status = 'terkirim_admin'")->fetchColumn();

$st = $pdo->query("SELECT u.nama, j.judul, j.status AS status_judul,
                          s.status AS status_sidang, s.revisi_status,
                          n.status AS status_naskah
                   FROM judul_skripsi j JOIN users u ON u.id = j.mahasiswa_id
                   LEFT JOIN sidang s ON s.id = (SELECT id FROM sidang s2 WHERE s2.judul_id = j.id ORDER BY s2.id DESC LIMIT 1)
                   LEFT JOIN naskah_final n ON n.id = (SELECT id FROM naskah_final n2 WHERE n2.judul_id = j.id ORDER BY n2.id DESC LIMIT 1)
                   ORDER BY u.nama");
$rekap = $st->fetchAll();

$judulHalaman = 'Rekap & Perhatian';
require __DIR__ . '/../includes/header.php';
?>

<section class="grid-stat">
  <?= kartu_statistik('Mahasiswa', $mahasiswa, 'Pengguna berperan mahasiswa') ?>
  <?= kartu_statistik('Dosen', $dosen, 'Pengguna berperan dosen') ?>
  <?= kartu_statistik('Admin', $admin, 'Pengguna berperan admin') ?>
  <?= kartu_statistik('Judul menunggu', $judulMenunggu, 'Belum diputuskan') ?>
  <?= kartu_statistik('Sidang aktif', $sidangAktif, 'Dalam proses') ?>
  <?= kartu_statistik('Naskah masuk', $naskahMasuk, 'Belum diarsipkan') ?>
</section>

<section class="kartu">
  <h2 class="judul-kartu">Rekap kemajuan mahasiswa</h2>
  <?php if ($rekap): ?>
    <div class="tabel-bungkus"><table class="tabel"><thead><tr><th>Mahasiswa</th><th>Judul</th><th>Judul</th><th>Sidang</th><th>Revisi</th><th>Naskah</th></tr></thead><tbody>
      <?php foreach ($rekap as $r): ?>
        <tr>
          <td><?= e($r['nama']) ?></td><td><?= e($r['judul']) ?></td>
          <td><?= lencana(str_replace('_', ' ', $r['status_judul']), $r['status_judul'] === 'disetujui' ? 'hijau' : ($r['status_judul'] === 'ditolak' ? 'merah' : 'kuning')) ?></td>
          <td><?= lencana(str_replace('_', ' ', $r['status_sidang'] ?: 'belum'), $r['status_sidang'] ? 'biru' : 'abu') ?></td>
          <td><?= lencana(str_replace('_', ' ', $r['revisi_status'] ?: 'belum'), $r['revisi_status'] === 'disetujui' ? 'hijau' : 'abu') ?></td>
          <td><?= lencana(str_replace('_', ' ', $r['status_naskah'] ?: 'belum'), $r['status_naskah'] === 'diarsipkan' ? 'hijau' : 'abu') ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody></table></div>
  <?php else: ?><p class="redup">Belum ada data kemajuan mahasiswa.</p><?php endif; ?>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
