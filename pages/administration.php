<?php
/**
 * MARIANCONNECT - Administration Page
 * Displays school leadership and administrative team
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

// Fetch Administration content from database
try {
    $stmt = $db->prepare("
        SELECT * FROM pages 
        WHERE page_type = 'administration' AND is_published = 1 
        LIMIT 1
    ");
    $stmt->execute();
    $page = $stmt->fetch();
    
    if (!$page) {
        $page = [
            'title' => 'Administration',
            'content' => '<p>Meet our dedicated leadership team...</p>',
            'meta_title' => 'Administration - ' . SITE_NAME,
            'meta_description' => 'Meet the leadership team of St. Mary\'s College of Catbalogan'
        ];
    }
} catch (Exception $e) {
    error_log("Database error: " . $e->getMessage());
    $page = [
        'title' => 'Administration',
        'content' => '<p>Content currently unavailable.</p>',
        'meta_title' => 'Administration - ' . SITE_NAME,
        'meta_description' => 'Leadership of St. Mary\'s College of Catbalogan'
    ];
}

$pageTitle = !empty($page['meta_title']) ? $page['meta_title'] : $page['title'] . ' - ' . SITE_NAME;
$metaDescription = !empty($page['meta_description']) ? $page['meta_description'] : 'Meet the leadership team of SMCC';

// Fetch Administration team from database
try {
    $stmt = $db->prepare("
        SELECT * FROM administration 
        WHERE is_active = 1 
        ORDER BY display_order ASC
    ");
    $stmt->execute();
    $administration = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Database error: " . $e->getMessage());
    $administration = [];
}

// Fetch Departments from database
try {
    $stmt = $db->prepare("
        SELECT * FROM departments 
        WHERE is_active = 1 
        ORDER BY display_order ASC
    ");
    $stmt->execute();
    $departments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Database error: " . $e->getMessage());
    $departments = [];
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
    <meta name="keywords" content="SMCC Administration, Leadership, School Officials">
    
    <!-- Open Graph -->
    <meta property="og:type" content="website">
    <meta property="og:title" content="<?php echo htmlspecialchars($pageTitle); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars($metaDescription); ?>">
    
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
                <h1 class="page-title"><?php echo htmlspecialchars($page['title']); ?></h1>
                <nav class="breadcrumb">
                    <a href="<?php echo url(); ?>">Home</a>
                    <span class="separator">/</span>
                    <a href="about.php">About Us</a>
                    <span class="separator">/</span>
                    <span class="current"><?php echo htmlspecialchars($page['title']); ?></span>
                </nav>
            </div>
        </div>
    </section>
    
    <!-- Introduction -->
    <section class="page-content section-padding">
        <div class="container">
            <div class="intro-content" data-aos="fade-up">
                <div class="content-wrapper">
                    <?php echo $page['content']; ?>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Leadership Team -->
    <section class="leadership-section section-padding bg-light">
        <div class="container">
            <div class="section-header text-center" data-aos="fade-up">
                <h2 class="section-title">Our Leadership Team</h2>
                <p class="section-subtitle">Dedicated professionals committed to educational excellence</p>
            </div>
            
            <div class="leadership-grid">
                <?php foreach ($administration as $index => $admin): ?>
                    <div class="admin-card" data-aos="fade-up" data-aos-delay="<?php echo $index * 100; ?>">
                        <div class="admin-image">
                            <img src="<?php echo escapeHtml(getImageUrl($admin['featured_image'])); ?>"
                                 alt="<?php echo htmlspecialchars($admin['name']); ?>"
                                 onerror="this.src='https://via.placeholder.com/300x300/003f87/ffffff?text=<?php echo urlencode(substr($admin['name'], 0, 1)); ?>'">
                            <div class="admin-overlay">
                                <a href="mailto:<?php echo htmlspecialchars($admin['email']); ?>" class="contact-btn">
                                    <i class="fas fa-envelope"></i>
                                </a>
                            </div>
                        </div>
                        <div class="admin-info">
                            <h3><?php echo htmlspecialchars($admin['name']); ?></h3>
                            <p class="admin-position"><?php echo htmlspecialchars($admin['position']); ?></p>
                            <p class="admin-description"><?php echo htmlspecialchars($admin['description']); ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    
    <!-- Departments -->
    <section class="departments-section section-padding">
        <div class="container">
            <div class="section-header text-center" data-aos="fade-up">
                <h2 class="section-title">Our Departments</h2>
                <p class="section-subtitle">Supporting your educational journey every step of the way</p>
            </div>
            
            <div class="departments-grid">
                <?php foreach ($departments as $index => $dept): ?>
                    <div class="department-card" data-aos="fade-up" data-aos-delay="<?php echo $index * 100; ?>">
                        <div class="dept-icon">
                            <i class="fas <?php echo htmlspecialchars($dept['icon']); ?>"></i>
                        </div>
                        <h3><?php echo htmlspecialchars($dept['name']); ?></h3>
                        <p class="dept-head">Head: <?php echo htmlspecialchars($dept['head_name']); ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    
    <!-- Contact Administration -->
    <section class="contact-admin-section section-padding bg-light">
        <div class="container">
            <div class="contact-admin-card" data-aos="fade-up">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h2>Need to Get in Touch?</h2>
                        <p>Have questions or concerns? Our administrative team is here to help you.</p>
                    </div>
                    <div class="col-md-4 text-center">
                        <a href="contact.php" class="btn btn-primary btn-lg">
                            <i class="fas fa-envelope"></i> Contact Us
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
/* Introduction */
.intro-content {
    margin-bottom: 3rem;
}

/* Leadership Grid */
.leadership-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 2rem;
    margin-top: 3rem;
}

.admin-card {
    background: var(--color-white);
    border-radius: var(--border-radius-lg);
    overflow: hidden;
    box-shadow: var(--shadow-md);
    transition: all var(--transition-base);
}

.admin-card:hover {
    transform: translateY(-10px);
    box-shadow: var(--shadow-xl);
}

.admin-image {
    position: relative;
    height: 300px;
    overflow: hidden;
}

.admin-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform var(--transition-slow);
}

.admin-card:hover .admin-image img {
    transform: scale(1.1);
}

.admin-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 63, 135, 0.9);
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: opacity var(--transition-base);
}

.admin-card:hover .admin-overlay {
    opacity: 1;
}

.contact-btn {
    width: 60px;
    height: 60px;
    background: var(--color-white);
    color: var(--color-primary);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    transition: all var(--transition-base);
}

.contact-btn:hover {
    background: var(--color-secondary);
    transform: scale(1.1);
}

.admin-info {
    padding: 1.5rem;
    text-align: center;
}

.admin-info h3 {
    font-size: 1.5rem;
    margin-bottom: 0.5rem;
    color: var(--color-primary);
}

.admin-position {
    font-weight: 600;
    color: var(--color-secondary);
    margin-bottom: 1rem;
    font-size: 1rem;
}

.admin-description {
    color: var(--color-gray);
    font-size: 0.9375rem;
    line-height: 1.6;
}

/* Departments Grid */
.departments-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 2rem;
    margin-top: 3rem;
}

.department-card {
    background: var(--color-white);
    padding: 2rem;
    border-radius: var(--border-radius-lg);
    box-shadow: var(--shadow-md);
    text-align: center;
    transition: all var(--transition-base);
    border-top: 4px solid var(--color-primary);
}

.department-card:hover {
    transform: translateY(-5px);
    box-shadow: var(--shadow-xl);
    border-top-color: var(--color-secondary);
}

.dept-icon {
    width: 70px;
    height: 70px;
    margin: 0 auto 1.5rem;
    background: linear-gradient(135deg, var(--color-primary), var(--color-primary-light));
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    color: var(--color-white);
    transition: all var(--transition-base);
}

.department-card:hover .dept-icon {
    transform: rotate(360deg);
}

.department-card h3 {
    font-size: 1.25rem;
    margin-bottom: 0.75rem;
    color: var(--color-primary);
}

.dept-head {
    color: var(--color-gray);
    font-size: 0.9375rem;
    margin-bottom: 0;
}

/* Contact Admin Section */
.contact-admin-card {
    background: linear-gradient(135deg, var(--color-primary), var(--color-primary-dark));
    padding: 3rem;
    border-radius: var(--border-radius-xl);
    color: var(--color-white);
    box-shadow: var(--shadow-xl);
}

.contact-admin-card h2 {
    font-size: 2rem;
    margin-bottom: 0.5rem;
    color: var(--color-white);
}

.contact-admin-card p {
    font-size: 1.125rem;
    margin-bottom: 0;
    opacity: 0.95;
}

.contact-admin-card .btn-primary {
    background-color: var(--color-white);
    color: var(--color-primary);
    border-color: var(--color-white);
}

.contact-admin-card .btn-primary:hover {
    background-color: var(--color-secondary);
    color: var(--color-dark-gray);
    border-color: var(--color-secondary);
}

/* Responsive */
@media (max-width: 768px) {
    .leadership-grid {
        grid-template-columns: 1fr;
    }
    
    .departments-grid {
        grid-template-columns: 1fr;
    }
    
    .contact-admin-card {
        padding: 2rem;
        text-align: center;
    }
    
    .contact-admin-card .col-md-4 {
        margin-top: 1.5rem;
    }
}
</style>
