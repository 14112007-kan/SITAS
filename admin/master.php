<?php
require_once __DIR__ . '/../includes/auth.php';
wajib_peran('admin');

$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $aksi = (string) ($_POST['aksi'] ?? '');

    if ($aksi === 'ruang') {
        $nama = trim((string) ($_POST['nama'] ?? ''));
        if ($nama !== '') {
            $pdo->prepare('INSERT INTO master_ruang (nama) VALUES (?)')->execute([$nama]);
            audit_log(pengguna_login()['id'], 'TAMBAH_MASTER_RUANG', 'Menambah ruang ' . $nama);
            flash('sukses', 'Ruang berhasil ditambahkan.');
        }
    } elseif ($aksi === 'periode') {
        $nama = trim((string) ($_POST['nama'] ?? ''));
        $mulai = (string) ($_POST['tgl_mulai'] ?? '');
        $selesai = (string) ($_POST['tgl_selesai'] ?? '');
        if ($nama !== '' && $mulai !== '' && $selesai !== '' && $selesai >= $mulai) {
            $pdo->prepare('INSERT INTO master_periode (nama, tgl_mulai, tgl_selesai) VALUES (?, ?, ?)')->execute([$nama, $mulai, $selesai]);
            audit_log(pengguna_login()['id'], 'TAMBAH_MASTER_PERIODE', 'Menambah periode ' . $nama);
            flash('sukses', 'Periode berhasil ditambahkan.');
        } else {
            flash('galat', 'Tanggal periode tidak valid.');
        }
    } elseif ($aksi === 'aturan') {
        $kunci = trim((string) ($_POST['kunci'] ?? ''));
        $nilai = trim((string) ($_POST['nilai'] ?? ''));
        $keterangan = trim((string) ($_POST['keterangan'] ?? ''));
        if ($kunci !== '' && $nilai !== '') {
            $pdo->prepare('INSERT INTO master_aturan (kunci, nilai, keterangan)
                           VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE nilai = VALUES(nilai), keterangan = VALUES(keterangan)')
                ->execute([$kunci, $nilai, $keterangan]);
            audit_log(pengguna_login()['id'], 'UBAH_MASTER_ATURAN', 'Mengubah aturan ' . $kunci);
            flash('sukses', 'Aturan berhasil disimpan.');
        }
    } elseif ($aksi === 'template') {
        $nama = trim((string) ($_POST['nama'] ?? ''));
        if ($nama !== '') {
            $pdo->prepare('INSERT INTO master_template (nama) VALUES (?)')->execute([$nama]);
            audit_log(pengguna_login()['id'], 'TAMBAH_MASTER_TEMPLATE', 'Menambah template ' . $nama);
            flash('sukses', 'Template berhasil ditambahkan.');
        }
    }
    redirect('admin/master.php');
}

$ruang = $pdo->query('SELECT * FROM master_ruang ORDER BY nama')->fetchAll();
$periode = $pdo->query('SELECT * FROM master_periode ORDER BY tgl_mulai DESC')->fetchAll();
$aturan = $pdo->query('SELECT * FROM master_aturan ORDER BY kunci')->fetchAll();
$template = $pdo->query('SELECT * FROM master_template ORDER BY nama')->fetchAll();

$judulHalaman = 'Data Master';
require __DIR__ . '/../includes/header.php';
?>

<section class="dua-kolom">
  <section class="kartu">
    <h2 class="judul-kartu">Ruang sidang</h2>
    <form method="post" class="aksi-baris"><?= csrf_field() ?><input type="hidden" name="aksi" value="ruang"><input type="text" name="nama" required placeholder="Nama ruang"><button class="btn btn-utama">Tambah</button></form>
    <div class="tabel-bungkus"><table class="tabel"><tbody><?php foreach ($ruang as $r): ?><tr><td><?= e($r['nama']) ?></td><td><?= lencana($r['aktif'] ? 'Aktif' : 'Nonaktif', $r['aktif'] ? 'hijau' : 'abu') ?></td></tr><?php endforeach; ?></tbody></table></div>
  </section>

  <section class="kartu">
    <h2 class="judul-kartu">Periode sidang</h2>
    <form method="post"><?= csrf_field() ?><input type="hidden" name="aksi" value="periode"><label>Nama periode</label><input type="text" name="nama" required><label>Tanggal mulai</label><input type="date" name="tgl_mulai" required><label>Tanggal selesai</label><input type="date" name="tgl_selesai" required><button class="btn btn-utama btn-penuh">Tambah periode</button></form>
  </section>

  <section class="kartu">
    <h2 class="judul-kartu">Aturan bisnis</h2>
    <form method="post"><?= csrf_field() ?><input type="hidden" name="aksi" value="aturan"><label>Kunci</label><input type="text" name="kunci" required placeholder="contoh: ambang_kemiripan"><label>Nilai</label><input type="text" name="nilai" required><label>Keterangan</label><input type="text" name="keterangan"><button class="btn btn-utama btn-penuh">Simpan aturan</button></form>
  </section>

  <section class="kartu">
    <h2 class="judul-kartu">Template</h2>
    <form method="post" class="aksi-baris"><?= csrf_field() ?><input type="hidden" name="aksi" value="template"><input type="text" name="nama" required placeholder="Nama template"><button class="btn btn-utama">Tambah</button></form>
    <div class="tabel-bungkus"><table class="tabel"><tbody><?php foreach ($template as $t): ?><tr><td><?= e($t['nama']) ?></td></tr><?php endforeach; ?></tbody></table></div>
  </section>
</section>

<?php if ($periode): ?><section class="kartu"><h2 class="judul-kartu">Daftar periode</h2><div class="tabel-bungkus"><table class="tabel"><thead><tr><th>Nama</th><th>Mulai</th><th>Selesai</th></tr></thead><tbody><?php foreach ($periode as $p): ?><tr><td><?= e($p['nama']) ?></td><td><?= e(tanggal_id($p['tgl_mulai'])) ?></td><td><?= e(tanggal_id($p['tgl_selesai'])) ?></td></tr><?php endforeach; ?></tbody></table></div></section><?php endif; ?>

<section class="kartu"><h2 class="judul-kartu">Daftar aturan</h2><div class="tabel-bungkus"><table class="tabel"><thead><tr><th>Kunci</th><th>Nilai</th><th>Keterangan</th></tr></thead><tbody><?php foreach ($aturan as $a): ?><tr><td><code><?= e($a['kunci']) ?></code></td><td><?= e($a['nilai']) ?></td><td><?= e($a['keterangan'] ?: '-') ?></td></tr><?php endforeach; ?></tbody></table></div></section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
