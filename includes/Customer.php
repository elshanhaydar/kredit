<?php
class Customer {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    // Yeni müştəri əlavə etmək
    public function create($data) {
        $sql = "INSERT INTO customers (first_name, last_name, father_name, id_number, fin_code, created_at) 
                VALUES (:first_name, :last_name, :father_name, :id_number, :fin_code, NOW())";

        $params = [
            ':first_name' => $data['first_name'],
            ':last_name' => $data['last_name'],
            ':father_name' => $data['father_name'],
            ':id_number' => $data['id_number'],
            ':fin_code' => $data['fin_code']
        ];

        return $this->db->insert($sql, $params);
    }

    // Müştəri məlumatlarını yeniləmək
    public function update($id, $data) {
        $sql = "UPDATE customers 
                SET first_name = :first_name,
                    last_name = :last_name,
                    father_name = :father_name,
                    id_number = :id_number,
                    fin_code = :fin_code
                WHERE id = :id";

        $params = [
            ':id' => $id,
            ':first_name' => $data['first_name'],
            ':last_name' => $data['last_name'],
            ':father_name' => $data['father_name'],
            ':id_number' => $data['id_number'],
            ':fin_code' => $data['fin_code']
        ];

        return $this->db->update($sql, $params);
    }

    // Müştərini silmək
    public function delete($id) {
        // Əvvəlcə müştərinin aktiv krediti olub-olmadığını yoxlayırıq
        if ($this->hasActiveCredits($id)) {
            return false;
        }

        $sql = "DELETE FROM customers WHERE id = :id";
        return $this->db->delete($sql, [':id' => $id]);
    }

    // Tək müştəri məlumatlarını almaq
    public function getById($id) {
        $sql = "SELECT * FROM customers WHERE id = :id";
        return $this->db->selectOne($sql, [':id' => $id]);
    }

    // Bütün müştəriləri almaq
    public function getAll($limit = null, $offset = 0) {
        $sql = "SELECT * FROM customers ORDER BY created_at DESC";
        
        if ($limit !== null) {
            $sql .= " LIMIT :limit OFFSET :offset";
            return $this->db->select($sql, [':limit' => $limit, ':offset' => $offset]);
        }

        return $this->db->select($sql);
    }

    // FIN kod və ya şəxsiyyət vəsiqəsi nömrəsinə görə axtarış
    public function search($term) {
        $sql = "SELECT * FROM customers 
                WHERE fin_code LIKE :term 
                OR id_number LIKE :term 
                OR CONCAT(first_name, ' ', last_name) LIKE :term";

        return $this->db->select($sql, [':term' => "%$term%"]);
    }

    // Müştərinin aktiv kreditlərini yoxlamaq
    public function hasActiveCredits($customerId) {
        $sql = "SELECT COUNT(*) as count FROM credits 
                WHERE customer_id = :customer_id 
                AND status = :status";

        $result = $this->db->selectOne($sql, [
            ':customer_id' => $customerId,
            ':status' => CREDIT_STATUS_ACTIVE
        ]);

        return $result['count'] > 0;
    }

    // Müştərinin bütün kreditlərini almaq
    public function getCredits($customerId) {
        $sql = "SELECT c.*, cs.first_name, cs.last_name 
                FROM credits c 
                JOIN customers cs ON c.customer_id = cs.id 
                WHERE c.customer_id = :customer_id 
                ORDER BY c.created_at DESC";

        return $this->db->select($sql, [':customer_id' => $customerId]);
    }

    // FIN kodun mövcudluğunu yoxlamaq
    public function isFinCodeExists($finCode, $excludeId = null) {
        $sql = "SELECT COUNT(*) as count FROM customers WHERE fin_code = :fin_code";
        $params = [':fin_code' => $finCode];

        if ($excludeId !== null) {
            $sql .= " AND id != :id";
            $params[':id'] = $excludeId;
        }

        $result = $this->db->selectOne($sql, $params);
        return $result['count'] > 0;
    }

    // Şəxsiyyət vəsiqəsi nömrəsinin mövcudluğunu yoxlamaq
    public function isIdNumberExists($idNumber, $excludeId = null) {
        $sql = "SELECT COUNT(*) as count FROM customers WHERE id_number = :id_number";
        $params = [':id_number' => $idNumber];

        if ($excludeId !== null) {
            $sql .= " AND id != :id";
            $params[':id'] = $excludeId;
        }

        $result = $this->db->selectOne($sql, $params);
        return $result['count'] > 0;
    }

    // Müştəri statistikasını almaq
    public function getStatistics() {
        $sql = "SELECT 
                COUNT(*) as total_customers,
                COUNT(CASE WHEN EXISTS (
                    SELECT 1 FROM credits 
                    WHERE credits.customer_id = customers.id 
                    AND credits.status = :active_status
                ) THEN 1 END) as customers_with_active_credits,
                COUNT(CASE WHEN EXISTS (
                    SELECT 1 FROM credits 
                    WHERE credits.customer_id = customers.id 
                    AND credits.status = :delayed_status
                ) THEN 1 END) as customers_with_delayed_credits
                FROM customers";

        return $this->db->selectOne($sql, [
            ':active_status' => CREDIT_STATUS_ACTIVE,
            ':delayed_status' => CREDIT_STATUS_DELAYED
        ]);
    }
}