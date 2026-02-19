<?php
require_once '../includes/auth-check.php';
// Restrict to Super Admin only
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'super_admin') {
    setFlashMessage('error', 'Access denied. Only Super Admins can access Settings.');
    redirect('../index.php');
}
$db = getDB();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_maintenance'])) {
    try {
        $maintenanceMode = isset($_POST['maintenance_mode']) ? 'true' : 'false';
        
        $stmt = $db->prepare("UPDATE site_settings SET setting_value = ? WHERE setting_key = 'maintenance_mode'");
        $stmt->execute([$maintenanceMode]);

        logActivity($_SESSION['admin_id'], 'update', 'site_settings', null, 'Updated maintenance mode: ' . $maintenanceMode);
        setFlashMessage('success', 'Maintenance mode ' . ($maintenanceMode === 'true' ? 'enabled' : 'disabled') . ' successfully!');
        header('Location: maintenance.php');
        exit();
    } catch (Exception $e) {
        setFlashMessage('error', 'Failed to update maintenance mode: ' . $e->getMessage());
    }
}

// Get current settings
$settingsQuery = $db->query("SELECT setting_key, setting_value FROM site_settings");
$settings = [];
while ($row = $settingsQuery->fetch()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

$maintenanceEnabled = ($settings['maintenance_mode'] ?? 'false') === 'true';

$pageTitle = 'Maintenance Mode';
include '../includes/admin-header.php';
?>

<div class="page-header">
    <div>
        <h1>Maintenance Mode</h1>
        <p class="page-subtitle">Control website accessibility for maintenance and updates</p>
    </div>
</div>

<!-- Settings Navigation -->
<div class="settings-nav">
    <a href="index.php" class="settings-nav-item">
        <i class="fas fa-cog"></i> General Settings
    </a>
    <a href="contact-info.php" class="settings-nav-item">
        <i class="fas fa-address-book"></i> Contact Information
    </a>
    <a href="social-media.php" class="settings-nav-item">
        <i class="fas fa-share-alt"></i> Social Media
    </a>
    <a href="maintenance.php" class="settings-nav-item active">
        <i class="fas fa-tools"></i> Maintenance Mode
    </a>
</div>

<!-- Current Status Alert -->
<?php if ($maintenanceEnabled): ?>
<div class="status-alert alert-warning">
    <div class="alert-icon">
        <i class="fas fa-exclamation-triangle"></i>
    </div>
    <div class="alert-content">
        <strong>Maintenance Mode is Currently ACTIVE</strong>
        <p>Your website is displaying a maintenance page to visitors. Only administrators can access the site.</p>
    </div>
</div>
<?php else: ?>
<div class="status-alert alert-success">
    <div class="alert-icon">
        <i class="fas fa-check-circle"></i>
    </div>
    <div class="alert-content">
        <strong>Website is Live and Accessible</strong>
        <p>Your website is currently accessible to all visitors.</p>
    </div>
</div>
<?php endif; ?>

<!-- Maintenance Mode Form -->
<div class="settings-card">
    <form method="POST" action="">
        <div class="form-section">
            <h3><i class="fas fa-power-off"></i> Maintenance Mode Control</h3>
            
            <div class="toggle-container">
                <div class="toggle-info">
                    <h4>Enable Maintenance Mode</h4>
                    <p>When enabled, visitors will see a maintenance page. Administrators can still access the admin panel.</p>
                </div>
                <label class="toggle-switch">
                    <input type="checkbox" 
                           name="maintenance_mode" 
                           id="maintenance_mode"
                           <?php echo $maintenanceEnabled ? 'checked' : ''; ?>>
                    <span class="toggle-slider"></span>
                </label>
            </div>
        </div>

        <div class="info-section">
            <h4><i class="fas fa-info-circle"></i> What happens when maintenance mode is enabled?</h4>
            <ul class="info-list">
                <li><i class="fas fa-check"></i> Visitors will see a "Under Maintenance" message</li>
                <li><i class="fas fa-check"></i> Search engines will receive a 503 status code</li>
                <li><i class="fas fa-check"></i> Administrators can still access the admin panel</li>
                <li><i class="fas fa-check"></i> No data or content will be lost</li>
            </ul>
        </div>

        <div class="warning-section">
            <div class="warning-icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <div class="warning-content">
                <strong>Important Notice</strong>
                <p>Remember to disable maintenance mode after completing your updates. Leaving it enabled will prevent visitors from accessing your website.</p>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" name="save_maintenance" class="btn btn-primary">
                <i class="fas fa-save"></i> Save Changes
            </button>
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Settings
            </a>
        </div>
    </form>
</div>

<!-- Preview Card -->
<div class="preview-card">
    <h3><i class="fas fa-eye"></i> Maintenance Page Preview</h3>
    <p class="preview-subtitle">This is what visitors will see when maintenance mode is enabled</p>
    
    <div class="maintenance-preview">
        <div class="preview-icon">
            <i class="fas fa-tools"></i>
        </div>
        <h2>We'll Be Back Soon!</h2>
        <p>Our website is currently undergoing scheduled maintenance. We apologize for any inconvenience.</p>
        <p class="preview-note">Please check back later.</p>
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

/* Status Alert */
.status-alert {
    display: flex;
    gap: 1.25rem;
    padding: 1.75rem;
    border-radius: 12px;
    margin-bottom: 2rem;
    border: 2px solid;
    align-items: center;
}

.status-alert.alert-warning {
    background: linear-gradient(135deg, #fff3cd 0%, #fffbf0 100%);
    border-color: #ffc107;
}

.status-alert.alert-success {
    background: linear-gradient(135deg, #d4edda 0%, #edf7ef 100%);
    border-color: #28a745;
}

.alert-icon {
    width: 60px;
    height: 60px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.75rem;
    flex-shrink: 0;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.alert-warning .alert-icon {
    background: linear-gradient(135deg, #ffc107, #ffb300);
    color: white;
}

.alert-success .alert-icon {
    background: linear-gradient(135deg, #28a745, #20873a);
    color: white;
}

.alert-content {
    flex: 1;
}

.alert-content strong {
    display: block;
    font-size: 1.15rem;
    margin-bottom: 0.5rem;
    font-weight: 700;
}

.alert-warning .alert-content strong {
    color: #856404;
}

.alert-success .alert-content strong {
    color: #155724;
}

.alert-content p {
    margin: 0;
    font-size: 0.95rem;
    line-height: 1.5;
}

.alert-warning .alert-content p {
    color: #856404;
}

.alert-success .alert-content p {
    color: #155724;
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
    margin-bottom: 2rem;
    padding-bottom: 2rem;
    border-bottom: 2px solid var(--admin-border);
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

/* Toggle Container */
.toggle-container {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 2rem;
    padding: 2rem;
    background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
    border-radius: 12px;
    border: 2px solid var(--admin-border);
}

.toggle-info {
    flex: 1;
}

.toggle-info h4 {
    margin: 0 0 0.5rem 0;
    font-size: 1.05rem;
    font-weight: 700;
    color: var(--admin-text);
}

.toggle-info p {
    margin: 0;
    font-size: 0.9rem;
    color: var(--admin-text-muted);
    line-height: 1.5;
}

/* Toggle Switch */
.toggle-switch {
    position: relative;
    display: inline-block;
    width: 64px;
    height: 36px;
    flex-shrink: 0;
}

.toggle-switch input {
    opacity: 0;
    width: 0;
    height: 0;
}

.toggle-slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: #ccc;
    transition: 0.4s;
    border-radius: 36px;
    box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.1);
}

.toggle-slider:before {
    position: absolute;
    content: "";
    height: 28px;
    width: 28px;
    left: 4px;
    bottom: 4px;
    background: white;
    transition: 0.4s;
    border-radius: 50%;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
}

input:checked + .toggle-slider {
    background: linear-gradient(135deg, #28a745, #20873a);
}

input:checked + .toggle-slider:before {
    transform: translateX(28px);
}

/* Info Section */
.info-section {
    background: linear-gradient(135deg, #e3f2fd 0%, #f0f8ff 100%);
    padding: 2rem;
    border-radius: 12px;
    margin-bottom: 2rem;
    border-left: 4px solid var(--admin-primary);
    box-shadow: 0 2px 8px rgba(0, 63, 135, 0.1);
}

.info-section h4 {
    margin: 0 0 1.25rem 0;
    font-size: 1.05rem;
    font-weight: 700;
    color: var(--admin-text);
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.info-section h4 i {
    color: var(--admin-primary);
}

.info-list {
    list-style: none;
    padding: 0;
    margin: 0;
    display: flex;
    flex-direction: column;
    gap: 0.875rem;
}

.info-list li {
    display: flex;
    align-items: center;
    gap: 0.875rem;
    font-size: 0.95rem;
    color: var(--admin-text);
    line-height: 1.5;
}

.info-list li i {
    color: var(--admin-primary);
    font-size: 0.9rem;
    background: white;
    width: 24px;
    height: 24px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

/* Warning Section */
.warning-section {
    display: flex;
    gap: 1.25rem;
    padding: 1.75rem;
    background: linear-gradient(135deg, #fff3cd 0%, #fffbf0 100%);
    border-radius: 12px;
    border-left: 4px solid #ffc107;
    margin-bottom: 2rem;
    box-shadow: 0 2px 8px rgba(255, 193, 7, 0.15);
}

.warning-icon {
    width: 50px;
    height: 50px;
    background: linear-gradient(135deg, #ffc107, #ffb300);
    color: white;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    flex-shrink: 0;
    box-shadow: 0 4px 12px rgba(255, 193, 7, 0.3);
}

.warning-content {
    flex: 1;
}

.warning-content strong {
    display: block;
    color: #856404;
    margin-bottom: 0.5rem;
    font-size: 1rem;
    font-weight: 700;
}

.warning-content p {
    margin: 0;
    color: #856404;
    font-size: 0.9rem;
    line-height: 1.6;
}

/* Form Actions */
.form-actions {
    display: flex;
    gap: 1rem;
    padding-top: 2rem;
    border-top: 2px solid var(--admin-border);
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

/* Preview Card */
.preview-card {
    background: white;
    border-radius: 12px;
    padding: 2rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
    border: 1px solid var(--admin-border);
}

.preview-card h3 {
    font-size: 1.15rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: var(--admin-text);
}

.preview-card h3 i {
    color: var(--admin-primary);
}

.preview-subtitle {
    color: var(--admin-text-muted);
    margin-bottom: 2rem;
    font-size: 0.9rem;
    line-height: 1.5;
}

/* Maintenance Preview */
.maintenance-preview {
    text-align: center;
    padding: 4rem 2rem;
    background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
    border-radius: 12px;
    border: 2px dashed var(--admin-border);
}

.preview-icon {
    width: 90px;
    height: 90px;
    background: linear-gradient(135deg, var(--admin-primary), #1976d2);
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2.25rem;
    margin: 0 auto 1.5rem;
    box-shadow: 0 8px 20px rgba(0, 63, 135, 0.25);
}

.maintenance-preview h2 {
    font-size: 1.75rem;
    font-weight: 700;
    color: var(--admin-text);
    margin-bottom: 1rem;
}

.maintenance-preview p {
    font-size: 1rem;
    color: var(--admin-text-muted);
    margin-bottom: 0.5rem;
    line-height: 1.6;
}

.preview-note {
    font-weight: 700;
    color: var(--admin-primary);
    font-size: 1.05rem;
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
    
    .status-alert {
        flex-direction: column;
        align-items: flex-start;
        padding: 1.5rem;
    }
    
    .toggle-container {
        flex-direction: column;
        align-items: flex-start;
        gap: 1.5rem;
    }
    
    .form-actions {
        flex-direction: column;
    }
    
    .btn {
        width: 100%;
        justify-content: center;
    }
    
    .preview-card {
        padding: 1.5rem;
    }
    
    .maintenance-preview {
        padding: 3rem 1.5rem;
    }
    
    .warning-section {
        flex-direction: column;
    }
}
</style>

<?php include '../includes/admin-footer.php'; ?>
