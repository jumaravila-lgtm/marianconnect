<?php
/**
 * MARIANCONNECT - About Page
 * Displays the About SMCC content from database
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

// Fetch About page content from database
try {
    $stmt = $db->prepare("
        SELECT * FROM pages 
        WHERE page_type = 'about' AND is_published = 1 
        LIMIT 1
    ");
    $stmt->execute();
    $page = $stmt->fetch();
    
    if (!$page) {
        // Default content if page not found
        $page = [
            'title' => 'About SMCC',
            'content' => '<p>St. Mary\'s College of Catbalogan is a Catholic educational institution committed to excellence in education, service, and faith formation.</p>',
            'meta_title' => 'About Us - ' . SITE_NAME,
            'meta_description' => 'Learn about St. Mary\'s College of Catbalogan, a Catholic educational institution committed to excellence.'
        ];
    }
} catch (Exception $e) {
    error_log("Database error: " . $e->getMessage());
    $page = [
        'title' => 'About SMCC',
        'content' => '<p>Content currently unavailable.</p>',
        'meta_title' => 'About Us - ' . SITE_NAME,
        'meta_description' => 'About St. Mary\'s College of Catbalogan'
    ];
}

// SEO Settings
$pageTitle = !empty($page['meta_title']) ? $page['meta_title'] : $page['title'] . ' - ' . SITE_NAME;
$metaDescription = !empty($page['meta_description']) ? $page['meta_description'] : 'Learn more about St. Mary\'s College of Catbalogan';

// Get related statistics
try {
    $stats = [
        'years_of_excellence' => 75,
        'total_students' => 3500,
        'faculty_members' => 150,
        'academic_programs' => 20
    ];
} catch (Exception $e) {
    $stats = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    
    <!-- SEO Meta Tags -->
    <meta name="description" content="<?php echo htmlspecialchars($metaDescription); ?>">
    <meta name="keywords" content="SMCC, About, St. Mary's College, Catbalogan, Catholic School">
    
    <!-- Open Graph -->
    <meta property="og:type" content="website">
    <meta property="og:title" content="<?php echo htmlspecialchars($pageTitle); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars($metaDescription); ?>">
    <meta property="og:url" content="<?php echo url('pages/about.php'); ?>">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="<?php echo asset('images/logo/favicon.ico'); ?>">
    
    <!-- Stylesheets -->
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
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- AOS Animation -->
    <link rel="stylesheet" href="https://unpkg.com/aos@2.3.1/dist/aos.css">
</head>
<body>
    
    <?php include '../includes/header.php'; ?>
    
    <!-- Page Header / Breadcrumb -->
    <section class="page-header">
        <div class="page-header-overlay"></div>
        <div class="container">
            <div class="page-header-content" data-aos="fade-up">
                <h1 class="page-title"><?php echo htmlspecialchars($page['title']); ?></h1>
                <nav class="breadcrumb">
                    <a href="<?php echo url(); ?>">Home</a>
                    <span class="separator">/</span>
                    <span class="current"><?php echo htmlspecialchars($page['title']); ?></span>
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
                    <article class="content-article" data-aos="fade-up">
                        <div class="article-body">
                            <?php echo $page['content']; ?>
                        </div>
                    </article>
                    
                    <!-- Additional Info Boxes -->
                    <div class="info-boxes" data-aos="fade-up" data-aos-delay="100">
                        <div class="row g-4">
                            <div class="col-md-6">
                                <div class="info-box">
                                    <div class="info-icon">
                                        <i class="fas fa-eye"></i>
                                    </div>
                                    <h3>Our Vision</h3>
                                    <p>To be a premier Catholic educational institution in the region.</p>
                                    <a href="<?php echo url('pages/mission-vision.php'); ?>" class="info-link">Learn More →</a>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="info-box">
                                    <div class="info-icon">
                                        <i class="fas fa-history"></i>
                                    </div>
                                    <h3>Our History</h3>
                                    <p>Discover our rich heritage and journey of excellence.</p>
                                    <a href="<?php echo url('pages/history.php'); ?>" class="info-link">Learn More →</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Sidebar -->
                <div class="col-md-4">
                    <!-- Quick Stats -->
                    <div class="sidebar-widget" data-aos="fade-up">
                        <h3 class="widget-title">Quick Facts</h3>
                        <div class="stats-list">
                            <div class="stat-item-small">
                                <div class="stat-icon">
                                    <i class="fas fa-calendar-alt"></i>
                                </div>
                                <div class="stat-content">
                                    <div class="stat-number"><?php echo $stats['years_of_excellence']; ?>+</div>
                                    <div class="stat-label">Years of Excellence</div>
                                </div>
                            </div>
                            <div class="stat-item-small">
                                <div class="stat-icon">
                                    <i class="fas fa-users"></i>
                                </div>
                                <div class="stat-content">
                                    <div class="stat-number"><?php echo number_format($stats['total_students']); ?>+</div>
                                    <div class="stat-label">Students</div>
                                </div>
                            </div>
                            <div class="stat-item-small">
                                <div class="stat-icon">
                                    <i class="fas fa-chalkboard-teacher"></i>
                                </div>
                                <div class="stat-content">
                                    <div class="stat-number"><?php echo $stats['faculty_members']; ?>+</div>
                                    <div class="stat-label">Faculty Members</div>
                                </div>
                            </div>
                            <div class="stat-item-small">
                                <div class="stat-icon">
                                    <i class="fas fa-graduation-cap"></i>
                                </div>
                                <div class="stat-content">
                                    <div class="stat-number"><?php echo $stats['academic_programs']; ?>+</div>
                                    <div class="stat-label">Programs</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Quick Links -->
                    <div class="sidebar-widget" data-aos="fade-up" data-aos-delay="100">
                        <h3 class="widget-title">Related Pages</h3>
                        <ul class="related-links">
                            <li><a href="<?php echo url('pages/mission-vision.php'); ?>"><i class="fas fa-chevron-right"></i> Mission & Vision</a></li>
                            <li><a href="<?php echo url('pages/history.php'); ?>"><i class="fas fa-chevron-right"></i> Our History</a></li>
                            <li><a href="<?php echo url('pages/administration.php'); ?>"><i class="fas fa-chevron-right"></i> Administration</a></li>
                            <li><a href="<?php echo url('pages/programs.php'); ?>"><i class="fas fa-chevron-right"></i> Academic Programs</a></li>
                            <li><a href="<?php echo url('pages/facilities.php'); ?>"><i class="fas fa-chevron-right"></i> Facilities</a></li>
                        </ul>
                    </div>
                    
                    <!-- Contact CTA -->
                    <div class="sidebar-widget cta-widget" data-aos="fade-up" data-aos-delay="200">
                        <h3>Visit Our Campus</h3>
                        <p>Experience SMCC firsthand. Schedule a campus tour today!</p>
                        <a href="<?php echo url('pages/contact.php'); ?>" class="btn btn-primary btn-block">Contact Us</a>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <?php include '../includes/footer.php'; ?>
    
    <!-- Scripts -->
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

/* Content Article */
.content-article {
    background: var(--color-white);
    padding: 2rem;
    border-radius: var(--border-radius-lg);
    box-shadow: var(--shadow-md);
    margin-bottom: 2rem;
}

.article-body {
    line-height: 1.8;
    color: var(--color-dark-gray);
}

.article-body p {
    margin-bottom: 1.5rem;
}

.article-body h2,
.article-body h3 {
    margin-top: 2rem;
    margin-bottom: 1rem;
}

/* Info Boxes */
.info-boxes {
    margin-top: 2rem;
}

.info-box {
    background: var(--color-white);
    padding: 2rem;
    border-radius: var(--border-radius-lg);
    box-shadow: var(--shadow-md);
    transition: all var(--transition-base);
    height: 100%;
}

.info-box:hover {
    transform: translateY(-5px);
    box-shadow: var(--shadow-xl);
}

.info-icon {
    width: 60px;
    height: 60px;
    background: linear-gradient(135deg, var(--color-primary), var(--color-primary-light));
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    color: var(--color-white);
    margin-bottom: 1rem;
}

.info-box h3 {
    font-size: 1.25rem;
    margin-bottom: 0.5rem;
}

.info-link {
    color: var(--color-primary);
    font-weight: 600;
    transition: all var(--transition-base);
}

.info-link:hover {
    color: var(--color-primary-light);
    transform: translateX(5px);
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
    margin-bottom: 1.5rem;
    padding-bottom: 0.75rem;
    border-bottom: 2px solid var(--color-light-gray);
}

/* Stats List */
.stats-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.stat-item-small {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    background: var(--color-off-white);
    border-radius: var(--border-radius-md);
}

.stat-item-small .stat-icon {
    width: 50px;
    height: 50px;
    background: linear-gradient(135deg, var(--color-primary), var(--color-primary-light));
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--color-white);
    font-size: 1.25rem;
}

.stat-item-small .stat-number {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--color-primary);
}

.stat-item-small .stat-label {
    font-size: 0.875rem;
    color: var(--color-gray);
}

/* Related Links */
.related-links {
    list-style: none;
    padding: 0;
}

.related-links li {
    margin-bottom: 0.75rem;
}

.related-links a {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem;
    color: var(--color-dark-gray);
    transition: all var(--transition-base);
    border-radius: var(--border-radius-sm);
}

.related-links a:hover {
    background: var(--color-off-white);
    color: var(--color-primary);
    padding-left: 1rem;
}

.related-links i {
    font-size: 0.75rem;
    color: var(--color-primary);
}

/* CTA Widget */
.cta-widget {
    background: linear-gradient(135deg, var(--color-primary), var(--color-primary-dark));
    color: var(--color-white);
    text-align: center;
}

.cta-widget h3 {
    color: var(--color-white);
    margin-bottom: 0.5rem;
}

.cta-widget p {
    opacity: 0.9;
    margin-bottom: 1.5rem;
}

.btn-block {
    display: block;
    width: 100%;
}

/* Responsive */
@media (max-width: 768px) {
    .page-title {
        font-size: 2rem;
    }
    
    .content-article {
        padding: 1.5rem;
    }
    
    .col-md-4,
    .col-md-8 {
        flex: 0 0 100%;
    }
}
</style>
