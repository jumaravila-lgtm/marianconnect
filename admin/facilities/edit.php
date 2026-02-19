<?php
require_once '../includes/auth-check.php';
$db = getDB();
$errors = [];

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    setFlashMessage('error', 'Invalid facility ID');
    redirect('index.php');
}

try {
    $stmt = $db->prepare("SELECT * FROM facilities WHERE facility_id = ?");
    $stmt->execute([$id]);
    $facility = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$facility) {
        setFlashMessage('error', 'Facility not found');
        redirect('index.php');
    }
} catch (PDOException $e) {
    setFlashMessage('error', 'Database error');
    redirect('index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name'] ?? '');
    $description = sanitize($_POST['description'] ?? '');
    $category = sanitize($_POST['category'] ?? '');
    $capacity = sanitize($_POST['capacity'] ?? '');
    $location = sanitize($_POST['location'] ?? '');
    $displayOrder = (int)($_POST['display_order'] ?? 0);
    $isAvailable = isset($_POST['is_available']) ? 1 : 0;
    
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));
    
    if (empty($name)) $errors[] = "Facility name is required";
    if (empty($description)) $errors[] = "Description is required";
    if (empty($category)) $errors[] = "Category is required";
    
    $featuredImage = $facility['featured_image'];
    if (isset($_FILES['featured_image']) && $_FILES['featured_image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../../assets/uploads/facilities/';
        if (!file_exists($uploadDir)) mkdir($uploadDir, 0777, true);
        
        $fileExtension = strtolower(pathinfo($_FILES['featured_image']['name'], PATHINFO_EXTENSION));
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
        
        if (in_array($fileExtension, $allowedExtensions)) {
            $fileName = uniqid() . '_' . time() . '.' . $fileExtension;
            $filePath = $uploadDir . $fileName;
            
            if (move_uploaded_file($_FILES['featured_image']['tmp_name'], $filePath)) {
                if ($facility['featured_image'] && file_exists('../../' . $facility['featured_image'])) {
                    unlink('../../' . $facility['featured_image']);
                }
                $featuredImage = '/assets/uploads/facilities/' . $fileName;
            } else {
                $errors[] = "Failed to upload image";
            }
        } else {
            $errors[] = "Invalid image format";
        }
    }
    
    if (empty($errors)) {
        try {
            $sql = "UPDATE facilities SET name = ?, slug = ?, description = ?, category = ?, capacity = ?, location = ?, featured_image = ?, is_available = ?, display_order = ? WHERE facility_id = ?";
            $stmt = $db->prepare($sql);
            $result = $stmt->execute([$name, $slug, $description, $category, $capacity, $location, $featuredImage, $isAvailable, $displayOrder, $id]);
            
            if ($result) {
                logActivity($_SESSION['admin_id'], 'update', 'facilities', $id, "Updated facility: {$name}");
                setFlashMessage('success', 'Facility updated successfully!');
                redirect('index.php');
            }
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $errors[] = "Facility name or slug already exists";
            } else {
                $errors[] = "Error: " . $e->getMessage();
            }
        }
    }
    
    $facility = array_merge($facility, $_POST);
}

$pageTitle = 'Edit Facility';
include '../includes/admin-header.php';
?>

<div class="page-header">
    <h1>Edit Facility</h1>
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
                <h3>Facility Information</h3>
                
                <div class="form-group">
                    <label for="name" class="required">Facility Name</label>
                    <input type="text" id="name" name="name" class="form-control" required maxlength="100" value="<?php echo escapeHtml($facility['name']); ?>">
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="location">Location</label>
                        <input type="text" id="location" name="location" class="form-control" maxlength="100" value="<?php echo escapeHtml($facility['location'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="capacity">Capacity</label>
                        <input type="text" id="capacity" name="capacity" class="form-control" maxlength="50" value="<?php echo escapeHtml($facility['capacity'] ?? ''); ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="description" class="required">Description</label>
                    <textarea id="description" name="description" class="form-control" rows="5" required><?php echo escapeHtml($facility['description']); ?></textarea>
                </div>
            </div>
        </div>
        
        <div class="form-sidebar">
            <div class="form-card">
                <h3>Featured Image</h3>
                
                <?php if ($facility['featured_image']): ?>
                    <div class="current-image">
                        <img src="<?php echo escapeHtml($facility['featured_image']); ?>" alt="Image">
                        <p class="text-muted">Current image</p>
                    </div>
                <?php endif; ?>
                
                <div class="form-group">
                    <label for="featured_image">Upload New Image</label>
                    <input type="file" id="featured_image" name="featured_image" class="form-control" accept="image/*">
                    <small class="form-text">Leave empty to keep current</small>
                </div>
                
                <div id="image-preview" class="image-preview" style="display:none">
                    <img id="preview-img" src="" alt="Preview">
                </div>
            </div>
            
            <div class="form-card">
                <h3>Settings</h3>
                
                <div class="form-group">
                    <label for="category" class="required">Category</label>
                    <select id="category" name="category" class="form-control" required>
                        <option value="">Select Category</option>
                        <option value="classroom" <?php echo $facility['category'] === 'classroom' ? 'selected' : ''; ?>>Classroom</option>
                        <option value="laboratory" <?php echo $facility['category'] === 'laboratory' ? 'selected' : ''; ?>>Laboratory</option>
                        <option value="library" <?php echo $facility['category'] === 'library' ? 'selected' : ''; ?>>Library</option>
                        <option value="sports" <?php echo $facility['category'] === 'sports' ? 'selected' : ''; ?>>Sports</option>
                        <option value="chapel" <?php echo $facility['category'] === 'chapel' ? 'selected' : ''; ?>>Chapel</option>
                        <option value="office" <?php echo $facility['category'] === 'office' ? 'selected' : ''; ?>>Office</option>
                        <option value="other" <?php echo $facility['category'] === 'other' ? 'selected' : ''; ?>>Other</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="display_order">Display Order</label>
                    <input type="number" id="display_order" name="display_order" class="form-control" value="<?php echo $facility['display_order']; ?>">
                </div>
                
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="is_available" value="1" <?php echo $facility['is_available'] ? 'checked' : ''; ?>>
                        <span>Available</span>
                    </label>
                </div>
            </div>
            
            <div class="form-card">
                <h3>Meta Info</h3>
                <div class="meta-info">
                    <div class="meta-item">
                        <span class="meta-label">Created:</span>
                        <span class="meta-value"><?php echo date('M d, Y', strtotime($facility['created_at'])); ?></span>
                    </div>
                    <div class="meta-item">
                        <span class="meta-label">Updated:</span>
                        <span class="meta-value"><?php echo date('M d, Y', strtotime($facility['updated_at'])); ?></span>
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
    box-shadow: 0 4px 8px rgba(220, 53, 69, 0.25);
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
    gap: 1.25rem;
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

select.form-control {
    cursor: pointer;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23333' d='M6 9L1 4h10z'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 1rem center;
    background-size: 12px;
    appearance: none;
    padding-right: 2.5rem;
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
    text-align: center;
}

.current-image img {
    width: 100%;
    display: block;
    border-radius: 8px;
}

.current-image .text-muted {
    padding: 0.5rem;
    font-size: 0.85rem;
    color: var(--admin-text-muted);
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
    
    .form-row {
        grid-template-columns: 1fr;
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
document.getElementById('featured_image').addEventListener('change', function(e) {
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
