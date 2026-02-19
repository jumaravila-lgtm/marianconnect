<?php
require_once '../includes/auth-check.php';
// Restrict to Super Admin only
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'super_admin') {
    setFlashMessage('error', 'Access denied. Only Super Admins can access Settings.');
    redirect('../index.php');
}
$db = getDB();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_contact'])) {
    try {
        $settings = [
            'contact_email' => $_POST['contact_email'] ?? '',
            'contact_phone' => $_POST['contact_phone'] ?? '',
            'contact_address' => $_POST['contact_address'] ?? ''
        ];

        // Validate email
        if (!empty($settings['contact_email']) && !filter_var($settings['contact_email'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Invalid email address format');
        }

        foreach ($settings as $key => $value) {
            $stmt = $db->prepare("UPDATE site_settings SET setting_value = ? WHERE setting_key = ?");
            $stmt->execute([$value, $key]);
        }

        logActivity($_SESSION['admin_id'], 'update', 'site_settings', null, 'Updated contact information');
        setFlashMessage('success', 'Contact information updated successfully!');
        header('Location: contact-info.php');
        exit();
    } catch (Exception $e) {
        setFlashMessage('error', 'Failed to update contact information: ' . $e->getMessage());
    }
}

// Get current settings
$settingsQuery = $db->query("SELECT setting_key, setting_value FROM site_settings");
$settings = [];
while ($row = $settingsQuery->fetch()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

$pageTitle = 'Contact Information';
include '../includes/admin-header.php';
?>

<div class="page-header">
    <div>
        <h1>Contact Information</h1>
        <p class="page-subtitle">Manage contact details displayed on your website</p>
    </div>
</div>

<!-- Settings Navigation -->
<div class="settings-nav">
    <a href="index.php" class="settings-nav-item">
        <i class="fas fa-cog"></i> General Settings
    </a>
    <a href="contact-info.php" class="settings-nav-item active">
        <i class="fas fa-address-book"></i> Contact Information
    </a>
    <a href="social-media.php" class="settings-nav-item">
        <i class="fas fa-share-alt"></i> Social Media
    </a>
    <a href="maintenance.php" class="settings-nav-item">
        <i class="fas fa-tools"></i> Maintenance Mode
    </a>
</div>

<!-- Contact Information Form -->
<div class="settings-card">
    <form method="POST" action="">
        <div class="form-section">
            <h3><i class="fas fa-envelope"></i> Email Contact</h3>
            
            <div class="form-group">
                <label for="contact_email">Primary Email Address <span class="required">*</span></label>
                <input type="email" 
                       id="contact_email" 
                       name="contact_email" 
                       class="form-control" 
                       value="<?php echo escapeHtml($settings['contact_email'] ?? ''); ?>" 
                       required
                       placeholder="info@example.com">
                <small class="form-help">Main email address for inquiries and correspondence</small>
            </div>
        </div>

        <div class="form-section">
            <h3><i class="fas fa-phone"></i> Phone Contact</h3>
            
            <div class="form-group">
                <label for="contact_phone">Primary Phone Number</label>
                <input type="text" 
                       id="contact_phone" 
                       name="contact_phone" 
                       class="form-control" 
                       value="<?php echo escapeHtml($settings['contact_phone'] ?? ''); ?>"
                       placeholder="(055) 123-4567">
                <small class="form-help">Include country/area code if necessary</small>
            </div>
        </div>

        <div class="form-section">
            <h3><i class="fas fa-map-marker-alt"></i> Physical Address</h3>
            
            <div class="form-group">
                <label for="contact_address">Complete Address</label>
                <textarea id="contact_address" 
                          name="contact_address" 
                          class="form-control" 
                          rows="4"
                          placeholder="Street Address, City, Province, Postal Code"><?php echo escapeHtml($settings['contact_address'] ?? ''); ?></textarea>
                <small class="form-help">Full address of your institution</small>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" name="save_contact" class="btn btn-primary">
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
    <h3><i class="fas fa-eye"></i> Contact Information Preview</h3>
    <p class="preview-subtitle">This is how your contact information will appear on the website</p>
    
    <div class="preview-content">
        <div class="contact-item">
            <div class="contact-icon">
                <i class="fas fa-envelope"></i>
            </div>
            <div class="contact-details">
                <strong>Email</strong>
                <p><?php echo escapeHtml($settings['contact_email'] ?? 'Not set'); ?></p>
            </div>
        </div>

        <div class="contact-item">
            <div class="contact-icon">
                <i class="fas fa-phone"></i>
            </div>
            <div class="contact-details">
                <strong>Phone</strong>
                <p><?php echo escapeHtml($settings['contact_phone'] ?? 'Not set'); ?></p>
            </div>
        </div>

        <div class="contact-item">
            <div class="contact-icon">
                <i class="fas fa-map-marker-alt"></i>
            </div>
            <div class="contact-details">
                <strong>Address</strong>
                <p><?php echo nl2br(escapeHtml($settings['contact_address'] ?? 'Not set')); ?></p>
            </div>
        </div>
    </div>
</div>

<style>
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
    font-family: inherit;
}

.form-control:hover {
    border-color: #c5cdd8;
}

.form-control:focus {
    outline: none;
    border-color: var(--admin-primary);
    box-shadow: 0 0 0 4px rgba(0, 63, 135, 0.08);
}

textarea.form-control {
    resize: vertical;
    min-height: 120px;
    line-height: 1.6;
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

/* Preview Content */
.preview-content {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.contact-item {
    display: flex;
    gap: 1.25rem;
    padding: 1.75rem;
    background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
    border-radius: 12px;
    border: 1px solid var(--admin-border);
    transition: all 0.3s;
}

.contact-item:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    transform: translateY(-2px);
}

.contact-icon {
    width: 55px;
    height: 55px;
    background: linear-gradient(135deg, var(--admin-primary), #1976d2);
    color: white;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    flex-shrink: 0;
    box-shadow: 0 2px 8px rgba(0, 63, 135, 0.2);
}

.contact-details {
    flex: 1;
}

.contact-details strong {
    display: block;
    font-size: 0.8rem;
    color: var(--admin-text-muted);
    margin-bottom: 0.5rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    font-weight: 700;
}

.contact-details p {
    margin: 0;
    font-size: 1rem;
    color: var(--admin-text);
    line-height: 1.6;
    font-weight: 500;
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
    
    .preview-card {
        padding: 1.5rem;
    }
    
    .contact-item {
        padding: 1.25rem;
    }
    
    .contact-icon {
        width: 50px;
        height: 50px;
        font-size: 1.25rem;
    }
}
</style>

<?php include '../includes/admin-footer.php'; ?>
