<?php
/**
 * MARIANCONNECT - Events API
 * Provides JSON endpoints for events data
 */

header('Content-Type: application/json');

require_once '../config/database.php';
require_once '../config/security.php';
require_once '../classes/Event.php';
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
    $event = new Event($db);
    
    // Auto-update event statuses
    $event->updateEventStatuses();
    
    // Get action parameter
    $action = $_GET['action'] ?? 'list';
    
    switch ($action) {
        case 'list':
            // Get all events with pagination
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
            $status = $_GET['status'] ?? null;
            $category = $_GET['category'] ?? null;
            
            $options = [
                'page' => $page,
                'limit' => $limit
            ];
            
            if ($status) {
                $options['status'] = $status;
            }
            
            if ($category) {
                $options['category'] = $category;
            }
            
            $result = $event->getAll($options);
            
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
            // Get single event by ID or slug
            if (isset($_GET['id'])) {
                $eventItem = $event->getById((int)$_GET['id']);
            } elseif (isset($_GET['slug'])) {
                $eventItem = $event->getBySlug($_GET['slug']);
            } else {
                throw new Exception('ID or slug is required');
            }
            
            if (!$eventItem) {
                http_response_code(404);
                echo json_encode(['error' => 'Event not found']);
                exit;
            }
            
            echo json_encode([
                'success' => true,
                'data' => $eventItem
            ]);
            break;
            
        case 'upcoming':
            // Get upcoming events
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 5;
            $upcoming = $event->getUpcoming($limit);
            
            echo json_encode([
                'success' => true,
                'data' => $upcoming
            ]);
            break;
            
        case 'featured':
            // Get featured events
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 3;
            $featured = $event->getFeatured($limit);
            
            echo json_encode([
                'success' => true,
                'data' => $featured
            ]);
            break;
            
        case 'past':
            // Get past events
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
            $past = $event->getPast($limit);
            
            echo json_encode([
                'success' => true,
                'data' => $past
            ]);
            break;
            
        case 'category':
            // Get events by category
            if (!isset($_GET['category'])) {
                throw new Exception('Category is required');
            }
            
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
            $categoryEvents = $event->getByCategory($_GET['category'], $limit);
            
            echo json_encode([
                'success' => true,
                'data' => $categoryEvents
            ]);
            break;
            
        case 'search':
            // Search events
            if (!isset($_GET['q'])) {
                throw new Exception('Search query is required');
            }
            
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
            $results = $event->search($_GET['q'], $limit);
            
            echo json_encode([
                'success' => true,
                'data' => $results,
                'query' => $_GET['q']
            ]);
            break;
            
        case 'stats':
            // Get event statistics
            $stats = $event->getStatistics();
            
            echo json_encode([
                'success' => true,
                'data' => $stats
            ]);
            break;
            
        case 'calendar':
            // Get events for calendar view
            $year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');
            $month = isset($_GET['month']) ? (int)$_GET['month'] : date('m');
            
            // Get all events for the specified month
            $startDate = date('Y-m-01', strtotime("$year-$month-01"));
            $endDate = date('Y-m-t', strtotime("$year-$month-01"));
            
            $sql = "
                SELECT * FROM events
                WHERE event_date BETWEEN :start_date AND :end_date
                ORDER BY event_date ASC
            ";
            
            $stmt = $db->prepare($sql);
            $stmt->execute([
                ':start_date' => $startDate,
                ':end_date' => $endDate
            ]);
            $calendarEvents = $stmt->fetchAll();
            
            echo json_encode([
                'success' => true,
                'data' => $calendarEvents,
                'year' => $year,
                'month' => $month
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
