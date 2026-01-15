<?php
/**
 * MARIANCONNECT - Events Page
 * Displays upcoming and past events
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
$items_per_page = 12;
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
    $count_stmt->execute($params);
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
    // Fix image paths for all events
    foreach ($events as &$event) {
        if (!empty($event['featured_image'])) {
            $event['featured_image'] = asset($event['featured_image']);
        }
    }
    unset($event); // Break reference
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

    foreach ($featured_events as &$featured) {
        if (!empty($featured['featured_image'])) {
            $featured['featured_image'] = asset($featured['featured_image']);
        }
    }
    unset($featured);
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
    <section class="page-header">
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
    
    <!-- Main Content -->
    <section class="page-content section-padding">
        <div class="container">
            <!-- Filter Tabs -->
            <div class="filter-tabs" data-aos="fade-up">
                <a href="?status=upcoming&page=1" class="tab-btn <?php echo $status_filter === 'upcoming' ? 'active' : ''; ?>">
                    <i class="fas fa-calendar-check"></i> Upcoming Events
                </a>
                <a href="?status=completed&page=1" class="tab-btn <?php echo $status_filter === 'completed' ? 'active' : ''; ?>">
                    <i class="fas fa-history"></i> Past Events
                </a>
            </div>
            
            <div class="row">
                <!-- Main Content -->
                <div class="col-md-8">
                    <!-- Category Filter -->
                    <div class="filter-bar" data-aos="fade-up">
                        <div class="filter-label">Category:</div>
                        <div class="filter-buttons">
                            <a href="?status=<?php echo $status_filter; ?>&page=1" class="filter-btn <?php echo empty($category_filter) ? 'active' : ''; ?>">All</a>
                            <?php foreach ($categories as $cat): ?>
                                <a href="?status=<?php echo $status_filter; ?>&category=<?php echo $cat; ?>&page=1" 
                                   class="filter-btn <?php echo $category_filter === $cat ? 'active' : ''; ?>">
                                    <?php echo ucfirst($cat); ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <!-- Events List -->
                    <?php if (!empty($events)): ?>
                        <div class="events-list">
                            <?php foreach ($events as $index => $event): ?>
                                <article class="event-card-horizontal" data-aos="fade-up" data-aos-delay="<?php echo $index * 100; ?>">
                                    <div class="event-date-badge">
                                        <span class="date-day"><?php echo date('d', strtotime($event['event_date'])); ?></span>
                                        <span class="date-month"><?php echo date('M', strtotime($event['event_date'])); ?></span>
                                        <span class="date-year"><?php echo date('Y', strtotime($event['event_date'])); ?></span>
                                    </div>
                                    
                                    <div class="event-info">
                                        <div class="event-category-badge">
                                            <?php echo htmlspecialchars(ucfirst($event['category'])); ?>
                                        </div>
                                        <h3 class="event-title">
                                            <a href="event-detail.php?slug=<?php echo htmlspecialchars($event['slug']); ?>">
                                                <?php echo htmlspecialchars($event['title']); ?>
                                            </a>
                                        </h3>
                                        
                                        <div class="event-meta">
                                            <?php if (!empty($event['event_time'])): ?>
                                                <span class="meta-item">
                                                    <i class="fas fa-clock"></i> <?php echo date('g:i A', strtotime($event['event_time'])); ?>
                                                </span>
                                            <?php endif; ?>
                                            <span class="meta-item">
                                                <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($event['location']); ?>
                                            </span>
                                            <?php if (!empty($event['organizer'])): ?>
                                                <span class="meta-item">
                                                    <i class="fas fa-user"></i> <?php echo htmlspecialchars($event['organizer']); ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <p class="event-description">
                                            <?php echo htmlspecialchars(truncateText(strip_tags($event['description']), 150)); ?>
                                        </p>
                                        
                                        <a href="event-detail.php?slug=<?php echo htmlspecialchars($event['slug']); ?>" class="btn btn-outline-primary btn-sm">
                                            View Details
                                        </a>
                                    </div>
                                    
                                    <?php if (!empty($event['featured_image'])): ?>
                                        <div class="event-image">
                                            <img src="<?php echo htmlspecialchars($event['featured_image']); ?>"
                                                 alt="<?php echo htmlspecialchars($event['title']); ?>"
                                                 onerror="this.src='https://via.placeholder.com/300x200/003f87/ffffff?text=Event'">
                                        </div>
                                    <?php endif; ?>
                                </article>
                            <?php endforeach; ?>
                        </div>
                        
                        <!-- Pagination -->
                        <?php if ($total_pages > 1): ?>
                            <div class="pagination" data-aos="fade-up">
                                <?php if ($current_page > 1): ?>
                                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $current_page - 1])); ?>" class="page-link">
                                        <i class="fas fa-chevron-left"></i> Previous
                                    </a>
                                <?php endif; ?>
                                
                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <?php if ($i == $current_page): ?>
                                        <span class="page-link active"><?php echo $i; ?></span>
                                    <?php elseif ($i == 1 || $i == $total_pages || abs($i - $current_page) <= 2): ?>
                                        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>" class="page-link">
                                            <?php echo $i; ?>
                                        </a>
                                    <?php elseif (abs($i - $current_page) == 3): ?>
                                        <span class="page-link">...</span>
                                    <?php endif; ?>
                                <?php endfor; ?>
                                
                                <?php if ($current_page < $total_pages): ?>
                                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $current_page + 1])); ?>" class="page-link">
                                        Next <i class="fas fa-chevron-right"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="no-results" data-aos="fade-up">
                            <i class="fas fa-calendar-times"></i>
                            <h3>No Events Found</h3>
                            <p>There are currently no <?php echo $status_filter; ?> events. Please check back later.</p>
                            <a href="?" class="btn btn-primary">View All Events</a>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Sidebar -->
                <div class="col-md-4">
                    <!-- Featured Events -->
                    <?php if (!empty($featured_events) && $status_filter === 'upcoming'): ?>
                        <div class="sidebar-widget" data-aos="fade-up">
                            <h3 class="widget-title">Featured Events</h3>
                            <div class="featured-events-list">
                                <?php foreach ($featured_events as $featured): ?>
                                    <article class="featured-event-item">
                                        <div class="featured-event-date">
                                            <span class="f-date-day"><?php echo date('d', strtotime($featured['event_date'])); ?></span>
                                            <span class="f-date-month"><?php echo date('M', strtotime($featured['event_date'])); ?></span>
                                        </div>
                                        <div class="featured-event-content">
                                            <h4>
                                                <a href="event-detail.php?slug=<?php echo htmlspecialchars($featured['slug']); ?>">
                                                    <?php echo htmlspecialchars($featured['title']); ?>
                                                </a>
                                            </h4>
                                            <span class="f-event-location">
                                                <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($featured['location']); ?>
                                            </span>
                                        </div>
                                    </article>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Categories -->
                    <div class="sidebar-widget" data-aos="fade-up" data-aos-delay="100">
                        <h3 class="widget-title">Event Categories</h3>
                        <ul class="category-list">
                            <li><a href="?status=<?php echo $status_filter; ?>&page=1">All Categories</a></li>
                            <?php foreach ($categories as $cat): ?>
                                <li class="<?php echo $category_filter === $cat ? 'active' : ''; ?>">
                                    <a href="?status=<?php echo $status_filter; ?>&category=<?php echo $cat; ?>&page=1">
                                        <?php echo ucfirst($cat); ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    
                    <!-- Event Submission CTA -->
                    <div class="sidebar-widget cta-widget" data-aos="fade-up" data-aos-delay="200">
                        <h3>Got an Event?</h3>
                        <p>Want to feature your organization's event? Contact us today!</p>
                        <a href="<?php echo url('pages/contact.php'); ?>" class="btn btn-primary btn-block">Submit Event</a>
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
    
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init({
            duration: 800,
            once: true
        });
    </script>
</body>
</html>

<style>
/* Page Header Styles */
.page-header {
    position: relative;
    background: linear-gradient(135deg, var(--color-primary-dark), var(--color-primary));
    padding: 5rem 0 3rem;
    color: var(--color-white);
    margin-bottom: 3rem;
}

.page-header-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: url('../assets/images/patterns/pattern-overlay.png') repeat;
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
    align-items: center;
    gap: 0.5rem;
    font-size: 0.95rem;
}

.breadcrumb a {
    color: var(--color-white);
    opacity: 0.8;
    transition: opacity var(--transition-base);
}

.breadcrumb a:hover {
    opacity: 1;
}

.breadcrumb .separator {
    opacity: 0.5;
}

.breadcrumb .current {
    opacity: 1;
    font-weight: 600;
}
/* Filter Bar */
.filter-bar {
    background: var(--color-white);
    padding: 1.5rem;
    border-radius: var(--border-radius-lg);
    box-shadow: var(--shadow-md);
    margin-bottom: 2rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    flex-wrap: wrap;
}

.filter-label {
    font-weight: 600;
    color: var(--color-primary);
}

.filter-buttons {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.filter-btn {
    padding: 0.5rem 1rem;
    border: 2px solid var(--color-light-gray);
    border-radius: var(--border-radius-md);
    color: var(--color-dark-gray);
    font-weight: 500;
    transition: all var(--transition-base);
    text-decoration: none;
}

.filter-btn:hover,
.filter-btn.active {
    background-color: var(--color-primary);
    border-color: var(--color-primary);
    color: var(--color-white);
}
/* Filter Tabs */
.filter-tabs {
    display: flex;
    gap: 1rem;
    margin-bottom: 2rem;
    justify-content: center;
}

.tab-btn {
    padding: 1rem 2rem;
    background: var(--color-white);
    border: 2px solid var(--color-light-gray);
    border-radius: var(--border-radius-lg);
    color: var(--color-dark-gray);
    font-weight: 600;
    transition: all var(--transition-base);
    box-shadow: var(--shadow-sm);
}

.tab-btn:hover,
.tab-btn.active {
    background: var(--color-primary);
    border-color: var(--color-primary);
    color: var(--color-white);
    box-shadow: var(--shadow-md);
}

/* Event Card Horizontal */
.events-list {
    display: flex;
    flex-direction: column;
    gap: 2rem;
    margin-bottom: 2rem;
}

.event-card-horizontal {
    background: var(--color-white);
    border-radius: var(--border-radius-lg);
    box-shadow: var(--shadow-md);
    padding: 1.5rem;
    display: grid;
    grid-template-columns: 100px 1fr auto;
    gap: 1.5rem;
    align-items: start;
    transition: all var(--transition-base);
}

.event-card-horizontal:hover {
    transform: translateY(-5px);
    box-shadow: var(--shadow-xl);
}

.event-date-badge {
    background: linear-gradient(135deg, var(--color-primary), var(--color-primary-light));
    color: var(--color-white);
    text-align: center;
    padding: 1rem;
    border-radius: var(--border-radius-lg);
    display: flex;
    flex-direction: column;
}

.date-day {
    font-size: 2.5rem;
    font-weight: 700;
    line-height: 1;
}

.date-month {
    font-size: 1rem;
    text-transform: uppercase;
    margin-top: 0.25rem;
}

.date-year {
    font-size: 0.875rem;
    opacity: 0.9;
    margin-top: 0.25rem;
}

.event-info {
    flex: 1;
}

.event-category-badge {
    display: inline-block;
    padding: 0.25rem 0.75rem;
    background: var(--color-secondary);
    color: var(--color-dark-gray);
    border-radius: var(--border-radius-sm);
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    margin-bottom: 0.5rem;
}

.event-title {
    font-size: 1.5rem;
    margin-bottom: 0.75rem;
}

.event-title a {
    color: var(--color-primary);
    transition: color var(--transition-base);
}

.event-title a:hover {
    color: var(--color-primary-light);
}

.event-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    margin-bottom: 1rem;
    font-size: 0.875rem;
    color: var(--color-gray);
}

.event-meta i {
    color: var(--color-primary);
}

.event-description {
    color: var(--color-gray);
    margin-bottom: 1rem;
    line-height: 1.6;
}

.event-image {
    width: 250px;
    height: 180px;
    border-radius: var(--border-radius-lg);
    overflow: hidden;
}

.event-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform var(--transition-slow);
}

.event-card-horizontal:hover .event-image img {
    transform: scale(1.1);
}
/* No Results */
.no-results {
    text-align: center;
    padding: 4rem 2rem;
    background: var(--color-white);
    border-radius: var(--border-radius-lg);
    box-shadow: var(--shadow-md);
}

.no-results i {
    font-size: 4rem;
    color: var(--color-gray);
    margin-bottom: 1rem;
}

.no-results h3 {
    margin-bottom: 1rem;
    color: var(--color-primary);
}

.no-results p {
    color: var(--color-gray);
    margin-bottom: 2rem;
}

/* Sidebar Widgets */
.sidebar-widget {
    background: var(--color-white);
    padding: 1.5rem;
    border-radius: var(--border-radius-lg);
    box-shadow: var(--shadow-md);
    margin-bottom: 2rem;
}

.widget-title {
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--color-primary);
    margin-bottom: 1.5rem;
    padding-bottom: 0.75rem;
    border-bottom: 2px solid var(--color-light-gray);
}
/* Category List */
.category-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.category-list li {
    margin-bottom: 0.75rem;
}

.category-list a {
    display: block;
    padding: 0.75rem 1rem;
    color: var(--color-dark-gray);
    border-radius: var(--border-radius-md);
    transition: all var(--transition-base);
    text-decoration: none;
}

.category-list li.active a,
.category-list a:hover {
    background-color: var(--color-primary);
    color: var(--color-white);
}

/* CTA Widget */
.cta-widget {
    background: linear-gradient(135deg, var(--color-primary), var(--color-primary-light));
    color: var(--color-white);
}

.cta-widget h3 {
    color: var(--color-white);
    border-bottom-color: rgba(255, 255, 255, 0.3);
}

.cta-widget p {
    color: rgba(255, 255, 255, 0.9);
    line-height: 1.6;
}

.cta-widget .btn {
    background: var(--color-white);
    color: var(--color-primary);
    border-color: var(--color-white);
}

.cta-widget .btn:hover {
    background: var(--color-secondary);
    border-color: var(--color-secondary);
    color: var(--color-primary);
}
/* Featured Events */
.featured-events-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.featured-event-item {
    display: flex;
    gap: 1rem;
    padding: 1rem;
    background: var(--color-off-white);
    border-radius: var(--border-radius-md);
    transition: all var(--transition-base);
}

.featured-event-item:hover {
    background: var(--color-light-gray);
}

.featured-event-date {
    background: linear-gradient(135deg, var(--color-primary), var(--color-primary-light));
    color: var(--color-white);
    text-align: center;
    padding: 0.75rem 0.5rem;
    border-radius: var(--border-radius-md);
    min-width: 60px;
}

.f-date-day {
    display: block;
    font-size: 1.5rem;
    font-weight: 700;
    line-height: 1;
}

.f-date-month {
    display: block;
    font-size: 0.75rem;
    text-transform: uppercase;
    margin-top: 0.25rem;
}

.featured-event-content h4 {
    font-size: 0.9375rem;
    line-height: 1.4;
    margin-bottom: 0.5rem;
}

.featured-event-content a {
    color: var(--color-dark-gray);
    transition: color var(--transition-base);
}

.featured-event-content a:hover {
    color: var(--color-primary);
}

.f-event-location {
    font-size: 0.8125rem;
    color: var(--color-gray);
}

/* Responsive */
@media (max-width: 768px) {
    .filter-tabs {
        flex-direction: column;
    }
    
    .event-card-horizontal {
        grid-template-columns: 1fr;
        gap: 1rem;
    }
    
    .event-date-badge {
        width: 100px;
        margin: 0 auto;
    }
    
    .event-image {
        width: 100%;
        height: 200px;
        order: -1;
    }
}
</style>
