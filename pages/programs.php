<?php
/**
 * MARIANCONNECT - Academic Programs Page
 * Displays all academic programs by level
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

// Filter by level
$level_filter = isset($_GET['level']) ? sanitize($_GET['level']) : '';

// Build query
$where_conditions = ["is_active = 1"];
$params = [];

if (!empty($level_filter)) {
    $where_conditions[] = "level = ?";
    $params[] = $level_filter;
}

$where_clause = 'WHERE ' . implode(' AND ', $where_conditions);

// Fetch programs
try {
    $sql = "
        SELECT * FROM academic_programs
        $where_clause
        ORDER BY display_order ASC, program_name ASC
    ";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $programs = $stmt->fetchAll();

    // Fix image paths for all programs
    foreach ($programs as &$program) {
        if (!empty($program['featured_image'])) {
            $program['featured_image'] = asset($program['featured_image']);
        }
    }
    unset($program); // Break reference

} catch (Exception $e) {
    error_log("Programs query error: " . $e->getMessage());
    $programs = [];
}

// Group programs by level
$programs_by_level = [
    'elementary' => [],
    'junior_high' => [],
    'senior_high' => [],
    'college' => []
];

foreach ($programs as $program) {
    $programs_by_level[$program['level']][] = $program;
}

// Program levels info
$levels = [
    'elementary' => [
        'title' => 'Elementary Education',
        'icon' => 'fa-child',
        'description' => 'Building strong foundations for lifelong learning'
    ],
    'junior_high' => [
        'title' => 'Junior High School',
        'icon' => 'fa-book-reader',
        'description' => 'Developing critical thinking and academic excellence'
    ],
    'senior_high' => [
        'title' => 'Senior High School',
        'icon' => 'fa-user-graduate',
        'description' => 'Specialized tracks preparing students for college and careers'
    ],
    'college' => [
        'title' => 'College Programs',
        'icon' => 'fa-graduation-cap',
        'description' => 'Professional degree programs for career success'
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
    
    <meta name="description" content="Explore academic programs at St. Mary's College of Catbalogan from elementary to college level">
    <meta name="keywords" content="SMCC Programs, Academic Programs, Elementary, High School, College, Education">
    
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
                <h1 class="page-title">Academic Programs</h1>
                <nav class="breadcrumb">
                    <a href="<?php echo url(); ?>">Home</a>
                    <span class="separator">/</span>
                    <span class="current">Programs</span>
                </nav>
            </div>
        </div>
    </section>
    
    <!-- Filter Tabs -->
    <section class="programs-filter section-padding">
        <div class="container">
            <div class="filter-tabs" data-aos="fade-up">
                <a href="?" class="tab-btn <?php echo empty($level_filter) ? 'active' : ''; ?>">
                    <i class="fas fa-th"></i> All Programs
                </a>
                <a href="?level=elementary" class="tab-btn <?php echo $level_filter === 'elementary' ? 'active' : ''; ?>">
                    <i class="fas fa-child"></i> Elementary
                </a>
                <a href="?level=junior_high" class="tab-btn <?php echo $level_filter === 'junior_high' ? 'active' : ''; ?>">
                    <i class="fas fa-book-reader"></i> Junior High
                </a>
                <a href="?level=senior_high" class="tab-btn <?php echo $level_filter === 'senior_high' ? 'active' : ''; ?>">
                    <i class="fas fa-user-graduate"></i> Senior High
                </a>
                <a href="?level=college" class="tab-btn <?php echo $level_filter === 'college' ? 'active' : ''; ?>">
                    <i class="fas fa-graduation-cap"></i> College
                </a>
            </div>
        </div>
    </section>
    
    <!-- Programs Content -->
    <section class="programs-content section-padding bg-light">
        <div class="container">
            <?php if (empty($level_filter)): ?>
                <!-- Show all levels -->
                <?php foreach ($levels as $level_key => $level_info): ?>
                    <?php if (!empty($programs_by_level[$level_key])): ?>
                        <div class="program-level-section" data-aos="fade-up">
                            <div class="level-header">
                                <div class="level-icon">
                                    <i class="fas <?php echo $level_info['icon']; ?>"></i>
                                </div>
                                <div class="level-info">
                                    <h2><?php echo htmlspecialchars($level_info['title']); ?></h2>
                                    <p><?php echo htmlspecialchars($level_info['description']); ?></p>
                                </div>
                            </div>
                            
                            <div class="programs-grid">
                                <?php foreach ($programs_by_level[$level_key] as $index => $program): ?>
                                    <div class="program-card" data-aos="fade-up" data-aos-delay="<?php echo $index * 100; ?>">
                                        <?php if (!empty($program['featured_image'])): ?>
                                            <div class="program-image">
                                                <img src="<?php echo htmlspecialchars($program['featured_image']); ?>" 
                                                     alt="<?php echo htmlspecialchars($program['program_name']); ?>"
                                                     onerror="this.src='https://via.placeholder.com/400x250/003f87/ffffff?text=Program'">
                                            </div>
                                        <?php endif; ?>
                                        
                                        <div class="program-content">
                                            <div class="program-code"><?php echo htmlspecialchars($program['program_code']); ?></div>
                                            <h3><?php echo htmlspecialchars($program['program_name']); ?></h3>
                                            
                                            <?php if (!empty($program['department'])): ?>
                                                <div class="program-meta">
                                                    <span><i class="fas fa-building"></i> <?php echo htmlspecialchars($program['department']); ?></span>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <?php if (!empty($program['duration'])): ?>
                                                <div class="program-meta">
                                                    <span><i class="fas fa-clock"></i> <?php echo htmlspecialchars($program['duration']); ?></span>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <p class="program-description">
                                                <?php echo htmlspecialchars(truncateText($program['description'], 120)); ?>
                                            </p>
                                            
                                            <a href="program-detail.php?slug=<?php echo htmlspecialchars($program['slug']); ?>" class="btn btn-outline-primary btn-sm">
                                                View Details <i class="fas fa-arrow-right"></i>
                                            </a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
                
            <?php else: ?>
                <!-- Show filtered level -->
                <?php if (!empty($programs_by_level[$level_filter])): ?>
                    <div class="level-header centered" data-aos="fade-up">
                        <div class="level-icon">
                            <i class="fas <?php echo $levels[$level_filter]['icon']; ?>"></i>
                        </div>
                        <div class="level-info">
                            <h2><?php echo htmlspecialchars($levels[$level_filter]['title']); ?></h2>
                            <p><?php echo htmlspecialchars($levels[$level_filter]['description']); ?></p>
                        </div>
                    </div>
                    
                    <div class="programs-grid">
                        <?php foreach ($programs_by_level[$level_filter] as $index => $program): ?>
                            <div class="program-card" data-aos="fade-up" data-aos-delay="<?php echo $index * 100; ?>">
                                <?php if (!empty($program['featured_image'])): ?>
                                    <div class="program-image">
                                        <img src="<?php echo htmlspecialchars($program['featured_image']); ?>" 
                                             alt="<?php echo htmlspecialchars($program['program_name']); ?>"
                                             onerror="this.src='https://via.placeholder.com/400x250/003f87/ffffff?text=Program'">
                                    </div>
                                <?php endif; ?>
                                
                                <div class="program-content">
                                    <div class="program-code"><?php echo htmlspecialchars($program['program_code']); ?></div>
                                    <h3><?php echo htmlspecialchars($program['program_name']); ?></h3>
                                    
                                    <?php if (!empty($program['department'])): ?>
                                        <div class="program-meta">
                                            <span><i class="fas fa-building"></i> <?php echo htmlspecialchars($program['department']); ?></span>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($program['duration'])): ?>
                                        <div class="program-meta">
                                            <span><i class="fas fa-clock"></i> <?php echo htmlspecialchars($program['duration']); ?></span>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <p class="program-description">
                                        <?php echo htmlspecialchars(truncateText($program['description'], 120)); ?>
                                    </p>
                                    
                                    <a href="program-detail.php?slug=<?php echo htmlspecialchars($program['slug']); ?>" class="btn btn-outline-primary btn-sm">
                                        View Details <i class="fas fa-arrow-right"></i>
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="no-results" data-aos="fade-up">
                        <i class="fas fa-graduation-cap"></i>
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
                <h2>Ready to Start Your Journey?</h2>
                <p>Discover how SMCC can help you achieve your educational goals and career aspirations.</p>
                <div class="cta-buttons">
                    <a href="contact.php" class="btn btn-primary btn-lg">
                        <i class="fas fa-envelope"></i> Get More Information
                    </a>
                    <a href="<?php echo url(); ?>" class="btn btn-outline-primary btn-lg">
                        <i class="fas fa-home"></i> Back to Home
                    </a>
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
/* Programs Filter */
.programs-filter {
    padding-bottom: 0;
}

.filter-tabs {
    display: flex;
    gap: 1rem;
    justify-content: center;
    flex-wrap: wrap;
}

.tab-btn {
    padding: 1rem 2rem;
    background: var(--color-white);
    border: 2px solid var(--color-light-gray);
    border-radius: var(--border-radius-lg);
    color: var(--color-dark-gray);
    font-weight: 600;
    transition: all var(--transition-base);
    box-shadow: var(--shadow-sm);
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.tab-btn:hover,
.tab-btn.active {
    background: var(--color-primary);
    border-color: var(--color-primary);
    color: var(--color-white);
    box-shadow: var(--shadow-md);
    transform: translateY(-2px);
}

/* Program Level Section */
.program-level-section {
    margin-bottom: 4rem;
}

.program-level-section:last-child {
    margin-bottom: 0;
}

.level-header {
    display: flex;
    align-items: center;
    gap: 2rem;
    margin-bottom: 2rem;
    padding: 2rem;
    background: var(--color-white);
    border-radius: var(--border-radius-lg);
    box-shadow: var(--shadow-md);
}

.level-header.centered {
    flex-direction: column;
    text-align: center;
    margin-bottom: 3rem;
}

.level-icon {
    width: 80px;
    height: 80px;
    flex-shrink: 0;
    background: linear-gradient(135deg, var(--color-primary), var(--color-primary-light));
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2.5rem;
    color: var(--color-white);
}

.level-info h2 {
    font-size: 2rem;
    margin-bottom: 0.5rem;
    color: var(--color-primary);
}

.level-info p {
    color: var(--color-gray);
    font-size: 1.125rem;
    margin-bottom: 0;
}

/* Programs Grid */
.programs-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 2rem;
    margin-top: 2rem;
}

.program-card {
    background: var(--color-white);
    border-radius: var(--border-radius-lg);
    overflow: hidden;
    box-shadow: var(--shadow-md);
    transition: all var(--transition-base);
    display: flex;
    flex-direction: column;
}

.program-card:hover {
    transform: translateY(-10px);
    box-shadow: var(--shadow-xl);
}

.program-image {
    height: 200px;
    overflow: hidden;
}

.program-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform var(--transition-slow);
}

.program-card:hover .program-image img {
    transform: scale(1.1);
}

.program-content {
    padding: 1.5rem;
    flex: 1;
    display: flex;
    flex-direction: column;
}

.program-code {
    display: inline-block;
    background: var(--color-secondary);
    color: var(--color-dark-gray);
    padding: 0.25rem 0.75rem;
    border-radius: var(--border-radius-sm);
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
    margin-bottom: 1rem;
    width: fit-content;
}

.program-content h3 {
    font-size: 1.25rem;
    margin-bottom: 1rem;
    color: var(--color-primary);
    line-height: 1.4;
}

.program-meta {
    display: flex;
    gap: 1rem;
    margin-bottom: 0.5rem;
    font-size: 0.875rem;
    color: var(--color-gray);
}

.program-meta i {
    color: var(--color-primary);
}

.program-description {
    color: var(--color-gray);
    line-height: 1.6;
    margin-bottom: 1.5rem;
    flex: 1;
}

.program-content .btn {
    margin-top: auto;
}

/* CTA Section */
.cta-section {
    background: linear-gradient(135deg, var(--color-primary), var(--color-primary-dark));
}

.cta-content {
    text-align: center;
    color: var(--color-white);
    padding: 2rem;
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

.btn-outline-primary {
    background-color: transparent;
    color: var(--color-white);
    border-color: var(--color-white);
}

.btn-outline-primary:hover {
    background-color: var(--color-white);
    color: var(--color-primary);
    border-color: var(--color-white);
}

/* No Results */
.no-results {
    text-align: center;
    padding: 4rem 2rem;
    background: var(--color-white);
    border-radius: var(--border-radius-lg);
    box-shadow: var(--shadow-md);
}

.no-results i {
    font-size: 4rem;
    color: var(--color-gray);
    margin-bottom: 1rem;
}

.no-results h3 {
    margin-bottom: 1rem;
}

.no-results p {
    color: var(--color-gray);
    margin-bottom: 2rem;
}

/* Responsive */
@media (max-width: 768px) {
    .filter-tabs {
        flex-direction: column;
    }
    
    .tab-btn {
        width: 100%;
        justify-content: center;
    }
    
    .level-header {
        flex-direction: column;
        text-align: center;
    }
    
    .level-info h2 {
        font-size: 1.5rem;
    }
    
    .programs-grid {
        grid-template-columns: 1fr;
    }
    
    .cta-content h2 {
        font-size: 1.75rem;
    }
    
    .cta-buttons {
        flex-direction: column;
    }
    
    .cta-buttons .btn {
        width: 100%;
    }
}
</style>
