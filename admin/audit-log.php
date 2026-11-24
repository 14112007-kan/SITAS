<?php
require_once __DIR__ . '/../includes/auth.php';
wajib_peran('admin');

$pdo = db();
$aksiFilter = trim((string) ($_GET['aksi'] ?? ''));

$sql = 'SELECT a.*, u.nama
        FROM audit_log a LEFT JOIN users u ON u.id = a.user_id';
$params = [];
if ($aksiFilter !== '') {
    $sql .= ' WHERE a.aksi LIKE ?';
    $params[] = '%' . $aksiFilter . '%';
}
$sql .= ' ORDER BY a.id DESC LIMIT 300';
$st = $pdo->prepare($sql);
$st->execute($params);
$log = $st->fetchAll();

$judulHalaman = 'Audit Log';
require __DIR__ . '/../includes/header.php';
?>

<section class="kartu">
  <div class="toolbar"><h2 class="judul-kartu">Riwayat aktivitas</h2><form method="get" class="filter-inline"><label for="aksi">Cari aksi</label><input id="aksi" type="text" name="aksi" value="<?= e($aksiFilter) ?>"><button class="btn btn-garis">Cari</button></form></div>
  <?php if ($log): ?>
    <div class="tabel-bungkus"><table class="tabel"><thead><tr><th>Waktu</th><th>Pelaku</th><th>Aksi</th><th>Keterangan</th></tr></thead><tbody>
      <?php foreach ($log as $a): ?><tr><td><?= e(tanggal_id($a['waktu'], true)) ?></td><td><?= e($a['nama'] ?: 'Sistem') ?></td><td><?= lencana($a['aksi'], 'biru') ?></td><td><?= e($a['keterangan'] ?: '-') ?></td></tr><?php endforeach; ?>
    </tbody></table></div>
  <?php else: ?><p class="redup">Belum ada audit log yang cocok.</p><?php endif; ?>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
