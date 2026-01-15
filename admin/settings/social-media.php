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
.page-header{margin-bottom:2rem}
.page-subtitle{color:var(--admin-text-muted);margin-top:0.5rem}

.settings-nav{display:flex;gap:0.5rem;margin-bottom:2rem;background:white;padding:0.75rem;border-radius:12px;box-shadow:var(--admin-shadow);overflow-x:auto}
.settings-nav-item{padding:0.75rem 1.5rem;border-radius:8px;text-decoration:none;color:var(--admin-text);font-weight:500;display:flex;align-items:center;gap:0.5rem;transition:all 0.2s;white-space:nowrap}
.settings-nav-item:hover{background:#f5f5f5}
.settings-nav-item.active{background:var(--admin-primary);color:white}

.settings-card{background:white;border-radius:12px;padding:2rem;box-shadow:var(--admin-shadow);margin-bottom:2rem}

.social-platforms{display:flex;flex-direction:column;gap:2rem;margin-bottom:2rem}

.platform-item{padding:1.5rem;background:#f8f9fa;border-radius:12px;border:2px solid transparent;transition:all 0.2s}
.platform-item:hover{border-color:var(--admin-border)}

.platform-header{display:flex;gap:1rem;align-items:center;margin-bottom:1rem}
.platform-icon{width:50px;height:50px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1.5rem;color:white;flex-shrink:0}
.platform-icon.facebook{background:#1877f2}
.platform-icon.twitter{background:#1da1f2}
.platform-icon.instagram{background:linear-gradient(45deg,#f09433 0%,#e6683c 25%,#dc2743 50%,#cc2366 75%,#bc1888 100%)}
.platform-icon.youtube{background:#ff0000}

.platform-info h4{margin:0 0 0.25rem 0;font-size:1.1rem;font-weight:600;color:var(--admin-text)}
.platform-info p{margin:0;font-size:0.85rem;color:var(--admin-text-muted)}

.form-group{margin-bottom:0}
.form-group label{display:block;margin-bottom:0.5rem;font-weight:500;font-size:0.9rem;color:var(--admin-text)}
.form-control{width:100%;padding:0.75rem 1rem;border:2px solid var(--admin-border);border-radius:8px;font-size:0.95rem;transition:border-color 0.2s}
.form-control:focus{outline:none;border-color:var(--admin-primary)}
.form-help{display:block;margin-top:0.5rem;font-size:0.85rem;color:var(--admin-text-muted)}

.form-actions{display:flex;gap:1rem;padding-top:2rem;border-top:1px solid var(--admin-border)}

.preview-card{background:white;border-radius:12px;padding:2rem;box-shadow:var(--admin-shadow)}
.preview-card h3{font-size:1.1rem;font-weight:600;margin-bottom:0.5rem;display:flex;align-items:center;gap:0.5rem;color:var(--admin-text)}
.preview-subtitle{color:var(--admin-text-muted);margin-bottom:2rem;font-size:0.9rem}

.social-preview{display:flex;gap:1rem;flex-wrap:wrap}
.social-link{width:50px;height:50px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1.25rem;color:white;text-decoration:none;transition:transform 0.2s}
.social-link:hover{transform:translateY(-3px)}
.social-link.facebook{background:#1877f2}
.social-link.twitter{background:#1da1f2}
.social-link.instagram{background:linear-gradient(45deg,#f09433 0%,#e6683c 25%,#dc2743 50%,#cc2366 75%,#bc1888 100%)}
.social-link.youtube{background:#ff0000}

.no-links{padding:2rem;text-align:center;color:var(--admin-text-muted);background:#f8f9fa;border-radius:8px}

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
