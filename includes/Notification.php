<?php
class Notification {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    // Yeni bildiriş yaratmaq
    public function create($data) {
        $sql = "INSERT INTO notifications (
                    customer_id, credit_id, message, type, is_read, created_at
                ) VALUES (
                    :customer_id, :credit_id, :message, :type, 0, NOW()
                )";

        $params = [
            ':customer_id' => $data['customer_id'],
            ':credit_id' => $data['credit_id'],
            ':message' => $data['message'],
            ':type' => $data['type']
        ];

        return $this->db->insert($sql, $params);
    }

    // Ödəniş bildirişi yaratmaq
    public function createPaymentNotification($creditId) {
        $credit = $this->getCreditInfo($creditId);
        if (!$credit) return false;

        $message = sprintf(
            "Hörmətli %s %s, sizin %s AZN məbləğində ödənişinizin vaxtı yaxınlaşır. Ödəniş tarixi: %s",
            $credit['first_name'],
            $credit['last_name'],
            number_format($credit['monthly_payment'], 2),
            $credit['next_payment_date']
        );

        return $this->create([
            'customer_id' => $credit['customer_id'],
            'credit_id' => $creditId,
            'message' => $message,
            'type' => NOTIFICATION_PAYMENT_DUE
        ]);
    }

    // Gecikmiş ödəniş bildirişi yaratmaq
    public function createLatePaymentNotification($creditId) {
        $credit = $this->getCreditInfo($creditId);
        if (!$credit) return false;

        $message = sprintf(
            "Hörmətli %s %s, sizin %s AZN məbləğində ödənişiniz gecikir. Xahiş edirik təcili ödəniş edin.",
            $credit['first_name'],
            $credit['last_name'],
            number_format($credit['monthly_payment'], 2)
        );

        return $this->create([
            'customer_id' => $credit['customer_id'],
            'credit_id' => $creditId,
            'message' => $message,
            'type' => NOTIFICATION_PAYMENT_LATE
        ]);
    }

    // Kredit təsdiqi bildirişi
    public function createCreditApprovedNotification($creditId) {
        $credit = $this->getCreditInfo($creditId);
        if (!$credit) return false;

        $message = sprintf(
            "Hörmətli %s %s, sizin %s AZN məbləğində kredit müraciətiniz təsdiq edilmişdir.",
            $credit['first_name'],
            $credit['last_name'],
            number_format($credit['amount'], 2)
        );

        return $this->create([
            'customer_id' => $credit['customer_id'],
            'credit_id' => $creditId,
            'message' => $message,
            'type' => NOTIFICATION_CREDIT_APPROVED
        ]);
    }

    // Kredit rədd edilməsi bildirişi
    public function createCreditRejectedNotification($creditId) {
        $credit = $this->getCreditInfo($creditId);
        if (!$credit) return false;

        $message = sprintf(
            "Hörmətli %s %s, təəssüf ki, sizin kredit müraciətiniz rədd edilmişdir.",
            $credit['first_name'],
            $credit['last_name']
        );

        return $this->create([
            'customer_id' => $credit['customer_id'],
            'credit_id' => $creditId,
            'message' => $message,
            'type' => NOTIFICATION_CREDIT_REJECTED
        ]);
    }

    // Yaxınlaşan ödənişləri yoxlamaq və bildiriş göndərmək
    public function checkUpcomingPayments() {
        $sql = "SELECT DISTINCT c.id as credit_id, c.customer_id, p.payment_date
                FROM credits c
                JOIN payments p ON c.id = p.credit_id
                WHERE c.status = :active_status
                AND p.status = 'pending'
                AND p.payment_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL :days DAY)
                AND NOT EXISTS (
                    SELECT 1 FROM notifications n 
                    WHERE n.credit_id = c.id 
                    AND n.type = :notification_type
                    AND DATE(n.created_at) = CURDATE()
                )";

        $upcomingPayments = $this->db->select($sql, [
            ':active_status' => CREDIT_STATUS_ACTIVE,
            ':days' => NOTIFICATION_DAYS,
            ':notification_type' => NOTIFICATION_PAYMENT_DUE
        ]);

        foreach ($upcomingPayments as $payment) {
            $this->createPaymentNotification($payment['credit_id']);
        }

        return count($upcomingPayments);
    }

    // Bildirişi oxunmuş kimi işarələmək
    public function markAsRead($id) {
        $sql = "UPDATE notifications SET is_read = 1 WHERE id = :id";
        return $this->db->update($sql, [':id' => $id]);
    }

    // Çoxlu bildirişləri oxunmuş kimi işarələmək
    public function markMultipleAsRead($ids) {
        if (empty($ids)) return false;

        $placeholders = str_repeat('?,', count($ids) - 1) . '?';
        $sql = "UPDATE notifications SET is_read = 1 WHERE id IN ($placeholders)";
        
        return $this->db->update($sql, $ids);
    }

    // Müştərinin bildirişlərini almaq
    public function getCustomerNotifications($customerId, $limit = null) {
        $sql = "SELECT * FROM notifications 
                WHERE customer_id = :customer_id 
                ORDER BY created_at DESC";

        if ($limit) {
            $sql .= " LIMIT :limit";
            return $this->db->select($sql, [
                ':customer_id' => $customerId,
                ':limit' => $limit
            ]);
        }

        return $this->db->select($sql, [':customer_id' => $customerId]);
    }

    // Oxunmamış bildiriş sayını almaq
    public function getUnreadCount($customerId) {
        $sql = "SELECT COUNT(*) as count FROM notifications 
                WHERE customer_id = :customer_id AND is_read = 0";

        $result = $this->db->selectOne($sql, [':customer_id' => $customerId]);
        return $result['count'];
    }

    // Kredit məlumatlarını almaq (daxili istifadə üçün)
    private function getCreditInfo($creditId) {
        $sql = "SELECT c.*, cs.first_name, cs.last_name, cs.customer_id,
                (SELECT MIN(payment_date) 
                 FROM payments 
                 WHERE credit_id = c.id AND status = 'pending') as next_payment_date
                FROM credits c
                JOIN customers cs ON c.customer_id = cs.id
                WHERE c.id = :credit_id";

        return $this->db->selectOne($sql, [':credit_id' => $creditId]);
    }

    // Köhnə bildirişləri təmizləmək
    public function cleanOldNotifications($days = 30) {
        $sql = "DELETE FROM notifications 
                WHERE created_at < DATE_SUB(NOW(), INTERVAL :days DAY) 
                AND is_read = 1";

        return $this->db->delete($sql, [':days' => $days]);
    }

    // Bildiriş statistikasını almaq
    public function getStatistics() {
        $sql = "SELECT 
                COUNT(*) as total_notifications,
                SUM(CASE WHEN is_read = 0 THEN 1 ELSE 0 END) as unread_notifications,
                COUNT(DISTINCT customer_id) as notified_customers,
                COUNT(DISTINCT credit_id) as notified_credits
                FROM notifications";

        return $this->db->selectOne($sql);
    }
}