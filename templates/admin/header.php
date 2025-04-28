<?php
require_once dirname(dirname(__DIR__)) . '/includes/Notification.php';

// Admin session yoxlanışı
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}
// Admin session yoxlanışı
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

// Bildiriş sayını alırıq
$notificationObj = new Notification();
$unreadNotifications = $notificationObj->getUnreadCount(null);
?>
<!DOCTYPE html>
<html lang="az">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'Admin Panel - ' . SITE_NAME; ?></title>
    
    <!-- CSS -->
    <link href="../assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/fontawesome.min.css" rel="stylesheet">
    <link href="../assets/css/datatables.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-user-shield me-2"></i>Admin Panel
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarAdmin">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarAdmin">
                <ul class="navbar-nav ms-auto">
                    <!-- Bildirişlər -->
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
                                <a href="notifications.php" class="btn btn-sm btn-primary w-100">
                                    Bütün Bildirişlər
                                </a>
                            </div>
                        </div>
                    </li>

                    <!-- Admin Menyusu -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                            <i class="fas fa-user me-2"></i><?php echo $_SESSION['admin_name']; ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <a class="dropdown-item" href="profile.php">
                                    <i class="fas fa-user-edit me-2"></i>Profil
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="settings.php">
                                    <i class="fas fa-cog me-2"></i>Parametrlər
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item" href="../logout.php">
                                    <i class="fas fa-sign-out-alt me-2"></i>Çıxış
                                </a>
                            </li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Mesajlar -->
    <?php 
    $message = getMessage();
    if ($message): 
    ?>
    <div class="container-fluid mt-5 pt-3">
        <div class="alert alert-<?php echo $message['type'] == 'error' ? 'danger' : 'success'; ?> alert-dismissible fade show">
            <?php echo $message['message']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    </div>
    <?php endif; ?>