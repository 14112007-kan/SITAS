<?php
// Sesi login, peran aktif, dan penjaga akses halaman.
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/helpers.php';

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
    session_start();
}

const NAMA_PERAN = [
    'mahasiswa' => 'Mahasiswa',
    'dosen'     => 'Dosen Pembimbing',
    'admin'     => 'Admin',
];

function pengguna_login(): ?array
{
    return $_SESSION['user'] ?? null;
}

function peran_aktif(): ?string
{
    return $_SESSION['peran_aktif'] ?? null;
}

function daftar_peran(): array
{
    return $_SESSION['peran'] ?? [];
}

// Simpan data pengguna ke sesi setelah password terverifikasi.
function proses_login(array $user, array $peran): void
{
    session_regenerate_id(true); // cegah session fixation
    $_SESSION['user'] = [
        'id'    => (int) $user['id'],
        'nama'  => $user['nama'],
        'email' => $user['email'],
    ];
    $_SESSION['peran'] = $peran;
    // Kalau hanya punya satu peran, langsung aktif. Kalau lebih, harus memilih (FR-02).
    $_SESSION['peran_aktif'] = count($peran) === 1 ? $peran[0] : null;
}

function proses_logout(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 3600, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

function url_beranda_peran(string $peran): string
{
    return $peran . '/dashboard.php';
}

// Setelah login atau ganti peran: arahkan ke tujuan yang tepat.
function arahkan_setelah_login(): void
{
    $peran = peran_aktif();
    redirect($peran ? url_beranda_peran($peran) : 'pilih-peran.php');
}

function wajib_login(): void
{
    if (!pengguna_login()) {
        redirect('login.php');
    }
}

// Penjaga akses sisi server (NFR-07, BR-15): panggil di baris pertama tiap halaman peran.
function wajib_peran(string $peran): void
{
    wajib_login();

    if (peran_aktif() === $peran) {
        return;
    }
    if (in_array($peran, daftar_peran(), true)) {
        flash('info', 'Pilih peran "' . NAMA_PERAN[$peran] . '" untuk membuka halaman itu.');
        redirect('pilih-peran.php');
    }
    http_response_code(403);
    exit('Akses ditolak. Anda tidak memiliki peran ' . e(NAMA_PERAN[$peran] ?? $peran) . '.');
}
