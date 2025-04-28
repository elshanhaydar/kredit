<?php
require_once '../config/config.php';
require_once '../includes/Database.php';
require_once '../includes/Notification.php';

header('Content-Type: application/json');

// API üçün auth yoxlaması
session_start();
if (!isset($_SESSION['admin_id']) && !isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$notificationObj = new Notification();

// GET sorğuları
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';
    
    switch ($action) {
        // Bildirişləri al
        case 'get':
            if (isset($_SESSION['user_id'])) {
                // Müştəri bildirişləri
                $notifications = $notificationObj->getCustomerNotifications($_SESSION['user_id']);
                $unreadCount = $notificationObj->getUnreadCount($_SESSION['user_id']);
                
                echo json_encode([
                    'notifications' => $notifications,
                    'unread' => $unreadCount
                ]);
            } elseif (isset($_SESSION['admin_id'])) {
                // Admin bildirişləri (bütün sistemdəki)
                $notifications = $notificationObj->getCustomerNotifications(null);
                echo json_encode([
                    'notifications' => $notifications
                ]);
            }
            break;

        // Oxunmamış bildiriş sayını al
        case 'unread_count':
            if (isset($_SESSION['user_id'])) {
                $count = $notificationObj->getUnreadCount($_SESSION['user_id']);
                echo json_encode(['unread_count' => $count]);
            } else {
                http_response_code(400);
                echo json_encode(['error' => 'İstifadəçi ID tapılmadı']);
            }
            break;

        // Yaxınlaşan ödənişləri yoxla
        case 'check_upcoming':
            if (isset($_SESSION['admin_id'])) {
                $count = $notificationObj->checkUpcomingPayments();
                echo json_encode([
                    'success' => true,
                    'count' => $count,
                    'message' => $count . ' yaxınlaşan ödəniş bildirişi yaradıldı'
                ]);
            } else {
                http_response_code(403);
                echo json_encode(['error' => 'Bu əməliyyat üçün admin icazəsi tələb olunur']);
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
        // Bildirişi oxunmuş et
        case 'mark_read':
            if (isset($data['id'])) {
                // Bildiriş sahibi yoxlaması
                $notification = $notificationObj->getById($data['id']);
                if (!isset($_SESSION['admin_id']) && 
                    isset($_SESSION['user_id']) && 
                    $notification['customer_id'] != $_SESSION['user_id']) {
                    http_response_code(403);
                    echo json_encode(['error' => 'Bu bildirişi idarə etmək üçün icazəniz yoxdur']);
                    exit();
                }

                if ($notificationObj->markAsRead($data['id'])) {
                    echo json_encode([
                        'success' => true,
                        'message' => 'Bildiriş oxunmuş kimi işarələndi'
                    ]);
                } else {
                    http_response_code(500);
                    echo json_encode(['error' => 'Bildiriş yenilənərkən xəta baş verdi']);
                }
            } else {
                http_response_code(400);
                echo json_encode(['error' => 'Bildiriş ID tələb olunur']);
            }
            break;

        // Çoxlu bildirişləri oxunmuş et
        case 'mark_multiple_read':
            if (isset($data['ids']) && is_array($data['ids'])) {
                // İstifadəçi yoxlaması
                if (!isset($_SESSION['admin_id'])) {
                    foreach ($data['ids'] as $id) {
                        $notification = $notificationObj->getById($id);
                        if ($notification['customer_id'] != $_SESSION['user_id']) {
                            http_response_code(403);
                            echo json_encode(['error' => 'Bəzi bildirişləri idarə etmək üçün icazəniz yoxdur']);
                            exit();
                        }
                    }
                }

                if ($notificationObj->markMultipleAsRead($data['ids'])) {
                    echo json_encode([
                        'success' => true,
                        'message' => 'Bildirişlər oxunmuş kimi işarələndi'
                    ]);
                } else {
                    http_response_code(500);
                    echo json_encode(['error' => 'Bildirişlər yenilənərkən xəta baş verdi']);
                }
            } else {
                http_response_code(400);
                echo json_encode(['error' => 'Bildiriş ID-ləri tələb olunur']);
            }
            break;

        // Yeni bildiriş yarat (admin üçün)
        case 'create':
            if (!isset($_SESSION['admin_id'])) {
                http_response_code(403);
                echo json_encode(['error' => 'Bu əməliyyat üçün admin icazəsi tələb olunur']);
                exit();
            }

            if (validateNotificationData($data)) {
                $notificationId = $notificationObj->create($data);
                if ($notificationId) {
                    echo json_encode([
                        'success' => true,
                        'notification_id' => $notificationId,
                        'message' => 'Bildiriş yaradıldı'
                    ]);
                } else {
                    http_response_code(500);
                    echo json_encode(['error' => 'Bildiriş yaradılarkən xəta baş verdi']);
                }
            } else {
                http_response_code(400);
                echo json_encode(['error' => 'Yanlış və ya natamam məlumat']);
            }
            break;

        // Köhnə bildirişləri təmizlə
        case 'clean_old':
            if (!isset($_SESSION['admin_id'])) {
                http_response_code(403);
                echo json_encode(['error' => 'Bu əməliyyat üçün admin icazəsi tələb olunur']);
                exit();
            }

            $days = isset($data['days']) ? (int)$data['days'] : 30;
            $count = $notificationObj->cleanOldNotifications($days);
            
            echo json_encode([
                'success' => true,
                'count' => $count,
                'message' => $count . ' köhnə bildiriş təmizləndi'
            ]);
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

// Bildiriş məlumatlarının validasiyası
function validateNotificationData($data) {
    return (
        isset($data['customer_id']) && !empty($data['customer_id']) &&
        isset($data['message']) && !empty($data['message']) &&
        isset($data['type']) && !empty($data['type']) &&
        in_array($data['type'], [
            NOTIFICATION_PAYMENT_DUE,
            NOTIFICATION_PAYMENT_LATE,
            NOTIFICATION_CREDIT_APPROVED,
            NOTIFICATION_CREDIT_REJECTED
        ])
    );
}