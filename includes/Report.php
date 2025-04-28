<?php
class Report {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    // Ümumi sistem statistikası
    public function getDashboardStats() {
        try {
            $sql = "SELECT
                    (SELECT COUNT(*) FROM customers) as total_customers,
                    (SELECT COUNT(*) FROM credits) as total_credits,
                    (SELECT COUNT(*) FROM credits WHERE status = :active) as active_credits,
                    (SELECT COUNT(*) FROM credits WHERE status = :delayed) as delayed_credits,
                    (SELECT COUNT(*) FROM credits WHERE status = :completed) as completed_credits,
                    (SELECT SUM(amount) FROM credits) as total_credit_amount,
                    (SELECT SUM(amount) FROM credits WHERE status = :active) as active_credit_amount,
                    (SELECT COUNT(*) FROM payments WHERE status = 'pending' AND payment_date < CURDATE()) as overdue_payments";

            return $this->db->selectOne($sql, [
                ':active' => CREDIT_STATUS_ACTIVE,
                ':delayed' => CREDIT_STATUS_DELAYED,
                ':completed' => CREDIT_STATUS_COMPLETED
            ]);
        } catch (Exception $e) {
            error_log($e->getMessage());
            return false;
        }
    }

    // Aylıq kredit hesabatı
    public function getMonthlyCreditReport($year = null, $month = null) {
        if (!$year) $year = date('Y');
        if (!$month) $month = date('m');

        $sql = "SELECT 
                COUNT(*) as total_credits,
                SUM(amount) as total_amount,
                AVG(interest_rate) as avg_interest_rate,
                SUM(initial_payment) as total_initial_payments,
                COUNT(CASE WHEN status = :active THEN 1 END) as active_credits,
                COUNT(CASE WHEN status = :delayed THEN 1 END) as delayed_credits
                FROM credits
                WHERE YEAR(created_at) = :year 
                AND MONTH(created_at) = :month";

        return $this->db->selectOne($sql, [
            ':year' => $year,
            ':month' => $month,
            ':active' => CREDIT_STATUS_ACTIVE,
            ':delayed' => CREDIT_STATUS_DELAYED
        ]);
    }

    // Ödəniş hesabatı
    public function getPaymentReport($startDate = null, $endDate = null) {
        if (!$startDate) $startDate = date('Y-m-01');
        if (!$endDate) $endDate = date('Y-m-t');

        $sql = "SELECT 
                COUNT(*) as total_payments,
                SUM(amount) as total_amount,
                SUM(CASE WHEN status = 'paid' THEN amount ELSE 0 END) as paid_amount,
                SUM(CASE WHEN status = 'pending' AND payment_date < CURDATE() THEN amount ELSE 0 END) as overdue_amount,
                COUNT(CASE WHEN status = 'paid' THEN 1 END) as paid_count,
                COUNT(CASE WHEN status = 'pending' AND payment_date < CURDATE() THEN 1 END) as overdue_count
                FROM payments
                WHERE payment_date BETWEEN :start_date AND :end_date";

        return $this->db->selectOne($sql, [
            ':start_date' => $startDate,
            ':end_date' => $endDate
        ]);
    }

    // Müştəri aktivliyi hesabatı
    public function getCustomerActivityReport() {
        $sql = "SELECT 
                cs.id,
                cs.first_name,
                cs.last_name,
                COUNT(c.id) as total_credits,
                SUM(c.amount) as total_credit_amount,
                COUNT(CASE WHEN c.status = :active THEN 1 END) as active_credits,
                COUNT(CASE WHEN c.status = :delayed THEN 1 END) as delayed_credits,
                MAX(c.created_at) as last_credit_date
                FROM customers cs
                LEFT JOIN credits c ON cs.id = c.customer_id
                GROUP BY cs.id
                ORDER BY total_credits DESC";

        return $this->db->select($sql, [
            ':active' => CREDIT_STATUS_ACTIVE,
            ':delayed' => CREDIT_STATUS_DELAYED
        ]);
    }

    // Gecikmiş ödənişlər hesabatı
    public function getOverduePaymentsReport() {
        $sql = "SELECT 
                c.id as credit_id,
                cs.first_name,
                cs.last_name,
                c.amount as credit_amount,
                p.payment_date,
                p.amount as payment_amount,
                DATEDIFF(CURDATE(), p.payment_date) as days_overdue
                FROM payments p
                JOIN credits c ON p.credit_id = c.id
                JOIN customers cs ON c.customer_id = cs.id
                WHERE p.status = 'pending'
                AND p.payment_date < CURDATE()
                ORDER BY p.payment_date ASC";

        return $this->db->select($sql);
    }

    // Kredit müddəti analizi
    public function getCreditPeriodAnalysis() {
        $sql = "SELECT 
                period_months,
                COUNT(*) as credit_count,
                AVG(amount) as avg_amount,
                AVG(interest_rate) as avg_interest_rate
                FROM credits
                GROUP BY period_months
                ORDER BY period_months ASC";

        return $this->db->select($sql);
    }

    // Faiz dərəcəsi analizi
    public function getInterestRateAnalysis() {
        $sql = "SELECT 
                ROUND(interest_rate, 1) as interest_rate,
                COUNT(*) as credit_count,
                AVG(amount) as avg_amount,
                AVG(period_months) as avg_period
                FROM credits
                GROUP BY ROUND(interest_rate, 1)
                ORDER BY interest_rate ASC";

        return $this->db->select($sql);
    }

    // Kredit məbləği analizi
    public function getCreditAmountAnalysis() {
        $sql = "SELECT 
                CASE 
                    WHEN amount <= 1000 THEN '0-1000'
                    WHEN amount <= 5000 THEN '1001-5000'
                    WHEN amount <= 10000 THEN '5001-10000'
                    ELSE '10000+'
                END as amount_range,
                COUNT(*) as credit_count,
                AVG(interest_rate) as avg_interest_rate,
                AVG(period_months) as avg_period
                FROM credits
                GROUP BY amount_range
                ORDER BY MIN(amount)";

        return $this->db->select($sql);
    }

    // Excel formatında hesabat yaratmaq
    public function generateExcelReport($type, $params = []) {
        // Hesabat tipinə görə məlumatları alırıq
        switch ($type) {
            case 'monthly':
                $data = $this->getMonthlyCreditReport($params['year'] ?? null, $params['month'] ?? null);
                $filename = "kredit_hesabati_{$params['year']}_{$params['month']}.xlsx";
                break;
            case 'payments':
                $data = $this->getPaymentReport($params['start_date'] ?? null, $params['end_date'] ?? null);
                $filename = "odenish_hesabati.xlsx";
                break;
            case 'overdue':
                $data = $this->getOverduePaymentsReport();
                $filename = "gecikme_hesabati.xlsx";
                break;
            default:
                return false;
        }

        if (!$data) return false;

        // Excel faylı yaradırıq
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Başlıqları əlavə edirik
        $headers = array_keys($data[0] ?? $data);
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '1', ucfirst(str_replace('_', ' ', $header)));
            $col++;
        }

        // Məlumatları əlavə edirik
        $row = 2;
        if (isset($data[0])) {
            foreach ($data as $record) {
                $col = 'A';
                foreach ($record as $value) {
                    $sheet->setCellValue($col . $row, $value);
                    $col++;
                }
                $row++;
            }
        } else {
            $col = 'A';
            foreach ($data as $value) {
                $sheet->setCellValue($col . $row, $value);
                $col++;
            }
        }

        // Faylı yaradırıq
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $filepath = __DIR__ . '/../reports/' . $filename;
        $writer->save($filepath);

        return $filepath;
    }

    // PDF formatında hesabat yaratmaq
    public function generatePDFReport($type, $params = []) {
        // PDF yaratma kodu...
        // Bu hissəni TCPDF və ya DOMPDF istifadə edərək tamamlaya bilərsiniz
    }

    // Qrafik məlumatları
    public function getChartData($type, $params = []) {
        switch ($type) {
            case 'monthly_credits':
                return $this->getMonthlyCreditsChartData($params);
            case 'payment_status':
                return $this->getPaymentStatusChartData($params);
            case 'credit_amounts':
                return $this->getCreditAmountsChartData($params);
            default:
                return false;
        }
    }

    // Aylıq kreditlər qrafiki üçün məlumatlar
    private function getMonthlyCreditsChartData($params) {
        $sql = "SELECT 
                DATE_FORMAT(created_at, '%Y-%m') as month,
                COUNT(*) as credit_count,
                SUM(amount) as total_amount
                FROM credits
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
                GROUP BY month
                ORDER BY month ASC";

        return $this->db->select($sql);
    }

    // Ödəniş statusları qrafiki üçün məlumatlar
    private function getPaymentStatusChartData($params) {
        $sql = "SELECT 
                status,
                COUNT(*) as payment_count,
                SUM(amount) as total_amount
                FROM payments
                GROUP BY status";

        return $this->db->select($sql);
    }

    // Kredit məbləğləri qrafiki üçün məlumatlar
    private function getCreditAmountsChartData($params) {
        $sql = "SELECT 
                CASE 
                    WHEN amount <= 1000 THEN '0-1000'
                    WHEN amount <= 5000 THEN '1001-5000'
                    WHEN amount <= 10000 THEN '5001-10000'
                    ELSE '10000+'
                END as amount_range,
                COUNT(*) as credit_count
                FROM credits
                GROUP BY amount_range
                ORDER BY MIN(amount)";

        return $this->db->select($sql);
    }
}