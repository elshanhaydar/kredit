<?php
// Error reporting - development mode
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Zaman qurşağı
date_default_timezone_set('Asia/Baku');

// Verilənlər bazası konfiqurasiyası
define('DB_HOST', 'localhost');
define('DB_USER', 'bakudoor_kredit');
define('DB_PASS', 'g1&Ak!N43[{s');
define('DB_NAME', 'bakudoor_kredit');

// URL və Path sabitləri
define('BASE_URL', 'http://elshanhaydar.com/credit');
define('ADMIN_URL', BASE_URL . '/admin');
define('ASSETS_URL', BASE_URL . '/assets');

// Sistem parametrləri
define('SITE_NAME', 'Kredit İdarəetmə Sistemi');
define('ADMIN_EMAIL', 'admin@example.com');
define('NOTIFICATION_DAYS', 3); // Bildiriş göndərmə günü
define('DEFAULT_CURRENCY', 'AZN');

// Session başlatma
session_start();

// Verilənlər bazası qoşulması
try {
    $db = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        )
    );
} catch(PDOException $e) {
    die("Verilənlər bazasına qoşulma xətası: " . $e->getMessage());
}

// Ümumi funksiyalar
function clean($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function redirect($url) {
    header("Location: " . $url);
    exit();
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;
}

// Xəta və uğur mesajları
function setMessage($message, $type = 'success') {
    $_SESSION['message'] = $message;
    $_SESSION['message_type'] = $type;
}

function getMessage() {
    if (isset($_SESSION['message'])) {
        $message = $_SESSION['message'];
        $type = $_SESSION['message_type'];
        unset($_SESSION['message']);
        unset($_SESSION['message_type']);
        return ['message' => $message, 'type' => $type];
    }
    return null;
}

// Faiz dərəcəsi və müddət parametrləri
define('MIN_INTEREST_RATE', 12); // Minimum faiz dərəcəsi
define('MAX_INTEREST_RATE', 24); // Maksimum faiz dərəcəsi
define('MIN_CREDIT_PERIOD', 3);  // Minimum kredit müddəti (ay)
define('MAX_CREDIT_PERIOD', 36); // Maksimum kredit müddəti (ay)
define('MIN_CREDIT_AMOUNT', 500); // Minimum kredit məbləği
define('MAX_CREDIT_AMOUNT', 30000); // Maksimum kredit məbləği

// Kredit statusları
define('CREDIT_STATUS_PENDING', 'pending');    // Gözləmədə
define('CREDIT_STATUS_ACTIVE', 'active');      // Aktiv
define('CREDIT_STATUS_COMPLETED', 'completed'); // Tamamlanmış
define('CREDIT_STATUS_REJECTED', 'rejected');   // Rədd edilmiş
define('CREDIT_STATUS_DELAYED', 'delayed');     // Gecikmiş

// Bildiriş tipləri
define('NOTIFICATION_PAYMENT_DUE', 'payment_due');     // Ödəniş vaxtı
define('NOTIFICATION_PAYMENT_LATE', 'payment_late');    // Gecikmiş ödəniş
define('NOTIFICATION_CREDIT_APPROVED', 'credit_approved'); // Kredit təsdiqləndi
define('NOTIFICATION_CREDIT_REJECTED', 'credit_rejected'); // Kredit rədd edildi