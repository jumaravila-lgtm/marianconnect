<?php
/**
 * MARIANCONNECT - Achievements Page
 * Displays awards, recognitions, and accomplishments
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
    $count_sql = "SELECT COUNT(*) as total FROM achievements $where_clause";
    $count_stmt = $db->prepare($count_sql);
    $count_stmt->execute($params);
    $total_items = $count_stmt->fetch()['total'];
    $total_pages = ceil($total_items / $items_per_page);
} catch (Exception $e) {
    error_log("Count query error: " . $e->getMessage());
    $total_items = 0;
    $total_pages = 1;
}

// Fetch achievements
try {
    $sql = "
        SELECT * FROM achievements
        $where_clause
        ORDER BY achievement_date DESC
        LIMIT $items_per_page OFFSET $offset
    ";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $achievements = $stmt->fetchAll();
    // Fix image paths for all achievements
    foreach ($achievements as &$achievement) {
        if (!empty($achievement['featured_image'])) {
            $achievement['featured_image'] = asset($achievement['featured_image']);
        }
    }
    unset($achievement); // Break reference

} catch (Exception $e) {
    error_log("Achievements query error: " . $e->getMessage());
    $achievements = [];
}


// Categories
$categories = [
    'academic' => ['icon' => 'fa-book', 'label' => 'Academic'],
    'sports' => ['icon' => 'fa-trophy', 'label' => 'Sports'],
    'cultural' => ['icon' => 'fa-theater-masks', 'label' => 'Cultural'],
    'community_service' => ['icon' => 'fa-hands-helping', 'label' => 'Community Service'],
    'research' => ['icon' => 'fa-microscope', 'label' => 'Research'],
    'other' => ['icon' => 'fa-medal', 'label' => 'Other']
];

// Award levels
$award_levels = [
    'local' => 'Local',
    'regional' => 'Regional',
    'national' => 'National',
    'international' => 'International'
];

$pageTitle = 'Achievements - ' . SITE_NAME;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    
    <meta name="description" content="Celebrate the achievements and recognitions of St. Mary's College of Catbalogan">
    <meta name="keywords" content="SMCC Achievements, Awards, Recognitions, Accomplishments">
    
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
                <h1 class="page-title">Achievements & Awards</h1>
                <nav class="breadcrumb">
                    <a href="<?php echo url(); ?>">Home</a>
                    <span class="separator">/</span>
                    <span class="current">Achievements</span>
                </nav>
            </div>
        </div>
    </section>
    
    <!-- Intro Section -->
    <section class="achievements-intro section-padding">
        <div class="container">
            <div class="intro-content text-center" data-aos="fade-up">
                <h2>Celebrating Excellence</h2>
                <p class="lead">Recognizing the outstanding accomplishments of our students, faculty, and institution in various fields and competitions.</p>
            </div>
        </div>
    </section>
    
    <!-- Category Filter -->
    <section class="filter-section section-padding bg-light">
        <div class="container">
            <div class="category-filter" data-aos="fade-up">
                <a href="?page=1" class="filter-btn <?php echo empty($category_filter) ? 'active' : ''; ?>">
                    <i class="fas fa-th"></i> All Categories
                </a>
                <?php foreach ($categories as $cat_key => $cat_info): ?>
                    <a href="?category=<?php echo $cat_key; ?>&page=1" class="filter-btn <?php echo $category_filter === $cat_key ? 'active' : ''; ?>">
                        <i class="fas <?php echo $cat_info['icon']; ?>"></i> <?php echo $cat_info['label']; ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    
    <!-- Achievements Grid -->
    <section class="achievements-content section-padding">
        <div class="container">
            <?php if (!empty($achievements)): ?>
                <div class="achievements-grid">
                    <?php foreach ($achievements as $index => $achievement): ?>
                        <div class="achievement-card" data-aos="fade-up" data-aos-delay="<?php echo $index * 100; ?>">
                            <?php if (!empty($achievement['featured_image'])): ?>
                                <div class="achievement-image">
                                    <img src="<?php echo htmlspecialchars($achievement['featured_image']); ?>" 
                                         alt="<?php echo htmlspecialchars($achievement['title']); ?>"
                                         onerror="this.src='https://via.placeholder.com/400x300/003f87/ffffff?text=Achievement'">
                                </div>
                            <?php endif; ?>
                            
                            <div class="achievement-content">
                                <div class="achievement-badges">
                                    <span class="category-badge">
                                        <i class="fas <?php echo $categories[$achievement['category']]['icon']; ?>"></i>
                                        <?php echo $categories[$achievement['category']]['label']; ?>
                                    </span>
                                    <span class="level-badge level-<?php echo $achievement['award_level']; ?>">
                                        <i class="fas fa-award"></i>
                                        <?php echo htmlspecialchars($award_levels[$achievement['award_level']] ?? ucfirst($achievement['award_level'])); ?>
                                    </span>
                                </div>
                                
                                <h3><?php echo htmlspecialchars($achievement['title']); ?></h3>
                                
                                <div class="achievement-meta">
                                    <div class="meta-item">
                                        <i class="fas fa-calendar"></i>
                                        <?php echo formatDate($achievement['achievement_date']); ?>
                                    </div>
                                    
                                    <?php if (!empty($achievement['recipient_name'])): ?>
                                        <div class="meta-item">
                                            <i class="fas fa-user"></i>
                                            <?php echo htmlspecialchars($achievement['recipient_name']); ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="meta-item">
                                        <i class="fas fa-tag"></i>
                                        <?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $achievement['recipient_type']))); ?>
                                    </div>
                                </div>
                                
                                <p class="achievement-description">
                                    <?php echo htmlspecialchars($achievement['description']); ?>
                                </p>
                            </div>
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
                    <i class="fas fa-trophy"></i>
                    <h3>No Achievements Found</h3>
                    <p>There are currently no achievements in this category.</p>
                    <a href="?" class="btn btn-primary">View All Achievements</a>
                </div>
            <?php endif; ?>
        </div>
    </section>
    
    <!-- Stats Section -->
    <section class="achievements-stats section-padding bg-light">
        <div class="container">
            <div class="section-header text-center" data-aos="fade-up">
                <h2 class="section-title">Our Track Record</h2>
                <p class="section-subtitle">A testament to our commitment to excellence</p>
            </div>
            
            <div class="row g-4">
                <div class="col-md-3" data-aos="fade-up">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-trophy"></i>
                        </div>
                        <div class="stat-number">100+</div>
                        <div class="stat-label">Awards & Recognitions</div>
                    </div>
                </div>
                <div class="col-md-3" data-aos="fade-up" data-aos-delay="100">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-globe"></i>
                        </div>
                        <div class="stat-number">20+</div>
                        <div class="stat-label">International Achievements</div>
                    </div>
                </div>
                <div class="col-md-3" data-aos="fade-up" data-aos-delay="200">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-flag"></i>
                        </div>
                        <div class="stat-number">50+</div>
                        <div class="stat-label">National Championships</div>
                    </div>
                </div>
                <div class="col-md-3" data-aos="fade-up" data-aos-delay="300">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-medal"></i>
                        </div>
                        <div class="stat-number">200+</div>
                        <div class="stat-label">Student Awardees</div>
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
/* Achievements Intro */
.achievements-intro {
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
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
    justify-content: center;
}

.filter-btn {
    padding: 0.875rem 1.5rem;
    background: var(--color-white);
    border: 2px solid var(--color-light-gray);
    border-radius: var(--border-radius-lg);
    color: var(--color-dark-gray);
    font-weight: 600;
    transition: all var(--transition-base);
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.filter-btn:hover,
.filter-btn.active {
    background: var(--color-primary);
    border-color: var(--color-primary);
    color: var(--color-white);
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
}

/* Achievements Grid */
.achievements-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 2rem;
    margin-bottom: 3rem;
}

.achievement-card {
    background: var(--color-white);
    border-radius: var(--border-radius-lg);
    overflow: hidden;
    box-shadow: var(--shadow-md);
    transition: all var(--transition-base);
}

.achievement-card:hover {
    transform: translateY(-10px);
    box-shadow: var(--shadow-xl);
}

.achievement-image {
    height: 200px;
    overflow: hidden;
}

.achievement-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform var(--transition-slow);
}

.achievement-card:hover .achievement-image img {
    transform: scale(1.1);
}

.achievement-content {
    padding: 1.5rem;
}

.achievement-badges {
    display: flex;
    gap: 0.75rem;
    margin-bottom: 1rem;
    flex-wrap: wrap;
}

.category-badge,
.level-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.375rem 0.875rem;
    border-radius: var(--border-radius-md);
    font-size: 0.8125rem;
    font-weight: 700;
    text-transform: uppercase;
}

.category-badge {
    background: var(--color-secondary);
    color: var(--color-dark-gray);
}

.level-badge {
    background: var(--color-primary);
    color: var(--color-white);
}

.level-badge.level-international {
    background: linear-gradient(135deg, #FFD700, #FFA500);
    color: var(--color-dark-gray);
}

.level-badge.level-national {
    background: linear-gradient(135deg, #4169E1, #1E90FF);
}

.level-badge.level-regional {
    background: linear-gradient(135deg, #32CD32, #228B22);
}

.level-badge.level-local {
    background: var(--color-gray);
}

.achievement-content h3 {
    font-size: 1.375rem;
    margin-bottom: 1rem;
    color: var(--color-primary);
    line-height: 1.4;
}

.achievement-meta {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    margin-bottom: 1rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid var(--color-light-gray);
}

.meta-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.9375rem;
    color: var(--color-gray);
}

.meta-item i {
    color: var(--color-primary);
    width: 16px;
}

.achievement-description {
    color: var(--color-gray);
    line-height: 1.6;
}
/* No Results */
.no-results {
    text-align: center;
    padding: 4rem 2rem;
    margin: 0 auto;
}

.no-results i {
    font-size: 4rem;
    color: var(--color-light-gray);
    margin-bottom: 1.5rem;
    display: block;
}

.no-results h3 {
    font-size: 1.75rem;
    margin-bottom: 1rem;
    color: var(--color-primary);
}

.no-results p {
    color: var(--color-gray);
    margin-bottom: 2rem;
    font-size: 1rem;
}

.no-results .btn {
    display: inline-block;
}

/* Stats Section */
.stat-card {
    background: var(--color-white);
    padding: 2.5rem 2rem;
    border-radius: var(--border-radius-lg);
    box-shadow: var(--shadow-md);
    text-align: center;
    transition: all var(--transition-base);
}

.stat-card:hover {
    transform: translateY(-10px);
    box-shadow: var(--shadow-xl);
}

.stat-icon {
    width: 80px;
    height: 80px;
    margin: 0 auto 1.5rem;
    background: linear-gradient(135deg, var(--color-primary), var(--color-primary-light));
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2.5rem;
    color: var(--color-white);
}

.stat-number {
    font-size: 3rem;
    font-weight: 700;
    font-family: var(--font-primary);
    color: var(--color-primary);
    margin-bottom: 0.5rem;
    line-height: 1;
}

.stat-label {
    font-size: 1.125rem;
    color: var(--color-gray);
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

/* Responsive */
@media (max-width: 768px) {
    .intro-content h2 {
        font-size: 1.75rem;
    }
    
    .category-filter {
        flex-direction: column;
    }
    
    .filter-btn {
        width: 100%;
        justify-content: center;
    }
    
    .achievements-grid {
        grid-template-columns: 1fr;
    }
    
    .stat-number {
        font-size: 2.5rem;
    }
}
</style>
