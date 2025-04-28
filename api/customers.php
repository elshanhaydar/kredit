<?php
require_once '../config/config.php';
require_once '../includes/Database.php';
require_once '../includes/Customer.php';

header('Content-Type: application/json');

// API üçün sadə auth yoxlaması
session_start();
if (!isset($_SESSION['admin_id']) && !isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$customerObj = new Customer();

// GET sorğuları
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';
    
    switch ($action) {
        // Müştəri məlumatlarını al
        case 'get':
            if (isset($_GET['id'])) {
                $customer = $customerObj->getById($_GET['id']);
                if ($customer) {
                    echo json_encode($customer);
                } else {
                    http_response_code(404);
                    echo json_encode(['error' => 'Müştəri tapılmadı']);
                }
            } else {
                http_response_code(400);
                echo json_encode(['error' => 'ID tələb olunur']);
            }
            break;

        // Müştəri axtarışı
        case 'search':
            if (isset($_GET['term'])) {
                $results = $customerObj->search($_GET['term']);
                echo json_encode($results);
            } else {
                http_response_code(400);
                echo json_encode(['error' => 'Axtarış termini tələb olunur']);
            }
            break;

        // Müştəri kreditlərini al
        case 'credits':
            if (isset($_GET['id'])) {
                $credits = $customerObj->getCredits($_GET['id']);
                echo json_encode($credits);
            } else {
                http_response_code(400);
                echo json_encode(['error' => 'Müştəri ID tələb olunur']);
            }
            break;

        // Müştəri statistikası
        case 'stats':
            if (isset($_GET['id'])) {
                $stats = [
                    'total_credits' => count($customerObj->getCredits($_GET['id'])),
                    'active_credits' => $customerObj->hasActiveCredits($_GET['id']),
                    // digər statistikalar
                ];
                echo json_encode($stats);
            } else {
                http_response_code(400);
                echo json_encode(['error' => 'Müştəri ID tələb olunur']);
            }
            break;

        default:
            http_response_code(400);
            echo json_encode(['error' => 'Yanlış əməliyyat']);
            break;
    }
}

// POST sorğuları
else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Yalnız admin girişinə icazə veririk
    if (!isset($_SESSION['admin_id'])) {
        http_response_code(403);
        echo json_encode(['error' => 'Bu əməliyyat üçün admin icazəsi tələb olunur']);
        exit();
    }

    $data = json_decode(file_get_contents('php://input'), true);
    $action = $_POST['action'] ?? $data['action'] ?? '';

    switch ($action) {
        // Yeni müştəri əlavə et
        case 'create':
            if (validateCustomerData($data)) {
                // FIN və şəxsiyyət vəsiqəsi yoxlanışı
                if ($customerObj->isFinCodeExists($data['fin_code'])) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Bu FIN kod artıq mövcuddur']);
                    exit();
                }
                if ($customerObj->isIdNumberExists($data['id_number'])) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Bu şəxsiyyət vəsiqəsi nömrəsi artıq mövcuddur']);
                    exit();
                }

                $customerId = $customerObj->create($data);
                if ($customerId) {
                    echo json_encode([
                        'success' => true,
                        'customer_id' => $customerId,
                        'message' => 'Müştəri uğurla əlavə edildi'
                    ]);
                } else {
                    http_response_code(500);
                    echo json_encode(['error' => 'Müştəri əlavə edilərkən xəta baş verdi']);
                }
            } else {
                http_response_code(400);
                echo json_encode(['error' => 'Yanlış və ya natamam məlumat']);
            }
            break;

        // Müştəri məlumatlarını yenilə
        case 'update':
            if (isset($data['id']) && validateCustomerData($data)) {
                // FIN və şəxsiyyət vəsiqəsi yoxlanışı
                if ($customerObj->isFinCodeExists($data['fin_code'], $data['id'])) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Bu FIN kod artıq mövcuddur']);
                    exit();
                }
                if ($customerObj->isIdNumberExists($data['id_number'], $data['id'])) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Bu şəxsiyyət vəsiqəsi nömrəsi artıq mövcuddur']);
                    exit();
                }

                if ($customerObj->update($data['id'], $data)) {
                    echo json_encode([
                        'success' => true,
                        'message' => 'Müştəri məlumatları yeniləndi'
                    ]);
                } else {
                    http_response_code(500);
                    echo json_encode(['error' => 'Məlumatlar yenilənərkən xəta baş verdi']);
                }
            } else {
                http_response_code(400);
                echo json_encode(['error' => 'Yanlış və ya natamam məlumat']);
            }
            break;

        // Müştərini sil
        case 'delete':
            if (isset($data['id'])) {
                if ($customerObj->delete($data['id'])) {
                    echo json_encode([
                        'success' => true,
                        'message' => 'Müştəri silindi'
                    ]);
                } else {
                    http_response_code(500);
                    echo json_encode(['error' => 'Müştəri silinərkən xəta baş verdi']);
                }
            } else {
                http_response_code(400);
                echo json_encode(['error' => 'Müştəri ID tələb olunur']);
            }
            break;

        default:
            http_response_code(400);
            echo json_encode(['error' => 'Yanlış əməliyyat']);
            break;
    }
}

// Digər sorğu metodları
else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}

// Müştəri məlumatlarının validasiyası
function validateCustomerData($data) {
    return (
        isset($data['first_name']) && !empty($data['first_name']) &&
        isset($data['last_name']) && !empty($data['last_name']) &&
        isset($data['father_name']) && !empty($data['father_name']) &&
        isset($data['id_number']) && !empty($data['id_number']) &&
        isset($data['fin_code']) && !empty($data['fin_code']) &&
        strlen($data['fin_code']) === 7 &&  // FIN kod 7 simvol olmalıdır
        preg_match('/^[A-Z]{2}[0-9]{7}$/', $data['id_number']) // Şəxsiyyət vəsiqəsi formatı
    );
}