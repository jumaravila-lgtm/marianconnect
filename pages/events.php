<?php
/**
 * MARIANCONNECT - Events Page (REDESIGNED - Ateneo Style)
 * Modern, clean events listing with improved UX
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Define the base path
define('BASE_PATH', dirname(__DIR__));

require_once BASE_PATH . '/config/database.php';
require_once BASE_PATH . '/config/settings.php';
require_once BASE_PATH . '/config/security.php';
require_once BASE_PATH . '/includes/functions.php';

// Track visitor
try {
    trackVisitor();
} catch (Exception $e) {
    error_log("Visitor tracking error: " . $e->getMessage());
}

$db = getDB();

// Pagination
$items_per_page = 9;
$current_page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($current_page - 1) * $items_per_page;

// Filter by status (upcoming, completed)
$status_filter = isset($_GET['status']) ? sanitize($_GET['status']) : 'upcoming';
$category_filter = isset($_GET['category']) ? sanitize($_GET['category']) : '';

// Build query
$where_conditions = [];
$params = [];

if ($status_filter === 'upcoming') {
    $where_conditions[] = "e.status = 'upcoming' AND e.event_date >= CURDATE()";
} elseif ($status_filter === 'completed') {
    $where_conditions[] = "e.status = 'completed' OR (e.status = 'upcoming' AND e.event_date < CURDATE())";
}

if (!empty($category_filter)) {
    $where_conditions[] = "e.category = :category";
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get total count
try {
    $count_sql = "SELECT COUNT(*) as total FROM events e $where_clause";
    $count_stmt = $db->prepare($count_sql);
    if (!empty($category_filter)) {
        $count_stmt->bindValue(':category', $category_filter, PDO::PARAM_STR);
    }
    $count_stmt->execute();
    $total_items = $count_stmt->fetch()['total'];
    $total_pages = ceil($total_items / $items_per_page);
} catch (Exception $e) {
    error_log("Count query error: " . $e->getMessage());
    $total_items = 0;
    $total_pages = 1;
}

// Fetch events
try {
    $sql = "
        SELECT e.* 
        FROM events e
        $where_clause
        ORDER BY e.event_date " . ($status_filter === 'upcoming' ? 'ASC' : 'DESC') . "
        LIMIT :limit OFFSET :offset
    ";
    
    $stmt = $db->prepare($sql);
    
    // Bind category parameter if exists
    if (!empty($category_filter)) {
        $stmt->bindValue(':category', $category_filter, PDO::PARAM_STR);
    }
    
    // Bind pagination parameters
    $stmt->bindValue(':limit', $items_per_page, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    
    $stmt->execute();
    $events = $stmt->fetchAll();
} catch (Exception $e) {
    error_log("Events query error: " . $e->getMessage());
    $events = [];
}

// Get featured events
try {
    $featured_stmt = $db->query("
        SELECT * FROM events 
        WHERE is_featured = 1 AND status = 'upcoming' AND event_date >= CURDATE()
        ORDER BY event_date ASC
        LIMIT 3
    ");
    $featured_events = $featured_stmt->fetchAll();
} catch (Exception $e) {
    $featured_events = [];
}

$categories = ['academic', 'sports', 'cultural', 'religious', 'seminar', 'other'];
$pageTitle = 'Events - ' . SITE_NAME;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    
    <meta name="description" content="Stay updated with upcoming events and activities at St. Mary's College of Catbalogan">
    <meta name="keywords" content="SMCC Events, Activities, Calendar, School Events">
    
    <link rel="icon" type="image/x-icon" href="<?php echo asset('images/logo/favicon.ico'); ?>">
    <link rel="stylesheet" href="<?php echo asset('css/main.css'); ?>">
    <link rel="stylesheet" href="<?php echo asset('css/responsive.css'); ?>">
    <!-- Component CSS Files -->
    <link rel="stylesheet" href="<?php echo asset('css/components/navbar.css'); ?>">
    <link rel="stylesheet" href="<?php echo asset('css/components/footer.css'); ?>">
    <link rel="stylesheet" href="<?php echo asset('css/components/cards.css'); ?>">
    <link rel="stylesheet" href="<?php echo asset('css/components/forms.css'); ?>">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&family=Open+Sans:wght@300;400;600&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/aos@2.3.1/dist/aos.css">
</head>
<body>
    
    <?php 
    $header_path = BASE_PATH . '/includes/header.php';
    if (file_exists($header_path)) {
        include $header_path;
    }
    ?>
    
    <!-- Page Header -->
       <section class="page-header" style="background: linear-gradient(135deg, rgba(0, 63, 135, 0.7), rgba(0, 40, 85, 0.9)), url('<?php echo asset("images/school header.jpg"); ?>') center/cover no-repeat;">
        <div class="page-header-overlay"></div>
        <div class="container">
            <div class="page-header-content" data-aos="fade-up">
                <h1 class="page-title">Events Calendar</h1>
                <nav class="breadcrumb">
                    <a href="<?php echo url(); ?>">Home</a>
                    <span class="separator">/</span>
                    <span class="current">Events</span>
                </nav>
            </div>
        </div>
    </section>
    
    <!-- Filter Section -->
    <section class="filter-section">
        <div class="container">
            <!-- Status Tabs -->
            <div class="filter-tabs" data-aos="fade-up">
                <a href="?status=upcoming&page=1" class="filter-tab <?php echo $status_filter === 'upcoming' ? 'active' : ''; ?>">
                    <i class="fas fa-calendar-check"></i>
                    <span>Upcoming Events</span>
                </a>
                <a href="?status=completed&page=1" class="filter-tab <?php echo $status_filter === 'completed' ? 'active' : ''; ?>">
                    <i class="fas fa-history"></i>
                    <span>Past Events</span>
                </a>
            </div>
            
            <!-- Category Filter -->
            <div class="filter-categories" data-aos="fade-up" data-aos-delay="100">
                <a href="?status=<?php echo $status_filter; ?>&page=1" class="category-chip <?php echo empty($category_filter) ? 'active' : ''; ?>">
                    All Categories
                </a>
                <?php foreach ($categories as $cat): ?>
                    <a href="?status=<?php echo $status_filter; ?>&category=<?php echo $cat; ?>&page=1" 
                       class="category-chip <?php echo $category_filter === $cat ? 'active' : ''; ?>">
                        <?php echo ucfirst($cat); ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    
    <!-- Main Content -->
    <section class="section-padding" style="background: #f8fafc;">
        <div class="container">
            <div class="row">
                <!-- Events Grid -->
                <div class="col-md-8">
                    <?php if (!empty($events)): ?>
                        <div class="events-grid">
                            <?php foreach ($events as $index => $event): ?>
                                <article class="event-card-modern" data-aos="fade-up" data-aos-delay="<?php echo min($index * 100, 400); ?>">
                                    <div class="event-image-wrapper">
                                        <?php if (!empty($event['featured_image'])): ?>
                                            <img src="<?php echo getImageUrl($event['featured_image']); ?>"
                                                 alt="<?php echo htmlspecialchars($event['title']); ?>"
                                                 loading="lazy"
                                                 onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22400%22 height=%22220%22%3E%3Crect fill=%22%23003f87%22 width=%22400%22 height=%22220%22/%3E%3Ctext fill=%22%23ffffff%22 font-family=%22Arial%22 font-size=%2224%22 x=%2250%25%22 y=%2250%25%22 text-anchor=%22middle%22 dominant-baseline=%22middle%22%3ENo Image%3C/text%3E%3C/svg%3E'">
                                        <?php endif; ?>
                                        
                                        <div class="event-date-overlay">
                                            <span class="event-date-day"><?php echo date('d', strtotime($event['event_date'])); ?></span>
                                            <span class="event-date-month"><?php echo date('M', strtotime($event['event_date'])); ?></span>
                                        </div>
                                        
                                        <span class="event-category-tag category-<?php echo htmlspecialchars($event['category']); ?>">
                                            <?php echo htmlspecialchars(ucfirst($event['category'])); ?>
                                        </span>
                                    </div>
                                    
                                    <div class="event-card-body">
                                        <a href="event-detail.php?slug=<?php echo htmlspecialchars($event['slug']); ?>" class="event-title-link">
                                            <h3 class="event-card-title"><?php echo htmlspecialchars($event['title']); ?></h3>
                                        </a>
                                        
                                        <div class="event-meta-list">
                                            <?php if (!empty($event['event_time'])): ?>
                                                <div class="event-meta-item">
                                                    <span class="event-meta-icon"><i class="fas fa-clock"></i></span>
                                                    <span><?php echo date('g:i A', strtotime($event['event_time'])); ?></span>
                                                </div>
                                            <?php endif; ?>
                                            <div class="event-meta-item">
                                                <span class="event-meta-icon"><i class="fas fa-map-marker-alt"></i></span>
                                                <span><?php echo htmlspecialchars($event['location']); ?></span>
                                            </div>
                                            <?php if (!empty($event['organizer'])): ?>
                                                <div class="event-meta-item">
                                                    <span class="event-meta-icon"><i class="fas fa-user"></i></span>
                                                    <span><?php echo htmlspecialchars($event['organizer']); ?></span>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <p class="event-description">
                                            <?php echo htmlspecialchars(truncateText(strip_tags($event['description']), 120)); ?>
                                        </p>
                                        
                                        <a href="event-detail.php?slug=<?php echo htmlspecialchars($event['slug']); ?>" class="event-cta">
                                            <span>Learn More</span>
                                            <i class="fas fa-arrow-right"></i>
                                        </a>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        </div>
                        
                        <!-- Pagination -->
                        <?php if ($total_pages > 1): ?>
                            <div class="pagination-modern" data-aos="fade-up">
                                <?php if ($current_page > 1): ?>
                                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $current_page - 1])); ?>" class="page-link-modern">
                                        <i class="fas fa-chevron-left"></i>
                                    </a>
                                <?php else: ?>
                                    <span class="page-link-modern disabled">
                                        <i class="fas fa-chevron-left"></i>
                                    </span>
                                <?php endif; ?>
                                
                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <?php if ($i == $current_page): ?>
                                        <span class="page-link-modern active"><?php echo $i; ?></span>
                                    <?php elseif ($i == 1 || $i == $total_pages || abs($i - $current_page) <= 2): ?>
                                        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>" class="page-link-modern">
                                            <?php echo $i; ?>
                                        </a>
                                    <?php elseif (abs($i - $current_page) == 3): ?>
                                        <span class="page-link-modern disabled">...</span>
                                    <?php endif; ?>
                                <?php endfor; ?>
                                
                                <?php if ($current_page < $total_pages): ?>
                                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $current_page + 1])); ?>" class="page-link-modern">
                                        <i class="fas fa-chevron-right"></i>
                                    </a>
                                <?php else: ?>
                                    <span class="page-link-modern disabled">
                                        <i class="fas fa-chevron-right"></i>
                                    </span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="empty-state" data-aos="fade-up">
                            <div class="empty-state-icon">
                                <i class="fas fa-calendar-times"></i>
                            </div>
                            <h3>No Events Found</h3>
                            <p>There are currently no <?php echo $status_filter; ?> events. Please check back later.</p>
                            <a href="?" class="cta-btn-s">
                                <i class="fas fa-calendar"></i>
                                <span>View All Events</span>
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Sidebar -->
                <div class="col-md-4">
                    <div class="sidebar-modern">
                        <!-- Featured Events -->
                        <?php if (!empty($featured_events) && $status_filter === 'upcoming'): ?>
                            <div class="sidebar-card" data-aos="fade-up">
                                <h3 class="sidebar-title">Featured Events</h3>
                                <?php foreach ($featured_events as $featured): ?>
                                    <div class="featured-event-item">
                                        <div class="featured-event-date">
                                            <i class="fas fa-calendar-day"></i>
                                            <?php echo date('M d, Y', strtotime($featured['event_date'])); ?>
                                        </div>
                                        <h4 class="featured-event-title">
                                            <a href="event-detail.php?slug=<?php echo htmlspecialchars($featured['slug']); ?>">
                                                <?php echo htmlspecialchars($featured['title']); ?>
                                            </a>
                                        </h4>
                                        <div class="featured-event-location">
                                            <i class="fas fa-map-marker-alt"></i>
                                            <?php echo htmlspecialchars($featured['location']); ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        
                        <!-- CTA Card -->
                        <div class="cta-card" data-aos="fade-up" data-aos-delay="100">
                            <i class="fas fa-bullhorn" style="font-size: 3rem; margin-bottom: 1rem;"></i>
                            <h3>Have an Event?</h3>
                            <p>Submit your organization's event to be featured on our calendar</p>
                            <a href="<?php echo url('pages/contact.php'); ?>" class="cta-btn">
                                <i class="fas fa-paper-plane"></i>
                                <span>Submit Event</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <?php 
    $footer_path = BASE_PATH . '/includes/footer.php';
    if (file_exists($footer_path)) {
        include $footer_path;
    }
    ?>
       <style>

        /* Page Header Styles */
        .page-header {
            position: relative;
            padding: 5rem 0 3rem;
            color: var(--color-white);
            margin-bottom: 0;
        }

        .page-header-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: var(--color-primary);
            opacity: 0.1;
        }

        .page-header-content {
            position: relative;
            z-index: 2;
        }

        .page-title {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 1rem;
            color: var(--color-white);
        }
        .breadcrumb {
            display: flex;
            justify-content: flex-start;
            gap: 0.5rem;
            font-size: 0.95rem;
        }

        .breadcrumb a {
            color: rgba(255, 255, 255, 0.9);
            text-decoration: none;
        }

        .breadcrumb a:hover {
            color: white;
        }

        .breadcrumb .separator {
            color: rgba(255, 255, 255, 0.6);
        }
        /* Filter Section - Clean Tabs */
        .filter-section {
            background: white;
            padding: 1.5rem 0;
            border-bottom: 1px solid #e5e7eb;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
        }
        
        .filter-tabs {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
        }
        
        .filter-tab {
            padding: 0.75rem 2rem;
            background: #f8fafc;
            border: 2px solid transparent;
            border-radius: 50px;
            color: #64748b;
            font-weight: 600;
            font-size: 0.95rem;
            text-decoration: none;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .filter-tab:hover {
            background: #f1f5f9;
            color: #003f87;
        }
        
        .filter-tab.active {
            background: #003f87;
            border-color: #003f87;
            color: white;
        }
        
        .filter-categories {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        
        .category-chip {
            padding: 0.5rem 1.25rem;
            background: white;
            border: 1.5px solid #e5e7eb;
            border-radius: 50px;
            color: #475569;
            font-size: 0.875rem;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.25s ease;
        }
        
        .category-chip:hover {
            border-color: #003f87;
            color: #003f87;
            transform: translateY(-2px);
        }
        
        .category-chip.active {
            background: #003f87;
            border-color: #003f87;
            color: white;
        }
        
        /* Events Grid - Modern Card Layout */
        .events-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 2rem;
            padding: 3rem 0;
        }
        
        .event-card-modern {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            flex-direction: column;
            height: 100%;
        }
        
        .event-card-modern:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 24px rgba(0, 63, 135, 0.15);
        }
        
        .event-image-wrapper {
            position: relative;
            height: 220px;
            overflow: hidden;
            background: linear-gradient(135deg, #003f87, #1a5fb4);
        }
        
        .event-image-wrapper img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.4s ease;
        }
        
        .event-card-modern:hover .event-image-wrapper img {
            transform: scale(1.08);
        }
        
        .event-date-overlay {
            position: absolute;
            top: 1rem;
            left: 1rem;
            background: white;
            padding: 0.75rem 1rem;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            text-align: center;
            min-width: 70px;
        }
        
        .event-date-day {
            display: block;
            font-size: 1.75rem;
            font-weight: 700;
            line-height: 1;
            color: #003f87;
            font-family: 'Outfit', sans-serif;
        }
        
        .event-date-month {
            display: block;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            color: #64748b;
            margin-top: 0.25rem;
            letter-spacing: 0.5px;
        }
        
        .event-category-tag {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(8px);
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .category-academic { color: #2563eb; }
        .category-sports { color: #059669; }
        .category-cultural { color: #dc2626; }
        .category-religious { color: #7c3aed; }
        .category-seminar { color: #ea580c; }
        .category-other { color: #64748b; }
        
        .event-card-body {
            padding: 1.75rem;
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        
        .event-title-link {
            text-decoration: none;
        }
        
        .event-card-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 1rem;
            line-height: 1.4;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .event-title-link:hover .event-card-title {
            color: #003f87;
        }
        
        .event-meta-list {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            margin-bottom: 1.25rem;
        }
        
        .event-meta-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 0.875rem;
            color: #64748b;
        }
        
        .event-meta-icon {
            width: 18px;
            height: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #003f87;
        }
        
        .event-description {
            color: #475569;
            font-size: 0.9375rem;
            line-height: 1.6;
            margin-bottom: 1.5rem;
            flex: 1;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .event-cta {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: #003f87;
            font-weight: 600;
            font-size: 0.9375rem;
            text-decoration: none;
            transition: gap 0.3s ease;
        }
        
        .event-cta:hover {
            gap: 0.75rem;
        }
        
        .event-cta i {
            transition: transform 0.3s ease;
        }
        
        .event-cta:hover i {
            transform: translateX(3px);
        }
        
        /* Sidebar - Modern Design */
        .sidebar-modern {
            position: sticky;
            top: 120px;
        }
        
        .sidebar-card {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
            margin-bottom: 2rem;
        }
        
        .sidebar-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 1.5rem;
        }
        
        .featured-event-item {
            padding: 1.25rem;
            background: #f8fafc;
            border-radius: 12px;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
        }
        
        .featured-event-item:hover {
            background: #f1f5f9;
            transform: translateX(4px);
        }
        
        .featured-event-item:last-child {
            margin-bottom: 0;
        }
        
        .featured-event-date {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
            font-weight: 600;
            color: #003f87;
            margin-bottom: 0.5rem;
        }
        
        .featured-event-title {
            font-size: 0.9375rem;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 0.5rem;
            line-height: 1.4;
        }
        
        .featured-event-title a {
            color: inherit;
            text-decoration: none;
            transition: color 0.3s ease;
        }
        
        .featured-event-title a:hover {
            color: #003f87;
        }
        
        .featured-event-location {
            font-size: 0.8125rem;
            color: #64748b;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        /* CTA Card */
        .cta-card {
            background: linear-gradient(135deg, #003f87, #1a5fb4);
            color: white;
            border-radius: 16px;
            padding: 2.5rem 2rem;
            text-align: center;
        }
        
        .cta-card h3 {
            color: white;
            font-size: 1.5rem;
            margin-bottom: 0.75rem;
        }
        
        .cta-card p {
            color: rgba(255, 255, 255, 0.9);
            margin-bottom: 1.5rem;
            line-height: 1.6;
        }
        
        .cta-btn, .cta-btn-s {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.875rem 2rem;
            background: white;
            color: #003f87;
            border-radius: 50px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            border: 2px solid #003f87;
        }
        
        .cta-btn:hover {
            background: var(--color-secondary);
            border-color: var(--color-secondary);
            color: var(--color-primary);
        }
        .cta-btn-s:hover {
            background: var(--color-primary);
            border-color: var(--color-primary);
            color: var(--color-white);
        }
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 5rem 2rem;
            background: white;
            border-radius: 16px;
            margin: 3rem 0;
        }
        
        .empty-state-icon {
            width: 100px;
            height: 100px;
            margin: 0 auto 1.5rem;
            background: #f1f5f9;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            color: #cbd5e1;
        }
        
        .empty-state h3 {
            font-size: 1.5rem;
            color: #1e293b;
            margin-bottom: 0.75rem;
        }
        
        .empty-state p {
            color: #64748b;
            font-size: 1.125rem;
            margin-bottom: 2rem;
        }
        
        /* Pagination - Modern Style */
        .pagination-modern {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 0.5rem;
            margin: 3rem 0;
        }
        
        .page-link-modern {
            display: flex;
            align-items: center;
            justify-content: center;
            min-width: 44px;
            height: 44px;
            padding: 0 1rem;
            background: white;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            color: #475569;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.25s ease;
        }
        
        .page-link-modern:hover {
            border-color: #003f87;
            color: #003f87;
            transform: translateY(-2px);
        }
        
        .page-link-modern.active {
            background: #003f87;
            border-color: #003f87;
            color: white;
        }
        
        .page-link-modern.disabled {
            opacity: 0.4;
            pointer-events: none;
        }
        
        /* Responsive Design */
        @media (max-width: 1024px) {
            .events-grid {
                grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            }
        }
        
        @media (max-width: 768px) {
            
            .events-grid {
                grid-template-columns: 1fr;
                gap: 1.5rem;
                padding: 2rem 0;
            }
            
            .filter-section {
                position: relative;
            }
            
            .sidebar-modern {
                position: relative;
                top: 0;
            }
            
            .filter-tab {
                flex: 1;
                justify-content: center;
                min-width: 150px;
            }
        }
        
        /* Loading Animation */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .event-card-modern {
            animation: fadeInUp 0.6s ease-out;
        }
    </style>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init({
            duration: 600,
            once: true,
            easing: 'ease-out'
        });
    </script>
</body>
</html>
