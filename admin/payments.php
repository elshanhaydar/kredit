<?php
require_once '../config/config.php';
require_once '../includes/Database.php';
require_once '../includes/Credit.php';
require_once '../includes/Customer.php';

// Session başlatma yoxlaması
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Admin session yoxlanışı
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

$pageTitle = 'Ödənişlər - Admin Panel';
$creditObj = new Credit();
$customerObj = new Customer();

// Ödəniş qəbul etmə
if (isset($_POST['make_payment'])) {
    $creditId = (int)$_POST['credit_id'];
    $paymentNumber = (int)$_POST['payment_number'];
    $amount = (float)$_POST['amount'];
    
    if ($creditObj->makePayment($creditId, $paymentNumber, $amount)) {
        setMessage('Ödəniş uğurla qəbul edildi');
    } else {
        setMessage('Ödəniş qəbul edilərkən xəta baş verdi', 'error');
    }
    
    header('Location: payments.php');
    exit();
}

// Filtrləmə
$status = $_GET['status'] ?? '';
$startDate = $_GET['start_date'] ?? date('Y-m-01'); // Cari ayın əvvəli
$endDate = $_GET['end_date'] ?? date('Y-m-t'); // Cari ayın sonu

// Ödənişləri alırıq
$sql = "SELECT p.*, c.amount as credit_amount, c.monthly_payment, 
               cs.first_name, cs.last_name, cs.fin_code
        FROM payments p
        JOIN credits c ON p.credit_id = c.id
        JOIN customers cs ON c.customer_id = cs.id
        WHERE p.payment_date BETWEEN :start_date AND :end_date";

if ($status) {
    $sql .= " AND p.status = :status";
}

$sql .= " ORDER BY p.payment_date DESC";

$params = [
    ':start_date' => $startDate,
    ':end_date' => $endDate
];

if ($status) {
    $params[':status'] = $status;
}

$db = Database::getInstance();
$payments = $db->select($sql, $params);

// Header
include '../templates/admin/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <?php include '../templates/admin/sidebar.php'; ?>

        <!-- Əsas Content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center mb-3">
                <h1 class="h2">Ödənişlər</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <button type="button" class="btn btn-primary" onclick="window.print()">
                        <i class="fas fa-print me-2"></i>Çap et
                    </button>
                </div>
            </div>

            <!-- Filter Panel -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <!-- Status filter -->
                        <div class="col-md-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="">Hamısı</option>
                                <option value="pending" <?php echo $status == 'pending' ? 'selected' : ''; ?>>Gözləyir</option>
                                <option value="paid" <?php echo $status == 'paid' ? 'selected' : ''; ?>>Ödənilib</option>
                                <option value="late" <?php echo $status == 'late' ? 'selected' : ''; ?>>Gecikib</option>
                            </select>
                        </div>

                        <!-- Tarix aralığı -->
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

                        <div class="col-md-3 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary me-2">
                                <i class="fas fa-search me-2"></i>Axtar
                            </button>
                            <a href="payments.php" class="btn btn-secondary">
                                <i class="fas fa-times me-2"></i>Təmizlə
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Statistika -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Ümumi ödənişlər</h5>
                            <h2>
                                <?php 
                                $totalPayments = array_reduce($payments, function($carry, $payment) {
                                    return $carry + $payment['amount'];
                                }, 0);
                                echo number_format($totalPayments, 2);
                                ?> AZN
                            </h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Ödənilmiş</h5>
                            <h2>
                                <?php 
                                $paidPayments = array_reduce($payments, function($carry, $payment) {
                                    return $payment['status'] == 'paid' ? $carry + $payment['amount'] : $carry;
                                }, 0);
                                echo number_format($paidPayments, 2);
                                ?> AZN
                            </h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Gözləyən</h5>
                            <h2>
                                <?php 
                                $pendingPayments = array_reduce($payments, function($carry, $payment) {
                                    return $payment['status'] == 'pending' ? $carry + $payment['amount'] : $carry;
                                }, 0);
                                echo number_format($pendingPayments, 2);
                                ?> AZN
                            </h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Gecikən</h5>
                            <h2>
                                <?php 
                                $latePayments = array_reduce($payments, function($carry, $payment) {
                                    return $payment['status'] == 'late' ? $carry + $payment['amount'] : $carry;
                                }, 0);
                                echo number_format($latePayments, 2);
                                ?> AZN
                            </h2>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Ödəniş Cədvəli -->
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped datatable">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Müştəri</th>
                                    <th>Kredit</th>
                                    <th>Ödəniş №</th>
                                    <th>Məbləğ</th>
                                    <th>Ödəniş tarixi</th>
                                    <th>Ödənilmə tarixi</th>
                                    <th>Status</th>
                                    <th>Əməliyyatlar</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($payments as $payment): ?>
                                <tr>
                                    <td><?php echo $payment['id']; ?></td>
                                    <td>
                                        <a href="customer_view.php?id=<?php echo $payment['customer_id']; ?>" 
                                           class="text-decoration-none">
                                            <?php echo $payment['first_name'] . ' ' . $payment['last_name']; ?>
                                        </a>
                                        <br>
                                        <small class="text-muted"><?php echo $payment['fin_code']; ?></small>
                                    </td>
                                    <td>
                                        <a href="credit_view.php?id=<?php echo $payment['credit_id']; ?>" 
                                           class="text-decoration-none">
                                            Kredit #<?php echo $payment['credit_id']; ?>
                                        </a>
                                    </td>
                                    <td><?php echo $payment['payment_number']; ?></td>
                                    <td><?php echo number_format($payment['amount'], 2); ?> AZN</td>
                                    <td><?php echo date('d.m.Y', strtotime($payment['payment_date'])); ?></td>
                                    <td>
                                        <?php 
                                        if ($payment['paid_date']) {
                                            echo date('d.m.Y H:i', strtotime($payment['paid_date']));
                                        } else {
                                            echo '-';
                                        }
                                        ?>
                                    </td>
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
                                        <?php if ($payment['status'] != 'paid'): ?>
                                            <button type="button" class="btn btn-sm btn-primary"
                                                    onclick="makePayment(<?php echo $payment['credit_id']; ?>, <?php echo $payment['payment_number']; ?>, <?php echo $payment['amount']; ?>)">
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
        </main>
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
                    <input type="hidden" name="credit_id" id="paymentCreditId">
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
// Ödəniş qəbulu
function makePayment(creditId, paymentNumber, amount) {
    document.getElementById('paymentCreditId').value = creditId;
    document.getElementById('paymentNumber').value = paymentNumber;
    document.getElementById('paymentAmount').value = amount;
    
    var paymentModal = new bootstrap.Modal(document.getElementById('paymentModal'));
    paymentModal.show();
}

// DataTables inisializasiyası
$(document).ready(function() {
    $('.datatable').DataTable({
        order: [[5, 'asc']], // Ödəniş tarixinə görə sıralama
        pageLength: 50
    });
});
</script>

<?php include '../templates/admin/footer.php'; ?>