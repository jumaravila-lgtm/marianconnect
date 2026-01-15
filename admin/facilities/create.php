<?php
require_once '../includes/auth-check.php';
$db = getDB();
$errors = [];

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
    
    $featuredImage = null;
    if (isset($_FILES['featured_image']) && $_FILES['featured_image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../../assets/uploads/facilities/';
        if (!file_exists($uploadDir)) mkdir($uploadDir, 0777, true);
        
        $fileExtension = strtolower(pathinfo($_FILES['featured_image']['name'], PATHINFO_EXTENSION));
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
        
        if (in_array($fileExtension, $allowedExtensions)) {
            $fileName = uniqid() . '_' . time() . '.' . $fileExtension;
            $filePath = $uploadDir . $fileName;
            
            if (move_uploaded_file($_FILES['featured_image']['tmp_name'], $filePath)) {
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
            $sql = "INSERT INTO facilities (name, slug, description, category, capacity, location, featured_image, is_available, display_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $db->prepare($sql);
            $result = $stmt->execute([$name, $slug, $description, $category, $capacity, $location, $featuredImage, $isAvailable, $displayOrder]);
            
            if ($result) {
                logActivity($_SESSION['admin_id'], 'create', 'facilities', $db->lastInsertId(), "Created facility: {$name}");
                setFlashMessage('success', 'Facility created successfully!');
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
}

$pageTitle = 'Create Facility';
include '../includes/admin-header.php';
?>

<div class="page-header">
    <h1>Create Facility</h1>
    <a href="index.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back</a>
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
                    <input type="text" id="name" name="name" class="form-control" required maxlength="100" value="<?php echo escapeHtml($_POST['name'] ?? ''); ?>">
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="location">Location</label>
                        <input type="text" id="location" name="location" class="form-control" maxlength="100" value="<?php echo escapeHtml($_POST['location'] ?? ''); ?>" placeholder="e.g., 2nd Floor, Building A">
                    </div>
                    
                    <div class="form-group">
                        <label for="capacity">Capacity</label>
                        <input type="text" id="capacity" name="capacity" class="form-control" maxlength="50" value="<?php echo escapeHtml($_POST['capacity'] ?? ''); ?>" placeholder="e.g., 50 students">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="description" class="required">Description</label>
                    <textarea id="description" name="description" class="form-control" rows="5" required><?php echo escapeHtml($_POST['description'] ?? ''); ?></textarea>
                </div>
            </div>
        </div>
        
        <div class="form-sidebar">
            <div class="form-card">
                <h3>Featured Image</h3>
                
                <div class="form-group">
                    <label for="featured_image">Upload Image</label>
                    <input type="file" id="featured_image" name="featured_image" class="form-control" accept="image/*">
                    <small class="form-text">JPG, PNG, or GIF (Max 5MB)</small>
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
                        <option value="classroom" <?php echo ($_POST['category'] ?? '') === 'classroom' ? 'selected' : ''; ?>>Classroom</option>
                        <option value="laboratory" <?php echo ($_POST['category'] ?? '') === 'laboratory' ? 'selected' : ''; ?>>Laboratory</option>
                        <option value="library" <?php echo ($_POST['category'] ?? '') === 'library' ? 'selected' : ''; ?>>Library</option>
                        <option value="sports" <?php echo ($_POST['category'] ?? '') === 'sports' ? 'selected' : ''; ?>>Sports</option>
                        <option value="chapel" <?php echo ($_POST['category'] ?? '') === 'chapel' ? 'selected' : ''; ?>>Chapel</option>
                        <option value="office" <?php echo ($_POST['category'] ?? '') === 'office' ? 'selected' : ''; ?>>Office</option>
                        <option value="other" <?php echo ($_POST['category'] ?? '') === 'other' ? 'selected' : ''; ?>>Other</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="display_order">Display Order</label>
                    <input type="number" id="display_order" name="display_order" class="form-control" value="<?php echo escapeHtml($_POST['display_order'] ?? '0'); ?>">
                    <small class="form-text">Lower numbers appear first</small>
                </div>
                
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="is_available" value="1" checked>
                        <span>Available</span>
                    </label>
                </div>
            </div>
            
            <div class="form-card">
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary btn-block">
                        <i class="fas fa-check"></i> Create Facility
                    </button>
                </div>
            </div>
        </div>
    </div>
</form>

<style>
.page-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:2rem}
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:1rem}
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
.alert{padding:1rem;border-radius:8px;margin-bottom:1.5rem}
.alert-danger{background:#ffebee;border:1px solid #ef5350;color:#c62828}
.alert ul{margin:0;padding-left:1.5rem}
.image-preview{margin-top:1rem;border-radius:8px;overflow:hidden}
.image-preview img{width:100%;height:auto;display:block}
.btn{padding:0.6rem 1.25rem;border:none;border-radius:8px;font-weight:500;cursor:pointer;display:inline-flex;align-items:center;gap:0.5rem;text-decoration:none;transition:all 0.2s}
.btn-primary{background:var(--admin-primary);color:white}
.btn-primary:hover{background:#004a99;transform:translateY(-2px)}
.btn-secondary{background:#6c757d;color:white}
.btn-secondary:hover{background:#5a6268}
@media(max-width:1024px){.form-grid{grid-template-columns:1fr}.form-row{grid-template-columns:1fr}}
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
