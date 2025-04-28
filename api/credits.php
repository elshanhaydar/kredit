<?php
require_once '../config/config.php';
require_once '../includes/Database.php';
require_once '../includes/Credit.php';
require_once '../includes/Notification.php';

header('Content-Type: application/json');

// API üçün auth yoxlaması
session_start();
if (!isset($_SESSION['admin_id']) && !isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$creditObj = new Credit();
$notificationObj = new Notification();

// GET sorğuları
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';
    
    switch ($action) {
        // Kredit məlumatlarını al
        case 'get':
            if (isset($_GET['id'])) {
                $credit = $creditObj->getById($_GET['id']);
                if ($credit) {
                    // İstifadəçi öz kreditinə baxırsa
                    if (isset($_SESSION['user_id']) && $credit['customer_id'] != $_SESSION['user_id']) {
                        http_response_code(403);
                        echo json_encode(['error' => 'Bu krediti görmək üçün icazəniz yoxdur']);
                        exit();
                    }
                    echo json_encode($credit);
                } else {
                    http_response_code(404);
                    echo json_encode(['error' => 'Kredit tapılmadı']);
                }
            } else {
                http_response_code(400);
                echo json_encode(['error' => 'Kredit ID tələb olunur']);
            }
            break;

        // Kredit ödənişlərini al
        case 'payments':
            if (isset($_GET['id'])) {
                $credit = $creditObj->getById($_GET['id']);
                // İstifadəçi yoxlaması
                if (isset($_SESSION['user_id']) && $credit['customer_id'] != $_SESSION['user_id']) {
                    http_response_code(403);
                    echo json_encode(['error' => 'Bu krediti görmək üçün icazəniz yoxdur']);
                    exit();
                }
                
                $payments = $creditObj->getPayments($_GET['id']);
                echo json_encode($payments);
            } else {
                http_response_code(400);
                echo json_encode(['error' => 'Kredit ID tələb olunur']);
            }
            break;

        // Kredit hesablaması
        case 'calculate':
            if (isset($_GET['amount']) && isset($_GET['period']) && isset($_GET['rate'])) {
                $amount = floatval($_GET['amount']);
                $period = intval($_GET['period']);
                $rate = floatval($_GET['rate']);
                $initialPayment = isset($_GET['initial_payment']) ? floatval($_GET['initial_payment']) : 0;

                // Validasiya
                if ($amount < MIN_CREDIT_AMOUNT || $amount > MAX_CREDIT_AMOUNT) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Kredit məbləği düzgün deyil']);
                    exit();
                }
                if ($period < MIN_CREDIT_PERIOD || $period > MAX_CREDIT_PERIOD) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Kredit müddəti düzgün deyil']);
                    exit();
                }
                if ($rate < MIN_INTEREST_RATE || $rate > MAX_INTEREST_RATE) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Faiz dərəcəsi düzgün deyil']);
                    exit();
                }

                $monthlyPayment = Credit::calculateMonthlyPayment($amount, $rate, $period, $initialPayment);
                $totalAmount = Credit::calculateTotalAmount($monthlyPayment, $period, $initialPayment);

                echo json_encode([
                    'monthly_payment' => round($monthlyPayment, 2),
                    'total_amount' => round($totalAmount, 2),
                    'interest_amount' => round($totalAmount - $amount, 2)
                ]);
            } else {
                http_response_code(400);
                echo json_encode(['error' => 'Məbləğ, müddət və faiz dərəcəsi tələb olunur']);
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
    $data = json_decode(file_get_contents('php://input'), true);
    $action = $_POST['action'] ?? $data['action'] ?? '';

    switch ($action) {
        // Yeni kredit əlavə et
        case 'create':
            // Yalnız admin
            if (!isset($_SESSION['admin_id'])) {
                http_response_code(403);
                echo json_encode(['error' => 'Bu əməliyyat üçün admin icazəsi tələb olunur']);
                exit();
            }

            if (validateCreditData($data)) {
                $creditId = $creditObj->create($data);
                if ($creditId) {
                    // Bildiriş göndəririk
                    $notificationObj->createCreditApprovedNotification($creditId);

                    echo json_encode([
                        'success' => true,
                        'credit_id' => $creditId,
                        'message' => 'Kredit uğurla əlavə edildi'
                    ]);
                } else {
                    http_response_code(500);
                    echo json_encode(['error' => 'Kredit əlavə edilərkən xəta baş verdi']);
                }
            } else {
                http_response_code(400);
                echo json_encode(['error' => 'Yanlış və ya natamam məlumat']);
            }
            break;

        // Kredit statusunu yenilə
        case 'update_status':
            // Yalnız admin
            if (!isset($_SESSION['admin_id'])) {
                http_response_code(403);
                echo json_encode(['error' => 'Bu əməliyyat üçün admin icazəsi tələb olunur']);
                exit();
            }

            if (isset($data['id']) && isset($data['status'])) {
                if ($creditObj->updateStatus($data['id'], $data['status'])) {
                    // Status dəyişikliyi bildirişi
                    if ($data['status'] == CREDIT_STATUS_ACTIVE) {
                        $notificationObj->createCreditApprovedNotification($data['id']);
                    } elseif ($data['status'] == CREDIT_STATUS_REJECTED) {
                        $notificationObj->createCreditRejectedNotification($data['id']);
                    }

                    echo json_encode([
                        'success' => true,
                        'message' => 'Kredit statusu yeniləndi'
                    ]);
                } else {
                    http_response_code(500);
                    echo json_encode(['error' => 'Status yenilənərkən xəta baş verdi']);
                }
            } else {
                http_response_code(400);
                echo json_encode(['error' => 'Kredit ID və status tələb olunur']);
            }
            break;

        // Ödəniş et
        case 'make_payment':
            if (isset($data['credit_id']) && isset($data['payment_number']) && isset($data['amount'])) {
                // Kredit sahibi yoxlaması
                $credit = $creditObj->getById($data['credit_id']);
                if (isset($_SESSION['user_id']) && $credit['customer_id'] != $_SESSION['user_id']) {
                    http_response_code(403);
                    echo json_encode(['error' => 'Bu kredit üzrə ödəniş etmək üçün icazəniz yoxdur']);
                    exit();
                }

                if ($creditObj->makePayment($data['credit_id'], $data['payment_number'], $data['amount'])) {
                    echo json_encode([
                        'success' => true,
                        'message' => 'Ödəniş uğurla qeydə alındı'
                    ]);
                } else {
                    http_response_code(500);
                    echo json_encode(['error' => 'Ödəniş qeydə alınarkən xəta baş verdi']);
                }
            } else {
                http_response_code(400);
                echo json_encode(['error' => 'Kredit ID, ödəniş nömrəsi və məbləğ tələb olunur']);
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

// Kredit məlumatlarının validasiyası
function validateCreditData($data) {
    return (
        isset($data['customer_id']) && !empty($data['customer_id']) &&
        isset($data['amount']) && is_numeric($data['amount']) &&
        $data['amount'] >= MIN_CREDIT_AMOUNT && $data['amount'] <= MAX_CREDIT_AMOUNT &&
        isset($data['period_months']) && is_numeric($data['period_months']) &&
        $data['period_months'] >= MIN_CREDIT_PERIOD && $data['period_months'] <= MAX_CREDIT_PERIOD &&
        isset($data['interest_rate']) && is_numeric($data['interest_rate']) &&
        $data['interest_rate'] >= MIN_INTEREST_RATE && $data['interest_rate'] <= MAX_INTEREST_RATE &&
        isset($data['monthly_payment']) && is_numeric($data['monthly_payment']) &&
        (!isset($data['initial_payment']) || is_numeric($data['initial_payment']))
    );
}