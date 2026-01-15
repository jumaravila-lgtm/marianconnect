<?php
/**
 * MARIANCONNECT - Contact Class
 * Handles all contact message operations (CRUD)
 */

class Contact {
    
    private $db;
    private $table = 'contact_messages';
    
    /**
     * Constructor
     * 
     * @param PDO $db Database connection
     */
    public function __construct($db) {
        $this->db = $db;
    }
    
    /**
     * Get all messages with pagination and filters
     * 
     * @param array $options Filter options
     * @return array
     */
    public function getAll($options = []) {
        $page = $options['page'] ?? 1;
        $limit = $options['limit'] ?? 20;
        $offset = ($page - 1) * $limit;
        $status = $options['status'] ?? null;
        $search = $options['search'] ?? null;
        $orderBy = $options['order_by'] ?? 'created_at';
        $orderDir = $options['order_dir'] ?? 'DESC';
        
        // Build WHERE clause
        $where = [];
        $params = [];
        
        if ($status) {
            $where[] = "status = :status";
            $params[':status'] = $status;
        }
        
        if ($search) {
            $where[] = "(full_name LIKE :search OR email LIKE :search OR subject LIKE :search OR message LIKE :search)";
            $params[':search'] = "%{$search}%";
        }
        
        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        
        // Get total count
        $countSql = "SELECT COUNT(*) FROM {$this->table} {$whereClause}";
        $countStmt = $this->db->prepare($countSql);
        $countStmt->execute($params);
        $total = $countStmt->fetchColumn();
        
        // Get messages
        $sql = "
            SELECT m.*, a.full_name as replier_name
            FROM {$this->table} m
            LEFT JOIN admin_users a ON m.replied_by = a.admin_id
            {$whereClause}
            ORDER BY {$orderBy} {$orderDir}
            LIMIT :limit OFFSET :offset
        ";
        
        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return [
            'data' => $stmt->fetchAll(),
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'pages' => ceil($total / $limit)
        ];
    }
    
    /**
     * Get message by ID
     * 
     * @param int $id Message ID
     * @return array|null
     */
    public function getById($id) {
        $sql = "
            SELECT m.*, a.full_name as replier_name, a.email as replier_email
            FROM {$this->table} m
            LEFT JOIN admin_users a ON m.replied_by = a.admin_id
            WHERE m.message_id = :id
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }
    
    /**
     * Create new contact message
     * 
     * @param array $data Message data
     * @return int|bool Inserted ID or false on failure
     */
    public function create($data) {
        $sql = "
            INSERT INTO {$this->table} (
                full_name, email, phone, subject, message, 
                ip_address, user_agent
            ) VALUES (
                :full_name, :email, :phone, :subject, :message,
                :ip_address, :user_agent
            )
        ";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':full_name' => $data['full_name'],
                ':email' => $data['email'],
                ':phone' => $data['phone'] ?? null,
                ':subject' => $data['subject'],
                ':message' => $data['message'],
                ':ip_address' => $data['ip_address'] ?? $_SERVER['REMOTE_ADDR'],
                ':user_agent' => $data['user_agent'] ?? $_SERVER['HTTP_USER_AGENT']
            ]);
            
            return $this->db->lastInsertId();
        } catch (PDOException $e) {
            error_log("Contact message creation error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update message status
     * 
     * @param int $id Message ID
     * @param string $status Status (new, read, replied, archived)
     * @return bool
     */
    public function updateStatus($id, $status) {
        $sql = "UPDATE {$this->table} SET status = :status WHERE message_id = :id";
        
        try {
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([':status' => $status, ':id' => $id]);
        } catch (PDOException $e) {
            error_log("Status update error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Mark message as read
     * 
     * @param int $id Message ID
     * @return bool
     */
    public function markAsRead($id) {
        return $this->updateStatus($id, 'read');
    }
    
    /**
     * Mark message as replied
     * 
     * @param int $id Message ID
     * @param int $repliedBy Admin user ID
     * @return bool
     */
    public function markAsReplied($id, $repliedBy) {
        $sql = "
            UPDATE {$this->table} 
            SET status = 'replied', replied_by = :replied_by, replied_at = NOW()
            WHERE message_id = :id
        ";
        
        try {
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([':replied_by' => $repliedBy, ':id' => $id]);
        } catch (PDOException $e) {
            error_log("Mark as replied error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Archive message
     * 
     * @param int $id Message ID
     * @return bool
     */
    public function archive($id) {
        return $this->updateStatus($id, 'archived');
    }
    
    /**
     * Delete message
     * 
     * @param int $id Message ID
     * @return bool
     */
    public function delete($id) {
        $sql = "DELETE FROM {$this->table} WHERE message_id = :id";
        
        try {
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([':id' => $id]);
        } catch (PDOException $e) {
            error_log("Message deletion error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get unread messages count
     * 
     * @return int
     */
    public function getUnreadCount() {
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE status = 'new'";
        $stmt = $this->db->query($sql);
        return $stmt->fetchColumn();
    }
    
    /**
     * Get recent messages
     * 
     * @param int $limit Number of messages
     * @return array
     */
    public function getRecent($limit = 5) {
        $sql = "
            SELECT *
            FROM {$this->table}
            ORDER BY created_at DESC
            LIMIT :limit
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    /**
     * Get messages by status
     * 
     * @param string $status Status filter
     * @param int $limit Number of messages
     * @return array
     */
    public function getByStatus($status, $limit = 20) {
        $sql = "
            SELECT *
            FROM {$this->table}
            WHERE status = :status
            ORDER BY created_at DESC
            LIMIT :limit
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':status', $status);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    /**
     * Get statistics
     * 
     * @return array
     */
    public function getStatistics() {
        $stats = [];
        
        // Total messages
        $stmt = $this->db->query("SELECT COUNT(*) FROM {$this->table}");
        $stats['total'] = $stmt->fetchColumn();
        
        // New messages
        $stmt = $this->db->query("SELECT COUNT(*) FROM {$this->table} WHERE status = 'new'");
        $stats['new'] = $stmt->fetchColumn();
        
        // Read messages
        $stmt = $this->db->query("SELECT COUNT(*) FROM {$this->table} WHERE status = 'read'");
        $stats['read'] = $stmt->fetchColumn();
        
        // Replied messages
        $stmt = $this->db->query("SELECT COUNT(*) FROM {$this->table} WHERE status = 'replied'");
        $stats['replied'] = $stmt->fetchColumn();
        
        // Archived messages
        $stmt = $this->db->query("SELECT COUNT(*) FROM {$this->table} WHERE status = 'archived'");
        $stats['archived'] = $stmt->fetchColumn();
        
        return $stats;
    }
    
    /**
     * Search messages
     * 
     * @param string $query Search query
     * @param int $limit Number of results
     * @return array
     */
    public function search($query, $limit = 20) {
        $sql = "
            SELECT *
            FROM {$this->table}
            WHERE (full_name LIKE :query OR email LIKE :query OR subject LIKE :query OR message LIKE :query)
            ORDER BY created_at DESC
            LIMIT :limit
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':query', "%{$query}%");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    /**
     * Check for spam (rate limiting by IP)
     * 
     * @param string $ipAddress IP address
     * @param int $timeWindow Time window in minutes (default: 60)
     * @param int $maxMessages Max messages allowed in time window
     * @return bool True if spam detected
     */
    public function isSpam($ipAddress, $timeWindow = 60, $maxMessages = 5) {
        $sql = "
            SELECT COUNT(*) 
            FROM {$this->table}
            WHERE ip_address = :ip 
            AND created_at >= DATE_SUB(NOW(), INTERVAL :timeWindow MINUTE)
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':ip' => $ipAddress,
            ':timeWindow' => $timeWindow
        ]);
        
        $count = $stmt->fetchColumn();
        return $count >= $maxMessages;
    }
}
?>
