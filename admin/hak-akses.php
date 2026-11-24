<?php
require_once __DIR__ . '/../includes/auth.php';
wajib_peran('admin');

$hak = [
    'Mahasiswa' => [
        'Pengajuan judul' => 'Ajukan dan pantau judul',
        'Bimbingan' => 'Logbook dan dokumen Bab',
        'Sidang' => 'Daftar sidang dan lihat jadwal',
        'Revisi/Naskah' => 'Unggah revisi dan naskah final',
        'Surat' => 'Ajukan dan pantau surat',
    ],
    'Dosen' => [
        'Mahasiswa bimbingan' => 'Lihat daftar mahasiswa',
        'Logbook/Bab' => 'Validasi atau minta revisi',
        'Maju sidang' => 'Setujui mahasiswa maju sidang',
        'Pengujian' => 'Menilai dan memberi revisi',
    ],
    'Admin' => [
        'Verifikasi' => 'Judul, berkas, dan naskah',
        'Penjadwalan' => 'Atur penguji, ruang, dan jadwal',
        'Surat' => 'Terbitkan atau tolak surat',
        'Pengelolaan' => 'Pengguna, peran, master, dan audit',
    ],
];

$judulHalaman = 'Hak Akses';
require __DIR__ . '/../includes/header.php';
?>

<section class="grid-stat">
  <?php foreach ($hak as $peran => $daftar): ?>
    <section class="kartu"><h2 class="judul-kartu"><?= e($peran) ?></h2><dl class="rincian">
      <?php foreach ($daftar as $menu => $fungsi): ?><dt><?= e($menu) ?></dt><dd><?= e($fungsi) ?></dd><?php endforeach; ?>
    </dl></section>
  <?php endforeach; ?>
</section>

<section class="kartu"><p>Hak akses halaman tetap dijaga oleh <code>wajib_peran()</code>. Pengguna tidak dapat membuka area peran lain kecuali peran tersebut aktif atau dimiliki di sesi login.</p></section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
