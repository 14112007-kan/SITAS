<?php
// Fungsi-fungsi kecil yang dipakai di banyak halaman.

// Amankan teks sebelum dicetak ke HTML (mencegah XSS).
function e($teks): string
{
    return htmlspecialchars((string) $teks, ENT_QUOTES, 'UTF-8');
}

// Buat URL absolut berdasarkan BASE_URL. Contoh: url('login.php')
function url(string $path = ''): string
{
    return BASE_URL . '/' . ltrim($path, '/');
}

function redirect(string $path): void
{
    header('Location: ' . url($path));
    exit;
}

// ---------- Pesan flash (muncul sekali setelah redirect) ----------
function flash(string $tipe, string $pesan): void
{
    $_SESSION['flash'][] = ['tipe' => $tipe, 'pesan' => $pesan]; // tipe: sukses | galat | info
}

function ambil_flash(): array
{
    $pesan = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $pesan;
}

// ---------- CSRF: pastikan form benar-benar dikirim dari halaman kita ----------
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function csrf_check(): void
{
    $kirim = $_POST['csrf'] ?? '';
    if (!hash_equals($_SESSION['csrf'] ?? '', $kirim)) {
        http_response_code(419);
        exit('Sesi formulir kedaluwarsa. Muat ulang halaman lalu coba lagi.');
    }
}

// ---------- Audit log (NFR-06, FR-30) ----------
function audit_log(?int $userId, string $aksi, string $keterangan = ''): void
{
    $st = db()->prepare('INSERT INTO audit_log (user_id, aksi, keterangan) VALUES (?, ?, ?)');
    $st->execute([$userId, $aksi, $keterangan]);
}

// ---------- Notifikasi dalam aplikasi (FR-31, versi awal) ----------
function kirim_notifikasi(int $userId, string $pesan, ?string $tautan = null): void
{
    $st = db()->prepare('INSERT INTO notifikasi (user_id, pesan, tautan) VALUES (?, ?, ?)');
    $st->execute([$userId, $pesan, $tautan]);
}

function notifikasi_terbaru(int $userId, int $batas = 5): array
{
    $batas = max(1, $batas);
    $st = db()->prepare("SELECT * FROM notifikasi WHERE user_id = ? ORDER BY id DESC LIMIT $batas");
    $st->execute([$userId]);
    return $st->fetchAll();
}

// ---------- Format tampilan ----------
function tanggal_id(?string $tanggal, bool $denganJam = false): string
{
    if (!$tanggal) {
        return '-';
    }
    $bulan = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    $t = strtotime($tanggal);
    $hasil = date('j', $t) . ' ' . $bulan[(int) date('n', $t)] . ' ' . date('Y', $t);
    return $denganJam ? $hasil . ', ' . date('H:i', $t) : $hasil;
}

function kartu_statistik(string $label, $nilai, string $keterangan = ''): string
{
    return '<div class="kartu stat"><div class="stat-label">' . e($label) . '</div>'
         . '<div class="stat-nilai">' . e($nilai) . '</div>'
         . ($keterangan !== '' ? '<div class="stat-ket">' . e($keterangan) . '</div>' : '')
         . '</div>';
}

function lencana(string $teks, string $warna = 'abu'): string
{
    return '<span class="lencana lencana-' . e($warna) . '">' . e($teks) . '</span>';
}
