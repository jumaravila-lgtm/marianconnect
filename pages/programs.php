<?php
/**
 * MARIANCONNECT - Academic Programs Page (Admission Focused)
 * Displays all academic programs with admission information
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
$level_filter = isset($_GET['level']) ? sanitize($_GET['level']) : '';

$where_conditions = ["is_active = 1"];
$params = [];

if (!empty($level_filter)) {
    $where_conditions[] = "level = ?";
    $params[] = $level_filter;
}

$where_clause = 'WHERE ' . implode(' AND ', $where_conditions);

try {
    $sql = "SELECT * FROM academic_programs $where_clause ORDER BY display_order ASC, program_name ASC";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $programs = $stmt->fetchAll();
} catch (Exception $e) {
    error_log("Programs query error: " . $e->getMessage());
    $programs = [];
}

$programs_by_level = [
    'elementary' => [],
    'junior_high' => [],
    'senior_high' => [],
    'college' => []
];

foreach ($programs as $program) {
    $programs_by_level[$program['level']][] = $program;
}

$levels = [
    'elementary' => [
        'title' => 'Elementary Education',
        'description' => 'Building strong foundations for lifelong learning',
        'color' => '#ff9800'
    ],
    'junior_high' => [
        'title' => 'Junior High School',
        'description' => 'Developing critical thinking and academic excellence',
        'color' => '#9c27b0'
    ],
    'senior_high' => [
        'title' => 'Senior High School',
        'description' => 'Specialized tracks preparing students for college and careers',
        'color' => '#673ab7'
    ],
    'college' => [
        'title' => 'College Programs',
        'description' => 'Professional degree programs for career success',
        'color' => '#4caf50'
    ]
];

$pageTitle = 'Academic Programs - ' . SITE_NAME;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    
    <meta name="description" content="Explore academic programs and admission requirements at St. Mary's College of Catbalogan">
    <meta name="keywords" content="SMCC Programs, Admission, Requirements, Elementary, High School, College">
    
    <link rel="icon" type="image/x-icon" href="<?php echo asset('images/logo/favicon.ico'); ?>">
    <link rel="stylesheet" href="<?php echo asset('css/main.css'); ?>">
    <link rel="stylesheet" href="<?php echo asset('css/responsive.css'); ?>">
    <link rel="stylesheet" href="<?php echo asset('css/components/navbar.css'); ?>">
    <link rel="stylesheet" href="<?php echo asset('css/components/footer.css'); ?>">
    <link rel="stylesheet" href="<?php echo asset('css/components/cards.css'); ?>">
<!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&family=Open+Sans:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/aos@2.3.1/dist/aos.css">
</head>
<body>
    
    <?php include BASE_PATH . '/includes/header.php'; ?>
    
    <!-- Page Header -->
    <section class="page-header" style="background: linear-gradient(135deg, rgba(0, 63, 135, 0.7), rgba(0, 40, 85, 0.9)), url('<?php echo asset("images/school header.jpg"); ?>') center/cover no-repeat;">
        <div class="page-header-overlay"></div>
        <div class="container">
            <div class="page-header-content" data-aos="fade-up">
                <h1 class="page-title">Academic Programs</h1>
                <nav class="breadcrumb">
                    <a href="<?php echo url(); ?>">Home</a>
                    <span class="separator">/</span>
                    <a href="#">Admission</a>
                    <span class="separator">/</span>
                    <span class="current">Programs</span>
                </nav>
            </div>
        </div>
    </section>
    
    <!-- Filter Tabs -->
    <section class="programs-filter">
        <div class="container">
            <div class="filter-tabs" data-aos="fade-up">
                <a href="?" class="tab-btn <?php echo empty($level_filter) ? 'active' : ''; ?>">
                    All Programs
                </a>
                <a href="?level=elementary" class="tab-btn <?php echo $level_filter === 'elementary' ? 'active' : ''; ?>">
                    Elementary
                </a>
                <a href="?level=junior_high" class="tab-btn <?php echo $level_filter === 'junior_high' ? 'active' : ''; ?>">
                    Junior High
                </a>
                <a href="?level=senior_high" class="tab-btn <?php echo $level_filter === 'senior_high' ? 'active' : ''; ?>">
                    Senior High
                </a>
                <a href="?level=college" class="tab-btn <?php echo $level_filter === 'college' ? 'active' : ''; ?>">
                    College
                </a>
            </div>
        </div>
    </section>
    
    <!-- Programs Content -->
    <section class="programs-content section-padding bg-light">
        <div class="container">
            <?php if (empty($level_filter)): ?>
                <?php foreach ($levels as $level_key => $level_info): ?>
                    <?php if (!empty($programs_by_level[$level_key])): ?>
                        <div class="program-level-section" data-aos="fade-up">
                            <div class="level-header">
                                <div class="level-info">
                                    <h2><?php echo htmlspecialchars($level_info['title']); ?></h2>
                                    <p><?php echo htmlspecialchars($level_info['description']); ?></p>
                                </div>
                                <a href="admission-policy.php#<?php echo $level_key; ?>" class="view-requirements-link">
                                    View Requirements
                                </a>
                            </div>
                            
                            <div class="programs-grid">
                                <?php foreach ($programs_by_level[$level_key] as $index => $program): ?>
                                    <div class="program-card-new" data-aos="fade-up" data-aos-delay="<?php echo $index * 100; ?>">
                                        <?php if (!empty($program['featured_image'])): ?>
                                            <div class="program-image">
                                                <img src="<?php echo getImageUrl($program['featured_image']); ?>"
                                                    alt="<?php echo htmlspecialchars($program['program_name']); ?>">
                                                <div class="program-overlay">
                                                    <span class="program-level-badge" style="background: <?php echo $level_info['color']; ?>">
                                                        <?php echo htmlspecialchars($level_info['title']); ?>
                                                    </span>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <div class="program-content">
                                            <div class="program-header-row">
                                                <span class="program-code"><?php echo htmlspecialchars($program['program_code']); ?></span>
                                                <?php if (!empty($program['duration'])): ?>
                                                    <span class="program-duration">
                                                        <?php echo htmlspecialchars($program['duration']); ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <h3><?php echo htmlspecialchars($program['program_name']); ?></h3>
                                            
                                            <?php if (!empty($program['department'])): ?>
                                                <div class="program-department">
                                                    <?php echo htmlspecialchars($program['department']); ?>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <p class="program-description">
                                                <?php echo htmlspecialchars(truncateText($program['description'], 100)); ?>
                                            </p>
                                            
                                            <div class="program-actions">
                                                <a href="program-detail.php?slug=<?php echo htmlspecialchars($program['slug']); ?>" class="btn btn-primary btn-sm">
                                                    Learn More
                                                </a>
                                                <a href="contact.php" class="btn btn-outline btn-sm">
                                                    Inquire
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
                
            <?php else: ?>
                <?php if (!empty($programs_by_level[$level_filter])): ?>
                    <div class="level-header centered" data-aos="fade-up">
                        <div class="level-info">
                            <h2><?php echo htmlspecialchars($levels[$level_filter]['title']); ?></h2>
                            <p><?php echo htmlspecialchars($levels[$level_filter]['description']); ?></p>
                        </div>
                        <a href="admission-policy.php#<?php echo $level_filter; ?>" class="btn btn-secondary">
                            View Admission Requirements
                        </a>
                    </div>
                    
                    <div class="programs-grid" style="margin-top: 3rem;">
                        <?php foreach ($programs_by_level[$level_filter] as $index => $program): ?>
                            <div class="program-card-new" data-aos="fade-up" data-aos-delay="<?php echo $index * 100; ?>">
                                <?php if (!empty($program['featured_image'])): ?>
                                    <div class="program-image">
                                        <img src="<?php echo getImageUrl($program['featured_image']); ?>" 
                                             alt="<?php echo htmlspecialchars($program['program_name']); ?>">
                                    </div>
                                <?php endif; ?>
                                
                                <div class="program-content">
                                    <div class="program-header-row">
                                        <span class="program-code"><?php echo htmlspecialchars($program['program_code']); ?></span>
                                        <?php if (!empty($program['duration'])): ?>
                                            <span class="program-duration">
                                                <?php echo htmlspecialchars($program['duration']); ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <h3><?php echo htmlspecialchars($program['program_name']); ?></h3>
                                    
                                    <?php if (!empty($program['department'])): ?>
                                        <div class="program-department">
                                            <?php echo htmlspecialchars($program['department']); ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <p class="program-description">
                                        <?php echo htmlspecialchars(truncateText($program['description'], 100)); ?>
                                    </p>
                                    
                                    <div class="program-actions">
                                        <a href="program-detail.php?slug=<?php echo htmlspecialchars($program['slug']); ?>" class="btn btn-primary btn-sm">
                                            Learn More
                                        </a>
                                        <a href="contact.php" class="btn btn-outline btn-sm">
                                            Inquire
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="no-results" data-aos="fade-up">
                        <h3>No Programs Found</h3>
                        <p>There are currently no programs in this category.</p>
                        <a href="?" class="btn btn-primary">View All Programs</a>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </section>
    
    <!-- CTA Section -->
    <section class="cta-section section-padding">
        <div class="container">
            <div class="cta-content" data-aos="fade-up">
                <h2>Ready to Begin Your Journey?</h2>
                <p>Learn about our admission process and take the first step toward your future at SMCC.</p>
                <div class="cta-buttons">
                    <a href="admission-policy.php" class="btn btn-light btn-lg">
                        View Admission Policy
                    </a>
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

.page-subtitle {
    font-size: 1.25rem;
    margin-bottom: 1.5rem;
    opacity: 0.9;
}

.breadcrumb {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.95rem;
}

.breadcrumb a {
    color: white;
    opacity: 0.8;
    transition: opacity 0.3s;
}

.breadcrumb a:hover {
    opacity: 1;
}

.breadcrumb .separator {
    opacity: 0.5;
}

/* Filter Tabs */
.programs-filter {
    padding: 2rem 0;
}

.filter-tabs {
    display: flex;
    gap: 1rem;
    justify-content: center;
    flex-wrap: wrap;
}

.tab-btn {
    padding: 1rem 2rem;
    background: white;
    border: 2px solid #dee2e6;
    border-radius: 8px;
    color: #495057;
    font-weight: 600;
    transition: all 0.3s;
    text-decoration: none;
}

.tab-btn:hover,
.tab-btn.active {
    background: var(--color-primary);
    border-color: var(--color-primary);
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,63,135,0.2);
}

/* Level Section */
.program-level-section {
    margin-bottom: 4rem;
}

.level-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 2rem;
    margin-bottom: 2rem;
    padding: 2rem;
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.08);
}

.level-header.centered {
    flex-direction: column;
    text-align: center;
    margin-bottom: 3rem;
}

.level-info h2 {
    font-size: 2rem;
    margin-bottom: 0.5rem;
    color: #003f87;
}

.level-info p {
    color: #6c757d;
    font-size: 1.1rem;
    margin: 0;
}

.view-requirements-link {
    padding: 0.75rem 1.5rem;
    background: #f8f9fa;
    border-radius: 8px;
    color: #003f87;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.3s;
    white-space: nowrap;
    border: 2px solid #dee2e6;
}

.view-requirements-link:hover {
    background: #003f87;
    color: white;
    border-color: #003f87;
}

/* Programs Grid */
.programs-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 2rem;
    margin-top: 2rem;
}

.program-card-new {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 2px 12px rgba(0,0,0,0.08);
    transition: all 0.3s;
    display: flex;
    flex-direction: column;
}

.program-card-new:hover {
    transform: translateY(-8px);
    box-shadow: 0 12px 24px rgba(0,0,0,0.15);
}

.program-image {
    position: relative;
    height: 200px;
    overflow: hidden;
}

.program-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.program-overlay {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    padding: 1rem;
    background: linear-gradient(transparent, rgba(0,0,0,0.7));
}

.program-level-badge {
    display: inline-block;
    padding: 0.4rem 0.8rem;
    border-radius: 6px;
    color: white;
    font-size: 0.8rem;
    font-weight: 600;
}

.program-content {
    padding: 1.5rem;
    flex: 1;
    display: flex;
    flex-direction: column;
}

.program-header-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}

.program-code {
    background: #e3f2fd;
    color: #1565c0;
    padding: 0.3rem 0.8rem;
    border-radius: 6px;
    font-size: 0.8rem;
    font-weight: 700;
}

.program-duration {
    font-size: 0.85rem;
    color: #6c757d;
    font-weight: 500;
}

.program-content h3 {
    font-size: 1.25rem;
    margin-bottom: 0.75rem;
    color: #003f87;
}

.program-department {
    font-size: 0.9rem;
    color: #6c757d;
    margin-bottom: 1rem;
}

.program-description {
    color: #495057;
    line-height: 1.6;
    margin-bottom: 1.5rem;
    flex: 1;
}

.program-actions {
    display: flex;
    gap: 0.75rem;
    margin-top: auto;
}

.program-actions .btn {
    flex: 1;
    text-align: center;
}

.btn {
    display: inline-block;
    padding: 0.75rem 1.5rem;
    border-radius: 8px;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.3s;
}

.btn-primary {
    background: #003f87;
    color: white;
}

.btn-primary:hover {
    background: #002855;
    transform: translateY(-2px);
}

.btn-outline {
    background: transparent;
    border: 2px solid #003f87;
    color: #003f87;
}

.btn-outline:hover {
    background: #003f87;
    color: white;
}

.btn-sm {
    padding: 0.5rem 1rem;
    font-size: 0.9rem;
}

/* CTA Section */
.cta-section {
    background: linear-gradient(135deg, #003f87, #0056b3);
    color: white;
}

.cta-content {
    text-align: center;
    padding: 2rem;
}

.cta-content h2 {
    font-size: 2.5rem;
    margin-bottom: 1rem;
    color: white;
}

.cta-content p {
    font-size: 1.25rem;
    margin-bottom: 2rem;
    opacity: 0.95;
}

.cta-buttons {
    display: flex;
    gap: 1rem;
    justify-content: center;
    flex-wrap: wrap;
}

.btn-lg {
    padding: 1rem 2rem;
    font-size: 1.1rem;
}

.btn-light {
    background: white;
    color: #003f87;
}

.btn-light:hover {
    background: #f8f9fa;
}

.btn-secondary {
    padding: 1rem 2rem;
    background: white;
    border: 2px solid #dee2e6;
    border-radius: 8px;
    color: #495057;
    font-weight: 600;
    transition: all 0.3s;
    text-decoration: none;
}

.btn-secondary:hover, btn-secondary:active {
    background: var(--color-primary);
    border-color: var(--color-primary);
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,63,135,0.2);
}

/* No Results */
.no-results {
    text-align: center;
    padding: 4rem 2rem;
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.08);
}

.no-results h3 {
    font-size: 1.75rem;
    margin-bottom: 1rem;
    color: #495057;
}

.no-results p {
    color: #6c757d;
    margin-bottom: 2rem;
}

/* Responsive */
@media (max-width: 768px) {
    .page-title {
        font-size: 2rem;
    }
    
    .filter-tabs {
        flex-direction: column;
    }
    
    .tab-btn {
        width: 100%;
    }
    
    .level-header {
        flex-direction: column;
        text-align: center;
    }
    
    .view-requirements-link {
        width: 100%;
        text-align: center;
    }
    
    .programs-grid {
        grid-template-columns: 1fr;
    }
    
    .program-actions {
        flex-direction: column;
    }
    
    .cta-buttons {
        flex-direction: column;
    }
}
</style>
