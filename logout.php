<?php
require_once __DIR__ . '/includes/auth.php';

// Logout hanya lewat POST (dari dialog konfirmasi) supaya tidak bisa dipicu lewat link.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && pengguna_login()) {
    csrf_check();
    audit_log(pengguna_login()['id'], 'LOGOUT', 'Keluar dari sistem');
    proses_logout();
}
redirect('login.php');
