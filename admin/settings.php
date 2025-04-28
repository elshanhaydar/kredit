<?php
require_once '../config/config.php';
require_once '../includes/Database.php';

// Session başlatma yoxlaması
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Admin session yoxlanışı
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

$pageTitle = 'Sistem Parametrləri - Admin Panel';
$db = Database::getInstance();

// Parametrləri yeniləmək
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_credit_settings'])) {
        // Kredit parametrlərini yeniləyirik
        $settings = [
            'min_credit_amount' => (float)$_POST['min_credit_amount'],
            'max_credit_amount' => (float)$_POST['max_credit_amount'],
            'min_credit_period' => (int)$_POST['min_credit_period'],
            'max_credit_period' => (int)$_POST['max_credit_period'],
            'min_interest_rate' => (float)$_POST['min_interest_rate'],
            'max_interest_rate' => (float)$_POST['max_interest_rate']
        ];

        try {
            foreach ($settings as $key => $value) {
                $sql = "UPDATE settings SET value = :value WHERE name = :name";
                $db->update($sql, [':value' => $value, ':name' => $key]);
            }
            setMessage('Kredit parametrləri yeniləndi');
        } catch (Exception $e) {
            setMessage('Parametrlər yenilənərkən xəta baş verdi', 'error');
        }
    }
    elseif (isset($_POST['update_notification_settings'])) {
        // Bildiriş parametrlərini yeniləyirik
        $settings = [
            'notification_days' => (int)$_POST['notification_days'],
            'clean_notifications_after' => (int)$_POST['clean_notifications_after'],
            'enable_email_notifications' => isset($_POST['enable_email_notifications']) ? 1 : 0,
            'notification_sender_email' => $_POST['notification_sender_email'],
            'notification_sender_name' => $_POST['notification_sender_name']
        ];

        try {
            foreach ($settings as $key => $value) {
                $sql = "UPDATE settings SET value = :value WHERE name = :name";
                $db->update($sql, [':value' => $value, ':name' => $key]);
            }
            setMessage('Bildiriş parametrləri yeniləndi');
        } catch (Exception $e) {
            setMessage('Parametrlər yenilənərkən xəta baş verdi', 'error');
        }
    }
    elseif (isset($_POST['update_company_settings'])) {
        // Şirkət məlumatlarını yeniləyirik
        $settings = [
            'company_name' => $_POST['company_name'],
            'company_address' => $_POST['company_address'],
            'company_phone' => $_POST['company_phone'],
            'company_email' => $_POST['company_email'],
            'company_tax_id' => $_POST['company_tax_id']
        ];

        try {
            foreach ($settings as $key => $value) {
                $sql = "UPDATE settings SET value = :value WHERE name = :name";
                $db->update($sql, [':value' => $value, ':name' => $key]);
            }
            setMessage('Şirkət məlumatları yeniləndi');
        } catch (Exception $e) {
            setMessage('Məlumatlar yenilənərkən xəta baş verdi', 'error');
        }
    }

    // Yenilənmiş səhifəyə yönləndiririk
    header('Location: settings.php');
    exit();
}

// Cari parametrləri alırıq
$sql = "SELECT * FROM settings";
$settingsData = $db->select($sql);

// Parametrləri array-ə çeviririk
$settings = [];
foreach ($settingsData as $setting) {
    $settings[$setting['name']] = $setting['value'];
}

// Header
include '../templates/admin/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <?php include '../templates/admin/sidebar.php'; ?>

        <!-- Əsas Content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
            <h1 class="h2 mb-4">Sistem Parametrləri</h1>

            <!-- Nav Tabs -->
            <ul class="nav nav-tabs mb-4" id="settingsTabs" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" id="credit-tab" data-bs-toggle="tab" href="#credit" role="tab">
                        <i class="fas fa-money-bill me-2"></i>Kredit Parametrləri
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="notification-tab" data-bs-toggle="tab" href="#notification" role="tab">
                        <i class="fas fa-bell me-2"></i>Bildiriş Parametrləri
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="company-tab" data-bs-toggle="tab" href="#company" role="tab">
                        <i class="fas fa-building me-2"></i>Şirkət Məlumatları
                    </a>
                </li>
            </ul>

            <!-- Tab Content -->
            <div class="tab-content" id="settingsTabContent">
                <!-- Kredit Parametrləri -->
                <div class="tab-pane fade show active" id="credit" role="tabpanel">
                    <div class="card">
                        <div class="card-body">
                            <form method="POST">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Minimum kredit məbləği (AZN)</label>
                                        <input type="number" name="min_credit_amount" class="form-control" 
                                               value="<?php echo $settings['min_credit_amount'] ?? 500; ?>" 
                                               min="0" step="100" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Maksimum kredit məbləği (AZN)</label>
                                        <input type="number" name="max_credit_amount" class="form-control" 
                                               value="<?php echo $settings['max_credit_amount'] ?? 30000; ?>" 
                                               min="0" step="100" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Minimum kredit müddəti (ay)</label>
                                        <input type="number" name="min_credit_period" class="form-control" 
                                               value="<?php echo $settings['min_credit_period'] ?? 3; ?>" 
                                               min="1" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Maksimum kredit müddəti (ay)</label>
                                        <input type="number" name="max_credit_period" class="form-control" 
                                               value="<?php echo $settings['max_credit_period'] ?? 36; ?>" 
                                               min="1" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Minimum faiz dərəcəsi (%)</label>
                                        <input type="number" name="min_interest_rate" class="form-control" 
                                               value="<?php echo $settings['min_interest_rate'] ?? 12; ?>" 
                                               min="0" step="0.5" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Maksimum faiz dərəcəsi (%)</label>
                                        <input type="number" name="max_interest_rate" class="form-control" 
                                               value="<?php echo $settings['max_interest_rate'] ?? 24; ?>" 
                                               min="0" step="0.5" required>
                                    </div>
                                </div>
                                <button type="submit" name="update_credit_settings" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Yadda saxla
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Bildiriş Parametrləri -->
                <div class="tab-pane fade" id="notification" role="tabpanel">
                    <div class="card">
                        <div class="card-body">
                            <form method="POST">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Bildiriş göndərmə günü</label>
                                        <input type="number" name="notification_days" class="form-control" 
                                               value="<?php echo $settings['notification_days'] ?? 3; ?>" 
                                               min="1" required>
                                        <div class="form-text">
                                            Ödəniş tarixindən neçə gün əvvəl bildiriş göndərilsin
                                        </div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Bildirişlərin təmizlənməsi (gün)</label>
                                        <input type="number" name="clean_notifications_after" class="form-control" 
                                               value="<?php echo $settings['clean_notifications_after'] ?? 30; ?>" 
                                               min="1" required>
                                        <div class="form-text">
                                            Oxunmuş bildirişlər neçə gündən sonra təmizlənsin
                                        </div>
                                    </div>
                                    <div class="col-md-12 mb-3">
                                        <div class="form-check form-switch">
                                            <input type="checkbox" name="enable_email_notifications" 
                                                   class="form-check-input" id="enableEmailNotifications"
                                                   <?php echo ($settings['enable_email_notifications'] ?? 0) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="enableEmailNotifications">
                                                Email bildirişlərini aktivləşdir
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Göndərən email</label>
                                        <input type="email" name="notification_sender_email" class="form-control" 
                                               value="<?php echo $settings['notification_sender_email'] ?? ''; ?>">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Göndərən adı</label>
                                        <input type="text" name="notification_sender_name" class="form-control" 
                                               value="<?php echo $settings['notification_sender_name'] ?? ''; ?>">
                                    </div>
                                </div>
                                <button type="submit" name="update_notification_settings" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Yadda saxla
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Şirkət Məlumatları -->
                <div class="tab-pane fade" id="company" role="tabpanel">
                    <div class="card">
                        <div class="card-body">
                            <form method="POST">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Şirkət adı</label>
                                        <input type="text" name="company_name" class="form-control" 
                                               value="<?php echo $settings['company_name'] ?? ''; ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">VÖEN</label>
                                        <input type="text" name="company_tax_id" class="form-control" 
                                               value="<?php echo $settings['company_tax_id'] ?? ''; ?>" required>
                                    </div>
                                    <div class="col-md-12 mb-3">
                                        <label class="form-label">Ünvan</label>
                                        <input type="text" name="company_address" class="form-control" 
                                               value="<?php echo $settings['company_address'] ?? ''; ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Telefon</label>
                                        <input type="text" name="company_phone" class="form-control" 
                                               value="<?php echo $settings['company_phone'] ?? ''; ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Email</label>
                                        <input type="email" name="company_email" class="form-control" 
                                               value="<?php echo $settings['company_email'] ?? ''; ?>" required>
                                    </div>
                                </div>
                                <button type="submit" name="update_company_settings" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Yadda saxla
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
// Tab-ların yadda saxlanması
document.addEventListener('DOMContentLoaded', function() {
    // URL-dən active tab-ı oxuyuruq
    var activeTab = window.location.hash.replace('#', '');
    if (activeTab) {
        $('#settingsTabs a[href="#' + activeTab + '"]').tab('show');
    }

    // Tab dəyişdikdə URL-ə əlavə edirik
    $('#settingsTabs a').on('shown.bs.tab', function (e) {
        window.location.hash = e.target.hash;
    });
});
</script>

<?php include '../templates/admin/footer.php'; ?>