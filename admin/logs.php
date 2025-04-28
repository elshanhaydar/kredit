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

$pageTitle = 'Sistem Logları - Admin Panel';
$db = Database::getInstance();

// Loqları təmizləmək
if (isset($_POST['clear_logs'])) {
    $days = (int)$_POST['days'];
    $sql = "DELETE FROM system_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL :days DAY)";
    
    if ($db->delete($sql, [':days' => $days])) {
        setMessage('Köhnə loglar təmizləndi');
    } else {
        setMessage('Loglar təmizlənərkən xəta baş verdi', 'error');
    }
    header('Location: logs.php');
    exit();
}

// Filtrləmə
$userType = $_GET['user_type'] ?? '';
$action = $_GET['action'] ?? '';
$startDate = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
$endDate = $_GET['end_date'] ?? date('Y-m-d');

// SQL sorğusu
$sql = "SELECT l.*, 
               CASE 
                   WHEN l.user_type = 'admin' THEN a.username
                   WHEN l.user_type = 'customer' THEN CONCAT(c.first_name, ' ', c.last_name)
               END as user_name
        FROM system_logs l
        LEFT JOIN admins a ON l.user_type = 'admin' AND l.user_id = a.id
        LEFT JOIN customers c ON l.user_type = 'customer' AND l.user_id = c.id
        WHERE l.created_at BETWEEN :start_date AND :end_date";

$params = [
    ':start_date' => $startDate . ' 00:00:00',
    ':end_date' => $endDate . ' 23:59:59'
];

if ($userType) {
    $sql .= " AND l.user_type = :user_type";
    $params[':user_type'] = $userType;
}

if ($action) {
    $sql .= " AND l.action = :action";
    $params[':action'] = $action;
}

$sql .= " ORDER BY l.created_at DESC";

// Loqları alırıq
$logs = $db->select($sql, $params);

// Unikal əməliyyatları alırıq
$sql = "SELECT DISTINCT action FROM system_logs ORDER BY action";
$actions = $db->select($sql);

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
                <h1 class="h2">Sistem Logları</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <button type="button" class="btn btn-danger me-2" onclick="showClearModal()">
                        <i class="fas fa-trash me-2"></i>Logları təmizlə
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
                        <!-- İstifadəçi tipi -->
                        <div class="col-md-3">
                            <label class="form-label">İstifadəçi tipi</label>
                            <select name="user_type" class="form-select">
                                <option value="">Hamısı</option>
                                <option value="admin" <?php echo $userType == 'admin' ? 'selected' : ''; ?>>
                                    Admin
                                </option>
                                <option value="customer" <?php echo $userType == 'customer' ? 'selected' : ''; ?>>
                                    Müştəri
                                </option>
                            </select>
                        </div>

                        <!-- Əməliyyat -->
                        <div class="col-md-3">
                            <label class="form-label">Əməliyyat</label>
                            <select name="action" class="form-select">
                                <option value="">Hamısı</option>
                                <?php foreach ($actions as $act): ?>
                                <option value="<?php echo $act['action']; ?>" 
                                        <?php echo $action == $act['action'] ? 'selected' : ''; ?>>
                                    <?php echo ucfirst($act['action']); ?>
                                </option>
                                <?php endforeach; ?>
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
                            <a href="logs.php" class="btn btn-secondary">
                                <i class="fas fa-times me-2"></i>Təmizlə
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Loglar -->
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped datatable">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>İstifadəçi</th>
                                    <th>Əməliyyat</th>
                                    <th>Təsvir</th>
                                    <th>IP Ünvan</th>
                                    <th>User Agent</th>
                                    <th>Tarix</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($logs as $log): ?>
                                <tr>
                                    <td><?php echo $log['id']; ?></td>
                                    <td>
                                        <?php if ($log['user_type'] == 'admin'): ?>
                                            <span class="badge bg-danger">Admin</span>
                                        <?php else: ?>
                                            <span class="badge bg-info">Müştəri</span>
                                        <?php endif; ?>
                                        <br>
                                        <?php echo $log['user_name'] ?? 'Naməlum'; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary">
                                            <?php echo ucfirst($log['action']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo $log['description']; ?></td>
                                    <td><?php echo $log['ip_address']; ?></td>
                                    <td>
                                        <small class="text-muted">
                                            <?php echo $log['user_agent']; ?>
                                        </small>
                                    </td>
                                    <td>
                                        <?php echo date('d.m.Y H:i:s', strtotime($log['created_at'])); ?>
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

<!-- Təmizləmə Modal -->
<div class="modal fade" id="clearModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Logları Təmizlə</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Neçə gündən köhnə loglar təmizlənsin?</label>
                        <input type="number" name="days" class="form-control" value="30" min="1" required>
                    </div>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Bu əməliyyat geri qaytarıla bilməz!
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Ləğv et</button>
                    <button type="submit" name="clear_logs" class="btn btn-danger">
                        <i class="fas fa-trash me-2"></i>Təmizlə
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Təmizləmə modalı
function showClearModal() {
    var modal = new bootstrap.Modal(document.getElementById('clearModal'));
    modal.show();
}

// DataTables inisializasiyası
$(document).ready(function() {
    $('.datatable').DataTable({
        order: [[0, 'desc']],
        pageLength: 50,
        columnDefs: [
            {
                targets: 5, // User Agent sütunu
                render: function(data, type, row) {
                    if (type === 'display') {
                        return '<span class="d-inline-block text-truncate" style="max-width: 200px;" title="' + data + '">' + data + '</span>';
                    }
                    return data;
                }
            }
        ]
    });
});
</script>

<?php include '../templates/admin/footer.php'; ?>