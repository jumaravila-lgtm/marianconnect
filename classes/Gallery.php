<?php
/**
 * MARIANCONNECT - Gallery Class
 * Handles all gallery-related database operations (CRUD)
 */

class Gallery {
    
    private $db;
    private $table = 'gallery';
    
    /**
     * Constructor
     * 
     * @param PDO $db Database connection
     */
    public function __construct($db) {
        $this->db = $db;
    }
    
    /**
     * Get all gallery items with pagination and filters
     * 
     * @param array $options Filter options
     * @return array
     */
    public function getAll($options = []) {
        $page = $options['page'] ?? 1;
        $limit = $options['limit'] ?? 12;
        $offset = ($page - 1) * $limit;
        $category = $options['category'] ?? null;
        $eventId = $options['event_id'] ?? null;
        $isFeatured = $options['is_featured'] ?? null;
        $orderBy = $options['order_by'] ?? 'display_order';
        $orderDir = $options['order_dir'] ?? 'ASC';
        
        // Build WHERE clause
        $where = [];
        $params = [];
        
        if ($category) {
            $where[] = "category = :category";
            $params[':category'] = $category;
        }
        
        if ($eventId) {
            $where[] = "event_id = :event_id";
            $params[':event_id'] = $eventId;
        }
        
        if ($isFeatured !== null) {
            $where[] = "is_featured = :is_featured";
            $params[':is_featured'] = $isFeatured;
        }
        
        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        
        // Get total count
        $countSql = "SELECT COUNT(*) FROM {$this->table} {$whereClause}";
        $countStmt = $this->db->prepare($countSql);
        $countStmt->execute($params);
        $total = $countStmt->fetchColumn();
        
        // Get gallery items
        $sql = "
            SELECT g.*, a.full_name as uploader_name, e.title as event_title
            FROM {$this->table} g
            LEFT JOIN admin_users a ON g.uploaded_by = a.admin_id
            LEFT JOIN events e ON g.event_id = e.event_id
            {$whereClause}
            ORDER BY {$orderBy} {$orderDir}, created_at DESC
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
     * Get gallery item by ID
     * 
     * @param int $id Gallery ID
     * @return array|null
     */
    public function getById($id) {
        $sql = "
            SELECT g.*, a.full_name as uploader_name, e.title as event_title
            FROM {$this->table} g
            LEFT JOIN admin_users a ON g.uploaded_by = a.admin_id
            LEFT JOIN events e ON g.event_id = e.event_id
            WHERE g.gallery_id = :id
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }
    
    /**
     * Create new gallery item
     * 
     * @param array $data Gallery data
     * @return int|bool Inserted ID or false on failure
     */
    public function create($data) {
        $sql = "
            INSERT INTO {$this->table} (
                title, description, image_path, thumbnail_path, category,
                event_id, uploaded_by, display_order, is_featured
            ) VALUES (
                :title, :description, :image_path, :thumbnail_path, :category,
                :event_id, :uploaded_by, :display_order, :is_featured
            )
        ";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':title' => $data['title'],
                ':description' => $data['description'] ?? null,
                ':image_path' => $data['image_path'],
                ':thumbnail_path' => $data['thumbnail_path'] ?? null,
                ':category' => $data['category'] ?? 'other',
                ':event_id' => $data['event_id'] ?? null,
                ':uploaded_by' => $data['uploaded_by'],
                ':display_order' => $data['display_order'] ?? 0,
                ':is_featured' => $data['is_featured'] ?? 0
            ]);
            
            return $this->db->lastInsertId();
        } catch (PDOException $e) {
            error_log("Gallery creation error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update gallery item
     * 
     * @param int $id Gallery ID
     * @param array $data Updated data
     * @return bool
     */
    public function update($id, $data) {
        $fields = [];
        $params = [':id' => $id];
        
        $allowedFields = [
            'title', 'description', 'image_path', 'thumbnail_path',
            'category', 'event_id', 'display_order', 'is_featured'
        ];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $fields[] = "{$field} = :{$field}";
                $params[":{$field}"] = $data[$field];
            }
        }
        
        if (empty($fields)) {
            return false;
        }
        
        $sql = "UPDATE {$this->table} SET " . implode(', ', $fields) . " WHERE gallery_id = :id";
        
        try {
            $stmt = $this->db->prepare($sql);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            error_log("Gallery update error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Delete gallery item
     * 
     * @param int $id Gallery ID
     * @return bool
     */
    public function delete($id) {
        $sql = "DELETE FROM {$this->table} WHERE gallery_id = :id";
        
        try {
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([':id' => $id]);
        } catch (PDOException $e) {
            error_log("Gallery deletion error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get featured gallery items
     * 
     * @param int $limit Number of items
     * @return array
     */
    public function getFeatured($limit = 6) {
        $sql = "
            SELECT *
            FROM {$this->table}
            WHERE is_featured = 1
            ORDER BY display_order ASC, created_at DESC
            LIMIT :limit
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    /**
     * Get recent gallery items
     * 
     * @param int $limit Number of items
     * @return array
     */
    public function getRecent($limit = 12) {
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
     * Get gallery items by category
     * 
     * @param string $category Category name
     * @param int $limit Number of items
     * @return array
     */
    public function getByCategory($category, $limit = 12) {
        $sql = "
            SELECT *
            FROM {$this->table}
            WHERE category = :category
            ORDER BY display_order ASC, created_at DESC
            LIMIT :limit
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':category', $category);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    /**
     * Get gallery items by event
     * 
     * @param int $eventId Event ID
     * @return array
     */
    public function getByEvent($eventId) {
        $sql = "
            SELECT *
            FROM {$this->table}
            WHERE event_id = :event_id
            ORDER BY display_order ASC, created_at DESC
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':event_id' => $eventId]);
        return $stmt->fetchAll();
    }
    
    /**
     * Update display order
     * 
     * @param int $id Gallery ID
     * @param int $order New display order
     * @return bool
     */
    public function updateDisplayOrder($id, $order) {
        $sql = "UPDATE {$this->table} SET display_order = :order WHERE gallery_id = :id";
        
        try {
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([':order' => $order, ':id' => $id]);
        } catch (PDOException $e) {
            error_log("Display order update error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Bulk upload gallery items
     * 
     * @param array $items Array of gallery data
     * @return bool
     */
    public function bulkCreate($items) {
        try {
            $this->db->beginTransaction();
            
            foreach ($items as $item) {
                $this->create($item);
            }
            
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Bulk upload error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get category statistics
     * 
     * @return array
     */
    public function getCategoryStats() {
        $sql = "
            SELECT category, COUNT(*) as count
            FROM {$this->table}
            GROUP BY category
            ORDER BY count DESC
        ";
        
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    }
    
    /**
     * Get statistics
     * 
     * @return array
     */
    public function getStatistics() {
        $stats = [];
        
        // Total images
        $stmt = $this->db->query("SELECT COUNT(*) FROM {$this->table}");
        $stats['total'] = $stmt->fetchColumn();
        
        // Featured images
        $stmt = $this->db->query("SELECT COUNT(*) FROM {$this->table} WHERE is_featured = 1");
        $stats['featured'] = $stmt->fetchColumn();
        
        // By category
        $stats['by_category'] = $this->getCategoryStats();
        
        return $stats;
    }
}
?>
