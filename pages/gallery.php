<?php
/**
 * MARIANCONNECT - Gallery Page
 * Displays photo gallery with categories
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

// Filter by category
$category_filter = isset($_GET['category']) ? sanitize($_GET['category']) : '';

// Build query
$where_conditions = [];
$params = [];

if (!empty($category_filter)) {
    $where_conditions[] = "category = ?";
    $params[] = $category_filter;
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get total count
try {
    $count_sql = "SELECT COUNT(*) as total FROM gallery $where_clause";
    $count_stmt = $db->prepare($count_sql);
    $count_stmt->execute($params);
    $total_items = $count_stmt->fetch()['total'];
    $total_pages = ceil($total_items / $items_per_page);
} catch (Exception $e) {
    error_log("Count query error: " . $e->getMessage());
    $total_items = 0;
    $total_pages = 1;
}

// Fetch gallery items
try {
    $sql = "
        SELECT * FROM gallery
        $where_clause
        ORDER BY display_order ASC, created_at DESC
        LIMIT $items_per_page OFFSET $offset
    ";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $gallery_items = $stmt->fetchAll();

    // Fix image paths for all gallery items
    foreach ($gallery_items as &$item) {
        if (!empty($item['image_path'])) {
            $item['image_path'] = asset($item['image_path']);
        }
        if (!empty($item['thumbnail_path'])) {
            $item['thumbnail_path'] = asset($item['thumbnail_path']);
        }
    }
    unset($item); // Break reference
} catch (Exception $e) {
    error_log("Gallery query error: " . $e->getMessage());
    $gallery_items = [];
}

// Categories
$categories = [
    'campus' => ['icon' => 'fa-university', 'label' => 'Campus'],
    'events' => ['icon' => 'fa-calendar', 'label' => 'Events'],
    'facilities' => ['icon' => 'fa-building', 'label' => 'Facilities'],
    'students' => ['icon' => 'fa-users', 'label' => 'Students'],
    'achievements' => ['icon' => 'fa-trophy', 'label' => 'Achievements'],
    'other' => ['icon' => 'fa-images', 'label' => 'Other']
];

$pageTitle = 'Gallery - ' . SITE_NAME;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    
    <meta name="description" content="Browse through photos of campus life, events, and activities at St. Mary's College of Catbalogan">
    <meta name="keywords" content="SMCC Gallery, Campus Photos, Events, Activities">
    
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
    
    <!-- Lightbox CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.3/css/lightbox.min.css">
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
                <h1 class="page-title">Photo Gallery</h1>
                <nav class="breadcrumb">
                    <a href="<?php echo url(); ?>">Home</a>
                    <span class="separator">/</span>
                    <span class="current">Gallery</span>
                </nav>
            </div>
        </div>
    </section>
    
    <!-- Intro Section -->
    <section class="gallery-intro section-padding">
        <div class="container">
            <div class="intro-content text-center" data-aos="fade-up">
                <h2>Capturing Our Moments</h2>
                <p class="lead">Explore the vibrant life at SMCC through our photo gallery. From academic events to campus activities, discover what makes our community special.</p>
            </div>
        </div>
    </section>
    
    <!-- Category Filter -->
    <section class="filter-section section-padding bg-light">
        <div class="container">
            <div class="category-filter" data-aos="fade-up">
                <a href="?page=1" class="filter-btn <?php echo empty($category_filter) ? 'active' : ''; ?>">
                    <i class="fas fa-th"></i>
                    <span>All Photos</span>
                </a>
                <?php foreach ($categories as $cat_key => $cat_info): ?>
                    <a href="?category=<?php echo $cat_key; ?>&page=1" class="filter-btn <?php echo $category_filter === $cat_key ? 'active' : ''; ?>">
                        <i class="fas <?php echo $cat_info['icon']; ?>"></i>
                        <span><?php echo $cat_info['label']; ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    
    <!-- Gallery Grid -->
    <section class="gallery-content section-padding">
        <div class="container">
            <?php if (!empty($gallery_items)): ?>
                <div class="gallery-grid">
                    <?php foreach ($gallery_items as $index => $item): ?>
                        <div class="gallery-item" data-aos="fade-up" data-aos-delay="<?php echo ($index % 8) * 100; ?>">
                            <a href="<?php echo htmlspecialchars($item['image_path']); ?>" 
                               data-lightbox="gallery" 
                               data-title="<?php echo htmlspecialchars($item['title']); ?>">
                                <div class="gallery-image">
                                    <?php if (!empty($item['thumbnail_path'])): ?>
                                        <img src="<?php echo htmlspecialchars($item['thumbnail_path']); ?>" 
                                             alt="<?php echo htmlspecialchars($item['title']); ?>"
                                             onerror="this.src='<?php echo htmlspecialchars($item['image_path']); ?>'">
                                    <?php else: ?>
                                        <img src="<?php echo htmlspecialchars($item['image_path']); ?>" 
                                             alt="<?php echo htmlspecialchars($item['title']); ?>"
                                             onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22400%22 height=%22300%22%3E%3Crect fill=%22%23003f87%22 width=%22400%22 height=%22300%22/%3E%3Ctext fill=%22%23ffffff%22 font-family=%22Arial%22 font-size=%2224%22 x=%2250%25%22 y=%2250%25%22 text-anchor=%22middle%22 dominant-baseline=%22middle%22%3EImage%3C/text%3E%3C/svg%3E'">
                                    <?php endif; ?>
                                    <div class="gallery-overlay">
                                        <div class="overlay-content">
                                            <i class="fas fa-search-plus"></i>
                                            <h4><?php echo htmlspecialchars($item['title']); ?></h4>
                                            <?php if (!empty($item['description'])): ?>
                                                <p><?php echo htmlspecialchars(truncateText($item['description'], 60)); ?></p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </div>
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
                    <i class="fas fa-images"></i>
                    <h3>No Photos Found</h3>
                    <p>There are currently no photos in this category. Check back soon!</p>
                    <a href="?" class="btn btn-primary">View All Photos</a>
                </div>
            <?php endif; ?>
        </div>
    </section>
    
    <?php 
    $footer_path = BASE_PATH . '/includes/footer.php';
    if (file_exists($footer_path)) {
        include $footer_path;
    }
    ?>
    
    <!-- jQuery (required for Lightbox) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <!-- Lightbox JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.3/js/lightbox.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init({
            duration: 800,
            once: true
        });
        
        // Lightbox configuration
        lightbox.option({
            'resizeDuration': 200,
            'wrapAround': true,
            'albumLabel': "Image %1 of %2"
        });
    </script>
</body>
</html>

<style>
    /* Page Header Styles */
.page-header {
    position: relative;
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
/* Gallery Intro */
.gallery-intro {
    padding-bottom: 0;
}

.intro-content h2 {
    font-size: 2.5rem;
    margin-bottom: 1rem;
    color: var(--color-primary);
}

.intro-content .lead {
    font-size: 1.25rem;
    color: var(--color-gray);
    max-width: 900px;
    margin: 0 auto;
    padding-bottom: 2rem;
}

/* Category Filter */
.category-filter {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 1rem;
}

.filter-btn {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
    padding: 1.25rem 1rem;
    background: var(--color-white);
    border: 2px solid var(--color-light-gray);
    border-radius: var(--border-radius-lg);
    color: var(--color-dark-gray);
    font-weight: 600;
    transition: all var(--transition-base);
    text-align: center;
}

.filter-btn i {
    font-size: 1.75rem;
    color: var(--color-primary);
}

.filter-btn:hover,
.filter-btn.active {
    background: var(--color-primary);
    border-color: var(--color-primary);
    color: var(--color-white);
    transform: translateY(-5px);
    box-shadow: var(--shadow-lg);
}

.filter-btn:hover i,
.filter-btn.active i {
    color: var(--color-white);
}

/* Lightbox Close Button Override */
/* Hide the default close button at the bottom */
.lb-data .lb-close {
    display: none !important;
}

/* Create new close button at top-right of the overlay */
.lightbox::after {
    content: '\f00d';
    font-family: 'Font Awesome 6 Free';
    font-weight: 900;
    position: fixed;
    top: 20px;
    right: 20px;
    width: 50px;
    height: 50px;
    background: rgba(0, 0, 0, 0.8);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 24px;
    cursor: pointer;
    z-index: 9999;
    transition: all 0.3s ease;
}

.lightbox::after:hover {
    background: var(--color-primary);
    transform: scale(1.1);
}

/* Alternative: Move the existing close button to top-right of image container */
.lb-outerContainer {
    position: relative !important;
}

.lb-container .lb-close {
    position: absolute !important;
    top: -50px !important;
    right: 0 !important;
    width: 50px !important;
    height: 50px !important;
    background: rgba(0, 0, 0, 0.8) !important;
    border-radius: 50% !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    opacity: 1 !important;
    transition: all 0.3s ease !important;
    text-indent: 0 !important;
    z-index: 10000 !important;
}

.lb-container .lb-close::before {
    content: '\f00d';
    font-family: 'Font Awesome 6 Free';
    font-weight: 900;
    color: white;
    font-size: 24px;
}

.lb-container .lb-close:hover {
    background: var(--color-primary) !important;
    transform: scale(1.1) !important;
}

/* Gallery Grid */
.gallery-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 1.5rem;
    margin-bottom: 3rem;
}

.gallery-item {
    position: relative;
    border-radius: var(--border-radius-lg);
    overflow: hidden;
    box-shadow: var(--shadow-md);
    transition: all var(--transition-base);
}

.gallery-item:hover {
    transform: translateY(-5px);
    box-shadow: var(--shadow-xl);
}

.gallery-image {
    position: relative;
    height: 250px;
    overflow: hidden;
}

.gallery-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform var(--transition-slow);
}

.gallery-item:hover .gallery-image img {
    transform: scale(1.1);
}

.gallery-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 63, 135, 0.95);
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: opacity var(--transition-base);
    padding: 1.5rem;
}

.gallery-item:hover .gallery-overlay {
    opacity: 1;
}

.overlay-content {
    text-align: center;
    color: var(--color-white);
}

.overlay-content i {
    font-size: 2.5rem;
    margin-bottom: 1rem;
}

.overlay-content h4 {
    font-size: 1.125rem;
    margin-bottom: 0.5rem;
    color: var(--color-white);
}

.overlay-content p {
    font-size: 0.9375rem;
    opacity: 0.9;
    margin: 0;
}

/* Pagination */
.pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 0.5rem;
    flex-wrap: wrap;
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
}

.no-results p {
    color: var(--color-gray);
    margin-bottom: 2rem;
}

/* Responsive */
@media (max-width: 768px) {
    .intro-content h2 {
        font-size: 1.75rem;
    }
    
    .category-filter {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .gallery-grid {
        grid-template-columns: 1fr;
    }
}
</style>
