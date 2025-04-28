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

// Super admin yoxlanışı
if ($_SESSION['admin_role'] !== 'admin') {
    setMessage('Bu səhifəyə giriş üçün icazəniz yoxdur', 'error');
    header('Location: index.php');
    exit();
}

$pageTitle = 'Admin İstifadəçiləri - Admin Panel';
$db = Database::getInstance();

// Admin silmə
if (isset($_POST['delete']) && isset($_POST['admin_id'])) {
    $adminId = (int)$_POST['admin_id'];
    
    // Özünü silməyə çalışırsa
    if ($adminId === (int)$_SESSION['admin_id']) {
        setMessage('Öz hesabınızı silə bilməzsiniz', 'error');
    } else {
        $sql = "DELETE FROM admins WHERE id = :id";
        if ($db->delete($sql, [':id' => $adminId])) {
            setMessage('Admin istifadəçisi silindi');
        } else {
            setMessage('Silinmə zamanı xəta baş verdi', 'error');
        }
    }
    header('Location: admins.php');
    exit();
}

// Status dəyişmə
if (isset($_POST['change_status']) && isset($_POST['admin_id'])) {
    $adminId = (int)$_POST['admin_id'];
    $status = clean($_POST['status']);
    
    // Özünün statusunu dəyişməyə çalışırsa
    if ($adminId === (int)$_SESSION['admin_id']) {
        setMessage('Öz statusunuzu dəyişə bilməzsiniz', 'error');
    } else {
        $sql = "UPDATE admins SET status = :status WHERE id = :id";
        if ($db->update($sql, [':status' => $status, ':id' => $adminId])) {
            setMessage('Admin statusu yeniləndi');
        } else {
            setMessage('Status yenilənərkən xəta baş verdi', 'error');
        }
    }
    header('Location: admins.php');
    exit();
}

// Admin siyahısını alırıq
$sql = "SELECT * FROM admins ORDER BY id ASC";
$admins = $db->select($sql);

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
                <h1 class="h2">Admin İstifadəçiləri</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <button type="button" class="btn btn-primary" onclick="showAddModal()">
                        <i class="fas fa-plus me-2"></i>Yeni Admin
                    </button>
                </div>
            </div>

            <!-- Admin Siyahısı -->
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover datatable">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>İstifadəçi adı</th>
                                    <th>Ad Soyad</th>
                                    <th>Email</th>
                                    <th>Rol</th>
                                    <th>Status</th>
                                    <th>Son giriş</th>
                                    <th width="150">Əməliyyatlar</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($admins as $admin): ?>
                                <tr>
                                    <td><?php echo $admin['id']; ?></td>
                                    <td><?php echo $admin['username']; ?></td>
                                    <td><?php echo $admin['full_name']; ?></td>
                                    <td><?php echo $admin['email']; ?></td>
                                    <td>
                                        <?php if ($admin['role'] == 'admin'): ?>
                                            <span class="badge bg-danger">Super Admin</span>
                                        <?php else: ?>
                                            <span class="badge bg-info">Manager</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($admin['status'] == 'active'): ?>
                                            <span class="badge bg-success">Aktiv</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Blok</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php 
                                        if ($admin['last_login']) {
                                            echo date('d.m.Y H:i', strtotime($admin['last_login']));
                                        } else {
                                            echo '-';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <?php if ($admin['id'] != $_SESSION['admin_id']): ?>
                                            <div class="btn-group">
                                                <button type="button" class="btn btn-sm btn-warning"
                                                        onclick="showEditModal(<?php echo htmlspecialchars(json_encode($admin)); ?>)"
                                                        title="Düzəliş et">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                
                                                <button type="button" class="btn btn-sm btn-<?php echo $admin['status'] == 'active' ? 'danger' : 'success'; ?>"
                                                        onclick="changeStatus(<?php echo $admin['id']; ?>, '<?php echo $admin['status']; ?>')"
                                                        title="<?php echo $admin['status'] == 'active' ? 'Blok et' : 'Aktiv et'; ?>">
                                                    <i class="fas fa-<?php echo $admin['status'] == 'active' ? 'ban' : 'check'; ?>"></i>
                                                </button>

                                                <button type="button" class="btn btn-sm btn-danger"
                                                        onclick="confirmDelete(<?php echo $admin['id']; ?>)"
                                                        title="Sil">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        <?php endif; ?>
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

<!-- Yeni Admin Modal -->
<div class="modal fade" id="adminModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Yeni Admin</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="adminForm">
                <div class="modal-body">
                    <input type="hidden" name="admin_id" id="adminId">
                    
                    <!-- İstifadəçi adı -->
                    <div class="mb-3">
                        <label class="form-label">İstifadəçi adı *</label>
                        <input type="text" name="username" id="username" class="form-control" required>
                    </div>

                    <!-- Ad Soyad -->
                    <div class="mb-3">
                        <label class="form-label">Ad Soyad *</label>
                        <input type="text" name="full_name" id="fullName" class="form-control" required>
                    </div>

                    <!-- Email -->
                    <div class="mb-3">
                        <label class="form-label">Email *</label>
                        <input type="email" name="email" id="email" class="form-control" required>
                    </div>

                    <!-- Rol -->
                    <div class="mb-3">
                        <label class="form-label">Rol *</label>
                        <select name="role" id="role" class="form-select" required>
                            <option value="manager">Manager</option>
                            <option value="admin">Super Admin</option>
                        </select>
                    </div>

                    <!-- Şifrə -->
                    <div class="mb-3">
                        <label class="form-label">Şifrə *</label>
                        <div class="input-group">
                            <input type="password" name="password" id="password" class="form-control" 
                                   minlength="6" required>
                            <button class="btn btn-outline-secondary" type="button" 
                                    onclick="togglePassword('password')">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <div class="form-text">Minimum 6 simvol</div>
                    </div>

                    <!-- Şifrə təkrarı -->
                    <div class="mb-3">
                        <label class="form-label">Şifrə təkrarı *</label>
                        <div class="input-group">
                            <input type="password" name="password_confirm" id="passwordConfirm" 
                                   class="form-control" minlength="6" required>
                            <button class="btn btn-outline-secondary" type="button" 
                                    onclick="togglePassword('passwordConfirm')">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Ləğv et</button>
                    <button type="submit" class="btn btn-primary">Yadda saxla</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Status Modal -->
<div class="modal fade" id="statusModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Status Dəyişdir</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="admin_id" id="statusAdminId">
                    <input type="hidden" name="status" id="newStatus">
                    <p>Admin statusunu dəyişmək istədiyinizə əminsiniz?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Ləğv et</button>
                    <button type="submit" name="change_status" class="btn btn-primary">Təsdiq et</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Silmə Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Admin Sil</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="admin_id" id="deleteAdminId">
                    <p>Bu admin istifadəçisini silmək istədiyinizə əminsiniz?</p>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Bu əməliyyat geri qaytarıla bilməz!
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Ləğv et</button>
                    <button type="submit" name="delete" class="btn btn-danger">Sil</button>
                </div>
            </form>
        </div>
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

// Admin əlavə etmə modalı
function showAddModal() {
    document.getElementById('modalTitle').textContent = 'Yeni Admin';
    document.getElementById('adminForm').reset();
    document.getElementById('adminId').value = '';
    
    // Şifrə sahələrini required edirik
    document.getElementById('password').required = true;
    document.getElementById('passwordConfirm').required = true;
    
    var modal = new bootstrap.Modal(document.getElementById('adminModal'));
    modal.show();
}

// Admin düzəliş modalı
function showEditModal(admin) {
    document.getElementById('modalTitle').textContent = 'Admin Düzəliş';
    document.getElementById('adminId').value = admin.id;
    document.getElementById('username').value = admin.username;
    document.getElementById('fullName').value = admin.full_name;
    document.getElementById('email').value = admin.email;
    document.getElementById('role').value = admin.role;
    
    // Şifrə sahələrini optional edirik
    document.getElementById('password').required = false;
    document.getElementById('passwordConfirm').required = false;
    
    var modal = new bootstrap.Modal(document.getElementById('adminModal'));
    modal.show();
}

// Status dəyişmə
function changeStatus(adminId, currentStatus) {
    document.getElementById('statusAdminId').value = adminId;
    document.getElementById('newStatus').value = currentStatus == 'active' ? 'blocked' : 'active';
    
    var modal = new bootstrap.Modal(document.getElementById('statusModal'));
    modal.show();
}

// Silmə təsdiqi
function confirmDelete(adminId) {
    document.getElementById('deleteAdminId').value = adminId;
    var modal = new bootstrap.Modal(document.getElementById('deleteModal'));
    modal.show();
}

// Form validasiyası
document.getElementById('adminForm').addEventListener('submit', function(event) {
    const password = document.getElementById('password');
    const passwordConfirm = document.getElementById('passwordConfirm');
    
    if (password.value !== passwordConfirm.value) {
        event.preventDefault();
        alert('Şifrələr uyğun gəlmir');
    }
});

// DataTables inisializasiyası
$(document).ready(function() {
    $('.datatable').DataTable({
        order: [[0, 'asc']]
    });
});
</script>

<?php include '../templates/admin/footer.php'; ?>