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
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

$creditObj = new Credit();
$customerObj = new Customer();
$notificationObj = new Notification();

// Kredit statusunu yeniləmək
if (isset($_POST['update_status'])) {
    $creditId = (int)$_POST['credit_id'];
    $newStatus = $_POST['status'];
    
    if ($creditObj->updateStatus($creditId, $newStatus)) {
        // Status yeniləndikdən sonra bildiriş göndəririk
        if ($newStatus == CREDIT_STATUS_ACTIVE) {
            $notificationObj->createCreditApprovedNotification($creditId);
        } elseif ($newStatus == CREDIT_STATUS_REJECTED) {
            $notificationObj->createCreditRejectedNotification($creditId);
        }
        setMessage('Kredit statusu uğurla yeniləndi');
    } else {
        setMessage('Status yenilənərkən xəta baş verdi', 'error');
    }
    header('Location: credits.php');
    exit();
}

// Ödəniş qeydə almaq
if (isset($_POST['make_payment'])) {
    $creditId = (int)$_POST['credit_id'];
    $paymentNumber = (int)$_POST['payment_number'];
    $amount = (float)$_POST['amount'];
    
    if ($creditObj->makePayment($creditId, $paymentNumber, $amount)) {
        setMessage('Ödəniş uğurla qeydə alındı');
    } else {
        setMessage('Ödəniş qeydə alınarkən xəta baş verdi', 'error');
    }
    header('Location: credits.php');
    exit();
}

// Filtrlər
$status = $_GET['status'] ?? '';
$filters = [];
if ($status) {
    $filters['status'] = $status;
}

// Kreditləri alırıq
$credits = $creditObj->getAll($filters);

// Mesajları yoxlayırıq
$message = getMessage();
?>
<!DOCTYPE html>
<html lang="az">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kreditlər - Admin Panel</title>
    
    <!-- CSS -->
    <link href="../assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/fontawesome.min.css" rel="stylesheet">
    <link href="../assets/css/datatables.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
    <!-- Admin Header -->
    <?php include '../templates/header.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php include '../templates/admin/sidebar.php'; ?>

            <!-- Əsas Content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center mb-3">
                    <h1 class="h2">Kreditlər</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="credit_add.php" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Yeni Kredit
                        </a>
                    </div>
                </div>

                <?php if ($message): ?>
                <div class="alert alert-<?php echo $message['type'] == 'error' ? 'danger' : 'success'; ?> alert-dismissible fade show">
                    <?php echo $message['message']; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <!-- Filter Paneli -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select" onchange="this.form.submit()">
                                    <option value="">Bütün</option>
                                    <option value="pending" <?php echo $status == 'pending' ? 'selected' : ''; ?>>Gözləmədə</option>
                                    <option value="active" <?php echo $status == 'active' ? 'selected' : ''; ?>>Aktiv</option>
                                    <option value="completed" <?php echo $status == 'completed' ? 'selected' : ''; ?>>Tamamlanmış</option>
                                    <option value="delayed" <?php echo $status == 'delayed' ? 'selected' : ''; ?>>Gecikmiş</option>
                                    <option value="rejected" <?php echo $status == 'rejected' ? 'selected' : ''; ?>>Rədd edilmiş</option>
                                </select>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Kredit Cədvəli -->
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover datatable">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Müştəri</th>
                                        <th>Məbləğ</th>
                                        <th>Müddət</th>
                                        <th>Faiz</th>
                                        <th>Aylıq Ödəniş</th>
                                        <th>Status</th>
                                        <th>Tarix</th>
                                        <th>Əməliyyatlar</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($credits as $credit): ?>
                                    <tr>
                                        <td><?php echo $credit['id']; ?></td>
                                        <td><?php echo $credit['first_name'] . ' ' . $credit['last_name']; ?></td>
                                        <td><?php echo number_format($credit['amount'], 2); ?> AZN</td>
                                        <td><?php echo $credit['period_months']; ?> ay</td>
                                        <td><?php echo $credit['interest_rate']; ?>%</td>
                                        <td><?php echo number_format($credit['monthly_payment'], 2); ?> AZN</td>
                                        <td>
                                            <span class="badge bg-<?php echo getStatusColor($credit['status']); ?>">
                                                <?php echo getStatusText($credit['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('d.m.Y', strtotime($credit['created_at'])); ?></td>
                                        <td>
                                            <div class="btn-group">
                                                <a href="credit_view.php?id=<?php echo $credit['id']; ?>" 
                                                   class="btn btn-sm btn-info" title="Ətraflı">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <?php if ($credit['status'] == 'pending'): ?>
                                                <button type="button" 
                                                        class="btn btn-sm btn-success" 
                                                        onclick="updateStatus(<?php echo $credit['id']; ?>, 'active')"
                                                        title="Təsdiq et">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                                <button type="button" 
                                                        class="btn btn-sm btn-danger" 
                                                        onclick="updateStatus(<?php echo $credit['id']; ?>, 'rejected')"
                                                        title="Rədd et">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                                <?php endif; ?>
                                                <?php if ($credit['status'] == 'active'): ?>
                                                <button type="button" 
                                                        class="btn btn-sm btn-primary" 
                                                        onclick="showPaymentModal(<?php echo $credit['id']; ?>)"
                                                        title="Ödəniş et">
                                                    <i class="fas fa-money-bill"></i>
                                                </button>
                                                <?php endif; ?>
                                            </div>
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

    <!-- Status Yeniləmə Modal -->
    <div class="modal fade" id="statusModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Kredit Statusunu Yenilə</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
             <input type="hidden" name="credit_id" id="statusCreditId">
                        <input type="hidden" name="status" id="newStatus">
                        <p>Kredit statusunu yeniləmək istədiyinizə əminsiniz?</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Ləğv et</button>
                        <button type="submit" name="update_status" class="btn btn-primary">Təsdiq et</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Ödəniş Modal -->
    <div class="modal fade" id="paymentModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Ödəniş Et</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="paymentForm">
                    <div class="modal-body">
                        <input type="hidden" name="credit_id" id="paymentCreditId">
                        
                        <div class="mb-3">
                            <label class="form-label">Ödəniş №</label>
                            <select name="payment_number" id="paymentNumber" class="form-select" required>
                                <!-- JavaScript ilə doldurulacaq -->
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Məbləğ</label>
                            <div class="input-group">
                                <input type="number" name="amount" id="paymentAmount" 
                                       class="form-control" step="0.01" required>
                                <span class="input-group-text">AZN</span>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Ləğv et</button>
                        <button type="submit" name="make_payment" class="btn btn-success">Ödəniş et</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- JavaScript -->
    <script src="../assets/js/jquery.min.js"></script>
    <script src="../assets/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/datatables.min.js"></script>
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

        // Status yeniləmə modalı
        function updateStatus(creditId, status) {
            document.getElementById('statusCreditId').value = creditId;
            document.getElementById('newStatus').value = status;
            
            var statusModal = new bootstrap.Modal(document.getElementById('statusModal'));
            statusModal.show();
        }

        // Ödəniş modalı
        function showPaymentModal(creditId) {
            document.getElementById('paymentCreditId').value = creditId;
            
            // Ödəniş məlumatlarını gətiririk
            fetch(`api/get_payments.php?credit_id=${creditId}`)
                .then(response => response.json())
                .then(data => {
                    const select = document.getElementById('paymentNumber');
                    select.innerHTML = '';
                    
                    data.forEach(payment => {
                        if (payment.status === 'pending') {
                            const option = new Option(
                                `Ödəniş ${payment.payment_number} - ${payment.payment_date}`, 
                                payment.payment_number
                            );
                            select.add(option);
                        }
                    });

                    // İlk ödənişin məbləğini yazırıq
                    if (data.length > 0) {
                        document.getElementById('paymentAmount').value = data[0].amount;
                    }
                });
            
            var paymentModal = new bootstrap.Modal(document.getElementById('paymentModal'));
            paymentModal.show();
        }

        // Ödəniş nömrəsi dəyişdikdə məbləği yeniləyirik
        document.getElementById('paymentNumber').addEventListener('change', function() {
            const creditId = document.getElementById('paymentCreditId').value;
            const paymentNumber = this.value;
            
            fetch(`api/get_payment_amount.php?credit_id=${creditId}&payment_number=${paymentNumber}`)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('paymentAmount').value = data.amount;
                });
        });

        // Status rəngi funksiyası
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

        // Status mətni funksiyası
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
    </script>
</body>
</html>