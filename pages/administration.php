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
    <section class="page-header" style="background: linear-gradient(135deg, rgba(0, 63, 135, 0.7), rgba(0, 40, 85, 0.9)), url('<?php echo asset("images/school header.jpg"); ?>') center/cover no-repeat;">
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
                            <p class="admin-description"><?php echo htmlspecialchars($admin['description'] ?? ''); ?></p>
                        </div>
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

/* Leadership Grid */
.leadership-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 2.5rem;
    margin-top: 3rem;
}

.admin-card {
    background: var(--color-white);
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    transition: all 0.4s ease;
    border: 1px solid rgba(0, 63, 135, 0.1);
}

.admin-card:hover {
    transform: translateY(-10px);
    box-shadow: 0 12px 40px rgba(0, 63, 135, 0.15);
}

.admin-image {
    position: relative;
    height: 320px;
    overflow: hidden;
}

.admin-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.6s ease;
}

.admin-card:hover .admin-image img {
    transform: scale(1.08);
}

.admin-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: linear-gradient(135deg, rgba(0, 63, 135, 0.95), rgba(0, 40, 85, 0.98));
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: opacity 0.4s ease;
}

.admin-card:hover .admin-overlay {
    opacity: 1;
}

.contact-btn {
    width: 64px;
    height: 64px;
    background: var(--color-white);
    color: #003f87;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    transition: all 0.3s ease;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.contact-btn:hover {
    background: #ffc107;
    transform: scale(1.15) rotate(5deg);
    box-shadow: 0 6px 20px rgba(255, 193, 7, 0.4);
}

.admin-info {
    padding: 2rem 1.75rem;
    text-align: center;
}

.admin-info h3 {
    font-size: 1.5rem;
    margin-bottom: 0.5rem;
    color: #003f87;
    font-weight: 700;
}

.admin-position {
    font-weight: 600;
    color: #ffc107;
    margin-bottom: 1rem;
    font-size: 1rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.admin-description {
    color: #6c757d;
    font-size: 0.9rem;
    line-height: 1.6;
    max-height: 4.8em; /* 3 lines max */
    overflow: hidden;
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
}

/* Contact Admin Section */
.contact-admin-card {
    background: linear-gradient(135deg, #003f87, #002855);
    padding: 3.5rem 3rem;
    border-radius: 20px;
    color: var(--color-white);
    box-shadow: 0 10px 40px rgba(0, 63, 135, 0.25);
    position: relative;
    overflow: hidden;
}

.contact-admin-card::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -20%;
    width: 400px;
    height: 400px;
    background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, transparent 70%);
    border-radius: 50%;
}

.contact-admin-card h2 {
    font-size: 2.25rem;
    margin-bottom: 0.75rem;
    color: var(--color-white);
    font-weight: 700;
}

.contact-admin-card p {
    font-size: 1.15rem;
    margin-bottom: 0;
    opacity: 0.95;
}

.contact-admin-card .btn-primary {
    background-color: #ffffff;
    color: #003f87;
    border: none;
    padding: 1rem 2.5rem;
    font-weight: 700;
    font-size: 1.05rem;
    transition: all 0.3s ease;
}

.contact-admin-card .btn-primary:hover {
    background-color: #ffc107;
    transform: translateY(-3px);
    box-shadow: 0 6px 25px rgba(255, 193, 7, 0.4);
}

/* Section Headers */
.section-header {
    margin-bottom: 1rem;
}

.section-title {
    font-size: 2.5rem;
    font-weight: 700;
    color: #003f87;
    margin-bottom: 0.75rem;
}

.section-subtitle {
    font-size: 1.15rem;
    color: #6c757d;
    max-width: 600px;
    margin: 0 auto;
}

/* Section Padding */
.section-padding {
    padding: 4.5rem 0;
}

.bg-light {
    background: #f8f9fa;
}

/* Responsive */
@media (max-width: 768px) {
    .page-title {
        font-size: 2rem;
    }
    
    .leadership-grid {
        grid-template-columns: 1fr;
        gap: 2rem;
    }
    
    .contact-admin-card {
        padding: 2.5rem 2rem;
        text-align: center;
    }
    
    .contact-admin-card h2 {
        font-size: 1.75rem;
    }
    
    .contact-admin-card .col-md-4 {
        margin-top: 1.5rem;
    }
    
    .section-title {
        font-size: 2rem;
    }
}
</style>
