<?php
/**
 * MARIANCONNECT - Event Detail Page
 * Displays detailed information about a specific event
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

// Get slug from URL
$slug = isset($_GET['slug']) ? sanitize($_GET['slug']) : '';

if (empty($slug)) {
    header("Location: events.php");
    exit;
}

// Fetch event details
try {
    $stmt = $db->prepare("
        SELECT * FROM events
        WHERE slug = ?
        LIMIT 1
    ");
    $stmt->execute([$slug]);
    $event = $stmt->fetch();
    
    if (!$event) {
        header("Location: events.php");
        exit;
    }
    // Fix image path for the main event
    if (!empty($event['featured_image'])) {
        $event['featured_image'] = asset($event['featured_image']);
    }
} catch (Exception $e) {
    error_log("Event fetch error: " . $e->getMessage());
    header("Location: events.php");
    exit;
}

// Get related events (same category)
try {
    $related_stmt = $db->prepare("
        SELECT * FROM events
        WHERE category = ? AND slug != ? AND status = 'upcoming' AND event_date >= CURDATE()
        ORDER BY event_date ASC
        LIMIT 3
    ");
    $related_stmt->execute([$event['category'], $slug]);
    $related_events = $related_stmt->fetchAll();
    // Fix image paths for related events
    foreach ($related_events as &$related) {
        if (!empty($related['featured_image'])) {
            $related['featured_image'] = asset($related['featured_image']);
        }
    }
    unset($related);
} catch (Exception $e) {
    $related_events = [];
}

// Categories
$categories = [
    'academic' => 'Academic',
    'sports' => 'Sports',
    'cultural' => 'Cultural',
    'religious' => 'Religious',
    'seminar' => 'Seminar',
    'other' => 'Other'
];

$pageTitle = htmlspecialchars($event['title']) . ' - Events - ' . SITE_NAME;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    
    <!-- SEO Meta Tags -->
    <meta name="description" content="<?php echo htmlspecialchars(truncateText(strip_tags($event['description']), 160)); ?>">
    <meta name="keywords" content="SMCC Events, <?php echo htmlspecialchars($event['title']); ?>, <?php echo htmlspecialchars($event['category']); ?>">
    
    <!-- Open Graph -->
    <meta property="og:type" content="event">
    <meta property="og:title" content="<?php echo htmlspecialchars($event['title']); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars(truncateText(strip_tags($event['description']), 160)); ?>">
    <?php if (!empty($event['featured_image'])): ?>
        <meta property="og:image" content="<?php echo htmlspecialchars($event['featured_image']); ?>">
    <?php endif; ?>
    
    <link rel="icon" type="image/x-icon" href="<?php echo asset('images/logo/favicon.ico'); ?>">
    <link rel="stylesheet" href="<?php echo asset('css/main.css'); ?>">
    <link rel="stylesheet" href="<?php echo asset('css/responsive.css'); ?>">
    
    <!--  Component CSS Files  -->
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
    <section class="page-header page-header-sm">
        <div class="page-header-overlay"></div>
        <div class="container">
            <div class="page-header-content" data-aos="fade-up">
                <nav class="breadcrumb">
                    <a href="<?php echo url(); ?>">Home</a>
                    <span class="separator">/</span>
                    <a href="events.php">Events</a>
                    <span class="separator">/</span>
                    <span class="current"><?php echo htmlspecialchars($event['title']); ?></span>
                </nav>
            </div>
        </div>
    </section>
    
    <!-- Event Details -->
    <section class="event-detail-section section-padding">
        <div class="container">
            <div class="row">
                <!-- Main Content -->
                <div class="col-md-8">
                    <article class="event-detail-card" data-aos="fade-up">
                        <!-- Event Header -->
                        <div class="event-header">
                            <div class="event-badges">
                                <span class="category-badge">
                                    <i class="fas fa-tag"></i>
                                    <?php echo htmlspecialchars($categories[$event['category']] ?? ucfirst($event['category'])); ?>
                                </span>
                                <span class="status-badge status-<?php echo $event['status']; ?>">
                                    <i class="fas fa-circle"></i>
                                    <?php echo htmlspecialchars(ucfirst($event['status'])); ?>
                                </span>
                            </div>
                            
                            <h1><?php echo htmlspecialchars($event['title']); ?></h1>
                            
                            <div class="event-info-row">
                                <div class="info-item">
                                    <i class="fas fa-calendar"></i>
                                    <span><?php echo formatDate($event['event_date'], 'F j, Y'); ?></span>
                                </div>
                                
                                <?php if (!empty($event['event_time'])): ?>
                                    <div class="info-item">
                                        <i class="fas fa-clock"></i>
                                        <span><?php echo date('g:i A', strtotime($event['event_time'])); ?></span>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="info-item">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <span><?php echo htmlspecialchars($event['location']); ?></span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Featured Image -->
                        <?php if (!empty($event['featured_image'])): ?>
                            <div class="event-image">
                                <img src="<?php echo htmlspecialchars($event['featured_image']); ?>" 
                                     alt="<?php echo htmlspecialchars($event['title']); ?>"
                                     onerror="this.src='https://via.placeholder.com/800x400/003f87/ffffff?text=Event'">
                            </div>
                        <?php endif; ?>
                        
                        <!-- Event Description -->
                        <div class="event-section">
                            <h2><i class="fas fa-info-circle"></i> Event Details</h2>
                            <div class="event-description">
                                <?php echo nl2br(htmlspecialchars($event['description'])); ?>
                            </div>
                        </div>
                        
                        <!-- Additional Information -->
                        <?php if (!empty($event['organizer']) || !empty($event['contact_info'])): ?>
                            <div class="event-section">
                                <h2><i class="fas fa-users"></i> Organizer Information</h2>
                                <div class="organizer-info">
                                    <?php if (!empty($event['organizer'])): ?>
                                        <p><strong>Organized by:</strong> <?php echo htmlspecialchars($event['organizer']); ?></p>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($event['contact_info'])): ?>
                                        <p><strong>Contact:</strong> <?php echo htmlspecialchars($event['contact_info']); ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Registration Info -->
                        <?php if ($event['registration_required']): ?>
                            <div class="event-section registration-section">
                                <h2><i class="fas fa-clipboard-check"></i> Registration Required</h2>
                                <?php if (!empty($event['max_participants'])): ?>
                                    <p><strong>Maximum Participants:</strong> <?php echo htmlspecialchars($event['max_participants']); ?></p>
                                <?php endif; ?>
                                <p>This event requires registration. Please contact the organizers for more information.</p>
                                <a href="contact.php" class="btn btn-primary">
                                    <i class="fas fa-envelope"></i> Register Now
                                </a>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Share Event -->
                        <div class="event-share">
                            <span class="share-label">Share this event:</span>
                            <div class="share-buttons">
                                <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode(url('pages/event-detail.php?slug=' . $event['slug'])); ?>" 
                                   target="_blank" class="share-btn facebook">
                                    <i class="fab fa-facebook-f"></i>
                                </a>
                                <a href="https://twitter.com/intent/tweet?url=<?php echo urlencode(url('pages/event-detail.php?slug=' . $event['slug'])); ?>&text=<?php echo urlencode($event['title']); ?>" 
                                   target="_blank" class="share-btn twitter">
                                    <i class="fab fa-twitter"></i>
                                </a>
                                <a href="mailto:?subject=<?php echo urlencode($event['title']); ?>&body=<?php echo urlencode(url('pages/event-detail.php?slug=' . $event['slug'])); ?>" 
                                   class="share-btn email">
                                    <i class="fas fa-envelope"></i>
                                </a>
                            </div>
                        </div>
                    </article>
                    
                    <!-- Related Events -->
                    <?php if (!empty($related_events)): ?>
                        <section class="related-events" data-aos="fade-up">
                            <h2 class="section-title">Other Upcoming Events</h2>
                            <div class="related-grid">
                                <?php foreach ($related_events as $related): ?>
                                    <div class="related-event-card">
                                        <div class="related-date">
                                            <span class="date-day"><?php echo date('d', strtotime($related['event_date'])); ?></span>
                                            <span class="date-month"><?php echo date('M', strtotime($related['event_date'])); ?></span>
                                        </div>
                                        <div class="related-content">
                                            <h3>
                                                <a href="event-detail.php?slug=<?php echo htmlspecialchars($related['slug']); ?>">
                                                    <?php echo htmlspecialchars($related['title']); ?>
                                                </a>
                                            </h3>
                                            <p class="related-location">
                                                <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($related['location']); ?>
                                            </p>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </section>
                    <?php endif; ?>
                </div>
                
                <!-- Sidebar -->
                <div class="col-md-4">
                    <!-- Event Info Card -->
                    <div class="sidebar-widget event-info-widget" data-aos="fade-up">
                        <h3 class="widget-title">Event Information</h3>
                        <div class="info-list">
                            <div class="info-item">
                                <i class="fas fa-calendar-alt"></i>
                                <div>
                                    <span class="info-label">Date</span>
                                    <span class="info-value"><?php echo formatDate($event['event_date'], 'F j, Y'); ?></span>
                                </div>
                            </div>
                            
                            <?php if (!empty($event['event_time'])): ?>
                                <div class="info-item">
                                    <i class="fas fa-clock"></i>
                                    <div>
                                        <span class="info-label">Time</span>
                                        <span class="info-value"><?php echo date('g:i A', strtotime($event['event_time'])); ?></span>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($event['end_date'])): ?>
                                <div class="info-item">
                                    <i class="fas fa-calendar-check"></i>
                                    <div>
                                        <span class="info-label">End Date</span>
                                        <span class="info-value"><?php echo formatDate($event['end_date'], 'F j, Y'); ?></span>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <div class="info-item">
                                <i class="fas fa-map-marker-alt"></i>
                                <div>
                                    <span class="info-label">Location</span>
                                    <span class="info-value"><?php echo htmlspecialchars($event['location']); ?></span>
                                </div>
                            </div>
                            
                            <div class="info-item">
                                <i class="fas fa-tag"></i>
                                <div>
                                    <span class="info-label">Category</span>
                                    <span class="info-value"><?php echo htmlspecialchars($categories[$event['category']] ?? ucfirst($event['category'])); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Add to Calendar -->
                    <div class="sidebar-widget calendar-widget" data-aos="fade-up" data-aos-delay="100">
                        <h3 class="widget-title">Add to Calendar</h3>
                        <p>Don't forget about this event! Add it to your calendar.</p>
                        <a href="#" class="btn btn-outline-primary btn-block">
                            <i class="fas fa-calendar-plus"></i> Add to Calendar
                        </a>
                    </div>
                    
                    <!-- Contact Organizer -->
                    <div class="sidebar-widget cta-widget" data-aos="fade-up" data-aos-delay="200">
                        <h3>Questions?</h3>
                        <p>Contact us for more information about this event.</p>
                        <a href="contact.php" class="btn btn-primary btn-block">
                            <i class="fas fa-envelope"></i> Contact Us
                        </a>
                    </div>
                    
                    <!-- Back to Events -->
                    <div class="sidebar-widget" data-aos="fade-up" data-aos-delay="300">
                        <a href="events.php" class="btn btn-outline-primary btn-block">
                            <i class="fas fa-arrow-left"></i> Back to Events
                        </a>
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
/* Event Detail Card */
.event-detail-card {
    background: var(--color-white);
    padding: 2.5rem;
    border-radius: var(--border-radius-lg);
    box-shadow: var(--shadow-md);
    margin-bottom: 2rem;
}

/* Event Header */
.event-header {
    margin-bottom: 2rem;
}

.event-badges {
    display: flex;
    gap: 0.75rem;
    margin-bottom: 1.5rem;
    flex-wrap: wrap;
}

.category-badge,
.status-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    border-radius: var(--border-radius-md);
    font-size: 0.875rem;
    font-weight: 700;
    text-transform: uppercase;
}

.category-badge {
    background: var(--color-secondary);
    color: var(--color-dark-gray);
}

.status-badge {
    background: var(--color-primary);
    color: var(--color-white);
}

.status-badge.status-upcoming {
    background: #28a745;
}

.status-badge.status-ongoing {
    background: #ffc107;
    color: var(--color-dark-gray);
}

.status-badge.status-completed {
    background: #6c757d;
}

.status-badge.status-cancelled {
    background: #dc3545;
}

.event-header h1 {
    font-size: 2.5rem;
    line-height: 1.3;
    margin-bottom: 1.5rem;
    color: var(--color-primary);
}

.event-info-row {
    display: flex;
    gap: 2rem;
    padding-top: 1rem;
    border-top: 2px solid var(--color-light-gray);
    flex-wrap: wrap;
}

.info-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: var(--color-gray);
}

.info-item i {
    color: var(--color-primary);
    font-size: 1.125rem;
}

/* Event Image */
.event-image {
    margin-bottom: 2rem;
    border-radius: var(--border-radius-lg);
    overflow: hidden;
}

.event-image img {
    width: 100%;
    height: auto;
    display: block;
}

/* Event Sections */
.event-section {
    margin-bottom: 2.5rem;
    padding-bottom: 2rem;
    border-bottom: 1px solid var(--color-light-gray);
}

.event-section:last-child {
    border-bottom: none;
}

.event-section h2 {
    font-size: 1.75rem;
    margin-bottom: 1.5rem;
    color: var(--color-primary);
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.event-description,
.organizer-info {
    line-height: 1.8;
    color: var(--color-dark-gray);
}

.registration-section {
    background: var(--color-off-white);
    padding: 2rem;
    border-radius: var(--border-radius-lg);
    border: 2px solid var(--color-light-gray);
}

/* Event Share */
.event-share {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding-top: 2rem;
    border-top: 2px solid var(--color-light-gray);
    flex-wrap: wrap;
}

.share-label {
    font-weight: 600;
    color: var(--color-dark-gray);
}

.share-buttons {
    display: flex;
    gap: 0.75rem;
}

.share-btn {
    width: 45px;
    height: 45px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    color: var(--color-white);
    font-size: 1.125rem;
    transition: all var(--transition-base);
}

.share-btn:hover {
    transform: translateY(-3px);
    box-shadow: var(--shadow-lg);
}

.share-btn.facebook { background-color: #3b5998; }
.share-btn.twitter { background-color: #1da1f2; }
.share-btn.email { background-color: var(--color-gray); }

/* Related Events */
.related-events {
    margin-top: 3rem;
}

.related-grid {
    display: grid;
    gap: 1.5rem;
    margin-top: 2rem;
}

.related-event-card {
    background: var(--color-white);
    padding: 1.5rem;
    border-radius: var(--border-radius-lg);
    box-shadow: var(--shadow-md);
    display: flex;
    gap: 1.5rem;
    transition: all var(--transition-base);
}

.related-event-card:hover {
    transform: translateY(-5px);
    box-shadow: var(--shadow-xl);
}

.related-date {
    background: linear-gradient(135deg, var(--color-primary), var(--color-primary-light));
    color: var(--color-white);
    text-align: center;
    padding: 1rem;
    border-radius: var(--border-radius-md);
    min-width: 80px;
}

.date-day {
    display: block;
    font-size: 2rem;
    font-weight: 700;
    line-height: 1;
}

.date-month {
    display: block;
    font-size: 0.875rem;
    text-transform: uppercase;
    margin-top: 0.25rem;
}

.related-content h3 {
    font-size: 1.125rem;
    margin-bottom: 0.5rem;
}

.related-content a {
    color: var(--color-dark-gray);
    transition: color var(--transition-base);
}

.related-content a:hover {
    color: var(--color-primary);
}

.related-location {
    font-size: 0.9375rem;
    color: var(--color-gray);
}

/* ============================================
   IMPROVED SIDEBAR WIDGETS
   Better contrast and modern card design
   ============================================ */

.sidebar-widget {
    background: var(--color-white);
    padding: 2rem;
    border-radius: var(--border-radius-lg);
    box-shadow: var(--shadow-md);
    margin-bottom: 2rem;
    border: 1px solid var(--color-light-gray);
}

.widget-title {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--color-primary);
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 3px solid var(--color-primary);
}

/* Event Info Widget - Modern Light Design */
.event-info-widget {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border-left: 5px solid var(--color-primary);
}

.event-info-widget .widget-title {
    color: var(--color-primary);
    border-color: var(--color-primary);
}

.info-list {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.info-list .info-item {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
    padding: 1rem;
    background: var(--color-white);
    border-radius: var(--border-radius-md);
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    transition: all var(--transition-base);
}

.info-list .info-item:hover {
    transform: translateX(5px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

.info-list .info-item i {
    font-size: 1.5rem;
    color: var(--color-primary);
    margin-top: 0.25rem;
    min-width: 24px;
}

.info-list .info-item > div {
    display: flex;
    flex-direction: column;
    flex: 1;
}

.info-label {
    font-size: 0.875rem;
    color: var(--color-gray);
    font-weight: 600;
    margin-bottom: 0.25rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.info-value {
    font-weight: 600;
    font-size: 1rem;
    color: var(--color-dark-gray);
}

/* Calendar Widget */
.calendar-widget {
    background: linear-gradient(135deg, #fff5e6 0%, #ffe6cc 100%);
    border-left: 5px solid #ff9800;
}

.calendar-widget .widget-title {
    color: #f57c00;
    border-color: #ff9800;
}

.calendar-widget p {
    margin-bottom: 1.5rem;
    color: var(--color-dark-gray);
    line-height: 1.6;
}

/* CTA Widget */
.cta-widget {
    background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
    border-left: 5px solid #2196f3;
    text-align: center;
}

.cta-widget h3 {
    font-size: 1.5rem;
    color: #1976d2;
    margin-bottom: 1rem;
}

.cta-widget p {
    color: var(--color-dark-gray);
    margin-bottom: 1.5rem;
    line-height: 1.6;
}

/* Button Styles */
.btn-block {
    display: block;
    width: 100%;
    text-align: center;
}

.btn-outline-primary {
    background: transparent;
    color: var(--color-primary);
    border: 2px solid var(--color-primary);
}

.btn-outline-primary:hover {
    background: var(--color-primary);
    color: var(--color-white);
}

/* Responsive */
@media (max-width: 768px) {
    .event-detail-card {
        padding: 1.5rem;
    }
    
    .event-header h1 {
        font-size: 1.75rem;
    }
    
    .event-section h2 {
        font-size: 1.5rem;
    }
    
    .event-share {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .sidebar-widget {
        padding: 1.5rem;
    }
    
    .widget-title {
        font-size: 1.25rem;
    }
    
    .info-list .info-item {
        padding: 0.75rem;
    }
}
</style>
