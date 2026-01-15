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
.page-header{margin-bottom:2rem}
.page-subtitle{color:var(--admin-text-muted);margin-top:0.5rem}

.settings-nav{display:flex;gap:0.5rem;margin-bottom:2rem;background:white;padding:0.75rem;border-radius:12px;box-shadow:var(--admin-shadow);overflow-x:auto}
.settings-nav-item{padding:0.75rem 1.5rem;border-radius:8px;text-decoration:none;color:var(--admin-text);font-weight:500;display:flex;align-items:center;gap:0.5rem;transition:all 0.2s;white-space:nowrap}
.settings-nav-item:hover{background:#f5f5f5}
.settings-nav-item.active{background:var(--admin-primary);color:white}

.status-alert{display:flex;gap:1rem;padding:1.5rem;border-radius:12px;margin-bottom:2rem;border:2px solid}
.status-alert.alert-warning{background:#fff3cd;border-color:#ffc107;color:#856404}
.status-alert.alert-success{background:#d4edda;border-color:#28a745;color:#155724}
.alert-icon{width:50px;height:50px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1.5rem;flex-shrink:0}
.alert-warning .alert-icon{background:#ffc107;color:#856404}
.alert-success .alert-icon{background:#28a745;color:white}
.alert-content strong{display:block;font-size:1.1rem;margin-bottom:0.5rem}
.alert-content p{margin:0;font-size:0.9rem}

.settings-card{background:white;border-radius:12px;padding:2rem;box-shadow:var(--admin-shadow);margin-bottom:2rem}

.form-section{margin-bottom:2rem;padding-bottom:2rem;border-bottom:1px solid var(--admin-border)}
.form-section h3{font-size:1.1rem;font-weight:600;margin-bottom:1.5rem;display:flex;align-items:center;gap:0.5rem;color:var(--admin-text)}

.toggle-container{display:flex;justify-content:space-between;align-items:center;gap:2rem;padding:1.5rem;background:#f8f9fa;border-radius:12px}
.toggle-info h4{margin:0 0 0.5rem 0;font-size:1rem;font-weight:600;color:var(--admin-text)}
.toggle-info p{margin:0;font-size:0.9rem;color:var(--admin-text-muted)}

.toggle-switch{position:relative;display:inline-block;width:60px;height:34px;flex-shrink:0}
.toggle-switch input{opacity:0;width:0;height:0}
.toggle-slider{position:absolute;cursor:pointer;top:0;left:0;right:0;bottom:0;background:#ccc;transition:0.4s;border-radius:34px}
.toggle-slider:before{position:absolute;content:"";height:26px;width:26px;left:4px;bottom:4px;background:white;transition:0.4s;border-radius:50%}
input:checked + .toggle-slider{background:#28a745}
input:checked + .toggle-slider:before{transform:translateX(26px)}

.info-section{background:#e3f2fd;padding:1.5rem;border-radius:12px;margin-bottom:2rem;border-left:4px solid var(--admin-primary)}
.info-section h4{margin:0 0 1rem 0;font-size:1rem;font-weight:600;color:var(--admin-text);display:flex;align-items:center;gap:0.5rem}
.info-list{list-style:none;padding:0;margin:0;display:flex;flex-direction:column;gap:0.75rem}
.info-list li{display:flex;align-items:center;gap:0.75rem;font-size:0.9rem;color:var(--admin-text)}
.info-list li i{color:var(--admin-primary);font-size:0.85rem}

.warning-section{display:flex;gap:1rem;padding:1.5rem;background:#fff3cd;border-radius:12px;border-left:4px solid #ffc107;margin-bottom:2rem}
.warning-icon{width:40px;height:40px;background:#ffc107;color:#856404;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:1.25rem;flex-shrink:0}
.warning-content strong{display:block;color:#856404;margin-bottom:0.5rem;font-size:0.95rem}
.warning-content p{margin:0;color:#856404;font-size:0.9rem;line-height:1.5}

.form-actions{display:flex;gap:1rem;padding-top:2rem;border-top:1px solid var(--admin-border)}

.preview-card{background:white;border-radius:12px;padding:2rem;box-shadow:var(--admin-shadow)}
.preview-card h3{font-size:1.1rem;font-weight:600;margin-bottom:0.5rem;display:flex;align-items:center;gap:0.5rem;color:var(--admin-text)}
.preview-subtitle{color:var(--admin-text-muted);margin-bottom:2rem;font-size:0.9rem}

.maintenance-preview{text-align:center;padding:3rem;background:#f8f9fa;border-radius:12px;border:2px dashed var(--admin-border)}
.preview-icon{width:80px;height:80px;background:var(--admin-primary);color:white;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:2rem;margin:0 auto 1.5rem}
.maintenance-preview h2{font-size:1.75rem;font-weight:700;color:var(--admin-text);margin-bottom:1rem}
.maintenance-preview p{font-size:1rem;color:var(--admin-text-muted);margin-bottom:0.5rem}
.preview-note{font-weight:600;color:var(--admin-primary)}

.btn{padding:0.75rem 1.5rem;border:none;border-radius:8px;font-weight:500;cursor:pointer;display:inline-flex;align-items:center;gap:0.5rem;text-decoration:none;transition:all 0.2s;font-size:0.95rem}
.btn-primary{background:var(--admin-primary);color:white}
.btn-primary:hover{background:#1565c0}
.btn-secondary{background:#6c757d;color:white}
.btn-secondary:hover{background:#5a6268}

@media(max-width:768px){
    .settings-nav{flex-wrap:wrap}
    .toggle-container{flex-direction:column;align-items:flex-start}
    .form-actions{flex-direction:column}
    .btn{width:100%}
    .status-alert{flex-direction:column}
}
</style>

<?php include '../includes/admin-footer.php'; ?>
