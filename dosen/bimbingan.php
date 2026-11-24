<?php
require_once __DIR__ . '/../includes/auth.php';
wajib_peran('dosen');

$uid = pengguna_login()['id'];
$pdo = db();

$st = $pdo->prepare("SELECT j.id, j.judul, u.nama, u.nim_nidn,
                            (SELECT COUNT(*) FROM logbook l
                             WHERE l.judul_id = j.id AND l.status = 'tervalidasi') AS tervalidasi,
                            (SELECT COUNT(DISTINCT b.bab) FROM bab_dokumen b
                             WHERE b.judul_id = j.id AND b.status = 'disetujui') AS bab_ok,
                            (SELECT COUNT(*) FROM logbook l
                             WHERE l.judul_id = j.id AND l.status = 'menunggu_dosen') AS logbook_menunggu,
                            (SELECT COUNT(*) FROM bab_dokumen b
                             WHERE b.judul_id = j.id AND b.status = 'menunggu_dosen') AS bab_menunggu
                     FROM judul_skripsi j JOIN users u ON u.id = j.mahasiswa_id
                     WHERE j.pembimbing_id = ? AND j.status = 'disetujui'
                     ORDER BY u.nama");
$st->execute([$uid]);
$bimbingan = $st->fetchAll();

$judulHalaman = 'Mahasiswa Bimbingan';
require __DIR__ . '/../includes/header.php';
?>

<section class="grid-stat">
  <?= kartu_statistik('Mahasiswa bimbingan', count($bimbingan), 'Judul yang sudah disetujui') ?>
  <?= kartu_statistik('Siap maju sidang', count(array_filter($bimbingan, fn($b) => (int) $b['tervalidasi'] >= MIN_BIMBINGAN)), 'Minimal ' . MIN_BIMBINGAN . ' bimbingan tervalidasi') ?>
  <?= kartu_statistik('Kuota pembimbing', count($bimbingan) . ' / ' . KUOTA_PEMBIMBING, 'Mahasiswa bimbingan aktif') ?>
</section>

<section class="kartu">
  <h2 class="judul-kartu">Daftar mahasiswa bimbingan</h2>
  <?php if ($bimbingan): ?>
    <div class="tabel-bungkus">
      <table class="tabel">
        <thead>
          <tr>
            <th>Mahasiswa</th>
            <th>Judul</th>
            <th>Progres logbook</th>
            <th>Bab disetujui</th>
            <th>Status</th>
            <th>Tindakan</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($bimbingan as $b): ?>
            <?php $siap = (int) $b['tervalidasi'] >= MIN_BIMBINGAN; ?>
            <tr>
              <td>
                <strong><?= e($b['nama']) ?></strong><br>
                <small class="redup"><?= e($b['nim_nidn'] ?: '-') ?></small>
              </td>
              <td><?= e($b['judul']) ?></td>
              <td>
                <?= (int) $b['tervalidasi'] ?> / <?= MIN_BIMBINGAN ?> tervalidasi
                <?php if ((int) $b['logbook_menunggu'] > 0): ?>
                  <br><small class="redup"><?= (int) $b['logbook_menunggu'] ?> menunggu validasi</small>
                <?php endif; ?>
              </td>
              <td>
                <?= (int) $b['bab_ok'] ?> / 5
                <?php if ((int) $b['bab_menunggu'] > 0): ?>
                  <br><small class="redup"><?= (int) $b['bab_menunggu'] ?> menunggu review</small>
                <?php endif; ?>
              </td>
              <td><?= $siap ? lencana('Siap maju sidang', 'hijau') : lencana('Masih bimbingan', 'kuning') ?></td>
              <td>
                <div class="aksi-cepat">
                  <a class="btn btn-garis" href="<?= e(url('dosen/logbook.php')) ?>">Logbook</a>
                  <a class="btn btn-garis" href="<?= e(url('dosen/bab.php')) ?>">Bab</a>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php else: ?>
    <p class="redup">Belum ada mahasiswa bimbingan yang ditetapkan kepada Anda.</p>
  <?php endif; ?>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
