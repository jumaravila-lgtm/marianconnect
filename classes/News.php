<?php
/**
 * MARIANCONNECT - News Class
 * Handles all news-related database operations (CRUD)
 */

class News {
    
    private $db;
    private $table = 'news';
    
    /**
     * Constructor
     * 
     * @param PDO $db Database connection
     */
    public function __construct($db) {
        $this->db = $db;
    }
    
    /**
     * Get all news with pagination and filters
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
        $orderBy = $options['order_by'] ?? 'created_at';
        $orderDir = $options['order_dir'] ?? 'DESC';
        
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
            $where[] = "(title LIKE :search OR content LIKE :search OR excerpt LIKE :search)";
            $params[':search'] = "%{$search}%";
        }
        
        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        
        // Get total count
        $countSql = "SELECT COUNT(*) FROM {$this->table} {$whereClause}";
        $countStmt = $this->db->prepare($countSql);
        $countStmt->execute($params);
        $total = $countStmt->fetchColumn();
        
        // Get news
        $sql = "
            SELECT n.*, a.full_name as author_name 
            FROM {$this->table} n
            LEFT JOIN admin_users a ON n.author_id = a.admin_id
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
     * Get news by ID
     * 
     * @param int $id News ID
     * @return array|null
     */
    public function getById($id) {
        $sql = "
            SELECT n.*, a.full_name as author_name, a.email as author_email
            FROM {$this->table} n
            LEFT JOIN admin_users a ON n.author_id = a.admin_id
            WHERE n.news_id = :id
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }
    
    /**
     * Get news by slug
     * 
     * @param string $slug News slug
     * @return array|null
     */
    public function getBySlug($slug) {
        $sql = "
            SELECT n.*, a.full_name as author_name
            FROM {$this->table} n
            LEFT JOIN admin_users a ON n.author_id = a.admin_id
            WHERE n.slug = :slug
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':slug' => $slug]);
        return $stmt->fetch();
    }
    
    /**
     * Create new news article
     * 
     * @param array $data News data
     * @return int|bool Inserted ID or false on failure
     */
    public function create($data) {
        $sql = "
            INSERT INTO {$this->table} (
                title, slug, excerpt, content, featured_image, 
                category, author_id, status, is_featured, published_date
            ) VALUES (
                :title, :slug, :excerpt, :content, :featured_image,
                :category, :author_id, :status, :is_featured, :published_date
            )
        ";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':title' => $data['title'],
                ':slug' => $data['slug'],
                ':excerpt' => $data['excerpt'] ?? null,
                ':content' => $data['content'],
                ':featured_image' => $data['featured_image'] ?? null,
                ':category' => $data['category'] ?? 'general',
                ':author_id' => $data['author_id'],
                ':status' => $data['status'] ?? 'draft',
                ':is_featured' => $data['is_featured'] ?? 0,
                ':published_date' => $data['published_date'] ?? null
            ]);
            
            return $this->db->lastInsertId();
        } catch (PDOException $e) {
            error_log("News creation error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update news article
     * 
     * @param int $id News ID
     * @param array $data Updated data
     * @return bool
     */
    public function update($id, $data) {
        // Build SET clause dynamically based on provided data
        $fields = [];
        $params = [':id' => $id];
        
        $allowedFields = [
            'title', 'slug', 'excerpt', 'content', 'featured_image',
            'category', 'status', 'is_featured', 'published_date'
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
        
        $sql = "UPDATE {$this->table} SET " . implode(', ', $fields) . " WHERE news_id = :id";
        
        try {
            $stmt = $this->db->prepare($sql);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            error_log("News update error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Delete news article
     * 
     * @param int $id News ID
     * @return bool
     */
    public function delete($id) {
        $sql = "DELETE FROM {$this->table} WHERE news_id = :id";
        
        try {
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([':id' => $id]);
        } catch (PDOException $e) {
            error_log("News deletion error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get featured news
     * 
     * @param int $limit Number of items
     * @return array
     */
    public function getFeatured($limit = 3) {
        $sql = "
            SELECT n.*, a.full_name as author_name
            FROM {$this->table} n
            LEFT JOIN admin_users a ON n.author_id = a.admin_id
            WHERE n.status = 'published' AND n.is_featured = 1
            ORDER BY n.published_date DESC
            LIMIT :limit
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    /**
     * Get recent news
     * 
     * @param int $limit Number of items
     * @return array
     */
    public function getRecent($limit = 5) {
        $sql = "
            SELECT n.*, a.full_name as author_name
            FROM {$this->table} n
            LEFT JOIN admin_users a ON n.author_id = a.admin_id
            WHERE n.status = 'published'
            ORDER BY n.published_date DESC
            LIMIT :limit
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    /**
     * Get popular news (by views)
     * 
     * @param int $limit Number of items
     * @return array
     */
    public function getPopular($limit = 5) {
        $sql = "
            SELECT n.*, a.full_name as author_name
            FROM {$this->table} n
            LEFT JOIN admin_users a ON n.author_id = a.admin_id
            WHERE n.status = 'published'
            ORDER BY n.views DESC
            LIMIT :limit
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    /**
     * Get news by category
     * 
     * @param string $category Category name
     * @param int $limit Number of items
     * @return array
     */
    public function getByCategory($category, $limit = 10) {
        $sql = "
            SELECT n.*, a.full_name as author_name
            FROM {$this->table} n
            LEFT JOIN admin_users a ON n.author_id = a.admin_id
            WHERE n.status = 'published' AND n.category = :category
            ORDER BY n.published_date DESC
            LIMIT :limit
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':category', $category);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    /**
     * Increment view count
     * 
     * @param int $id News ID
     * @return bool
     */
    public function incrementViews($id) {
        $sql = "UPDATE {$this->table} SET views = views + 1 WHERE news_id = :id";
        
        try {
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([':id' => $id]);
        } catch (PDOException $e) {
            error_log("View increment error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Check if slug exists (for validation)
     * 
     * @param string $slug Slug to check
     * @param int $excludeId Exclude this ID (for updates)
     * @return bool
     */
    public function slugExists($slug, $excludeId = null) {
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE slug = :slug";
        
        if ($excludeId) {
            $sql .= " AND news_id != :id";
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
        
        // Total news
        $stmt = $this->db->query("SELECT COUNT(*) FROM {$this->table}");
        $stats['total'] = $stmt->fetchColumn();
        
        // Published
        $stmt = $this->db->query("SELECT COUNT(*) FROM {$this->table} WHERE status = 'published'");
        $stats['published'] = $stmt->fetchColumn();
        
        // Draft
        $stmt = $this->db->query("SELECT COUNT(*) FROM {$this->table} WHERE status = 'draft'");
        $stats['draft'] = $stmt->fetchColumn();
        
        // Total views
        $stmt = $this->db->query("SELECT SUM(views) FROM {$this->table}");
        $stats['total_views'] = $stmt->fetchColumn() ?? 0;
        
        return $stats;
    }
    
    /**
     * Search news
     * 
     * @param string $query Search query
     * @param int $limit Number of results
     * @return array
     */
    public function search($query, $limit = 20) {
        $sql = "
            SELECT n.*, a.full_name as author_name
            FROM {$this->table} n
            LEFT JOIN admin_users a ON n.author_id = a.admin_id
            WHERE n.status = 'published' 
            AND (n.title LIKE :query OR n.content LIKE :query OR n.excerpt LIKE :query)
            ORDER BY n.published_date DESC
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
