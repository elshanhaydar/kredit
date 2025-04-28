<?php
require_once 'config/config.php';
require_once 'includes/Database.php';
require_once 'includes/Credit.php';
require_once 'includes/Customer.php';
require_once 'includes/Notification.php';

// Session yoxlaması
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$pageTitle = 'Şəxsi Kabinet - ' . SITE_NAME;

$creditObj = new Credit();
$customerObj = new Customer();
$notificationObj = new Notification();

// Müştəri məlumatları
$customer = $customerObj->getById($_SESSION['user_id']);

// Kreditlər
$credits = $creditObj->getAll(['customer_id' => $_SESSION['user_id']]);

// Aktiv kreditləri filtirləyirik
$activeCredits = array_filter($credits, function($credit) {
    return $credit['status'] == CREDIT_STATUS_ACTIVE;
});

// Son bildirişlər
$notifications = $notificationObj->getCustomerNotifications($_SESSION['user_id'], 5);

// Header
include 'templates/header.php';
?>

<div class="container py-5">
    <div class="row">
        <!-- Sol Panel -->
        <div class="col-lg-4">
            <!-- Profil Kartı -->
            <div class="card shadow mb-4">
                <div class="card-body text-center">
                    <div class="mb-3">
                        <i class="fas fa-user-circle fa-5x text-primary"></i>
                    </div>
                    <h5 class="card-title mb-0">
                        <?php echo $customer['first_name'] . ' ' . $customer['last_name']; ?>
                    </h5>
                    <p class="text-muted mb-3"><?php echo $customer['email']; ?></p>
                    <hr>
                    <div class="text-start">
                        <p class="mb-2">
                            <i class="fas fa-id-card me-2"></i>
                            <?php echo $customer['id_number']; ?>
                        </p>
                        <p class="mb-2">
                            <i class="fas fa-fingerprint me-2"></i>
                            <?php echo $customer['fin_code']; ?>
                        </p>
                        <p class="mb-0">
                            <i class="fas fa-calendar me-2"></i>
                            Qeydiyyat: <?php echo date('d.m.Y', strtotime($customer['created_at'])); ?>
                        </p>
                    </div>
                    <hr>
                    <a href="profile.php" class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-edit me-2"></i>Profili düzəlt
                    </a>
                </div>
            </div>

            <!-- Kredit Statistikası -->
            <div class="card shadow mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Kredit Statistikası</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-3">
                        <div>Ümumi kreditlər</div>
                        <div class="fw-bold"><?php echo count($credits); ?></div>
                    </div>
                    <div class="d-flex justify-content-between mb-3">
                        <div>Aktiv kreditlər</div>
                        <div class="fw-bold"><?php echo count($activeCredits); ?></div>
                    </div>
                    <div class="d-flex justify-content-between">
                        <div>Ümumi məbləğ</div>
                        <div class="fw-bold">
                            <?php
                            $totalAmount = array_reduce($credits, function($carry, $credit) {
                                return $carry + $credit['amount'];
                            }, 0);
                            echo number_format($totalAmount, 2) . ' AZN';
                            ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Son Bildirişlər -->
            <div class="card shadow">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Son Bildirişlər</h5>
                    <a href="notifications.php" class="btn btn-sm btn-primary">
                        Hamısı
                    </a>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        <?php foreach ($notifications as $notification): ?>
                        <div class="list-group-item <?php echo $notification['is_read'] ? '' : 'bg-light'; ?>">
                            <div class="d-flex justify-content-between align-items-center">
                                <small class="text-muted">
                                    <?php echo date('d.m.Y H:i', strtotime($notification['created_at'])); ?>
                                </small>
                                <?php if (!$notification['is_read']): ?>
                                <span class="badge bg-primary">Yeni</span>
                                <?php endif; ?>
                            </div>
                            <p class="mb-0 mt-1"><?php echo $notification['message']; ?></p>
                        </div>
                        <?php endforeach; ?>
                        <?php if (empty($notifications)): ?>
                        <div class="list-group-item text-center text-muted">
                            Bildiriş yoxdur
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sağ Panel -->
        <div class="col-lg-8">
            <!-- Aktiv Kreditlər -->
            <div class="card shadow mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Aktiv Kreditlər</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($activeCredits)): ?>
                        <?php foreach ($activeCredits as $credit): ?>
                            <?php
                            // Növbəti ödənişi tapırıq
                            $payments = $creditObj->getPayments($credit['id']);
                            $nextPayment = null;
                            foreach ($payments as $payment) {
                                if ($payment['status'] == 'pending') {
                                    $nextPayment = $payment;
                                    break;
                                }
                            }
                            ?>
                            <div class="credit-card mb-4">
                                <div class="row">
                                    <div class="col-md-6">
                                        <h6>Kredit #<?php echo $credit['id']; ?></h6>
                                        <p class="mb-1">Məbləğ: <?php echo number_format($credit['amount'], 2); ?> AZN</p>
                                        <p class="mb-1">Müddət: <?php echo $credit['period_months']; ?> ay</p>
                                        <p class="mb-1">Faiz: <?php echo $credit['interest_rate']; ?>%</p>
                                        <p class="mb-0">
                                            Tarix: <?php echo date('d.m.Y', strtotime($credit['created_at'])); ?>
                                        </p>
                                    </div>
                                    <div class="col-md-6">
                                        <?php if ($nextPayment): ?>
                                        <div class="alert alert-info mb-0">
                                            <h6>Növbəti ödəniş</h6>
                                            <p class="mb-1">Məbləğ: <?php echo number_format($nextPayment['amount'], 2); ?> AZN</p>
                                            <p class="mb-1">Tarix: <?php echo date('d.m.Y', strtotime($nextPayment['payment_date'])); ?></p>
                                            <button type="button" class="btn btn-primary btn-sm mt-2"
                                                    onclick="makePayment(<?php echo $credit['id']; ?>, <?php echo $nextPayment['payment_number']; ?>)">
                                                <i class="fas fa-money-bill me-2"></i>Ödəniş et
                                            </button>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-info-circle fa-2x mb-3"></i>
                            <p class="mb-0">Aktiv kredit yoxdur</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Kredit Tarixçəsi -->
            <div class="card shadow">
                <div class="card-header">
                    <h5 class="card-title mb-0">Kredit Tarixçəsi</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
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
                                    <td><?php echo date('d.m.Y', strtotime($credit['created_at'])); ?></td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-info" 
                                                onclick="showCreditDetails(<?php echo $credit['id']; ?>)">
                                            <i class="fas fa-eye"></i>
                                        </button>
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
</div>

<!-- Kredit Detalları Modal -->
<div class="modal fade" id="creditDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Kredit Detalları</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <!-- Yüklənmə animasiyası -->
                <div class="text-center py-4" id="creditDetailsLoader">
                    <div class="spinner-border text-primary"></div>
                </div>
                <!-- Kredit məlumatları -->
                <div id="creditDetailsContent" style="display: none;">
                    <!-- JavaScript ilə doldurulacaq -->
                </div>
            </div>
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
            <div class="modal-body">
                <div id="paymentDetails">
                    <!-- JavaScript ilə doldurulacaq -->
                </div>
                <form id="paymentForm" class="mt-3">
                    <input type="hidden" id="paymentCreditId">
                    <input type="hidden" id="paymentNumber">
                    <div class="mb-3">
                        <label class="form-label">Kart nömrəsi</label>
                        <input type="text" class="form-control" id="cardNumber" required
                               pattern="\d{4} \d{4} \d{4} \d{4}" placeholder="0000 0000 0000 0000">
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Bitmə tarixi</label>
                                <input type="text" class="form-control" id="cardExpiry" required
                                       pattern="\d{2}/\d{2}" placeholder="MM/YY">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">CVV</label>
                                <input type="text" class="form-control" id="cardCvv" required
                                       pattern="\d{3}" placeholder="000">
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Ləğv et</button>
                <button type="button" class="btn btn-primary" onclick="processPayment()">Ödəniş et</button>
            </div>
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

// Kredit detallarını göstərmək
function showCreditDetails(creditId) {
    // Modal göstəririk
    const modal = new bootstrap.Modal(document.getElementById('creditDetailsModal'));
    modal.show();

    // Loader göstəririk
    document.getElementById('creditDetailsLoader').style.display = 'block';
    document.getElementById('creditDetailsContent').style.display = 'none';

    // Kredit məlumatlarını API-dən alırıq
    fetch(`api/credits.php?action=get&id=${creditId}`)
        .then(response => response.json())
        .then(credit => {
            // Ödəniş cədvəlini alırıq
            return fetch(`api/credits.php?action=payments&id=${creditId}`)
                .then(response => response.json())
                .then(payments => {
                    return { credit, payments };
                });
        })
        .then(data => {
            // Kredit məlumatlarını hazırlayırıq
            let html = `
                <div class="mb-4">
                    <h6>Kredit Məlumatları</h6>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <tr>
                                <th>Kredit №:</th>
                                <td>${data.credit.id}</td>
                                <th>Status:</th>
                                <td>
                                    <span class="badge bg-${getStatusColor(data.credit.status)}">
                                        ${getStatusText(data.credit.status)}
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <th>Məbləğ:</th>
                                <td>${Number(data.credit.amount).toFixed(2)} AZN</td>
                                <th>İlkin ödəniş:</th>
                                <td>${Number(data.credit.initial_payment).toFixed(2)} AZN</td>
                            </tr>
                            <tr>
                                <th>Müddət:</th>
                                <td>${data.credit.period_months} ay</td>
                                <th>Faiz:</th>
                                <td>${data.credit.interest_rate}%</td>
                            </tr>
                            <tr>
                                <th>Aylıq ödəniş:</th>
                                <td>${Number(data.credit.monthly_payment).toFixed(2)} AZN</td>
                                <th>Tarix:</th>
                                <td>${new Date(data.credit.created_at).toLocaleDateString()}</td>
                            </tr>
                        </table>
                    </div>
                </div>

                <h6>Ödəniş Cədvəli</h6>
                <div class="table-responsive">
                    <table class="table table-sm table-striped">
                        <thead>
                            <tr>
                                <th>№</th>
                                <th>Tarix</th>
                                <th>Məbləğ</th>
                                <th>Əsas borc</th>
                                <th>Faiz</th>
                                <th>Qalıq</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
            `;

            // Ödəniş cədvəlini əlavə edirik
            data.payments.forEach(payment => {
                html += `
                    <tr>
                        <td>${payment.payment_number}</td>
                        <td>${new Date(payment.payment_date).toLocaleDateString()}</td>
                        <td>${Number(payment.amount).toFixed(2)} AZN</td>
                        <td>${Number(payment.principal_amount).toFixed(2)} AZN</td>
                        <td>${Number(payment.interest_amount).toFixed(2)} AZN</td>
                        <td>${Number(payment.remaining_balance).toFixed(2)} AZN</td>
                        <td>
                            <span class="badge bg-${payment.status === 'paid' ? 'success' : 'warning'}">
                                ${payment.status === 'paid' ? 'Ödənilib' : 'Gözləyir'}
                            </span>
                        </td>
                    </tr>
                `;
            });

            html += `
                        </tbody>
                    </table>
                </div>
            `;

            // Məlumatları göstəririk
            document.getElementById('creditDetailsLoader').style.display = 'none';
            document.getElementById('creditDetailsContent').style.display = 'block';
            document.getElementById('creditDetailsContent').innerHTML = html;
        })
        .catch(error => {
            console.error('Xəta:', error);
            document.getElementById('creditDetailsContent').innerHTML = `
                <div class="alert alert-danger">
                    Məlumatlar yüklənərkən xəta baş verdi
                </div>
            `;
        });
}

// Ödəniş etmək
function makePayment(creditId, paymentNumber) {
    // Ödəniş məlumatlarını alırıq
    fetch(`api/credits.php?action=payments&id=${creditId}`)
        .then(response => response.json())
        .then(payments => {
            const payment = payments.find(p => p.payment_number === paymentNumber);
            if (!payment) throw new Error('Ödəniş tapılmadı');

            // Ödəniş məlumatlarını göstəririk
            document.getElementById('paymentDetails').innerHTML = `
                <div class="alert alert-info">
                    <h6>Ödəniş Məlumatları</h6>
                    <p class="mb-1">Ödəniş №: ${payment.payment_number}</p>
                    <p class="mb-1">Məbləğ: ${Number(payment.amount).toFixed(2)} AZN</p>
                    <p class="mb-0">Son ödəniş tarixi: ${new Date(payment.payment_date).toLocaleDateString()}</p>
                </div>
            `;

            // Form məlumatlarını təmizləyirik
            document.getElementById('paymentForm').reset();
            document.getElementById('paymentCreditId').value = creditId;
            document.getElementById('paymentNumber').value = paymentNumber;

            // Modalı göstəririk
            const modal = new bootstrap.Modal(document.getElementById('paymentModal'));
            modal.show();
        })
        .catch(error => {
            console.error('Xəta:', error);
            alert('Ödəniş məlumatları alınarkən xəta baş verdi');
        });
}

// Ödənişi həyata keçirmək
function processPayment() {
    const form = document.getElementById('paymentForm');
    if (form.checkValidity()) {
        const creditId = document.getElementById('paymentCreditId').value;
        const paymentNumber = document.getElementById('paymentNumber').value;
        const amount = parseFloat(document.getElementById('paymentDetails')
            .querySelector('p:nth-child(3)').textContent.split(': ')[1]);

        // Ödənişi göndəririk
        fetch('api/credits.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'make_payment',
                credit_id: creditId,
                payment_number: paymentNumber,
                amount: amount
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Modalı bağlayırıq
                bootstrap.Modal.getInstance(document.getElementById('paymentModal')).hide();
                
                // Səhifəni yeniləyirik
                location.reload();
            } else {
                throw new Error(data.error || 'Ödəniş zamanı xəta baş verdi');
            }
        })
        .catch(error => {
            console.error('Xəta:', error);
            alert(error.message);
        });
    } else {
        form.reportValidity();
    }
}

// Kart nömrəsi formatı
document.getElementById('cardNumber').addEventListener('input', function(e) {
    let value = e.target.value.replace(/\D/g, '');
    let formatted = '';
    for (let i = 0; i < value.length && i < 16; i++) {
        if (i > 0 && i % 4 === 0) {
            formatted += ' ';
        }
        formatted += value[i];
    }
    e.target.value = formatted;
});

// Kart bitmə tarixi formatı
document.getElementById('cardExpiry').addEventListener('input', function(e) {
    let value = e.target.value.replace(/\D/g, '');
    let formatted = '';
    if (value.length > 0) {
        formatted = value.substr(0, 2);
        if (value.length > 2) {
            formatted += '/' + value.substr(2, 2);
        }
    }
    e.target.value = formatted;
});

// CVV formatı
document.getElementById('cardCvv').addEventListener('input', function(e) {
    e.target.value = e.target.value.replace(/\D/g, '').substr(0, 3);
});
</script>

<?php
// Footer
include 'templates/footer.php';
?>