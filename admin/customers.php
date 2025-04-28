
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

$pageTitle = 'Müştərilər - Admin Panel';

$customerObj = new Customer();

// Axtarış funksiyası
$searchTerm = $_GET['search'] ?? '';
$customers = $searchTerm ? $customerObj->search($searchTerm) : $customerObj->getAll();

// Əməliyyat mesajlarını yoxlayırıq
$message = getMessage();

// Müştəri silmə əməliyyatı
if (isset($_POST['delete']) && isset($_POST['customer_id'])) {
    $customerId = (int)$_POST['customer_id'];
    if ($customerObj->delete($customerId)) {
        setMessage('Müştəri uğurla silindi');
    } else {
        setMessage('Müştəri silinə bilmədi. Aktiv kreditləri ola bilər.', 'error');
    }
    header('Location: customers.php');
    exit();
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
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center mb-3">
                <h1 class="h2">Müştərilər</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="customer_add.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Yeni Müştəri
                    </a>
                </div>
            </div>

            <?php if ($message): ?>
            <div class="alert alert-<?php echo $message['type'] == 'error' ? 'danger' : 'success'; ?> alert-dismissible fade show">
                <?php echo $message['message']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <!-- Müştəri Cədvəli -->
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover datatable">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Ad Soyad</th>
                                    <th>Ata adı</th>
                                    <th>Ş/V Seriya</th>
                                    <th>FIN kod</th>
                                    <th>Aktiv Kredit</th>
                                    <th>Qeydiyyat tarixi</th>
                                    <th>Əməliyyatlar</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($customers)): ?>
                                    <?php foreach ($customers as $customer): ?>
                                    <tr>
                                        <td><?php echo $customer['id']; ?></td>
                                        <td><?php echo $customer['first_name'] . ' ' . $customer['last_name']; ?></td>
                                        <td><?php echo $customer['father_name']; ?></td>
                                        <td><?php echo $customer['id_number']; ?></td>
                                        <td><?php echo $customer['fin_code']; ?></td>
                                        <td>
                                            <?php if ($customerObj->hasActiveCredits($customer['id'])): ?>
                                                <span class="badge bg-success">Var</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Yox</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo date('d.m.Y', strtotime($customer['created_at'])); ?></td>
                                        <td>
                                            <div class="btn-group">
                                                <a href="customer_view.php?id=<?php echo $customer['id']; ?>" 
                                                   class="btn btn-sm btn-info" title="Ətraflı">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="customer_edit.php?id=<?php echo $customer['id']; ?>" 
                                                   class="btn btn-sm btn-warning" title="Düzəliş et">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <?php if (!$customerObj->hasActiveCredits($customer['id'])): ?>
                                                <button type="button" class="btn btn-sm btn-danger" 
                                                        onclick="confirmDelete(<?php echo $customer['id']; ?>)"
                                                        title="Sil">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8" class="text-center">Müştəri tapılmadı</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Silmə Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Müştərini Sil</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Bu müştərini silmək istədiyinizə əminsiniz?</p>
            </div>
            <div class="modal-footer">
                <form method="POST">
                    <input type="hidden" name="customer_id" id="deleteCustomerId">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Ləğv et</button>
                    <button type="submit" name="delete" class="btn btn-danger">Sil</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// DataTables inisializasiyası
$(document).ready(function() {
    $('.datatable').DataTable({
        language: {
            url: '../assets/js/datatables-az.json'
        },
        pageLength: 25,
        order: [[0, 'desc']]
    });
});

// Silmə təsdiqi
function confirmDelete(customerId) {
    document.getElementById('deleteCustomerId').value = customerId;
    var deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
    deleteModal.show();
}
</script>

<?php
// Footer
include '../templates/admin/footer.php';
?>