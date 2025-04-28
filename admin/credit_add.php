<?php
require_once '../config/config.php';
require_once '../includes/Database.php';
require_once '../includes/Customer.php';
require_once '../includes/Credit.php';
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

$pageTitle = 'Yeni Kredit - Admin Panel';
$customerObj = new Customer();
$creditObj = new Credit();
$notificationObj = new Notification();

$errors = [];

// Form göndərildikdə
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Məlumatları təmizləyirik
    $customerId = (int)$_POST['customer_id'];
    $amount = (float)$_POST['amount'];
    $periodMonths = (int)$_POST['period_months'];
    $interestRate = (float)$_POST['interest_rate'];
    $initialPayment = (float)($_POST['initial_payment'] ?? 0);

    // Validasiya
    if (empty($customerId)) {
        $errors[] = 'Müştəri seçilməyib';
    }
    if ($amount < MIN_CREDIT_AMOUNT || $amount > MAX_CREDIT_AMOUNT) {
        $errors[] = 'Kredit məbləği düzgün deyil';
    }
    if ($periodMonths < MIN_CREDIT_PERIOD || $periodMonths > MAX_CREDIT_PERIOD) {
        $errors[] = 'Kredit müddəti düzgün deyil';
    }
    if ($interestRate < MIN_INTEREST_RATE || $interestRate > MAX_INTEREST_RATE) {
        $errors[] = 'Faiz dərəcəsi düzgün deyil';
    }
    if ($initialPayment >= $amount) {
        $errors[] = 'İlkin ödəniş məbləği kredit məbləğindən çox ola bilməz';
    }

    // Müştərini yoxlayırıq
    $customer = $customerObj->getById($customerId);
    if (!$customer) {
        $errors[] = 'Müştəri tapılmadı';
    } elseif ($customer['status'] !== 'active') {
        $errors[] = 'Müştəri aktiv deyil';
    }

    // Xəta yoxdursa krediti əlavə edirik
    if (empty($errors)) {
        // Aylıq ödənişi hesablayırıq
        $monthlyPayment = Credit::calculateMonthlyPayment($amount, $interestRate, $periodMonths, $initialPayment);

        $data = [
            'customer_id' => $customerId,
            'amount' => $amount,
            'period_months' => $periodMonths,
            'interest_rate' => $interestRate,
            'initial_payment' => $initialPayment,
            'monthly_payment' => $monthlyPayment,
            'status' => 'pending'
        ];

        $creditId = $creditObj->create($data);
        if ($creditId) {
            // Bildiriş göndəririk
            $notificationObj->createCreditApprovedNotification($creditId);
            
            setMessage('Kredit uğurla əlavə edildi');
            header('Location: credit_view.php?id=' . $creditId);
            exit();
        } else {
            $errors[] = 'Kredit əlavə edilərkən xəta baş verdi';
        }
    }
}

// Müştəri siyahısını alırıq
$customers = $customerObj->getAll();

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
                <h1 class="h2">Yeni Kredit</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="credits.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Geri
                    </a>
                </div>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo $error; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-body">
                    <form method="POST" id="creditForm">
                        <!-- Müştəri seçimi -->
                        <div class="mb-3">
                            <label class="form-label">Müştəri *</label>
                            <select name="customer_id" class="form-select" required>
                                <option value="">Müştəri seçin</option>
                                <?php foreach ($customers as $customer): ?>
                                    <?php if ($customer['status'] == 'active'): ?>
                                    <option value="<?php echo $customer['id']; ?>" 
                                            <?php echo isset($_POST['customer_id']) && $_POST['customer_id'] == $customer['id'] ? 'selected' : ''; ?>>
                                        <?php echo $customer['first_name'] . ' ' . $customer['last_name'] . 
                                              ' (' . $customer['fin_code'] . ')'; ?>
                                    </option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="row">
                            <!-- Kredit məbləği -->
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Kredit məbləği (AZN) *</label>
                                <input type="number" name="amount" class="form-control" 
                                       min="<?php echo MIN_CREDIT_AMOUNT; ?>" 
                                       max="<?php echo MAX_CREDIT_AMOUNT; ?>" 
                                       step="100"
                                       value="<?php echo $_POST['amount'] ?? 5000; ?>" 
                                       required>
                                <div class="form-text">
                                    Min: <?php echo MIN_CREDIT_AMOUNT; ?> AZN, 
                                    Max: <?php echo MAX_CREDIT_AMOUNT; ?> AZN
                                </div>
                            </div>

                            <!-- İlkin ödəniş -->
                            <div class="col-md-6 mb-3">
                                <label class="form-label">İlkin ödəniş (AZN)</label>
                                <input type="number" name="initial_payment" class="form-control" 
                                       min="0" step="100"
                                       value="<?php echo $_POST['initial_payment'] ?? 0; ?>">
                            </div>

                            <!-- Kredit müddəti -->
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Kredit müddəti (ay) *</label>
                                <select name="period_months" class="form-select" required>
                                    <?php for ($i = MIN_CREDIT_PERIOD; $i <= MAX_CREDIT_PERIOD; $i++): ?>
                                        <option value="<?php echo $i; ?>" 
                                                <?php echo isset($_POST['period_months']) && $_POST['period_months'] == $i ? 'selected' : ''; ?>>
                                            <?php echo $i; ?> ay
                                        </option>
                                    <?php endfor; ?>
                                </select>
                            </div>

                            <!-- Faiz dərəcəsi -->
                            <div class="col-md-6 mb-3">
                                <label class="form-label">İllik faiz dərəcəsi (%) *</label>
                                <select name="interest_rate" class="form-select" required>
                                    <?php 
                                    for ($i = MIN_INTEREST_RATE; $i <= MAX_INTEREST_RATE; $i += 0.5): 
                                        $selected = isset($_POST['interest_rate']) && $_POST['interest_rate'] == $i;
                                    ?>
                                        <option value="<?php echo $i; ?>" <?php echo $selected ? 'selected' : ''; ?>>
                                            <?php echo $i; ?>%
                                        </option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                        </div>

                        <!-- Kredit hesablaması -->
                        <div class="alert alert-info mb-4" id="creditCalculation" style="display: none;">
                            <h6>Kredit Hesablaması</h6>
                            <div id="calculationDetails"></div>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Kredit əlavə et
                        </button>
                    </form>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Kredit kalkulyatoru
    const form = document.getElementById('creditForm');
    const inputs = form.querySelectorAll('input[type="number"], select');
    
    inputs.forEach(input => {
        input.addEventListener('change', calculateCredit);
    });

    // İlk hesablama
    calculateCredit();

    function calculateCredit() {
        const amount = parseFloat(form.amount.value) || 0;
        const period = parseInt(form.period_months.value) || 0;
        const rate = parseFloat(form.interest_rate.value) || 0;
        const initialPayment = parseFloat(form.initial_payment.value) || 0;

        if (amount && period && rate) {
            // API-dən hesablama
            fetch(`../api/credits.php?action=calculate&amount=${amount}&period=${period}&rate=${rate}&initial_payment=${initialPayment}`)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('creditCalculation').style.display = 'block';
                    document.getElementById('calculationDetails').innerHTML = `
                        <p class="mb-1">Kredit məbləği: ${amount.toFixed(2)} AZN</p>
                        <p class="mb-1">İlkin ödəniş: ${initialPayment.toFixed(2)} AZN</p>
                        <p class="mb-1">Kredit müddəti: ${period} ay</p>
                        <p class="mb-1">İllik faiz dərəcəsi: ${rate}%</p>
                        <p class="mb-1">Aylıq ödəniş: ${data.monthly_payment} AZN</p>
                        <p class="mb-0">Ümumi ödəniləcək məbləğ: ${data.total_amount} AZN</p>
                    `;
                })
                .catch(error => {
                    console.error('Hesablama xətası:', error);
                });
        }
    }
});
</script>

<?php include '../templates/admin/footer.php'; ?>