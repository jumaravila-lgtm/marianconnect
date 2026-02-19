<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../logs/php_errors.log');
/**
 * MARIANCONNECT - Create News (FIXED VERSION)
 * Removed conflicting upload code
 */

// Authentication check
require_once '../includes/auth-check.php';

$db = getDB();
$errors = [];
$success = false;

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid security token. Please refresh and try again.';
    }
    
    // Get form data
    $title = sanitize($_POST['title'] ?? '');
    $slug = sanitize($_POST['slug'] ?? '');
    $excerpt = sanitize($_POST['excerpt'] ?? '');
    $content = $_POST['content'] ?? ''; // Don't sanitize HTML content
    $category = sanitize($_POST['category'] ?? 'general');
    $status = sanitize($_POST['status'] ?? 'draft'); // Get status from dropdown
    $isFeatured = isset($_POST['is_featured']) ? 1 : 0;
    $publishedDate = !empty($_POST['published_date']) ? $_POST['published_date'] : null;
    
    // Validation
    if (empty($title)) {
        $errors[] = "Title is required";
    }
    
    if (empty($slug)) {
        $slug = generateSlug($title);
    }
    
    // Check if slug already exists
    $slugCheck = $db->prepare("SELECT news_id FROM news WHERE slug = ?");
    $slugCheck->execute([$slug]);
    if ($slugCheck->rowCount() > 0) {
        $errors[] = "Slug already exists. Please use a different one.";
    }
    
    if (empty($content)) {
        $errors[] = "Content is required";
    }
    
    // Handle image upload
    $featuredImage = null;
    if (isset($_FILES['featured_image']) && $_FILES['featured_image']['error'] === UPLOAD_ERR_OK) {
        // ✅ CORRECT: Upload directory relative to this file
        $uploadDir = '../../assets/uploads/news';
        
        // Create directory if it doesn't exist
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $uploadResult = secureFileUpload(
            $_FILES['featured_image'],
            $uploadDir,
            ALLOWED_IMAGE_TYPES
        );
        
        if ($uploadResult['success']) {
            // ✅ CORRECT: Store as /assets/uploads/news/filename.jpg (no /marianconnect/)
            $featuredImage = '/assets/uploads/news/' . $uploadResult['filename'];
        } else {
            $errors = array_merge($errors, $uploadResult['errors']);
        }
    }
    
    // If no errors, insert into database
    if (empty($errors)) {
        try {
            $sql = "
                INSERT INTO news 
                (title, slug, excerpt, content, category, featured_image, author_id, status, is_featured, published_date)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ";
            
            $stmt = $db->prepare($sql);
            $result = $stmt->execute([
                $title,
                $slug,
                $excerpt,
                $content,
                $category,
                $featuredImage,
                $_SESSION['admin_id'],
                $status,
                $isFeatured,
                $publishedDate
            ]);
            
            if ($result) {
                $newsId = $db->lastInsertId();
                
                // Log activity
                logActivity($_SESSION['admin_id'], 'create', 'news', $newsId, "Created news: {$title}");
                
                // Set success message
                setFlashMessage('success', 'News article created successfully!');
                
                // Redirect to edit page or list
                redirect('edit.php?id=' . $newsId);
            }
        } catch (PDOException $e) {
            $errors[] = "Database error: " . $e->getMessage();
            error_log("News creation error: " . $e->getMessage());
        }
    }
}

$pageTitle = 'Create News Article';
include '../includes/admin-header.php';
?>

<!-- Rest of your HTML stays exactly the same -->
<div class="page-header">
    <div>
        <h1>Create News Article</h1>
        <p class="subtitle">Add a new news article to your website</p>
    </div>
    <div class="page-actions">
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
                        value="<?php echo escapeHtml($_POST['title'] ?? ''); ?>"
                        placeholder="Enter news title"
                    >
                    <small class="form-text">Maximum 255 characters</small>
                </div>
                
                <!-- Slug -->
                <div class="form-group">
                    <label for="slug">URL Slug</label>
                    <input 
                        type="text" 
                        id="slug" 
                        name="slug" 
                        class="form-control"
                        value="<?php echo escapeHtml($_POST['slug'] ?? ''); ?>"
                        placeholder="auto-generated-from-title"
                    >
                    <small class="form-text">Leave blank to auto-generate from title</small>
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
                        placeholder="Brief summary of the article (optional)"
                    ><?php echo escapeHtml($_POST['excerpt'] ?? ''); ?></textarea>
                    <small class="form-text">Maximum 500 characters. Used in article previews.</small>
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
                    ><?php echo htmlspecialchars($_POST['content'] ?? ''); ?></textarea>
                </div>
            </div>
        </div>
        
        <!-- Sidebar Column -->
        <div class="form-sidebar">
            <!-- Publish Settings -->
            <div class="form-card">
                <h3>Publish Settings</h3>
                
                <div class="form-group">
                    <label for="status" class="required">Status</label>
                    <select id="status" name="status" class="form-control" required>
                        <option value="draft" <?php echo ($_POST['status'] ?? 'draft') === 'draft' ? 'selected' : ''; ?>>Draft</option>
                        <option value="published" <?php echo ($_POST['status'] ?? '') === 'published' ? 'selected' : ''; ?>>Published</option>
                        <option value="archived" <?php echo ($_POST['status'] ?? '') === 'archived' ? 'selected' : ''; ?>>Archived</option>
                    </select>
                    <small class="form-text">Draft articles are not visible to the public</small>
                </div>
                
                <div class="form-group">
                    <label for="published_date">Publish Date</label>
                    <input 
                        type="datetime-local" 
                        id="published_date" 
                        name="published_date" 
                        class="form-control"
                        value="<?php echo $_POST['published_date'] ?? date('Y-m-d\TH:i'); ?>"
                    >
                </div>
                
                <div class="form-group">
                    <label class="checkbox-label">
                        <input 
                            type="checkbox" 
                            name="is_featured" 
                            value="1"
                            <?php echo isset($_POST['is_featured']) ? 'checked' : ''; ?>
                        >
                        <span>⭐ Feature this article</span>
                    </label>
                    <small class="form-text">Featured articles appear on homepage</small>
                </div>
            </div>
            
            <!-- Category -->
            <div class="form-card">
                <h3>Category</h3>
                
                <div class="form-group">
                    <label for="category">Select Category</label>
                    <select id="category" name="category" class="form-control">
                        <option value="general" <?php echo ($_POST['category'] ?? 'general') === 'general' ? 'selected' : ''; ?>>General</option>
                        <option value="academic" <?php echo ($_POST['category'] ?? '') === 'academic' ? 'selected' : ''; ?>>Academic</option>
                        <option value="sports" <?php echo ($_POST['category'] ?? '') === 'sports' ? 'selected' : ''; ?>>Sports</option>
                        <option value="events" <?php echo ($_POST['category'] ?? '') === 'events' ? 'selected' : ''; ?>>Events</option>
                        <option value="achievements" <?php echo ($_POST['category'] ?? '') === 'achievements' ? 'selected' : ''; ?>>Achievements</option>
                    </select>
                </div>
            </div>
            
            <!-- Featured Image -->
            <div class="form-card">
                <h3>Featured Image</h3>
                
                <div class="form-group">
                    <div class="image-upload-area" id="imageUploadArea">
                        <div class="upload-placeholder">
                            <i class="fas fa-cloud-upload-alt"></i>
                            <p>Click to upload or drag and drop</p>
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
            
            <!-- Submit Buttons -->
            <div class="form-card">
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary btn-block">
                        <i class="fas fa-save"></i> Save Article
                    </button>
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
    resize: true
});

// Remove 'required' attribute from textarea (TinyMCE will handle validation)
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('content').removeAttribute('required');
});

// Handle form submission
// Handle form submission
document.querySelector('.news-form').addEventListener('submit', function(e) {
    // Don't prevent default - let it submit naturally
    
    // Save TinyMCE content to textarea
    tinymce.triggerSave();
    
    // Get content from TinyMCE
    const content = tinymce.get('content').getContent();
    
    // Validate content
    if (!content || content === '' || content === '<p></p>' || content === '<p><br></p>' || content === '<p>&nbsp;</p>') {
        e.preventDefault(); // Only prevent if validation fails
        alert('Please add content to your article before publishing.');
        tinymce.get('content').focus();
        return false;
    }
    
    // Validate title
    const title = document.getElementById('title').value.trim();
    if (!title) {
        e.preventDefault(); // Only prevent if validation fails
        alert('Please enter a title');
        document.getElementById('title').focus();
        return false;
    }
    
    // All valid - disable form changed warning
    formChanged = false;
    
    // Show loading state on the button that was clicked
    const submitButton = e.submitter;
    if (submitButton && submitButton.tagName === 'BUTTON') {
        submitButton.disabled = true;
        submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
    }
    
    // Let the form submit naturally (don't call this.submit())
});

// Auto-generate slug from title
document.getElementById('title').addEventListener('input', function() {
    const slugField = document.getElementById('slug');
    if (!slugField.value || slugField.dataset.manual !== 'true') {
        const slug = this.value
            .toLowerCase()
            .replace(/[^a-z0-9\s-]/g, '')
            .replace(/\s+/g, '-')
            .replace(/-+/g, '-')
            .substring(0, 200);
        slugField.value = slug;
    }
});

document.getElementById('slug').addEventListener('input', function() {
    this.dataset.manual = 'true';
});

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
        // Validate file size (5MB)
        if (file.size > 5242880) {
            alert('File size must be less than 5MB');
            this.value = '';
            return;
        }
        
        // Validate file type
        const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        if (!allowedTypes.includes(file.type)) {
            alert('Only JPG, PNG, GIF, and WebP images are allowed');
            this.value = '';
            return;
        }
        
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
    margin-bottom: 0.25rem;
}

.subtitle {
    color: var(--admin-text-muted);
    font-size: 0.95rem;
    margin: 0;
}

.page-actions {
    display: flex;
    gap: 0.75rem;
}

.btn-secondary {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.5rem;
    background: white;
    color: var(--admin-primary);
    border: 2px solid var(--admin-border);
    border-radius: 8px;
    text-decoration: none;
    font-weight: 500;
    transition: all 0.3s;
    cursor: pointer;
}

.btn-secondary:hover {
    background: var(--admin-primary);
    color: white;
    border-color: var(--admin-primary);
    transform: translateY(-2px);
    box-shadow: var(--admin-shadow-lg);
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
    letter-spacing: 0.01em;
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

.form-control::placeholder {
    color: #a0aec0;
}

textarea.form-control {
    resize: vertical;
    min-height: 100px;
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

/* Form Actions */
.form-actions {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.btn-primary {
    padding: 1rem 1.5rem;
    background: var(--admin-primary);
    color: white;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    font-size: 1rem;
    cursor: pointer;
    transition: all 0.3s;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
}

.btn-primary:hover {
    background: var(--admin-primary-dark);
    transform: translateY(-2px);
    box-shadow: 0 8px 16px rgba(0, 63, 135, 0.25);
}

.btn-primary:active {
    transform: translateY(0);
}

.btn-block {
    width: 100%;
}

/* Sidebar Sticky */
.form-sidebar {
    position: sticky;
    top: 2rem;
}

/* TinyMCE Container Enhancement */
.tox-tinymce {
    border: 2px solid var(--admin-border) !important;
    border-radius: 8px !important;
}

.tox-tinymce:focus-within {
    border-color: var(--admin-primary) !important;
    box-shadow: 0 0 0 4px rgba(0, 63, 135, 0.08) !important;
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
    }
    
    .page-actions {
        width: 100%;
    }
    
    .btn-secondary {
        flex: 1;
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
}
</style>

<?php include '../includes/admin-footer.php'; ?>
