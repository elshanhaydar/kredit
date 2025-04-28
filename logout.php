<?php
require_once 'config/config.php';

// Session başlatma yoxlaması
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// İstifadəçi tipinə görə yönləndirmə URL-i
$redirectUrl = isset($_SESSION['admin_id']) ? 'admin/login.php' : 'login.php';

// Remember me cookie-ni silirik
if (isset($_COOKIE['remember_token'])) {
    setcookie('remember_token', '', time() - 3600, '/');
}

// Session-u təmizləyirik
session_unset();
session_destroy();

// Uğurlu çıxış mesajı
session_start();
setMessage('Uğurla çıxış edildi');

// İstifadəçini login səhifəsinə yönləndiririk
header('Location: ' . $redirectUrl);
exit();