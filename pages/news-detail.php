<?php
/**
 * MARIANCONNECT - News Detail Page
 * Displays a single news article with full content
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
    header("Location: news.php");
    exit;
}

// Fetch news article
try {
    $stmt = $db->prepare("
        SELECT n.*, a.full_name as author_name, a.avatar as author_avatar
        FROM news n
        JOIN admin_users a ON n.author_id = a.admin_id
        WHERE n.slug = ? AND n.status = 'published'
        LIMIT 1
    ");
    $stmt->execute([$slug]);
    $news = $stmt->fetch();

    if (!$news) {
        header("Location: news.php");
        exit;
    }

    // Fix author avatar path
    if (!empty($news['author_avatar'])) {
        $news['author_avatar'] = url($news['author_avatar']);
    }

    // Update view count
    $update_stmt = $db->prepare("UPDATE news SET views = views + 1 WHERE news_id = ?");
    $update_stmt->execute([$news['news_id']]);
    
} catch (Exception $e) {
    error_log("News fetch error: " . $e->getMessage());
    header("Location: news.php");
    exit;
}

// Get related news (same category)
try {
    $related_stmt = $db->prepare("
        SELECT n.*, a.full_name as author_name
        FROM news n
        JOIN admin_users a ON n.author_id = a.admin_id
        WHERE n.category = ? AND n.slug != ? AND n.status = 'published'
        ORDER BY n.published_date DESC
        LIMIT 3
    ");
    $related_stmt->execute([$news['category'], $slug]);
    $related_news = $related_stmt->fetchAll();
} catch (Exception $e) {
    $related_news = [];
}

// Get recent news for sidebar
try {
    $recent_stmt = $db->query("
        SELECT n.*, a.full_name as author_name
        FROM news n
        JOIN admin_users a ON n.author_id = a.admin_id
        WHERE n.status = 'published' AND n.slug != '$slug'
        ORDER BY n.published_date DESC
        LIMIT 5
    ");
    $recent_news = $recent_stmt->fetchAll();
} catch (Exception $e) {
    $recent_news = [];
}

$pageTitle = htmlspecialchars($news['title']) . ' - News - ' . SITE_NAME;
$metaDescription = htmlspecialchars($news['excerpt'] ?? truncateText(strip_tags($news['content']), 160));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    
    <!-- SEO Meta Tags -->
    <meta name="description" content="<?php echo $metaDescription; ?>">
    <meta name="keywords" content="SMCC News, <?php echo htmlspecialchars($news['category']); ?>, <?php echo htmlspecialchars($news['title']); ?>">
    <meta name="author" content="<?php echo htmlspecialchars($news['author_name']); ?>">
    
    <!-- Open Graph -->
    <meta property="og:type" content="article">
    <meta property="og:title" content="<?php echo htmlspecialchars($news['title']); ?>">
    <meta property="og:description" content="<?php echo $metaDescription; ?>">
    <meta property="og:image" content="<?php echo !empty($news['featured_image']) ? htmlspecialchars($news['featured_image']) : asset('images/logo/logo-main.png'); ?>">
    <meta property="article:published_time" content="<?php echo date('c', strtotime($news['published_date'] ?? $news['created_at'])); ?>">
    <meta property="article:author" content="<?php echo htmlspecialchars($news['author_name']); ?>">
    
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

    <!-- Main Content -->
    <section class="page-content section-padding">
        <div class="container">
            <div class="row">
                <!-- Main Article -->
                <div class="col-md-8">
                    <article class="news-article" data-aos="fade-up">
                        <!-- Article Header -->
                        <header class="article-header">
                            <div class="article-category">
                                <span class="category-badge"><?php echo htmlspecialchars(ucfirst($news['category'])); ?></span>
                            </div>
                            <h1 class="article-title"><?php echo htmlspecialchars($news['title']); ?></h1>
                            
                            <div class="article-meta">
                                <div class="meta-item">
                                    <i class="fas fa-calendar"></i>
                                    <span><?php echo formatDate($news['published_date'] ?? $news['created_at'], 'F j, Y'); ?></span>
                                </div>
                                <div class="meta-item">
                                    <i class="fas fa-user"></i>
                                    <span>By <?php echo htmlspecialchars($news['author_name']); ?></span>
                                </div>
                                <div class="meta-item">
                                    <i class="fas fa-eye"></i>
                                    <span><?php echo number_format($news['views']); ?> views</span>
                                </div>
                            </div>
                        </header>
                        
                        <!-- Featured Image -->
                        <?php if (!empty($news['featured_image'])): ?>
                            <div class="article-image">
                                <img src="<?php echo getImageUrl($news['featured_image']); ?>"
                                     alt="<?php echo htmlspecialchars($news['title']); ?>"
                                     onerror="this.src='https://via.placeholder.com/800x500/003f87/ffffff?text=News+Image'">
                            </div>
                        <?php endif; ?>
                        
                        <!-- Article Content -->
                        <div class="article-content">
                            <?php if (!empty($news['excerpt'])): ?>
                                <div class="article-excerpt">
                                    <?php echo htmlspecialchars($news['excerpt']); ?>
                                </div>
                            <?php endif; ?>
                            
                            <div class="article-body">
                                <?php echo $news['content']; ?>
                            </div>
                        </div>
                        
                        <!-- Article Footer -->
                        <footer class="article-footer">
                            <div class="article-share">
                                <span class="share-label">Share this article:</span>
                                <div class="share-buttons">
                                    <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode(url('pages/news-detail.php?slug=' . $news['slug'])); ?>" 
                                       target="_blank" class="share-btn facebook" title="Share on Facebook">
                                        <i class="fab fa-facebook-f"></i>
                                    </a>
                                    <a href="https://twitter.com/intent/tweet?url=<?php echo urlencode(url('pages/news-detail.php?slug=' . $news['slug'])); ?>&text=<?php echo urlencode($news['title']); ?>" 
                                       target="_blank" class="share-btn twitter" title="Share on Twitter">
                                        <i class="fab fa-twitter"></i>
                                    </a>
                                    <a href="https://www.linkedin.com/shareArticle?mini=true&url=<?php echo urlencode(url('pages/news-detail.php?slug=' . $news['slug'])); ?>" 
                                       target="_blank" class="share-btn linkedin" title="Share on LinkedIn">
                                        <i class="fab fa-linkedin-in"></i>
                                    </a>
                                    <a href="mailto:?subject=<?php echo urlencode($news['title']); ?>&body=<?php echo urlencode(url('pages/news-detail.php?slug=' . $news['slug'])); ?>" 
                                       class="share-btn email" title="Share via Email">
                                        <i class="fas fa-envelope"></i>
                                    </a>
                                </div>
                            </div>
                        </footer>
                    </article>
                    
                    <!-- Related News -->
                    <?php if (!empty($related_news)): ?>
                        <section class="related-news" data-aos="fade-up">
                            <h2 class="section-title">Related News</h2>
                            <div class="related-grid">
                                <?php foreach ($related_news as $related): ?>
                                    <article class="related-card">
                                        <?php if (!empty($related['featured_image'])): ?>
                                            <div class="related-image">
                                                <img src="<?php echo getImageUrl($related['featured_image']); ?>"
                                                     alt="<?php echo htmlspecialchars($related['title']); ?>"
                                                     onerror="this.src='https://via.placeholder.com/300x200/003f87/ffffff?text=News'">
                                            </div>
                                        <?php endif; ?>
                                        <div class="related-content">
                                            <span class="related-date">
                                                <i class="fas fa-calendar"></i> <?php echo formatDate($related['published_date'] ?? $related['created_at']); ?>
                                            </span>
                                            <h3>
                                                <a href="news-detail.php?slug=<?php echo htmlspecialchars($related['slug']); ?>">
                                                    <?php echo htmlspecialchars($related['title']); ?>
                                                </a>
                                            </h3>
                                        </div>
                                    </article>
                                <?php endforeach; ?>
                            </div>
                        </section>
                    <?php endif; ?>
                </div>
                
                <!-- Sidebar -->
                <div class="col-md-4">
                    <!-- Author Box -->
                    <div class="sidebar-widget author-box" data-aos="fade-up">
                        <h3 class="widget-title">About the Author</h3>
                        <div class="author-info">
                            <?php if (!empty($news['author_avatar'])): ?>
                                <img src="<?php echo htmlspecialchars($news['author_avatar']); ?>" 
                                     alt="<?php echo htmlspecialchars($news['author_name']); ?>"
                                     class="author-avatar">
                            <?php else: ?>
                                <div class="author-avatar-placeholder">
                                    <i class="fas fa-user"></i>
                                </div>
                            <?php endif; ?>
                            <div class="author-details">
                                <h4><?php echo htmlspecialchars($news['author_name']); ?></h4>
                                <p class="author-role">Content Writer</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Recent News -->
                    <?php if (!empty($recent_news)): ?>
                        <div class="sidebar-widget" data-aos="fade-up" data-aos-delay="100">
                            <h3 class="widget-title">Recent News</h3>
                            <div class="recent-list">
                                <?php foreach ($recent_news as $recent): ?>
                                    <article class="recent-item">
                                        <?php if (!empty($recent['featured_image'])): ?>
                                            <div class="recent-image">
                                                <img src="<?php echo getImageUrl($recent['featured_image']); ?>"
                                                     alt="<?php echo htmlspecialchars($recent['title']); ?>"
                                                     onerror="this.src='https://via.placeholder.com/80x60/003f87/ffffff?text=News'">
                                            </div>
                                        <?php endif; ?>
                                        <div class="recent-content">
                                            <h4>
                                                <a href="news-detail.php?slug=<?php echo htmlspecialchars($recent['slug']); ?>">
                                                    <?php echo htmlspecialchars($recent['title']); ?>
                                                </a>
                                            </h4>
                                            <span class="recent-date">
                                                <i class="fas fa-calendar"></i> <?php echo formatDate($recent['published_date'] ?? $recent['created_at']); ?>
                                            </span>
                                        </div>
                                    </article>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Back to News -->
                    <div class="sidebar-widget cta-widget" data-aos="fade-up" data-aos-delay="200">
                        <h3>More News</h3>
                        <p>Explore more news and updates from SMCC.</p>
                        <a href="news.php" class="btn btn-primary btn-block">View All News</a>
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
/* News Article */
.news-article {
    background: var(--color-white);
    padding: 2.5rem;
    border-radius: var(--border-radius-lg);
    box-shadow: var(--shadow-md);
    margin-bottom: 2rem;
}

/* Article Header */
.article-header {
    margin-bottom: 2rem;
}

.article-category {
    margin-bottom: 1rem;
}

.category-badge {
    display: inline-block;
    padding: 0.5rem 1rem;
    background: linear-gradient(135deg, var(--color-primary), var(--color-primary-light));
    color: var(--color-white);
    border-radius: var(--border-radius-md);
    font-size: 0.875rem;
    font-weight: 600;
    text-transform: uppercase;
}

.article-title {
    font-size: 2.5rem;
    line-height: 1.3;
    margin-bottom: 1.5rem;
    color: var(--color-primary);
}

.article-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 1.5rem;
    padding-top: 1rem;
    border-top: 2px solid var(--color-light-gray);
}

.meta-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: var(--color-gray);
    font-size: 0.95rem;
}

.meta-item i {
    color: var(--color-primary);
}

/* Article Image */
.article-image {
    margin-bottom: 2rem;
    border-radius: var(--border-radius-lg);
    overflow: hidden;
}

.article-image img {
    width: 100%;
    height: auto;
    display: block;
}

/* Article Content */
.article-excerpt {
    font-size: 1.125rem;
    font-style: italic;
    color: var(--color-gray);
    padding: 1.5rem;
    background: var(--color-off-white);
    border-left: 4px solid var(--color-primary);
    margin-bottom: 2rem;
    border-radius: var(--border-radius-md);
}

.article-body {
    line-height: 1.8;
    color: var(--color-dark-gray);
    font-size: 1.0625rem;
}

.article-body p {
    margin-bottom: 1.5rem;
}

.article-body h2,
.article-body h3 {
    margin-top: 2rem;
    margin-bottom: 1rem;
    color: var(--color-primary);
}

.article-body img {
    max-width: 100%;
    height: auto;
    border-radius: var(--border-radius-md);
    margin: 1.5rem 0;
}

/* Article Footer */
.article-footer {
    margin-top: 3rem;
    padding-top: 2rem;
    border-top: 2px solid var(--color-light-gray);
}

.article-share {
    display: flex;
    align-items: center;
    gap: 1rem;
    flex-wrap: wrap;
}

.share-label {
    font-weight: 600;
    color: var(--color-dark-gray);
}

.share-buttons {
    display: flex;
    gap: 0.75rem;
}

.share-btn {
    width: 45px;
    height: 45px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    color: var(--color-white);
    font-size: 1.125rem;
    transition: all var(--transition-base);
}

.share-btn:hover {
    transform: translateY(-3px);
    box-shadow: var(--shadow-lg);
}

.share-btn.facebook { background-color: #3b5998; }
.share-btn.twitter { background-color: #1da1f2; }
.share-btn.linkedin { background-color: #0077b5; }
.share-btn.email { background-color: var(--color-gray); }

/* Related News */
.related-news {
    margin-top: 3rem;
}

.related-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 1.5rem;
    margin-top: 2rem;
}

.related-card {
    background: var(--color-white);
    border-radius: var(--border-radius-lg);
    overflow: hidden;
    box-shadow: var(--shadow-md);
    transition: all var(--transition-base);
}

.related-card:hover {
    transform: translateY(-5px);
    box-shadow: var(--shadow-xl);
}

.related-image {
    height: 180px;
    overflow: hidden;
}

.related-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform var(--transition-slow);
}

.related-card:hover .related-image img {
    transform: scale(1.1);
}

.related-content {
    padding: 1rem;
}

.related-date {
    font-size: 0.875rem;
    color: var(--color-gray);
    margin-bottom: 0.5rem;
    display: block;
}

.related-content h3 {
    font-size: 1rem;
    line-height: 1.4;
    margin: 0;
}

.related-content a {
    color: var(--color-dark-gray);
    transition: color var(--transition-base);
}

.related-content a:hover {
    color: var(--color-primary);
}

/* ============================================
   IMPROVED SIDEBAR WIDGETS - NEWS PAGE
   Better contrast and modern card design
   ============================================ */

.sidebar-widget {
    background: var(--color-white);
    padding: 2rem;
    border-radius: var(--border-radius-lg);
    box-shadow: var(--shadow-md);
    margin-bottom: 2rem;
    border: 1px solid var(--color-light-gray);
}

.widget-title {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--color-primary);
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 3px solid var(--color-primary);
}

/* Author Box - Modern Light Design */
.author-box {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border-left: 5px solid var(--color-primary);
}

.author-box .widget-title {
    color: var(--color-primary);
    border-color: var(--color-primary);
}

.author-info {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    background: var(--color-white);
    border-radius: var(--border-radius-md);
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
}

.author-avatar {
    width: 70px;
    height: 70px;
    border-radius: 50%;
    object-fit: cover;
    border: 3px solid var(--color-primary);
}

.author-avatar-placeholder {
    width: 70px;
    height: 70px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--color-primary), var(--color-primary-light));
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    border: 3px solid var(--color-primary);
    color: var(--color-white);
}

.author-details h4 {
    margin: 0 0 0.25rem 0;
    color: var(--color-primary);
    font-size: 1.125rem;
}

.author-role {
    margin: 0;
    color: var(--color-gray);
    font-size: 0.875rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* Recent News List */
.recent-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.recent-item {
    display: flex;
    gap: 1rem;
    padding: 1rem;
    background: var(--color-off-white);
    border-radius: var(--border-radius-md);
    transition: all var(--transition-base);
    border-left: 3px solid transparent;
}

.recent-item:hover {
    background: var(--color-white);
    border-left-color: var(--color-primary);
    transform: translateX(5px);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.recent-image {
    flex-shrink: 0;
    width: 80px;
    height: 60px;
    border-radius: var(--border-radius-md);
    overflow: hidden;
}

.recent-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform var(--transition-base);
}

.recent-item:hover .recent-image img {
    transform: scale(1.1);
}

.recent-content {
    flex: 1;
}

.recent-content h4 {
    font-size: 0.9375rem;
    line-height: 1.4;
    margin-bottom: 0.5rem;
}

.recent-content a {
    color: var(--color-dark-gray);
    transition: color var(--transition-base);
}

.recent-content a:hover {
    color: var(--color-primary);
}

.recent-date {
    font-size: 0.8125rem;
    color: var(--color-gray);
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

.recent-date i {
    font-size: 0.75rem;
}

/* CTA Widget */
.cta-widget {
    background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
    border-left: 5px solid #2196f3;
    text-align: center;
}

.cta-widget h3 {
    font-size: 1.5rem;
    color: #1976d2;
    margin-bottom: 1rem;
    border: none;
    padding: 0;
}

.cta-widget p {
    color: var(--color-dark-gray);
    margin-bottom: 1.5rem;
    line-height: 1.6;
}

/* Button Styles */
.btn-block {
    display: block;
    width: 100%;
    text-align: center;
}

.btn-primary {
    background: linear-gradient(135deg, var(--color-primary), var(--color-primary-dark));
    color: var(--color-white);
    padding: 0.75rem 1.5rem;
    border-radius: var(--border-radius-md);
    font-weight: 600;
    transition: all var(--transition-base);
    border: none;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 63, 135, 0.3);
}

/* Responsive */
@media (max-width: 768px) {
    .news-article {
        padding: 1.5rem;
    }
    
    .article-title {
        font-size: 1.75rem;
    }
    
    .related-grid {
        grid-template-columns: 1fr;
    }
    
    .article-share {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .sidebar-widget {
        padding: 1.5rem;
    }
    
    .widget-title {
        font-size: 1.25rem;
    }
    
    .author-avatar,
    .author-avatar-placeholder {
        width: 60px;
        height: 60px;
    }
    
    .recent-image {
        width: 70px;
        height: 55px;
    }
}
</style>
