<?php
/**
 * MARIANCONNECT - Event Class
 * Handles all event-related database operations (CRUD)
 */

class Event {
    
    private $db;
    private $table = 'events';
    
    /**
     * Constructor
     * 
     * @param PDO $db Database connection
     */
    public function __construct($db) {
        $this->db = $db;
    }
    
    /**
     * Get all events with pagination and filters
     * 
     * @param array $options Filter options
     * @return array
     */
    public function getAll($options = []) {
        $page = $options['page'] ?? 1;
        $limit = $options['limit'] ?? 10;
        $offset = ($page - 1) * $limit;
        $status = $options['status'] ?? null;
        $category = $options['category'] ?? null;
        $search = $options['search'] ?? null;
        $orderBy = $options['order_by'] ?? 'event_date';
        $orderDir = $options['order_dir'] ?? 'ASC';
        
        // Build WHERE clause
        $where = [];
        $params = [];
        
        if ($status) {
            $where[] = "status = :status";
            $params[':status'] = $status;
        }
        
        if ($category) {
            $where[] = "category = :category";
            $params[':category'] = $category;
        }
        
        if ($search) {
            $where[] = "(title LIKE :search OR description LIKE :search OR location LIKE :search)";
            $params[':search'] = "%{$search}%";
        }
        
        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        
        // Get total count
        $countSql = "SELECT COUNT(*) FROM {$this->table} {$whereClause}";
        $countStmt = $this->db->prepare($countSql);
        $countStmt->execute($params);
        $total = $countStmt->fetchColumn();
        
        // Get events
        $sql = "
            SELECT e.*, a.full_name as creator_name
            FROM {$this->table} e
            LEFT JOIN admin_users a ON e.created_by = a.admin_id
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
     * Get event by ID
     * 
     * @param int $id Event ID
     * @return array|null
     */
    public function getById($id) {
        $sql = "
            SELECT e.*, a.full_name as creator_name, a.email as creator_email
            FROM {$this->table} e
            LEFT JOIN admin_users a ON e.created_by = a.admin_id
            WHERE e.event_id = :id
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }
    
    /**
     * Get event by slug
     * 
     * @param string $slug Event slug
     * @return array|null
     */
    public function getBySlug($slug) {
        $sql = "
            SELECT e.*, a.full_name as creator_name
            FROM {$this->table} e
            LEFT JOIN admin_users a ON e.created_by = a.admin_id
            WHERE e.slug = :slug
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':slug' => $slug]);
        return $stmt->fetch();
    }
    
    /**
     * Create new event
     * 
     * @param array $data Event data
     * @return int|bool Inserted ID or false on failure
     */
    public function create($data) {
        $sql = "
            INSERT INTO {$this->table} (
                title, slug, description, event_date, event_time, end_date,
                location, featured_image, category, status, organizer,
                contact_info, max_participants, registration_required, 
                is_featured, created_by
            ) VALUES (
                :title, :slug, :description, :event_date, :event_time, :end_date,
                :location, :featured_image, :category, :status, :organizer,
                :contact_info, :max_participants, :registration_required,
                :is_featured, :created_by
            )
        ";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':title' => $data['title'],
                ':slug' => $data['slug'],
                ':description' => $data['description'],
                ':event_date' => $data['event_date'],
                ':event_time' => $data['event_time'] ?? null,
                ':end_date' => $data['end_date'] ?? null,
                ':location' => $data['location'],
                ':featured_image' => $data['featured_image'] ?? null,
                ':category' => $data['category'] ?? 'other',
                ':status' => $data['status'] ?? 'upcoming',
                ':organizer' => $data['organizer'] ?? null,
                ':contact_info' => $data['contact_info'] ?? null,
                ':max_participants' => $data['max_participants'] ?? null,
                ':registration_required' => $data['registration_required'] ?? 0,
                ':is_featured' => $data['is_featured'] ?? 0,
                ':created_by' => $data['created_by']
            ]);
            
            return $this->db->lastInsertId();
        } catch (PDOException $e) {
            error_log("Event creation error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update event
     * 
     * @param int $id Event ID
     * @param array $data Updated data
     * @return bool
     */
    public function update($id, $data) {
        $fields = [];
        $params = [':id' => $id];
        
        $allowedFields = [
            'title', 'slug', 'description', 'event_date', 'event_time', 'end_date',
            'location', 'featured_image', 'category', 'status', 'organizer',
            'contact_info', 'max_participants', 'registration_required', 'is_featured'
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
        
        $sql = "UPDATE {$this->table} SET " . implode(', ', $fields) . " WHERE event_id = :id";
        
        try {
            $stmt = $this->db->prepare($sql);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            error_log("Event update error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Delete event
     * 
     * @param int $id Event ID
     * @return bool
     */
    public function delete($id) {
        $sql = "DELETE FROM {$this->table} WHERE event_id = :id";
        
        try {
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([':id' => $id]);
        } catch (PDOException $e) {
            error_log("Event deletion error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get upcoming events
     * 
     * @param int $limit Number of items
     * @return array
     */
    public function getUpcoming($limit = 5) {
        $sql = "
            SELECT *
            FROM {$this->table}
            WHERE status = 'upcoming' AND event_date >= CURDATE()
            ORDER BY event_date ASC
            LIMIT :limit
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    /**
     * Get featured events
     * 
     * @param int $limit Number of items
     * @return array
     */
    public function getFeatured($limit = 3) {
        $sql = "
            SELECT *
            FROM {$this->table}
            WHERE is_featured = 1 AND event_date >= CURDATE()
            ORDER BY event_date ASC
            LIMIT :limit
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    /**
     * Get events by category
     * 
     * @param string $category Category name
     * @param int $limit Number of items
     * @return array
     */
    public function getByCategory($category, $limit = 10) {
        $sql = "
            SELECT *
            FROM {$this->table}
            WHERE category = :category AND event_date >= CURDATE()
            ORDER BY event_date ASC
            LIMIT :limit
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':category', $category);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    /**
     * Get past events
     * 
     * @param int $limit Number of items
     * @return array
     */
    public function getPast($limit = 10) {
        $sql = "
            SELECT *
            FROM {$this->table}
            WHERE event_date < CURDATE()
            ORDER BY event_date DESC
            LIMIT :limit
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    /**
     * Auto-update event status based on dates
     * 
     * @return bool
     */
    public function updateEventStatuses() {
        try {
            // Mark events as ongoing if they started today
            $sql1 = "
                UPDATE {$this->table}
                SET status = 'ongoing'
                WHERE event_date = CURDATE() AND status = 'upcoming'
            ";
            $this->db->exec($sql1);
            
            // Mark events as completed if they ended
            $sql2 = "
                UPDATE {$this->table}
                SET status = 'completed'
                WHERE (end_date < CURDATE() OR (end_date IS NULL AND event_date < CURDATE()))
                AND status != 'cancelled'
            ";
            $this->db->exec($sql2);
            
            return true;
        } catch (PDOException $e) {
            error_log("Event status update error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Check if slug exists
     * 
     * @param string $slug Slug to check
     * @param int $excludeId Exclude this ID (for updates)
     * @return bool
     */
    public function slugExists($slug, $excludeId = null) {
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE slug = :slug";
        
        if ($excludeId) {
            $sql .= " AND event_id != :id";
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':slug', $slug);
        
        if ($excludeId) {
            $stmt->bindValue(':id', $excludeId, PDO::PARAM_INT);
        }
        
        $stmt->execute();
        return $stmt->fetchColumn() > 0;
    }
    
    /**
     * Generate unique slug from title
     * 
     * @param string $title Title to convert
     * @param int $excludeId Exclude this ID (for updates)
     * @return string
     */
    public function generateUniqueSlug($title, $excludeId = null) {
        $slug = $this->createSlug($title);
        $originalSlug = $slug;
        $counter = 1;
        
        while ($this->slugExists($slug, $excludeId)) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }
        
        return $slug;
    }
    
    /**
     * Create slug from string
     * 
     * @param string $string String to convert
     * @return string
     */
    private function createSlug($string) {
        $string = strtolower($string);
        $string = preg_replace('/[^a-z0-9\s-]/', '', $string);
        $string = preg_replace('/[\s-]+/', '-', $string);
        $string = trim($string, '-');
        return $string;
    }
    
    /**
     * Get statistics
     * 
     * @return array
     */
    public function getStatistics() {
        $stats = [];
        
        // Total events
        $stmt = $this->db->query("SELECT COUNT(*) FROM {$this->table}");
        $stats['total'] = $stmt->fetchColumn();
        
        // Upcoming
        $stmt = $this->db->query("SELECT COUNT(*) FROM {$this->table} WHERE status = 'upcoming'");
        $stats['upcoming'] = $stmt->fetchColumn();
        
        // Ongoing
        $stmt = $this->db->query("SELECT COUNT(*) FROM {$this->table} WHERE status = 'ongoing'");
        $stats['ongoing'] = $stmt->fetchColumn();
        
        // Completed
        $stmt = $this->db->query("SELECT COUNT(*) FROM {$this->table} WHERE status = 'completed'");
        $stats['completed'] = $stmt->fetchColumn();
        
        return $stats;
    }
    
    /**
     * Search events
     * 
     * @param string $query Search query
     * @param int $limit Number of results
     * @return array
     */
    public function search($query, $limit = 20) {
        $sql = "
            SELECT *
            FROM {$this->table}
            WHERE (title LIKE :query OR description LIKE :query OR location LIKE :query)
            ORDER BY event_date DESC
            LIMIT :limit
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':query', "%{$query}%");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
?>
