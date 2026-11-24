<?php
require_once __DIR__ . '/../includes/auth.php';
wajib_peran('admin');

$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $sidangId = (int) ($_POST['sidang_id'] ?? 0);
    $pengujiId = (int) ($_POST['penguji_id'] ?? 0);
    $ruangId = (int) ($_POST['ruang_id'] ?? 0);
    $jadwal = trim((string) ($_POST['jadwal'] ?? ''));

    $st = $pdo->prepare("SELECT s.*, j.mahasiswa_id, j.judul
                         FROM sidang s JOIN judul_skripsi j ON j.id = s.judul_id
                         WHERE s.id = ?");
    $st->execute([$sidangId]);
    $sidang = $st->fetch();

    if (!$sidang || $sidang['status'] !== 'berkas_lengkap') {
        flash('galat', 'Sidang harus berstatus berkas lengkap sebelum dijadwalkan.');
        redirect('admin/jadwal.php');
    }
    if ($pengujiId <= 0 || $ruangId <= 0 || !preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/', $jadwal)) {
        flash('galat', 'Lengkapi penguji, ruang, dan jadwal sidang.');
        redirect('admin/jadwal.php');
    }

    $tanggal = str_replace('T', ' ', $jadwal) . ':00';
    $st = $pdo->prepare("UPDATE sidang
                         SET penguji_id = ?, ruang_id = ?, jadwal = ?, status = 'terjadwal'
                         WHERE id = ?");
    $st->execute([$pengujiId, $ruangId, $tanggal, $sidangId]);

    kirim_notifikasi((int) $sidang['mahasiswa_id'], 'Sidang Anda dijadwalkan pada ' . tanggal_id($tanggal, true) . '.', 'mahasiswa/sidang.php');
    kirim_notifikasi($pengujiId, 'Anda ditetapkan sebagai penguji sidang: ' . $sidang['judul'] . '.', 'dosen/menguji.php');
    audit_log(pengguna_login()['id'], 'JADWALKAN_SIDANG', 'Sidang ID ' . $sidangId . ' dijadwalkan');
    flash('sukses', 'Penguji dan jadwal sidang berhasil ditetapkan.');
    redirect('admin/jadwal.php');
}

$st = $pdo->query("SELECT s.*, j.judul, m.nama AS nama_mahasiswa,
                          p.nama AS nama_penguji, r.nama AS nama_ruang
                   FROM sidang s JOIN judul_skripsi j ON j.id = s.judul_id
                   JOIN users m ON m.id = j.mahasiswa_id
                   LEFT JOIN users p ON p.id = s.penguji_id
                   LEFT JOIN master_ruang r ON r.id = s.ruang_id
                   WHERE s.status IN ('berkas_lengkap', 'terjadwal', 'dinilai')
                   ORDER BY s.jadwal IS NULL, s.jadwal, s.created_at DESC");
$daftar = $st->fetchAll();

$dosen = $pdo->query("SELECT u.id, u.nama FROM users u
                      JOIN user_roles r ON r.user_id = u.id
                      WHERE r.role = 'dosen' AND u.aktif = 1
                      ORDER BY u.nama")->fetchAll();
$ruang = $pdo->query("SELECT id, nama FROM master_ruang WHERE aktif = 1 ORDER BY nama")->fetchAll();

$judulHalaman = 'Penguji & Jadwal';
require __DIR__ . '/../includes/header.php';
?>

<section class="kartu">
  <h2 class="judul-kartu">Penjadwalan sidang</h2>
  <?php if ($daftar): ?>
    <div class="tabel-bungkus">
      <table class="tabel">
        <thead><tr><th>Mahasiswa</th><th>Jenis / Judul</th><th>Penguji</th><th>Ruang & Waktu</th><th>Status / Aksi</th></tr></thead>
        <tbody>
          <?php foreach ($daftar as $s): ?>
            <tr>
              <td><?= e($s['nama_mahasiswa']) ?></td>
              <td><small class="redup"><?= ucfirst($s['jenis']) ?></small><br><strong><?= e($s['judul']) ?></strong></td>
              <td><?= e($s['nama_penguji'] ?: 'Belum ditetapkan') ?></td>
              <td><?= e($s['nama_ruang'] ?: 'Belum ditetapkan') ?><br><small><?= e($s['jadwal'] ? tanggal_id($s['jadwal'], true) : 'Belum dijadwalkan') ?></small></td>
              <td>
                <?php if ($s['status'] === 'berkas_lengkap'): ?>
                  <details class="aksi-verifikasi">
                    <summary class="btn btn-garis">Atur jadwal</summary>
                    <div class="panel-verifikasi">
                      <form method="post">
                        <?= csrf_field() ?>
                        <input type="hidden" name="sidang_id" value="<?= (int) $s['id'] ?>">
                        <label for="penguji-<?= (int) $s['id'] ?>">Penguji</label>
                        <select id="penguji-<?= (int) $s['id'] ?>" name="penguji_id" required>
                          <option value="">Pilih dosen</option>
                          <?php foreach ($dosen as $d): ?><option value="<?= (int) $d['id'] ?>"><?= e($d['nama']) ?></option><?php endforeach; ?>
                        </select>
                        <label for="ruang-<?= (int) $s['id'] ?>">Ruang</label>
                        <select id="ruang-<?= (int) $s['id'] ?>" name="ruang_id" required>
                          <option value="">Pilih ruang</option>
                          <?php foreach ($ruang as $r): ?><option value="<?= (int) $r['id'] ?>"><?= e($r['nama']) ?></option><?php endforeach; ?>
                        </select>
                        <label for="jadwal-<?= (int) $s['id'] ?>">Waktu</label>
                        <input type="datetime-local" id="jadwal-<?= (int) $s['id'] ?>" name="jadwal" required>
                        <button type="submit" class="btn btn-utama">Simpan jadwal</button>
                      </form>
                    </div>
                  </details>
                <?php else: ?>
                  <?= lencana(str_replace('_', ' ', $s['status']), $s['status'] === 'dinilai' ? 'hijau' : 'biru') ?>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php else: ?>
    <p class="redup">Belum ada sidang yang siap dijadwalkan.</p>
  <?php endif; ?>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
