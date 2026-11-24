<?php
require_once __DIR__ . '/../includes/auth.php';
wajib_peran('admin');

$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $sidangId = (int) ($_POST['sidang_id'] ?? 0);

    $st = $pdo->prepare("SELECT s.*, j.mahasiswa_id, j.judul
                         FROM sidang s JOIN judul_skripsi j ON j.id = s.judul_id
                         WHERE s.id = ?");
    $st->execute([$sidangId]);
    $sidang = $st->fetch();

    if (!$sidang) {
        flash('galat', 'Pendaftaran sidang tidak ditemukan.');
        redirect('admin/berkas.php');
    }

    $checklist = [
        'chk_transkrip' => isset($_POST['chk_transkrip']),
        'chk_surat_penelitian' => isset($_POST['chk_surat_penelitian']),
        'chk_naskah' => isset($_POST['chk_naskah']),
        'chk_formulir' => isset($_POST['chk_formulir']),
        'chk_pinjaman' => isset($_POST['chk_pinjaman']),
    ];

    $lengkap = count(array_filter($checklist)) === count($checklist)
        && (int) $sidang['persetujuan_dosen'] === 1;
    if (!$sidang['persetujuan_dosen'] && count(array_filter($checklist)) === count($checklist)) {
        flash('galat', 'Seluruh berkas tercentang, tetapi dosen pembimbing belum menyetujui maju sidang.');
        redirect('admin/berkas.php');
    }

    $st = $pdo->prepare('UPDATE sidang SET chk_transkrip = ?, chk_surat_penelitian = ?,
                                chk_naskah = ?, chk_formulir = ?, chk_pinjaman = ?,
                                status = ?, alasan_kembali = NULL
                         WHERE id = ?');
    $st->execute([
        (int) $checklist['chk_transkrip'],
        (int) $checklist['chk_surat_penelitian'],
        (int) $checklist['chk_naskah'],
        (int) $checklist['chk_formulir'],
        (int) $checklist['chk_pinjaman'],
        $lengkap ? 'berkas_lengkap' : 'didaftarkan',
        $sidangId,
    ]);

    kirim_notifikasi((int) $sidang['mahasiswa_id'],
        $lengkap ? 'Seluruh berkas sidang Anda telah dinyatakan lengkap.' : 'Berkas sidang Anda masih menunggu pemeriksaan administrasi.',
        'mahasiswa/sidang.php');
    audit_log(pengguna_login()['id'], 'VERIFIKASI_BERKAS', 'Sidang ID ' . $sidangId . ' berstatus ' . ($lengkap ? 'berkas_lengkap' : 'didaftarkan'));
    flash('sukses', $lengkap ? 'Berkas dinyatakan lengkap.' : 'Checklist berkas berhasil diperbarui.');
    redirect('admin/berkas.php');
}

$st = $pdo->query("SELECT s.*, j.judul, u.nama AS nama_mahasiswa, u.nim_nidn
                   FROM sidang s JOIN judul_skripsi j ON j.id = s.judul_id
                   JOIN users u ON u.id = j.mahasiswa_id
                   WHERE s.jenis = 'proposal'
                   ORDER BY s.status = 'didaftarkan' DESC, s.created_at DESC");
$daftar = $st->fetchAll();

$judulHalaman = 'Berkas & Bebas Pustaka';
require __DIR__ . '/../includes/header.php';
?>

<section class="kartu">
  <h2 class="judul-kartu">Pemeriksaan berkas sidang proposal</h2>
  <?php if ($daftar): ?>
    <div class="tabel-bungkus">
      <table class="tabel">
        <thead><tr><th>Mahasiswa</th><th>Judul</th><th>Persetujuan dosen</th><th>Checklist</th><th>Status</th></tr></thead>
        <tbody>
          <?php foreach ($daftar as $s): ?>
            <tr>
              <td><strong><?= e($s['nama_mahasiswa']) ?></strong><br><small class="redup"><?= e($s['nim_nidn'] ?: '-') ?></small></td>
              <td><?= e($s['judul']) ?></td>
              <td><?= lencana($s['persetujuan_dosen'] ? 'Disetujui' : 'Belum', $s['persetujuan_dosen'] ? 'hijau' : 'kuning') ?></td>
              <td>
                <form method="post" class="checklist-berkas">
                  <?= csrf_field() ?>
                  <input type="hidden" name="sidang_id" value="<?= (int) $s['id'] ?>">
                  <?php
                  $item = [
                      'chk_transkrip' => 'Transkrip nilai',
                      'chk_surat_penelitian' => 'Surat penelitian',
                      'chk_naskah' => 'Naskah proposal',
                      'chk_formulir' => 'Formulir sidang',
                      'chk_pinjaman' => 'Bebas pustaka',
                  ];
                  foreach ($item as $nama => $label): ?>
                    <label class="checklist-item">
                      <input type="checkbox" name="<?= e($nama) ?>" <?= $s[$nama] ? 'checked' : '' ?>>
                      <?= e($label) ?>
                    </label>
                  <?php endforeach; ?>
                  <button type="submit" class="btn btn-utama">Simpan</button>
                </form>
              </td>
              <td><?= lencana(str_replace('_', ' ', $s['status']), $s['status'] === 'berkas_lengkap' ? 'hijau' : 'kuning') ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php else: ?>
    <p class="redup">Belum ada pendaftaran sidang proposal.</p>
  <?php endif; ?>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
