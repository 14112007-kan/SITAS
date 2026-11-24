<?php
require_once __DIR__ . '/../includes/auth.php';
wajib_peran('mahasiswa');

$uid = pengguna_login()['id'];
$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $jenis = (string) ($_POST['jenis'] ?? '');
    $jenisValid = ['sk_pembimbing', 'izin_penelitian', 'keterangan_aktif', 'undangan_sidang'];
    if (!in_array($jenis, $jenisValid, true)) {
        flash('galat', 'Jenis surat tidak dikenali.');
        redirect('mahasiswa/surat.php');
    }

    $judulId = null;
    $st = $pdo->prepare("SELECT id FROM judul_skripsi
                         WHERE mahasiswa_id = ? AND status = 'disetujui'
                         ORDER BY id DESC LIMIT 1");
    $st->execute([$uid]);
    $judul = $st->fetch();
    if ($judul) {
        $judulId = (int) $judul['id'];
    }

    $st = $pdo->prepare("SELECT id FROM surat
                         WHERE pemohon_id = ? AND jenis = ? AND status = 'menunggu'
                         LIMIT 1");
    $st->execute([$uid, $jenis]);
    if ($st->fetch()) {
        flash('info', 'Anda masih memiliki pengajuan surat jenis ini yang menunggu proses.');
        redirect('mahasiswa/surat.php');
    }

    $st = $pdo->prepare('INSERT INTO surat (jenis, judul_id, pemohon_id, status)
                         VALUES (?, ?, ?, "menunggu")');
    $st->execute([$jenis, $judulId, $uid]);

    $adminIds = $pdo->query("SELECT u.id FROM users u JOIN user_roles r ON r.user_id = u.id
                             WHERE r.role = 'admin' AND u.aktif = 1")->fetchAll();
    foreach ($adminIds as $admin) {
        kirim_notifikasi((int) $admin['id'], 'Ada pengajuan surat baru dari mahasiswa.', 'admin/surat.php');
    }
    audit_log($uid, 'AJUKAN_SURAT', 'Mengajukan surat jenis ' . $jenis);
    flash('sukses', 'Pengajuan surat berhasil dikirim.');
    redirect('mahasiswa/surat.php');
}

$st = $pdo->prepare("SELECT s.*, j.judul
                     FROM surat s LEFT JOIN judul_skripsi j ON j.id = s.judul_id
                     WHERE s.pemohon_id = ? ORDER BY s.created_at DESC");
$st->execute([$uid]);
$surat = $st->fetchAll();

$namaJenis = [
    'sk_pembimbing' => 'Surat Keterangan Pembimbing',
    'izin_penelitian' => 'Surat Izin Penelitian',
    'keterangan_aktif' => 'Surat Keterangan Aktif',
    'undangan_sidang' => 'Surat Undangan Sidang',
];

$judulHalaman = 'Surat & Template';
require __DIR__ . '/../includes/header.php';
?>

<div class="dua-kolom">
  <section class="kartu">
    <h2 class="judul-kartu">Ajukan surat</h2>
    <form method="post">
      <?= csrf_field() ?>
      <label for="jenis-surat">Jenis surat</label>
      <select id="jenis-surat" name="jenis" required>
        <option value="">Pilih jenis surat</option>
        <?php foreach ($namaJenis as $jenis => $nama): ?>
          <option value="<?= e($jenis) ?>"><?= e($nama) ?></option>
        <?php endforeach; ?>
      </select>
      <button type="submit" class="btn btn-utama btn-penuh">Kirim pengajuan</button>
    </form>
  </section>

  <section class="kartu">
    <h2 class="judul-kartu">Riwayat surat</h2>
    <?php if ($surat): ?>
      <div class="tabel-bungkus">
        <table class="tabel">
          <thead><tr><th>Jenis</th><th>Status</th><th>Nomor</th><th>Tanggal</th></tr></thead>
          <tbody>
            <?php foreach ($surat as $s): ?>
              <tr>
                <td><?= e($namaJenis[$s['jenis']] ?? $s['jenis']) ?></td>
                <td><?= lencana(str_replace('_', ' ', $s['status']), $s['status'] === 'terbit' ? 'hijau' : ($s['status'] === 'ditolak' ? 'merah' : 'kuning')) ?></td>
                <td><?= e($s['nomor'] ?: '-') ?></td>
                <td><?= e(tanggal_id($s['created_at'], true)) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php else: ?>
      <p class="redup">Belum ada pengajuan surat.</p>
    <?php endif; ?>
  </section>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
