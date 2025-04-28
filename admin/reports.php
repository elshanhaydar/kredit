<?php
require_once '../config/config.php';
require_once '../includes/Database.php';
require_once '../includes/Report.php';

// Admin session yoxlanışı
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

$reportObj = new Report();

// Hesabat növü və tarix aralığı
$reportType = $_GET['type'] ?? 'monthly';
$year = $_GET['year'] ?? date('Y');
$month = $_GET['month'] ?? date('m');
$startDate = $_GET['start_date'] ?? date('Y-m-01');
$endDate = $_GET['end_date'] ?? date('Y-m-t');

// Excel export
if (isset($_GET['export']) && $_GET['export'] == 'excel') {
    $params = [
        'year' => $year,
        'month' => $month,
        'start_date' => $startDate,
        'end_date' => $endDate
    ];
    
    $filePath = $reportObj->generateExcelReport($reportType, $params);
    
    if ($filePath) {
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="hesabat.xlsx"');
        readfile($filePath);
        unlink($filePath);
        exit();
    }
}

// Hesabat məlumatlarını alırıq
switch ($reportType) {
    case 'monthly':
        $report = $reportObj->getMonthlyCreditReport($year, $month);
        break;
    case 'payments':
        $report = $reportObj->getPaymentReport($startDate, $endDate);
        break;
    case 'overdue':
        $report = $reportObj->getOverduePaymentsReport();
        break;
    case 'customer_activity':
        $report = $reportObj->getCustomerActivityReport();
        break;
    default:
        $report = $reportObj->getMonthlyCreditReport($year, $month);
}

?>
<!DOCTYPE html>
<html lang="az">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hesabatlar - Admin Panel</title>
    
    <!-- CSS -->
    <link href="../assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/fontawesome.min.css" rel="stylesheet">
    <link href="../assets/css/datatables.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
    <!-- Admin Header -->
    <?php include '../templates/admin_header.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php include '../templates/admin_sidebar.php'; ?>

            <!-- Əsas Content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center mb-3">
                    <h1 class="h2">Hesabatlar</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="?type=<?php echo $reportType; ?>&export=excel" class="btn btn-success me-2">
                            <i class="fas fa-file-excel"></i> Excel
                        </a>
                    </div>
                </div>

                <!-- Hesabat Növü Seçimi -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">Hesabat növü</label>
                                <select name="type" class="form-select" onchange="this.form.submit()">
                                    <option value="monthly" <?php echo $reportType == 'monthly' ? 'selected' : ''; ?>>
                                        Aylıq Kredit Hesabatı
                                    </option>
                                    <option value="payments" <?php echo $reportType == 'payments' ? 'selected' : ''; ?>>
                                        Ödəniş Hesabatı
                                    </option>
                                    <option value="overdue" <?php echo $reportType == 'overdue' ? 'selected' : ''; ?>>
                                        Gecikmiş Ödənişlər
                                    </option>
                                    <option value="customer_activity" <?php echo $reportType == 'customer_activity' ? 'selected' : ''; ?>>
                                        Müştəri Aktivliyi
                                    </option>
                                </select>
                            </div>

                            <?php if ($reportType == 'monthly'): ?>
                            <div class="col-md-2">
                                <label class="form-label">İl</label>
                                <select name="year" class="form-select">
                                    <?php for ($y = date('Y'); $y >= 2020; $y--): ?>
                                        <option value="<?php echo $y; ?>" <?php echo $year == $y ? 'selected' : ''; ?>>
                                            <?php echo $y; ?>
                                        </option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Ay</label>
                                <select name="month" class="form-select">
                                    <?php for ($m = 1; $m <= 12; $m++): ?>
                                        <option value="<?php echo str_pad($m, 2, '0', STR_PAD_LEFT); ?>" 
                                                <?php echo $month == $m ? 'selected' : ''; ?>>
                                            <?php echo str_pad($m, 2, '0', STR_PAD_LEFT); ?>
                                        </option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <?php endif; ?>

                            <?php if ($reportType == 'payments'): ?>
                            <div class="col-md-3">
                                <label class="form-label">Başlanğıc tarix</label>
                                <input type="date" name="start_date" class="form-control" 
                                       value="<?php echo $startDate; ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Son tarix</label>
                                <input type="date" name="end_date" class="form-control" 
                                       value="<?php echo $endDate; ?>">
                            </div>
                            <?php endif; ?>

                            <div class="col-md-2 align-self-end">
                                <button type="submit" class="btn btn-primary">Göstər</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Hesabat Məzmunu -->
                <div class="card">
                    <div class="card-body">
                        <?php if ($reportType == 'monthly'): ?>
                            <!-- Aylıq Kredit Hesabatı -->
                            <div class="row g-4">
                                <div class="col-md-3">
                                    <div class="card bg-primary text-white">
                                        <div class="card-body">
                                            <h5 class="card-title">Ümumi Kreditlər</h5>
                                            <h2><?php echo $report['total_credits']; ?></h2>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card bg-success text-white">
                                        <div class="card-body">
                                            <h5 class="card-title">Ümumi Məbləğ</h5>
                                            <h2><?php echo number_format($report['total_amount'], 2); ?> AZN</h2>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card bg-info text-white">
                                        <div class="card-body">
                                            <h5 class="card-title">Orta Faiz</h5>
                                            <h2><?php echo number_format($report['avg_interest_rate'], 2); ?>%</h2>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card bg-warning text-white">
                                        <div class="card-body">
                                            <h5 class="card-title">İlkin Ödənişlər</h5>
                                            <h2><?php echo number_format($report['total_initial_payments'], 2); ?> AZN</h2>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Qrafik -->
                            <div class="mt-4">
                                <canvas id="monthlyChart"></canvas>
                            </div>

                        <?php elseif ($reportType == 'payments'): ?>
                            <!-- Ödəniş Hesabatı -->
                            <div class="table-responsive">
                                <table class="table table-striped datatable">
                                    <thead>
                                        <tr>
                                            <th>Ümumi Ödənişlər</th>
                                            <th>Ödənilmiş</th>
                                            <th>Gecikmədə</th>
                                            <th>Ödəniş Sayı</th>
                                            <th>Gecikən Sayı</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td><?php echo number_format($report['total_amount'], 2); ?> AZN</td>
                                            <td><?php echo number_format($report['paid_amount'], 2); ?> AZN</td>
                                            <td><?php echo number_format($report['overdue_amount'], 2); ?> AZN</td>
                                            <td><?php echo $report['paid_count']; ?></td>
                                            <td><?php echo $report['overdue_count']; ?></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                        <?php elseif ($reportType == 'overdue'): ?>
                            <!-- Gecikmiş Ödənişlər -->
                            <div class="table-responsive">
                                <table class="table table-striped datatable">
                                    <thead>
                                        <tr>
                                            <th>Müştəri</th>
                                            <th>Kredit Məbləği</th>
                                            <th>Ödəniş Tarixi</th>
                                            <th>Ödəniş Məbləği</th>
                                            <th>Gecikən Gün</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($report as $payment): ?>
                                        <tr>
                                            <td><?php echo $payment['first_name'] . ' ' . $payment['last_name']; ?></td>
                                            <td><?php echo number_format($payment['credit_amount'], 2); ?> AZN</td>
                                            <td><?php echo date('d.m.Y', strtotime($payment['payment_date'])); ?></td>
                                            <td><?php echo number_format($payment['payment_amount'], 2); ?> AZN</td>
                                            <td><?php echo $payment['days_overdue']; ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                        <?php elseif ($reportType == 'customer_activity'): ?>
                            <!-- Müştəri Aktivliyi -->
                            <div class="table-responsive">
                                <table class="table table-striped datatable">
                                    <thead>
                                        <tr>
                                            <th>Müştəri</th>
                                            <th>Kredit Sayı</th>
                                            <th>Ümumi Məbləğ</th>
                                            <th>Aktiv Kreditlər</th>
                                            <th>Gecikən Kreditlər</th>
                                            <th>Son Kredit</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($report as $customer): ?>
                                        <tr>
                                            <td><?php echo $customer['first_name'] . ' ' . $customer['last_name']; ?></td>
                                            <td><?php echo $customer['total_credits']; ?></td>
                                            <td><?php echo number_format($customer['total_credit_amount'], 2); ?> AZN</td>
                                            <td><?php echo $customer['active_credits']; ?></td>
                                            <td><?php echo $customer['delayed_credits']; ?></td>
                                            <td><?php echo date('d.m.Y', strtotime($customer['last_credit_date'])); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- JavaScript -->
    <script src="../assets/js/jquery.min.js"></script>
    <script src="../assets/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/datatables.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // DataTables inisializasiyası
        $(document).ready(function() {
            $('.datatable').DataTable({
                language: {
                    url: '../assets/js/datatables-az.json'
                },
                pageLength: 25,
                order: [[0, 'desc']]
            });
        });

        <?php if ($reportType == 'monthly'): ?>
        // Aylıq qrafik
        fetch('api/chart_data.php?type=monthly_credits&year=<?php echo $year; ?>')
            .then(response => response.json())
            .then(data => {
                const ctx = document.getElementById('monthlyChart').getContext('2d');
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
                                text: 'Aylıq Kredit Statistikası'
                            }
                        }
                    }
                });
            });
        <?php endif; ?>
    </script>
</body>
</html>