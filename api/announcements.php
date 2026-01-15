<?php
/**
 * MARIANCONNECT - Announcements API
 * Provides JSON endpoints for announcements data
 */

header('Content-Type: application/json');

require_once '../config/database.php';
require_once '../config/security.php';
require_once '../classes/Security.php';

// Set secure headers
Security::setSecureHeaders();

// Only allow GET requests
if (!Security::isGetRequest()) {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

try {
    $db = getDB();
    
    // Get action parameter
    $action = $_GET['action'] ?? 'active';
    
    switch ($action) {
        case 'active':
            // Get active announcements
            $sql = "
                SELECT a.*, u.full_name as author_name
                FROM announcements a
                LEFT JOIN admin_users u ON a.created_by = u.admin_id
                WHERE a.is_active = 1
                AND CURDATE() BETWEEN DATE(a.start_date) AND DATE(a.end_date)
                ORDER BY a.priority DESC, a.start_date DESC
            ";
            
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
            $sql .= " LIMIT " . $limit;
            
            $stmt = $db->query($sql);
            $announcements = $stmt->fetchAll();
            
            echo json_encode([
                'success' => true,
                'data' => $announcements
            ]);
            break;
            
        case 'list':
            // Get all announcements with pagination (admin use)
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
            $offset = ($page - 1) * $limit;
            
            // Count total
            $countStmt = $db->query("SELECT COUNT(*) FROM announcements");
            $total = $countStmt->fetchColumn();
            
            // Get announcements
            $sql = "
                SELECT a.*, u.full_name as author_name
                FROM announcements a
                LEFT JOIN admin_users u ON a.created_by = u.admin_id
                ORDER BY a.created_at DESC
                LIMIT :limit OFFSET :offset
            ";
            
            $stmt = $db->prepare($sql);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            $announcements = $stmt->fetchAll();
            
            echo json_encode([
                'success' => true,
                'data' => $announcements,
                'pagination' => [
                    'current_page' => $page,
                    'total_pages' => ceil($total / $limit),
                    'total_items' => $total,
                    'per_page' => $limit
                ]
            ]);
            break;
            
        case 'get':
            // Get single announcement
            if (!isset($_GET['id'])) {
                throw new Exception('Announcement ID is required');
            }
            
            $sql = "
                SELECT a.*, u.full_name as author_name
                FROM announcements a
                LEFT JOIN admin_users u ON a.created_by = u.admin_id
                WHERE a.announcement_id = :id
            ";
            
            $stmt = $db->prepare($sql);
            $stmt->execute([':id' => (int)$_GET['id']]);
            $announcement = $stmt->fetch();
            
            if (!$announcement) {
                http_response_code(404);
                echo json_encode(['error' => 'Announcement not found']);
                exit;
            }
            
            echo json_encode([
                'success' => true,
                'data' => $announcement
            ]);
            break;
            
        case 'type':
            // Get announcements by type
            if (!isset($_GET['type'])) {
                throw new Exception('Type is required');
            }
            
            $sql = "
                SELECT a.*, u.full_name as author_name
                FROM announcements a
                LEFT JOIN admin_users u ON a.created_by = u.admin_id
                WHERE a.type = :type
                AND a.is_active = 1
                AND CURDATE() BETWEEN DATE(a.start_date) AND DATE(a.end_date)
                ORDER BY a.priority DESC, a.start_date DESC
            ";
            
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
            $sql .= " LIMIT " . $limit;
            
            $stmt = $db->prepare($sql);
            $stmt->execute([':type' => $_GET['type']]);
            $announcements = $stmt->fetchAll();
            
            echo json_encode([
                'success' => true,
                'data' => $announcements
            ]);
            break;
            
        case 'urgent':
            // Get urgent announcements
            $sql = "
                SELECT a.*, u.full_name as author_name
                FROM announcements a
                LEFT JOIN admin_users u ON a.created_by = u.admin_id
                WHERE a.priority = 'high'
                AND a.is_active = 1
                AND CURDATE() BETWEEN DATE(a.start_date) AND DATE(a.end_date)
                ORDER BY a.start_date DESC
            ";
            
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 5;
            $sql .= " LIMIT " . $limit;
            
            $stmt = $db->query($sql);
            $announcements = $stmt->fetchAll();
            
            echo json_encode([
                'success' => true,
                'data' => $announcements
            ]);
            break;
            
        case 'ticker':
            // Get announcements for ticker display
            $sql = "
                SELECT title, content, type, priority
                FROM announcements
                WHERE is_active = 1
                AND CURDATE() BETWEEN DATE(start_date) AND DATE(end_date)
                ORDER BY priority DESC, start_date DESC
                LIMIT 5
            ";
            
            $stmt = $db->query($sql);
            $announcements = $stmt->fetchAll();
            
            // Format for ticker
            $tickerItems = [];
            foreach ($announcements as $ann) {
                $tickerItems[] = [
                    'text' => $ann['title'] . ': ' . strip_tags($ann['content']),
                    'type' => $ann['type'],
                    'priority' => $ann['priority']
                ];
            }
            
            echo json_encode([
                'success' => true,
                'data' => $tickerItems
            ]);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
            break;
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Server error',
        'message' => $e->getMessage()
    ]);
}
?>
