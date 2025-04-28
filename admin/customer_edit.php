<?php
require_once '../config/config.php';
require_once '../includes/Database.php';
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

// ID yoxlanışı
if (!isset($_GET['id'])) {
    header('Location: customers.php');
    exit();
}

$customerId = (int)$_GET['id'];
$customerObj = new Customer();
$errors = [];

// Müştəri məlumatlarını alırıq
$customer = $customerObj->getById($customerId);
if (!$customer) {
    setMessage('Müştəri tapılmadı', 'error');
    header('Location: customers.php');
    exit();
}

// Form göndərildikdə
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Məlumatları təmizləyirik
    $firstName = clean($_POST['first_name']);
    $lastName = clean($_POST['last_name']);
    $fatherName = clean($_POST['father_name']);
    $idNumber = clean($_POST['id_number']);
    $finCode = clean($_POST['fin_code']);
    $email = clean($_POST['email']);
    $status = clean($_POST['status']);
    
    // Şifrə dəyişikliyi
    $password = !empty($_POST['password']) ? $_POST['password'] : null;
    $passwordConfirm = !empty($_POST['password_confirm']) ? $_POST['password_confirm'] : null;

    // Validasiya
    if (empty($firstName)) {
        $errors[] = 'Ad daxil edilməyib';
    }
    if (empty($lastName)) {
        $errors[] = 'Soyad daxil edilməyib';
    }
    if (empty($fatherName)) {
        $errors[] = 'Ata adı daxil edilməyib';
    }
    if (empty($idNumber)) {
        $errors[] = 'Şəxsiyyət vəsiqəsinin seriya və nömrəsi daxil edilməyib';
    } elseif (!preg_match('/^[A-Z]{2}[0-9]{7}$/', $idNumber)) {
        $errors[] = 'Şəxsiyyət vəsiqəsinin seriya və nömrəsi düzgün formatda deyil';
    }
    if (empty($finCode)) {
        $errors[] = 'FİN kod daxil edilməyib';
    } elseif (strlen($finCode) !== 7) {
        $errors[] = 'FİN kod 7 simvoldan ibarət olmalıdır';
    }
    if (empty($email)) {
        $errors[] = 'Email daxil edilməyib';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Email düzgün formatda deyil';
    }

    // Şifrə yoxlanışı
    if ($password !== null) {
        if (strlen($password) < 6) {
            $errors[] = 'Şifrə minimum 6 simvoldan ibarət olmalıdır';
        }
        if ($password !== $passwordConfirm) {
            $errors[] = 'Şifrələr uyğun gəlmir';
        }
    }

    // FIN və şəxsiyyət vəsiqəsi yoxlanışı
    if ($customerObj->isFinCodeExists($finCode, $customerId)) {
        $errors[] = 'Bu FİN kod artıq qeydiyyatdan keçib';
    }
    if ($customerObj->isIdNumberExists($idNumber, $customerId)) {
        $errors[] = 'Bu şəxsiyyət vəsiqəsi artıq qeydiyyatdan keçib';
    }

    // Xəta yoxdursa müştərini yeniləyirik
    if (empty($errors)) {
        $data = [
            'first_name' => $firstName,
            'last_name' => $lastName,
            'father_name' => $fatherName,
            'id_number' => $idNumber,
            'fin_code' => $finCode,
            'email' => $email,
            'status' => $status
        ];

        // Şifrə dəyişdirilibsə əlavə edirik
        if ($password !== null) {
            $data['password'] = password_hash($password, PASSWORD_DEFAULT);
        }

        if ($customerObj->update($customerId, $data)) {
            setMessage('Müştəri məlumatları yeniləndi');
            header('Location: customer_view.php?id=' . $customerId);
            exit();
        } else {
            $errors[] = 'Məlumatlar yenilənərkən xəta baş verdi';
        }
    }
}

$pageTitle = $customer['first_name'] . ' ' . $customer['last_name'] . ' - Düzəliş';

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
                <h1 class="h2">Müştəri Məlumatlarında Düzəliş</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="customer_view.php?id=<?php echo $customerId; ?>" class="btn btn-secondary me-2">
                        <i class="fas fa-arrow-left me-2"></i>Geri
                    </a>
                    <a href="customers.php" class="btn btn-primary">
                        <i class="fas fa-users me-2"></i>Bütün müştərilər
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
                    <form method="POST" id="customerForm" class="needs-validation" novalidate>
                        <div class="row">
                            <!-- Ad -->
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Ad *</label>
                                <input type="text" name="first_name" class="form-control" 
                                       value="<?php echo $customer['first_name']; ?>" required>
                                <div class="invalid-feedback">Ad daxil edilməyib</div>
                            </div>

                            <!-- Soyad -->
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Soyad *</label>
                                <input type="text" name="last_name" class="form-control" 
                                       value="<?php echo $customer['last_name']; ?>" required>
                                <div class="invalid-feedback">Soyad daxil edilməyib</div>
                            </div>

                            <!-- Ata adı -->
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Ata adı *</label>
                                <input type="text" name="father_name" class="form-control" 
                                       value="<?php echo $customer['father_name']; ?>" required>
                                <div class="invalid-feedback">Ata adı daxil edilməyib</div>
                            </div>

                            <!-- Şəxsiyyət vəsiqəsi -->
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Şəxsiyyət vəsiqəsinin seriya və nömrəsi *</label>
                                <input type="text" name="id_number" class="form-control" 
                                       value="<?php echo $customer['id_number']; ?>" 
                                       pattern="[A-Z]{2}[0-9]{7}"
                                       placeholder="Məs: AA1234567" required>
                                <div class="invalid-feedback">
                                    Şəxsiyyət vəsiqəsinin seriya və nömrəsi düzgün deyil (Məs: AA1234567)
                                </div>
                            </div>

                            <!-- FİN kod -->
                            <div class="col-md-6 mb-3">
                                <label class="form-label">FİN kod *</label>
                                <input type="text" name="fin_code" class="form-control" 
                                       value="<?php echo $customer['fin_code']; ?>" 
                                       pattern=".{7,7}"
                                       maxlength="7" required>
                                <div class="invalid-feedback">FİN kod 7 simvoldan ibarət olmalıdır</div>
                            </div>

                            <!-- Email -->
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email *</label>
                                <input type="email" name="email" class="form-control" 
                                       value="<?php echo $customer['email']; ?>" required>
                                <div class="invalid-feedback">Düzgün email daxil edin</div>
                            </div>

                            <!-- Status -->
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Status *</label>
                                <select name="status" class="form-select" required>
                                    <option value="active" <?php echo $customer['status'] == 'active' ? 'selected' : ''; ?>>
                                        Aktiv
                                    </option>
                                    <option value="blocked" <?php echo $customer['status'] == 'blocked' ? 'selected' : ''; ?>>
                                        Blok
                                    </option>
                                </select>
                            </div>

                            <hr class="my-4">

                            <!-- Şifrə -->
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Yeni şifrə</label>
                                <div class="input-group">
                                    <input type="password" name="password" class="form-control" 
                                           id="password" minlength="6">
                                    <button class="btn btn-outline-secondary" type="button" 
                                            onclick="togglePassword('password')">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <div class="form-text">Dəyişdirməyəcəksinizsə boş buraxın</div>
                            </div>

                            <!-- Şifrə təkrarı -->
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Yeni şifrə təkrarı</label>
                                <div class="input-group">
                                    <input type="password" name="password_confirm" class="form-control" 
                                           id="passwordConfirm" minlength="6">
                                    <button class="btn btn-outline-secondary" type="button" 
                                            onclick="togglePassword('passwordConfirm')">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Yadda saxla
                        </button>
                    </form>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
// Şifrə göstər/gizlət
function togglePassword(fieldId) {
    const field = document.getElementById(fieldId);
    const button = field.nextElementSibling;
    const icon = button.querySelector('i');
    
    if (field.type === 'password') {
        field.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        field.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}

// Form validasiyası
(function () {
    'use strict'
    var forms = document.querySelectorAll('.needs-validation')
    Array.prototype.slice.call(forms)
        .forEach(function (form) {
            form.addEventListener('submit', function (event) {
                if (!form.checkValidity()) {
                    event.preventDefault()
                    event.stopPropagation()
                }
                form.classList.add('was-validated')

                // Şifrə yoxlaması
                const password = document.getElementById('password');
                const passwordConfirm = document.getElementById('passwordConfirm');
                
                if (password.value !== '' && password.value !== passwordConfirm.value) {
                    passwordConfirm.setCustomValidity('Şifrələr uyğun gəlmir');
                    event.preventDefault();
                } else {
                    passwordConfirm.setCustomValidity('');
                }
            }, false)
        })
})()
</script>

<?php include '../templates/admin/footer.php'; ?>