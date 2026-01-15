<?php
/**
 * MARIANCONNECT - Main Navigation Menu
 * Generates dynamic navigation with active page detection
 */

// Get current page for active menu highlighting
$current_page = basename($_SERVER['PHP_SELF'], '.php');
$current_folder = basename(dirname($_SERVER['PHP_SELF']));

/**
 * Check if a menu item is active
 */
function isActive($page_slugs, $current_page, $current_folder = '') {
    if (is_array($page_slugs)) {
        return in_array($current_page, $page_slugs) || in_array($current_folder, $page_slugs) ? 'active' : '';
    }
    return ($current_page === $page_slugs || $current_folder === $page_slugs) ? 'active' : '';
}

/**
 * Get base URL
 */
function getBaseUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $script = dirname($_SERVER['SCRIPT_NAME']);
    $baseUrl = $protocol . '://' . $host . $script;
    return rtrim($baseUrl, '/') . '/';
}

$base_url = getBaseUrl();
?>

<!-- Main Navigation Menu -->
<ul class="navbar-nav">
    <!-- Home -->
    <li class="nav-item <?php echo isActive('index', $current_page); ?>">
        <a href="<?php echo $base_url; ?>" class="nav-link">
            <i class="fas fa-home"></i> Home
        </a>
    </li>
    
    <!-- About Us Dropdown -->
    <li class="nav-item dropdown <?php echo isActive(['about', 'mission-vision', 'history', 'administration'], $current_page); ?>">
        <a href="#" class="nav-link dropdown-toggle">
            <i class="fas fa-info-circle"></i> About Us
        </a>
        <ul class="dropdown-menu">
            <li>
                <a href="<?php echo $base_url; ?>pages/about.php" class="<?php echo isActive('about', $current_page); ?>">
                    <i class="fas fa-university"></i> About SMCC
                </a>
            </li>
            <li>
                <a href="<?php echo $base_url; ?>pages/mission-vision.php" class="<?php echo isActive('mission-vision', $current_page); ?>">
                    <i class="fas fa-eye"></i> Mission & Vision
                </a>
            </li>
            <li>
                <a href="<?php echo $base_url; ?>pages/history.php" class="<?php echo isActive('history', $current_page); ?>">
                    <i class="fas fa-history"></i> History
                </a>
            </li>
            <li>
                <a href="<?php echo $base_url; ?>pages/administration.php" class="<?php echo isActive('administration', $current_page); ?>">
                    <i class="fas fa-users-cog"></i> Administration
                </a>
            </li>
        </ul>
    </li>
    
    <!-- Academic Programs -->
    <li class="nav-item <?php echo isActive('programs', $current_page); ?>">
        <a href="<?php echo $base_url; ?>pages/programs.php" class="nav-link">
            <i class="fas fa-graduation-cap"></i> Programs
        </a>
    </li>
    
    <!-- Campus Life Dropdown -->
    <li class="nav-item dropdown <?php echo isActive(['facilities', 'organizations', 'achievements'], $current_page); ?>">
        <a href="#" class="nav-link dropdown-toggle">
            <i class="fas fa-school"></i> Campus Life
        </a>
        <ul class="dropdown-menu">
            <li>
                <a href="<?php echo $base_url; ?>pages/facilities.php" class="<?php echo isActive('facilities', $current_page); ?>">
                    <i class="fas fa-building"></i> Facilities
                </a>
            </li>
            <li>
                <a href="<?php echo $base_url; ?>pages/organizations.php" class="<?php echo isActive('organizations', $current_page); ?>">
                    <i class="fas fa-users"></i> Student Organizations
                </a>
            </li>
            <li>
                <a href="<?php echo $base_url; ?>pages/achievements.php" class="<?php echo isActive('achievements', $current_page); ?>">
                    <i class="fas fa-trophy"></i> Achievements
                </a>
            </li>
        </ul>
    </li>
    
    <!-- News -->
    <li class="nav-item <?php echo isActive(['news', 'news-detail'], $current_page); ?>">
        <a href="<?php echo $base_url; ?>pages/news.php" class="nav-link">
            <i class="fas fa-newspaper"></i> News
        </a>
    </li>
    
    <!-- Events -->
    <li class="nav-item <?php echo isActive(['events', 'event-detail'], $current_page); ?>">
        <a href="<?php echo $base_url; ?>pages/events.php" class="nav-link">
            <i class="fas fa-calendar-alt"></i> Events
        </a>
    </li>
    
    <!-- Gallery -->
    <li class="nav-item <?php echo isActive('gallery', $current_page); ?>">
        <a href="<?php echo $base_url; ?>pages/gallery.php" class="nav-link">
            <i class="fas fa-images"></i> Gallery
        </a>
    </li>
    
    <!-- Contact -->
    <li class="nav-item <?php echo isActive('contact', $current_page); ?>">
        <a href="<?php echo $base_url; ?>pages/contact.php" class="nav-link">
            <i class="fas fa-envelope"></i> Contact
        </a>
    </li>
</ul>

<!-- Mobile Navigation Script -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Mobile Dropdown Toggle
    const dropdownToggles = document.querySelectorAll('.dropdown-toggle');
    
    dropdownToggles.forEach(toggle => {
        toggle.addEventListener('click', function(e) {
            if (window.innerWidth <= 768) {
                e.preventDefault();
                const parentItem = this.closest('.nav-item');
                const dropdownMenu = parentItem.querySelector('.dropdown-menu');
                
                // Close other dropdowns
                document.querySelectorAll('.nav-item.dropdown').forEach(item => {
                    if (item !== parentItem) {
                        item.classList.remove('active');
                        const menu = item.querySelector('.dropdown-menu');
                        if (menu) menu.style.display = 'none';
                    }
                });
                
                // Toggle current dropdown
                parentItem.classList.toggle('active');
                if (dropdownMenu) {
                    dropdownMenu.style.display = dropdownMenu.style.display === 'block' ? 'none' : 'block';
                }
            }
        });
    });
    
    // Close dropdowns when clicking outside
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.nav-item.dropdown')) {
            document.querySelectorAll('.nav-item.dropdown').forEach(item => {
                item.classList.remove('active');
                const menu = item.querySelector('.dropdown-menu');
                if (menu) menu.style.display = 'none';
            });
        }
    });
});
</script>
