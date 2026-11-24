<?php
require_once __DIR__ . '/../includes/auth.php';
wajib_peran('admin');

$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $aksi = (string) ($_POST['aksi'] ?? '');

    if ($aksi === 'tambah') {
        $nama = trim((string) ($_POST['nama'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $nimNidn = trim((string) ($_POST['nim_nidn'] ?? ''));
        $peran = (array) ($_POST['peran'] ?? []);
        $password = (string) ($_POST['password'] ?? '');

        $peranValid = array_values(array_filter($peran, fn($p) => in_array($p, ['mahasiswa', 'dosen', 'admin'], true)));
        if ($nama === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '' || !$peranValid) {
            flash('galat', 'Nama, email, password, dan paling sedikit satu peran wajib diisi.');
            redirect('admin/pengguna.php');
        }

        $pdo->beginTransaction();
        try {
            $st = $pdo->prepare('INSERT INTO users (nama, email, password_hash, nim_nidn) VALUES (?, ?, ?, ?)');
            $st->execute([$nama, $email, password_hash($password, PASSWORD_DEFAULT), $nimNidn ?: null]);
            $userId = (int) $pdo->lastInsertId();
            $stPeran = $pdo->prepare('INSERT INTO user_roles (user_id, role) VALUES (?, ?)');
            foreach (array_unique($peranValid) as $p) {
                $stPeran->execute([$userId, $p]);
            }
            audit_log(pengguna_login()['id'], 'TAMBAH_PENGGUNA', 'Menambah pengguna ' . $email);
            $pdo->commit();
            flash('sukses', 'Pengguna berhasil ditambahkan.');
        } catch (Throwable $e) {
            $pdo->rollBack();
            flash('galat', 'Email sudah digunakan atau pengguna gagal ditambahkan.');
        }
    }

    if ($aksi === 'peran') {
        $userId = (int) ($_POST['user_id'] ?? 0);
        $peran = (array) ($_POST['peran'] ?? []);
        $peranValid = array_values(array_filter($peran, fn($p) => in_array($p, ['mahasiswa', 'dosen', 'admin'], true)));
        if (!$peranValid || $userId <= 0) {
            flash('galat', 'Pengguna harus memiliki paling sedikit satu peran.');
            redirect('admin/pengguna.php');
        }

        $pdo->beginTransaction();
        try {
            $pdo->prepare('DELETE FROM user_roles WHERE user_id = ?')->execute([$userId]);
            $st = $pdo->prepare('INSERT INTO user_roles (user_id, role) VALUES (?, ?)');
            foreach (array_unique($peranValid) as $p) {
                $st->execute([$userId, $p]);
            }
            audit_log(pengguna_login()['id'], 'UBAH_PERAN', 'Mengubah peran pengguna ID ' . $userId);
            $pdo->commit();
            flash('sukses', 'Peran pengguna berhasil diperbarui.');
        } catch (Throwable $e) {
            $pdo->rollBack();
            flash('galat', 'Peran pengguna gagal diperbarui.');
        }
    }

    if ($aksi === 'aktif') {
        $userId = (int) ($_POST['user_id'] ?? 0);
        $aktif = (int) ($_POST['aktif'] ?? 0);
        $pdo->prepare('UPDATE users SET aktif = ? WHERE id = ?')->execute([$aktif ? 1 : 0, $userId]);
        audit_log(pengguna_login()['id'], 'UBAH_AKTIVASI', 'Mengubah status pengguna ID ' . $userId);
        flash('sukses', 'Status akun berhasil diperbarui.');
    }

    redirect('admin/pengguna.php');
}

$users = $pdo->query("SELECT u.*, GROUP_CONCAT(r.role ORDER BY r.role SEPARATOR ',') AS peran
                      FROM users u LEFT JOIN user_roles r ON r.user_id = u.id
                      GROUP BY u.id ORDER BY u.nama")->fetchAll();

$judulHalaman = 'Pengguna & Peran';
require __DIR__ . '/../includes/header.php';
?>

<section class="dua-kolom">
  <section class="kartu">
    <h2 class="judul-kartu">Tambah pengguna</h2>
    <form method="post">
      <?= csrf_field() ?>
      <input type="hidden" name="aksi" value="tambah">
      <label>Nama</label><input type="text" name="nama" required maxlength="120">
      <label>Email</label><input type="email" name="email" required maxlength="150">
      <label>NIM/NIDN</label><input type="text" name="nim_nidn" maxlength="30">
      <label>Password awal</label><input type="password" name="password" required minlength="6">
      <label>Peran</label>
      <div class="checklist-berkas">
        <?php foreach (['mahasiswa' => 'Mahasiswa', 'dosen' => 'Dosen', 'admin' => 'Admin'] as $p => $label): ?>
          <label class="checklist-item"><input type="checkbox" name="peran[]" value="<?= e($p) ?>"> <?= e($label) ?></label>
        <?php endforeach; ?>
      </div>
      <button type="submit" class="btn btn-utama btn-penuh">Tambah pengguna</button>
    </form>
  </section>

  <section class="kartu">
    <h2 class="judul-kartu">Daftar pengguna</h2>
    <div class="tabel-bungkus">
      <table class="tabel"><thead><tr><th>Nama</th><th>Email</th><th>Peran</th><th>Status</th></tr></thead><tbody>
      <?php foreach ($users as $u): $peran = $u['peran'] ? explode(',', $u['peran']) : []; ?>
        <tr>
          <td><strong><?= e($u['nama']) ?></strong><br><small class="redup"><?= e($u['nim_nidn'] ?: '-') ?></small></td>
          <td><?= e($u['email']) ?></td>
          <td>
            <form method="post" class="checklist-berkas">
              <?= csrf_field() ?><input type="hidden" name="aksi" value="peran"><input type="hidden" name="user_id" value="<?= (int) $u['id'] ?>">
              <?php foreach (['mahasiswa', 'dosen', 'admin'] as $p): ?>
                <label class="checklist-item"><input type="checkbox" name="peran[]" value="<?= e($p) ?>" <?= in_array($p, $peran, true) ? 'checked' : '' ?>> <?= ucfirst($p) ?></label>
              <?php endforeach; ?>
              <button type="submit" class="btn btn-garis">Simpan peran</button>
            </form>
          </td>
          <td>
            <form method="post"><?= csrf_field() ?><input type="hidden" name="aksi" value="aktif"><input type="hidden" name="user_id" value="<?= (int) $u['id'] ?>"><input type="hidden" name="aktif" value="<?= $u['aktif'] ? 0 : 1 ?>"><button type="submit" class="btn <?= $u['aktif'] ? 'btn-bahaya' : 'btn-utama' ?>"><?= $u['aktif'] ? 'Nonaktifkan' : 'Aktifkan' ?></button></form>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody></table>
    </div>
  </section>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
