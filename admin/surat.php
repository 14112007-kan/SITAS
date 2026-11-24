<?php
require_once __DIR__ . '/../includes/auth.php';
wajib_peran('admin');

$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $suratId = (int) ($_POST['surat_id'] ?? 0);
    $aksi = (string) ($_POST['aksi'] ?? '');

    $st = $pdo->prepare('SELECT s.*, u.nama AS nama_mahasiswa
                         FROM surat s JOIN users u ON u.id = s.pemohon_id
                         WHERE s.id = ?');
    $st->execute([$suratId]);
    $surat = $st->fetch();

    if (!$surat || $surat['status'] !== 'menunggu') {
        flash('galat', 'Pengajuan surat tidak ditemukan atau sudah diproses.');
        redirect('admin/surat.php');
    }

    if ($aksi === 'ditolak') {
        $st = $pdo->prepare("UPDATE surat SET status = 'ditolak' WHERE id = ?");
        $st->execute([$suratId]);
        kirim_notifikasi((int) $surat['pemohon_id'], 'Pengajuan surat Anda ditolak oleh Admin.', 'mahasiswa/surat.php');
        audit_log(pengguna_login()['id'], 'TOLAK_SURAT', 'Menolak surat ID ' . $suratId);
        flash('sukses', 'Pengajuan surat ditolak.');
    } elseif ($aksi === 'terbit') {
        $tahun = (int) date('Y');
        $pdo->beginTransaction();
        try {
            $st = $pdo->prepare("SELECT COALESCE(MAX(nomor_urut), 0)
                                 FROM surat WHERE jenis = ? AND tahun = ? AND status = 'terbit'");
            $st->execute([$surat['jenis'], $tahun]);
            $nomorUrut = (int) $st->fetchColumn() + 1;

            $kodeJenis = [
                'sk_pembimbing' => 'SK-PB',
                'izin_penelitian' => 'IZN-PNL',
                'keterangan_aktif' => 'SK-AKT',
                'undangan_sidang' => 'UND-SDG',
            ];
            $nomor = $nomorUrut . '/' . ($kodeJenis[$surat['jenis']] ?? 'SURAT') . '/SITAS/' . $tahun;
            $qrToken = bin2hex(random_bytes(24));

            $st = $pdo->prepare("UPDATE surat
                                 SET status = 'terbit', nomor_urut = ?, tahun = ?, nomor = ?,
                                     qr_token = ?, diterbitkan_at = NOW()
                                 WHERE id = ?");
            $st->execute([$nomorUrut, $tahun, $nomor, $qrToken, $suratId]);

            kirim_notifikasi((int) $surat['pemohon_id'], 'Surat Anda telah diterbitkan dengan nomor ' . $nomor . '.', 'mahasiswa/surat.php');
            audit_log(pengguna_login()['id'], 'TERBIT_SURAT', 'Menerbitkan surat ID ' . $suratId . ' nomor ' . $nomor);
            $pdo->commit();
            flash('sukses', 'Surat diterbitkan dengan nomor ' . $nomor . '.');
        } catch (Throwable $e) {
            $pdo->rollBack();
            flash('galat', 'Surat gagal diterbitkan.');
        }
    } else {
        flash('galat', 'Tindakan surat tidak dikenali.');
    }

    redirect('admin/surat.php');
}

$statusFilter = (string) ($_GET['status'] ?? 'menunggu');
$statusValid = ['menunggu', 'terbit', 'ditolak', 'semua'];
if (!in_array($statusFilter, $statusValid, true)) {
    $statusFilter = 'menunggu';
}

$sql = 'SELECT s.*, u.nama AS nama_mahasiswa, u.nim_nidn, j.judul
        FROM surat s JOIN users u ON u.id = s.pemohon_id
        LEFT JOIN judul_skripsi j ON j.id = s.judul_id';
$params = [];
if ($statusFilter !== 'semua') {
    $sql .= ' WHERE s.status = ?';
    $params[] = $statusFilter;
}
$sql .= ' ORDER BY s.created_at DESC';
$st = $pdo->prepare($sql);
$st->execute($params);
$surat = $st->fetchAll();

$namaJenis = [
    'sk_pembimbing' => 'Surat Keterangan Pembimbing',
    'izin_penelitian' => 'Surat Izin Penelitian',
    'keterangan_aktif' => 'Surat Keterangan Aktif',
    'undangan_sidang' => 'Surat Undangan Sidang',
];

$judulHalaman = 'Antrean Surat';
require __DIR__ . '/../includes/header.php';
?>

<section class="kartu">
  <div class="toolbar">
    <h2 class="judul-kartu">Antrean pengajuan surat</h2>
    <form method="get" class="filter-inline">
      <label for="status-filter">Status</label>
      <select id="status-filter" name="status" onchange="this.form.submit()">
        <option value="menunggu" <?= $statusFilter === 'menunggu' ? 'selected' : '' ?>>Menunggu</option>
        <option value="terbit" <?= $statusFilter === 'terbit' ? 'selected' : '' ?>>Terbit</option>
        <option value="ditolak" <?= $statusFilter === 'ditolak' ? 'selected' : '' ?>>Ditolak</option>
        <option value="semua" <?= $statusFilter === 'semua' ? 'selected' : '' ?>>Semua</option>
      </select>
    </form>
  </div>

  <?php if ($surat): ?>
    <div class="tabel-bungkus">
      <table class="tabel">
        <thead><tr><th>Pemohon</th><th>Surat</th><th>Keterangan</th><th>Status</th><th>Aksi</th></tr></thead>
        <tbody>
          <?php foreach ($surat as $s): ?>
            <tr>
              <td><strong><?= e($s['nama_mahasiswa']) ?></strong><br><small class="redup"><?= e($s['nim_nidn'] ?: '-') ?></small></td>
              <td><?= e($namaJenis[$s['jenis']] ?? $s['jenis']) ?></td>
              <td><?= e($s['judul'] ?: '-') ?><br><small><?= e(tanggal_id($s['created_at'], true)) ?></small></td>
              <td><?= lencana(str_replace('_', ' ', $s['status']), $s['status'] === 'terbit' ? 'hijau' : ($s['status'] === 'ditolak' ? 'merah' : 'kuning')) ?></td>
              <td>
                <?php if ($s['status'] === 'menunggu'): ?>
                  <form method="post" class="aksi-baris">
                    <?= csrf_field() ?>
                    <input type="hidden" name="surat_id" value="<?= (int) $s['id'] ?>">
                    <button type="submit" name="aksi" value="terbit" class="btn btn-utama">Terbitkan</button>
                    <button type="submit" name="aksi" value="ditolak" class="btn btn-bahaya">Tolak</button>
                  </form>
                <?php else: ?>
                  <strong><?= e($s['nomor'] ?: '-') ?></strong>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php else: ?>
    <p class="redup">Tidak ada pengajuan surat pada status ini.</p>
  <?php endif; ?>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
