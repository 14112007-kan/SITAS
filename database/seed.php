<?php
// ============================================================
// SEEDER: mengisi database dengan data contoh untuk demo.
// Jalankan SEKALI setelah import schema.sql:
//   - lewat browser : http://localhost/sitas/database/seed.php
//   - atau terminal : php database/seed.php
// Hanya boleh dijalankan dari komputer sendiri (localhost).
// Setelah demo siap, sebaiknya hapus file ini.
// ============================================================
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

$alamat = $_SERVER['REMOTE_ADDR'] ?? 'cli';
if (!in_array($alamat, ['127.0.0.1', '::1', 'cli'], true)) {
    http_response_code(403);
    exit('Seeder hanya boleh dijalankan dari localhost.');
}
header('Content-Type: text/plain; charset=utf-8');

$pdo = db();

if ((int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn() > 0) {
    exit("Database sudah berisi data. Import ulang schema.sql kalau ingin mengosongkannya.\n");
}

const PASSWORD_DEMO = 'sitas123';
$hash = password_hash(PASSWORD_DEMO, PASSWORD_DEFAULT);

$pdo->beginTransaction();
try {
    // ---------- Data master ----------
    foreach (['Ruang Sidang 1', 'Ruang Sidang 2', 'Zoom'] as $r) {
        $pdo->prepare('INSERT INTO master_ruang (nama) VALUES (?)')->execute([$r]);
    }
    $pdo->prepare('INSERT INTO master_periode (nama, tgl_mulai, tgl_selesai) VALUES (?, ?, ?)')
        ->execute(['Sidang Proposal Ganjil 2026/2027', '2026-12-01', '2026-12-20']);
    foreach ([
        ['min_bimbingan', '8', 'Minimal bimbingan tervalidasi untuk maju sidang'],
        ['kuota_pembimbing', '10', 'Maksimal mahasiswa per pembimbing'],
        ['ambang_kemiripan', '60', 'Ambang kemiripan judul (persen)'],
        ['batas_studi_semester', '2', 'Batas masa studi skripsi (semester)'],
    ] as $a) {
        $pdo->prepare('INSERT INTO master_aturan (kunci, nilai, keterangan) VALUES (?, ?, ?)')->execute($a);
    }
    foreach (['Template Pengajuan Judul', 'Template Logbook', 'Template Formulir Sidang', 'Template Revisi'] as $t) {
        $pdo->prepare('INSERT INTO master_template (nama) VALUES (?)')->execute([$t]);
    }

    // ---------- Pengguna ----------
    $buatUser = function (string $nama, string $email, ?string $id, array $peran) use ($pdo, $hash): int {
        $pdo->prepare('INSERT INTO users (nama, email, password_hash, nim_nidn) VALUES (?, ?, ?, ?)')
            ->execute([$nama, $email, $hash, $id]);
        $uid = (int) $pdo->lastInsertId();
        foreach ($peran as $p) {
            $pdo->prepare('INSERT INTO user_roles (user_id, role) VALUES (?, ?)')->execute([$uid, $p]);
        }
        return $uid;
    };

    $admin   = $buatUser('Admin Program Studi',      'admin@sitas.test',   null,          ['admin']);
    $dosen1  = $buatUser('Dr. Siti Rahma, M.Kom.',   'siti@sitas.test',    '198501012010', ['dosen']);
    $dosen2  = $buatUser('Budi Santoso, M.Si.',      'budi@sitas.test',    '198703152012', ['dosen']);
    $kaprodi = $buatUser('Dr. Andi Wijaya, M.T.',    'kaprodi@sitas.test', '197905202005', ['dosen', 'admin']);
    $aisyah  = $buatUser('Aisyah Putri',             'aisyah@sitas.test',  '257411001',   ['mahasiswa']);
    $rizky   = $buatUser('Rizky Pratama',            'rizky@sitas.test',   '257411002',   ['mahasiswa']);
    $dewi    = $buatUser('Dewi Lestari',             'dewi@sitas.test',    '257411003',   ['mahasiswa']);
    $fajar   = $buatUser('Fajar Nugroho',            'fajar@sitas.test',   '257411004',   ['mahasiswa']);

    // ---------- Judul skripsi ----------
    $buatJudul = function (int $mhs, string $judul, string $bidang, float $mirip, string $status, ?int $pembimbing) use ($pdo): int {
        $pdo->prepare('INSERT INTO judul_skripsi (mahasiswa_id, judul, abstrak, bidang_minat, kemiripan, status, pembimbing_id, diputuskan_at)
                       VALUES (?, ?, ?, ?, ?, ?, ?, ?)')
            ->execute([$mhs, $judul, 'Abstrak contoh untuk keperluan demo SITAS.', $bidang, $mirip, $status, $pembimbing,
                       $status === 'menunggu' ? null : date('Y-m-d H:i:s')]);
        return (int) $pdo->lastInsertId();
    };

    $jAisyah = $buatJudul($aisyah, 'Prediksi Harga Beras Menggunakan Long Short-Term Memory', 'Machine Learning', 18.5, 'disetujui', $dosen1);
    $buatJudul($rizky, 'Analisis Sentimen Ulasan Aplikasi Mobile Banking dengan Naive Bayes', 'Text Mining', 22.0, 'menunggu', null);
    $jFajar  = $buatJudul($fajar, 'Segmentasi Pelanggan UMKM Menggunakan K-Means dan RFM', 'Data Mining', 15.0, 'disetujui', $dosen2);

    // ---------- Logbook ----------
    $buatLog = function (int $judulId, int $hariLalu, string $topik, string $status) use ($pdo): void {
        $pdo->prepare('INSERT INTO logbook (judul_id, tanggal, topik, uraian, status, validated_at) VALUES (?, ?, ?, ?, ?, ?)')
            ->execute([$judulId, date('Y-m-d', strtotime("-$hariLalu days")), $topik, 'Uraian bimbingan contoh.', $status,
                       $status === 'tervalidasi' ? date('Y-m-d H:i:s') : null]);
    };

    // Aisyah: 3 tervalidasi + 1 menunggu dosen
    $buatLog($jAisyah, 40, 'Diskusi latar belakang masalah', 'tervalidasi');
    $buatLog($jAisyah, 30, 'Perbaikan rumusan masalah', 'tervalidasi');
    $buatLog($jAisyah, 20, 'Tinjauan pustaka Bab 2', 'tervalidasi');
    $buatLog($jAisyah, 2,  'Metode penelitian Bab 3', 'menunggu_dosen');

    // Fajar: 8 tervalidasi (siap maju sidang, untuk demo Tahap 4)
    $topikFajar = ['Pengenalan topik', 'Rumusan masalah', 'Tinjauan pustaka', 'Pengumpulan data',
                   'Preprocessing data', 'Penentuan jumlah klaster', 'Evaluasi silhouette', 'Draf Bab 1 sampai 3'];
    foreach ($topikFajar as $i => $t) {
        $buatLog($jFajar, 70 - $i * 7, $t, 'tervalidasi');
    }

    // ---------- Notifikasi dan audit contoh ----------
    $notif = function (int $uid, string $pesan) use ($pdo): void {
        $pdo->prepare('INSERT INTO notifikasi (user_id, pesan) VALUES (?, ?)')->execute([$uid, $pesan]);
    };
    $notif($aisyah, 'Judul Anda disetujui dan pembimbing telah ditetapkan.');
    $notif($aisyah, 'Bimbingan "Tinjauan pustaka Bab 2" telah divalidasi dosen.');
    $notif($rizky,  'Pengajuan judul Anda sedang diverifikasi Admin.');
    $notif($dosen1, 'Aisyah Putri mengirim catatan bimbingan baru.');
    $notif($admin,  'Ada 1 pengajuan judul baru yang menunggu verifikasi.');

    $pdo->prepare('INSERT INTO audit_log (user_id, aksi, keterangan) VALUES (?, ?, ?)')
        ->execute([$admin, 'SEED', 'Data contoh demo dimuat']);

    $pdo->commit();
} catch (Throwable $e) {
    $pdo->rollBack();
    http_response_code(500);
    exit('Seeder gagal: ' . $e->getMessage() . "\n");
}

echo "Seeder selesai.\n\n";
echo "Kata sandi semua akun: " . PASSWORD_DEMO . "\n";
echo "- admin@sitas.test    (Admin)\n";
echo "- siti@sitas.test     (Dosen)\n";
echo "- budi@sitas.test     (Dosen)\n";
echo "- kaprodi@sitas.test  (Dosen + Admin)\n";
echo "- aisyah@sitas.test   (Mahasiswa)\n";
echo "- rizky@sitas.test    (Mahasiswa)\n";
echo "- dewi@sitas.test     (Mahasiswa)\n";
echo "- fajar@sitas.test    (Mahasiswa)\n";
