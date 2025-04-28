<?php
require_once '../config/config.php';
require_once '../includes/Database.php';
require_once '../includes/Customer.php';
require_once '../includes/Credit.php';

// Session başlatma yoxlaması
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Admin session yoxlanışı
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

// ID yoxlanışı
if (!isset($_GET['id'])) {
    header('Location: customers.php');
    exit();
}

$customerId = (int)$_GET['id'];
$customerObj = new Customer();
$creditObj = new Credit();

// Müştəri məlumatlarını alırıq
$customer = $customerObj->getById($customerId);
if (!$customer) {
    setMessage('Müştəri tapılmadı', 'error');
    header('Location: customers.php');
    exit();
}

// Müştərinin kreditlərini alırıq
$credits = $creditObj->getAll(['customer_id' => $customerId]);

// Aktiv kreditləri sayırıq
$activeCredits = array_filter($credits, function($credit) {
    return $credit['status'] == 'active';
});

$pageTitle = $customer['first_name'] . ' ' . $customer['last_name'] . ' - Müştəri Məlumatları';

// Header
include '../templates/admin/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <?php include '../templates/admin/sidebar.php'; ?>

        <!-- Əsas Content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
            <!-- Səhifə başlığı -->
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center mb-3">
                <h1 class="h2">Müştəri Məlumatları</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="customers.php" class="btn btn-secondary me-2">
                        <i class="fas fa-arrow-left me-2"></i>Geri
                    </a>
                    <a href="customer_edit.php?id=<?php echo $customer['id']; ?>" class="btn btn-primary">
                        <i class="fas fa-edit me-2"></i>Düzəliş et
                    </a>
                </div>
            </div>

            <div class="row">
                <!-- Müştəri məlumatları -->
                <div class="col-md-4">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Şəxsi Məlumatlar</h5>
                        </div>
                        <div class="card-body">
                            <div class="text-center mb-4">
                                <i class="fas fa-user-circle fa-5x text-primary"></i>
                                <h4 class="mt-3"><?php echo $customer['first_name'] . ' ' . $customer['last_name']; ?></h4>
                                <span class="badge bg-<?php echo $customer['status'] == 'active' ? 'success' : 'danger'; ?>">
                                    <?php echo $customer['status'] == 'active' ? 'Aktiv' : 'Blok'; ?>
                                </span>
                            </div>

                            <ul class="list-unstyled">
                                <li class="mb-2">
                                    <strong>Ata adı:</strong> <?php echo $customer['father_name']; ?>
                                </li>
                                <li class="mb-2">
                                    <strong>Şəxsiyyət vəsiqəsi:</strong> <?php echo $customer['id_number']; ?>
                                </li>
                                <li class="mb-2">
                                    <strong>FİN kod:</strong> <?php echo $customer['fin_code']; ?>
                                </li>
                                <li class="mb-2">
                                    <strong>Email:</strong> <?php echo $customer['email']; ?>
                                </li>
                                <li class="mb-2">
                                    <strong>Qeydiyyat tarixi:</strong>
                                    <?php echo date('d.m.Y H:i', strtotime($customer['created_at'])); ?>
                                </li>
                                <li>
                                    <strong>Son yenilənmə:</strong>
                                    <?php echo date('d.m.Y H:i', strtotime($customer['updated_at'])); ?>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <!-- Kredit Statistikası -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Kredit Statistikası</h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-6">
                                    <div class="border rounded p-3 text-center">
                                        <h3 class="mb-1"><?php echo count($credits); ?></h3>
                                        <small class="text-muted">Ümumi Kreditlər</small>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="border rounded p-3 text-center">
                                        <h3 class="mb-1"><?php echo count($activeCredits); ?></h3>
                                        <small class="text-muted">Aktiv Kreditlər</small>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="border rounded p-3 text-center">
                                        <h3 class="mb-1">
                                            <?php 
                                            $totalAmount = array_reduce($credits, function($carry, $item) {
                                                return $carry + $item['amount'];
                                            }, 0);
                                            echo number_format($totalAmount, 2);
                                            ?> AZN
                                        </h3>
                                        <small class="text-muted">Ümumi Kredit Məbləği</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Kreditlər -->
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Kreditlər</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped datatable">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Məbləğ</th>
                                            <th>Müddət</th>
                                            <th>Faiz</th>
                                            <th>Status</th>
                                            <th>Tarix</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($credits as $credit): ?>
                                        <tr>
                                            <td><?php echo $credit['id']; ?></td>
                                            <td><?php echo number_format($credit['amount'], 2); ?> AZN</td>
                                            <td><?php echo $credit['period_months']; ?> ay</td>
                                            <td><?php echo $credit['interest_rate']; ?>%</td>
                                            <td>
                                                <span class="badge bg-<?php echo getStatusColor($credit['status']); ?>">
                                                    <?php echo getStatusText($credit['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php echo date('d.m.Y', strtotime($credit['created_at'])); ?>
                                            </td>
                                            <td>
                                                <a href="credit_view.php?id=<?php echo $credit['id']; ?>" 
                                                   class="btn btn-sm btn-info" title="Ətraflı">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                        <?php if (empty($credits)): ?>
                                        <tr>
                                            <td colspan="7" class="text-center">Kredit tapılmadı</td>
                                        </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
// Status funksiyaları
function getStatusColor(status) {
    switch(status) {
        case 'active': return 'success';
        case 'pending': return 'warning';
        case 'completed': return 'info';
        case 'delayed': return 'danger';
        case 'rejected': return 'danger';
        default: return 'secondary';
    }
}

function getStatusText(status) {
    switch(status) {
        case 'active': return 'Aktiv';
        case 'pending': return 'Gözləmədə';
        case 'completed': return 'Tamamlanmış';
        case 'delayed': return 'Gecikmiş';
        case 'rejected': return 'Rədd edilmiş';
        default: return 'Naməlum';
    }
}

// DataTables inisializasiyası
$(document).ready(function() {
    $('.datatable').DataTable({
        language: {
            url: '../assets/js/datatables-az.json'
        },
        pageLength: 10,
        order: [[0, 'desc']]
    });
});
</script>

<?php include '../templates/admin/footer.php'; ?>