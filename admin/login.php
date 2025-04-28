<?php
require_once '../config/config.php';
require_once '../includes/Database.php';

// Əgər admin artıq daxil olubsa
if (isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit();
}

$errors = [];

// Form göndərildikdə
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = Database::getInstance();
    
    $username = clean($_POST['username']);
    $password = $_POST['password'];
    
    // Username və şifrə yoxlanışı
    if (empty($username)) {
        $errors[] = 'İstifadəçi adı daxil edilməyib';
    }
    if (empty($password)) {
        $errors[] = 'Şifrə daxil edilməyib';
    }
    
    // Xəta yoxdursa
    if (empty($errors)) {
        $sql = "SELECT * FROM admins WHERE username = :username AND status = 'active'";
        $admin = $db->selectOne($sql, [':username' => $username]);
        
        if ($admin && password_verify($password, $admin['password'])) {
            // Session-a əlavə edirik
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_name'] = $admin['full_name'];
            $_SESSION['admin_role'] = $admin['role'];
            
            // Son giriş tarixini yeniləyirik
            $sql = "UPDATE admins SET last_login = NOW() WHERE id = :id";
            $db->update($sql, [':id' => $admin['id']]);
            
            // Giriş logunu qeyd edirik
            $sql = "INSERT INTO system_logs (user_id, user_type, action, ip_address, user_agent) 
                   VALUES (:user_id, 'admin', 'login', :ip, :user_agent)";
            $db->insert($sql, [
                ':user_id' => $admin['id'],
                ':ip' => $_SERVER['REMOTE_ADDR'],
                ':user_agent' => $_SERVER['HTTP_USER_AGENT']
            ]);
            
            // Uğurlu giriş mesajı
            setMessage('Admin panelə xoş gəldiniz!');
            
            // Admini yönləndiririk
            header('Location: index.php');
            exit();
        } else {
            $errors[] = 'İstifadəçi adı və ya şifrə yanlışdır';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="az">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Giriş - <?php echo SITE_NAME; ?></title>
    
    <!-- CSS -->
    <link href="../assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/fontawesome.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-5">
                <!-- Logo -->
                <div class="text-center mb-4">
                    <h1 class="h3"><?php echo SITE_NAME; ?></h1>
                    <p class="text-muted">Admin Panel</p>
                </div>

                <div class="card shadow">
                    <div class="card-body p-4">
                        <h2 class="text-center mb-4">Admin Giriş</h2>
                        
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
                            <!-- İstifadəçi adı -->
                            <div class="mb-3">
                                <label class="form-label">İstifadəçi adı</label>
                                <input type="text" name="username" class="form-control" 
                                       value="<?php echo $_POST['username'] ?? ''; ?>" required>
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

                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-sign-in-alt me-2"></i>Daxil ol
                            </button>
                        </form>

                        <div class="text-center mt-3">
                            <a href="forgot-password.php">Şifrəni unutmusunuz?</a>
                        </div>
                    </div>
                </div>

                <!-- Geri qayıt -->
                <div class="text-center mt-4">
                    <a href="../" class="text-decoration-none">
                        <i class="fas fa-arrow-left me-2"></i>Sayta qayıt
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript -->
    <script src="../assets/js/bootstrap.bundle.min.js"></script>
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
</body>
</html>