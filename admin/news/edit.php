<?php
/**
 * MARIANCONNECT - Edit News
 */

// Authentication check
require_once '../includes/auth-check.php';

$db = getDB();
$errors = [];
$newsId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Fetch existing news
$stmt = $db->prepare("SELECT * FROM news WHERE news_id = ?");
$stmt->execute([$newsId]);
$news = $stmt->fetch();

if (!$news) {
    setFlashMessage('error', 'News article not found.');
    redirect('index.php');
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $title = sanitize($_POST['title'] ?? '');
    $slug = sanitize($_POST['slug'] ?? '');
    $excerpt = sanitize($_POST['excerpt'] ?? '');
    $content = $_POST['content'] ?? '';
    $category = sanitize($_POST['category'] ?? 'general');
    $status = sanitize($_POST['status'] ?? 'draft');
    $isFeatured = isset($_POST['is_featured']) ? 1 : 0;
    $publishedDate = !empty($_POST['published_date']) ? $_POST['published_date'] : null;
    
    // Validation
    if (empty($title)) {
        $errors[] = "Title is required";
    }
    
    if (empty($slug)) {
        $slug = generateSlug($title);
    }
    
    // Check if slug already exists (excluding current news)
    $slugCheck = $db->prepare("SELECT news_id FROM news WHERE slug = ? AND news_id != ?");
    $slugCheck->execute([$slug, $newsId]);
    if ($slugCheck->rowCount() > 0) {
        $errors[] = "Slug already exists. Please use a different one.";
    }
    
    if (empty($content)) {
        $errors[] = "Content is required";
    }
    
    // Handle image upload
    $featuredImage = null;
    if (isset($_FILES['featured_image']) && $_FILES['featured_image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../../assets/uploads/news';   // ✅ CORRECT PATH
        
        $uploadResult = secureFileUpload(
            $_FILES['featured_image'],
            $uploadDir,
            ALLOWED_IMAGE_TYPES
        );
        
        if ($uploadResult['success']) {
            // Delete old image if exists
            if (!empty($news['featured_image'])) {
                deleteUploadedFile($news['featured_image']);
            }
            
            // Save new path as: /uploads/news/filename.jpg
            $featuredImage = '/assets/uploads/news/' . $uploadResult['filename'];
        } else {
            $errors = array_merge($errors, $uploadResult['errors']);
        }
    }

    // Handle image removal
    if (isset($_POST['remove_image']) && $_POST['remove_image'] == '1') {
        if (!empty($news['featured_image'])) {
            deleteUploadedFile($news['featured_image']);
        }
        $featuredImage = null;
    }
    
    // If no errors, update database
    if (empty($errors)) {
        try {
            $sql = "
                UPDATE news SET
                    title = ?,
                    slug = ?,
                    excerpt = ?,
                    content = ?,
                    category = ?,
                    featured_image = ?,
                    status = ?,
                    is_featured = ?,
                    published_date = ?,
                    updated_at = NOW()
                WHERE news_id = ?
            ";
            
            $stmt = $db->prepare($sql);
            $result = $stmt->execute([
                $title,
                $slug,
                $excerpt,
                $content,
                $category,
                $featuredImage,
                $status,
                $isFeatured,
                $publishedDate,
                $newsId
            ]);
            
            if ($result) {
                // Log activity
                logActivity($_SESSION['admin_id'], 'update', 'news', $newsId, "Updated news: {$title}");
                
                // Set success message
                setFlashMessage('success', 'News article updated successfully!');
                
                // Refresh data
                $stmt = $db->prepare("SELECT * FROM news WHERE news_id = ?");
                $stmt->execute([$newsId]);
                $news = $stmt->fetch();
            }
        } catch (PDOException $e) {
            $errors[] = "Database error: " . $e->getMessage();
        }
    }
}

$pageTitle = 'Edit News Article';

// Add inline CSS to ensure styling loads
echo '<link rel="stylesheet" href="../assets/css/admin.css">';

include '../includes/admin-header.php';
?>

<div class="page-header">
    <div>
        <h1>Edit News Article</h1>
        <p class="subtitle">Last updated: <?php echo formatDate($news['updated_at'], 'F j, Y g:i A'); ?></p>
    </div>
    <div class="page-actions">
        <a href="../../pages/news-detail.php?slug=<?php echo $news['slug']; ?>" class="btn btn-info" target="_blank">
            <i class="fas fa-eye"></i> Preview
        </a>
        <a href="index.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to List
        </a>
    </div>
</div>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger">
    <strong>Please fix the following errors:</strong>
    <ul>
        <?php foreach ($errors as $error): ?>
            <li><?php echo escapeHtml($error); ?></li>
        <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<form method="POST" action="" enctype="multipart/form-data" class="news-form">
    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
    <input type="hidden" name="remove_image" id="removeImageFlag" value="0">
    
    <div class="form-grid">
        <!-- Main Content Column -->
        <div class="form-main">
            <div class="form-card">
                <h3>Article Content</h3>
                
                <!-- Title -->
                <div class="form-group">
                    <label for="title" class="required">Title</label>
                    <input 
                        type="text" 
                        id="title" 
                        name="title" 
                        class="form-control" 
                        required
                        maxlength="255"
                        value="<?php echo escapeHtml($news['title']); ?>"
                    >
                </div>
                
                <!-- Slug -->
                <div class="form-group">
                    <label for="slug">URL Slug</label>
                    <input 
                        type="text" 
                        id="slug" 
                        name="slug" 
                        class="form-control"
                        value="<?php echo escapeHtml($news['slug']); ?>"
                    >
                    <small class="form-text">Permalink: <a href="../../pages/news-detail.php?slug=<?php echo $news['slug']; ?>" target="_blank"><?php echo SITE_URL; ?>/pages/news-detail.php?slug=<?php echo $news['slug']; ?></a></small>
                </div>
                
                <!-- Excerpt -->
                <div class="form-group">
                    <label for="excerpt">Excerpt</label>
                    <textarea 
                        id="excerpt" 
                        name="excerpt" 
                        class="form-control" 
                        rows="3"
                        maxlength="500"
                    ><?php echo escapeHtml($news['excerpt']); ?></textarea>
                </div>
                
                <!-- Content -->
                <div class="form-group">
                    <label for="content" class="required">Content</label>
                    <textarea 
                        id="content" 
                        name="content" 
                        class="form-control tinymce-editor"
                        rows="15"
                        required
                    ><?php echo htmlspecialchars($news['content']); ?></textarea>
                </div>
            </div>
            
            <!-- Article Stats -->
            <div class="form-card">
                <h3>Article Statistics</h3>
                <div class="stats-grid">
                    <div class="stat-item">
                        <i class="fas fa-eye"></i>
                        <div>
                            <strong><?php echo number_format($news['views']); ?></strong>
                            <span>Views</span>
                        </div>
                    </div>
                    <div class="stat-item">
                        <i class="fas fa-calendar-plus"></i>
                        <div>
                            <strong><?php echo formatDate($news['created_at'], 'M j, Y'); ?></strong>
                            <span>Created</span>
                        </div>
                    </div>
                    <div class="stat-item">
                        <i class="fas fa-edit"></i>
                        <div>
                            <strong><?php echo timeAgo($news['updated_at']); ?></strong>
                            <span>Last Modified</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Sidebar Column -->
        <div class="form-sidebar">
            <!-- Publish Settings -->
            <div class="form-card">
                <h3>Publish Settings</h3>
                
                <div class="form-group">
                    <label for="status">Status</label>
                    <select id="status" name="status" class="form-control">
                        <option value="draft" <?php echo $news['status'] === 'draft' ? 'selected' : ''; ?>>Draft</option>
                        <option value="published" <?php echo $news['status'] === 'published' ? 'selected' : ''; ?>>Published</option>
                        <option value="archived" <?php echo $news['status'] === 'archived' ? 'selected' : ''; ?>>Archived</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="published_date">Publish Date</label>
                    <input 
                        type="datetime-local" 
                        id="published_date" 
                        name="published_date" 
                        class="form-control"
                        value="<?php echo $news['published_date'] ? date('Y-m-d\TH:i', strtotime($news['published_date'])) : date('Y-m-d\TH:i'); ?>"
                    >
                </div>
                
                <div class="form-group">
                    <label class="checkbox-label">
                        <input 
                            type="checkbox" 
                            name="is_featured" 
                            value="1"
                            <?php echo $news['is_featured'] ? 'checked' : ''; ?>
                        >
                        <span>⭐ Feature this article</span>
                    </label>
                </div>
            </div>
            
            <!-- Category -->
            <div class="form-card">
                <h3>Category</h3>
                
                <div class="form-group">
                    <select id="category" name="category" class="form-control">
                        <option value="general" <?php echo $news['category'] === 'general' ? 'selected' : ''; ?>>General</option>
                        <option value="academic" <?php echo $news['category'] === 'academic' ? 'selected' : ''; ?>>Academic</option>
                        <option value="sports" <?php echo $news['category'] === 'sports' ? 'selected' : ''; ?>>Sports</option>
                        <option value="events" <?php echo $news['category'] === 'events' ? 'selected' : ''; ?>>Events</option>
                        <option value="achievements" <?php echo $news['category'] === 'achievements' ? 'selected' : ''; ?>>Achievements</option>
                    </select>
                </div>
            </div>
            
            <!-- Featured Image -->
            <div class="form-card">
                <h3>Featured Image</h3>
                
                <div class="form-group">
                    <?php if (!empty($news['featured_image'])): ?>
                        <div class="current-image">
                            <img src="<?php echo escapeHtml(getImageUrl($news['featured_image'])); ?>" 
                                alt="Current image"
                                onerror="this.style.display='none'">
                            <button type="button" class="btn-remove-current" onclick="removeCurrentImage()">
                                <i class="fas fa-times"></i> Remove Image
                            </button>
                        </div>
                    <?php endif; ?>
                    
                    <div class="image-upload-area" id="imageUploadArea">
                        <div class="upload-placeholder">
                            <i class="fas fa-cloud-upload-alt"></i>
                            <p><?php echo !empty($news['featured_image']) ? 'Change Image' : 'Upload Image'; ?></p>
                            <small>JPG, PNG, GIF or WebP (Max 5MB)</small>
                        </div>
                        <input 
                            type="file" 
                            id="featured_image" 
                            name="featured_image" 
                            accept="image/*"
                            class="file-input"
                        >
                        <div class="image-preview" id="imagePreview" style="display: none;">
                            <img src="" alt="Preview" id="previewImage">
                            <button type="button" class="remove-image" id="removeImage">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Actions -->
            <div class="form-card">
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary btn-block">
                        <i class="fas fa-save"></i> Update Article
                    </button>
                    <a href="delete.php?id=<?php echo $news['news_id']; ?>" class="btn btn-danger btn-block">
                        <i class="fas fa-trash"></i> Delete Article
                    </a>
                </div>
            </div>
        </div>
    </div>
</form>

<!-- TinyMCE Editor -->
<script src="https://cdn.tiny.cloud/1/45anirrmzgk362e3h0rf9oosec2cxev5w0atdjl7srwi8wri/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
<script>
tinymce.init({
    selector: '.tinymce-editor',
    height: 500,
    menubar: false,
    plugins: [
        'advlist', 'autolink', 'lists', 'link', 'image', 'charmap',
        'searchreplace', 'visualblocks', 'code', 'fullscreen',
        'insertdatetime', 'table', 'wordcount'
    ],
    toolbar: 'undo redo | blocks | bold italic underline forecolor backcolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image | removeformat code',
    content_style: 'body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; font-size: 14px; padding: 10px; }',
    branding: false,
    promotion: false,
    statusbar: true,
    elementpath: false,
    resize: true,
    setup: function(editor) {
        // Sync content on change
        editor.on('change', function() {
            editor.save();
        });
    }
});

// IMPORTANT: Sync TinyMCE content before form submit
document.querySelector('.news-form').addEventListener('submit', function(e) {
    // Save TinyMCE content to textarea
    tinymce.triggerSave();
    
    // Validate content is not empty
    const content = document.getElementById('content').value.trim();
    if (!content || content === '' || content === '<p></p>' || content === '<p><br></p>') {
        e.preventDefault();
        alert('Please add content to your article before saving.');
        return false;
    }
    
    // Show loading state on button
    const submitButton = e.submitter;
    if (submitButton) {
        submitButton.disabled = true;
        const originalText = submitButton.innerHTML;
        submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
    }
    
    // Disable form changed warning
    formChanged = false;
});

// Remove current image
function removeCurrentImage() {
    if (confirm('Are you sure you want to remove the current image?')) {
        document.getElementById('removeImageFlag').value = '1';
        document.querySelector('.current-image').style.display = 'none';
    }
}

// Image upload preview
const fileInput = document.getElementById('featured_image');
const uploadArea = document.getElementById('imageUploadArea');
const imagePreview = document.getElementById('imagePreview');
const previewImage = document.getElementById('previewImage');
const removeImageBtn = document.getElementById('removeImage');

uploadArea.addEventListener('click', () => fileInput.click());

fileInput.addEventListener('change', function() {
    const file = this.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            previewImage.src = e.target.result;
            uploadArea.querySelector('.upload-placeholder').style.display = 'none';
            imagePreview.style.display = 'block';
        }
        reader.readAsDataURL(file);
    }
});

removeImageBtn.addEventListener('click', function(e) {
    e.stopPropagation();
    fileInput.value = '';
    uploadArea.querySelector('.upload-placeholder').style.display = 'flex';
    imagePreview.style.display = 'none';
    previewImage.src = '';
});

// Prevent accidental page leave
let formChanged = false;
document.querySelector('.news-form').addEventListener('input', () => formChanged = true);
window.addEventListener('beforeunload', function(e) {
    if (formChanged) {
        e.preventDefault();
        e.returnValue = '';
    }
});
document.querySelector('.news-form').addEventListener('submit', () => formChanged = false);
</script>

<style>
.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
}

.page-header h1 {
    font-size: 1.75rem;
    font-weight: 700;
    color: var(--admin-text);
}

.subtitle {
    color: var(--admin-text-muted);
    margin-top: 0.5rem;
}

.page-actions {
    display: flex;
    gap: 0.5rem;
}

.btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.5rem;
    background: var(--admin-primary);
    color: white;
    border: none;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 500;
    transition: all 0.3s;
    cursor: pointer;
}

.btn:hover {
    background: var(--admin-primary-dark);
    transform: translateY(-2px);
    box-shadow: var(--admin-shadow-lg);
}

.btn-secondary {
    background: #6c757d;
}

.btn-secondary:hover {
    background: #5a6268;
}

.btn-info {
    background: var(--admin-info);
}

.btn-info:hover {
    background: #138496;
}

.form-grid {
    display: grid;
    grid-template-columns: 1fr 350px;
    gap: 2rem;
}

.form-card {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
    box-shadow: var(--admin-shadow);
}

.form-card h3 {
    font-size: 1.1rem;
    font-weight: 600;
    margin-bottom: 1.5rem;
    padding-bottom: 0.75rem;
    border-bottom: 2px solid var(--admin-border);
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-group:last-child {
    margin-bottom: 0;
}

.form-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 500;
    color: var(--admin-text);
}

.form-group label.required::after {
    content: ' *';
    color: var(--admin-danger);
}

.form-control {
    width: 100%;
    padding: 0.75rem 1rem;
    border: 2px solid var(--admin-border);
    border-radius: 8px;
    font-size: 0.95rem;
    transition: all 0.3s;
}

.form-control:focus {
    outline: none;
    border-color: var(--admin-primary);
    box-shadow: 0 0 0 3px rgba(0, 63, 135, 0.1);
}

.form-text {
    display: block;
    margin-top: 0.5rem;
    font-size: 0.85rem;
    color: var(--admin-text-muted);
}

.form-text a {
    color: var(--admin-primary);
    text-decoration: none;
}

.form-text a:hover {
    text-decoration: underline;
}

.checkbox-label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    cursor: pointer;
    font-weight: 400;
}

.checkbox-label input[type="checkbox"] {
    width: 18px;
    height: 18px;
    cursor: pointer;
}

.stats-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 1rem;
}

.stat-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    background: var(--admin-hover);
    border-radius: 8px;
}

.stat-item i {
    font-size: 1.5rem;
    color: var(--admin-primary);
}

.stat-item strong {
    display: block;
    font-size: 1.1rem;
}

.stat-item span {
    font-size: 0.85rem;
    color: var(--admin-text-muted);
}

.current-image {
    margin-bottom: 1rem;
    position: relative;
}

.current-image img {
    width: 100%;
    border-radius: 8px;
    display: block;
}

.btn-remove-current {
    margin-top: 0.5rem;
    width: 100%;
    padding: 0.5rem;
    background: var(--admin-danger);
    color: white;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.3s;
}

.btn-remove-current:hover {
    background: #c82333;
}

.image-upload-area {
    position: relative;
    border: 2px dashed var(--admin-border);
    border-radius: 8px;
    padding: 2rem;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s;
}

.image-upload-area:hover {
    border-color: var(--admin-primary);
    background: var(--admin-hover);
}

.upload-placeholder {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
}

.upload-placeholder i {
    font-size: 3rem;
    color: var(--admin-text-muted);
}

.file-input {
    display: none;
}

.image-preview {
    position: relative;
}

.image-preview img {
    width: 100%;
    height: auto;
    border-radius: 8px;
}

.remove-image {
    position: absolute;
    top: 10px;
    right: 10px;
    width: 32px;
    height: 32px;
    background: var(--admin-danger);
    color: white;
    border: none;
    border-radius: 50%;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s;
}

.remove-image:hover {
    transform: scale(1.1);
}

.form-actions {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.btn-block {
    width: 100%;
    justify-content: center;
}

@media (max-width: 1024px) {
    .form-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<?php include '../includes/admin-footer.php'; ?>
