<?php
/**
 * MARIANCONNECT - Search Results Page
 * Searches across news, events, programs, and pages
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

// Get search query
$search_query = isset($_GET['q']) ? sanitize($_GET['q']) : '';
$results = [];
$total_results = 0;

if (!empty($search_query) && strlen($search_query) >= 3) {
    $search_param = '%' . $search_query . '%';
    
    // Search News
    try {
        $news_stmt = $db->prepare("
            SELECT 'news' as type, news_id as id, title, excerpt as description, slug, published_date as date
            FROM news
            WHERE status = 'published' 
            AND (title LIKE ? OR excerpt LIKE ? OR content LIKE ?)
            ORDER BY published_date DESC
            LIMIT 10
        ");
        $news_stmt->execute([$search_param, $search_param, $search_param]);
        $news_results = $news_stmt->fetchAll();
        $results = array_merge($results, $news_results);
    } catch (Exception $e) {
        error_log("News search error: " . $e->getMessage());
    }
    
    // Search Events
    try {
        $events_stmt = $db->prepare("
            SELECT 'event' as type, event_id as id, title, description, slug, event_date as date
            FROM events
            WHERE (title LIKE ? OR description LIKE ?)
            ORDER BY event_date DESC
            LIMIT 10
        ");
        $events_stmt->execute([$search_param, $search_param]);
        $events_results = $events_stmt->fetchAll();
        $results = array_merge($results, $events_results);
    } catch (Exception $e) {
        error_log("Events search error: " . $e->getMessage());
    }
    
    // Search Programs
    try {
        $programs_stmt = $db->prepare("
            SELECT 'program' as type, program_id as id, program_name as title, description, slug, created_at as date
            FROM academic_programs
            WHERE is_active = 1
            AND (program_name LIKE ? OR description LIKE ? OR program_code LIKE ?)
            ORDER BY program_name ASC
            LIMIT 10
        ");
        $programs_stmt->execute([$search_param, $search_param, $search_param]);
        $programs_results = $programs_stmt->fetchAll();
        $results = array_merge($results, $programs_results);
    } catch (Exception $e) {
        error_log("Programs search error: " . $e->getMessage());
    }
    
    // Search Pages
    try {
        $pages_stmt = $db->prepare("
            SELECT 'page' as type, page_id as id, title, content as description, slug, updated_at as date
            FROM pages
            WHERE is_published = 1
            AND (title LIKE ? OR content LIKE ?)
            ORDER BY updated_at DESC
            LIMIT 10
        ");
        $pages_stmt->execute([$search_param, $search_param]);
        $pages_results = $pages_stmt->fetchAll();
        $results = array_merge($results, $pages_results);
    } catch (Exception $e) {
        error_log("Pages search error: " . $e->getMessage());
    }
    
    $total_results = count($results);
}

$pageTitle = !empty($search_query) ? 'Search Results for "' . htmlspecialchars($search_query) . '" - ' . SITE_NAME : 'Search - ' . SITE_NAME;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    
    <meta name="description" content="Search St. Mary's College of Catbalogan website">
    <meta name="robots" content="noindex, nofollow">
    
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
                <h1 class="page-title">Search Results</h1>
                <nav class="breadcrumb">
                    <a href="<?php echo url(); ?>">Home</a>
                    <span class="separator">/</span>
                    <span class="current">Search</span>
                </nav>
            </div>
        </div>
    </section>
    
    <!-- Search Section -->
    <section class="search-section section-padding">
        <div class="container">
            <!-- Search Form -->
            <div class="search-form-wrapper" data-aos="fade-up">
                <form action="" method="GET" class="search-form-large">
                    <div class="search-input-group">
                        <i class="fas fa-search"></i>
                        <input type="text" 
                               name="q" 
                               class="search-input" 
                               placeholder="Search for news, events, programs..." 
                               value="<?php echo htmlspecialchars($search_query); ?>"
                               required
                               minlength="3">
                        <button type="submit" class="btn btn-primary">
                            Search
                        </button>
                    </div>
                    <p class="search-hint">Enter at least 3 characters to search</p>
                </form>
            </div>
            
            <?php if (!empty($search_query)): ?>
                <!-- Results Summary -->
                <div class="results-summary" data-aos="fade-up">
                    <?php if ($total_results > 0): ?>
                        <h2>Found <?php echo $total_results; ?> result<?php echo $total_results != 1 ? 's' : ''; ?> for "<?php echo htmlspecialchars($search_query); ?>"</h2>
                    <?php else: ?>
                        <h2>No results found for "<?php echo htmlspecialchars($search_query); ?>"</h2>
                    <?php endif; ?>
                </div>
                
                <!-- Search Results -->
                <?php if ($total_results > 0): ?>
                    <div class="search-results">
                        <?php foreach ($results as $index => $result): ?>
                            <article class="result-item" data-aos="fade-up" data-aos-delay="<?php echo ($index % 5) * 100; ?>">
                                <div class="result-type">
                                    <?php
                                    $type_icons = [
                                        'news' => 'fa-newspaper',
                                        'event' => 'fa-calendar-alt',
                                        'program' => 'fa-graduation-cap',
                                        'page' => 'fa-file-alt'
                                    ];
                                    $type_labels = [
                                        'news' => 'News',
                                        'event' => 'Event',
                                        'program' => 'Program',
                                        'page' => 'Page'
                                    ];
                                    ?>
                                    <i class="fas <?php echo $type_icons[$result['type']] ?? 'fa-file'; ?>"></i>
                                    <span><?php echo $type_labels[$result['type']] ?? ucfirst($result['type']); ?></span>
                                </div>
                                
                                <h3 class="result-title">
                                    <?php
                                    $url_map = [
                                        'news' => 'news-detail.php?slug=',
                                        'event' => 'event-detail.php?slug=',
                                        'program' => 'program-detail.php?slug=',
                                        'page' => $result['slug'] . '.php'
                                    ];
                                    $url = $url_map[$result['type']] ?? '#';
                                    if ($result['type'] !== 'page') {
                                        $url .= $result['slug'];
                                    }
                                    ?>
                                    <a href="<?php echo htmlspecialchars($url); ?>">
                                        <?php echo htmlspecialchars($result['title']); ?>
                                    </a>
                                </h3>
                                
                                <p class="result-description">
                                    <?php echo htmlspecialchars(truncateText(strip_tags($result['description']), 200)); ?>
                                </p>
                                
                                <div class="result-meta">
                                    <span class="result-date">
                                        <i class="fas fa-calendar"></i>
                                        <?php echo formatDate($result['date']); ?>
                                    </span>
                                    <a href="<?php echo htmlspecialchars($url); ?>" class="result-link">
                                        Read More <i class="fas fa-arrow-right"></i>
                                    </a>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <!-- No Results -->
                    <div class="no-results" data-aos="fade-up">
                        <i class="fas fa-search"></i>
                        <h3>No Results Found</h3>
                        <p>We couldn't find anything matching your search. Try:</p>
                        <ul class="search-tips">
                            <li>Using different keywords</li>
                            <li>Checking your spelling</li>
                            <li>Using more general terms</li>
                            <li>Using fewer keywords</li>
                        </ul>
                    </div>
                <?php endif; ?>
            <?php elseif (isset($_GET['q'])): ?>
                <!-- Empty Query -->
                <div class="no-results" data-aos="fade-up">
                    <i class="fas fa-exclamation-circle"></i>
                    <h3>Please Enter a Search Term</h3>
                    <p>Enter at least 3 characters to start searching.</p>
                </div>
            <?php endif; ?>
        </div>
    </section>
    
    <!-- Popular Pages -->
    <section class="popular-pages section-padding bg-light">
        <div class="container">
            <div class="section-header text-center" data-aos="fade-up">
                <h2 class="section-title">Popular Pages</h2>
                <p class="section-subtitle">Quick links to frequently visited pages</p>
            </div>
            
            <div class="row g-4">
                <div class="col-md-3" data-aos="fade-up">
                    <a href="about.php" class="popular-link">
                        <i class="fas fa-university"></i>
                        <span>About SMCC</span>
                    </a>
                </div>
                <div class="col-md-3" data-aos="fade-up" data-aos-delay="100">
                    <a href="programs.php" class="popular-link">
                        <i class="fas fa-graduation-cap"></i>
                        <span>Programs</span>
                    </a>
                </div>
                <div class="col-md-3" data-aos="fade-up" data-aos-delay="200">
                    <a href="news.php" class="popular-link">
                        <i class="fas fa-newspaper"></i>
                        <span>News</span>
                    </a>
                </div>
                <div class="col-md-3" data-aos="fade-up" data-aos-delay="300">
                    <a href="contact.php" class="popular-link">
                        <i class="fas fa-envelope"></i>
                        <span>Contact Us</span>
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
        
        // Auto-focus search input
        document.querySelector('.search-input')?.focus();
    </script>
</body>
</html>

<style>
/* Search Form */
.search-form-wrapper {
    max-width: 800px;
    margin: 0 auto 3rem;
}

.search-input-group {
    display: flex;
    align-items: center;
    background: var(--color-white);
    border: 3px solid var(--color-primary);
    border-radius: var(--border-radius-xl);
    padding: 0.5rem 0.5rem 0.5rem 1.5rem;
    box-shadow: var(--shadow-lg);
}

.search-input-group i {
    color: var(--color-primary);
    font-size: 1.5rem;
    margin-right: 1rem;
}

.search-input {
    flex: 1;
    border: none;
    padding: 1rem;
    font-size: 1.125rem;
    outline: none;
}

.search-input-group .btn {
    padding: 1rem 2rem;
    border-radius: var(--border-radius-lg);
    white-space: nowrap;
}

.search-hint {
    text-align: center;
    color: var(--color-gray);
    margin-top: 1rem;
    font-size: 0.9375rem;
}

/* Results Summary */
.results-summary {
    margin-bottom: 2rem;
}

.results-summary h2 {
    font-size: 1.75rem;
    color: var(--color-primary);
}

/* Search Results */
.search-results {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.result-item {
    background: var(--color-white);
    padding: 2rem;
    border-radius: var(--border-radius-lg);
    box-shadow: var(--shadow-md);
    transition: all var(--transition-base);
}

.result-item:hover {
    transform: translateY(-5px);
    box-shadow: var(--shadow-xl);
}

.result-type {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.375rem 0.875rem;
    background: var(--color-primary);
    color: var(--color-white);
    border-radius: var(--border-radius-md);
    font-size: 0.875rem;
    font-weight: 600;
    text-transform: uppercase;
    margin-bottom: 1rem;
}

.result-title {
    font-size: 1.5rem;
    margin-bottom: 0.75rem;
}

.result-title a {
    color: var(--color-primary);
    transition: color var(--transition-base);
}

.result-title a:hover {
    color: var(--color-primary-light);
}

.result-description {
    color: var(--color-gray);
    line-height: 1.6;
    margin-bottom: 1rem;
}

.result-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 1rem;
    border-top: 1px solid var(--color-light-gray);
}

.result-date {
    color: var(--color-gray);
    font-size: 0.9375rem;
}

.result-date i {
    color: var(--color-primary);
    margin-right: 0.25rem;
}

.result-link {
    color: var(--color-primary);
    font-weight: 600;
    transition: all var(--transition-base);
}

.result-link:hover {
    color: var(--color-primary-light);
    transform: translateX(5px);
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
    margin-bottom: 1.5rem;
}

.search-tips {
    list-style: none;
    padding: 0;
    max-width: 400px;
    margin: 0 auto;
    text-align: left;
}

.search-tips li {
    padding: 0.5rem 0;
    color: var(--color-gray);
    position: relative;
    padding-left: 1.5rem;
}

.search-tips li:before {
    content: "•";
    position: absolute;
    left: 0;
    color: var(--color-primary);
    font-weight: 700;
}

/* Popular Pages */
.popular-link {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 1rem;
    padding: 2rem;
    background: var(--color-white);
    border-radius: var(--border-radius-lg);
    box-shadow: var(--shadow-md);
    transition: all var(--transition-base);
    text-align: center;
}

.popular-link:hover {
    transform: translateY(-10px);
    box-shadow: var(--shadow-xl);
    background: var(--color-primary);
    color: var(--color-white);
}

.popular-link i {
    font-size: 3rem;
    color: var(--color-primary);
    transition: color var(--transition-base);
}

.popular-link:hover i {
    color: var(--color-white);
}

.popular-link span {
    font-size: 1.125rem;
    font-weight: 600;
    color: var(--color-dark-gray);
    transition: color var(--transition-base);
}

.popular-link:hover span {
    color: var(--color-white);
}

/* Responsive */
@media (max-width: 768px) {
    .search-input-group {
        flex-direction: column;
        padding: 1rem;
    }
    
    .search-input-group i {
        display: none;
    }
    
    .search-input-group .btn {
        width: 100%;
        margin-top: 1rem;
    }
    
    .result-meta {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
    }
}
</style>
