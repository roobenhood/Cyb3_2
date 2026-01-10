<?php
/**
 * إعدادات قاعدة البيانات
 */

class Database {
    private $host = "localhost";
    private $db_name = "courses_db";
    private $username = "root";
    private $password = "";
    private $conn;

    public function getConnection() {
        $this->conn = null;

        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4",
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            echo json_encode([
                "success" => false,
                "message" => "خطأ في الاتصال بقاعدة البيانات: " . $e->getMessage()
            ]);
            exit;
        }

        return $this->conn;
    }
}
?>
