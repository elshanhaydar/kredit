<?php
require_once 'config/config.php';
require_once 'includes/Database.php';
require_once 'includes/Customer.php';

$pageTitle = 'Qeydiyyat - ' . SITE_NAME;

// Əgər istifadəçi daxil olubsa, dashboard-a yönləndir
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit();
}

$customerObj = new Customer();
$errors = [];

// Form göndərildikdə
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Məlumatları təmizləyirik
    $firstName = clean($_POST['first_name']);
    $lastName = clean($_POST['last_name']);
    $fatherName = clean($_POST['father_name']);
    $idNumber = clean($_POST['id_number']);
    $finCode = clean($_POST['fin_code']);
    $email = clean($_POST['email']);
    $password = $_POST['password'];
    $passwordConfirm = $_POST['password_confirm'];

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
    if (empty($password)) {
        $errors[] = 'Şifrə daxil edilməyib';
    } elseif (strlen($password) < 6) {
        $errors[] = 'Şifrə minimum 6 simvoldan ibarət olmalıdır';
    }
    if ($password !== $passwordConfirm) {
        $errors[] = 'Şifrələr uyğun gəlmir';
    }

    // FIN və şəxsiyyət vəsiqəsi yoxlanışı
    if ($customerObj->isFinCodeExists($finCode)) {
        $errors[] = 'Bu FİN kod artıq qeydiyyatdan keçib';
    }
    if ($customerObj->isIdNumberExists($idNumber)) {
        $errors[] = 'Bu şəxsiyyət vəsiqəsi artıq qeydiyyatdan keçib';
    }

    // Xəta yoxdursa qeydiyyatdan keçirik
    if (empty($errors)) {
        $data = [
            'first_name' => $firstName,
            'last_name' => $lastName,
            'father_name' => $fatherName,
            'id_number' => $idNumber,
            'fin_code' => $finCode,
            'email' => $email,
            'password' => password_hash($password, PASSWORD_DEFAULT)
        ];

        $customerId = $customerObj->create($data);
        if ($customerId) {
            // Session-a əlavə edirik
            $_SESSION['user_id'] = $customerId;
            $_SESSION['user_name'] = $firstName . ' ' . $lastName;

            // Uğurlu qeydiyyat mesajı
            setMessage('Qeydiyyat uğurla tamamlandı');
            header('Location: dashboard.php');
            exit();
        } else {
            $errors[] = 'Qeydiyyat zamanı xəta baş verdi';
        }
    }
}

// Header
include 'templates/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card shadow">
                <div class="card-body p-4">
                    <h2 class="text-center mb-4">Qeydiyyat</h2>

                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo $error; ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form method="POST" id="registerForm">
                        <!-- Ad -->
                        <div class="mb-3">
                            <label class="form-label">Ad *</label>
                            <input type="text" name="first_name" class="form-control" 
                                   value="<?php echo $_POST['first_name'] ?? ''; ?>" required>
                        </div>

                        <!-- Soyad -->
                        <div class="mb-3">
                            <label class="form-label">Soyad *</label>
                            <input type="text" name="last_name" class="form-control" 
                                   value="<?php echo $_POST['last_name'] ?? ''; ?>" required>
                        </div>

                        <!-- Ata adı -->
                        <div class="mb-3">
                            <label class="form-label">Ata adı *</label>
                            <input type="text" name="father_name" class="form-control" 
                                   value="<?php echo $_POST['father_name'] ?? ''; ?>" required>
                        </div>

                        <!-- Şəxsiyyət vəsiqəsi -->
                        <div class="mb-3">
                            <label class="form-label">Şəxsiyyət vəsiqəsinin seriya və nömrəsi *</label>
                            <input type="text" name="id_number" class="form-control" 
                                   value="<?php echo $_POST['id_number'] ?? ''; ?>" 
                                   placeholder="Məs: AA1234567" required
                                   pattern="[A-Z]{2}[0-9]{7}">
                            <div class="form-text">Format: AA1234567</div>
                        </div>

                        <!-- FİN kod -->
                        <div class="mb-3">
                            <label class="form-label">FİN kod *</label>
                            <input type="text" name="fin_code" class="form-control" 
                                   value="<?php echo $_POST['fin_code'] ?? ''; ?>" 
                                   maxlength="7" required>
                        </div>

                        <!-- Email -->
                        <div class="mb-3">
                            <label class="form-label">Email *</label>
                            <input type="email" name="email" class="form-control" 
                                   value="<?php echo $_POST['email'] ?? ''; ?>" required>
                        </div>

                        <!-- Şifrə -->
                        <div class="mb-3">
                            <label class="form-label">Şifrə *</label>
                            <input type="password" name="password" class="form-control" 
                                   minlength="6" required>
                            <div class="form-text">Minimum 6 simvol</div>
                        </div>

                        <!-- Şifrə təkrarı -->
                        <div class="mb-3">
                            <label class="form-label">Şifrə təkrarı *</label>
                            <input type="password" name="password_confirm" class="form-control" 
                                   minlength="6" required>
                        </div>

                        <div class="mb-3">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="terms" required>
                                <label class="form-check-label" for="terms">
                                    <a href="terms.php" target="_blank">İstifadə şərtləri</a> ilə razıyam
                                </label>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">Qeydiyyatdan keç</button>
                    </form>

                    <div class="text-center mt-3">
                        <p class="mb-0">
                            Artıq hesabınız var? 
                            <a href="login.php">Daxil olun</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Footer
include 'templates/footer.php';
?>