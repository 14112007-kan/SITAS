<?php
require_once __DIR__ . '/includes/auth.php';

if (pengguna_login()) {
    arahkan_setelah_login();
}

$galat = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {                       // FR-01: isian kosong
        $galat = 'Email dan kata sandi wajib diisi.';
    } else {
        $st = db()->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
        $st->execute([$email]);
        $user = $st->fetch();

        if (!$user) {                                              // FR-01: akun tidak ditemukan
            $galat = 'Akun tidak ditemukan.';
        } elseif (!$user['aktif']) {
            $galat = 'Akun Anda dinonaktifkan. Hubungi Admin.';
        } elseif (!password_verify($password, $user['password_hash'])) {   // FR-01: kredensial salah
            $galat = 'Email atau kata sandi salah.';
        } else {
            $st = db()->prepare("SELECT role FROM user_roles WHERE user_id = ?
                                 ORDER BY FIELD(role, 'mahasiswa', 'dosen', 'admin')");
            $st->execute([$user['id']]);
            $peran = $st->fetchAll(PDO::FETCH_COLUMN);

            if (!$peran) {
                $galat = 'Akun belum memiliki peran. Hubungi Admin.';
            } else {
                proses_login($user, $peran);
                audit_log((int) $user['id'], 'LOGIN', 'Masuk ke sistem');
                arahkan_setelah_login();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Masuk | <?= e(APP_NAME) ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= e(url('assets/css/style.css')) ?>">
</head>
<body class="halaman-tamu">
  <div class="kotak-tamu">
    <div class="brand brand-besar">
      <span class="brand-logo">S</span>
      <div><strong><?= e(APP_NAME) ?></strong><small><?= e(APP_FULL_NAME) ?></small></div>
    </div>

    <h2>Masuk ke akun</h2>

    <?php if ($galat): ?>
      <div class="alert alert-galat"><?= e($galat) ?></div>
    <?php endif; ?>

    <form method="post" novalidate>
      <?= csrf_field() ?>
      <label for="email">Email</label>
      <input type="email" id="email" name="email" value="<?= e($email) ?>" autocomplete="username" autofocus>

      <label for="password">Kata sandi</label>
      <input type="password" id="password" name="password" autocomplete="current-password">

      <button type="submit" class="btn btn-utama btn-penuh">Masuk</button>
    </form>

    <?php if (APP_DEBUG): ?>
      <div class="demo-akun">
        <strong>Akun demo</strong> (kata sandi semua: <code>sitas123</code>)
        <ul>
          <li>Mahasiswa: <code>aisyah@sitas.test</code></li>
          <li>Dosen: <code>siti@sitas.test</code></li>
          <li>Admin: <code>admin@sitas.test</code></li>
          <li>Dosen + Admin: <code>kaprodi@sitas.test</code></li>
        </ul>
      </div>
    <?php endif; ?>
  </div>
</body>
</html>
