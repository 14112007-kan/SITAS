<?php
require_once __DIR__ . '/config.php';

// Mengembalikan satu koneksi PDO yang dipakai ulang di seluruh aplikasi.
function db(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            exit('Database tidak dapat dihubungi. Pastikan MySQL di XAMPP aktif, '
               . 'database "' . DB_NAME . '" sudah dibuat, dan config/config.php benar.');
        }
    }
    return $pdo;
}
