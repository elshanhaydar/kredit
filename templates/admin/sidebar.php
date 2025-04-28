<?php
// Cari səhifəni alırıq
$currentPage = basename($_SERVER['PHP_SELF']);
?>

<nav id="sidebar" class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
    <div class="position-sticky pt-3">
        <ul class="nav flex-column">
            <!-- Dashboard -->
            <li class="nav-item">
                <a class="nav-link <?php echo $currentPage == 'index.php' ? 'active' : ''; ?>" 
                   href="index.php">
                    <i class="fas fa-tachometer-alt me-2"></i>
                    Dashboard
                </a>
            </li>

            <!-- Müştərilər -->
            <li class="nav-item">
                <a class="nav-link <?php echo $currentPage == 'customers.php' ? 'active' : ''; ?>"
                   href="customers.php">
                    <i class="fas fa-users me-2"></i>
                    Müştərilər
                </a>
            </li>

            <!-- Kreditlər -->
            <li class="nav-item">
                <a class="nav-link <?php echo $currentPage == 'credits.php' ? 'active' : ''; ?>"
                   href="credits.php">
                    <i class="fas fa-money-bill-wave me-2"></i>
                    Kreditlər
                </a>
            </li>

            <!-- Ödənişlər -->
            <li class="nav-item">
                <a class="nav-link <?php echo $currentPage == 'payments.php' ? 'active' : ''; ?>"
                   href="payments.php">
                    <i class="fas fa-receipt me-2"></i>
                    Ödənişlər
                </a>
            </li>

            <!-- Bildirişlər -->
            <li class="nav-item">
                <a class="nav-link <?php echo $currentPage == 'notifications.php' ? 'active' : ''; ?>"
                   href="notifications.php">
                    <i class="fas fa-bell me-2"></i>
                    Bildirişlər
                    <?php if ($unreadNotifications > 0): ?>
                        <span class="badge bg-danger"><?php echo $unreadNotifications; ?></span>
                    <?php endif; ?>
                </a>
            </li>

            <!-- Hesabatlar -->
            <li class="nav-item">
                <a class="nav-link <?php echo $currentPage == 'reports.php' ? 'active' : ''; ?>"
                   href="reports.php">
                    <i class="fas fa-chart-bar me-2"></i>
                    Hesabatlar
                </a>
            </li>
        </ul>

        <!-- Sistem Tənzimləmələri -->
        <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
            <span>Sistem</span>
        </h6>
        <ul class="nav flex-column mb-2">
            <!-- Parametrlər -->
            <li class="nav-item">
                <a class="nav-link <?php echo $currentPage == 'settings.php' ? 'active' : ''; ?>"
                   href="settings.php">
                    <i class="fas fa-cog me-2"></i>
                    Parametrlər
                </a>
            </li>

            <!-- Admin İstifadəçiləri -->
            <?php if ($_SESSION['admin_role'] == 'admin'): ?>
            <li class="nav-item">
                <a class="nav-link <?php echo $currentPage == 'admins.php' ? 'active' : ''; ?>"
                   href="admins.php">
                    <i class="fas fa-user-shield me-2"></i>
                    Admin İstifadəçiləri
                </a>
            </li>
            <?php endif; ?>

            <!-- System Logs -->
            <li class="nav-item">
                <a class="nav-link <?php echo $currentPage == 'logs.php' ? 'active' : ''; ?>"
                   href="logs.php">
                    <i class="fas fa-history me-2"></i>
                    Sistem Logları
                </a>
            </li>
        </ul>

        <!-- Çıxış -->
        <div class="px-3 mt-4">
            <a href="../logout.php" class="btn btn-danger w-100">
                <i class="fas fa-sign-out-alt me-2"></i>
                Çıxış
            </a>
        </div>
    </div>
</nav>