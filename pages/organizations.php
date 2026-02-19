<?php
/**
 * MARIANCONNECT - Student Organizations Page
 * Displays student clubs and organizations
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

// Filter by category
$category_filter = isset($_GET['category']) ? sanitize($_GET['category']) : '';

// Build query
$where_conditions = ["is_active = 1"];
$params = [];

if (!empty($category_filter)) {
    $where_conditions[] = "category = ?";
    $params[] = $category_filter;
}

$where_clause = 'WHERE ' . implode(' AND ', $where_conditions);

// Fetch organizations
try {
    $sql = "
        SELECT * FROM student_organizations
        $where_clause
        ORDER BY display_order ASC, org_name ASC
    ";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $organizations = $stmt->fetchAll();

} catch (Exception $e) {
    error_log("Organizations query error: " . $e->getMessage());
    $organizations = [];
}

// Categories
$categories = [
    'academic' => ['icon' => 'fa-book', 'label' => 'Academic'],
    'sports' => ['icon' => 'fa-basketball-ball', 'label' => 'Sports'],
    'cultural' => ['icon' => 'fa-theater-masks', 'label' => 'Cultural'],
    'religious' => ['icon' => 'fa-church', 'label' => 'Religious'],
    'service' => ['icon' => 'fa-hands-helping', 'label' => 'Community Service'],
    'special_interest' => ['icon' => 'fa-lightbulb', 'label' => 'Special Interest']
];

$pageTitle = 'Student Organizations - ' . SITE_NAME;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    
    <meta name="description" content="Discover student organizations and clubs at St. Mary's College of Catbalogan">
    <meta name="keywords" content="SMCC Organizations, Student Clubs, Extracurricular Activities">
    
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
       <section class="page-header" style="background: linear-gradient(135deg, rgba(0, 63, 135, 0.7), rgba(0, 40, 85, 0.9)), url('<?php echo asset("images/school header.jpg"); ?>') center/cover no-repeat;">
        <div class="page-header-overlay"></div>
        <div class="container">
            <div class="page-header-content" data-aos="fade-up">
                <h1 class="page-title">Student Organizations</h1>
                <nav class="breadcrumb">
                    <a href="<?php echo url(); ?>">Home</a>
                    <span class="separator">/</span>
                    <span class="current">Organizations</span>
                </nav>
            </div>
        </div>
    </section>
    
    <!-- Intro Section -->
    <section class="organizations-intro section-padding">
        <div class="container">
            <div class="intro-content text-center" data-aos="fade-up">
                <h2>Get Involved in Campus Life</h2>
                <p class="lead">Join one of our many student organizations and clubs to enhance your college experience, develop leadership skills, and make lasting friendships.</p>
            </div>
        </div>
    </section>
    
    <!-- Category Filter -->
    <section class="filter-section section-padding bg-light">
        <div class="container">
            <div class="category-tabs" data-aos="fade-up">
                <a href="?" class="tab-btn <?php echo empty($category_filter) ? 'active' : ''; ?>">
                    <i class="fas fa-th"></i> All Organizations
                </a>
                <?php foreach ($categories as $cat_key => $cat_info): ?>
                    <a href="?category=<?php echo $cat_key; ?>" class="tab-btn <?php echo $category_filter === $cat_key ? 'active' : ''; ?>">
                        <i class="fas <?php echo $cat_info['icon']; ?>"></i> <?php echo $cat_info['label']; ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    
    <!-- Organizations Grid -->
    <section class="organizations-content section-padding">
        <div class="container">
            <?php if (!empty($organizations)): ?>
                <div class="organizations-grid">
                    <?php foreach ($organizations as $index => $org): ?>
                        <div class="org-card" data-aos="fade-up" data-aos-delay="<?php echo $index * 100; ?>">
                            <div class="org-logo">
                                <?php if (!empty($org['logo'])): ?>
                                    <img src="<?php echo getImageUrl($org['logo']); ?>" 
                                         alt="<?php echo htmlspecialchars($org['org_name']); ?>"
                                         onerror="this.style.display='none'; this.parentElement.innerHTML += '<div class=\'org-logo-placeholder\'><i class=\'fas <?php echo $categories[$org['category']]['icon']; ?>\'></i></div>';">
                                <?php else: ?>
                                    <div class="org-logo-placeholder">
                                        <i class="fas <?php echo $categories[$org['category']]['icon']; ?>"></i>
                                    </div>
                                <?php endif; ?>
                                <div class="org-category-badge">
                                    <i class="fas <?php echo $categories[$org['category']]['icon']; ?>"></i>
                                    <?php echo $categories[$org['category']]['label']; ?>
                                </div>
                            </div>
                            
                            <div class="org-content">
                                <h3><?php echo htmlspecialchars($org['org_name']); ?></h3>
                                
                                <?php if (!empty($org['acronym'])): ?>
                                    <div class="org-acronym"><?php echo htmlspecialchars($org['acronym']); ?></div>
                                <?php endif; ?>
                                
                                <?php if (!empty($org['established_year'])): ?>
                                    <div class="org-meta">
                                        <i class="fas fa-calendar"></i> Established <?php echo htmlspecialchars($org['established_year']); ?>
                                    </div>
                                <?php endif; ?>
                                
                                <p class="org-description">
                                    <?php echo htmlspecialchars($org['description']); ?>
                                </p>
                                
                                <div class="org-details">
                                    <?php if (!empty($org['adviser_name'])): ?>
                                        <div class="detail-item">
                                            <i class="fas fa-user-tie"></i>
                                            <div>
                                                <span class="detail-label">Adviser:</span>
                                                <span class="detail-value"><?php echo htmlspecialchars($org['adviser_name']); ?></span>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($org['president_name'])): ?>
                                        <div class="detail-item">
                                            <i class="fas fa-user"></i>
                                            <div>
                                                <span class="detail-label">President:</span>
                                                <span class="detail-value"><?php echo htmlspecialchars($org['president_name']); ?></span>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($org['contact_email'])): ?>
                                        <div class="detail-item">
                                            <i class="fas fa-envelope"></i>
                                            <div>
                                                <span class="detail-label">Contact:</span>
                                                <span class="detail-value">
                                                    <a href="mailto:<?php echo htmlspecialchars($org['contact_email']); ?>">
                                                        <?php echo htmlspecialchars($org['contact_email']); ?>
                                                    </a>
                                                </span>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="no-results" data-aos="fade-up">
                    <i class="fas fa-users"></i>
                    <h3>No Organizations Found</h3>
                    <p>There are currently no organizations in this category.</p>
                    <a href="?" class="btn btn-primary">View All Organizations</a>
                </div>
            <?php endif; ?>
        </div>
    </section>
    
    <!-- Join CTA -->
    <section class="join-cta-section section-padding bg-light">
        <div class="container">
            <div class="join-cta-card" data-aos="fade-up">
                <h2>Ready to Get Involved?</h2>
                <p>Discover your passion and develop your skills by joining student organizations. Contact the Student Affairs Office to learn more about membership and activities.</p>
                <a href="contact.php" class="btn btn-primary btn-lg">
                    <i class="fas fa-envelope"></i> Contact Student Affairs
                </a>
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
/* Organizations Intro */
.organizations-intro {
    padding-bottom: 0;
}

.intro-content h2 {
    font-size: 2.5rem;
    margin-bottom: 1rem;
    color: var(--color-primary);
}

.intro-content .lead {
    font-size: 1.25rem;
    color: var(--color-gray);
    max-width: 900px;
    margin: 0 auto;
}

/* Category Tabs */
.category-tabs {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
    justify-content: center;
}

.tab-btn {
    padding: 1rem 1.5rem;
    background: var(--color-white);
    border: 2px solid var(--color-light-gray);
    border-radius: var(--border-radius-lg);
    color: var(--color-dark-gray);
    font-weight: 600;
    transition: all var(--transition-base);
    text-decoration: none;
}

.tab-btn i {
    display: none;
}

.tab-btn:hover,
.tab-btn.active {
    background: var(--color-primary);
    border-color: var(--color-primary);
    color: var(--color-white);
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
}
/* No Results - Centered */
.no-results {
    max-width: 600px;
    margin: 4rem auto;
    text-align: center;
    padding: 4rem 3rem;
}

.no-results i {
    font-size: 5rem;
    color: var(--color-primary);
    margin-bottom: 1.5rem;
    opacity: 0.7;
}

.no-results h3 {
    font-size: 2rem;
    color: var(--color-primary);
    margin-bottom: 1rem;
    font-weight: 700;
}

.no-results p {
    font-size: 1.125rem;
    color: var(--color-gray);
    margin-bottom: 2rem;
    line-height: 1.6;
}

.no-results .btn {
    padding: 0.875rem 2rem;
    font-size: 1.0625rem;
    font-weight: 600;
}
/* Organizations Grid */
.organizations-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
    gap: 2rem;
}

.org-card {
    background: var(--color-white);
    border-radius: var(--border-radius-lg);
    overflow: hidden;
    box-shadow: var(--shadow-md);
    transition: all var(--transition-base);
}

.org-card:hover {
    transform: translateY(-10px);
    box-shadow: var(--shadow-xl);
}

.org-logo {
    position: relative;
    height: 200px;
    background: linear-gradient(135deg, var(--color-off-white), var(--color-light-gray));
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 2rem;
}

.org-logo img {
    max-width: 150px;
    max-height: 150px;
    object-fit: contain;
}

.org-logo-placeholder {
    width: 120px;
    height: 120px;
    background: var(--color-primary);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 3rem;
    color: var(--color-white);
}

.org-category-badge {
    position: absolute;
    top: 1rem;
    right: 1rem;
    background: var(--color-primary);
    color: var(--color-white);
    padding: 0.5rem 1rem;
    border-radius: var(--border-radius-md);
    font-size: 0.875rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.org-content {
    padding: 1.5rem;
}

.org-content h3 {
    font-size: 1.5rem;
    margin-bottom: 0.5rem;
    color: var(--color-primary);
}

.org-acronym {
    display: inline-block;
    background: var(--color-secondary);
    color: var(--color-dark-gray);
    padding: 0.25rem 0.75rem;
    border-radius: var(--border-radius-sm);
    font-size: 0.875rem;
    font-weight: 700;
    margin-bottom: 1rem;
}

.org-meta {
    font-size: 0.9375rem;
    color: var(--color-gray);
    margin-bottom: 1rem;
}

.org-meta i {
    color: var(--color-primary);
    margin-right: 0.25rem;
}

.org-description {
    color: var(--color-gray);
    line-height: 1.6;
    margin-bottom: 1.5rem;
}

.org-details {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
    padding-top: 1rem;
    border-top: 1px solid var(--color-light-gray);
}

.detail-item {
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
    font-size: 0.9375rem;
}

.detail-item i {
    color: var(--color-primary);
    margin-top: 0.25rem;
}

.detail-label {
    font-weight: 600;
    color: var(--color-dark-gray);
    margin-right: 0.5rem;
}

.detail-value {
    color: var(--color-gray);
}

.detail-value a {
    color: var(--color-primary);
}

.detail-value a:hover {
    text-decoration: underline;
}

/* Join CTA */
.join-cta-card {
    background: linear-gradient(135deg, var(--color-primary), var(--color-primary-dark));
    padding: 4rem 3rem;
    border-radius: var(--border-radius-xl);
    text-align: center;
    color: var(--color-white);
    box-shadow: var(--shadow-xl);
}

.join-cta-card h2 {
    font-size: 2.5rem;
    margin-bottom: 1rem;
    color: var(--color-white);
}

.join-cta-card p {
    font-size: 1.25rem;
    margin-bottom: 2rem;
    opacity: 0.95;
    max-width: 700px;
    margin-left: auto;
    margin-right: auto;
}

.join-cta-card .btn-primary {
    background-color: var(--color-white);
    color: var(--color-primary);
    border-color: var(--color-white);
}

.join-cta-card .btn-primary:hover {
    background-color: var(--color-secondary);
    color: var(--color-dark-gray);
    border-color: var(--color-secondary);
}

/* Responsive */
@media (max-width: 768px) {
    .intro-content h2 {
        font-size: 1.75rem;
    }
    
    .category-tabs {
        flex-direction: column;
    }
    
    .organizations-grid {
        grid-template-columns: 1fr;
    }
    
    .join-cta-card {
        padding: 3rem 2rem;
    }
    
    .join-cta-card h2 {
        font-size: 1.75rem;
    }
}
</style>
