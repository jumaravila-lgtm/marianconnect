<?php
/**
 * MARIANCONNECT - Program Detail Page (Admission Focused)
 * Displays detailed program information with admission requirements
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

define('BASE_PATH', dirname(__DIR__));

require_once BASE_PATH . '/config/database.php';
require_once BASE_PATH . '/config/settings.php';
require_once BASE_PATH . '/config/security.php';
require_once BASE_PATH . '/includes/functions.php';

try {
    trackVisitor();
} catch (Exception $e) {
    error_log("Visitor tracking error: " . $e->getMessage());
}

$db = getDB();
$slug = isset($_GET['slug']) ? sanitize($_GET['slug']) : '';

if (empty($slug)) {
    header("Location: programs.php");
    exit;
}

try {
    $stmt = $db->prepare("SELECT * FROM academic_programs WHERE slug = ? AND is_active = 1 LIMIT 1");
    $stmt->execute([$slug]);
    $program = $stmt->fetch();

    if (!$program) {
        header("Location: programs.php");
        exit;
    }
} catch (Exception $e) {
    error_log("Program fetch error: " . $e->getMessage());
    header("Location: programs.php");
    exit;
}

try {
    $related_stmt = $db->prepare("
        SELECT * FROM academic_programs
        WHERE level = ? AND slug != ? AND is_active = 1
        ORDER BY display_order ASC
        LIMIT 3
    ");
    $related_stmt->execute([$program['level'], $slug]);
    $related_programs = $related_stmt->fetchAll();
} catch (Exception $e) {
    $related_programs = [];
}

$level_labels = [
    'elementary' => 'Elementary Education',
    'junior_high' => 'Junior High School',
    'senior_high' => 'Senior High School',
    'college' => 'College Programs'
];

$level_colors = [
    'elementary' => '#ff9800',
    'junior_high' => '#9c27b0',
    'senior_high' => '#673ab7',
    'college' => '#4caf50'
];

$pageTitle = htmlspecialchars($program['program_name']) . ' - Programs - ' . SITE_NAME;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    
    <meta name="description" content="<?php echo htmlspecialchars($program['description']); ?>">
    <meta name="keywords" content="SMCC, <?php echo htmlspecialchars($program['program_name']); ?>, Admission, Requirements">
    
    <?php if (!empty($program['featured_image'])): ?>
        <meta property="og:image" content="<?php echo htmlspecialchars($program['featured_image']); ?>">
    <?php endif; ?>
    
    <link rel="icon" type="image/x-icon" href="<?php echo asset('images/logo/favicon.ico'); ?>">
    <link rel="stylesheet" href="<?php echo asset('css/main.css'); ?>">
    <link rel="stylesheet" href="<?php echo asset('css/responsive.css'); ?>">
    
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/aos@2.3.1/dist/aos.css">
</head>
<body>
    
    <?php include BASE_PATH . '/includes/header.php'; ?>
    
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
                                <span class="badge-level" style="background: <?php echo $level_colors[$program['level']]; ?>">
                                    <?php echo htmlspecialchars($level_labels[$program['level']] ?? ucfirst($program['level'])); ?>
                                </span>
                            </div>
                            <h1><?php echo htmlspecialchars($program['program_name']); ?></h1>
                            
                            <div class="program-info-row">
                                <?php if (!empty($program['department'])): ?>
                                    <div class="info-item">
                                        <span class="info-label">Department:</span>
                                        <span><?php echo htmlspecialchars($program['department']); ?></span>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($program['duration'])): ?>
                                    <div class="info-item">
                                        <span class="info-label">Duration:</span>
                                        <span><?php echo htmlspecialchars($program['duration']); ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Featured Image -->
                        <?php if (!empty($program['featured_image'])): ?>
                            <div class="program-image">
                                <img src="/marianconnect<?php echo htmlspecialchars($program['featured_image']); ?>"
                                     alt="<?php echo htmlspecialchars($program['program_name']); ?>"
                                     onerror="this.src='https://via.placeholder.com/800x400/003f87/ffffff?text=Program'">
                            </div>
                        <?php endif; ?>
                        
                        <!-- Program Description -->
                        <div class="program-section">
                            <h2>Program Overview</h2>
                            <div class="program-description">
                                <?php echo nl2br(htmlspecialchars($program['description'])); ?>
                            </div>
                        </div>
                        
                        <!-- Admission Requirements - HIGHLIGHTED -->
                        <?php if (!empty($program['admission_requirements'])): ?>
                            <div class="program-section admission-requirements-section">
                                <div class="section-highlight-badge">Important</div>
                                <h2>Admission Requirements</h2>
                                <div class="requirements-box">
                                    <div class="requirements-content">
                                        <?php echo nl2br(htmlspecialchars($program['admission_requirements'])); ?>
                                    </div>
                                    <div class="requirements-footer">
                                        <a href="admission-policy.php#<?php echo $program['level']; ?>" class="btn btn-secondary">
                                            View Complete Admission Policy
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Curriculum Highlights -->
                        <?php if (!empty($program['curriculum_highlights'])): ?>
                            <div class="program-section">
                                <h2>Curriculum Highlights</h2>
                                <div class="program-content">
                                    <?php echo nl2br(htmlspecialchars($program['curriculum_highlights'])); ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Career Opportunities -->
                        <?php if (!empty($program['career_opportunities'])): ?>
                            <div class="program-section">
                                <h2>Career Opportunities</h2>
                                <div class="program-content">
                                    <?php echo nl2br(htmlspecialchars($program['career_opportunities'])); ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Brochure Download -->
                        <?php if (!empty($program['brochure_pdf'])): ?>
                            <div class="program-section brochure-section">
                                <h2>Program Brochure</h2>
                                <p>Download our detailed program brochure for more information.</p>
                                <a href="<?php echo htmlspecialchars($program['brochure_pdf']); ?>" 
                                   class="btn btn-primary" 
                                   download 
                                   target="_blank">
                                    Download Brochure
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
                    <!-- Apply Now CTA -->
                    <div class="sidebar-widget cta-widget-primary" data-aos="fade-up">
                        <h3>Interested in this Program?</h3>
                        <p>Get in touch with our admissions team to learn more and start your application.</p>
                        <a href="contact.php" class="btn btn-light btn-block">
                            Contact Admissions
                        </a>
                        <div class="cta-divider">or</div>
                        <a href="enrollment-process.php" class="btn btn-outline-light btn-block">
                            View Enrollment Steps
                        </a>
                    </div>
                    
                    <!-- Quick Info -->
                    <div class="sidebar-widget" data-aos="fade-up" data-aos-delay="100">
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
                    
                    <!-- Explore More -->
                    <div class="sidebar-widget" data-aos="fade-up" data-aos-delay="300">
                        <h3 class="widget-title">Explore More</h3>
                        <ul class="sidebar-links">
                            <li><a href="programs.php">All Programs</a></li>
                            <li><a href="programs.php?level=<?php echo $program['level']; ?>"><?php echo htmlspecialchars($level_labels[$program['level']]); ?></a></li>
                            <li><a href="contact.php">Contact Us</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <?php include BASE_PATH . '/includes/footer.php'; ?>
    
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init({ duration: 800, once: true });
    </script>
</body>
</html>

<style>
/* Program Detail Card */
.program-detail-card {
    background: white;
    padding: 2.5rem;
    border-radius: 12px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.08);
    margin-bottom: 2rem;
}

.program-header {
    margin-bottom: 2rem;
}

.program-badges {
    display: flex;
    gap: 0.75rem;
    margin-bottom: 1.5rem;
    flex-wrap: wrap;
}

.badge-code, .badge-level {
    display: inline-block;
    padding: 0.5rem 1rem;
    border-radius: 8px;
    font-size: 0.875rem;
    font-weight: 700;
    text-transform: uppercase;
}

.badge-code {
    background: #e3f2fd;
    color: #1565c0;
}

.badge-level {
    color: white;
}

.program-header h1 {
    font-size: 2.5rem;
    line-height: 1.3;
    margin-bottom: 1.5rem;
    color: #003f87;
}

.program-info-row {
    display: flex;
    gap: 2rem;
    padding-top: 1rem;
    border-top: 2px solid #f0f0f0;
    flex-wrap: wrap;
}

.info-item {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.info-label {
    font-size: 0.85rem;
    color: #6c757d;
    font-weight: 500;
}

.info-item > span {
    font-size: 1rem;
    color: #212529;
    font-weight: 600;
}

.program-image {
    margin-bottom: 2rem;
    border-radius: 12px;
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
    border-bottom: 1px solid #e0e0e0;
}

.program-section:last-child {
    border-bottom: none;
    margin-bottom: 0;
    padding-bottom: 0;
}

.program-section h2 {
    font-size: 1.75rem;
    margin-bottom: 1.5rem;
    color: #003f87;
}

.program-description, .program-content {
    line-height: 1.8;
    color: #333;
}

/* Admission Requirements Section */
.admission-requirements-section {
    position: relative;
    background: linear-gradient(135deg, #e3f2fd, #f3e5f5);
    padding: 2rem;
    border-radius: 12px;
    border: 3px solid #2196f3;
}

.section-highlight-badge {
    position: absolute;
    top: -12px;
    left: 20px;
    background: #ff9800;
    color: white;
    padding: 0.4rem 1rem;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 700;
}

.requirements-box {
    background: white;
    border-radius: 8px;
    padding: 1.5rem;
    margin-top: 1rem;
}

.requirements-content {
    line-height: 1.8;
    color: #333;
}

.requirements-footer {
    margin-top: 1.5rem;
    padding-top: 1.5rem;
    border-top: 2px dashed #dee2e6;
    text-align: center;
}

/* Sidebar */
.sidebar-widget {
    background: white;
    padding: 2rem;
    border-radius: 12px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.08);
    margin-bottom: 2rem;
}

.widget-title {
    font-size: 1.25rem;
    font-weight: 700;
    color: #003f87;
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 3px solid #003f87;
}

/* CTA Widget */
.cta-widget-primary {
    background: linear-gradient(135deg, #003f87, #0056b3);
    color: white;
    text-align: center;
}

.cta-widget-primary h3 {
    color: white;
    font-size: 1.5rem;
    margin-bottom: 1rem;
    font-weight: 700;
}

.cta-widget-primary p {
    color: rgba(255,255,255,0.9);
    margin-bottom: 1.5rem;
    line-height: 1.6;
}

.btn {
    display: inline-block;
    padding: 0.75rem 1.5rem;
    border-radius: 8px;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.3s;
    border: 2px solid transparent;
}

.btn-light {
    background: white;
    color: #003f87;
}

.btn-light:hover {
    background: #f8f9fa;
    transform: translateY(-2px);
}

.btn-outline-light {
    background: transparent;
    border-color: white;
    color: white;
}

.btn-outline-light:hover {
    background: white;
    color: #003f87;
}

.btn-block {
    display: block;
    width: 100%;
    text-align: center;
    margin-bottom: 0.75rem;
}

.cta-divider {
    margin: 1rem 0;
    color: rgba(255,255,255,0.7);
    font-size: 0.9rem;
}

.btn-primary {
    background: #003f87;
    color: white;
}

.btn-primary:hover {
    background: #002855;
}

.btn-secondary {
    background: #6c757d;
    color: white;
}

.btn-secondary:hover {
    background: #5a6268;
}

/* Quick Info */
.quick-info-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.quick-info-list .info-item {
    padding: 1rem;
    background: #f8f9fa;
    border-radius: 8px;
    display: flex;
    flex-direction: column;
}

.info-label {
    font-size: 0.875rem;
    color: #6c757d;
    margin-bottom: 0.25rem;
}

.info-value {
    font-weight: 600;
    color: #333;
    font-size: 1rem;
}

/* Sidebar Links */
.sidebar-links {
    list-style: none;
    padding: 0;
}

.sidebar-links li {
    margin-bottom: 0.75rem;
}

.sidebar-links a {
    display: block;
    padding: 0.75rem;
    color: #333;
    transition: all 0.3s;
    border-radius: 6px;
    text-decoration: none;
    font-weight: 500;
}

.sidebar-links a:hover {
    background: #f8f9fa;
    color: #003f87;
    padding-left: 1.25rem;
}

/* Related Programs */
.related-programs {
    margin-top: 3rem;
}

.section-title {
    font-size: 1.75rem;
    color: #003f87;
    margin-bottom: 2rem;
}

.related-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 1.5rem;
}

.related-program-card {
    background: white;
    padding: 1.5rem;
    border-radius: 12px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.08);
    transition: all 0.3s;
}

.related-program-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.15);
}

.related-code {
    display: inline-block;
    background: #e3f2fd;
    color: #1565c0;
    padding: 0.25rem 0.75rem;
    border-radius: 6px;
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
    color: #333;
    transition: color 0.3s;
    text-decoration: none;
}

.related-program-card h3 a:hover {
    color: #003f87;
}

.related-program-card p {
    color: #6c757d;
    font-size: 0.9375rem;
    line-height: 1.6;
    margin-bottom: 1rem;
}

.view-link {
    color: #003f87;
    font-weight: 600;
}

.view-link:hover {
    color: #0056b3;
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
