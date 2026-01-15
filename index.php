<?php
/**
 * MARIANCONNECT - Homepage
 */

// Error reporting for debugging (remove in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/database.php';
require_once 'config/settings.php';
require_once 'config/security.php';
require_once 'includes/functions.php';

// Track visitor
try {
    trackVisitor();
} catch (Exception $e) {
    // Silent fail for tracking
    error_log("Visitor tracking error: " . $e->getMessage());
}

// Fetch homepage data
$db = getDB();

// Get active sliders
try {
    $sliders = $db->query("
        SELECT * FROM homepage_sliders 
        WHERE is_active = 1 
        ORDER BY display_order ASC
    ")->fetchAll();
} catch (Exception $e) {
    $sliders = [];
    error_log("Slider query error: " . $e->getMessage());
}

// Get active announcements
try {
    $announcements = $db->query("
        SELECT * FROM announcements
        WHERE is_active = 1 
        AND CURDATE() BETWEEN DATE(start_date) AND DATE(end_date)
        ORDER BY priority DESC, start_date DESC
        LIMIT 5
    ")->fetchAll();
} catch (Exception $e) {
    $announcements = [];
    error_log("Announcements query error: " . $e->getMessage());
}

// Get featured news
try {
    $featuredNews = $db->query("
        SELECT n.*, a.full_name as author_name 
        FROM news n
        JOIN admin_users a ON n.author_id = a.admin_id
        WHERE n.status = 'published' AND n.is_featured = 1
        ORDER BY n.published_date DESC
        LIMIT 3
    ")->fetchAll();
} catch (Exception $e) {
    $featuredNews = [];
    error_log("Featured news query error: " . $e->getMessage());
}

// Get upcoming events
try {
    $upcomingEvents = $db->query("
        SELECT * FROM events 
        WHERE status = 'upcoming' AND event_date >= CURDATE()
        ORDER BY event_date ASC
        LIMIT 3
    ")->fetchAll();
        // Fix image paths for upcoming events
    foreach ($upcomingEvents as &$event) {
        if (!empty($event['featured_image'])) {
            $event['featured_image'] = asset($event['featured_image']);
        }
    }
    unset($event);
} catch (Exception $e) {
    $upcomingEvents = [];
    error_log("Events query error: " . $e->getMessage());
}

// Get site settings
$siteName = getSiteSetting('site_name', 'St. Mary\'s College of Catbalogan');
$siteTagline = getSiteSetting('site_tagline', 'Excellence in Catholic Education');

$pageTitle = $siteName . ' - ' . $siteTagline;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo escapeHtml($pageTitle); ?></title>
    
    <!-- SEO Meta Tags -->
    <meta name="description" content="<?php echo escapeHtml($siteTagline); ?>. Official website of St. Mary's College of Catbalogan, Samar, Philippines.">
    <meta name="keywords" content="SMCC, St. Mary's College, Catbalogan, Catholic School, Education, Samar, Philippines">
    <meta name="author" content="St. Mary's College of Catbalogan">
    
    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:title" content="<?php echo escapeHtml($pageTitle); ?>">
    <meta property="og:description" content="<?php echo escapeHtml($siteTagline); ?>">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="assets/images/logo/favicon.ico">
    
    <!-- Stylesheets -->
    <link rel="stylesheet" href="assets/css/main.css">
    
    <!-- Component stylesheets -->
    <link rel="stylesheet" href="assets/css/components/navbar.css">
    <link rel="stylesheet" href="assets/css/components/footer.css">
    <link rel="stylesheet" href="assets/css/components/cards.css">
    <link rel="stylesheet" href="assets/css/components/forms.css">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&family=Open+Sans:wght@300;400;600&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Swiper CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css">
    
    <!-- AOS CSS -->
    <link rel="stylesheet" href="https://unpkg.com/aos@2.3.1/dist/aos.css">
</head>
<body>
    
    <?php include 'includes/header.php'; ?>
    
    <!-- Hero Slider Section -->
    <section class="hero-slider">
        <?php if (!empty($sliders)): ?>
        <div class="swiper heroSwiper">
            <div class="swiper-wrapper">
                <?php foreach ($sliders as $slider): ?>
                <div class="swiper-slide">
                    <div class="hero-slide" style="background-image: url('<?php echo escapeHtml(getImageUrl($slider['image_path'])); ?>');">
                        <div class="hero-overlay"></div>
                        <div class="hero-content container">
                            <h1 class="hero-title" data-aos="fade-up"><?php echo escapeHtml($slider['title']); ?></h1>
                            <?php if (!empty($slider['subtitle'])): ?>
                            <p class="hero-subtitle" data-aos="fade-up" data-aos-delay="100"><?php echo escapeHtml($slider['subtitle']); ?></p>
                            <?php endif; ?>
                            <?php if (!empty($slider['button_text']) && !empty($slider['button_link'])): ?>
                            <a href="<?php echo escapeHtml($slider['button_link']); ?>" class="btn btn-primary" data-aos="fade-up" data-aos-delay="200">
                                <?php echo escapeHtml($slider['button_text']); ?>
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="swiper-pagination"></div>
            <div class="swiper-button-prev"></div>
            <div class="swiper-button-next"></div>
        </div>
        <?php else: ?>
        <!-- Default Hero when no sliders -->
        <div class="hero-slide" style="background: linear-gradient(135deg, #003f87, #002855);">
            <div class="hero-content container">
                <h1 class="hero-title" data-aos="fade-up" style="color: white;">Welcome to <?php echo escapeHtml($siteName); ?></h1>
                <p class="hero-subtitle" data-aos="fade-up" data-aos-delay="100" style="color: white;"><?php echo escapeHtml($siteTagline); ?></p>
            </div>
        </div>
        <?php endif; ?>
    </section>
    
    <!-- Announcements Ticker -->
    <?php if (!empty($announcements)): ?>
    <section class="announcements-ticker">
        <div class="container">
            <div class="ticker-wrapper">
                <div class="ticker-icon">
                    <i class="fas fa-bullhorn"></i>
                    <span>Announcements</span>
                </div>
                <div class="ticker-content">
                    <div class="ticker-items">
                        <?php foreach ($announcements as $announcement): ?>
                        <div class="ticker-item">
                            <strong><?php echo escapeHtml($announcement['title']); ?>:</strong>
                            <?php echo escapeHtml(truncateText($announcement['content'], 100)); ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <?php endif; ?>
    
    <!-- Welcome Section -->
    <section class="welcome-section section-padding">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6" data-aos="fade-right">
                    <img src="assets/images/president.jpg" alt="School President" class="img-fluid rounded shadow">
                </div>
                <div class="col-md-6" data-aos="fade-left">
                    <h2 class="section-title">Welcome to <?php echo escapeHtml($siteName); ?></h2>
                    <p class="lead"><?php echo escapeHtml($siteTagline); ?></p>
                    <p>St. Mary's College of Catbalogan is a Catholic educational institution committed to providing quality education rooted in Christian values. We nurture students to become competent, service-oriented, and morally upright individuals.</p>
                    <a href="pages/about.php" class="btn btn-outline-primary">Learn More About SMCC</a>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Quick Links Section -->
    <section class="quick-links-section section-padding bg-light">
        <div class="container">
            <div class="row g-4">
                <div class="col-md-4" data-aos="fade-up">
                    <div class="quick-link-card">
                        <div class="card-icon">
                            <i class="fas fa-graduation-cap"></i>
                        </div>
                        <h3>Academic Programs</h3>
                        <p>Explore our comprehensive range of programs from elementary to college levels.</p>
                        <a href="pages/programs.php" class="card-link">View Programs →</a>
                    </div>
                </div>
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="100">
                    <div class="quick-link-card">
                        <div class="card-icon">
                            <i class="fas fa-trophy"></i>
                        </div>
                        <h3>Achievements</h3>
                        <p>Celebrating our students' and faculty's outstanding accomplishments.</p>
                        <a href="pages/achievements.php" class="card-link">See Achievements →</a>
                    </div>
                </div>
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="200">
                    <div class="quick-link-card">
                        <div class="card-icon">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                        <h3>Upcoming Events</h3>
                        <p>Stay updated with our latest events, activities, and schedules.</p>
                        <a href="pages/events.php" class="card-link">View Events →</a>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- News & Updates Section -->
    <?php if (!empty($featuredNews)): ?>
    <section class="news-section section-padding">
        <div class="container">
            <div class="section-header text-center" data-aos="fade-up">
                <h2 class="section-title">News & Updates</h2>
                <p class="section-subtitle">Stay informed with the latest happenings at SMCC</p>
            </div>
            <div class="row g-4">
                <?php foreach ($featuredNews as $index => $news): ?>
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="<?php echo $index * 100; ?>">
                    <article class="news-card">
                        <div class="news-image">
                            <img src="<?php echo getImageUrl($news['featured_image']); ?>" 
                                alt="<?php echo htmlspecialchars($news['title']); ?>"
                                onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                            <div class="no-image-placeholder" style="display: none;">
                                <i class="fas fa-newspaper"></i>
                            </div>
                            <div class="news-category"><?php echo htmlspecialchars(ucfirst($news['category'])); ?></div>
                        </div>
                        <div class="news-content">
                            <div class="news-meta">
                                <span class="news-date"><i class="fas fa-calendar"></i> <?php echo formatDate($news['published_date']); ?></span>
                                <span class="news-author"><i class="fas fa-user"></i> <?php echo escapeHtml($news['author_name']); ?></span>
                            </div>
                            <h3 class="news-title">
                                <a href="pages/news-detail.php?slug=<?php echo escapeHtml($news['slug']); ?>">
                                    <?php echo escapeHtml($news['title']); ?>
                                </a>
                            </h3>
                            <p class="news-excerpt"><?php echo escapeHtml($news['excerpt']); ?></p>
                            <a href="pages/news-detail.php?slug=<?php echo escapeHtml($news['slug']); ?>" class="read-more">
                                Read More →
                            </a>
                        </div>
                    </article>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="text-center mt-5" data-aos="fade-up">
                <a href="pages/news.php" class="btn btn-primary btn-lg">View All News</a>
            </div>
        </div>
    </section>
    <?php else: ?>
    <section class="news-section section-padding">
        <div class="container">
            <div class="section-header text-center" data-aos="fade-up">
                <h2 class="section-title">News & Updates</h2>
                <p class="section-subtitle">Check back soon for the latest news from SMCC</p>
            </div>
        </div>
    </section>
    <?php endif; ?>
    
    <!-- Statistics Counter Section -->
    <section class="stats-section section-padding bg-primary text-white">
        <div class="container">
            <div class="row g-4 text-center">
                <div class="col-md-3" data-aos="fade-up">
                    <div class="stat-item">
                        <div class="stat-number" data-count="75">0</div>
                        <div class="stat-label">Years of Excellence</div>
                    </div>
                </div>
                <div class="col-md-3" data-aos="fade-up" data-aos-delay="100">
                    <div class="stat-item">
                        <div class="stat-number" data-count="3500">0</div>
                        <div class="stat-label">Students</div>
                    </div>
                </div>
                <div class="col-md-3" data-aos="fade-up" data-aos-delay="200">
                    <div class="stat-item">
                        <div class="stat-number" data-count="150">0</div>
                        <div class="stat-label">Faculty Members</div>
                    </div>
                </div>
                <div class="col-md-3" data-aos="fade-up" data-aos-delay="300">
                    <div class="stat-item">
                        <div class="stat-number" data-count="20">0</div>
                        <div class="stat-label">Academic Programs</div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Events Section -->
    <?php if (!empty($upcomingEvents)): ?>
    <section class="events-section section-padding bg-light">
        <div class="container">
            <div class="section-header text-center" data-aos="fade-up">
                <h2 class="section-title">Upcoming Events</h2>
                <p class="section-subtitle">Mark your calendars for these exciting events</p>
            </div>
            <div class="row g-4">
                <?php foreach ($upcomingEvents as $index => $event): ?>
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="<?php echo $index * 100; ?>">
                    <div class="event-card">
                        <div class="event-date">
                            <span class="date-day"><?php echo date('d', strtotime($event['event_date'])); ?></span>
                            <span class="date-month"><?php echo date('M', strtotime($event['event_date'])); ?></span>
                        </div>
                        <div class="event-content">
                            <h4 class="event-title"><?php echo escapeHtml($event['title']); ?></h4>
                            <p class="event-location">
                                <i class="fas fa-map-marker-alt"></i> <?php echo escapeHtml($event['location']); ?>
                            </p>
                            <?php if (!empty($event['event_time'])): ?>
                            <p class="event-time">
                                <i class="fas fa-clock"></i> <?php echo date('g:i A', strtotime($event['event_time'])); ?>
                            </p>
                            <?php endif; ?>
                            <a href="pages/event-detail.php?slug=<?php echo escapeHtml($event['slug']); ?>" class="btn btn-sm btn-outline-primary">
                                View Details
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="text-center mt-5" data-aos="fade-up">
                <a href="pages/events.php" class="btn btn-primary btn-lg">View All Events</a>
            </div>
        </div>
    </section>
    <?php endif; ?>
    
    <?php include 'includes/footer.php'; ?>
    
    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    
    <script>
        // Initialize AOS
        AOS.init({
            duration: 800,
            once: true
        });
        
        // Initialize Hero Swiper (only if sliders exist)
        <?php if (!empty($sliders)): ?>
        const heroSwiper = new Swiper('.heroSwiper', {
            loop: true,
            autoplay: {
                delay: 5000,
                disableOnInteraction: false,
            },
            pagination: {
                el: '.swiper-pagination',
                clickable: true,
            },
            navigation: {
                nextEl: '.swiper-button-next',
                prevEl: '.swiper-button-prev',
            },
        });
        <?php endif; ?>
        
        // Counter animation
        const counters = document.querySelectorAll('.stat-number');
        counters.forEach(counter => {
            const target = +counter.getAttribute('data-count');
            const increment = target / 100;
            
            const updateCounter = () => {
                const current = +counter.innerText;
                if (current < target) {
                    counter.innerText = Math.ceil(current + increment);
                    setTimeout(updateCounter, 20);
                } else {
                    counter.innerText = target;
                }
            };
            
            // Trigger when element is in view
            const observer = new IntersectionObserver(entries => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        updateCounter();
                        observer.unobserve(entry.target);
                    }
                });
            });
            
            observer.observe(counter);
        });
    </script>
    
</body>
</html>
