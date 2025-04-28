<?php
class Database {
    private $connection;
    private static $instance = null;

    private function __construct() {
        try {
            $this->connection = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER,
                DB_PASS,
                array(
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                )
            );
        } catch(PDOException $e) {
            die("Verilənlər bazası xətası: " . $e->getMessage());
        }
    }

    // Singleton pattern
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    // Select əməliyyatı
    public function select($sql, $params = []) {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch(PDOException $e) {
            $this->logError($e);
            return false;
        }
    }

    // Tək sətir seçmək üçün
    public function selectOne($sql, $params = []) {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch();
            return $result !== false ? $result : null;
        } catch(PDOException $e) {
            $this->logError($e);
            return null;
        }
    }

    // Insert əməliyyatı
    public function insert($sql, $params = []) {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $this->connection->lastInsertId();
        } catch(PDOException $e) {
            $this->logError($e);
            return false;
        }
    }

    // Update əməliyyatı
    public function update($sql, $params = []) {
        try {
            $stmt = $this->connection->prepare($sql);
            return $stmt->execute($params);
        } catch(PDOException $e) {
            $this->logError($e);
            return false;
        }
    }

    // Delete əməliyyatı
    public function delete($sql, $params = []) {
        try {
            $stmt = $this->connection->prepare($sql);
            return $stmt->execute($params);
        } catch(PDOException $e) {
            $this->logError($e);
            return false;
        }
    }

    // Transaction başlatmaq
    public function beginTransaction() {
        return $this->connection->beginTransaction();
    }

    // Transaction commit
    public function commit() {
        return $this->connection->commit();
    }

    // Transaction rollback
    public function rollback() {
        return $this->connection->rollBack();
    }

    // Xətaların loglanması
    private function logError($e) {
        $logDir = dirname(__DIR__) . '/logs';
        
        // Qovluq yoxdursa yaradırıq
        if (!is_dir($logDir)) {
            mkdir($logDir, 0777, true);
        }
        
        $errorMessage = date('Y-m-d H:i:s') . " - Error: " . $e->getMessage() . 
                       "\nFile: " . $e->getFile() . 
                       "\nLine: " . $e->getLine() . "\n\n";
        
        error_log($errorMessage, 3, $logDir . '/db_errors.log');
    }

    // Singleton pattern üçün clone-u bağlayırıq
    private function __clone() {}
}