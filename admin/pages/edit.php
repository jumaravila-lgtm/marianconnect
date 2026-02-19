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
                <h3>Meta Info</h3>
                <div class="meta-info">
                    <div class="meta-item">
                        <span class="meta-label">Created:</span>
                        <span class="meta-value"><?php echo date('M d, Y', strtotime($page['created_at'])); ?></span>
                    </div>
                    <div class="meta-item">
                        <span class="meta-label">Updated:</span>
                        <span class="meta-value"><?php echo date('M d, Y', strtotime($page['updated_at'])); ?></span>
                    </div>
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

.form-group label.required::after {
    content: ' *';
    color: var(--admin-danger);
    font-weight: 700;
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

/* Form Text Helper */
.form-text {
    display: block;
    margin-top: 0.5rem;
    font-size: 0.825rem;
    color: var(--admin-text-muted);
    line-height: 1.4;
}

/* Checkbox */
.checkbox-label {
    display: flex;
    align-items: center;
    gap: 0.625rem;
    cursor: pointer;
    font-weight: 500;
    padding: 0.75rem;
    border-radius: 8px;
    transition: background 0.3s;
}

.checkbox-label:hover {
    background: var(--admin-hover);
}

.checkbox-label input[type="checkbox"] {
    width: 20px;
    height: 20px;
    cursor: pointer;
    accent-color: var(--admin-primary);
}

/* Info Items */
.info-item {
    padding: 1rem;
    background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
    border-radius: 8px;
    margin-bottom: 0.75rem;
    border: 1px solid var(--admin-border);
    font-size: 0.9rem;
}

.info-item:last-child {
    margin-bottom: 0;
}

.info-item strong {
    display: block;
    color: var(--admin-text-muted);
    font-weight: 600;
    font-size: 0.825rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin-bottom: 0.5rem;
}

.info-item code {
    background: #f5f5f5;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-size: 0.85rem;
    color: var(--admin-text);
    font-weight: 500;
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

/* Meta Information */
.meta-info {
    font-size: 0.9rem;
}

.meta-item {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    padding: 1rem;
    background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
    border-radius: 8px;
    margin-bottom: 0.75rem;
    border: 1px solid var(--admin-border);
}

.meta-item:last-child {
    margin-bottom: 0;
}

.meta-label {
    color: var(--admin-text-muted);
    font-weight: 600;
    font-size: 0.825rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.meta-value {
    color: var(--admin-text);
    font-weight: 500;
    font-size: 0.95rem;
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
}
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
