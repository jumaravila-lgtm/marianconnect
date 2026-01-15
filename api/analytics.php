<?php
/**
 * MARIANCONNECT - Analytics API
 * Track page views and provide analytics data
 */

header('Content-Type: application/json');

require_once '../config/database.php';
require_once '../config/security.php';
require_once '../classes/Analytics.php';
require_once '../classes/Security.php';

// Set secure headers
Security::setSecureHeaders();

try {
    $db = getDB();
    $analytics = new Analytics($db);
    
    $action = $_GET['action'] ?? $_POST['action'] ?? 'track';
    
    switch ($action) {
        case 'track':
            // Track page visit (POST request)
            if (!Security::isPostRequest()) {
                http_response_code(405);
                echo json_encode(['error' => 'Method not allowed']);
                exit;
            }
            
            // Track the visit
            $result = $analytics->trackVisit();
            
            echo json_encode([
                'success' => $result,
                'message' => $result ? 'Visit tracked' : 'Failed to track visit'
            ]);
            break;
            
        case 'stats':
            // Get dashboard statistics (GET request)
            if (!Security::isGetRequest()) {
                http_response_code(405);
                echo json_encode(['error' => 'Method not allowed']);
                exit;
            }
            
            $startDate = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
            $endDate = $_GET['end_date'] ?? date('Y-m-d');
            
            $stats = $analytics->getDashboardStats([
                'start_date' => $startDate,
                'end_date' => $endDate
            ]);
            
            echo json_encode([
                'success' => true,
                'data' => $stats,
                'date_range' => [
                    'start' => $startDate,
                    'end' => $endDate
                ]
            ]);
            break;
            
        case 'visits':
            // Get visits by date for charts
            if (!Security::isGetRequest()) {
                http_response_code(405);
                echo json_encode(['error' => 'Method not allowed']);
                exit;
            }
            
            $days = isset($_GET['days']) ? (int)$_GET['days'] : 30;
            $visits = $analytics->getVisitsByDate($days);
            
            echo json_encode([
                'success' => true,
                'data' => $visits,
                'days' => $days
            ]);
            break;
            
        case 'devices':
            // Get device breakdown
            if (!Security::isGetRequest()) {
                http_response_code(405);
                echo json_encode(['error' => 'Method not allowed']);
                exit;
            }
            
            $startDate = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
            $endDate = $_GET['end_date'] ?? date('Y-m-d');
            
            $devices = $analytics->getDeviceBreakdown([
                'start_date' => $startDate,
                'end_date' => $endDate
            ]);
            
            echo json_encode([
                'success' => true,
                'data' => $devices
            ]);
            break;
            
        case 'top_pages':
            // Get top pages
            if (!Security::isGetRequest()) {
                http_response_code(405);
                echo json_encode(['error' => 'Method not allowed']);
                exit;
            }
            
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
            $startDate = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
            $endDate = $_GET['end_date'] ?? date('Y-m-d');
            
            $topPages = $analytics->getTopPages($limit, [
                'start_date' => $startDate,
                'end_date' => $endDate
            ]);
            
            echo json_encode([
                'success' => true,
                'data' => $topPages
            ]);
            break;
            
        case 'referrers':
            // Get top referrers
            if (!Security::isGetRequest()) {
                http_response_code(405);
                echo json_encode(['error' => 'Method not allowed']);
                exit;
            }
            
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
            $startDate = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
            $endDate = $_GET['end_date'] ?? date('Y-m-d');
            
            $referrers = $analytics->getTopReferrers($limit, [
                'start_date' => $startDate,
                'end_date' => $endDate
            ]);
            
            echo json_encode([
                'success' => true,
                'data' => $referrers
            ]);
            break;
            
        case 'browsers':
            // Get browser statistics
            if (!Security::isGetRequest()) {
                http_response_code(405);
                echo json_encode(['error' => 'Method not allowed']);
                exit;
            }
            
            $startDate = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
            $endDate = $_GET['end_date'] ?? date('Y-m-d');
            
            $browsers = $analytics->getBrowserStats([
                'start_date' => $startDate,
                'end_date' => $endDate
            ]);
            
            echo json_encode([
                'success' => true,
                'data' => $browsers
            ]);
            break;
            
        case 'hourly':
            // Get hourly traffic pattern
            if (!Security::isGetRequest()) {
                http_response_code(405);
                echo json_encode(['error' => 'Method not allowed']);
                exit;
            }
            
            $startDate = $_GET['start_date'] ?? date('Y-m-d', strtotime('-7 days'));
            $endDate = $_GET['end_date'] ?? date('Y-m-d');
            
            $hourly = $analytics->getHourlyTraffic([
                'start_date' => $startDate,
                'end_date' => $endDate
            ]);
            
            echo json_encode([
                'success' => true,
                'data' => $hourly
            ]);
            break;
            
        case 'summary':
            // Get quick summary stats
            if (!Security::isGetRequest()) {
                http_response_code(405);
                echo json_encode(['error' => 'Method not allowed']);
                exit;
            }
            
            $today = $analytics->getTotalVisits([
                'start_date' => date('Y-m-d'),
                'end_date' => date('Y-m-d')
            ]);
            
            $week = $analytics->getTotalVisits([
                'start_date' => date('Y-m-d', strtotime('-7 days')),
                'end_date' => date('Y-m-d')
            ]);
            
            $month = $analytics->getTotalVisits([
                'start_date' => date('Y-m-d', strtotime('-30 days')),
                'end_date' => date('Y-m-d')
            ]);
            
            echo json_encode([
                'success' => true,
                'data' => [
                    'today' => $today,
                    'week' => $week,
                    'month' => $month
                ]
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
