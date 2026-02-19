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

.btn-info {
    background: white;
    color: var(--admin-info);
    border: 2px solid var(--admin-info);
}

.btn-info:hover {
    background: var(--admin-info);
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(23, 162, 184, 0.25);
}

.btn-danger {
    background: white;
    color: var(--admin-danger);
    border: 2px solid var(--admin-danger);
}

.btn-danger:hover {
    background: var(--admin-danger);
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(220, 53, 69, 0.25);
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

textarea.form-control {
    resize: vertical;
    min-height: 150px;
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

.form-text a {
    color: var(--admin-primary);
    text-decoration: none;
    word-break: break-all;
}

.form-text a:hover {
    text-decoration: underline;
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

/* Article Statistics */
.stats-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 1rem;
}

.stat-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1.25rem;
    background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
    border-radius: 10px;
    border: 1px solid var(--admin-border);
    transition: all 0.3s;
}

.stat-item:hover {
    transform: translateX(5px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
}

.stat-item i {
    font-size: 1.75rem;
    color: var(--admin-primary);
    min-width: 35px;
    text-align: center;
}

.stat-item div {
    flex: 1;
}

.stat-item strong {
    display: block;
    font-size: 1.15rem;
    font-weight: 700;
    color: var(--admin-text);
    margin-bottom: 0.15rem;
}

.stat-item span {
    font-size: 0.825rem;
    color: var(--admin-text-muted);
    font-weight: 500;
}

/* Current Image */
.current-image {
    margin-bottom: 1.5rem;
    position: relative;
    border-radius: 10px;
    overflow: hidden;
    border: 2px solid var(--admin-border);
}

.current-image img {
    width: 100%;
    display: block;
    border-radius: 8px;
}

.btn-remove-current {
    margin-top: 0.75rem;
    width: 100%;
    padding: 0.75rem;
    background: var(--admin-danger);
    color: white;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
}

.btn-remove-current:hover {
    background: #c82333;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(220, 53, 69, 0.3);
}

/* Image Upload Area */
.image-upload-area {
    position: relative;
    border: 2px dashed var(--admin-border);
    border-radius: 12px;
    padding: 3rem 2rem;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s;
    background: #fafbfc;
}

.image-upload-area:hover {
    border-color: var(--admin-primary);
    background: #f0f4ff;
    border-style: solid;
}

.upload-placeholder {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.75rem;
}

.upload-placeholder i {
    font-size: 3rem;
    color: var(--admin-primary);
    opacity: 0.5;
}

.upload-placeholder p {
    font-weight: 600;
    color: var(--admin-text);
    margin: 0;
    font-size: 0.95rem;
}

.upload-placeholder small {
    color: var(--admin-text-muted);
    font-size: 0.825rem;
}

.file-input {
    display: none;
}

/* Image Preview */
.image-preview {
    position: relative;
    border-radius: 8px;
    overflow: hidden;
}

.image-preview img {
    width: 100%;
    height: auto;
    display: block;
    border-radius: 8px;
}

.remove-image {
    position: absolute;
    top: 0.75rem;
    right: 0.75rem;
    width: 36px;
    height: 36px;
    background: rgba(220, 53, 69, 0.95);
    color: white;
    border: 2px solid white;
    border-radius: 50%;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s;
    font-size: 1.1rem;
}

.remove-image:hover {
    background: var(--admin-danger);
    transform: scale(1.1) rotate(90deg);
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

.btn-block {
    width: 100%;
    justify-content: center;
}

/* Sidebar Sticky */
.form-sidebar {
    position: sticky;
    top: 2rem;
}

/* Select Styling */
select.form-control {
    cursor: pointer;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23333' d='M6 9L1 4h10z'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 1rem center;
    background-size: 12px;
    appearance: none;
    padding-right: 2.5rem;
}

/* Date Input Styling */
input[type="datetime-local"].form-control {
    cursor: pointer;
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
    margin: 0;
    padding-left: 1.25rem;
}

.alert li {
    margin: 0.25rem 0;
}

/* TinyMCE Enhancement */
.tox-tinymce {
    border: 2px solid var(--admin-border) !important;
    border-radius: 8px !important;
}

.tox-tinymce:focus-within {
    border-color: var(--admin-primary) !important;
    box-shadow: 0 0 0 4px rgba(0, 63, 135, 0.08) !important;
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
    
    .image-upload-area {
        padding: 2rem 1rem;
    }
    
    .header-actions {
        flex-direction: column;
    }
    
    .btn {
        width: 100%;
    }
}
</style>

<?php include '../includes/admin-footer.php'; ?>
