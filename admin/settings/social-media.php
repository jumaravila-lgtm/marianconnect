<?php
require_once '../includes/auth-check.php';
// Restrict to Super Admin only
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'super_admin') {
    setFlashMessage('error', 'Access denied. Only Super Admins can access Settings.');
    redirect('../index.php');
}
$db = getDB();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_social'])) {
    try {
        $settings = [
            'facebook_url' => $_POST['facebook_url'] ?? '',
            'twitter_url' => $_POST['twitter_url'] ?? '',
            'instagram_url' => $_POST['instagram_url'] ?? '',
            'youtube_url' => $_POST['youtube_url'] ?? ''
        ];

        // Validate URLs
        foreach ($settings as $key => $value) {
            if (!empty($value) && !filter_var($value, FILTER_VALIDATE_URL)) {
                throw new Exception('Invalid URL format for ' . str_replace('_url', '', $key));
            }
        }

        foreach ($settings as $key => $value) {
            $stmt = $db->prepare("UPDATE site_settings SET setting_value = ? WHERE setting_key = ?");
            $stmt->execute([$value, $key]);
        }

        logActivity($_SESSION['admin_id'], 'update', 'site_settings', null, 'Updated social media links');
        setFlashMessage('success', 'Social media links updated successfully!');
        header('Location: social-media.php');
        exit();
    } catch (Exception $e) {
        setFlashMessage('error', 'Failed to update social media links: ' . $e->getMessage());
    }
}

// Get current settings
$settingsQuery = $db->query("SELECT setting_key, setting_value FROM site_settings");
$settings = [];
while ($row = $settingsQuery->fetch()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

$pageTitle = 'Social Media Links';
include '../includes/admin-header.php';
?>

<div class="page-header">
    <div>
        <h1>Social Media Links</h1>
        <p class="page-subtitle">Connect your social media profiles to your website</p>
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
    <a href="social-media.php" class="settings-nav-item active">
        <i class="fas fa-share-alt"></i> Social Media
    </a>
    <a href="maintenance.php" class="settings-nav-item">
        <i class="fas fa-tools"></i> Maintenance Mode
    </a>
</div>

<!-- Social Media Form -->
<div class="settings-card">
    <form method="POST" action="">
        <div class="social-platforms">
            <div class="platform-item">
                <div class="platform-header">
                    <div class="platform-icon facebook">
                        <i class="fab fa-facebook-f"></i>
                    </div>
                    <div class="platform-info">
                        <h4>Facebook</h4>
                        <p>Connect your Facebook page</p>
                    </div>
                </div>
                <div class="form-group">
                    <label for="facebook_url">Facebook Page URL</label>
                    <input type="url" 
                           id="facebook_url" 
                           name="facebook_url" 
                           class="form-control" 
                           value="<?php echo escapeHtml($settings['facebook_url'] ?? ''); ?>"
                           placeholder="https://facebook.com/yourpage">
                    <small class="form-help">Example: https://facebook.com/smccatbalogan</small>
                </div>
            </div>

            <div class="platform-item">
                <div class="platform-header">
                    <div class="platform-icon twitter">
                        <i class="fab fa-twitter"></i>
                    </div>
                    <div class="platform-info">
                        <h4>Twitter / X</h4>
                        <p>Connect your Twitter/X profile</p>
                    </div>
                </div>
                <div class="form-group">
                    <label for="twitter_url">Twitter/X Profile URL</label>
                    <input type="url" 
                           id="twitter_url" 
                           name="twitter_url" 
                           class="form-control" 
                           value="<?php echo escapeHtml($settings['twitter_url'] ?? ''); ?>"
                           placeholder="https://twitter.com/yourusername">
                    <small class="form-help">Example: https://twitter.com/smcc_official</small>
                </div>
            </div>

            <div class="platform-item">
                <div class="platform-header">
                    <div class="platform-icon instagram">
                        <i class="fab fa-instagram"></i>
                    </div>
                    <div class="platform-info">
                        <h4>Instagram</h4>
                        <p>Connect your Instagram account</p>
                    </div>
                </div>
                <div class="form-group">
                    <label for="instagram_url">Instagram Profile URL</label>
                    <input type="url" 
                           id="instagram_url" 
                           name="instagram_url" 
                           class="form-control" 
                           value="<?php echo escapeHtml($settings['instagram_url'] ?? ''); ?>"
                           placeholder="https://instagram.com/yourusername">
                    <small class="form-help">Example: https://instagram.com/smcc_catbalogan</small>
                </div>
            </div>

            <div class="platform-item">
                <div class="platform-header">
                    <div class="platform-icon youtube">
                        <i class="fab fa-youtube"></i>
                    </div>
                    <div class="platform-info">
                        <h4>YouTube</h4>
                        <p>Connect your YouTube channel</p>
                    </div>
                </div>
                <div class="form-group">
                    <label for="youtube_url">YouTube Channel URL</label>
                    <input type="url" 
                           id="youtube_url" 
                           name="youtube_url" 
                           class="form-control" 
                           value="<?php echo escapeHtml($settings['youtube_url'] ?? ''); ?>"
                           placeholder="https://youtube.com/@yourchannel">
                    <small class="form-help">Example: https://youtube.com/@smcccatbalogan</small>
                </div>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" name="save_social" class="btn btn-primary">
                <i class="fas fa-save"></i> Save Changes
            </button>
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Settings
            </a>
        </div>
    </form>
</div>

<!-- Social Links Preview -->
<div class="preview-card">
    <h3><i class="fas fa-eye"></i> Social Media Links Preview</h3>
    <p class="preview-subtitle">Active social media links that will appear on your website</p>
    
    <div class="social-preview">
        <?php if (!empty($settings['facebook_url'])): ?>
        <a href="<?php echo escapeHtml($settings['facebook_url']); ?>" 
           class="social-link facebook" 
           target="_blank" 
           rel="noopener noreferrer">
            <i class="fab fa-facebook-f"></i>
        </a>
        <?php endif; ?>

        <?php if (!empty($settings['twitter_url'])): ?>
        <a href="<?php echo escapeHtml($settings['twitter_url']); ?>" 
           class="social-link twitter" 
           target="_blank" 
           rel="noopener noreferrer">
            <i class="fab fa-twitter"></i>
        </a>
        <?php endif; ?>

        <?php if (!empty($settings['instagram_url'])): ?>
        <a href="<?php echo escapeHtml($settings['instagram_url']); ?>" 
           class="social-link instagram" 
           target="_blank" 
           rel="noopener noreferrer">
            <i class="fab fa-instagram"></i>
        </a>
        <?php endif; ?>

        <?php if (!empty($settings['youtube_url'])): ?>
        <a href="<?php echo escapeHtml($settings['youtube_url']); ?>" 
           class="social-link youtube" 
           target="_blank" 
           rel="noopener noreferrer">
            <i class="fab fa-youtube"></i>
        </a>
        <?php endif; ?>

        <?php if (empty($settings['facebook_url']) && empty($settings['twitter_url']) && empty($settings['instagram_url']) && empty($settings['youtube_url'])): ?>
        <p class="no-links">No social media links configured yet</p>
        <?php endif; ?>
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

/* Social Platforms */
.social-platforms {
    display: flex;
    flex-direction: column;
    gap: 2rem;
    margin-bottom: 2rem;
}

.platform-item {
    padding: 2rem;
    background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
    border-radius: 12px;
    border: 2px solid var(--admin-border);
    transition: all 0.3s;
}

.platform-item:hover {
    border-color: var(--admin-primary);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    transform: translateY(-2px);
}

.platform-header {
    display: flex;
    gap: 1.25rem;
    align-items: center;
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 2px solid #f0f0f0;
}

.platform-icon {
    width: 60px;
    height: 60px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.75rem;
    color: white;
    flex-shrink: 0;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.platform-icon.facebook {
    background: linear-gradient(135deg, #1877f2, #0c63d4);
}

.platform-icon.twitter {
    background: linear-gradient(135deg, #1da1f2, #0d8bd9);
}

.platform-icon.instagram {
    background: linear-gradient(45deg, #f09433 0%, #e6683c 25%, #dc2743 50%, #cc2366 75%, #bc1888 100%);
}

.platform-icon.youtube {
    background: linear-gradient(135deg, #ff0000, #cc0000);
}

.platform-info {
    flex: 1;
}

.platform-info h4 {
    margin: 0 0 0.25rem 0;
    font-size: 1.15rem;
    font-weight: 700;
    color: var(--admin-text);
}

.platform-info p {
    margin: 0;
    font-size: 0.9rem;
    color: var(--admin-text-muted);
}

/* Form Group */
.form-group {
    margin-bottom: 0;
}

.form-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 600;
    font-size: 0.9rem;
    color: var(--admin-text);
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

/* Social Preview */
.social-preview {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
}

.social-link {
    width: 60px;
    height: 60px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    color: white;
    text-decoration: none;
    transition: all 0.3s;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.social-link:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.25);
}

.social-link.facebook {
    background: linear-gradient(135deg, #1877f2, #0c63d4);
}

.social-link.twitter {
    background: linear-gradient(135deg, #1da1f2, #0d8bd9);
}

.social-link.instagram {
    background: linear-gradient(45deg, #f09433 0%, #e6683c 25%, #dc2743 50%, #cc2366 75%, #bc1888 100%);
}

.social-link.youtube {
    background: linear-gradient(135deg, #ff0000, #cc0000);
}

.no-links {
    padding: 3rem;
    text-align: center;
    color: var(--admin-text-muted);
    background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
    border-radius: 12px;
    border: 2px dashed var(--admin-border);
    font-size: 1rem;
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
    
    .social-platforms {
        gap: 1.5rem;
    }
    
    .platform-item {
        padding: 1.5rem;
    }
    
    .platform-header {
        flex-direction: column;
        align-items: flex-start;
        text-align: left;
    }
    
    .platform-icon {
        width: 55px;
        height: 55px;
        font-size: 1.5rem;
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
    
    .social-link {
        width: 55px;
        height: 55px;
        font-size: 1.25rem;
    }
}
</style>

<?php include '../includes/admin-footer.php'; ?>
