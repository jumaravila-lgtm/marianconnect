<?php
/**
 * MARIANCONNECT - Program Detail Page
 * Displays detailed information about a specific academic program
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

// Get slug from URL
$slug = isset($_GET['slug']) ? sanitize($_GET['slug']) : '';

if (empty($slug)) {
    header("Location: programs.php");
    exit;
}

// Fetch program details
try {
    $stmt = $db->prepare("
        SELECT * FROM academic_programs
        WHERE slug = ? AND is_active = 1
        LIMIT 1
    ");
    $stmt->execute([$slug]);
    $program = $stmt->fetch();

    if (!$program) {
        header("Location: programs.php");
        exit;
    }

    // Fix image path for the main program
    if (!empty($program['featured_image'])) {
        $program['featured_image'] = asset($program['featured_image']);
    }

} catch (Exception $e) {
    error_log("Program fetch error: " . $e->getMessage());
    header("Location: programs.php");
    exit;
}

// Get related programs (same level)
try {
    $related_stmt = $db->prepare("
        SELECT * FROM academic_programs
        WHERE level = ? AND slug != ? AND is_active = 1
        ORDER BY display_order ASC
        LIMIT 3
    ");
    $related_stmt->execute([$program['level'], $slug]);
    $related_programs = $related_stmt->fetchAll();

    // Fix image paths for related programs
    foreach ($related_programs as &$related) {
        if (!empty($related['featured_image'])) {
            $related['featured_image'] = asset($related['featured_image']);
        }
    }
    unset($related);
} catch (Exception $e) {
    $related_programs = [];
}

// Level labels
$level_labels = [
    'elementary' => 'Elementary Education',
    'junior_high' => 'Junior High School',
    'senior_high' => 'Senior High School',
    'college' => 'College Programs'
];

$pageTitle = htmlspecialchars($program['program_name']) . ' - Programs - ' . SITE_NAME;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    
    <!-- SEO Meta Tags -->
    <meta name="description" content="<?php echo htmlspecialchars($program['description']); ?>">
    <meta name="keywords" content="SMCC, <?php echo htmlspecialchars($program['program_name']); ?>, <?php echo htmlspecialchars($program['program_code']); ?>, Academic Program">
    
    <!-- Open Graph -->
    <meta property="og:type" content="website">
    <meta property="og:title" content="<?php echo htmlspecialchars($program['program_name']); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars($program['description']); ?>">
    <?php if (!empty($program['featured_image'])): ?>
        <meta property="og:image" content="<?php echo htmlspecialchars($program['featured_image']); ?>">
    <?php endif; ?>
    
    <link rel="icon" type="image/x-icon" href="<?php echo asset('images/logo/favicon.ico'); ?>">
    <link rel="stylesheet" href="<?php echo asset('css/main.css'); ?>">
    <link rel="stylesheet" href="<?php echo asset('css/responsive.css'); ?>">
    
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
    <section class="page-header page-header-sm">
        <div class="page-header-overlay"></div>
        <div class="container">
            <div class="page-header-content" data-aos="fade-up">
                <nav class="breadcrumb">
                    <a href="<?php echo url(); ?>">Home</a>
                    <span class="separator">/</span>
                    <a href="programs.php">Programs</a>
                    <span class="separator">/</span>
                    <span class="current"><?php echo htmlspecialchars($program['program_name']); ?></span>
                </nav>
            </div>
        </div>
    </section>
    
    <!-- Program Details -->
    <section class="program-detail-section section-padding">
        <div class="container">
            <div class="row">
                <!-- Main Content -->
                <div class="col-md-8">
                    <article class="program-detail-card" data-aos="fade-up">
                        <!-- Program Header -->
                        <div class="program-header">
                            <div class="program-badges">
                                <span class="badge-code"><?php echo htmlspecialchars($program['program_code']); ?></span>
                                <span class="badge-level"><?php echo htmlspecialchars($level_labels[$program['level']] ?? ucfirst($program['level'])); ?></span>
                            </div>
                            <h1><?php echo htmlspecialchars($program['program_name']); ?></h1>
                            
                            <div class="program-info-row">
                                <?php if (!empty($program['department'])): ?>
                                    <div class="info-item">
                                        <i class="fas fa-building"></i>
                                        <span><?php echo htmlspecialchars($program['department']); ?></span>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($program['duration'])): ?>
                                    <div class="info-item">
                                        <i class="fas fa-clock"></i>
                                        <span><?php echo htmlspecialchars($program['duration']); ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Featured Image -->
                        <?php if (!empty($program['featured_image'])): ?>
                            <div class="program-image">
                                <img src="<?php echo htmlspecialchars($program['featured_image']); ?>" 
                                     alt="<?php echo htmlspecialchars($program['program_name']); ?>"
                                     onerror="this.src='https://via.placeholder.com/800x400/003f87/ffffff?text=Program'">
                            </div>
                        <?php endif; ?>
                        
                        <!-- Program Description -->
                        <div class="program-section">
                            <h2><i class="fas fa-info-circle"></i> Program Overview</h2>
                            <div class="program-description">
                                <?php echo nl2br(htmlspecialchars($program['description'])); ?>
                            </div>
                        </div>
                        
                        <!-- Admission Requirements -->
                        <?php if (!empty($program['admission_requirements'])): ?>
                            <div class="program-section">
                                <h2><i class="fas fa-clipboard-check"></i> Admission Requirements</h2>
                                <div class="program-content">
                                    <?php echo nl2br(htmlspecialchars($program['admission_requirements'])); ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Curriculum Highlights -->
                        <?php if (!empty($program['curriculum_highlights'])): ?>
                            <div class="program-section">
                                <h2><i class="fas fa-book-open"></i> Curriculum Highlights</h2>
                                <div class="program-content">
                                    <?php echo nl2br(htmlspecialchars($program['curriculum_highlights'])); ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Career Opportunities -->
                        <?php if (!empty($program['career_opportunities'])): ?>
                            <div class="program-section">
                                <h2><i class="fas fa-briefcase"></i> Career Opportunities</h2>
                                <div class="program-content">
                                    <?php echo nl2br(htmlspecialchars($program['career_opportunities'])); ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Brochure Download -->
                        <?php if (!empty($program['brochure_pdf'])): ?>
                            <div class="program-section brochure-section">
                                <h2><i class="fas fa-file-pdf"></i> Program Brochure</h2>
                                <p>Download our detailed program brochure for more information.</p>
                                <a href="<?php echo htmlspecialchars($program['brochure_pdf']); ?>" 
                                   class="btn btn-primary" 
                                   download 
                                   target="_blank">
                                    <i class="fas fa-download"></i> Download Brochure
                                </a>
                            </div>
                        <?php endif; ?>
                    </article>
                    
                    <!-- Related Programs -->
                    <?php if (!empty($related_programs)): ?>
                        <section class="related-programs" data-aos="fade-up">
                            <h2 class="section-title">Other <?php echo htmlspecialchars($level_labels[$program['level']] ?? 'Programs'); ?></h2>
                            <div class="related-grid">
                                <?php foreach ($related_programs as $related): ?>
                                    <div class="related-program-card">
                                        <div class="related-code"><?php echo htmlspecialchars($related['program_code']); ?></div>
                                        <h3>
                                            <a href="program-detail.php?slug=<?php echo htmlspecialchars($related['slug']); ?>">
                                                <?php echo htmlspecialchars($related['program_name']); ?>
                                            </a>
                                        </h3>
                                        <p><?php echo htmlspecialchars(truncateText($related['description'], 100)); ?></p>
                                        <a href="program-detail.php?slug=<?php echo htmlspecialchars($related['slug']); ?>" class="view-link">
                                            View Details →
                                        </a>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </section>
                    <?php endif; ?>
                </div>
                
                <!-- Sidebar -->
                <div class="col-md-4">
                    <!-- Quick Info -->
                    <div class="sidebar-widget" data-aos="fade-up">
                        <h3 class="widget-title">Quick Information</h3>
                        <div class="quick-info-list">
                            <div class="info-item">
                                <div class="info-label">Program Code:</div>
                                <div class="info-value"><?php echo htmlspecialchars($program['program_code']); ?></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Level:</div>
                                <div class="info-value"><?php echo htmlspecialchars($level_labels[$program['level']] ?? ucfirst($program['level'])); ?></div>
                            </div>
                            <?php if (!empty($program['department'])): ?>
                                <div class="info-item">
                                    <div class="info-label">Department:</div>
                                    <div class="info-value"><?php echo htmlspecialchars($program['department']); ?></div>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($program['duration'])): ?>
                                <div class="info-item">
                                    <div class="info-label">Duration:</div>
                                    <div class="info-value"><?php echo htmlspecialchars($program['duration']); ?></div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Apply Now CTA -->
                    <div class="sidebar-widget cta-widget" data-aos="fade-up" data-aos-delay="100">
                        <h3>Interested in this Program?</h3>
                        <p>Get in touch with our admissions team to learn more and start your application.</p>
                        <a href="contact.php" class="btn btn-primary btn-block">
                            <i class="fas fa-paper-plane"></i> Contact Admissions
                        </a>
                    </div>
                    
                    <!-- Back to Programs -->
                    <div class="sidebar-widget" data-aos="fade-up" data-aos-delay="200">
                        <h3 class="widget-title">Explore More</h3>
                        <ul class="sidebar-links">
                            <li><a href="programs.php"><i class="fas fa-chevron-right"></i> All Programs</a></li>
                            <li><a href="programs.php?level=<?php echo $program['level']; ?>"><i class="fas fa-chevron-right"></i> <?php echo htmlspecialchars($level_labels[$program['level']]); ?></a></li>
                            <li><a href="contact.php"><i class="fas fa-chevron-right"></i> Contact Us</a></li>
                        </ul>
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
/* Program Detail Card */
.program-detail-card {
    background: var(--color-white);
    padding: 2.5rem;
    border-radius: var(--border-radius-lg);
    box-shadow: var(--shadow-md);
    margin-bottom: 2rem;
}

/* Program Header */
.program-header {
    margin-bottom: 2rem;
}

.program-badges {
    display: flex;
    gap: 0.75rem;
    margin-bottom: 1.5rem;
    flex-wrap: wrap;
}

.badge-code,
.badge-level {
    display: inline-block;
    padding: 0.5rem 1rem;
    border-radius: var(--border-radius-md);
    font-size: 0.875rem;
    font-weight: 700;
    text-transform: uppercase;
}

.badge-code {
    background: var(--color-secondary);
    color: var(--color-dark-gray);
}

.badge-level {
    background: var(--color-primary);
    color: var(--color-white);
}

.program-header h1 {
    font-size: 2.5rem;
    line-height: 1.3;
    margin-bottom: 1.5rem;
    color: var(--color-primary);
}

.program-info-row {
    display: flex;
    gap: 2rem;
    padding-top: 1rem;
    border-top: 2px solid var(--color-light-gray);
    flex-wrap: wrap;
}

.info-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: var(--color-gray);
}

.info-item i {
    color: var(--color-primary);
    font-size: 1.125rem;
}

/* Program Image */
.program-image {
    margin-bottom: 2rem;
    border-radius: var(--border-radius-lg);
    overflow: hidden;
}

.program-image img {
    width: 100%;
    height: auto;
    display: block;
}

/* Program Sections */
.program-section {
    margin-bottom: 2.5rem;
    padding-bottom: 2rem;
    border-bottom: 1px solid var(--color-light-gray);
}

.program-section:last-child {
    border-bottom: none;
    margin-bottom: 0;
    padding-bottom: 0;
}

.program-section h2 {
    font-size: 1.75rem;
    margin-bottom: 1.5rem;
    color: var(--color-primary);
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.program-section h2 i {
    font-size: 1.5rem;
}

.program-description,
.program-content {
    line-height: 1.8;
    color: var(--color-dark-gray);
}

.program-content ul {
    padding-left: 1.5rem;
}

.program-content ul li {
    margin-bottom: 0.75rem;
}

/* Brochure Section */
.brochure-section {
    background: var(--color-off-white);
    padding: 2rem;
    border-radius: var(--border-radius-lg);
    text-align: center;
}

/* Related Programs */
.related-programs {
    margin-top: 3rem;
}

.related-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 1.5rem;
    margin-top: 2rem;
}

.related-program-card {
    background: var(--color-white);
    padding: 1.5rem;
    border-radius: var(--border-radius-lg);
    box-shadow: var(--shadow-md);
    transition: all var(--transition-base);
}

.related-program-card:hover {
    transform: translateY(-5px);
    box-shadow: var(--shadow-xl);
}

.related-code {
    display: inline-block;
    background: var(--color-secondary);
    color: var(--color-dark-gray);
    padding: 0.25rem 0.75rem;
    border-radius: var(--border-radius-sm);
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
    margin-bottom: 0.75rem;
}

.related-program-card h3 {
    font-size: 1.125rem;
    margin-bottom: 0.75rem;
}

.related-program-card a {
    color: var(--color-dark-gray);
    transition: color var(--transition-base);
}

.related-program-card h3 a:hover {
    color: var(--color-primary);
}

.related-program-card p {
    color: var(--color-gray);
    font-size: 0.9375rem;
    line-height: 1.6;
    margin-bottom: 1rem;
}

.view-link {
    color: var(--color-primary);
    font-weight: 600;
    transition: all var(--transition-base);
}

.view-link:hover {
    color: var(--color-primary-light);
    transform: translateX(5px);
}

/* Sidebar */
.quick-info-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.quick-info-list .info-item {
    padding: 1rem;
    background: var(--color-off-white);
    border-radius: var(--border-radius-md);
    display: flex;
    flex-direction: column;
}

.info-label {
    font-size: 0.875rem;
    color: var(--color-gray);
    margin-bottom: 0.25rem;
}

.info-value {
    font-weight: 600;
    color: var(--color-dark-gray);
    font-size: 1rem;
}

/* Sidebar Widgets */
.sidebar-widget {
    background: var(--color-white);
    padding: 2rem;
    border-radius: var(--border-radius-lg);
    box-shadow: var(--shadow-md);
    margin-bottom: 2rem;
}

.widget-title {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--color-primary);
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 3px solid var(--color-primary);
}

.sidebar-links {
    list-style: none;
    padding: 0;
}

.sidebar-links li {
    margin-bottom: 0.75rem;
}

.sidebar-links a {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem;
    color: var(--color-dark-gray);
    transition: all var(--transition-base);
    border-radius: var(--border-radius-sm);
}

.sidebar-links a:hover {
    background: var(--color-off-white);
    color: var(--color-primary);
    padding-left: 1rem;
}

.sidebar-links i {
    font-size: 0.75rem;
    color: var(--color-primary);
}
/* CTA Widget */
.cta-widget {
    background: linear-gradient(135deg, var(--color-primary), var(--color-primary-dark));
    color: var(--color-white);
}

.cta-widget h3 {
    color: var(--color-white);
    font-size: 1.5rem;
    margin-bottom: 1rem;
}

.cta-widget p {
    color: rgba(255, 255, 255, 0.9);
    margin-bottom: 1.5rem;
    line-height: 1.6;
}

.btn-block {
    display: block;
    width: 100%;
    text-align: center;
}
/* Responsive */
@media (max-width: 768px) {
    .program-detail-card {
        padding: 1.5rem;
    }
    
    .program-header h1 {
        font-size: 1.75rem;
    }
    
    .program-section h2 {
        font-size: 1.5rem;
    }
    
    .related-grid {
        grid-template-columns: 1fr;
    }
}
</style>
