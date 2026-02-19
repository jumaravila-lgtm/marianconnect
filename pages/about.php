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
        'academic_programs' => 11
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
       <section class="page-header" style="background: linear-gradient(135deg, rgba(0, 63, 135, 0.7), rgba(0, 40, 85, 0.9)), url('<?php echo asset("images/school header.jpg"); ?>') center/cover no-repeat;">
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
                                    <div class="stat-number"><?php echo number_format($stats['total_students']); ?></div>
                                    <div class="stat-label">Students</div>
                                </div>
                            </div>
                            <div class="stat-item-small">
                                <div class="stat-icon">
                                    <i class="fas fa-chalkboard-teacher"></i>
                                </div>
                                <div class="stat-content">
                                    <div class="stat-number"><?php echo $stats['faculty_members']; ?></div>
                                    <div class="stat-label">Faculty Members</div>
                                </div>
                            </div>
                            <div class="stat-item-small">
                                <div class="stat-icon">
                                    <i class="fas fa-graduation-cap"></i>
                                </div>
                                <div class="stat-content">
                                    <div class="stat-number"><?php echo $stats['academic_programs']; ?></div>
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
    text-decoration: none;
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

/* Main Content Area - Clean & Simple like SPSPS */
.page-content {
    padding: 3rem 0;
}

.content-article {
    background: var(--color-white);
    padding: 2.5rem;
    border-radius: var(--border-radius-lg);
    box-shadow: var(--shadow-sm);
    margin-bottom: 2rem;
}

.article-body {
    line-height: 1.8;
    color: #333;
    font-size: 1rem;
}

.article-body p {
    margin-bottom: 1.25rem;
    text-align: justify;
}

.article-body h2 {
    font-size: 1.75rem;
    font-weight: 600;
    color: var(--color-primary);
    margin-top: 2rem;
    margin-bottom: 1rem;
}

.article-body h3 {
    font-size: 1.4rem;
    font-weight: 600;
    color: var(--color-primary);
    margin-top: 1.5rem;
    margin-bottom: 0.75rem;
}

.article-body ul,
.article-body ol {
    margin-bottom: 1.25rem;
    padding-left: 2rem;
}

.article-body li {
    margin-bottom: 0.5rem;
}

.article-body strong {
    font-weight: 600;
    color: var(--color-primary);
}

/* Sidebar Widgets */
.sidebar-widget {
    background: var(--color-white);
    padding: 1.5rem;
    border-radius: var(--border-radius-lg);
    box-shadow: var(--shadow-sm);
    margin-bottom: 1.5rem;
}

.widget-title {
    font-size: 1.25rem;
    font-weight: 600;
    margin-bottom: 1.25rem;
    padding-bottom: 0.75rem;
    border-bottom: 2px solid #e5e7eb;
    color: var(--color-primary);
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
    background: #f9fafb;
    border-radius: var(--border-radius-md);
    transition: all 0.3s ease;
}

.stat-item-small:hover {
    background: #f3f4f6;
    transform: translateX(5px);
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
    flex-shrink: 0;
}

.stat-item-small .stat-number {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--color-primary);
    line-height: 1;
}

.stat-item-small .stat-label {
    font-size: 0.875rem;
    color: #6b7280;
    margin-top: 0.25rem;
}

/* Related Links */
.related-links {
    list-style: none;
    padding: 0;
    margin: 0;
}

.related-links li {
    margin-bottom: 0;
}

.related-links a {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem;
    color: #374151;
    transition: all 0.3s ease;
    border-radius: var(--border-radius-sm);
    text-decoration: none;
}

.related-links a:hover {
    background: #f9fafb;
    color: var(--color-primary);
    padding-left: 1.25rem;
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

.cta-widget .widget-title {
    color: var(--color-white);
    border-bottom-color: rgba(255, 255, 255, 0.3);
}

.cta-widget h3 {
    color: var(--color-white);
    margin-bottom: 0.75rem;
}

.cta-widget p {
    opacity: 0.9;
    margin-bottom: 1.5rem;
    line-height: 1.6;
} 

/* Contact Us Button - White by default, Yellow on hover */
.btn-primary {
    background: #ffffff !important; /* White background - force override */
    color: #003f87 !important; /* Dark blue text - force override */
    padding: 0.75rem 1.5rem;
    border-radius: var(--border-radius-md);
    font-weight: 600;
    text-decoration: none;
    display: inline-block;
    transition: all 0.3s ease;
    border: 2px solid #ffffff;
}

.btn-primary:hover {
    background: #ffc107 !important; /* Yellow on hover - force override */
    color: #003f87 !important;
    border-color: #ffc107;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(255, 193, 7, 0.4);
}

/* Responsive */
@media (max-width: 768px) {
    .page-title {
        font-size: 2rem;
    }
    
    .content-article {
        padding: 1.5rem;
    }
    
    .article-body {
        font-size: 0.95rem;
    }
    
    .col-md-4,
    .col-md-8 {
        flex: 0 0 100%;
        max-width: 100%;
    }
    
    .sidebar-widget {
        margin-top: 1rem;
    }
}
</style>
