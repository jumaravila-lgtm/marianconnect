<?php
require_once '../includes/auth-check.php';
$db = getDB();
$errors = [];

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    setFlashMessage('error', 'Invalid slider ID');
    redirect('index.php');
}

try {
    $stmt = $db->prepare("SELECT * FROM homepage_sliders WHERE slider_id = ?");
    $stmt->execute([$id]);
    $slider = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$slider) {
        setFlashMessage('error', 'Slider not found');
        redirect('index.php');
    }
} catch (PDOException $e) {
    setFlashMessage('error', 'Database error');
    redirect('index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = sanitize($_POST['title'] ?? '');
    $subtitle = sanitize($_POST['subtitle'] ?? '');
    $buttonText = sanitize($_POST['button_text'] ?? '');
    $buttonLink = sanitize($_POST['button_link'] ?? '');
    $displayOrder = (int)($_POST['display_order'] ?? 0);
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    
    if (empty($title)) $errors[] = "Title is required";
    
    $imagePath = $slider['image_path'];
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../../assets/uploads/sliders/';
        if (!file_exists($uploadDir)) mkdir($uploadDir, 0777, true);
        
        $fileExtension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
        
        if (in_array($fileExtension, $allowedExtensions)) {
            $fileName = uniqid() . '_' . time() . '.' . $fileExtension;
            $filePath = $uploadDir . $fileName;
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $filePath)) {
                if ($slider['image_path'] && file_exists('../../' . $slider['image_path'])) {
                    unlink('../../' . $slider['image_path']);
                }
                $imagePath = '/assets/uploads/sliders/' . $fileName;
            } else {
                $errors[] = "Failed to upload image";
            }
        } else {
            $errors[] = "Invalid image format";
        }
    }
    
    if (empty($errors)) {
        try {
            $sql = "UPDATE homepage_sliders SET title = ?, subtitle = ?, image_path = ?, button_text = ?, button_link = ?, display_order = ?, is_active = ? WHERE slider_id = ?";
            $stmt = $db->prepare($sql);
            $result = $stmt->execute([$title, $subtitle, $imagePath, $buttonText, $buttonLink, $displayOrder, $isActive, $id]);
            
            if ($result) {
                logActivity($_SESSION['admin_id'], 'update', 'homepage_sliders', $id, "Updated slider: {$title}");
                setFlashMessage('success', 'Slider updated successfully!');
                redirect('index.php');
            }
        } catch (PDOException $e) {
            $errors[] = "Error: " . $e->getMessage();
        }
    }
    
    $slider = array_merge($slider, $_POST);
}

$pageTitle = 'Edit Slider';
include '../includes/admin-header.php';
?>

<div class="page-header">
    <h1>Edit Slider</h1>
    <div class="header-actions">
        <a href="delete.php?id=<?php echo $id; ?>" class="btn btn-danger">
            <i class="fas fa-trash"></i> Delete
        </a>
        <a href="index.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back</a>
    </div>
</div>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger">
    <ul><?php foreach ($errors as $error): ?><li><?php echo escapeHtml($error); ?></li><?php endforeach; ?></ul>
</div>
<?php endif; ?>

<form method="POST" action="" enctype="multipart/form-data">
    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
    
    <div class="form-grid">
        <div class="form-main">
            <div class="form-card">
                <h3>Slider Content</h3>
                
                <div class="form-group">
                    <label for="title" class="required">Title</label>
                    <input type="text" id="title" name="title" class="form-control" required maxlength="255" value="<?php echo escapeHtml($slider['title']); ?>">
                </div>
                
                <div class="form-group">
                    <label for="subtitle">Subtitle</label>
                    <textarea id="subtitle" name="subtitle" class="form-control" rows="3"><?php echo escapeHtml($slider['subtitle'] ?? ''); ?></textarea>
                    <small class="form-text">Optional descriptive text below the title</small>
                </div>
            </div>
            
            <div class="form-card">
                <h3>Call-to-Action Button (Optional)</h3>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="button_text">Button Text</label>
                        <input type="text" id="button_text" name="button_text" class="form-control" maxlength="50" value="<?php echo escapeHtml($slider['button_text'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="button_link">Button Link</label>
                        <input type="text" id="button_link" name="button_link" class="form-control" maxlength="255" value="<?php echo escapeHtml($slider['button_link'] ?? ''); ?>">
                    </div>
                </div>
                
                <small class="form-text">Leave both empty to hide the button</small>
            </div>
        </div>
        
        <div class="form-sidebar">
            <div class="form-card">
                <h3>Slider Image</h3>
                
                <?php if ($slider['image_path']): ?>
                    <div class="current-image">
                        <img src="<?php echo escapeHtml($slider['image_path']); ?>" alt="Current">
                        <p class="text-muted">Current image</p>
                    </div>
                <?php endif; ?>
                
                <div class="form-group">
                    <label for="image">Upload New Image</label>
                    <input type="file" id="image" name="image" class="form-control" accept="image/*">
                    <small class="form-text">Leave empty to keep current image</small>
                </div>
                
                <div id="image-preview" class="image-preview" style="display:none">
                    <img id="preview-img" src="" alt="Preview">
                </div>
            </div>
            
            <div class="form-card">
                <h3>Settings</h3>
                
                <div class="form-group">
                    <label for="display_order">Display Order</label>
                    <input type="number" id="display_order" name="display_order" class="form-control" value="<?php echo $slider['display_order']; ?>">
                    <small class="form-text">Lower numbers appear first</small>
                </div>
                
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="is_active" value="1" <?php echo $slider['is_active'] ? 'checked' : ''; ?>>
                        <span>Active</span>
                    </label>
                </div>
            </div>
            
            <div class="form-card">
                <h3>Meta Info</h3>
                <div class="meta-info">
                    <div class="meta-item">
                        <span class="meta-label">Created:</span>
                        <span class="meta-value"><?php echo date('M d, Y', strtotime($slider['created_at'])); ?></span>
                    </div>
                    <div class="meta-item">
                        <span class="meta-label">Updated:</span>
                        <span class="meta-value"><?php echo date('M d, Y', strtotime($slider['updated_at'])); ?></span>
                    </div>
                </div>
            </div>
            
            <div class="form-card">
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary btn-block">
                        <i class="fas fa-save"></i> Update
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
    margin-bottom: 0.25rem;
}

.subtitle {
    color: var(--admin-text-muted);
    font-size: 0.9rem;
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

.btn-danger {
    background: white;
    color: var(--admin-danger);
    border: 2px solid var(--admin-danger);
}

.btn-danger:hover {
    background: var(--admin-danger);
    color: white;
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

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
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

.current-image .text-muted {
    text-align: center;
    margin-top: 0.75rem;
    padding: 0.5rem;
    font-size: 0.85rem;
    color: var(--admin-text-muted);
    font-weight: 500;
    background: #f8f9fa;
}

/* Image Preview */
.image-preview {
    margin-top: 1rem;
    border-radius: 8px;
    overflow: hidden;
    border: 2px solid var(--admin-border);
}

.image-preview img {
    width: 100%;
    height: auto;
    display: block;
}

/* Meta Info */
.meta-info {
    font-size: 0.9rem;
}

.meta-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem;
    border-bottom: 1px solid var(--admin-border);
    transition: background 0.3s;
}

.meta-item:hover {
    background: var(--admin-hover);
}

.meta-item:last-child {
    border-bottom: none;
}

.meta-label {
    color: var(--admin-text-muted);
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.meta-label i {
    color: var(--admin-primary);
}

.meta-value {
    color: var(--admin-text);
    font-weight: 600;
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

/* Alert */
.alert {
    padding: 1.25rem 1.5rem;
    border-radius: 12px;
    margin-bottom: 1.5rem;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.alert-danger {
    background: linear-gradient(135deg, #fff5f5 0%, #ffe8e8 100%);
    border: 2px solid #ef5350;
    color: #c62828;
}

.alert strong {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 700;
}

.alert ul {
    margin: 0.5rem 0 0 0;
    padding-left: 1.5rem;
}

.alert ul li {
    margin-bottom: 0.25rem;
}

/* Responsive */
@media (max-width: 1024px) {
    .form-grid {
        grid-template-columns: 1fr;
    }
    
    .form-sidebar {
        position: static;
    }
    
    .form-row {
        grid-template-columns: 1fr;
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

<script>
document.getElementById('image').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('preview-img').src = e.target.result;
            document.getElementById('image-preview').style.display = 'block';
        }
        reader.readAsDataURL(file);
    }
});
</script>

<?php include '../includes/admin-footer.php'; ?>
