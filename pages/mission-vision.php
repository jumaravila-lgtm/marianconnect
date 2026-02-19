<?php
/**
 * MARIANCONNECT - Mission & Vision Page
 * Displays the school's mission, vision, and core values
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

// Fetch Mission & Vision content from database
try {
    $stmt = $db->prepare("
        SELECT * FROM pages 
        WHERE page_type = 'mission_vision' AND is_published = 1 
        LIMIT 1
    ");
    $stmt->execute();
    $page = $stmt->fetch();
    
    if (!$page) {
        // Default content if page not found
        $page = [
            'title' => 'Mission & Vision',
            'content' => '<h2>Our Mission</h2><p>To provide quality Catholic education...</p><h2>Our Vision</h2><p>To be a premier educational institution...</p>',
            'meta_title' => 'Mission & Vision - ' . SITE_NAME,
            'meta_description' => 'Learn about the mission, vision, and core values of St. Mary\'s College of Catbalogan'
        ];
    }
} catch (Exception $e) {
    error_log("Database error: " . $e->getMessage());
    $page = [
        'title' => 'Mission & Vision',
        'content' => '<p>Content currently unavailable.</p>',
        'meta_title' => 'Mission & Vision - ' . SITE_NAME,
        'meta_description' => 'Mission and Vision of St. Mary\'s College of Catbalogan'
    ];
}

$pageTitle = !empty($page['meta_title']) ? $page['meta_title'] : $page['title'] . ' - ' . SITE_NAME;
$metaDescription = !empty($page['meta_description']) ? $page['meta_description'] : 'Learn about the mission, vision, and core values of SMCC';

// Core Values (can be managed via database later)
$core_values = [
    [
        'icon' => 'fa-cross',
        'title' => 'Faith',
        'description' => 'Rooted in Catholic tradition and Christian values, we nurture spiritual growth and moral development.'
    ],
    [
        'icon' => 'fa-book-open',
        'title' => 'Excellence',
        'description' => 'We strive for academic excellence and continuous improvement in all aspects of education.'
    ],
    [
        'icon' => 'fa-hands-helping',
        'title' => 'Service',
        'description' => 'Committed to serving the community and developing compassionate, socially responsible individuals.'
    ]
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    
    <!-- SEO Meta Tags -->
    <meta name="description" content="<?php echo htmlspecialchars($metaDescription); ?>">
    <meta name="keywords" content="SMCC Mission, Vision, Core Values, Catholic Education">
    
    <!-- Open Graph -->
    <meta property="og:type" content="website">
    <meta property="og:title" content="<?php echo htmlspecialchars($pageTitle); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars($metaDescription); ?>">
    
    <link rel="icon" type="image/x-icon" href="<?php echo asset('images/logo/favicon.ico'); ?>">
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
                    <span class="current"><?php echo htmlspecialchars($page['title']); ?></span>
                </nav>
            </div>
        </div>
    </section>
    
    <!-- Mission & Vision Content -->
    <section class="page-content section-padding">
        <div class="container">
            <!-- Main Content -->
            <div class="mission-vision-content" data-aos="fade-up">
                <div class="content-wrapper">
                    <?php echo $page['content']; ?>
                </div>
            </div>
            
            <!-- Core Values Section -->
            <div class="core-values-section" data-aos="fade-up">
                <div class="section-header text-center">
                    <h2 class="section-title">Our Core Values</h2>
                    <p class="section-subtitle">The principles that guide everything we do at SMCC</p>
                </div>
                
                <div class="values-grid">
                    <?php foreach ($core_values as $index => $value): ?>
                        <div class="value-card" data-aos="fade-up" data-aos-delay="<?php echo $index * 100; ?>">
                            <div class="value-icon">
                                <i class="fas <?php echo $value['icon']; ?>"></i>
                            </div>
                            <h3><?php echo htmlspecialchars($value['title']); ?></h3>
                            <p><?php echo htmlspecialchars($value['description']); ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Call to Action -->
            <div class="cta-section" data-aos="fade-up">
                <div class="cta-content">
                    <h2>Join Our Community</h2>
                    <p>Be part of an institution that values faith, excellence, and service. Discover what makes SMCC special.</p>
                    <div class="cta-buttons">
                        <a href="programs.php" class="btn btn-primary btn-lg">
                            <i class="fas fa-graduation-cap"></i> View Programs
                        </a>
                        <a href="contact.php" class="btn btn-outline-primary btn-lg">
                            <i class="fas fa-envelope"></i> Contact Us
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Related Pages -->
    <section class="related-pages-section section-padding bg-light">
        <div class="container">
            <div class="section-header text-center" data-aos="fade-up">
                <h2 class="section-title">Learn More About SMCC</h2>
            </div>
            
            <div class="row g-4">
                <div class="col-md-4" data-aos="fade-up">
                    <div class="related-page-card">
                        <div class="card-icon">
                            <i class="fas fa-university"></i>
                        </div>
                        <h3>About SMCC</h3>
                        <p>Learn about our institution, our commitment to Catholic education, and our community.</p>
                        <a href="about.php" class="card-link">Learn More →</a>
                    </div>
                </div>
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="100">
                    <div class="related-page-card">
                        <div class="card-icon">
                            <i class="fas fa-history"></i>
                        </div>
                        <h3>Our History</h3>
                        <p>Discover our rich heritage and journey of excellence spanning over seven decades.</p>
                        <a href="history.php" class="card-link">Learn More →</a>
                    </div>
                </div>
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="200">
                    <div class="related-page-card">
                        <div class="card-icon">
                            <i class="fas fa-users-cog"></i>
                        </div>
                        <h3>Administration</h3>
                        <p>Meet our dedicated leadership team committed to educational excellence.</p>
                        <a href="administration.php" class="card-link">Learn More →</a>
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
    margin-bottom: 0;
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

/* Mission & Vision Content */
.mission-vision-content {
    margin-bottom: 4rem;
    margin-top: 0;
}

.content-wrapper {
    background: var(--color-white);
    padding: 3rem;
    border-radius: var(--border-radius-lg);
    box-shadow: var(--shadow-md);
}

.content-wrapper h2 {
    font-size: 2rem;
    color: var(--color-primary);
    margin-top: 2rem;
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 3px solid var(--color-secondary);
    text-align: center;
}

.content-wrapper h2:first-child {
    margin-top: 0;
}

.content-wrapper p {
    line-height: 1.8;
    color: var(--color-dark-gray);
    margin-bottom: 1.5rem;
    font-size: 1.0625rem;
    text-align: justify;
}

.content-wrapper ol,
.content-wrapper ul {
    padding-left: 2rem;
    margin-bottom: 2rem;
}

.content-wrapper ol li,
.content-wrapper ul li {
    margin-bottom: 1rem;
    line-height: 1.8;
    color: var(--color-dark-gray);
}

/* Core Values Section */
.core-values-section {
    margin-bottom: 4rem;
}

.values-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 2rem;
    margin-top: 3rem;
}

.value-card {
    background: var(--color-white);
    padding: 2.5rem 2rem;
    border-radius: var(--border-radius-lg);
    box-shadow: var(--shadow-md);
    text-align: center;
    transition: all var(--transition-base);
    border-top: 4px solid var(--color-primary);
}

.value-card:hover {
    transform: translateY(-10px);
    box-shadow: var(--shadow-xl);
    border-top-color: var(--color-secondary);
}

.value-icon {
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
    transition: all var(--transition-base);
}

.value-card:hover .value-icon {
    transform: scale(1.1) rotate(5deg);
}

.value-card h3 {
    font-size: 1.5rem;
    margin-bottom: 1rem;
    color: var(--color-primary);
}

.value-card p {
    color: var(--color-gray);
    line-height: 1.6;
    margin-bottom: 0;
}

/* CTA Section */
.cta-section {
    background: linear-gradient(135deg, var(--color-primary), var(--color-primary-dark));
    padding: 4rem 3rem;
    border-radius: var(--border-radius-xl);
    text-align: center;
    color: var(--color-white);
    box-shadow: var(--shadow-xl);
}

.cta-content h2 {
    font-size: 2.5rem;
    margin-bottom: 1rem;
    color: var(--color-white);
}

.cta-content p {
    font-size: 1.25rem;
    margin-bottom: 2rem;
    opacity: 0.95;
    max-width: 700px;
    margin-left: auto;
    margin-right: auto;
}

.cta-buttons {
    display: flex;
    gap: 1rem;
    justify-content: center;
    flex-wrap: wrap;
}

/* CTA Buttons Styling */
.cta-buttons .btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 1rem 2.5rem;
    border-radius: var(--border-radius-md);
    font-weight: 600;
    text-decoration: none;
    transition: all 0.3s ease;
    border: 2px solid;
    font-size: 1.0625rem;
}

/* View Programs Button - Yellow by default, White on hover */
.cta-buttons .btn-primary {
    background: var(--color-white);
    color: var(--color-primary);
}

.cta-buttons .btn-primary:hover {
    background: #ffc107;
    color: var(--color-primary);
    transform: translateY(-3px);
    box-shadow: 0 6px 16px rgba(255, 193, 7, 0.3);
}

/* Contact Us Button - White outline by default, Yellow on hover */
.cta-buttons .btn-outline-primary {
    background-color: transparent;
    color: var(--color-white);
    border-color: var(--color-white);
}

.cta-buttons .btn-outline-primary:hover {
    background-color: #ffc107;
    color: var(--color-primary);
    border-color: #ffc107;
    transform: translateY(-3px);
    box-shadow: 0 6px 16px rgba(255, 193, 7, 0.3);
}

/* Related Pages */
.related-page-card {
    background: var(--color-white);
    padding: 2rem;
    border-radius: var(--border-radius-lg);
    box-shadow: var(--shadow-md);
    text-align: center;
    height: 100%;
    transition: all var(--transition-base);
}

.related-page-card:hover {
    transform: translateY(-10px);
    box-shadow: var(--shadow-xl);
}

.related-page-card .card-icon {
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

.related-page-card h3 {
    font-size: 1.5rem;
    margin-bottom: 1rem;
}

.related-page-card p {
    color: var(--color-gray);
    margin-bottom: 1.5rem;
}

.card-link {
    display: inline-block;
    color: var(--color-primary);
    font-weight: 600;
    transition: all var(--transition-base);
}

.card-link:hover {
    color: var(--color-primary-light);
    transform: translateX(5px);
}

/* Responsive */
@media (max-width: 768px) {
    .content-wrapper {
        padding: 2rem;
    }
    
    .content-wrapper h2 {
        font-size: 1.5rem;
    }
    
    .values-grid {
        grid-template-columns: 1fr;
    }
    
    .cta-section {
        padding: 3rem 2rem;
    }
    
    .cta-content h2 {
        font-size: 1.75rem;
    }
    
    .cta-content p {
        font-size: 1rem;
    }
    
    .cta-buttons {
        flex-direction: column;
    }
    
    .cta-buttons .btn {
        width: 100%;
    }
}
</style>
