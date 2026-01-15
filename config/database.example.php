<?php
/**
 * MARIANCONNECT - Database Configuration
 * St. Mary's College of Catbalogan
 */

class Database {
    private $host = "localhost";      // Usually localhost
    private $db_name = "your_database_name"; // Your database name
    private $username = "root";         // Your MySQL username
    private $password = "";            // Your MySQL password (blank for XAMPP)
    private $conn;
    
    /**
     * Get database connection
     */
    public function getConnection() {
        $this->conn = null;
        
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                $this->username,
                $this->password
            );
            
            // Set charset to utf8mb4 for full Unicode support
            $this->conn->exec("set names utf8mb4");
            
            // Set PDO error mode to exception
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Set default fetch mode to associative array
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            
        } catch(PDOException $exception) {
            error_log("Connection error: " . $exception->getMessage());
            die("Database connection failed. Please contact the administrator.");
        }
        
        return $this->conn;
    }
    
    /**
     * Close database connection
     */
    public function closeConnection() {
        $this->conn = null;
    }
}

/**
 * Get database instance (Singleton pattern)
 */
function getDB() {
    static $db = null;
    if ($db === null) {
        $database = new Database();
        $db = $database->getConnection();
    }
    return $db;
}
?>
