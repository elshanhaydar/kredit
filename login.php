<?php
require_once 'config/config.php';
require_once 'includes/Database.php';
require_once 'includes/Customer.php';

$pageTitle = 'Giriş - ' . SITE_NAME;

// Əgər istifadəçi artıq daxil olubsa
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit();
}

$errors = [];

// Form göndərildikdə
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = Database::getInstance();
    
    $email = clean($_POST['email']);
    $password = $_POST['password'];
    
    // Email və şifrə yoxlanışı
    if (empty($email)) {
        $errors[] = 'Email daxil edilməyib';
    }
    if (empty($password)) {
        $errors[] = 'Şifrə daxil edilməyib';
    }
    
    // Xəta yoxdursa
    if (empty($errors)) {
        $sql = "SELECT * FROM customers WHERE email = :email AND status = 'active'";
        $customer = $db->selectOne($sql, [':email' => $email]);
        
        if ($customer && password_verify($password, $customer['password'])) {
            // Session-a əlavə edirik
            $_SESSION['user_id'] = $customer['id'];
            $_SESSION['user_name'] = $customer['first_name'] . ' ' . $customer['last_name'];
            
            // Əgər "məni yadda saxla" seçilibsə
            if (isset($_POST['remember']) && $_POST['remember'] == 1) {
                $token = bin2hex(random_bytes(32));
                
                // Token-i bazaya yazırıq
                $sql = "UPDATE customers SET remember_token = :token WHERE id = :id";
                $db->update($sql, [
                    ':token' => $token,
                    ':id' => $customer['id']
                ]);
                
                // Cookie qeyd edirik (30 gün)
                setcookie('remember_token', $token, time() + (86400 * 30), '/');
            }
            
            // Uğurlu giriş mesajı
            setMessage('Xoş gəldiniz!');
            
            // İstifadəçini yönləndiririk
            header('Location: dashboard.php');
            exit();
        } else {
            $errors[] = 'Email və ya şifrə yanlışdır';
        }
    }
}

// Header
include 'templates/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card shadow">
                <div class="card-body p-4">
                    <h2 class="text-center mb-4">Giriş</h2>
                    
                    <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo $error; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php endif; ?>

                    <form method="POST" id="loginForm">
                        <!-- Email -->
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" 
                                   value="<?php echo $_POST['email'] ?? ''; ?>" required>
                        </div>

                        <!-- Şifrə -->
                        <div class="mb-3">
                            <label class="form-label">Şifrə</label>
                            <div class="input-group">
                                <input type="password" name="password" class="form-control" 
                                       id="password" required>
                                <button class="btn btn-outline-secondary" type="button" 
                                        onclick="togglePassword()">
                                    <i class="fas fa-eye" id="toggleIcon"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Məni yadda saxla -->
                        <div class="mb-3">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" 
                                       name="remember" value="1" id="remember">
                                <label class="form-check-label" for="remember">
                                    Məni yadda saxla
                                </label>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">Daxil ol</button>
                    </form>

                    <div class="text-center mt-3">
                        <a href="forgot-password.php">Şifrəni unutmusunuz?</a>
                    </div>

                    <hr>

                    <div class="text-center">
                        <p class="mb-0">
                            Hesabınız yoxdur? 
                            <a href="register.php">Qeydiyyatdan keçin</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Şifrəni göstər/gizlət
function togglePassword() {
    const passwordInput = document.getElementById('password');
    const toggleIcon = document.getElementById('toggleIcon');
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        toggleIcon.classList.remove('fa-eye');
        toggleIcon.classList.add('fa-eye-slash');
    } else {
        passwordInput.type = 'password';
        toggleIcon.classList.remove('fa-eye-slash');
        toggleIcon.classList.add('fa-eye');
    }
}

// Enter düyməsi ilə submit
document.getElementById('loginForm').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        this.submit();
    }
});
</script>

<?php
// Footer
include 'templates/footer.php';
?>