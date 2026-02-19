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
/* Page Header */
.page-header {
    margin-bottom: 2rem;
    padding-bottom: 1.5rem;
    border-bottom: 1px solid var(--admin-border);
}

.page-header h1 {
    font-size: 1.75rem;
    font-weight: 700;
    color: var(--admin-text);
    margin: 0 0 0.5rem 0;
}

.page-subtitle {
    color: var(--admin-text-muted);
    font-size: 0.95rem;
    margin: 0;
}

/* Settings Navigation */
.settings-nav {
    display: flex;
    gap: 0.5rem;
    margin-bottom: 2rem;
    background: white;
    padding: 0.75rem;
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
    border: 1px solid var(--admin-border);
    overflow-x: auto;
}

.settings-nav-item {
    padding: 0.875rem 1.5rem;
    border-radius: 8px;
    text-decoration: none;
    color: var(--admin-text);
    font-weight: 500;
    font-size: 0.95rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.3s;
    white-space: nowrap;
    border: 2px solid transparent;
}

.settings-nav-item:hover {
    background: #f8f9fa;
    border-color: #e9ecef;
}

.settings-nav-item.active {
    background: var(--admin-primary);
    color: white;
    box-shadow: 0 2px 8px rgba(0, 63, 135, 0.25);
}

/* Settings Card */
.settings-card {
    background: white;
    border-radius: 12px;
    padding: 2rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
    border: 1px solid var(--admin-border);
    margin-bottom: 2rem;
}

/* Form Section */
.form-section {
    margin-bottom: 2.5rem;
    padding-bottom: 2rem;
    border-bottom: 2px solid var(--admin-border);
}

.form-section:last-of-type {
    border-bottom: none;
    margin-bottom: 0;
    padding-bottom: 0;
}

.form-section h3 {
    font-size: 1.15rem;
    font-weight: 700;
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: var(--admin-text);
    padding-bottom: 0.75rem;
    border-bottom: 2px solid #f0f0f0;
}

.form-section h3 i {
    color: var(--admin-primary);
}

/* Form Group */
.form-group {
    margin-bottom: 1.5rem;
}

.form-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 600;
    font-size: 0.9rem;
    color: var(--admin-text);
}

.form-group .required {
    color: #dc3545;
    margin-left: 2px;
}

.form-control {
    width: 100%;
    padding: 0.875rem 1rem;
    border: 2px solid var(--admin-border);
    border-radius: 8px;
    font-size: 0.95rem;
    transition: all 0.3s;
    background: white;
}

.form-control:hover {
    border-color: #c5cdd8;
}

.form-control:focus {
    outline: none;
    border-color: var(--admin-primary);
    box-shadow: 0 0 0 4px rgba(0, 63, 135, 0.08);
}

select.form-control {
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23333' d='M6 9L1 4h10z'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 1rem center;
    background-size: 12px;
    padding-right: 2.5rem;
    cursor: pointer;
}

.form-help {
    display: block;
    margin-top: 0.5rem;
    font-size: 0.85rem;
    color: var(--admin-text-muted);
    line-height: 1.4;
}

/* Form Actions */
.form-actions {
    display: flex;
    gap: 1rem;
    padding-top: 2rem;
    border-top: 2px solid var(--admin-border);
    margin-top: 2rem;
}

/* Buttons */
.btn {
    padding: 0.875rem 1.75rem;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    text-decoration: none;
    transition: all 0.3s;
    font-size: 0.95rem;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.btn-primary {
    background: var(--admin-primary);
    color: white;
}

.btn-primary:hover {
    background: var(--admin-primary-dark);
    transform: translateY(-2px);
    box-shadow: 0 6px 12px rgba(0, 63, 135, 0.25);
}

.btn-secondary {
    background: #6c757d;
    color: white;
}

.btn-secondary:hover {
    background: #5a6268;
    transform: translateY(-2px);
    box-shadow: 0 6px 12px rgba(108, 117, 125, 0.25);
}

/* Info Grid */
.info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
}

.info-card {
    background: white;
    border-radius: 12px;
    padding: 1.75rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
    border: 1px solid var(--admin-border);
    display: flex;
    gap: 1.25rem;
    align-items: center;
    transition: all 0.3s;
}

.info-card:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    transform: translateY(-2px);
}

.info-icon {
    width: 65px;
    height: 65px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.75rem;
    flex-shrink: 0;
}

.info-content {
    flex: 1;
}

.info-content h4 {
    font-size: 0.85rem;
    color: var(--admin-text-muted);
    margin-bottom: 0.5rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.info-content p {
    font-size: 1.1rem;
    font-weight: 700;
    color: var(--admin-text);
    margin: 0;
    line-height: 1.2;
}

/* Responsive */
@media (max-width: 1024px) {
    .settings-nav {
        flex-wrap: wrap;
    }
}

@media (max-width: 768px) {
    .page-header {
        margin-bottom: 1.5rem;
    }
    
    .settings-nav {
        flex-direction: column;
        gap: 0.25rem;
    }
    
    .settings-nav-item {
        justify-content: center;
    }
    
    .settings-card {
        padding: 1.5rem;
    }
    
    .form-section {
        margin-bottom: 2rem;
        padding-bottom: 1.5rem;
    }
    
    .form-actions {
        flex-direction: column;
    }
    
    .btn {
        width: 100%;
        justify-content: center;
    }
    
    .info-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<?php include '../includes/admin-footer.php'; ?>
