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
        <a href="index.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back</a>
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
                    <strong>Page:</strong>
                    <?php echo escapeHtml($page['title']); ?>
                </div>
                
                <div class="info-item">
                    <strong>Type:</strong>
                    <span class="badge badge-type"><?php echo ucfirst(str_replace('_', ' ', $page['page_type'])); ?></span>
                </div>
                
                <div class="info-item">
                    <strong>URL:</strong>
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
.page-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:2rem}
.header-actions{display:flex;gap:0.75rem}
.info-banner{background:#fff3e0;border-left:4px solid #ff9800;padding:1rem 1.5rem;border-radius:8px;margin-bottom:2rem;display:flex;gap:1rem;align-items:flex-start}
.info-banner i{color:#ff9800;font-size:1.5rem;margin-top:0.25rem}
.form-grid{display:grid;grid-template-columns:1fr 350px;gap:2rem}
.form-card{background:white;border-radius:12px;padding:1.5rem;margin-bottom:1.5rem;box-shadow:var(--admin-shadow)}
.form-card h3{font-size:1.1rem;font-weight:600;margin-bottom:1.5rem;padding-bottom:0.75rem;border-bottom:2px solid var(--admin-border)}
.form-group{margin-bottom:1.5rem}
.form-group label{display:block;margin-bottom:0.5rem;font-weight:500}
.form-control{width:100%;padding:0.75rem 1rem;border:2px solid var(--admin-border);border-radius:8px}
.form-control:focus{outline:none;border-color:var(--admin-primary);box-shadow:0 0 0 3px rgba(0,63,135,0.1)}
.form-text{display:block;margin-top:0.5rem;font-size:0.85rem;color:var(--admin-text-muted)}
.char-counter{margin-top:0.5rem;font-size:0.85rem;color:var(--admin-text-muted);text-align:right}
.char-counter.warning{color:#ff9800}
.char-counter.error{color:#f44336}
.search-preview{background:#f8f9fa;border:1px solid var(--admin-border);border-radius:8px;padding:1.5rem}
.preview-url{color:#1a73e8;font-size:0.85rem;margin-bottom:0.5rem}
.preview-title{color:#1a0dab;font-size:1.25rem;font-weight:400;margin-bottom:0.5rem;cursor:pointer}
.preview-title:hover{text-decoration:underline}
.preview-description{color:#545454;font-size:0.9rem;line-height:1.5}
.info-item{padding:0.75rem 0;border-bottom:1px solid var(--admin-border);font-size:0.9rem}
.info-item:last-child{border-bottom:none}
.info-item strong{display:block;margin-bottom:0.5rem;color:var(--admin-text)}
.info-item code{background:#f5f5f5;padding:0.25rem 0.5rem;border-radius:4px;font-size:0.85rem}
.badge-type{background:#e3f2fd;color:#1976d2;padding:0.35rem 0.75rem;border-radius:6px;font-size:0.85rem;font-weight:500}
.tips-card{background:#e8f5e9}
.tips-list{list-style:none;padding:0;margin:0}
.tips-list li{padding:0.5rem 0;color:var(--admin-text);font-size:0.9rem;display:flex;align-items:flex-start;gap:0.5rem}
.tips-list i{color:#4caf50;margin-top:0.25rem;flex-shrink:0}
.form-actions{display:flex;flex-direction:column;gap:0.75rem}
.btn-block{width:100%;justify-content:center}
.alert{padding:1rem;border-radius:8px;margin-bottom:1.5rem}
.alert-danger{background:#ffebee;border:1px solid #ef5350;color:#c62828}
.alert ul{margin:0;padding-left:1.5rem}
.btn{padding:0.6rem 1.25rem;border:none;border-radius:8px;font-weight:500;cursor:pointer;display:inline-flex;align-items:center;gap:0.5rem;text-decoration:none;transition:all 0.2s}
.btn-primary{background:var(--admin-primary);color:white}
.btn-primary:hover{background:#004a99;transform:translateY(-2px)}
.btn-secondary{background:#6c757d;color:white}
.btn-secondary:hover{background:#5a6268}
@media(max-width:1024px){.form-grid{grid-template-columns:1fr}}
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
    titleCount.parentElement.classList.toggle('warning', titleLen > 50 && titleLen <= 60);
    titleCount.parentElement.classList.toggle('error', titleLen > 60);
    
    // Description counter
    const descLen = metaDesc.value.length;
    descCount.textContent = descLen;
    descCount.parentElement.classList.toggle('warning', descLen > 150 && descLen <= 160);
    descCount.parentElement.classList.toggle('error', descLen > 160);
    
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
