<?php
/**
 * MARIANCONNECT - Main Header (FIXED VERSION)
 */

if (!defined('SITE_NAME')) {
    require_once __DIR__ . '/../config/settings.php';
}

// Get current page for active menu highlighting
$current_page = basename($_SERVER['PHP_SELF'], '.php');
?>

<!-- Top Bar -->
<div class="top-bar">
    <div class="container">
        <div class="top-bar-content">
            <div class="top-bar-left">
                <div class="contact-info">
                    <span><i class="fas fa-envelope"></i> <?php echo htmlspecialchars(getSiteSetting('contact_email', 'info@smcc.edu.ph')); ?></span>
                </div>
            </div>
            <div class="top-bar-right">
                <div class="social-links">
                    <?php
                    $facebook = getSiteSetting('facebook_url', FACEBOOK_URL);
                    $twitter = getSiteSetting('twitter_url', TWITTER_URL);
                    $instagram = getSiteSetting('instagram_url', INSTAGRAM_URL);
                    $youtube = getSiteSetting('youtube_url', YOUTUBE_URL);
                    
                    if (!empty($facebook)): ?>
                        <a href="<?php echo htmlspecialchars($facebook); ?>" target="_blank" rel="noopener" title="Facebook">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                    <?php endif;
                    
                    if (!empty($twitter)): ?>
                        <a href="<?php echo htmlspecialchars($twitter); ?>" target="_blank" rel="noopener" title="Twitter">
                            <i class="fab fa-twitter"></i>
                        </a>
                    <?php endif;
                    
                    if (!empty($instagram)): ?>
                        <a href="<?php echo htmlspecialchars($instagram); ?>" target="_blank" rel="noopener" title="Instagram">
                            <i class="fab fa-instagram"></i>
                        </a>
                    <?php endif;
                    
                    if (!empty($youtube)): ?>
                        <a href="<?php echo htmlspecialchars($youtube); ?>" target="_blank" rel="noopener" title="YouTube">
                            <i class="fab fa-youtube"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Main Navigation -->
<header class="main-header">
    <nav class="navbar">
        <div class="container">
            <div class="navbar-content">
                <!-- Logo -->
                <div class="navbar-brand">
                    <a href="<?php echo url(); ?>">
                        <img src="<?php echo asset('images/logo/logo-main.png'); ?>" alt="<?php echo htmlspecialchars(SITE_NAME); ?>" class="logo">
                        <div class="brand-text">
                            <span class="brand-name"><?php echo htmlspecialchars(SITE_NAME); ?></span>
                            <span class="brand-tagline"><?php echo htmlspecialchars(SITE_TAGLINE); ?></span>
                        </div>
                    </a>
                </div>

                <!-- Mobile Toggle -->
                <button class="navbar-toggle" id="navbarToggle" aria-label="Toggle navigation">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>

                <!-- Navigation Menu -->
                <div class="navbar-menu" id="navbarMenu">
                    <ul class="navbar-nav">
                        <li class="nav-item <?php echo $current_page === 'index' ? 'active' : ''; ?>">
                            <a href="<?php echo url(); ?>" class="nav-link">Home</a>
                        </li>
                        
                        <li class="nav-item dropdown <?php echo in_array($current_page, ['about', 'mission-vision', 'history', 'administration']) ? 'active' : ''; ?>">
                            <a href="#" class="nav-link dropdown-toggle">About</a>
                            <ul class="dropdown-menu">
                                <li><a href="<?php echo url('pages/about.php'); ?>">About SMCC</a></li>
                                <li><a href="<?php echo url('pages/mission-vision.php'); ?>">Mission & Vision</a></li>
                                <li><a href="<?php echo url('pages/history.php'); ?>">History</a></li>
                                <li><a href="<?php echo url('pages/administration.php'); ?>">Administration</a></li>
                            </ul>
                        </li>
                        
                        <li class="nav-item <?php echo $current_page === 'programs' ? 'active' : ''; ?>">
                            <a href="<?php echo url('pages/programs.php'); ?>" class="nav-link">Programs</a>
                        </li>
                        
                        <li class="nav-item dropdown <?php echo in_array($current_page, ['facilities', 'organizations', 'achievements']) ? 'active' : ''; ?>">
                            <a href="#" class="nav-link dropdown-toggle">Campus</a>
                            <ul class="dropdown-menu">
                                <li><a href="<?php echo url('pages/facilities.php'); ?>">Facilities</a></li>
                                <li><a href="<?php echo url('pages/organizations.php'); ?>">Organizations</a></li>
                                <li><a href="<?php echo url('pages/achievements.php'); ?>">Achievements</a></li>
                            </ul>
                        </li>
                        
                        <li class="nav-item <?php echo $current_page === 'news' ? 'active' : ''; ?>">
                            <a href="<?php echo url('pages/news.php'); ?>" class="nav-link">News</a>
                        </li>
                        
                        <li class="nav-item <?php echo $current_page === 'events' ? 'active' : ''; ?>">
                            <a href="<?php echo url('pages/events.php'); ?>" class="nav-link">Events</a>
                        </li>
                        
                        <li class="nav-item <?php echo $current_page === 'gallery' ? 'active' : ''; ?>">
                            <a href="<?php echo url('pages/gallery.php'); ?>" class="nav-link">Gallery</a>
                        </li>
                        
                        <li class="nav-item <?php echo $current_page === 'contact' ? 'active' : ''; ?>">
                            <a href="<?php echo url('pages/contact.php'); ?>" class="nav-link">Contact</a>
                        </li>
                    </ul>

                    <!-- Search Button -->
                    <div class="navbar-search">
                        <button class="search-toggle" id="searchToggle" aria-label="Search">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </nav>
</header>

<!-- Search Modal -->
<div class="search-modal" id="searchModal">
    <div class="search-modal-overlay"></div>
    <div class="search-modal-content">
        <button class="search-close" id="searchClose">
            <i class="fas fa-times"></i>
        </button>
        <div class="search-container">
            <h2>Search</h2>
            <form action="<?php echo url('pages/search.php'); ?>" method="GET" class="search-form">
                <div class="search-input-wrapper">
                    <i class="fas fa-search"></i>
                    <input type="text" name="q" placeholder="Search for news, events, programs..." class="search-input" autofocus minlength="3" required>
                </div>
                <button type="submit" class="search-submit">
                    <i class="fas fa-search"></i> Search
                </button>
            </form>
        </div>
    </div>
</div>

<style>
/* Top Bar Styles */
.top-bar {
    background-color: var(--color-primary-dark);
    color: var(--color-white);
    padding: 0.5rem 0;
    font-size: 0.875rem;
}

.top-bar-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.contact-info {
    display: flex;
    gap: 1.5rem;
}

.contact-info span {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.social-links {
    display: flex;
    gap: 1rem;
}

.social-links a {
    color: var(--color-white);
    transition: color var(--transition-base);
}

.social-links a:hover {
    color: var(--color-secondary);
}

/* Main Header Styles */
.main-header {
    background-color: var(--color-white);
    box-shadow: var(--shadow-md);
    position: sticky;
    top: 0;
    z-index: 1000;
}

.navbar-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 0;
}

.navbar-brand a {
    display: flex;
    align-items: center;
    gap: 1rem;
    text-decoration: none;
}

.navbar-brand .logo {
    height: 60px;
    width: auto;
}

.brand-text {
    display: flex;
    flex-direction: column;
}

.brand-name {
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--color-primary);
    line-height: 1.2;
}

.brand-tagline {
    font-size: 0.875rem;
    color: var(--color-gray);
}

/* Navigation Menu */
.navbar-menu {
    display: flex;
    align-items: center;
    gap: 1.5rem;
}

.navbar-nav {
    display: flex;
    list-style: none;
    gap: 0.25rem;
    margin: 0;
    padding: 0;
}

.nav-item {
    position: relative;
}

.nav-link {
    display: block;
    padding: 0.75rem 1rem;
    color: var(--color-dark-gray);
    font-weight: 500;
    text-decoration: none;
    transition: color var(--transition-base);
    font-size: 0.95rem;
}

.nav-link:hover,
.nav-item.active .nav-link {
    color: var(--color-primary);
}

/* Dropdown Menu */
.dropdown-menu {
    position: absolute;
    top: 100%;
    left: 0;
    background-color: var(--color-white);
    box-shadow: var(--shadow-lg);
    border-radius: var(--border-radius-md);
    min-width: 200px;
    padding: 0.5rem 0;
    list-style: none;
    opacity: 0;
    visibility: hidden;
    transform: translateY(-10px);
    transition: all var(--transition-base);
    z-index: 1001;
}

.nav-item.dropdown:hover .dropdown-menu {
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
}

.dropdown-menu li {
    margin: 0;
}

.dropdown-menu a {
    display: block;
    padding: 0.75rem 1.5rem;
    color: var(--color-dark-gray);
    text-decoration: none;
    transition: all var(--transition-base);
    font-size: 0.9375rem;
}

.dropdown-menu a:hover {
    background-color: var(--color-off-white);
    color: var(--color-primary);
    padding-left: 2rem;
}

/* Search Button */
.navbar-search {
    margin-left: 0.5rem;
}

.search-toggle {
    width: 40px;
    height: 40px;
    border: 2px solid var(--color-primary);
    background: transparent;
    border-radius: 50%;
    color: var(--color-primary);
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    transition: all var(--transition-base);
}

.search-toggle:hover {
    background-color: var(--color-primary);
    color: var(--color-white);
}

/* Mobile Toggle */
.navbar-toggle {
    display: none;
    flex-direction: column;
    gap: 4px;
    background: none;
    border: none;
    cursor: pointer;
    padding: 0.5rem;
}

.navbar-toggle span {
    display: block;
    width: 25px;
    height: 3px;
    background-color: var(--color-primary);
    transition: all var(--transition-base);
}

/* Search Modal */
.search-modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: 2000;
    align-items: center;
    justify-content: center;
}

.search-modal.active {
    display: flex;
}

.search-modal-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.8);
}

.search-modal-content {
    position: relative;
    width: 90%;
    max-width: 600px;
    z-index: 2001;
}

.search-close {
    position: absolute;
    top: -50px;
    right: 0;
    font-size: 2rem;
    color: var(--color-white);
    background: none;
    border: none;
    cursor: pointer;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all var(--transition-base);
}

.search-close:hover {
    color: var(--color-secondary);
    transform: rotate(90deg);
}

.search-container {
    background: var(--color-white);
    padding: 2rem;
    border-radius: var(--border-radius-lg);
}

.search-container h2 {
    margin-bottom: 1.5rem;
    color: var(--color-primary);
}

.search-input-wrapper {
    position: relative;
    margin-bottom: 1rem;
}

.search-input-wrapper i {
    position: absolute;
    left: 1.5rem;
    top: 50%;
    transform: translateY(-50%);
    color: var(--color-gray);
    font-size: 1.25rem;
}

.search-input {
    width: 100%;
    padding: 1.25rem 1.5rem 1.25rem 4rem;
    font-size: 1.125rem;
    border: 2px solid var(--color-light-gray);
    border-radius: var(--border-radius-md);
    transition: border-color var(--transition-base);
}

.search-input:focus {
    outline: none;
    border-color: var(--color-primary);
}

.search-submit {
    width: 100%;
    padding: 1.25rem;
    background-color: var(--color-primary);
    color: var(--color-white);
    border: none;
    border-radius: var(--border-radius-md);
    cursor: pointer;
    font-size: 1.125rem;
    font-weight: 600;
    transition: all var(--transition-base);
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
}

.search-submit:hover {
    background-color: var(--color-primary-dark);
}

/* Responsive Design */
@media (max-width: 768px) {
    .top-bar-content {
        flex-direction: column;
        gap: 0.5rem;
        text-align: center;
    }
    
    .navbar-toggle {
        display: flex;
    }
    
    .navbar-menu {
        display: none;
        position: absolute;
        top: 100%;
        left: 0;
        width: 100%;
        background-color: var(--color-white);
        flex-direction: column;
        padding: 1rem;
        box-shadow: var(--shadow-lg);
        gap: 0;
    }
    
    .navbar-menu.active {
        display: flex;
    }
    
    .navbar-nav {
        flex-direction: column;
        width: 100%;
        gap: 0;
    }
    
    .nav-link {
        padding: 1rem;
        width: 100%;
    }
    
    .dropdown-menu {
        position: static;
        opacity: 1;
        visibility: visible;
        transform: none;
        box-shadow: none;
        display: none;
        padding-left: 1rem;
        background: var(--color-off-white);
    }
    
    .nav-item.dropdown.active .dropdown-menu {
        display: block;
    }
    
    .navbar-search {
        width: 100%;
        margin-left: 0;
        margin-top: 1rem;
    }
    
    .search-toggle {
        width: 100%;
        border-radius: var(--border-radius-md);
        height: 50px;
    }
    
    .brand-name {
        font-size: 1rem;
    }
    
    .brand-tagline {
        font-size: 0.75rem;
    }
}

@media (max-width: 992px) and (min-width: 769px) {
    .navbar-nav {
        gap: 0;
    }
    
    .nav-link {
        padding: 0.75rem 0.75rem;
        font-size: 0.875rem;
    }
}
</style>

<script>
// Mobile Navigation Toggle
document.getElementById('navbarToggle')?.addEventListener('click', function() {
    document.getElementById('navbarMenu').classList.toggle('active');
});

// Mobile Dropdown Toggle
document.querySelectorAll('.nav-item.dropdown > .nav-link').forEach(item => {
    item.addEventListener('click', function(e) {
        if (window.innerWidth <= 768) {
            e.preventDefault();
            this.parentElement.classList.toggle('active');
        }
    });
});

// Search Modal
document.getElementById('searchToggle')?.addEventListener('click', function() {
    document.getElementById('searchModal').classList.add('active');
    setTimeout(() => {
        document.querySelector('.search-input')?.focus();
    }, 100);
});

document.getElementById('searchClose')?.addEventListener('click', function() {
    document.getElementById('searchModal').classList.remove('active');
});

document.querySelector('.search-modal-overlay')?.addEventListener('click', function() {
    document.getElementById('searchModal').classList.remove('active');
});

// Close search modal on escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        document.getElementById('searchModal').classList.remove('active');
    }
});

// Close mobile menu when clicking outside
document.addEventListener('click', function(e) {
    const navbar = document.getElementById('navbarMenu');
    const toggle = document.getElementById('navbarToggle');
    
    if (navbar && toggle && !navbar.contains(e.target) && !toggle.contains(e.target)) {
        navbar.classList.remove('active');
    }
});
</script>
