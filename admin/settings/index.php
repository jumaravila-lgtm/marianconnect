<?php
require_once '../includes/auth-check.php';

// Restrict to Super Admin only
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'super_admin') {
    setFlashMessage('error', 'Access denied. Only Super Admins can access Settings.');
    redirect('../index.php');
}
$db = getDB();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_general'])) {
    try {
        $settings = [
            'site_name' => $_POST['site_name'] ?? '',
            'site_tagline' => $_POST['site_tagline'] ?? '',
            'timezone' => $_POST['timezone'] ?? 'Asia/Manila',
            'google_analytics_id' => $_POST['google_analytics_id'] ?? ''
        ];

        foreach ($settings as $key => $value) {
            $stmt = $db->prepare("UPDATE site_settings SET setting_value = ? WHERE setting_key = ?");
            $stmt->execute([$value, $key]);
        }

        logActivity($_SESSION['admin_id'], 'update', 'site_settings', null, 'Updated general settings');
        setFlashMessage('success', 'General settings updated successfully!');
        header('Location: index.php');
        exit();
    } catch (Exception $e) {
        setFlashMessage('error', 'Failed to update settings: ' . $e->getMessage());
    }
}

// Get current settings
$settingsQuery = $db->query("SELECT setting_key, setting_value FROM site_settings");
$settings = [];
while ($row = $settingsQuery->fetch()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

$pageTitle = 'General Settings';
include '../includes/admin-header.php';
?>

<div class="page-header">
    <div>
        <h1>Site Settings</h1>
        <p class="page-subtitle">Manage your website configuration</p>
    </div>
</div>

<!-- Settings Navigation -->
<div class="settings-nav">
    <a href="index.php" class="settings-nav-item active">
        <i class="fas fa-cog"></i> General Settings
    </a>
    <a href="contact-info.php" class="settings-nav-item">
        <i class="fas fa-address-book"></i> Contact Information
    </a>
    <a href="social-media.php" class="settings-nav-item">
        <i class="fas fa-share-alt"></i> Social Media
    </a>
    <a href="maintenance.php" class="settings-nav-item">
        <i class="fas fa-tools"></i> Maintenance Mode
    </a>
</div>

<!-- General Settings Form -->
<div class="settings-card">
    <form method="POST" action="">
        <div class="form-section">
            <h3><i class="fas fa-info-circle"></i> Basic Information</h3>
            
            <div class="form-group">
                <label for="site_name">Site Name <span class="required">*</span></label>
                <input type="text" 
                       id="site_name" 
                       name="site_name" 
                       class="form-control" 
                       value="<?php echo escapeHtml($settings['site_name'] ?? ''); ?>" 
                       required>
                <small class="form-help">The name of your institution</small>
            </div>

            <div class="form-group">
                <label for="site_tagline">Site Tagline</label>
                <input type="text" 
                       id="site_tagline" 
                       name="site_tagline" 
                       class="form-control" 
                       value="<?php echo escapeHtml($settings['site_tagline'] ?? ''); ?>">
                <small class="form-help">A short description or motto</small>
            </div>
        </div>

        <div class="form-section">
            <h3><i class="fas fa-globe"></i> Regional Settings</h3>
            
            <div class="form-group">
                <label for="timezone">Timezone <span class="required">*</span></label>
                <select id="timezone" name="timezone" class="form-control" required>
                    <option value="Asia/Manila" <?php echo ($settings['timezone'] ?? '') === 'Asia/Manila' ? 'selected' : ''; ?>>Asia/Manila (Philippine Time)</option>
                    <option value="UTC" <?php echo ($settings['timezone'] ?? '') === 'UTC' ? 'selected' : ''; ?>>UTC (Coordinated Universal Time)</option>
                    <option value="America/New_York" <?php echo ($settings['timezone'] ?? '') === 'America/New_York' ? 'selected' : ''; ?>>America/New York (EST)</option>
                    <option value="Europe/London" <?php echo ($settings['timezone'] ?? '') === 'Europe/London' ? 'selected' : ''; ?>>Europe/London (GMT)</option>
                    <option value="Asia/Tokyo" <?php echo ($settings['timezone'] ?? '') === 'Asia/Tokyo' ? 'selected' : ''; ?>>Asia/Tokyo (JST)</option>
                </select>
                <small class="form-help">Used for timestamps and scheduling</small>
            </div>
        </div>

        <div class="form-section">
            <h3><i class="fas fa-chart-bar"></i> Analytics & Tracking</h3>
            
            <div class="form-group">
                <label for="google_analytics_id">Google Analytics Tracking ID</label>
                <input type="text" 
                       id="google_analytics_id" 
                       name="google_analytics_id" 
                       class="form-control" 
                       value="<?php echo escapeHtml($settings['google_analytics_id'] ?? ''); ?>"
                       placeholder="G-XXXXXXXXXX or UA-XXXXXXXXX-X">
                <small class="form-help">Leave empty to disable Google Analytics tracking</small>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" name="save_general" class="btn btn-primary">
                <i class="fas fa-save"></i> Save Changes
            </button>
            <a href="../index.php" class="btn btn-secondary">
                <i class="fas fa-times"></i> Cancel
            </a>
        </div>
    </form>
</div>

<!-- Quick Info Cards -->
<div class="info-grid">
    <div class="info-card">
        <div class="info-icon" style="background:#e3f2fd;color:#1976d2">
            <i class="fas fa-clock"></i>
        </div>
        <div class="info-content">
            <h4>Current Server Time</h4>
            <p><?php echo date('F j, Y g:i:s A'); ?></p>
        </div>
    </div>

    <div class="info-card">
        <div class="info-icon" style="background:#e8f5e9;color:#388e3c">
            <i class="fas fa-database"></i>
        </div>
        <div class="info-content">
            <h4>Database</h4>
            <p>marianconnect (MySQL)</p>
        </div>
    </div>

    <div class="info-card">
        <div class="info-icon" style="background:#fff3e0;color:#f57c00">
            <i class="fas fa-php"></i>
        </div>
        <div class="info-content">
            <h4>PHP Version</h4>
            <p><?php echo phpversion(); ?></p>
        </div>
    </div>
</div>

<style>
.page-header{margin-bottom:2rem}
.page-subtitle{color:var(--admin-text-muted);margin-top:0.5rem}

.settings-nav{display:flex;gap:0.5rem;margin-bottom:2rem;background:white;padding:0.75rem;border-radius:12px;box-shadow:var(--admin-shadow);overflow-x:auto}
.settings-nav-item{padding:0.75rem 1.5rem;border-radius:8px;text-decoration:none;color:var(--admin-text);font-weight:500;display:flex;align-items:center;gap:0.5rem;transition:all 0.2s;white-space:nowrap}
.settings-nav-item:hover{background:#f5f5f5}
.settings-nav-item.active{background:var(--admin-primary);color:white}

.settings-card{background:white;border-radius:12px;padding:2rem;box-shadow:var(--admin-shadow);margin-bottom:2rem}

.form-section{margin-bottom:2.5rem;padding-bottom:2rem;border-bottom:1px solid var(--admin-border)}
.form-section:last-of-type{border-bottom:none;margin-bottom:0;padding-bottom:0}
.form-section h3{font-size:1.1rem;font-weight:600;margin-bottom:1.5rem;display:flex;align-items:center;gap:0.5rem;color:var(--admin-text)}

.form-group{margin-bottom:1.5rem}
.form-group label{display:block;margin-bottom:0.5rem;font-weight:500;font-size:0.95rem;color:var(--admin-text)}
.form-group .required{color:#dc3545}
.form-control{width:100%;padding:0.75rem 1rem;border:2px solid var(--admin-border);border-radius:8px;font-size:0.95rem;transition:border-color 0.2s}
.form-control:focus{outline:none;border-color:var(--admin-primary)}
.form-help{display:block;margin-top:0.5rem;font-size:0.85rem;color:var(--admin-text-muted)}

.form-actions{display:flex;gap:1rem;padding-top:2rem;border-top:1px solid var(--admin-border);margin-top:2rem}

.info-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:1.5rem}
.info-card{background:white;border-radius:12px;padding:1.5rem;box-shadow:var(--admin-shadow);display:flex;gap:1rem;align-items:center}
.info-icon{width:60px;height:60px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.5rem;flex-shrink:0}
.info-content h4{font-size:0.9rem;color:var(--admin-text-muted);margin-bottom:0.25rem;font-weight:500}
.info-content p{font-size:1.1rem;font-weight:600;color:var(--admin-text);margin:0}

.btn{padding:0.75rem 1.5rem;border:none;border-radius:8px;font-weight:500;cursor:pointer;display:inline-flex;align-items:center;gap:0.5rem;text-decoration:none;transition:all 0.2s;font-size:0.95rem}
.btn-primary{background:var(--admin-primary);color:white}
.btn-primary:hover{background:#1565c0}
.btn-secondary{background:#6c757d;color:white}
.btn-secondary:hover{background:#5a6268}

@media(max-width:768px){
    .settings-nav{flex-wrap:wrap}
    .form-actions{flex-direction:column}
    .btn{width:100%}
}
</style>

<?php include '../includes/admin-footer.php'; ?>
