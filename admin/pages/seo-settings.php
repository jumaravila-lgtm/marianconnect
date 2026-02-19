<?php
require_once '../includes/auth-check.php';
$db = getDB();
$errors = [];

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    setFlashMessage('error', 'Invalid page ID');
    redirect('index.php');
}

try {
    $stmt = $db->prepare("SELECT * FROM pages WHERE page_id = ?");
    $stmt->execute([$id]);
    $page = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$page) {
        setFlashMessage('error', 'Page not found');
        redirect('index.php');
    }
} catch (PDOException $e) {
    setFlashMessage('error', 'Database error');
    redirect('index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $metaTitle = sanitize($_POST['meta_title'] ?? '');
    $metaDescription = sanitize($_POST['meta_description'] ?? '');
    
    try {
        $sql = "UPDATE pages SET meta_title = ?, meta_description = ?, updated_by = ? WHERE page_id = ?";
        $stmt = $db->prepare($sql);
        $result = $stmt->execute([$metaTitle, $metaDescription, $_SESSION['admin_id'], $id]);
        
        if ($result) {
            logActivity($_SESSION['admin_id'], 'update', 'pages', $id, "Updated SEO settings for: {$page['title']}");
            setFlashMessage('success', 'SEO settings updated successfully!');
            redirect('index.php');
        }
    } catch (PDOException $e) {
        $errors[] = "Error: " . $e->getMessage();
    }
    
    $page = array_merge($page, $_POST);
}

$pageTitle = 'SEO Settings';
include '../includes/admin-header.php';
?>

<div class="page-header">
    <h1>SEO Settings</h1>
    <div class="header-actions">
        <a href="edit.php?id=<?php echo $id; ?>" class="btn btn-secondary">
            <i class="fas fa-edit"></i> Edit Content
        </a>
        <a href="index.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back
        </a>
    </div>
</div>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger">
    <ul><?php foreach ($errors as $error): ?><li><?php echo escapeHtml($error); ?></li><?php endforeach; ?></ul>
</div>
<?php endif; ?>

<div class="info-banner">
    <i class="fas fa-lightbulb"></i>
    <div>
        <strong>About SEO Settings:</strong> These settings control how your page appears in search engine results (Google, Bing, etc.). 
        Good SEO meta tags help your website rank better and attract more visitors.
    </div>
</div>

<form method="POST" action="">
    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
    
    <div class="form-grid">
        <div class="form-main">
            <div class="form-card">
                <h3>Search Engine Optimization</h3>
                
                <div class="form-group">
                    <label for="meta_title">Meta Title</label>
                    <input type="text" id="meta_title" name="meta_title" class="form-control" maxlength="255" value="<?php echo escapeHtml($page['meta_title'] ?? $page['title']); ?>" placeholder="<?php echo escapeHtml($page['title']); ?>">
                    <small class="form-text">Recommended: 50-60 characters. This appears as the clickable headline in search results.</small>
                    <div class="char-counter">
                        <span id="title-count">0</span> / 60 characters
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="meta_description">Meta Description</label>
                    <textarea id="meta_description" name="meta_description" class="form-control" rows="4" maxlength="160"><?php echo escapeHtml($page['meta_description'] ?? ''); ?></textarea>
                    <small class="form-text">Recommended: 150-160 characters. This appears as the description snippet in search results.</small>
                    <div class="char-counter">
                        <span id="desc-count">0</span> / 160 characters
                    </div>
                </div>
            </div>
            
            <div class="form-card">
                <h3>Search Result Preview</h3>
                <div class="search-preview">
                    <div class="preview-url">
                        <?php echo $_SERVER['HTTP_HOST']; ?>/<?php echo escapeHtml($page['slug']); ?>
                    </div>
                    <div class="preview-title" id="preview-title">
                        <?php echo escapeHtml($page['meta_title'] ?: $page['title']); ?>
                    </div>
                    <div class="preview-description" id="preview-description">
                        <?php echo escapeHtml($page['meta_description'] ?: 'No meta description set. Add one to improve your search engine visibility.'); ?>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="form-sidebar">
            <div class="form-card">
                <h3>Page Info</h3>
                
                <div class="info-item">
                    <span class="info-label">Page:</span>
                    <span class="info-value"><?php echo escapeHtml($page['title']); ?></span>
                </div>
                
                <div class="info-item">
                    <span class="info-label">Type:</span>
                    <span class="badge badge-type"><?php echo ucfirst(str_replace('_', ' ', $page['page_type'])); ?></span>
                </div>
                
                <div class="info-item">
                    <span class="info-label">URL:</span>
                    <code>/<?php echo escapeHtml($page['slug']); ?></code>
                </div>
            </div>
            
            <div class="form-card tips-card">
                <h3>SEO Tips</h3>
                <ul class="tips-list">
                    <li><i class="fas fa-check-circle"></i> Include your main keyword in the meta title</li>
                    <li><i class="fas fa-check-circle"></i> Make the description compelling and actionable</li>
                    <li><i class="fas fa-check-circle"></i> Keep titles under 60 characters</li>
                    <li><i class="fas fa-check-circle"></i> Keep descriptions under 160 characters</li>
                    <li><i class="fas fa-check-circle"></i> Make each page's meta unique</li>
                </ul>
            </div>
            
            <div class="form-card">
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary btn-block">
                        <i class="fas fa-save"></i> Save SEO Settings
                    </button>
                </div>
            </div>
        </div>
    </div>
</form>

<style>
/* Page Header */
.page-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 2rem;
    padding-bottom: 1.5rem;
    border-bottom: 1px solid var(--admin-border);
}

.page-header h1 {
    font-size: 1.75rem;
    font-weight: 700;
    color: var(--admin-text);
    margin: 0;
}

.header-actions {
    display: flex;
    gap: 0.75rem;
}

/* Buttons */
.btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.5rem;
    border: none;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 500;
    font-size: 0.95rem;
    transition: all 0.3s;
    cursor: pointer;
}

.btn-primary {
    background: var(--admin-primary);
    color: white;
}

.btn-primary:hover {
    background: var(--admin-primary-dark);
    transform: translateY(-2px);
    box-shadow: 0 8px 16px rgba(0, 63, 135, 0.25);
}

.btn-secondary {
    background: white;
    color: var(--admin-primary);
    border: 2px solid var(--admin-border);
}

.btn-secondary:hover {
    background: var(--admin-primary);
    color: white;
    border-color: var(--admin-primary);
    transform: translateY(-2px);
    box-shadow: var(--admin-shadow-lg);
}

.btn-block {
    width: 100%;
    justify-content: center;
}

/* Info Banner */
.info-banner {
    background: linear-gradient(135deg, #fff8e1 0%, #fff3e0 100%);
    border-left: 4px solid #ff9800;
    padding: 1.25rem 1.5rem;
    border-radius: 8px;
    margin-bottom: 2rem;
    display: flex;
    gap: 1rem;
    align-items: flex-start;
    box-shadow: 0 2px 8px rgba(255, 152, 0, 0.1);
}

.info-banner i {
    color: #ff9800;
    font-size: 1.5rem;
    margin-top: 0.25rem;
    flex-shrink: 0;
}

.info-banner strong {
    color: #e65100;
}

/* Form Grid Layout */
.form-grid {
    display: grid;
    grid-template-columns: 1fr 380px;
    gap: 1.5rem;
    align-items: start;
}

/* Form Cards */
.form-card {
    background: white;
    border-radius: 12px;
    padding: 2rem;
    margin-bottom: 1.5rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
    border: 1px solid var(--admin-border);
    transition: all 0.3s;
}

.form-card:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
}

.form-card h3 {
    font-size: 1rem;
    font-weight: 600;
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 2px solid var(--admin-border);
    color: var(--admin-text);
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.form-card h3::before {
    content: '';
    width: 4px;
    height: 20px;
    background: var(--admin-primary);
    border-radius: 2px;
}

/* Form Groups */
.form-group {
    margin-bottom: 1.75rem;
}

.form-group:last-child {
    margin-bottom: 0;
}

.form-group label {
    display: block;
    margin-bottom: 0.625rem;
    font-weight: 600;
    font-size: 0.9rem;
    color: var(--admin-text);
}

/* Form Controls */
.form-control {
    width: 100%;
    padding: 0.875rem 1rem;
    border: 2px solid var(--admin-border);
    border-radius: 8px;
    font-size: 0.95rem;
    font-family: inherit;
    transition: all 0.3s ease;
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

textarea.form-control {
    resize: vertical;
    min-height: 100px;
    line-height: 1.6;
}

/* Form Text Helper */
.form-text {
    display: block;
    margin-top: 0.5rem;
    font-size: 0.825rem;
    color: var(--admin-text-muted);
    line-height: 1.4;
}

/* Character Counter */
.char-counter {
    margin-top: 0.5rem;
    font-size: 0.85rem;
    color: var(--admin-text-muted);
    text-align: right;
    font-weight: 500;
}

.char-counter.warning {
    color: #ff9800;
}

.char-counter.error {
    color: #f44336;
}

/* Search Preview */
.search-preview {
    background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
    border: 2px solid var(--admin-border);
    border-radius: 8px;
    padding: 1.5rem;
}

.preview-url {
    color: #1a73e8;
    font-size: 0.85rem;
    margin-bottom: 0.5rem;
    font-weight: 500;
}

.preview-title {
    color: #1a0dab;
    font-size: 1.25rem;
    font-weight: 400;
    margin-bottom: 0.5rem;
    cursor: pointer;
    line-height: 1.3;
}

.preview-title:hover {
    text-decoration: underline;
}

.preview-description {
    color: #545454;
    font-size: 0.9rem;
    line-height: 1.6;
}

/* Info Items */
.info-item {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    padding: 1rem;
    background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
    border-radius: 8px;
    margin-bottom: 0.75rem;
    border: 1px solid var(--admin-border);
}

.info-item:last-child {
    margin-bottom: 0;
}

.info-label {
    color: var(--admin-text-muted);
    font-weight: 600;
    font-size: 0.825rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.info-value {
    color: var(--admin-text);
    font-weight: 500;
    font-size: 0.95rem;
}

.info-item code {
    background: #f5f5f5;
    padding: 0.35rem 0.65rem;
    border-radius: 4px;
    font-size: 0.85rem;
    color: var(--admin-text);
    font-weight: 500;
    display: inline-block;
}

.badge-type {
    background: #e3f2fd;
    color: #1976d2;
    padding: 0.35rem 0.75rem;
    border-radius: 6px;
    font-size: 0.85rem;
    font-weight: 500;
    display: inline-block;
}

/* Tips Card */
.tips-card {
    background: linear-gradient(135deg, #e8f5e9 0%, #f1f8f4 100%);
    border: 1px solid #c8e6c9;
}

.tips-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.tips-list li {
    padding: 0.75rem 0;
    color: var(--admin-text);
    font-size: 0.9rem;
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
    line-height: 1.5;
}

.tips-list li:not(:last-child) {
    border-bottom: 1px solid rgba(76, 175, 80, 0.1);
}

.tips-list i {
    color: #4caf50;
    margin-top: 0.25rem;
    flex-shrink: 0;
}

/* Form Actions */
.form-actions {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

/* Sidebar Sticky */
.form-sidebar {
    position: sticky;
    top: 2rem;
}

/* Alert Styling */
.alert {
    padding: 1rem 1.25rem;
    border-radius: 8px;
    margin-bottom: 1.5rem;
    border-left: 4px solid;
}

.alert-danger {
    background: #f8d7da;
    color: #721c24;
    border-left-color: #dc3545;
}

.alert ul {
    margin: 0.5rem 0 0 1.5rem;
    padding-left: 0;
}

.alert li {
    margin: 0.25rem 0;
}

/* Responsive */
@media (max-width: 1024px) {
    .form-grid {
        grid-template-columns: 1fr;
    }
    
    .form-sidebar {
        position: static;
    }
    
    .page-header {
        flex-direction: column;
        gap: 1rem;
        align-items: flex-start;
    }
    
    .header-actions {
        width: 100%;
        flex-wrap: wrap;
    }
    
    .btn {
        flex: 1;
        min-width: 140px;
        justify-content: center;
    }
}

@media (max-width: 640px) {
    .form-card {
        padding: 1.5rem;
    }
    
    .header-actions {
        flex-direction: column;
    }
    
    .btn {
        width: 100%;
    }
    
    .info-banner {
        flex-direction: column;
        text-align: center;
    }
    
    .info-banner i {
        margin: 0;
    }
}
</style>

<script>
// Character counters and live preview
const metaTitle = document.getElementById('meta_title');
const metaDesc = document.getElementById('meta_description');
const titleCount = document.getElementById('title-count');
const descCount = document.getElementById('desc-count');
const previewTitle = document.getElementById('preview-title');
const previewDesc = document.getElementById('preview-description');

function updateCounters() {
    // Title counter
    const titleLen = metaTitle.value.length;
    titleCount.textContent = titleLen;
    titleCount.parentElement.classList.remove('warning', 'error');
    if (titleLen > 50 && titleLen <= 60) {
        titleCount.parentElement.classList.add('warning');
    } else if (titleLen > 60) {
        titleCount.parentElement.classList.add('error');
    }
    
    // Description counter
    const descLen = metaDesc.value.length;
    descCount.textContent = descLen;
    descCount.parentElement.classList.remove('warning', 'error');
    if (descLen > 150 && descLen <= 160) {
        descCount.parentElement.classList.add('warning');
    } else if (descLen > 160) {
        descCount.parentElement.classList.add('error');
    }
    
    // Live preview
    previewTitle.textContent = metaTitle.value || '<?php echo addslashes($page['title']); ?>';
    previewDesc.textContent = metaDesc.value || 'No meta description set. Add one to improve your search engine visibility.';
}

metaTitle.addEventListener('input', updateCounters);
metaDesc.addEventListener('input', updateCounters);

// Initialize
updateCounters();
</script>

<?php include '../includes/admin-footer.php'; ?>
