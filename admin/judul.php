<?php
require_once __DIR__ . '/../includes/auth.php';
wajib_peran('admin');

$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $judulId = (int) ($_POST['judul_id'] ?? 0);
    $aksi = (string) ($_POST['aksi'] ?? '');

    $st = $pdo->prepare('SELECT j.*, u.nama AS nama_mahasiswa
                         FROM judul_skripsi j
                         JOIN users u ON u.id = j.mahasiswa_id
                         WHERE j.id = ?');
    $st->execute([$judulId]);
    $judul = $st->fetch();

    if (!$judul || $judul['status'] !== 'menunggu') {
        flash('galat', 'Pengajuan tidak ditemukan atau sudah diputuskan.');
        redirect('admin/judul.php');
    }

    if ($aksi === 'tolak') {
        $alasan = trim((string) ($_POST['alasan'] ?? ''));
        if (mb_strlen($alasan) < 10) {
            flash('galat', 'Alasan penolakan minimal 10 karakter.');
            redirect('admin/judul.php');
        }

        $pdo->beginTransaction();
        try {
            $st = $pdo->prepare('UPDATE judul_skripsi
                                 SET status = "ditolak", alasan_penolakan = ?, diputuskan_at = NOW()
                                 WHERE id = ?');
            $st->execute([$alasan, $judulId]);
            kirim_notifikasi((int) $judul['mahasiswa_id'], 'Pengajuan judul Anda ditolak. Baca alasan penolakan dan ajukan judul baru.', 'mahasiswa/judul.php');
            audit_log(pengguna_login()['id'], 'TOLAK_JUDUL', 'Judul ID ' . $judulId . ' ditolak: ' . $alasan);
            $pdo->commit();
            flash('sukses', 'Pengajuan judul ditolak dan mahasiswa telah diberi notifikasi.');
        } catch (Throwable $e) {
            $pdo->rollBack();
            flash('galat', 'Pengajuan gagal diputuskan.');
        }
        redirect('admin/judul.php');
    }

    if ($aksi === 'setujui') {
        $pembimbingId = (int) ($_POST['pembimbing_id'] ?? 0);
        if ($pembimbingId <= 0) {
            flash('galat', 'Pilih dosen pembimbing sebelum menyetujui judul.');
            redirect('admin/judul.php');
        }
        if ((float) $judul['kemiripan'] >= AMBANG_KEMIRIPAN) {
            flash('galat', 'Judul tidak dapat disetujui karena kemiripannya mencapai ambang ' . AMBANG_KEMIRIPAN . '%.');
            redirect('admin/judul.php');
        }

        $st = $pdo->prepare("SELECT COUNT(*) FROM judul_skripsi
                             WHERE pembimbing_id = ? AND status = 'disetujui'");
        $st->execute([$pembimbingId]);
        if ((int) $st->fetchColumn() >= KUOTA_PEMBIMBING) {
            flash('galat', 'Kuota pembimbing tersebut sudah penuh.');
            redirect('admin/judul.php');
        }

        $pdo->beginTransaction();
        try {
            $st = $pdo->prepare('UPDATE judul_skripsi
                                 SET status = "disetujui", pembimbing_id = ?,
                                     alasan_penolakan = NULL, diputuskan_at = NOW()
                                 WHERE id = ?');
            $st->execute([$pembimbingId, $judulId]);

            $st = $pdo->prepare('SELECT nama FROM users WHERE id = ?');
            $st->execute([$pembimbingId]);
            $namaPembimbing = (string) $st->fetchColumn();

            kirim_notifikasi((int) $judul['mahasiswa_id'], 'Pengajuan judul Anda disetujui. Pembimbing: ' . $namaPembimbing . '.', 'mahasiswa/dashboard.php');
            audit_log(pengguna_login()['id'], 'SETUJUI_JUDUL', 'Judul ID ' . $judulId . ' disetujui; pembimbing ID ' . $pembimbingId);
            $pdo->commit();
            flash('sukses', 'Judul disetujui dan pembimbing berhasil ditetapkan.');
        } catch (Throwable $e) {
            $pdo->rollBack();
            flash('galat', 'Pengajuan gagal diputuskan.');
        }
        redirect('admin/judul.php');
    }

    flash('galat', 'Tindakan tidak dikenali.');
    redirect('admin/judul.php');
}

$statusFilter = (string) ($_GET['status'] ?? 'menunggu');
$statusValid = ['menunggu', 'disetujui', 'ditolak', 'semua'];
if (!in_array($statusFilter, $statusValid, true)) {
    $statusFilter = 'menunggu';
}

$sql = 'SELECT j.*, m.nama AS nama_mahasiswa, m.nim_nidn,
               d.nama AS nama_pembimbing
        FROM judul_skripsi j
        JOIN users m ON m.id = j.mahasiswa_id
        LEFT JOIN users d ON d.id = j.pembimbing_id';
$params = [];
if ($statusFilter !== 'semua') {
    $sql .= ' WHERE j.status = ?';
    $params[] = $statusFilter;
}
$sql .= ' ORDER BY j.diajukan_at DESC, j.id DESC';
$st = $pdo->prepare($sql);
$st->execute($params);
$daftarJudul = $st->fetchAll();

$st = $pdo->query("SELECT u.id, u.nama,
                          (SELECT COUNT(*) FROM judul_skripsi j
                           WHERE j.pembimbing_id = u.id AND j.status = 'disetujui') AS jumlah
                   FROM users u JOIN user_roles r ON r.user_id = u.id
                   WHERE r.role = 'dosen' AND u.aktif = 1
                   ORDER BY u.nama");
$dosen = $st->fetchAll();

$judulHalaman = 'Verifikasi Judul';
require __DIR__ . '/../includes/header.php';
?>

<section class="kartu">
  <div class="toolbar">
    <h2 class="judul-kartu">Antrean pengajuan judul</h2>
    <form method="get" class="filter-inline">
      <label for="status-filter">Status</label>
      <select id="status-filter" name="status" onchange="this.form.submit()">
        <option value="menunggu" <?= $statusFilter === 'menunggu' ? 'selected' : '' ?>>Menunggu</option>
        <option value="disetujui" <?= $statusFilter === 'disetujui' ? 'selected' : '' ?>>Disetujui</option>
        <option value="ditolak" <?= $statusFilter === 'ditolak' ? 'selected' : '' ?>>Ditolak</option>
        <option value="semua" <?= $statusFilter === 'semua' ? 'selected' : '' ?>>Semua</option>
      </select>
    </form>
  </div>

  <?php if ($daftarJudul): ?>
    <div class="tabel-bungkus">
      <table class="tabel">
        <thead>
          <tr>
            <th>Mahasiswa</th>
            <th>Judul & Bidang</th>
            <th>Kemiripan</th>
            <th>Pembimbing</th>
            <th>Tanggal</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($daftarJudul as $j): ?>
            <tr>
              <td><strong><?= e($j['nama_mahasiswa']) ?></strong><br><small class="redup"><?= e($j['nim_nidn'] ?: '-') ?></small></td>
              <td>
                <strong><?= e($j['judul']) ?></strong><br>
                <small class="redup"><?= e($j['bidang_minat'] ?: '-') ?></small>
                <?php if ($j['proposal_path']): ?>
                  <br><a href="<?= e(url($j['proposal_path'])) ?>" target="_blank" rel="noopener">Lihat proposal</a>
                <?php endif; ?>
              </td>
              <td><?= lencana(number_format((float) $j['kemiripan'], 1) . '%', (float) $j['kemiripan'] >= AMBANG_KEMIRIPAN ? 'merah' : 'hijau') ?></td>
              <td><?= e($j['nama_pembimbing'] ?: 'Belum ditetapkan') ?></td>
              <td><?= e(tanggal_id($j['diajukan_at'], true)) ?></td>
              <td>
                <?php if ($j['status'] === 'menunggu'): ?>
                  <details class="aksi-verifikasi">
                    <summary class="btn btn-garis">Proses</summary>
                    <div class="panel-verifikasi">
                      <?php if ($j['abstrak']): ?>
                        <p><strong>Abstrak</strong><br><?= e($j['abstrak']) ?></p>
                      <?php endif; ?>
                      <form method="post">
                        <?= csrf_field() ?>
                        <input type="hidden" name="judul_id" value="<?= (int) $j['id'] ?>">
                        <input type="hidden" name="aksi" value="setujui">
                        <label for="pembimbing-<?= (int) $j['id'] ?>">Pembimbing</label>
                        <select id="pembimbing-<?= (int) $j['id'] ?>" name="pembimbing_id" required>
                          <option value="">Pilih dosen</option>
                          <?php foreach ($dosen as $d): ?>
                            <option value="<?= (int) $d['id'] ?>" <?= (int) $d['jumlah'] >= KUOTA_PEMBIMBING ? 'disabled' : '' ?>>
                              <?= e($d['nama']) ?> (<?= (int) $d['jumlah'] ?>/<?= KUOTA_PEMBIMBING ?>)
                            </option>
                          <?php endforeach; ?>
                        </select>
                        <button type="submit" class="btn btn-utama">Setujui</button>
                      </form>
                      <form method="post">
                        <?= csrf_field() ?>
                        <input type="hidden" name="judul_id" value="<?= (int) $j['id'] ?>">
                        <input type="hidden" name="aksi" value="tolak">
                        <label for="alasan-<?= (int) $j['id'] ?>">Alasan penolakan</label>
                        <textarea id="alasan-<?= (int) $j['id'] ?>" name="alasan" minlength="10" required></textarea>
                        <button type="submit" class="btn btn-bahaya">Tolak</button>
                      </form>
                    </div>
                  </details>
                <?php else: ?>
                  <?= lencana(str_replace('_', ' ', ucfirst($j['status'])), $j['status'] === 'disetujui' ? 'hijau' : 'merah') ?>
                  <?php if ($j['status'] === 'ditolak' && $j['alasan_penolakan']): ?>
                    <small class="redup"><?= e($j['alasan_penolakan']) ?></small>
                  <?php endif; ?>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php else: ?>
    <p class="redup">Tidak ada pengajuan judul pada status ini.</p>
  <?php endif; ?>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
