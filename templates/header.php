<?php
// header.php-nin əvvəlinə əlavə edirik
require_once dirname(__DIR__) . '/includes/Notification.php';

// Səhifəyə aid titleni alırıq
$pageTitle = $pageTitle ?? SITE_NAME;

// İstifadəçi tipini təyin edirik
$isAdmin = isset($_SESSION['admin_id']);
$isUser = isset($_SESSION['user_id']);

// Səhifəyə aid titleni alırıq
$pageTitle = $pageTitle ?? SITE_NAME;

// İstifadəçi tipini təyin edirik
$isAdmin = isset($_SESSION['admin_id']);
$isUser = isset($_SESSION['user_id']);

// Bildiriş sayını alırıq
$unreadNotifications = 0;
if ($isUser) {
    $notificationObj = new Notification();
    $unreadNotifications = $notificationObj->getUnreadCount($_SESSION['user_id']);
}
?>

<!DOCTYPE html>
<html lang="az">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="assets/css/fontawesome.min.css" rel="stylesheet">
    
    <!-- DataTables CSS (admin panel üçün) -->
    <?php if(isset($isAdmin) && $isAdmin): ?>
    <link href="assets/css/datatables.min.css" rel="stylesheet">
    <?php endif; ?>
    
    <!-- Custom CSS -->
    <link href="assets/css/style.css" rel="stylesheet">

    <!-- Əlavə CSS faylları -->
    <?php if(isset($extraCSS)): ?>
        <?php foreach($extraCSS as $css): ?>
        <link href="<?php echo $css; ?>" rel="stylesheet">
        <?php endforeach; ?>
    <?php endif; ?>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="<?php echo BASE_URL; ?>">
                <i class="fas fa-credit-card me-2"></i>
                <?php echo SITE_NAME; ?>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarMain">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo BASE_URL; ?>">Ana Səhifə</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo BASE_URL; ?>/calculate.php">Kredit Kalkulyatoru</a>
                    </li>
                    <?php if ($isUser): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo BASE_URL; ?>/dashboard.php">Şəxsi Kabinet</a>
                    </li>
                    <?php endif; ?>
                    <?php if ($isAdmin): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo ADMIN_URL; ?>">Admin Panel</a>
                    </li>
                    <?php endif; ?>
                </ul>

                <ul class="navbar-nav ms-auto">
                    <?php if ($isUser || $isAdmin): ?>
                        <!-- Bildiriş Menyusu -->
                        <li class="nav-item dropdown">
                            <a class="nav-link" href="#" data-bs-toggle="dropdown">
                                <i class="fas fa-bell"></i>
                                <?php if ($unreadNotifications > 0): ?>
                                    <span class="badge bg-danger"><?php echo $unreadNotifications; ?></span>
                                <?php endif; ?>
                            </a>
                            <div class="dropdown-menu dropdown-menu-end notification-dropdown">
                                <div class="notification-header">
                                    <h6 class="m-0">Bildirişlər</h6>
                                </div>
                                <div class="notification-body" id="notificationList">
                                    <!-- JavaScript ilə doldurulacaq -->
                                    <div class="text-center py-3">
                                        <div class="spinner-border text-primary" role="status">
                                            <span class="visually-hidden">Loading...</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="notification-footer">
                                    <a href="<?php echo BASE_URL; ?>/notifications.php" class="btn btn-sm btn-primary w-100">
                                        Bütün Bildirişlər
                                    </a>
                                </div>
                            </div>
                        </li>

                        <!-- İstifadəçi Menyusu -->
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                                <i class="fas fa-user me-1"></i>
                                <?php 
                                if ($isAdmin) {
                                    echo 'Admin';
                                } else {
                                    echo $_SESSION['user_name'];
                                }
                                ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <?php if ($isUser): ?>
                                <li>
                                    <a class="dropdown-item" href="<?php echo BASE_URL; ?>/profile.php">
                                        <i class="fas fa-user-edit me-2"></i>Profil
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="<?php echo BASE_URL; ?>/my_credits.php">
                                        <i class="fas fa-money-bill me-2"></i>Kreditlərim
                                    </a>
                                </li>
                                <?php endif; ?>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item" href="<?php echo BASE_URL; ?>/logout.php">
                                        <i class="fas fa-sign-out-alt me-2"></i>Çıxış
                                    </a>
                                </li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo BASE_URL; ?>/login.php">
                                <i class="fas fa-sign-in-alt me-1"></i>Giriş
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo BASE_URL; ?>/register.php">
                                <i class="fas fa-user-plus me-1"></i>Qeydiyyat
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <?php
    // Mesajları göstəririk
    $message = getMessage();
    if ($message): 
    ?>
    <div class="container mt-3">
        <div class="alert alert-<?php echo $message['type'] == 'error' ? 'danger' : 'success'; ?> alert-dismissible fade show">
            <?php echo $message['message']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    </div>
    <?php endif; ?>

    <!-- Bildiriş JavaScript -->
    <?php if ($isUser || $isAdmin): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            function loadNotifications() {
                fetch('<?php echo BASE_URL; ?>/api/notifications.php?action=get')
                    .then(response => response.json())
                    .then(data => {
                        const container = document.getElementById('notificationList');
                        container.innerHTML = '';

                        if (data.notifications.length === 0) {
                            container.innerHTML = '<div class="text-center py-3">Bildiriş yoxdur</div>';
                            return;
                        }

                        data.notifications.slice(0, 5).forEach(notification => {
                            container.innerHTML += `
                                <div class="notification-item ${notification.is_read ? '' : 'unread'}">
                                    <div class="notification-content">
                                        ${notification.message}
                                    </div>
                                    <div class="notification-time">
                                        ${new Date(notification.created_at).toLocaleString()}
                                    </div>
                                </div>
                            `;
                        });
                    });
            }

            // Bildirişləri yükləyirik
            loadNotifications();

            // Hər 5 dəqiqədən bir yeniləyirik
            setInterval(loadNotifications, 300000);
        });
    </script>
    <?php endif; ?>