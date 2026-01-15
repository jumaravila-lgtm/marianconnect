<?php
/**
 * MARIANCONNECT - Facilities Page
 * Displays school facilities and amenities
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

// Filter by category
$category_filter = isset($_GET['category']) ? sanitize($_GET['category']) : '';

// Build query
$where_conditions = ["is_available = 1"];
$params = [];

if (!empty($category_filter)) {
    $where_conditions[] = "category = ?";
    $params[] = $category_filter;
}

$where_clause = 'WHERE ' . implode(' AND ', $where_conditions);

// Fetch facilities
try {
    $sql = "
        SELECT * FROM facilities
        $where_clause
        ORDER BY display_order ASC, name ASC
    ";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $facilities = $stmt->fetchAll();
    // Fix image paths for all facilities
    foreach ($facilities as &$facility) {
        if (!empty($facility['featured_image'])) {
            $facility['featured_image'] = asset($facility['featured_image']);
        }
    }
    unset($facility); // Break reference

} catch (Exception $e) {
    error_log("Facilities query error: " . $e->getMessage());
    $facilities = [];
}

// Categories
$categories = [
    'classroom' => ['icon' => 'fa-chalkboard', 'label' => 'Classrooms'],
    'laboratory' => ['icon' => 'fa-flask', 'label' => 'Laboratories'],
    'library' => ['icon' => 'fa-book', 'label' => 'Library'],
    'sports' => ['icon' => 'fa-basketball-ball', 'label' => 'Sports Facilities'],
    'chapel' => ['icon' => 'fa-church', 'label' => 'Chapel'],
    'office' => ['icon' => 'fa-building', 'label' => 'Offices'],
    'other' => ['icon' => 'fa-landmark', 'label' => 'Other Facilities']
];

$pageTitle = 'Facilities - ' . SITE_NAME;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    
    <meta name="description" content="Explore the modern facilities and amenities at St. Mary's College of Catbalogan">
    <meta name="keywords" content="SMCC Facilities, Campus, Classrooms, Laboratories, Library, Sports">
    
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
    <section class="page-header">
        <div class="page-header-overlay"></div>
        <div class="container">
            <div class="page-header-content" data-aos="fade-up">
                <h1 class="page-title">Campus Facilities</h1>
                <nav class="breadcrumb">
                    <a href="<?php echo url(); ?>">Home</a>
                    <span class="separator">/</span>
                    <span class="current">Facilities</span>
                </nav>
            </div>
        </div>
    </section>
    
    <!-- Intro Section -->
    <section class="facilities-intro section-padding">
        <div class="container">
            <div class="intro-content text-center" data-aos="fade-up">
                <h2>World-Class Facilities for Holistic Development</h2>
                <p class="lead">St. Mary's College of Catbalogan provides modern, well-equipped facilities that support academic excellence, physical fitness, spiritual growth, and overall student development.</p>
            </div>
        </div>
    </section>
    
    <!-- Category Filter -->
    <section class="filter-section section-padding bg-light">
        <div class="container">
            <div class="category-filter" data-aos="fade-up">
                <a href="?" class="category-btn <?php echo empty($category_filter) ? 'active' : ''; ?>">
                    <i class="fas fa-th"></i>
                    <span>All Facilities</span>
                </a>
                <?php foreach ($categories as $cat_key => $cat_info): ?>
                    <a href="?category=<?php echo $cat_key; ?>" class="category-btn <?php echo $category_filter === $cat_key ? 'active' : ''; ?>">
                        <i class="fas <?php echo $cat_info['icon']; ?>"></i>
                        <span><?php echo $cat_info['label']; ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    
    <!-- Facilities Grid -->
    <section class="facilities-content section-padding">
        <div class="container">
            <?php if (!empty($facilities)): ?>
                <div class="facilities-grid">
                    <?php foreach ($facilities as $index => $facility): ?>
                        <div class="facility-card" data-aos="fade-up" data-aos-delay="<?php echo $index * 100; ?>">
                            <div class="facility-image">
                                <?php if (!empty($facility['featured_image'])): ?>
                                    <img src="<?php echo htmlspecialchars($facility['featured_image']); ?>" 
                                         alt="<?php echo htmlspecialchars($facility['name']); ?>"
                                         onerror="this.src='https://via.placeholder.com/400x300/003f87/ffffff?text=Facility'">
                                <?php else: ?>
                                    <img src="https://via.placeholder.com/400x300/003f87/ffffff?text=<?php echo urlencode($facility['name']); ?>" 
                                         alt="<?php echo htmlspecialchars($facility['name']); ?>">
                                <?php endif; ?>
                                <div class="facility-category">
                                    <i class="fas <?php echo $categories[$facility['category']]['icon'] ?? 'fa-building'; ?>"></i>
                                </div>
                            </div>
                            
                            <div class="facility-content">
                                <h3><?php echo htmlspecialchars($facility['name']); ?></h3>
                                
                                <div class="facility-meta">
                                    <?php if (!empty($facility['location'])): ?>
                                        <span class="meta-item">
                                            <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($facility['location']); ?>
                                        </span>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($facility['capacity'])): ?>
                                        <span class="meta-item">
                                            <i class="fas fa-users"></i> Capacity: <?php echo htmlspecialchars($facility['capacity']); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                
                                <p class="facility-description">
                                    <?php echo htmlspecialchars($facility['description']); ?>
                                </p>
                                
                                <div class="facility-status">
                                    <span class="status-badge <?php echo $facility['is_available'] ? 'available' : 'unavailable'; ?>">
                                        <i class="fas <?php echo $facility['is_available'] ? 'fa-check-circle' : 'fa-times-circle'; ?>"></i>
                                        <?php echo $facility['is_available'] ? 'Available' : 'Under Maintenance'; ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="no-results" data-aos="fade-up">
                    <i class="fas fa-building"></i>
                    <h3>No Facilities Found</h3>
                    <p>There are currently no facilities in this category.</p>
                    <a href="?" class="btn btn-primary">View All Facilities</a>
                </div>
            <?php endif; ?>
        </div>
    </section>
    
    <!-- Virtual Tour CTA -->
    <section class="tour-cta-section section-padding bg-light">
        <div class="container">
            <div class="tour-cta-card" data-aos="fade-up">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h2><i class="fas fa-video"></i> Take a Virtual Tour</h2>
                        <p>Experience our campus from anywhere! Schedule a virtual tour or visit us in person to see our facilities firsthand.</p>
                    </div>
                    <div class="col-md-4 text-center">
                        <a href="contact.php" class="btn btn-primary btn-lg">
                            <i class="fas fa-calendar-alt"></i> Schedule a Visit
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Related Links -->
    <section class="related-links-section section-padding">
        <div class="container">
            <div class="section-header text-center" data-aos="fade-up">
                <h2 class="section-title">Explore More</h2>
            </div>
            
            <div class="row g-4">
                <div class="col-md-4" data-aos="fade-up">
                    <div class="link-card">
                        <div class="link-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <h3>Student Organizations</h3>
                        <p>Discover the various student clubs and organizations on campus.</p>
                        <a href="organizations.php" class="card-link">Learn More →</a>
                    </div>
                </div>
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="100">
                    <div class="link-card">
                        <div class="link-icon">
                            <i class="fas fa-trophy"></i>
                        </div>
                        <h3>Achievements</h3>
                        <p>Celebrate our students' and faculty's accomplishments.</p>
                        <a href="achievements.php" class="card-link">Learn More →</a>
                    </div>
                </div>
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="200">
                    <div class="link-card">
                        <div class="link-icon">
                            <i class="fas fa-images"></i>
                        </div>
                        <h3>Campus Gallery</h3>
                        <p>Browse through photos of campus life and events.</p>
                        <a href="gallery.php" class="card-link">Learn More →</a>
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
/* Facilities Intro */
.facilities-intro {
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
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 1rem;
}

.category-btn {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.75rem;
    padding: 1.5rem 1rem;
    background: var(--color-white);
    border: 2px solid var(--color-light-gray);
    border-radius: var(--border-radius-lg);
    color: var(--color-dark-gray);
    font-weight: 600;
    transition: all var(--transition-base);
    text-align: center;
}

.category-btn i {
    font-size: 2rem;
    color: var(--color-primary);
}

.category-btn:hover,
.category-btn.active {
    background: var(--color-primary);
    border-color: var(--color-primary);
    color: var(--color-white);
    transform: translateY(-5px);
    box-shadow: var(--shadow-lg);
}

.category-btn:hover i,
.category-btn.active i {
    color: var(--color-white);
}

/* Facilities Grid */
.facilities-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 2rem;
}

.facility-card {
    background: var(--color-white);
    border-radius: var(--border-radius-lg);
    overflow: hidden;
    box-shadow: var(--shadow-md);
    transition: all var(--transition-base);
}

.facility-card:hover {
    transform: translateY(-10px);
    box-shadow: var(--shadow-xl);
}

.facility-image {
    position: relative;
    height: 250px;
    overflow: hidden;
}

.facility-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform var(--transition-slow);
}

.facility-card:hover .facility-image img {
    transform: scale(1.1);
}

.facility-category {
    position: absolute;
    top: 1rem;
    right: 1rem;
    width: 50px;
    height: 50px;
    background: var(--color-white);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
    color: var(--color-primary);
    box-shadow: var(--shadow-md);
}

.facility-content {
    padding: 1.5rem;
}

.facility-content h3 {
    font-size: 1.5rem;
    margin-bottom: 1rem;
    color: var(--color-primary);
}

.facility-meta {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    margin-bottom: 1rem;
    font-size: 0.9375rem;
    color: var(--color-gray);
}

.meta-item i {
    color: var(--color-primary);
    margin-right: 0.25rem;
}

.facility-description {
    color: var(--color-gray);
    line-height: 1.6;
    margin-bottom: 1rem;
}

.facility-status {
    padding-top: 1rem;
    border-top: 1px solid var(--color-light-gray);
}

.status-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    border-radius: var(--border-radius-md);
    font-size: 0.875rem;
    font-weight: 600;
}

.status-badge.available {
    background: #d4edda;
    color: #155724;
}

.status-badge.unavailable {
    background: #f8d7da;
    color: #721c24;
}

/* Virtual Tour CTA */
.tour-cta-card {
    background: linear-gradient(135deg, var(--color-primary), var(--color-primary-dark));
    padding: 3rem;
    border-radius: var(--border-radius-xl);
    color: var(--color-white);
    box-shadow: var(--shadow-xl);
}

.tour-cta-card h2 {
    font-size: 2rem;
    margin-bottom: 0.5rem;
    color: var(--color-white);
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.tour-cta-card p {
    font-size: 1.125rem;
    margin-bottom: 0;
    opacity: 0.95;
}

.tour-cta-card .btn-primary {
    background-color: var(--color-white);
    color: var(--color-primary);
    border-color: var(--color-white);
}

.tour-cta-card .btn-primary:hover {
    background-color: var(--color-secondary);
    color: var(--color-dark-gray);
    border-color: var(--color-secondary);
}

/* Link Cards */
.link-card {
    background: var(--color-white);
    padding: 2rem;
    border-radius: var(--border-radius-lg);
    box-shadow: var(--shadow-md);
    text-align: center;
    height: 100%;
    transition: all var(--transition-base);
}

.link-card:hover {
    transform: translateY(-10px);
    box-shadow: var(--shadow-xl);
}

.link-icon {
    width: 80px;
    height: 80px;
    margin: 0 auto 1.5rem;
    background: linear-gradient(135deg, var(--color-primary), var(--color-primary-light));
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    color: var(--color-white);
}

.link-card h3 {
    font-size: 1.5rem;
    margin-bottom: 1rem;
}

.link-card p {
    color: var(--color-gray);
    margin-bottom: 1.5rem;
}

/* No Results - Improved Styling */
.no-results {
    max-width: 600px;
    margin: 4rem auto;
    text-align: center;
    padding: 4rem 3rem;
    /*background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border-radius: var(--border-radius-xl);
    box-shadow: var(--shadow-lg);
    border: 3px dashed var(--color-light-gray);*/
}

.no-results i {
    font-size: 5rem;
    color: var(--color-primary);
    margin-bottom: 1.5rem;
    opacity: 0.7;
}

.no-results h3 {
    font-size: 2rem;
    color: var(--color-primary);
    margin-bottom: 1rem;
    font-weight: 700;
}

.no-results p {
    font-size: 1.125rem;
    color: var(--color-gray);
    margin-bottom: 2rem;
    line-height: 1.6;
}

.no-results .btn {
    padding: 0.875rem 2rem;
    font-size: 1.0625rem;
    font-weight: 600;
    box-shadow: var(--shadow-md);
}

.no-results .btn:hover {
    transform: translateY(-3px);
    box-shadow: var(--shadow-xl);
}

/* Responsive for No Results */
@media (max-width: 768px) {
    .no-results {
        padding: 3rem 2rem;
        margin: 2rem 1rem;
    }
    
    .no-results i {
        font-size: 4rem;
    }
    
    .no-results h3 {
        font-size: 1.5rem;
    }
    
    .no-results p {
        font-size: 1rem;
    }
}
/* Responsive */
@media (max-width: 768px) {
    .intro-content h2 {
        font-size: 1.75rem;
    }
    
    .category-filter {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .facilities-grid {
        grid-template-columns: 1fr;
    }
    
    .tour-cta-card {
        padding: 2rem;
        text-align: center;
    }
    
    .tour-cta-card h2 {
        font-size: 1.5rem;
        justify-content: center;
    }
    
    .tour-cta-card .col-md-4 {
        margin-top: 1.5rem;
    }
}
</style>
