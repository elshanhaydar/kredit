<?php
require_once '../config/config.php';
require_once '../includes/Database.php';
require_once '../includes/Credit.php';
require_once '../includes/Customer.php';
require_once '../includes/Notification.php';

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
    header('Location: credits.php');
    exit();
}

$creditId = (int)$_GET['id'];
$creditObj = new Credit();
$customerObj = new Customer();
$notificationObj = new Notification();

// Kredit məlumatlarını alırıq
$credit = $creditObj->getById($creditId);
if (!$credit) {
    setMessage('Kredit tapılmadı', 'error');
    header('Location: credits.php');
    exit();
}

// Kredit ödənişlərini alırıq
$payments = $creditObj->getPayments($creditId);

// Status dəyişmə
if (isset($_POST['update_status'])) {
    $newStatus = clean($_POST['status']);
    
    if ($creditObj->updateStatus($creditId, $newStatus)) {
        // Bildiriş göndəririk
        if ($newStatus == 'active') {
            $notificationObj->createCreditApprovedNotification($creditId);
        } elseif ($newStatus == 'rejected') {
            $notificationObj->createCreditRejectedNotification($creditId);
        }
        
        setMessage('Kredit statusu yeniləndi');
        header('Location: credit_view.php?id=' . $creditId);
        exit();
    } else {
        setMessage('Status yenilənərkən xəta baş verdi', 'error');
    }
}

// Ödəniş qəbul etmə
if (isset($_POST['make_payment'])) {
    $paymentNumber = (int)$_POST['payment_number'];
    $amount = (float)$_POST['amount'];
    
    if ($creditObj->makePayment($creditId, $paymentNumber, $amount)) {
        setMessage('Ödəniş uğurla qəbul edildi');
        header('Location: credit_view.php?id=' . $creditId);
        exit();
    } else {
        setMessage('Ödəniş qəbul edilərkən xəta baş verdi', 'error');
    }
}

$pageTitle = 'Kredit #' . $credit['id'] . ' - Admin Panel';

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
                <h1 class="h2">Kredit Detalları</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="credits.php" class="btn btn-secondary me-2">
                        <i class="fas fa-arrow-left me-2"></i>Geri
                    </a>
                    <?php if ($credit['status'] == 'pending'): ?>
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-success" 
                                    onclick="updateStatus('active')">
                                <i class="fas fa-check me-2"></i>Təsdiq et
                            </button>
                            <button type="button" class="btn btn-danger" 
                                    onclick="updateStatus('rejected')">
                                <i class="fas fa-times me-2"></i>Rədd et
                            </button>
                        </div>
                    <?php endif; ?>
                    <a href="#" class="btn btn-primary" onclick="window.print()">
                        <i class="fas fa-print me-2"></i>Çap et
                    </a>
                </div>
            </div>

            <div class="row">
                <!-- Kredit məlumatları -->
                <div class="col-md-4">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Kredit Məlumatları</h5>
                        </div>
                        <div class="card-body">
                            <div class="text-center mb-4">
                                <span class="badge bg-<?php echo getStatusColor($credit['status']); ?> fs-5">
                                    <?php echo getStatusText($credit['status']); ?>
                                </span>
                            </div>

                            <ul class="list-unstyled">
                                <li class="mb-2">
                                    <strong>Məbləğ:</strong> 
                                    <?php echo number_format($credit['amount'], 2); ?> AZN
                                </li>
                                <li class="mb-2">
                                    <strong>İlkin ödəniş:</strong>
                                    <?php echo number_format($credit['initial_payment'], 2); ?> AZN
                                </li>
                                <li class="mb-2">
                                    <strong>Müddət:</strong>
                                    <?php echo $credit['period_months']; ?> ay
                                </li>
                                <li class="mb-2">
                                    <strong>Faiz dərəcəsi:</strong>
                                    <?php echo $credit['interest_rate']; ?>%
                                </li>
                                <li class="mb-2">
                                    <strong>Aylıq ödəniş:</strong>
                                    <?php echo number_format($credit['monthly_payment'], 2); ?> AZN
                                </li>
                                <li class="mb-2">
                                    <strong>Başlama tarixi:</strong>
                                    <?php echo date('d.m.Y', strtotime($credit['created_at'])); ?>
                                </li>
                                <li>
                                    <strong>Son yenilənmə:</strong>
                                    <?php echo date('d.m.Y', strtotime($credit['updated_at'])); ?>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <!-- Müştəri məlumatları -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Müştəri Məlumatları</h5>
                        </div>
                        <div class="card-body">
                            <div class="text-center mb-4">
                                <i class="fas fa-user-circle fa-3x text-primary"></i>
                                <h5 class="mt-3">
                                    <a href="customer_view.php?id=<?php echo $credit['customer_id']; ?>" 
                                       class="text-decoration-none">
                                        <?php echo $credit['first_name'] . ' ' . $credit['last_name']; ?>
                                    </a>
                                </h5>
                            </div>

                            <ul class="list-unstyled">
                                <li class="mb-2">
                                    <strong>Ata adı:</strong>
                                    <?php echo $credit['father_name']; ?>
                                </li>
                                <li class="mb-2">
                                    <strong>Şəxsiyyət vəsiqəsi:</strong>
                                    <?php echo $credit['id_number']; ?>
                                </li>
                                <li class="mb-2">
                                    <strong>FİN kod:</strong>
                                    <?php echo $credit['fin_code']; ?>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Ödəniş cədvəli -->
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Ödəniş Cədvəli</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>№</th>
                                            <th>Tarix</th>
                                            <th>Məbləğ</th>
                                            <th>Əsas borc</th>
                                            <th>Faiz</th>
                                            <th>Qalıq</th>
                                            <th>Status</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($payments as $payment): ?>
                                        <tr>
                                            <td><?php echo $payment['payment_number']; ?></td>
                                            <td><?php echo date('d.m.Y', strtotime($payment['payment_date'])); ?></td>
                                            <td><?php echo number_format($payment['amount'], 2); ?> AZN</td>
                                            <td><?php echo number_format($payment['principal_amount'], 2); ?> AZN</td>
                                            <td><?php echo number_format($payment['interest_amount'], 2); ?> AZN</td>
                                            <td><?php echo number_format($payment['remaining_balance'], 2); ?> AZN</td>
                                            <td>
                                                <?php if ($payment['status'] == 'paid'): ?>
                                                    <span class="badge bg-success">Ödənilib</span>
                                                <?php elseif ($payment['status'] == 'late'): ?>
                                                    <span class="badge bg-danger">Gecikib</span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning">Gözləyir</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($payment['status'] != 'paid' && $credit['status'] == 'active'): ?>
                                                    <button type="button" class="btn btn-sm btn-primary"
                                                            onclick="makePayment(<?php echo $payment['payment_number']; ?>, <?php echo $payment['amount']; ?>)">
                                                        <i class="fas fa-money-bill"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
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

<!-- Status Modal -->
<div class="modal fade" id="statusModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Kredit Statusunu Dəyiş</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Kredit statusunu dəyişmək istədiyinizə əminsiniz?</p>
            </div>
            <div class="modal-footer">
                <form method="POST">
                    <input type="hidden" name="status" id="newStatus">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Ləğv et</button>
                    <button type="submit" name="update_status" class="btn btn-primary">Təsdiq et</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Ödəniş Modal -->
<div class="modal fade" id="paymentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Ödəniş Qəbulu</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="payment_number" id="paymentNumber">
                    <div class="mb-3">
                        <label class="form-label">Ödəniş məbləği (AZN)</label>
                        <input type="number" name="amount" id="paymentAmount" 
                               class="form-control" step="0.01" required readonly>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Ləğv et</button>
                    <button type="submit" name="make_payment" class="btn btn-success">
                        <i class="fas fa-check me-2"></i>Ödənişi qəbul et
                    </button>
                </div>
            </form>
        </div>
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

// Status dəyişmə
function updateStatus(status) {
    document.getElementById('newStatus').value = status;
    var statusModal = new bootstrap.Modal(document.getElementById('statusModal'));
    statusModal.show();
}

// Ödəniş qəbulu
function makePayment(paymentNumber, amount) {
    document.getElementById('paymentNumber').value = paymentNumber;
    document.getElementById('paymentAmount').value = amount;
    var paymentModal = new bootstrap.Modal(document.getElementById('paymentModal'));
    paymentModal.show();
}
</script>

<?php include '../templates/admin/footer.php'; ?>