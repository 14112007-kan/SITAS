<?php
require_once __DIR__ . '/../includes/auth.php';
wajib_peran('dosen');

$uid = pengguna_login()['id'];
$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $sidangId = (int) ($_POST['sidang_id'] ?? 0);

    $st = $pdo->prepare("SELECT s.*, j.mahasiswa_id, j.judul
                         FROM sidang s JOIN judul_skripsi j ON j.id = s.judul_id
                         WHERE s.id = ? AND s.penguji_id = ?");
    $st->execute([$sidangId, $uid]);
    $sidang = $st->fetch();

    if (!$sidang || $sidang['status'] !== 'terjadwal') {
        flash('galat', 'Sidang belum dijadwalkan atau Anda bukan penguji yang ditetapkan.');
        redirect('dosen/menguji.php');
    }

    $materi = (float) ($_POST['nilai_materi'] ?? -1);
    $metodologi = (float) ($_POST['nilai_metodologi'] ?? -1);
    $penulisan = (float) ($_POST['nilai_penulisan'] ?? -1);
    $presentasi = (float) ($_POST['nilai_presentasi'] ?? -1);
    $catatan = trim((string) ($_POST['catatan_revisi'] ?? ''));

    foreach ([$materi, $metodologi, $penulisan, $presentasi] as $nilai) {
        if ($nilai < 0 || $nilai > 100) {
            flash('galat', 'Setiap nilai harus berada pada rentang 0 sampai 100.');
            redirect('dosen/menguji.php');
        }
    }

    $nilaiAkhir = ($materi * 0.30) + ($metodologi * 0.30) + ($penulisan * 0.20) + ($presentasi * 0.20);

    $pdo->beginTransaction();
    try {
        $st = $pdo->prepare('INSERT INTO sidang_nilai
                             (sidang_id, penguji_id, nilai_materi, nilai_metodologi,
                              nilai_penulisan, nilai_presentasi, nilai_akhir, catatan_revisi)
                             VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
        $st->execute([$sidangId, $uid, $materi, $metodologi, $penulisan, $presentasi, $nilaiAkhir, $catatan ?: null]);

        $st = $pdo->prepare("UPDATE sidang
                             SET status = 'dinilai', revisi_status = 'menunggu_mahasiswa'
                             WHERE id = ?");
        $st->execute([$sidangId]);

        kirim_notifikasi((int) $sidang['mahasiswa_id'], 'Sidang telah dinilai. Silakan pantau dan unggah revisi pasca-sidang.', 'mahasiswa/revisi.php');
        audit_log($uid, 'NILAI_SIDANG', 'Menilai sidang ID ' . $sidangId . ' dengan nilai ' . number_format($nilaiAkhir, 2));
        $pdo->commit();
        flash('sukses', 'Penilaian berhasil disimpan. Nilai akhir: ' . number_format($nilaiAkhir, 2));
    } catch (Throwable $e) {
        $pdo->rollBack();
        flash('galat', 'Penilaian gagal disimpan atau sidang sudah dinilai.');
    }
    redirect('dosen/menguji.php');
}

$st = $pdo->prepare("SELECT s.*, j.judul, m.nama AS nama_mahasiswa, r.nama AS nama_ruang,
                            n.nilai_akhir
                     FROM sidang s JOIN judul_skripsi j ON j.id = s.judul_id
                     JOIN users m ON m.id = j.mahasiswa_id
                     LEFT JOIN master_ruang r ON r.id = s.ruang_id
                     LEFT JOIN sidang_nilai n ON n.sidang_id = s.id
                     WHERE s.penguji_id = ? AND s.status IN ('terjadwal', 'dinilai')
                     ORDER BY s.jadwal");
$st->execute([$uid]);
$daftar = $st->fetchAll();

$judulHalaman = 'Menguji & Penilaian';
require __DIR__ . '/../includes/header.php';
?>

<section class="kartu">
  <h2 class="judul-kartu">Sidang yang menjadi tanggung jawab Anda</h2>
  <?php if ($daftar): ?>
    <div class="tabel-bungkus">
      <table class="tabel">
        <thead><tr><th>Mahasiswa</th><th>Sidang</th><th>Jadwal</th><th>Status / Nilai</th><th>Aksi</th></tr></thead>
        <tbody>
          <?php foreach ($daftar as $s): ?>
            <tr>
              <td><?= e($s['nama_mahasiswa']) ?></td>
              <td><small class="redup"><?= ucfirst($s['jenis']) ?></small><br><strong><?= e($s['judul']) ?></strong></td>
              <td><?= e($s['nama_ruang'] ?: '-') ?><br><small><?= e($s['jadwal'] ? tanggal_id($s['jadwal'], true) : '-') ?></small></td>
              <td><?= lencana(str_replace('_', ' ', $s['status']), $s['status'] === 'dinilai' ? 'hijau' : 'kuning') ?>
                <?php if ($s['nilai_akhir'] !== null): ?><br><strong><?= number_format((float) $s['nilai_akhir'], 2) ?></strong><?php endif; ?>
              </td>
              <td>
                <?php if ($s['status'] === 'terjadwal'): ?>
                  <details class="aksi-verifikasi">
                    <summary class="btn btn-garis">Nilai sidang</summary>
                    <div class="panel-verifikasi">
                      <form method="post">
                        <?= csrf_field() ?>
                        <input type="hidden" name="sidang_id" value="<?= (int) $s['id'] ?>">
                        <?php foreach ([
                          'nilai_materi' => 'Materi (30%)',
                          'nilai_metodologi' => 'Metodologi (30%)',
                          'nilai_penulisan' => 'Penulisan (20%)',
                          'nilai_presentasi' => 'Presentasi (20%)',
                        ] as $nama => $label): ?>
                          <label for="<?= e($nama) ?>-<?= (int) $s['id'] ?>"><?= e($label) ?></label>
                          <input type="number" id="<?= e($nama) ?>-<?= (int) $s['id'] ?>" name="<?= e($nama) ?>" min="0" max="100" step="0.01" required>
                        <?php endforeach; ?>
                        <label for="catatan-<?= (int) $s['id'] ?>">Catatan revisi</label>
                        <textarea id="catatan-<?= (int) $s['id'] ?>" name="catatan_revisi"></textarea>
                        <button type="submit" class="btn btn-utama">Simpan nilai</button>
                      </form>
                    </div>
                  </details>
                <?php else: ?>
                  <span class="redup">Sudah dinilai</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php else: ?>
    <p class="redup">Belum ada sidang yang ditugaskan kepada Anda.</p>
  <?php endif; ?>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
