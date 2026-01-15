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
    $title = sanitize($_POST['title'] ?? '');
    $content = $_POST['content'] ?? ''; // Don't sanitize - TinyMCE handles this
    $isPublished = isset($_POST['is_published']) ? 1 : 0;
    
    if (empty($title)) $errors[] = "Title is required";
    if (empty($content)) $errors[] = "Content is required";
    
    if (empty($errors)) {
        try {
            $sql = "UPDATE pages SET title = ?, content = ?, is_published = ?, updated_by = ? WHERE page_id = ?";
            $stmt = $db->prepare($sql);
            $result = $stmt->execute([$title, $content, $isPublished, $_SESSION['admin_id'], $id]);
            
            if ($result) {
                logActivity($_SESSION['admin_id'], 'update', 'pages', $id, "Updated page: {$title}");
                setFlashMessage('success', 'Page updated successfully!');
                redirect('index.php');
            }
        } catch (PDOException $e) {
            $errors[] = "Error: " . $e->getMessage();
        }
    }
    
    $page = array_merge($page, $_POST);
}

$pageTitle = 'Edit Page';
include '../includes/admin-header.php';
?>

<div class="page-header">
    <h1>Edit Page</h1>
    <div class="header-actions">
        <a href="seo-settings.php?id=<?php echo $id; ?>" class="btn btn-secondary">
            <i class="fas fa-search"></i> SEO Settings
        </a>
        <a href="index.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back</a>
    </div>
</div>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger">
    <ul><?php foreach ($errors as $error): ?><li><?php echo escapeHtml($error); ?></li><?php endforeach; ?></ul>
</div>
<?php endif; ?>

<form method="POST" action="">
    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
    
    <div class="form-grid">
        <div class="form-main">
            <div class="form-card">
                <h3>Page Content</h3>
                
                <div class="form-group">
                    <label for="title" class="required">Page Title</label>
                    <input type="text" id="title" name="title" class="form-control" required maxlength="255" value="<?php echo escapeHtml($page['title']); ?>">
                </div>
                
                <div class="form-group">
                    <label for="content" class="required">Content</label>
                    <textarea id="content" name="content" class="form-control"><?php echo htmlspecialchars($page['content']); ?></textarea>
                </div>
            </div>
        </div>
        
        <div class="form-sidebar">
            <div class="form-card">
                <h3>Page Info</h3>
                
                <div class="info-item">
                    <strong>Page Type:</strong>
                    <span class="badge badge-type"><?php echo ucfirst(str_replace('_', ' ', $page['page_type'])); ?></span>
                </div>
                
                <div class="info-item">
                    <strong>URL Slug:</strong>
                    <code>/<?php echo escapeHtml($page['slug']); ?></code>
                </div>
                
                <div class="info-item">
                    <strong>Last Updated:</strong>
                    <?php echo date('M d, Y g:i A', strtotime($page['updated_at'])); ?>
                </div>
            </div>
            
            <div class="form-card">
                <h3>Publish Settings</h3>
                
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="is_published" value="1" <?php echo $page['is_published'] ? 'checked' : ''; ?>>
                        <span>Published</span>
                    </label>
                    <small class="form-text">Only published pages appear on the website</small>
                </div>
            </div>
            
            <div class="form-card">
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary btn-block">
                        <i class="fas fa-save"></i> Update Page
                    </button>
                    <a href="seo-settings.php?id=<?php echo $id; ?>" class="btn btn-secondary btn-block">
                        <i class="fas fa-search"></i> SEO Settings
                    </a>
                </div>
            </div>
        </div>
    </div>
</form>

<style>
.page-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:2rem}
.header-actions{display:flex;gap:0.75rem}
.form-grid{display:grid;grid-template-columns:1fr 350px;gap:2rem}
.form-card{background:white;border-radius:12px;padding:1.5rem;margin-bottom:1.5rem;box-shadow:var(--admin-shadow)}
.form-card h3{font-size:1.1rem;font-weight:600;margin-bottom:1.5rem;padding-bottom:0.75rem;border-bottom:2px solid var(--admin-border)}
.form-group{margin-bottom:1.5rem}
.form-group label{display:block;margin-bottom:0.5rem;font-weight:500}
.form-group label.required::after{content:' *';color:var(--admin-danger)}
.form-control{width:100%;padding:0.75rem 1rem;border:2px solid var(--admin-border);border-radius:8px}
.form-control:focus{outline:none;border-color:var(--admin-primary);box-shadow:0 0 0 3px rgba(0,63,135,0.1)}
.form-text{display:block;margin-top:0.5rem;font-size:0.85rem;color:var(--admin-text-muted)}
.checkbox-label{display:flex;align-items:center;gap:0.5rem;cursor:pointer}
.checkbox-label input{width:18px;height:18px}
.form-actions{display:flex;flex-direction:column;gap:0.75rem}
.btn-block{width:100%;justify-content:center}
.info-item{padding:0.75rem 0;border-bottom:1px solid var(--admin-border);font-size:0.9rem}
.info-item:last-child{border-bottom:none}
.info-item strong{display:block;margin-bottom:0.5rem;color:var(--admin-text)}
.info-item code{background:#f5f5f5;padding:0.25rem 0.5rem;border-radius:4px;font-size:0.85rem}
.badge-type{background:#e3f2fd;color:#1976d2;padding:0.35rem 0.75rem;border-radius:6px;font-size:0.85rem;font-weight:500}
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

<!-- TinyMCE WYSIWYG Editor -->
<script src="https://cdn.tiny.cloud/1/45anirrmzgk362e3h0rf9oosec2cxev5w0atdjl7srwi8wri/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
<script>
tinymce.init({
    selector: '#content',
    height: 500,
    menubar: true,
    plugins: [
        'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview',
        'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
        'insertdatetime', 'media', 'table', 'help', 'wordcount'
    ],
    toolbar: 'undo redo | blocks | bold italic underline strikethrough | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image | removeformat | code fullscreen',
    content_style: 'body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; font-size: 14px; line-height: 1.6; }',
    branding: false
});
</script>

<?php include '../includes/admin-footer.php'; ?>
