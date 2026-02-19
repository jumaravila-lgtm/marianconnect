<?php
/**
 * MARIANCONNECT - Admin Header
 */

if (!defined('SITE_NAME')) {
    require_once '../../config/settings.php';
}

// Get current page for active menu
$current_page = basename($_SERVER['PHP_SELF'], '.php');
$current_dir = basename(dirname($_SERVER['PHP_SELF']));

// Get message count for notifications (optional - you can expand this)
if (!isset($stats)) {
    $stats = [];
    try {
        $db = getDB();
        $stats['new_messages'] = $db->query("SELECT COUNT(*) FROM contact_messages WHERE status = 'new'")->fetchColumn();
    } catch (Exception $e) {
        $stats['new_messages'] = 0;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' : ''; ?>Admin Panel - MARIANCONNECT</title>
    
    <link rel="icon" type="image/x-icon" href="<?php echo url('assets/images/logo/favicon.ico'); ?>">
    
    <!-- Admin Styles -->
    <link rel="stylesheet" href="<?php echo url('assets/css/admin.css'); ?>">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="admin-body">
    
    <?php 
    // Include the sidebar
    $sidebar_path = __DIR__ . '/admin-sidebar.php';
    if (file_exists($sidebar_path)) {
        include $sidebar_path;
    }
    ?>

    <!-- Main Content Area -->
    <div class="admin-main" id="adminMain">
        
        <!-- Top Header -->
        <header class="admin-header">
            <div class="header-left">
                <!-- Logo and Hamburger -->
                <div class="header-brand">
                    <button class="sidebar-toggle" id="sidebarToggle">
                        <i class="fas fa-bars"></i>
                    </button>
                    <img src="<?php echo url('assets/images/logo/logo-white.png'); ?>" alt="SMCC" class="header-logo">
                    <span class="header-title">MARIANCONNECT</span>
                </div>
                
                <div class="breadcrumb">
                    <a href="<?php echo url('admin/index.php'); ?>">Admin</a>
                    <?php if (isset($pageTitle)): ?>
                        <span>/</span>
                        <span><?php echo escapeHtml($pageTitle); ?></span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="header-right">
                <!-- Notifications -->
                <div class="header-notifications">
                    <button class="notification-btn" id="notificationBtn">
                        <i class="fas fa-bell"></i>
                        <?php if (($stats['new_messages'] ?? 0) > 0): ?>
                            <span class="notification-badge"><?php echo $stats['new_messages']; ?></span>
                        <?php endif; ?>
                    </button>
                    <div class="notification-dropdown" id="notificationDropdown">
                        <div class="notification-header">
                            <h4>Notifications</h4>
                        </div>
                        <div class="notification-body">
                            <?php if (($stats['new_messages'] ?? 0) > 0): ?>
                                <a href="<?php echo url('admin/messages/index.php'); ?>" class="notification-item">
                                    <i class="fas fa-envelope"></i>
                                    <div>
                                        <strong>New Messages</strong>
                                        <p>You have <?php echo $stats['new_messages']; ?> unread messages</p>
                                    </div>
                                </a>
                            <?php else: ?>
                                <p class="no-notifications">No new notifications</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- User Profile -->
                <div class="header-profile">
                    <button class="profile-btn" id="profileBtn">
                        <?php if (!empty($_SESSION['avatar'])): ?>
                            <img src="<?php echo url($_SESSION['avatar']); ?>" alt="Profile" onerror="this.style.display='none'">
                        <?php else: ?>
                            <div class="header-avatar-fallback"><?php echo strtoupper(substr($_SESSION['full_name'], 0, 1)); ?></div>
                        <?php endif; ?>
                        <span><?php echo escapeHtml($_SESSION['full_name']); ?></span>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="profile-dropdown" id="profileDropdown">
                        <div class="profile-header">
                            <?php if (!empty($_SESSION['avatar'])): ?>
                                <img src="<?php echo url($_SESSION['avatar']); ?>" alt="Profile">
                            <?php else: ?>
                                <div class="dropdown-avatar-fallback"><?php echo strtoupper(substr($_SESSION['full_name'], 0, 2)); ?></div>
                            <?php endif; ?>
                            <div>
                                <strong><?php echo escapeHtml($_SESSION['full_name']); ?></strong>
                                <small><?php echo escapeHtml($_SESSION['username']); ?></small>
                            </div>
                        </div>
                        <div class="profile-menu">
                            <a href="<?php echo url('admin/users/profile.php'); ?>">
                                <i class="fas fa-user"></i> My Profile
                            </a>
                            <?php if ($_SESSION['role'] !== 'editor'): ?>
                            <a href="<?php echo url('admin/users/change-password.php'); ?>">
                                <i class="fas fa-key"></i> Change Password
                            </a>
                            <?php endif; ?>
                            <a href="<?php echo url('admin/settings/index.php'); ?>">
                                <i class="fas fa-cog"></i> Settings
                            </a>
                            <hr>
                            <a href="<?php echo url('admin/logout.php'); ?>" class="text-danger">
                                <i class="fas fa-sign-out-alt"></i> Logout
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- Flash Messages -->
        <?php
        $flash = getFlashMessage();
        if ($flash):
        ?>
        <div class="alert alert-<?php echo $flash['type']; ?> alert-dismissible">
            <?php echo escapeHtml($flash['message']); ?>
            <button class="alert-close" onclick="this.parentElement.remove()">&times;</button>
        </div>
        <?php endif; ?>

        <!-- Page Content -->
        <main class="admin-content">
