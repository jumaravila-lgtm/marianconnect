<?php
/**
 * MARIANCONNECT - Program Class
 * Handles all academic program-related database operations (CRUD)
 */

class Program {
    
    private $db;
    private $table = 'academic_programs';
    
    /**
     * Constructor
     * 
     * @param PDO $db Database connection
     */
    public function __construct($db) {
        $this->db = $db;
    }
    
    /**
     * Get all programs with filters
     * 
     * @param array $options Filter options
     * @return array
     */
    public function getAll($options = []) {
        $level = $options['level'] ?? null;
        $department = $options['department'] ?? null;
        $isActive = $options['is_active'] ?? null;
        $orderBy = $options['order_by'] ?? 'display_order';
        $orderDir = $options['order_dir'] ?? 'ASC';
        
        // Build WHERE clause
        $where = [];
        $params = [];
        
        if ($level) {
            $where[] = "level = :level";
            $params[':level'] = $level;
        }
        
        if ($department) {
            $where[] = "department = :department";
            $params[':department'] = $department;
        }
        
        if ($isActive !== null) {
            $where[] = "is_active = :is_active";
            $params[':is_active'] = $isActive;
        }
        
        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        
        $sql = "
            SELECT *
            FROM {$this->table}
            {$whereClause}
            ORDER BY {$orderBy} {$orderDir}
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    /**
     * Get program by ID
     * 
     * @param int $id Program ID
     * @return array|null
     */
    public function getById($id) {
        $sql = "SELECT * FROM {$this->table} WHERE program_id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }
    
    /**
     * Get program by slug
     * 
     * @param string $slug Program slug
     * @return array|null
     */
    public function getBySlug($slug) {
        $sql = "SELECT * FROM {$this->table} WHERE slug = :slug";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':slug' => $slug]);
        return $stmt->fetch();
    }
    
    /**
     * Get program by code
     * 
     * @param string $code Program code
     * @return array|null
     */
    public function getByCode($code) {
        $sql = "SELECT * FROM {$this->table} WHERE program_code = :code";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':code' => $code]);
        return $stmt->fetch();
    }
    
    /**
     * Create new program
     * 
     * @param array $data Program data
     * @return int|bool Inserted ID or false on failure
     */
    public function create($data) {
        $sql = "
            INSERT INTO {$this->table} (
                program_code, program_name, slug, level, department, description,
                duration, featured_image, brochure_pdf, admission_requirements,
                career_opportunities, curriculum_highlights, is_active, display_order
            ) VALUES (
                :program_code, :program_name, :slug, :level, :department, :description,
                :duration, :featured_image, :brochure_pdf, :admission_requirements,
                :career_opportunities, :curriculum_highlights, :is_active, :display_order
            )
        ";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':program_code' => $data['program_code'],
                ':program_name' => $data['program_name'],
                ':slug' => $data['slug'],
                ':level' => $data['level'],
                ':department' => $data['department'] ?? null,
                ':description' => $data['description'],
                ':duration' => $data['duration'] ?? null,
                ':featured_image' => $data['featured_image'] ?? null,
                ':brochure_pdf' => $data['brochure_pdf'] ?? null,
                ':admission_requirements' => $data['admission_requirements'] ?? null,
                ':career_opportunities' => $data['career_opportunities'] ?? null,
                ':curriculum_highlights' => $data['curriculum_highlights'] ?? null,
                ':is_active' => $data['is_active'] ?? 1,
                ':display_order' => $data['display_order'] ?? 0
            ]);
            
            return $this->db->lastInsertId();
        } catch (PDOException $e) {
            error_log("Program creation error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update program
     * 
     * @param int $id Program ID
     * @param array $data Updated data
     * @return bool
     */
    public function update($id, $data) {
        $fields = [];
        $params = [':id' => $id];
        
        $allowedFields = [
            'program_code', 'program_name', 'slug', 'level', 'department', 'description',
            'duration', 'featured_image', 'brochure_pdf', 'admission_requirements',
            'career_opportunities', 'curriculum_highlights', 'is_active', 'display_order'
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
        
        $sql = "UPDATE {$this->table} SET " . implode(', ', $fields) . " WHERE program_id = :id";
        
        try {
            $stmt = $this->db->prepare($sql);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            error_log("Program update error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Delete program
     * 
     * @param int $id Program ID
     * @return bool
     */
    public function delete($id) {
        $sql = "DELETE FROM {$this->table} WHERE program_id = :id";
        
        try {
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([':id' => $id]);
        } catch (PDOException $e) {
            error_log("Program deletion error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get programs by level
     * 
     * @param string $level Level (elementary, junior_high, senior_high, college)
     * @return array
     */
    public function getByLevel($level) {
        $sql = "
            SELECT *
            FROM {$this->table}
            WHERE level = :level AND is_active = 1
            ORDER BY display_order ASC, program_name ASC
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':level' => $level]);
        return $stmt->fetchAll();
    }
    
    /**
     * Get programs grouped by level
     * 
     * @return array
     */
    public function getAllGroupedByLevel() {
        $levels = ['elementary', 'junior_high', 'senior_high', 'college'];
        $grouped = [];
        
        foreach ($levels as $level) {
            $grouped[$level] = $this->getByLevel($level);
        }
        
        return $grouped;
    }
    
    /**
     * Get active programs count by level
     * 
     * @return array
     */
    public function getCountByLevel() {
        $sql = "
            SELECT level, COUNT(*) as count
            FROM {$this->table}
            WHERE is_active = 1
            GROUP BY level
        ";
        
        $stmt = $this->db->query($sql);
        $results = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        
        return [
            'elementary' => $results['elementary'] ?? 0,
            'junior_high' => $results['junior_high'] ?? 0,
            'senior_high' => $results['senior_high'] ?? 0,
            'college' => $results['college'] ?? 0
        ];
    }
    
    /**
     * Update display order
     * 
     * @param int $id Program ID
     * @param int $order New display order
     * @return bool
     */
    public function updateDisplayOrder($id, $order) {
        $sql = "UPDATE {$this->table} SET display_order = :order WHERE program_id = :id";
        
        try {
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([':order' => $order, ':id' => $id]);
        } catch (PDOException $e) {
            error_log("Display order update error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Check if program code exists
     * 
     * @param string $code Program code
     * @param int $excludeId Exclude this ID (for updates)
     * @return bool
     */
    public function codeExists($code, $excludeId = null) {
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE program_code = :code";
        
        if ($excludeId) {
            $sql .= " AND program_id != :id";
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':code', $code);
        
        if ($excludeId) {
            $stmt->bindValue(':id', $excludeId, PDO::PARAM_INT);
        }
        
        $stmt->execute();
        return $stmt->fetchColumn() > 0;
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
            $sql .= " AND program_id != :id";
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
        
        // Total programs
        $stmt = $this->db->query("SELECT COUNT(*) FROM {$this->table}");
        $stats['total'] = $stmt->fetchColumn();
        
        // Active programs
        $stmt = $this->db->query("SELECT COUNT(*) FROM {$this->table} WHERE is_active = 1");
        $stats['active'] = $stmt->fetchColumn();
        
        // By level
        $stats['by_level'] = $this->getCountByLevel();
        
        return $stats;
    }
    
    /**
     * Search programs
     * 
     * @param string $query Search query
     * @return array
     */
    public function search($query) {
        $sql = "
            SELECT *
            FROM {$this->table}
            WHERE (program_name LIKE :query OR program_code LIKE :query OR description LIKE :query)
            AND is_active = 1
            ORDER BY display_order ASC, program_name ASC
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':query' => "%{$query}%"]);
        return $stmt->fetchAll();
    }
}
?>
