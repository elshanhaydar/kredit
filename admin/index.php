<?php
require_once '../config/config.php';
require_once '../includes/Database.php';
require_once '../includes/Credit.php';
require_once '../includes/Customer.php';
require_once '../includes/Report.php';

// Session artıq başladılıbsa yenidən başlatmırıq
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Admin session yoxlanışı
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

$creditObj = new Credit();
$customerObj = new Customer();
$reportObj = new Report();

// Dashboard statistikalarını alırıq
$dashboardStats = $reportObj->getDashboardStats();
if (!$dashboardStats) {
    $dashboardStats = [
        'total_customers' => 0,
        'active_credits' => 0,
        'delayed_credits' => 0,
        'total_credit_amount' => 0
    ];
}

// Son 5 krediti alırıq
$recentCredits = $creditObj->getAll([], 5) ?? [];

// Son 5 müştərini alırıq
$recentCustomers = $customerObj->getAll(5) ?? [];

// Son ödənişləri alırıq
$overduePayments = $reportObj->getOverduePaymentsReport() ?? [];

// Header və Sidebar include etmə yollarını düzəldirik
include '../templates/admin/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include '../templates/admin/sidebar.php'; ?>

        <!-- Qalan kod eyni qalır -->

            <!-- Əsas Content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                <h1 class="h2 mb-4">Dashboard</h1>

                <!-- Statistika Kartları -->
                <div class="row">
                    <!-- Ümumi Müştərilər -->
                    <div class="col-12 col-md-6 col-lg-3 mb-4">
                        <div class="card dashboard-card bg-primary text-white h-100">
                            <div class="card-body">
                                <h5 class="card-title">Ümumi Müştərilər</h5>
                                <h2><?php echo $dashboardStats['total_customers']; ?></h2>
                                <p class="card-text">Sistemdə qeydiyyatda olan müştərilər</p>
                            </div>
                        </div>
                    </div>

                    <!-- Aktiv Kreditlər -->
                    <div class="col-12 col-md-6 col-lg-3 mb-4">
                        <div class="card dashboard-card bg-success text-white h-100">
                            <div class="card-body">
                                <h5 class="card-title">Aktiv Kreditlər</h5>
                                <h2><?php echo $dashboardStats['active_credits']; ?></h2>
                                <p class="card-text">Hazırda aktiv olan kreditlər</p>
                            </div>
                        </div>
                    </div>

                    <!-- Gecikmiş Kreditlər -->
                    <div class="col-12 col-md-6 col-lg-3 mb-4">
                        <div class="card dashboard-card bg-danger text-white h-100">
                            <div class="card-body">
                                <h5 class="card-title">Gecikmiş Kreditlər</h5>
                                <h2><?php echo $dashboardStats['delayed_credits']; ?></h2>
                                <p class="card-text">Ödənişi gecikən kreditlər</p>
                            </div>
                        </div>
                    </div>

                    <!-- Ümumi Məbləğ -->
                    <div class="col-12 col-md-6 col-lg-3 mb-4">
                        <div class="card dashboard-card bg-info text-white h-100">
                            <div class="card-body">
                                <h5 class="card-title">Ümumi Məbləğ</h5>
                                <h2><?php echo number_format($dashboardStats['total_credit_amount'], 2); ?> AZN</h2>
                                <p class="card-text">Verilmiş kreditlərin cəmi</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Son Kreditlər və Müştərilər -->
                <div class="row">
                    <!-- Son Kreditlər -->
                    <div class="col-12 col-lg-6 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Son Kreditlər</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Müştəri</th>
                                                <th>Məbləğ</th>
                                                <th>Status</th>
                                                <th>Tarix</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($recentCredits as $credit): ?>
                                            <tr>
                                                <td><?php echo $credit['first_name'] . ' ' . $credit['last_name']; ?></td>
                                                <td><?php echo number_format($credit['amount'], 2); ?> AZN</td>
                                                <td>
                                                    <span class="badge bg-<?php echo getStatusColor($credit['status']); ?>">
                                                        <?php echo getStatusText($credit['status']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo date('d.m.Y', strtotime($credit['created_at'])); ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <a href="credits.php" class="btn btn-primary btn-sm">Bütün Kreditlər</a>
                            </div>
                        </div>
                    </div>

                    <!-- Gecikmiş Ödənişlər -->
                    <div class="col-12 col-lg-6 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Gecikmiş Ödənişlər</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Müştəri</th>
                                                <th>Məbləğ</th>
                                                <th>Gecikmə</th>
                                                <th>Əməliyyat</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($overduePayments as $payment): ?>
                                            <tr>
                                                <td><?php echo $payment['first_name'] . ' ' . $payment['last_name']; ?></td>
                                                <td><?php echo number_format($payment['payment_amount'], 2); ?> AZN</td>
                                                <td><?php echo $payment['days_overdue']; ?> gün</td>
                                                <td>
                                                    <a href="credits.php?id=<?php echo $payment['credit_id']; ?>" 
                                                       class="btn btn-sm btn-primary">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <a href="reports.php?type=overdue" class="btn btn-danger btn-sm">Bütün Gecikmələr</a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Qrafiklər -->
                <div class="row">
                    <!-- Aylıq Kredit Statistikası -->
                    <div class="col-12 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Aylıq Kredit Statistikası</h5>
                            </div>
                            <div class="card-body">
                                <canvas id="monthlyCreditsChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- JavaScript -->
    <script src="../assets/js/jquery.min.js"></script>
    <script src="../assets/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Status rəngi funksiyası
        function getStatusColor(status) {
            switch(status) {
                case 'active': return 'success';
                case 'pending': return 'warning';
                case 'completed': return 'info';
                case 'delayed': return 'danger';
                default: return 'secondary';
            }
        }

        // Status mətni funksiyası
        function getStatusText(status) {
            switch(status) {
                case 'active': return 'Aktiv';
                case 'pending': return 'Gözləmədə';
                case 'completed': return 'Tamamlanmış';
                case 'delayed': return 'Gecikmiş';
                default: return 'Naməlum';
            }
        }

        // Qrafik məlumatlarını yükləyirik
        fetch('api/chart_data.php?type=monthly_credits')
            .then(response => response.json())
            .then(data => {
                const ctx = document.getElementById('monthlyCreditsChart').getContext('2d');
                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: data.map(item => item.month),
                        datasets: [{
                            label: 'Kredit Sayı',
                            data: data.map(item => item.credit_count),
                            borderColor: 'rgb(75, 192, 192)',
                            tension: 0.1
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: {
                                position: 'top',
                            },
                            title: {
                                display: true,
                                text: 'Aylıq Kredit Sayı'
                            }
                        }
                    }
                });
            });
    </script>
</body>
</html>