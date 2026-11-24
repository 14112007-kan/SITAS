<?php
require_once __DIR__ . '/includes/auth.php';

if (pengguna_login()) {
    arahkan_setelah_login();
}
redirect('login.php');
