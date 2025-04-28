<?php
require_once '../config/config.php';
require_once '../includes/Database.php';
require_once '../includes/Notification.php';
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

$pageTitle = 'Bildirişlər - Admin Panel';
$notificationObj = new Notification();

// Bildirişləri oxunmuş etmək
if (isset($_POST['mark_read']) && isset($_POST['notification_ids'])) {
    $ids = $_POST['notification_ids'];
    if ($notificationObj->markMultipleAsRead($ids)) {
        setMessage('Seçilmiş bildirişlər oxunmuş kimi işarələndi');
    } else {
        setMessage('Bildirişlər yenilənərkən xəta baş verdi', 'error');
    }
    header('Location: notifications.php');
    exit();
}

// Köhnə bildirişləri təmizləmək
if (isset($_POST['clean_old'])) {
    $days = isset($_POST['days']) ? (int)$_POST['days'] : 30;
    $count = $notificationObj->cleanOldNotifications($days);
    if ($count !== false) {
        setMessage($count . ' köhnə bildiriş təmizləndi');
    } else {
        setMessage('Bildirişlər təmizlənərkən xəta baş verdi', 'error');
    }
    header('Location: notifications.php');
    exit();
}

// Filtrləmə
$type = $_GET['type'] ?? '';
$isRead = isset($_GET['is_read']) ? (int)$_GET['is_read'] : -1;
$startDate = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
$endDate = $_GET['end_date'] ?? date('Y-m-d');

// SQL sorğusu
$sql = "SELECT n.*, c.first_name, c.last_name, c.fin_code 
        FROM notifications n
        LEFT JOIN customers c ON n.customer_id = c.id
        WHERE n.created_at BETWEEN :start_date AND :end_date";

$params = [
    ':start_date' => $startDate . ' 00:00:00',
    ':end_date' => $endDate . ' 23:59:59'
];

if ($type) {
    $sql .= " AND n.type = :type";
    $params[':type'] = $type;
}

if ($isRead !== -1) {
    $sql .= " AND n.is_read = :is_read";
    $params[':is_read'] = $isRead;
}

$sql .= " ORDER BY n.created_at DESC";

$db = Database::getInstance();
$notifications = $db->select($sql, $params);

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
                <h1 class="h2">Bildirişlər</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <button type="button" class="btn btn-danger me-2" onclick="cleanOldNotifications()">
                        <i class="fas fa-trash me-2"></i>Köhnələri təmizlə
                    </button>
                    <button type="button" class="btn btn-primary me-2" id="markSelectedButton" style="display: none;">
                        <i class="fas fa-check me-2"></i>Seçilənləri oxunmuş et
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="window.print()">
                        <i class="fas fa-print me-2"></i>Çap et
                    </button>
                </div>
            </div>

            <!-- Filter Panel -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <!-- Bildiriş tipi -->
                        <div class="col-md-3">
                            <label class="form-label">Bildiriş tipi</label>
                            <select name="type" class="form-select">
                                <option value="">Hamısı</option>
                                <option value="payment_due" <?php echo $type == 'payment_due' ? 'selected' : ''; ?>>
                                    Ödəniş vaxtı
                                </option>
                                <option value="payment_late" <?php echo $type == 'payment_late' ? 'selected' : ''; ?>>
                                    Gecikmiş ödəniş
                                </option>
                                <option value="credit_approved" <?php echo $type == 'credit_approved' ? 'selected' : ''; ?>>
                                    Kredit təsdiqi
                                </option>
                                <option value="credit_rejected" <?php echo $type == 'credit_rejected' ? 'selected' : ''; ?>>
                                    Kredit rəddi
                                </option>
                            </select>
                        </div>

                        <!-- Status -->
                        <div class="col-md-3">
                            <label class="form-label">Status</label>
                            <select name="is_read" class="form-select">
                                <option value="-1">Hamısı</option>
                                <option value="0" <?php echo $isRead === 0 ? 'selected' : ''; ?>>Oxunmamış</option>
                                <option value="1" <?php echo $isRead === 1 ? 'selected' : ''; ?>>Oxunmuş</option>
                            </select>
                        </div>

                        <!-- Tarix aralığı -->
                        <div class="col-md-2">
                            <label class="form-label">Başlanğıc tarix</label>
                            <input type="date" name="start_date" class="form-control" 
                                   value="<?php echo $startDate; ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Son tarix</label>
                            <input type="date" name="end_date" class="form-control" 
                                   value="<?php echo $endDate; ?>">
                        </div>

                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary me-2">
                                <i class="fas fa-search me-2"></i>Axtar
                            </button>
                            <a href="notifications.php" class="btn btn-secondary">
                                <i class="fas fa-times me-2"></i>Təmizlə
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Bildiriş Siyahısı -->
            <div class="card">
                <div class="card-body">
                    <form id="notificationForm" method="POST">
                        <div class="table-responsive">
                            <table class="table table-striped datatable">
                                <thead>
                                    <tr>
                                        <th width="20">
                                            <input type="checkbox" class="form-check-input" id="selectAll">
                                        </th>
                                        <th>Müştəri</th>
                                        <th>Bildiriş</th>
                                        <th>Tip</th>
                                        <th>Tarix</th>
                                        <th>Status</th>
                                        <th>Əməliyyatlar</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($notifications as $notification): ?>
                                    <tr class="<?php echo $notification['is_read'] ? '' : 'table-light'; ?>">
                                        <td>
                                            <?php if (!$notification['is_read']): ?>
                                            <input type="checkbox" class="form-check-input notification-checkbox" 
                                                   name="notification_ids[]" value="<?php echo $notification['id']; ?>">
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($notification['customer_id']): ?>
                                            <a href="customer_view.php?id=<?php echo $notification['customer_id']; ?>" 
                                               class="text-decoration-none">
                                                <?php echo $notification['first_name'] . ' ' . $notification['last_name']; ?>
                                            </a>
                                            <br>
                                            <small class="text-muted"><?php echo $notification['fin_code']; ?></small>
                                            <?php else: ?>
                                            <span class="text-muted">Sistem</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo $notification['message']; ?></td>
                                        <td>
                                            <?php
                                            $badgeClass = '';
                                            $typeText = '';
                                            switch ($notification['type']) {
                                                case 'payment_due':
                                                    $badgeClass = 'warning';
                                                    $typeText = 'Ödəniş vaxtı';
                                                    break;
                                                case 'payment_late':
                                                    $badgeClass = 'danger';
                                                    $typeText = 'Gecikmiş ödəniş';
                                                    break;
                                                case 'credit_approved':
                                                    $badgeClass = 'success';
                                                    $typeText = 'Kredit təsdiqi';
                                                    break;
                                                case 'credit_rejected':
                                                    $badgeClass = 'danger';
                                                    $typeText = 'Kredit rəddi';
                                                    break;
                                            }
                                            ?>
                                            <span class="badge bg-<?php echo $badgeClass; ?>">
                                                <?php echo $typeText; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php echo date('d.m.Y H:i', strtotime($notification['created_at'])); ?>
                                        </td>
                                        <td>
                                            <?php if ($notification['is_read']): ?>
                                                <span class="badge bg-secondary">Oxunub</span>
                                            <?php else: ?>
                                                <span class="badge bg-success">Yeni</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($notification['credit_id']): ?>
                                            <a href="credit_view.php?id=<?php echo $notification['credit_id']; ?>" 
                                               class="btn btn-sm btn-info" title="Krediti göstər">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Təmizləmə Modal -->
<div class="modal fade" id="cleanModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Köhnə Bildirişləri Təmizlə</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Neçə gündən köhnə bildirişlər təmizlənsin?</label>
                        <input type="number" name="days" class="form-control" value="30" min="1" required>
                    </div>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Bu əməliyyat geri qaytarıla bilməz!
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Ləğv et</button>
                    <button type="submit" name="clean_old" class="btn btn-danger">
                        <i class="fas fa-trash me-2"></i>Təmizlə
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Hamısını seç
document.getElementById('selectAll').addEventListener('change', function() {
    var checkboxes = document.getElementsByClassName('notification-checkbox');
    for (var checkbox of checkboxes) {
        checkbox.checked = this.checked;
    }
    toggleMarkSelectedButton();
});

// Seçilmiş bildirişləri göstər/gizlət
document.querySelectorAll('.notification-checkbox').forEach(function(checkbox) {
    checkbox.addEventListener('change', function() {
        toggleMarkSelectedButton();
    });
});

function toggleMarkSelectedButton() {
    var checkboxes = document.getElementsByClassName('notification-checkbox');
    var selectedCount = 0;
    for (var checkbox of checkboxes) {
        if (checkbox.checked) selectedCount++;
    }
    
    document.getElementById('markSelectedButton').style.display = 
        selectedCount > 0 ? 'inline-block' : 'none';
}

// Seçilənləri oxunmuş et
document.getElementById('markSelectedButton').addEventListener('click', function() {
    document.getElementById('notificationForm').submit();
});

// Köhnə bildirişləri təmizlə
function cleanOldNotifications() {
    var cleanModal = new bootstrap.Modal(document.getElementById('cleanModal'));
    cleanModal.show();
}

// DataTables inisializasiyası
$(document).ready(function() {
    $('.datatable').DataTable({
        order: [[4, 'desc']], // Tarixə görə sıralama
        pageLength: 50
    });
});
</script>

<?php include '../templates/admin/footer.php'; ?>