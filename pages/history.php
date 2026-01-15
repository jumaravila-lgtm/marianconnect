<?php
/**
 * MARIANCONNECT - History Page
 * Displays the school's history and timeline
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

// Fetch History content from database
try {
    $stmt = $db->prepare("
        SELECT * FROM pages 
        WHERE page_type = 'history' AND is_published = 1 
        LIMIT 1
    ");
    $stmt->execute();
    $page = $stmt->fetch();
    
    if (!$page) {
        // Default content if page not found
        $page = [
            'title' => 'Our History',
            'content' => '<p>St. Mary\'s College of Catbalogan has a rich history of educational excellence...</p>',
            'meta_title' => 'History - ' . SITE_NAME,
            'meta_description' => 'Discover the rich heritage and journey of St. Mary\'s College of Catbalogan'
        ];
    }
} catch (Exception $e) {
    error_log("Database error: " . $e->getMessage());
    $page = [
        'title' => 'Our History',
        'content' => '<p>Content currently unavailable.</p>',
        'meta_title' => 'History - ' . SITE_NAME,
        'meta_description' => 'History of St. Mary\'s College of Catbalogan'
    ];
}

$pageTitle = !empty($page['meta_title']) ? $page['meta_title'] : $page['title'] . ' - ' . SITE_NAME;
$metaDescription = !empty($page['meta_description']) ? $page['meta_description'] : 'Discover the rich heritage of SMCC';

// Historical Timeline (can be managed via database later)
$timeline = [
    [
        'year' => '1950',
        'title' => 'Foundation',
        'description' => 'St. Mary\'s College of Catbalogan was established by the Sisters of Charity, bringing Catholic education to Samar.',
        'icon' => 'fa-flag'
    ],
    [
        'year' => '1965',
        'title' => 'Expansion',
        'description' => 'The college expanded its facilities and introduced new academic programs to meet growing demand.',
        'icon' => 'fa-building'
    ],
    [
        'year' => '1980',
        'title' => 'Recognition',
        'description' => 'SMCC received full accreditation and recognition as one of the leading institutions in Eastern Visayas.',
        'icon' => 'fa-award'
    ],
    [
        'year' => '2000',
        'title' => 'Modernization',
        'description' => 'Introduction of computer laboratories and modern teaching facilities marked the digital transformation.',
        'icon' => 'fa-laptop'
    ],
    [
        'year' => '2015',
        'title' => 'Excellence Achievement',
        'description' => 'Multiple programs achieved Level III accreditation, recognizing our commitment to quality education.',
        'icon' => 'fa-trophy'
    ],
    [
        'year' => '2025',
        'title' => 'Continuing Legacy',
        'description' => 'Today, SMCC continues to provide quality Catholic education while embracing innovation and progress.',
        'icon' => 'fa-rocket'
    ]
];

// Milestones
$milestones = [
    ['number' => '75+', 'label' => 'Years of Excellence'],
    ['number' => '10,000+', 'label' => 'Alumni Worldwide'],
    ['number' => '3,500+', 'label' => 'Current Students'],
    ['number' => '150+', 'label' => 'Dedicated Faculty']
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
    <meta name="keywords" content="SMCC History, Heritage, Timeline, Catholic Education History">
    
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
    
    <!-- History Content -->
    <section class="page-content section-padding">
        <div class="container">
            <!-- Introduction -->
            <div class="history-intro" data-aos="fade-up">
                <div class="content-wrapper">
                    <?php echo $page['content']; ?>
                </div>
            </div>
            
            <!-- Milestones Stats -->
            <div class="milestones-section" data-aos="fade-up">
                <div class="row g-4">
                    <?php foreach ($milestones as $index => $milestone): ?>
                        <div class="col-md-3" data-aos="fade-up" data-aos-delay="<?php echo $index * 100; ?>">
                            <div class="milestone-card">
                                <div class="milestone-number"><?php echo htmlspecialchars($milestone['number']); ?></div>
                                <div class="milestone-label"><?php echo htmlspecialchars($milestone['label']); ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Timeline Section -->
    <section class="timeline-section section-padding bg-light">
        <div class="container">
            <div class="section-header text-center" data-aos="fade-up">
                <h2 class="section-title">Our Journey Through Time</h2>
                <p class="section-subtitle">Key milestones in our 75+ years of educational excellence</p>
            </div>
            
            <div class="timeline">
                <?php foreach ($timeline as $index => $item): ?>
                    <div class="timeline-item <?php echo $index % 2 == 0 ? 'timeline-left' : 'timeline-right'; ?>" 
                         data-aos="<?php echo $index % 2 == 0 ? 'fade-right' : 'fade-left'; ?>">
                        <div class="timeline-content">
                            <div class="timeline-icon">
                                <i class="fas <?php echo $item['icon']; ?>"></i>
                            </div>
                            <div class="timeline-year"><?php echo htmlspecialchars($item['year']); ?></div>
                            <h3><?php echo htmlspecialchars($item['title']); ?></h3>
                            <p><?php echo htmlspecialchars($item['description']); ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    
    <!-- Legacy Section -->
    <section class="legacy-section section-padding">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6" data-aos="fade-right">
                    <div class="legacy-image">
                        <img src="<?php echo asset('images/placeholder-legacy.jpg'); ?>" 
                        alt="SMCC Legacy" 
                        class="img-fluid rounded shadow"
                        onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22600%22 height=%22400%22%3E%3Crect fill=%22%23003f87%22 width=%22600%22 height=%22400%22/%3E%3Ctext fill=%22%23ffffff%22 font-family=%22Arial%22 font-size=%2228%22 x=%2250%25%22 y=%2250%25%22 text-anchor=%22middle%22 dominant-baseline=%22middle%22%3ESMCC Legacy%3C/text%3E%3C/svg%3E'">
                    </div>
                </div>
                <div class="col-md-6" data-aos="fade-left">
                    <div class="legacy-content">
                        <h2>Our Continuing Legacy</h2>
                        <p class="lead">For over seven decades, St. Mary's College of Catbalogan has been a beacon of Catholic education in Eastern Visayas.</p>
                        <p>From our humble beginnings to becoming one of the region's premier educational institutions, SMCC has consistently upheld its commitment to academic excellence, faith formation, and community service.</p>
                        <p>Our legacy lives on through thousands of alumni who have made significant contributions to their communities and professions, carrying forward the values and education they received at SMCC.</p>
                        <a href="about.php" class="btn btn-primary">
                            <i class="fas fa-university"></i> Learn More About SMCC
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
                <h2 class="section-title">Explore More</h2>
            </div>
            
            <div class="row g-4">
                <div class="col-md-4" data-aos="fade-up">
                    <div class="related-page-card">
                        <div class="card-icon">
                            <i class="fas fa-eye"></i>
                        </div>
                        <h3>Mission & Vision</h3>
                        <p>Discover our mission, vision, and the core values that guide us.</p>
                        <a href="mission-vision.php" class="card-link">Learn More →</a>
                    </div>
                </div>
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="100">
                    <div class="related-page-card">
                        <div class="card-icon">
                            <i class="fas fa-users-cog"></i>
                        </div>
                        <h3>Administration</h3>
                        <p>Meet the leadership team dedicated to educational excellence.</p>
                        <a href="administration.php" class="card-link">Learn More →</a>
                    </div>
                </div>
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="200">
                    <div class="related-page-card">
                        <div class="card-icon">
                            <i class="fas fa-trophy"></i>
                        </div>
                        <h3>Achievements</h3>
                        <p>Celebrate our accomplishments and recognitions throughout the years.</p>
                        <a href="achievements.php" class="card-link">Learn More →</a>
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
/* History Intro */
.history-intro {
    margin-bottom: 4rem;
}

/* Milestones Section */
.milestones-section {
    margin-bottom: 4rem;
}

.milestone-card {
    background: linear-gradient(135deg, var(--color-primary), var(--color-primary-dark));
    padding: 2.5rem 2rem;
    border-radius: var(--border-radius-lg);
    text-align: center;
    color: var(--color-white);
    box-shadow: var(--shadow-lg);
    transition: all var(--transition-base);
}

.milestone-card:hover {
    transform: translateY(-10px);
    box-shadow: var(--shadow-xl);
}

.milestone-number {
    font-size: 3rem;
    font-weight: 700;
    font-family: var(--font-primary);
    margin-bottom: 0.5rem;
    line-height: 1;
}

.milestone-label {
    font-size: 1.125rem;
    opacity: 0.95;
}

/* Timeline */
.timeline {
    position: relative;
    max-width: 1000px;
    margin: 3rem auto;
}

.timeline::before {
    content: '';
    position: absolute;
    left: 50%;
    transform: translateX(-50%);
    width: 4px;
    height: 100%;
    background: linear-gradient(180deg, var(--color-primary), var(--color-secondary));
    border-radius: 2px;
}

.timeline-item {
    position: relative;
    width: 50%;
    padding: 2rem;
    margin-bottom: 3rem;
}

.timeline-left {
    left: 0;
    padding-right: 4rem;
}

.timeline-right {
    left: 50%;
    padding-left: 4rem;
}

.timeline-content {
    background: var(--color-white);
    padding: 2rem;
    border-radius: var(--border-radius-lg);
    box-shadow: var(--shadow-md);
    position: relative;
    transition: all var(--transition-base);
}

.timeline-content:hover {
    transform: translateY(-5px);
    box-shadow: var(--shadow-xl);
}

.timeline-left .timeline-content::after {
    content: '';
    position: absolute;
    right: -15px;
    top: 30px;
    width: 0;
    height: 0;
    border-left: 15px solid var(--color-white);
    border-top: 15px solid transparent;
    border-bottom: 15px solid transparent;
}

.timeline-right .timeline-content::after {
    content: '';
    position: absolute;
    left: -15px;
    top: 30px;
    width: 0;
    height: 0;
    border-right: 15px solid var(--color-white);
    border-top: 15px solid transparent;
    border-bottom: 15px solid transparent;
}

.timeline-icon {
    position: absolute;
    width: 60px;
    height: 60px;
    background: linear-gradient(135deg, var(--color-primary), var(--color-primary-light));
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    color: var(--color-white);
    box-shadow: var(--shadow-lg);
    border: 4px solid var(--color-white);
}

.timeline-left .timeline-icon {
    right: -4rem;
    top: 2rem;
}

.timeline-right .timeline-icon {
    left: -4rem;
    top: 2rem;
}

.timeline-year {
    display: inline-block;
    background: var(--color-secondary);
    color: var(--color-dark-gray);
    padding: 0.5rem 1rem;
    border-radius: var(--border-radius-md);
    font-weight: 700;
    font-size: 1.125rem;
    margin-bottom: 1rem;
}

.timeline-content h3 {
    font-size: 1.5rem;
    margin-bottom: 1rem;
    color: var(--color-primary);
}

.timeline-content p {
    color: var(--color-gray);
    line-height: 1.6;
    margin-bottom: 0;
}

/* Legacy Section */
.legacy-section {
    padding: 4rem 0;
}

.legacy-image img {
    border-radius: var(--border-radius-lg);
}

.legacy-content h2 {
    font-size: 2.5rem;
    margin-bottom: 1.5rem;
}

.legacy-content .lead {
    font-size: 1.25rem;
    color: var(--color-primary);
    margin-bottom: 1.5rem;
}

.legacy-content p {
    line-height: 1.8;
    margin-bottom: 1.5rem;
    color: var(--color-gray);
}

/* Responsive */
@media (max-width: 768px) {
    .timeline::before {
        left: 30px;
    }
    
    .timeline-item {
        width: 100%;
        padding-left: 80px;
        padding-right: 0;
    }
    
    .timeline-left,
    .timeline-right {
        left: 0;
        padding-right: 0;
        padding-left: 80px;
    }
    
    .timeline-left .timeline-content::after,
    .timeline-right .timeline-content::after {
        left: -15px;
        right: auto;
        border-right: 15px solid var(--color-white);
        border-left: none;
    }
    
    .timeline-left .timeline-icon,
    .timeline-right .timeline-icon {
        left: 0;
        right: auto;
    }
    
    .milestone-number {
        font-size: 2.5rem;
    }
    
    .legacy-content {
        margin-top: 2rem;
    }
}
</style>
