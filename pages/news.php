<?php
/**
 * MARIANCONNECT - News Listing Page
 * Displays all published news articles with pagination and filtering
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

// Pagination settings
$items_per_page = 9;
$current_page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($current_page - 1) * $items_per_page;

// Category filter
$category_filter = isset($_GET['category']) ? sanitize($_GET['category']) : '';

// Build query
$where_conditions = ["n.status = 'published'"];
$params = [];

if (!empty($category_filter)) {
    $where_conditions[] = "n.category = ?";
    $params[] = $category_filter;
}

$where_clause = implode(' AND ', $where_conditions);

// Get total count
try {
    $count_sql = "SELECT COUNT(*) as total FROM news n WHERE " . $where_clause;
    $count_stmt = $db->prepare($count_sql);
    $count_stmt->execute($params);
    $total_items = $count_stmt->fetch()['total'];
    $total_pages = ceil($total_items / $items_per_page);
} catch (Exception $e) {
    error_log("Count query error: " . $e->getMessage());
    $total_items = 0;
    $total_pages = 1;
}

// Fetch news articles
try {
    $sql = "
        SELECT n.*, a.full_name as author_name 
        FROM news n
        JOIN admin_users a ON n.author_id = a.admin_id
        WHERE " . $where_clause . "
        ORDER BY n.published_date DESC
        LIMIT {$items_per_page} OFFSET {$offset}
    ";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $news_list = $stmt->fetchAll();
} catch (Exception $e) {
    error_log("News query error: " . $e->getMessage());
    $news_list = [];
}

// Get featured news for sidebar
try {
    $featured_stmt = $db->query("
        SELECT n.*, a.full_name as author_name 
        FROM news n
        JOIN admin_users a ON n.author_id = a.admin_id
        WHERE n.status = 'published' AND n.is_featured = 1
        ORDER BY n.published_date DESC
        LIMIT 3
    ");
    $featured_news = $featured_stmt->fetchAll();
} catch (Exception $e) {
    $featured_news = [];
}

// Get categories
$categories = ['academic', 'sports', 'events', 'achievements', 'general'];

$pageTitle = 'News & Updates - ' . SITE_NAME;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    
    <meta name="description" content="Stay updated with the latest news and announcements from St. Mary's College of Catbalogan">
    <meta name="keywords" content="SMCC News, Updates, Announcements, Events">
    
    <link rel="icon" type="image/x-icon" href="<?php echo asset('images/logo/favicon.ico'); ?>">
    
    <!-- Main Stylesheets -->
    <link rel="stylesheet" href="<?php echo asset('css/main.css'); ?>">
    <link rel="stylesheet" href="<?php echo asset('css/responsive.css'); ?>">
    
    <!--  Component CSS Files  -->
    <link rel="stylesheet" href="<?php echo asset('css/components/navbar.css'); ?>">
    <link rel="stylesheet" href="<?php echo asset('css/components/footer.css'); ?>">
    <link rel="stylesheet" href="<?php echo asset('css/components/cards.css'); ?>">
    <link rel="stylesheet" href="<?php echo asset('css/components/forms.css'); ?>">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&family=Open+Sans:wght@300;400;600&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- AOS Animation Library -->
    <link rel="stylesheet" href="https://unpkg.com/aos@2.3.1/dist/aos.css">
</head>
<body>
    
    <?php include BASE_PATH . '/includes/header.php'; ?>
    
    <!-- Page Header -->
    <section class="page-header">
        <div class="page-header-overlay"></div>
        <div class="container">
            <div class="page-header-content" data-aos="fade-up">
                <h1 class="page-title">News & Updates</h1>
                <nav class="breadcrumb">
                    <a href="<?php echo url(); ?>">Home</a>
                    <span class="separator">/</span>
                    <span class="current">News</span>
                </nav>
            </div>
        </div>
    </section>
    
    <!-- Main Content -->
    <section class="page-content section-padding">
        <div class="container">
            <div class="row">
                <!-- Main Content Area -->
                <div class="col-md-8">
                    <!-- Category Filter -->
                    <div class="filter-bar" data-aos="fade-up">
                        <div class="filter-label">Filter by Category:</div>
                        <div class="filter-buttons">
                            <a href="?page=1" class="filter-btn <?php echo empty($category_filter) ? 'active' : ''; ?>">All</a>
                            <?php foreach ($categories as $cat): ?>
                                <a href="?category=<?php echo $cat; ?>&page=1" class="filter-btn <?php echo $category_filter === $cat ? 'active' : ''; ?>">
                                    <?php echo ucfirst($cat); ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <!-- News Grid (Using card component from cards.css) -->
                    <?php if (!empty($news_list)): ?>
                        <div class="card-grid">
                            <?php foreach ($news_list as $index => $news): ?>
                                <article class="news-card" data-aos="fade-up" data-aos-delay="<?php echo ($index % 3) * 100; ?>">
                                    <div class="card-image news-image">
                                        <?php if (!empty($news['featured_image'])): ?>
                                            <img src="<?php echo getImageUrl($news['featured_image']); ?>"
                                                 alt="<?php echo htmlspecialchars($news['title']); ?>"
                                                 onerror="this.src='https://via.placeholder.com/400x300/003f87/ffffff?text=News'">
                                        <?php else: ?>
                                            <img src="https://via.placeholder.com/400x300/003f87/ffffff?text=News" 
                                                 alt="<?php echo htmlspecialchars($news['title']); ?>">
                                        <?php endif; ?>
                                        <span class="news-card-badge category-<?php echo $news['category']; ?>">
                                            <?php echo htmlspecialchars(ucfirst($news['category'])); ?>
                                        </span>
                                    </div>
                                    <div class="card-body news-content">
                                        <div class="news-card-meta">
                                            <span class="news-card-meta-item">
                                                <i class="fas fa-calendar"></i> 
                                                <?php echo formatDate($news['published_date'] ?? $news['created_at']); ?>
                                            </span>
                                            <span class="news-card-meta-item">
                                                <i class="fas fa-user"></i> 
                                                <?php echo htmlspecialchars($news['author_name']); ?>
                                            </span>
                                        </div>
                                        <h3 class="card-title news-title">
                                            <a href="news-detail.php?slug=<?php echo htmlspecialchars($news['slug']); ?>">
                                                <?php echo htmlspecialchars($news['title']); ?>
                                            </a>
                                        </h3>
                                        <p class="news-card-excerpt">
                                            <?php echo htmlspecialchars($news['excerpt'] ?? truncateText(strip_tags($news['content']), 120)); ?>
                                        </p>
                                        <a href="news-detail.php?slug=<?php echo htmlspecialchars($news['slug']); ?>" class="news-card-link">
                                            Read More <i class="fas fa-arrow-right"></i>
                                        </a>
                                    </div>
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
                            <i class="fas fa-newspaper"></i>
                            <h3>No News Found</h3>
                            <p>There are currently no news articles in this category. Please check back later.</p>
                            <a href="?" class="btn btn-primary">View All News</a>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Sidebar -->
                <div class="col-md-4">
                    <!-- Featured News -->
                    <?php if (!empty($featured_news)): ?>
                        <div class="sidebar-widget" data-aos="fade-up">
                            <h3 class="widget-title">Featured News</h3>
                            <div class="featured-list">
                                <?php foreach ($featured_news as $featured): ?>
                                    <article class="featured-item">
                                        <?php if (!empty($featured['featured_image'])): ?>
                                            <div class="featured-image">
                                                <img src="<?php echo htmlspecialchars(getImageUrl($featured['featured_image'])); ?>"
                                                     alt="<?php echo htmlspecialchars($featured['title']); ?>"
                                                     onerror="this.src='https://via.placeholder.com/100x80/003f87/ffffff?text=News'">
                                            </div>
                                        <?php endif; ?>
                                        <div class="featured-content">
                                            <h4>
                                                <a href="news-detail.php?slug=<?php echo htmlspecialchars($featured['slug']); ?>">
                                                    <?php echo htmlspecialchars($featured['title']); ?>
                                                </a>
                                            </h4>
                                            <span class="featured-date">
                                                <i class="fas fa-calendar"></i> 
                                                <?php echo formatDate($featured['published_date'] ?? $featured['created_at']); ?>
                                            </span>
                                        </div>
                                    </article>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Categories Widget -->
                    <div class="sidebar-widget" data-aos="fade-up" data-aos-delay="100">
                        <h3 class="widget-title">Categories</h3>
                        <ul class="category-list">
                            <li><a href="?page=1">All Categories</a></li>
                            <?php foreach ($categories as $cat): ?>
                                <li class="<?php echo $category_filter === $cat ? 'active' : ''; ?>">
                                    <a href="?category=<?php echo $cat; ?>&page=1">
                                        <?php echo ucfirst($cat); ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    
                    <!-- Subscribe CTA -->
                    <div class="sidebar-widget cta-widget" data-aos="fade-up" data-aos-delay="200">
                        <h3>Stay Updated</h3>
                        <p>Get the latest news and updates from SMCC delivered to your inbox.</p>
                        <a href="<?php echo url('pages/contact.php'); ?>" class="btn btn-primary btn-block">Subscribe Now</a>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <?php include BASE_PATH . '/includes/footer.php'; ?>
    
    <!-- AOS Animation Script -->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init({
            duration: 800,
            once: true,
            offset: 50
        });
    </script>
</body>
</html>

<!-- Page-Specific Styles (Keep these for custom styling not in components) -->
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

/* Pagination */
.pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 0.5rem;
    flex-wrap: wrap;
    margin-top: 3rem;
}

.page-link {
    padding: 0.75rem 1rem;
    border: 2px solid var(--color-light-gray);
    border-radius: var(--border-radius-md);
    color: var(--color-dark-gray);
    font-weight: 500;
    transition: all var(--transition-base);
    min-width: 45px;
    text-align: center;
    text-decoration: none;
}

.page-link:hover,
.page-link.active {
    background-color: var(--color-primary);
    border-color: var(--color-primary);
    color: var(--color-white);
}

.page-link.active {
    cursor: default;
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

/* Featured News */
.featured-list {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.featured-item {
    display: flex;
    gap: 1rem;
}

.featured-image {
    flex-shrink: 0;
    width: 100px;
    height: 80px;
    border-radius: var(--border-radius-md);
    overflow: hidden;
}

.featured-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.featured-content h4 {
    font-size: 0.95rem;
    margin-bottom: 0.5rem;
    line-height: 1.4;
}

.featured-content h4 a {
    color: var(--color-dark-gray);
    text-decoration: none;
    transition: color var(--transition-base);
}

.featured-content h4 a:hover {
    color: var(--color-primary);
}

.featured-date {
    font-size: 0.875rem;
    color: var(--color-gray);
}

.featured-date i {
    margin-right: 0.25rem;
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

/* Responsive */
@media (max-width: 768px) {
    .filter-bar {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .filter-buttons {
        width: 100%;
    }
    
    .filter-btn {
        flex: 1;
        text-align: center;
    }
    
    .card-grid {
        grid-template-columns: 1fr;
    }
}
</style>
