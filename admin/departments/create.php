<?php
require_once '../includes/auth-check.php';

// Restrict to Super Admin only
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'super_admin') {
    setFlashMessage('error', 'Access denied. Only Super Admins can manage Departments.');
    redirect('../index.php');
}

$db = getDB();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid security token';
    }
    
    $name = sanitize($_POST['name'] ?? '');
    $headName = sanitize($_POST['head_name'] ?? '');
    $description = sanitize($_POST['description'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    $displayOrder = (int)($_POST['display_order'] ?? 0);
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    
    // Validation
    if (empty($name)) {
        $errors[] = "Department name is required";
    }
    if (empty($headName)) {
        $errors[] = "Department head is required";
    }
    
    // Handle image upload
    $imagePath = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploadResult = secureFileUpload(
            $_FILES['image'],
            '../../assets/uploads/departments',
            ALLOWED_IMAGE_TYPES
        );
        
        if ($uploadResult['success']) {
            $imagePath = '/assets/uploads/departments/' . $uploadResult['filename'];
        } else {
            $errors = array_merge($errors, $uploadResult['errors']);
        }
    }
    
    if (empty($errors)) {
        try {
            $sql = "INSERT INTO departments (name, image, head_name, description, email, phone, display_order, is_active) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $db->prepare($sql);
            $result = $stmt->execute([$name, $imagePath, $headName, $description, $email, $phone, $displayOrder, $isActive]);
            
            if ($result) {
                $deptId = $db->lastInsertId();
                logActivity($_SESSION['admin_id'], 'create', 'departments', $deptId, "Created department: {$name}");
                setFlashMessage('success', 'Department created successfully!');
                redirect('index.php');
            }
        } catch (PDOException $e) {
            // If database insert fails, delete uploaded image
            if ($imagePath) {
                $fullPath = '../../' . ltrim($imagePath, '/');
                if (file_exists($fullPath)) {
                    unlink($fullPath);
                }
            }
            $errors[] = "Database error: " . $e->getMessage();
        }
    }
}

$pageTitle = 'Create Department';
include '../includes/admin-header.php';
?>

<div class="page-header">
    <div>
        <h1>Create Department</h1>
        <p class="subtitle">Add a new department to your organization</p>
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

<form method="POST" action="" enctype="multipart/form-data" class="department-form">
    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
    
    <div class="form-grid">
        <div class="form-main">
            <div class="form-card">
                <h3>Department Information</h3>
                
                <div class="form-group">
                    <label for="name" class="required">Department Name</label>
                    <input type="text" id="name" name="name" class="form-control" required
                           value="<?php echo escapeHtml($_POST['name'] ?? ''); ?>"
                           placeholder="e.g., Academic Affairs">
                </div>
                
                <div class="form-group">
                    <label for="image">Department Image</label>
                    <div class="image-upload-wrapper">
                        <div class="image-upload-area" id="imageUploadArea">
                            <div class="upload-placeholder">
                                <i class="fas fa-cloud-upload-alt"></i>
                                <p>Click to upload or drag and drop</p>
                                <small>JPG, PNG, GIF or WebP (Max 5MB)</small>
                            </div>
                            <input type="file" id="image" name="image" accept="image/*" class="file-input">
                            <div class="image-preview" id="imagePreview" style="display: none;">
                                <img src="" alt="Preview" id="previewImage">
                                <button type="button" class="remove-image" id="removeImage">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="head_name" class="required">Department Head</label>
                    <input type="text" id="head_name" name="head_name" class="form-control" required
                           value="<?php echo escapeHtml($_POST['head_name'] ?? ''); ?>"
                           placeholder="e.g., Dr. Juan dela Cruz">
                </div>
                
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" class="form-control" rows="4"
                              placeholder="Brief description of the department"><?php echo escapeHtml($_POST['description'] ?? ''); ?></textarea>
                </div>
            </div>
        </div>
        
        <div class="form-sidebar">
            <div class="form-card">
                <h3>Contact Information</h3>
                
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" class="form-control"
                           value="<?php echo escapeHtml($_POST['email'] ?? ''); ?>"
                           placeholder="department@smcc.edu.ph">
                </div>
                
                <div class="form-group">
                    <label for="phone">Phone</label>
                    <input type="text" id="phone" name="phone" class="form-control"
                           value="<?php echo escapeHtml($_POST['phone'] ?? ''); ?>"
                           placeholder="+63 XXX XXX XXXX">
                </div>
            </div>
            
            <div class="form-card">
                <h3>Display Settings</h3>
                
                <div class="form-group">
                    <label for="display_order">Display Order</label>
                    <input type="number" id="display_order" name="display_order" class="form-control"
                           value="<?php echo escapeHtml($_POST['display_order'] ?? '0'); ?>"
                           min="0">
                    <small class="form-text">Lower numbers appear first</small>
                </div>
                
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="is_active" value="1" 
                               <?php echo isset($_POST['is_active']) || !$_POST ? 'checked' : ''; ?>>
                        <span>Active</span>
                    </label>
                    <small class="form-text">Only active departments are shown on the website</small>
                </div>
            </div>
            
            <div class="form-card">
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary btn-block">
                        <i class="fas fa-save"></i> Create Department
                    </button>
                </div>
            </div>
        </div>
    </div>
</form>

<script>
// Image upload
const fileInput = document.getElementById('image');
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
});
</script>

<style>
.page-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:2rem}
.subtitle{color:var(--admin-text-muted);margin-top:0.5rem}
.btn-secondary{display:inline-flex;align-items:center;gap:0.5rem;padding:0.75rem 1.5rem;background:#6c757d;color:white;border:none;border-radius:8px;text-decoration:none;font-weight:500;transition:all 0.3s}
.btn-secondary:hover{background:#5a6268;transform:translateY(-2px)}
.form-grid{display:grid;grid-template-columns:1fr 350px;gap:2rem}
.form-card{background:white;border-radius:12px;padding:1.5rem;margin-bottom:1.5rem;box-shadow:var(--admin-shadow)}
.form-card h3{font-size:1.1rem;font-weight:600;margin-bottom:1.5rem;padding-bottom:0.75rem;border-bottom:2px solid var(--admin-border)}
.form-group{margin-bottom:1.5rem}
.form-group:last-child{margin-bottom:0}
.form-group label{display:block;margin-bottom:0.5rem;font-weight:500;color:var(--admin-text)}
.form-group label.required::after{content:' *';color:var(--admin-danger)}
.form-control{width:100%;padding:0.75rem 1rem;border:2px solid var(--admin-border);border-radius:8px;font-size:0.95rem;transition:all 0.3s}
.form-control:focus{outline:none;border-color:var(--admin-primary);box-shadow:0 0 0 3px rgba(0,63,135,0.1)}
.image-upload-wrapper{margin-top:0.5rem}
.image-upload-area{position:relative;border:2px dashed var(--admin-border);border-radius:8px;padding:2rem;text-align:center;cursor:pointer;transition:all 0.3s}
.image-upload-area:hover{border-color:var(--admin-primary);background:var(--admin-hover)}
.upload-placeholder{display:flex;flex-direction:column;align-items:center;gap:0.5rem}
.upload-placeholder i{font-size:3rem;color:var(--admin-text-muted)}
.file-input{display:none}
.image-preview{position:relative}
.image-preview img{width:100%;border-radius:8px}
.remove-image{position:absolute;top:10px;right:10px;width:32px;height:32px;background:var(--admin-danger);color:white;border:none;border-radius:50%;cursor:pointer;display:flex;align-items:center;justify-content:center}
.form-text{display:block;margin-top:0.5rem;font-size:0.85rem;color:var(--admin-text-muted)}
.form-text a{color:var(--admin-primary)}
.checkbox-label{display:flex;align-items:center;gap:0.5rem;cursor:pointer;font-weight:400}
.checkbox-label input[type="checkbox"]{width:18px;height:18px;cursor:pointer}
.form-actions{display:flex;flex-direction:column;gap:0.75rem}
.btn-block{width:100%;justify-content:center}
.btn{padding:0.75rem 1.5rem;border:none;border-radius:8px;font-weight:500;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;gap:0.5rem;text-decoration:none;transition:all 0.3s}
.btn-primary{background:var(--admin-primary);color:white}
.btn-primary:hover{background:var(--admin-primary-dark);transform:translateY(-2px)}
.alert{padding:1rem 1.5rem;border-radius:8px;margin-bottom:1.5rem}
.alert-danger{background:#ffebee;border:1px solid #ef5350;color:#c62828}
.alert ul{margin:0.5rem 0 0 1.5rem}
@media(max-width:1024px){.form-grid{grid-template-columns:1fr}}
</style>

<?php include '../includes/admin-footer.php'; ?>
