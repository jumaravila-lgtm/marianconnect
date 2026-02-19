<?php
/**
 * MARIANCONNECT - Admin Sidebar Navigation
 */

// Get current page for active menu (if not already set in header)
if (!isset($current_page)) {
    $current_page = basename($_SERVER['PHP_SELF'], '.php');
}
if (!isset($current_dir)) {
    $current_dir = basename(dirname($_SERVER['PHP_SELF']));
}
?>

<!-- Admin Sidebar -->
<aside class="admin-sidebar" id="adminSidebar">
    <nav class="sidebar-nav">
        <ul class="nav-menu">
            <li class="nav-item <?php echo $current_page === 'index' && $current_dir === 'admin' ? 'active' : ''; ?>">
                <a href="<?php echo url('admin/index.php'); ?>">
                    <i class="fas fa-th-large"></i>
                    <span>Dashboard</span>
                </a>
            </li>

            <li class="nav-item <?php echo $current_dir === 'news' ? 'active' : ''; ?>">
                <a href="<?php echo url('admin/news/index.php'); ?>">
                    <i class="fas fa-newspaper"></i>
                    <span>News</span>
                </a>
            </li>

            <li class="nav-item <?php echo $current_dir === 'announcements' ? 'active' : ''; ?>">
                <a href="<?php echo url('admin/announcements/index.php'); ?>">
                    <i class="fas fa-bullhorn"></i>
                    <span>Announcements</span>
                </a>
            </li>

            <li class="nav-item <?php echo $current_dir === 'events' ? 'active' : ''; ?>">
                <a href="<?php echo url('admin/events/index.php'); ?>">
                    <i class="fas fa-calendar-alt"></i>
                    <span>Events</span>
                </a>
            </li>

            <li class="nav-item <?php echo $current_dir === 'gallery' ? 'active' : ''; ?>">
                <a href="<?php echo url('admin/gallery/index.php'); ?>">
                    <i class="fas fa-images"></i>
                    <span>Gallery</span>
                </a>
            </li>

            <li class="nav-item <?php echo $current_dir === 'sliders' ? 'active' : ''; ?>">
                <a href="<?php echo url('admin/sliders/index.php'); ?>">
                    <i class="fas fa-sliders-h"></i>
                    <span>Homepage Sliders</span>
                </a>
            </li>

            <li class="nav-item <?php echo $current_dir === 'programs' ? 'active' : ''; ?>">
                <a href="<?php echo url('admin/programs/index.php'); ?>">
                    <i class="fas fa-graduation-cap"></i>
                    <span>Programs</span>
                </a>
            </li>
            
            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'super_admin'): ?>
            <li class="nav-item <?php echo $current_dir === 'organizations' ? 'active' : ''; ?>">
                <a href="<?php echo url('admin/organizations/index.php'); ?>">
                    <i class="fas fa-users"></i>
                    <span>Organizations</span>
                </a>
            </li>

            <li class="nav-item <?php echo $current_dir === 'facilities' ? 'active' : ''; ?>">
                <a href="<?php echo url('admin/facilities/index.php'); ?>">
                    <i class="fas fa-building"></i>
                    <span>Facilities</span>
                </a>
            </li>

            <li class="nav-item <?php echo $current_dir === 'achievements' ? 'active' : ''; ?>">
                <a href="<?php echo url('admin/achievements/index.php'); ?>">
                    <i class="fas fa-trophy"></i>
                    <span>Achievements</span>
                </a>
            </li>

            <li class="nav-item <?php echo $current_dir === 'pages' ? 'active' : ''; ?>">
                <a href="<?php echo url('admin/pages/index.php'); ?>">
                    <i class="fas fa-file-alt"></i>
                    <span>Pages</span>
                </a>
            </li>

            <li class="nav-item <?php echo $current_dir === 'administration' ? 'active' : ''; ?>">
                <a href="<?php echo url('admin/administration/index.php'); ?>">
                    <i class="fas fa-users-cog"></i>
                    <span>Administration</span>
                </a>
            </li> 

            <?php endif; ?>

            <li class="nav-item <?php echo $current_dir === 'messages' ? 'active' : ''; ?>">
                <a href="<?php echo url('admin/messages/index.php'); ?>">
                    <i class="fas fa-envelope"></i>
                    <span>Messages</span>
                    <?php if (isset($stats['new_messages']) && $stats['new_messages'] > 0): ?>
                        <span class="badge"><?php echo $stats['new_messages']; ?></span>
                    <?php endif; ?>
                </a>
            </li>

            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'super_admin'): ?>
                <li class="nav-item <?php echo $current_dir === 'users' ? 'active' : ''; ?>">
                    <a href="<?php echo url('admin/users/index.php'); ?>">
                        <i class="fas fa-user-shield"></i>
                        <span>Admin Users</span>
                    </a>
                </li>
            <?php endif; ?>

            <li class="nav-item <?php echo $current_dir === 'analytics' ? 'active' : ''; ?>">
                <a href="<?php echo url('admin/analytics/index.php'); ?>">
                    <i class="fas fa-chart-line"></i>
                    <span>Analytics</span>
                </a>
            </li>
            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'super_admin'): ?>
                <li class="nav-item <?php echo $current_dir === 'settings' ? 'active' : ''; ?>">
                    <a href="<?php echo url('admin/settings/index.php'); ?>">
                        <i class="fas fa-cog"></i>
                        <span>Settings</span>
                    </a>
                </li>
            <?php endif; ?>
        </ul>
    </nav>

    <div class="sidebar-footer">
        <a href="<?php echo url(); ?>" class="sidebar-link" target="_blank">
            <i class="fas fa-external-link-alt"></i>
            <span>View Website</span>
        </a>
    </div>
</aside>
