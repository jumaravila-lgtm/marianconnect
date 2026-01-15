<?php
/**
 * User Management Class
 * Handles user authentication, registration, and management
 */

require_once __DIR__ . '/Database.php';

class User {
    private $db;
    private $table = 'users';
    
    // User properties
    public $id;
    public $username;
    public $email;
    public $password;
    public $full_name;
    public $role;
    public $status;
    public $avatar;
    public $last_login;
    public $created_at;
    public $updated_at;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    /**
     * Create new user
     * @return int|false User ID or false on failure
     */
    public function create() {
        // Validate required fields
        if (empty($this->username) || empty($this->email) || empty($this->password)) {
            return false;
        }
        
        // Check if username or email already exists
        if ($this->usernameExists($this->username)) {
            return false;
        }
        
        if ($this->emailExists($this->email)) {
            return false;
        }
        
        // Hash password
        $hashedPassword = password_hash($this->password, PASSWORD_DEFAULT);
        
        $data = [
            'username' => $this->username,
            'email' => $this->email,
            'password' => $hashedPassword,
            'full_name' => $this->full_name ?? '',
            'role' => $this->role ?? 'admin',
            'status' => $this->status ?? 'active',
            'avatar' => $this->avatar ?? null,
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        return $this->db->insert($this->table, $data);
    }
    
    /**
     * Login user
     * @param string $username Username or email
     * @param string $password Password
     * @return array|false User data or false on failure
     */
    public function login($username, $password) {
        $query = "SELECT * FROM {$this->table} 
                  WHERE (username = :username OR email = :username) 
                  AND status = 'active' 
                  LIMIT 1";
        
        $user = $this->db->fetchOne($query, ['username' => $username]);
        
        if ($user && password_verify($password, $user['password'])) {
            // Update last login
            $this->updateLastLogin($user['id']);
            
            // Remove password from returned data
            unset($user['password']);
            
            return $user;
        }
        
        return false;
    }
    
    /**
     * Get user by ID
     * @param int $id User ID
     * @return array|false
     */
    public function getById($id) {
        $query = "SELECT * FROM {$this->table} WHERE id = :id LIMIT 1";
        $user = $this->db->fetchOne($query, ['id' => $id]);
        
        if ($user) {
            unset($user['password']);
        }
        
        return $user;
    }
    
    /**
     * Get user by username
     * @param string $username Username
     * @return array|false
     */
    public function getByUsername($username) {
        $query = "SELECT * FROM {$this->table} WHERE username = :username LIMIT 1";
        $user = $this->db->fetchOne($query, ['username' => $username]);
        
        if ($user) {
            unset($user['password']);
        }
        
        return $user;
    }
    
    /**
     * Get user by email
     * @param string $email Email address
     * @return array|false
     */
    public function getByEmail($email) {
        $query = "SELECT * FROM {$this->table} WHERE email = :email LIMIT 1";
        $user = $this->db->fetchOne($query, ['email' => $email]);
        
        if ($user) {
            unset($user['password']);
        }
        
        return $user;
    }
    
    /**
     * Get all users
     * @param string $role Filter by role (optional)
     * @param string $status Filter by status (optional)
     * @return array
     */
    public function getAll($role = '', $status = '') {
        $query = "SELECT id, username, email, full_name, role, status, avatar, last_login, created_at 
                  FROM {$this->table} WHERE 1=1";
        $params = [];
        
        if ($role) {
            $query .= " AND role = :role";
            $params['role'] = $role;
        }
        
        if ($status) {
            $query .= " AND status = :status";
            $params['status'] = $status;
        }
        
        $query .= " ORDER BY created_at DESC";
        
        return $this->db->fetchAll($query, $params);
    }
    
    /**
     * Update user
     * @param int $id User ID
     * @return bool
     */
    public function update($id) {
        $data = [
            'full_name' => $this->full_name,
            'email' => $this->email,
            'role' => $this->role,
            'status' => $this->status,
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        // Only update username if provided and different
        if (!empty($this->username)) {
            $currentUser = $this->getById($id);
            if ($currentUser && $this->username !== $currentUser['username']) {
                if ($this->usernameExists($this->username)) {
                    return false;
                }
                $data['username'] = $this->username;
            }
        }
        
        // Only update avatar if provided
        if (!empty($this->avatar)) {
            $data['avatar'] = $this->avatar;
        }
        
        return $this->db->update($this->table, $data, 'id = :id', ['id' => $id]);
    }
    
    /**
     * Update password
     * @param int $id User ID
     * @param string $newPassword New password
     * @return bool
     */
    public function updatePassword($id, $newPassword) {
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        
        $data = [
            'password' => $hashedPassword,
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        return $this->db->update($this->table, $data, 'id = :id', ['id' => $id]);
    }
    
    /**
     * Change password with old password verification
     * @param int $id User ID
     * @param string $oldPassword Old password
     * @param string $newPassword New password
     * @return bool
     */
    public function changePassword($id, $oldPassword, $newPassword) {
        $query = "SELECT password FROM {$this->table} WHERE id = :id LIMIT 1";
        $user = $this->db->fetchOne($query, ['id' => $id]);
        
        if ($user && password_verify($oldPassword, $user['password'])) {
            return $this->updatePassword($id, $newPassword);
        }
        
        return false;
    }
    
    /**
     * Update user profile
     * @param int $id User ID
     * @return bool
     */
    public function updateProfile($id) {
        $data = [
            'full_name' => $this->full_name,
            'email' => $this->email,
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        if (!empty($this->avatar)) {
            $data['avatar'] = $this->avatar;
        }
        
        return $this->db->update($this->table, $data, 'id = :id', ['id' => $id]);
    }
    
    /**
     * Update last login timestamp
     * @param int $id User ID
     * @return bool
     */
    public function updateLastLogin($id) {
        $data = [
            'last_login' => date('Y-m-d H:i:s')
        ];
        
        return $this->db->update($this->table, $data, 'id = :id', ['id' => $id]);
    }
    
    /**
     * Delete user
     * @param int $id User ID
     * @return bool
     */
    public function delete($id) {
        // Don't allow deleting the last admin
        if ($this->isLastAdmin($id)) {
            return false;
        }
        
        return $this->db->delete($this->table, 'id = :id', ['id' => $id]);
    }
    
    /**
     * Check if username exists
     * @param string $username Username
     * @param int $excludeId Exclude this user ID (for updates)
     * @return bool
     */
    public function usernameExists($username, $excludeId = 0) {
        $query = "SELECT COUNT(*) as count FROM {$this->table} WHERE username = :username";
        $params = ['username' => $username];
        
        if ($excludeId > 0) {
            $query .= " AND id != :excludeId";
            $params['excludeId'] = $excludeId;
        }
        
        $result = $this->db->fetchOne($query, $params);
        return $result && $result['count'] > 0;
    }
    
    /**
     * Check if email exists
     * @param string $email Email address
     * @param int $excludeId Exclude this user ID (for updates)
     * @return bool
     */
    public function emailExists($email, $excludeId = 0) {
        $query = "SELECT COUNT(*) as count FROM {$this->table} WHERE email = :email";
        $params = ['email' => $email];
        
        if ($excludeId > 0) {
            $query .= " AND id != :excludeId";
            $params['excludeId'] = $excludeId;
        }
        
        $result = $this->db->fetchOne($query, $params);
        return $result && $result['count'] > 0;
    }
    
    /**
     * Check if user is the last admin
     * @param int $id User ID
     * @return bool
     */
    private function isLastAdmin($id) {
        $user = $this->getById($id);
        
        if ($user && $user['role'] === 'admin') {
            $adminCount = $this->db->count($this->table, "role = 'admin' AND status = 'active'");
            return $adminCount <= 1;
        }
        
        return false;
    }
    
    /**
     * Activate user
     * @param int $id User ID
     * @return bool
     */
    public function activate($id) {
        $data = [
            'status' => 'active',
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        return $this->db->update($this->table, $data, 'id = :id', ['id' => $id]);
    }
    
    /**
     * Deactivate user
     * @param int $id User ID
     * @return bool
     */
    public function deactivate($id) {
        // Don't allow deactivating the last admin
        if ($this->isLastAdmin($id)) {
            return false;
        }
        
        $data = [
            'status' => 'inactive',
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        return $this->db->update($this->table, $data, 'id = :id', ['id' => $id]);
    }
    
    /**
     * Count total users
     * @param string $role Filter by role (optional)
     * @return int
     */
    public function countUsers($role = '') {
        if ($role) {
            return $this->db->count($this->table, "role = :role", ['role' => $role]);
        }
        return $this->db->count($this->table);
    }
    
    /**
     * Search users
     * @param string $keyword Search keyword
     * @return array
     */
    public function search($keyword) {
        $keyword = '%' . $this->db->escapeLike($keyword) . '%';
        
        $query = "SELECT id, username, email, full_name, role, status, avatar, created_at 
                  FROM {$this->table} 
                  WHERE username LIKE :keyword 
                  OR email LIKE :keyword 
                  OR full_name LIKE :keyword 
                  ORDER BY created_at DESC";
        
        return $this->db->fetchAll($query, ['keyword' => $keyword]);
    }
    
    /**
     * Verify current password
     * @param int $id User ID
     * @param string $password Password to verify
     * @return bool
     */
    public function verifyPassword($id, $password) {
        $query = "SELECT password FROM {$this->table} WHERE id = :id LIMIT 1";
        $user = $this->db->fetchOne($query, ['id' => $id]);
        
        return $user && password_verify($password, $user['password']);
    }
}
?>
