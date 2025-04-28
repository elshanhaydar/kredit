<?php
class Credit {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    // Yeni kredit yaratmaq
    public function create($data) {
        try {
            $this->db->beginTransaction();

            // Kredit məlumatlarını əlavə edirik
            $sql = "INSERT INTO credits (
                customer_id, amount, interest_rate, period_months, 
                initial_payment, monthly_payment, status, created_at
            ) VALUES (
                :customer_id, :amount, :interest_rate, :period_months,
                :initial_payment, :monthly_payment, :status, NOW()
            )";

            $params = [
                ':customer_id' => $data['customer_id'],
                ':amount' => $data['amount'],
                ':interest_rate' => $data['interest_rate'],
                ':period_months' => $data['period_months'],
                ':initial_payment' => $data['initial_payment'],
                ':monthly_payment' => $data['monthly_payment'],
                ':status' => CREDIT_STATUS_PENDING
            ];

            $creditId = $this->db->insert($sql, $params);

            if (!$creditId) {
                throw new Exception("Kredit yaradılarkən xəta baş verdi");
            }

            // Ödəniş cədvəlini yaradırıq
            $this->createPaymentSchedule($creditId, $data);

            $this->db->commit();
            return $creditId;

        } catch (Exception $e) {
            $this->db->rollback();
            error_log($e->getMessage());
            return false;
        }
    }

    // Ödəniş cədvəlini yaratmaq
    private function createPaymentSchedule($creditId, $data) {
        $amount = $data['amount'] - $data['initial_payment'];
        $monthlyRate = $data['interest_rate'] / 12 / 100;
        $monthlyPayment = $data['monthly_payment'];

        for ($month = 1; $month <= $data['period_months']; $month++) {
            $interest = $amount * $monthlyRate;
            $principal = $monthlyPayment - $interest;
            $amount -= $principal;

            $paymentDate = date('Y-m-d', strtotime("+$month months"));

            $sql = "INSERT INTO payments (
                credit_id, payment_number, payment_date, amount,
                principal_amount, interest_amount, remaining_balance, status
            ) VALUES (
                :credit_id, :payment_number, :payment_date, :amount,
                :principal_amount, :interest_amount, :remaining_balance, :status
            )";

            $params = [
                ':credit_id' => $creditId,
                ':payment_number' => $month,
                ':payment_date' => $paymentDate,
                ':amount' => $monthlyPayment,
                ':principal_amount' => $principal,
                ':interest_amount' => $interest,
                ':remaining_balance' => max(0, $amount),
                ':status' => 'pending'
            ];

            $this->db->insert($sql, $params);
        }
    }

    // Kredit məlumatlarını yeniləmək
    public function update($id, $data) {
        $sql = "UPDATE credits SET 
                amount = :amount,
                interest_rate = :interest_rate,
                period_months = :period_months,
                initial_payment = :initial_payment,
                monthly_payment = :monthly_payment,
                status = :status
                WHERE id = :id";

        $params = [
            ':id' => $id,
            ':amount' => $data['amount'],
            ':interest_rate' => $data['interest_rate'],
            ':period_months' => $data['period_months'],
            ':initial_payment' => $data['initial_payment'],
            ':monthly_payment' => $data['monthly_payment'],
            ':status' => $data['status']
        ];

        return $this->db->update($sql, $params);
    }

    // Kredit statusunu yeniləmək
    public function updateStatus($id, $status) {
        $sql = "UPDATE credits SET status = :status WHERE id = :id";
        return $this->db->update($sql, [':id' => $id, ':status' => $status]);
    }

    // Krediti ID-yə görə almaq
    public function getById($id) {
        $sql = "SELECT c.*, cs.first_name, cs.last_name, cs.father_name, cs.id_number, cs.fin_code
                FROM credits c
                JOIN customers cs ON c.customer_id = cs.id
                WHERE c.id = :id";
        
        return $this->db->selectOne($sql, [':id' => $id]);
    }

    // Bütün kreditləri almaq
    public function getAll($filters = [], $limit = null, $offset = 0) {
        $sql = "SELECT c.*, cs.first_name, cs.last_name 
                FROM credits c
                JOIN customers cs ON c.customer_id = cs.id
                WHERE 1=1";
        
        $params = [];

        // Filtrləri əlavə edirik
        if (!empty($filters['status'])) {
            $sql .= " AND c.status = :status";
            $params[':status'] = $filters['status'];
        }

        if (!empty($filters['customer_id'])) {
            $sql .= " AND c.customer_id = :customer_id";
            $params[':customer_id'] = $filters['customer_id'];
        }

        $sql .= " ORDER BY c.created_at DESC";

        if ($limit !== null) {
            $sql .= " LIMIT :limit OFFSET :offset";
            $params[':limit'] = $limit;
            $params[':offset'] = $offset;
        }

        return $this->db->select($sql, $params);
    }

    // Kredit ödənişlərini almaq
    public function getPayments($creditId) {
        $sql = "SELECT * FROM payments 
                WHERE credit_id = :credit_id 
                ORDER BY payment_number ASC";

        return $this->db->select($sql, [':credit_id' => $creditId]);
    }

    // Ödəniş qeydə almaq
    public function makePayment($creditId, $paymentNumber, $amount) {
        try {
            $this->db->beginTransaction();

            // Ödənişi yeniləyirik
            $sql = "UPDATE payments 
                    SET status = 'paid', paid_amount = :amount, paid_date = NOW() 
                    WHERE credit_id = :credit_id AND payment_number = :payment_number";

            $params = [
                ':credit_id' => $creditId,
                ':payment_number' => $paymentNumber,
                ':amount' => $amount
            ];

            $this->db->update($sql, $params);

            // Bütün ödənişlər edilibsə krediti tamamlanmış kimi işarələyirik
            $this->checkCreditCompletion($creditId);

            $this->db->commit();
            return true;

        } catch (Exception $e) {
            $this->db->rollback();
            error_log($e->getMessage());
            return false;
        }
    }

    // Kredit tamamlanmasını yoxlamaq
    private function checkCreditCompletion($creditId) {
        $sql = "SELECT COUNT(*) as total, 
                SUM(CASE WHEN status = 'paid' THEN 1 ELSE 0 END) as paid 
                FROM payments 
                WHERE credit_id = :credit_id";

        $result = $this->db->selectOne($sql, [':credit_id' => $creditId]);

        if ($result['total'] == $result['paid']) {
            $this->updateStatus($creditId, CREDIT_STATUS_COMPLETED);
        }
    }

    // Gecikmiş ödənişləri yoxlamaq
    public function checkDelayedPayments() {
        $sql = "SELECT DISTINCT c.id, c.customer_id
                FROM credits c
                JOIN payments p ON c.id = p.credit_id
                WHERE c.status = :active_status
                AND p.status = 'pending'
                AND p.payment_date < CURDATE()";

        $delayedCredits = $this->db->select($sql, [
            ':active_status' => CREDIT_STATUS_ACTIVE
        ]);

        foreach ($delayedCredits as $credit) {
            $this->updateStatus($credit['id'], CREDIT_STATUS_DELAYED);
        }

        return count($delayedCredits);
    }

    // Kredit statistikasını almaq
    public function getStatistics() {
        $sql = "SELECT 
                COUNT(*) as total_credits,
                SUM(CASE WHEN status = :active_status THEN 1 ELSE 0 END) as active_credits,
                SUM(CASE WHEN status = :delayed_status THEN 1 ELSE 0 END) as delayed_credits,
                SUM(CASE WHEN status = :completed_status THEN 1 ELSE 0 END) as completed_credits,
                SUM(amount) as total_amount,
                AVG(interest_rate) as avg_interest_rate
                FROM credits";

        return $this->db->selectOne($sql, [
            ':active_status' => CREDIT_STATUS_ACTIVE,
            ':delayed_status' => CREDIT_STATUS_DELAYED,
            ':completed_status' => CREDIT_STATUS_COMPLETED
        ]);
    }

    // Aylıq ödənişi hesablamaq
    public static function calculateMonthlyPayment($amount, $rate, $months, $initialPayment = 0) {
        $loanAmount = $amount - $initialPayment;
        $monthlyRate = $rate / 12 / 100;
        
        return ($loanAmount * $monthlyRate * pow(1 + $monthlyRate, $months)) / 
               (pow(1 + $monthlyRate, $months) - 1);
    }

    // Ümumi ödəniləcək məbləği hesablamaq
    public static function calculateTotalAmount($monthlyPayment, $months, $initialPayment = 0) {
        return ($monthlyPayment * $months) + $initialPayment;
    }
}