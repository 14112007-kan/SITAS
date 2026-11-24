<?php
// Daftar menu sidebar per peran, mengikuti flowchart peran SITAS.
// 'tahap' = tahap pengembangan tempat halaman itu dibuat.
// Selama tahap > TAHAP_SELESAI, menu tampil nonaktif (abu-abu).

function menu_untuk(string $peran): array
{
    $menu = [
        'mahasiswa' => [
            ['Dashboard',              'mahasiswa/dashboard.php',   1],
            ['Pengajuan Judul',        'mahasiswa/judul.php',       2],
            ['Logbook Bimbingan',      'mahasiswa/logbook.php',     3],
            ['Dokumen Bab',            'mahasiswa/bab.php',         3],
            ['Sidang',                 'mahasiswa/sidang.php',      4],
            ['Revisi & Naskah Final',  'mahasiswa/revisi.php',      6],
            ['Surat & Template',       'mahasiswa/surat.php',       7],
        ],
        'dosen' => [
            ['Dashboard',              'dosen/dashboard.php',       1],
            ['Mahasiswa Bimbingan',    'dosen/bimbingan.php',       3],
            ['Validasi Logbook',       'dosen/logbook.php',         3],
            ['Review Bab',             'dosen/bab.php',             3],
            ['Maju Sidang',            'dosen/maju-sidang.php',     4],
            ['Menguji & Penilaian',    'dosen/menguji.php',         5],
            ['Revisi Pasca-Sidang',    'dosen/revisi.php',          6],
        ],
        'admin' => [
            ['Dashboard',              'admin/dashboard.php',       1],
            ['Verifikasi Judul',       'admin/judul.php',           2],
            ['Berkas & Bebas Pustaka', 'admin/berkas.php',          4],
            ['Penguji & Jadwal',       'admin/jadwal.php',          5],
            ['Antrean Surat',          'admin/surat.php',           7],
            ['Naskah Final',           'admin/naskah.php',          6],
            ['Pengguna & Peran',       'admin/pengguna.php',        8],
            ['Data Master',            'admin/master.php',          8],
            ['Rekap & Perhatian',      'admin/rekap.php',           9],
            ['Hak Akses',              'admin/hak-akses.php',       9],
            ['Audit Log',              'admin/audit-log.php',       8],
        ],
    ];
    return $menu[$peran] ?? [];
}
