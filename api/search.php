<?php
/**
 * MARIANCONNECT - Search API
 * Global search across news, events, programs, and pages
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
    
    // Get search query
    if (!isset($_GET['q']) || empty(trim($_GET['q']))) {
        throw new Exception('Search query is required');
    }
    
    $query = trim($_GET['q']);
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
    $type = $_GET['type'] ?? 'all'; // all, news, events, programs, pages
    
    $results = [];
    
    // Search News
    if ($type === 'all' || $type === 'news') {
        $sql = "
            SELECT 
                news_id as id,
                'news' as type,
                title,
                slug,
                excerpt as description,
                featured_image as image,
                published_date as date,
                CONCAT('/pages/news-detail.php?slug=', slug) as url
            FROM news
            WHERE status = 'published'
            AND (title LIKE :query OR content LIKE :query OR excerpt LIKE :query)
            ORDER BY published_date DESC
            LIMIT :limit
        ";
        
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':query', "%{$query}%");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        $newsResults = $stmt->fetchAll();
        
        $results['news'] = $newsResults;
    }
    
    // Search Events
    if ($type === 'all' || $type === 'events') {
        $sql = "
            SELECT 
                event_id as id,
                'event' as type,
                title,
                slug,
                description,
                featured_image as image,
                event_date as date,
                location,
                CONCAT('/pages/event-detail.php?slug=', slug) as url
            FROM events
            WHERE (title LIKE :query OR description LIKE :query OR location LIKE :query)
            ORDER BY event_date DESC
            LIMIT :limit
        ";
        
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':query', "%{$query}%");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        $eventResults = $stmt->fetchAll();
        
        $results['events'] = $eventResults;
    }
    
    // Search Academic Programs
    if ($type === 'all' || $type === 'programs') {
        $sql = "
            SELECT 
                program_id as id,
                'program' as type,
                program_name as title,
                slug,
                description,
                featured_image as image,
                level,
                CONCAT('/pages/program-detail.php?slug=', slug) as url
            FROM academic_programs
            WHERE is_active = 1
            AND (program_name LIKE :query OR program_code LIKE :query OR description LIKE :query)
            ORDER BY display_order ASC
            LIMIT :limit
        ";
        
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':query', "%{$query}%");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        $programResults = $stmt->fetchAll();
        
        $results['programs'] = $programResults;
    }
    
    // Search Pages
    if ($type === 'all' || $type === 'pages') {
        $sql = "
            SELECT 
                page_id as id,
                'page' as type,
                title,
                slug,
                LEFT(content, 200) as description,
                page_type,
                CONCAT('/pages/', slug, '.php') as url
            FROM pages
            WHERE is_published = 1
            AND (title LIKE :query OR content LIKE :query)
            ORDER BY title ASC
            LIMIT :limit
        ";
        
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':query', "%{$query}%");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        $pageResults = $stmt->fetchAll();
        
        $results['pages'] = $pageResults;
    }
    
    // Search Organizations
    if ($type === 'all' || $type === 'organizations') {
        $sql = "
            SELECT 
                org_id as id,
                'organization' as type,
                org_name as title,
                slug,
                description,
                logo as image,
                category,
                CONCAT('/pages/organizations.php#', slug) as url
            FROM student_organizations
            WHERE is_active = 1
            AND (org_name LIKE :query OR acronym LIKE :query OR description LIKE :query)
            ORDER BY org_name ASC
            LIMIT :limit
        ";
        
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':query', "%{$query}%");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        $orgResults = $stmt->fetchAll();
        
        $results['organizations'] = $orgResults;
    }
    
    // Calculate total results
    $totalResults = 0;
    $allResults = [];
    
    foreach ($results as $category => $items) {
        $totalResults += count($items);
        foreach ($items as $item) {
            $item['category'] = $category;
            $allResults[] = $item;
        }
    }
    
    // If type is 'all', merge and sort all results
    if ($type === 'all') {
        // Sort by relevance (you can customize this)
        usort($allResults, function($a, $b) {
            // Prioritize exact matches in title
            $aTitle = isset($a['title']) ? $a['title'] : '';
            $bTitle = isset($b['title']) ? $b['title'] : '';
            return strcmp($aTitle, $bTitle);
        });
        
        // Limit combined results
        $allResults = array_slice($allResults, 0, $limit);
    }
    
    echo json_encode([
        'success' => true,
        'query' => $query,
        'total_results' => $totalResults,
        'results' => $type === 'all' ? $allResults : $results,
        'type' => $type
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Server error',
        'message' => $e->getMessage()
    ]);
}
?>
