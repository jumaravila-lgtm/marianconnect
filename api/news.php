<?php
/**
 * MARIANCONNECT - News API
 * Provides JSON endpoints for news data
 */

header('Content-Type: application/json');

require_once '../config/database.php';
require_once '../config/security.php';
require_once '../classes/News.php';
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
    $news = new News($db);
    
    // Get action parameter
    $action = $_GET['action'] ?? 'list';
    
    switch ($action) {
        case 'list':
            // Get all news with pagination
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
            $category = $_GET['category'] ?? null;
            $status = $_GET['status'] ?? 'published';
            
            $options = [
                'page' => $page,
                'limit' => $limit,
                'status' => $status
            ];
            
            if ($category) {
                $options['category'] = $category;
            }
            
            $result = $news->getAll($options);
            
            echo json_encode([
                'success' => true,
                'data' => $result['data'],
                'pagination' => [
                    'current_page' => $result['page'],
                    'total_pages' => $result['pages'],
                    'total_items' => $result['total'],
                    'per_page' => $result['limit']
                ]
            ]);
            break;
            
        case 'get':
            // Get single news by ID or slug
            if (isset($_GET['id'])) {
                $newsItem = $news->getById((int)$_GET['id']);
            } elseif (isset($_GET['slug'])) {
                $newsItem = $news->getBySlug($_GET['slug']);
                
                // Increment view count for public access
                if ($newsItem && !isset($_SESSION['admin_id'])) {
                    $news->incrementViews($newsItem['news_id']);
                }
            } else {
                throw new Exception('ID or slug is required');
            }
            
            if (!$newsItem) {
                http_response_code(404);
                echo json_encode(['error' => 'News not found']);
                exit;
            }
            
            echo json_encode([
                'success' => true,
                'data' => $newsItem
            ]);
            break;
            
        case 'featured':
            // Get featured news
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 3;
            $featured = $news->getFeatured($limit);
            
            echo json_encode([
                'success' => true,
                'data' => $featured
            ]);
            break;
            
        case 'recent':
            // Get recent news
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 5;
            $recent = $news->getRecent($limit);
            
            echo json_encode([
                'success' => true,
                'data' => $recent
            ]);
            break;
            
        case 'popular':
            // Get popular news by views
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 5;
            $popular = $news->getPopular($limit);
            
            echo json_encode([
                'success' => true,
                'data' => $popular
            ]);
            break;
            
        case 'category':
            // Get news by category
            if (!isset($_GET['category'])) {
                throw new Exception('Category is required');
            }
            
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
            $categoryNews = $news->getByCategory($_GET['category'], $limit);
            
            echo json_encode([
                'success' => true,
                'data' => $categoryNews
            ]);
            break;
            
        case 'search':
            // Search news
            if (!isset($_GET['q'])) {
                throw new Exception('Search query is required');
            }
            
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
            $results = $news->search($_GET['q'], $limit);
            
            echo json_encode([
                'success' => true,
                'data' => $results,
                'query' => $_GET['q']
            ]);
            break;
            
        case 'stats':
            // Get news statistics
            $stats = $news->getStatistics();
            
            echo json_encode([
                'success' => true,
                'data' => $stats
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
