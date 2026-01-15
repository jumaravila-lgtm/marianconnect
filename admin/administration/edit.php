<?php
/**
 * MARIANCONNECT - Edit Administration Member
 */

// Authentication check
require_once '../includes/auth-check.php';

$db = getDB();
$errors = [];
$memberId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Fetch existing member
$stmt = $db->prepare("SELECT * FROM administration WHERE admin_member_id = ?");
$stmt->execute([$memberId]);
$member = $stmt->fetch();

if (!$member) {
    setFlashMessage('error', 'Administration member not found.');
    redirect('index.php');
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid security token. Please refresh and try again.';
    }
    
    // Get form data
    $name = sanitize($_POST['name'] ?? '');
    $position = sanitize($_POST['position'] ?? '');
    $description = sanitize($_POST['description'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    $officeLocation = sanitize($_POST['office_location'] ?? '');
    $displayOrder = (int)($_POST['display_order'] ?? 0);
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    
    // Validation
    if (empty($name)) {
        $errors[] = "Name is required";
    }
    
    if (empty($position)) {
        $errors[] = "Position is required";
    }
    
    if (empty($description)) {
        $errors[] = "Description is required";
    }
    
    if (!empty($email) && !validateEmail($email)) {
        $errors[] = "Invalid email address";
    }
    
    // Handle image upload
    $featuredImage = $member['featured_image']; // Keep existing image by default
    
    if (isset($_FILES['featured_image']) && $_FILES['featured_image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../../assets/uploads/administration';
        
        // Create directory if it doesn't exist
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        $uploadResult = secureFileUpload(
            $_FILES['featured_image'],
            $uploadDir,
            ALLOWED_IMAGE_TYPES
        );
        
        if ($uploadResult['success']) {
            // Delete old image if exists
            if (!empty($member['featured_image'])) {
                deleteUploadedFile($member['featured_image']);
            }
            
            $featuredImage = '/assets/uploads/administration/' . $uploadResult['filename'];
        } else {
            $errors = array_merge($errors, $uploadResult['errors']);
        }
    }
    
    // Handle image removal
    if (isset($_POST['remove_image']) && $_POST['remove_image'] == '1') {
        if (!empty($member['featured_image'])) {
            deleteUploadedFile($member['featured_image']);
        }
        $featuredImage = null;
    }
    
    // If no errors, update database
    if (empty($errors)) {
        try {
            $sql = "
                UPDATE administration SET
                    name = ?,
                    position = ?,
                    description = ?,
                    email = ?,
                    phone = ?,
                    office_location = ?,
                    featured_image = ?,
                    display_order = ?,
                    is_active = ?,
                    updated_at = NOW()
                WHERE admin_member_id = ?
            ";
            
            $stmt = $db->prepare($sql);
            $result = $stmt->execute([
                $name,
                $position,
                $description,
                $email,
                $phone,
                $officeLocation,
                $featuredImage,
                $displayOrder,
                $isActive,
                $memberId
            ]);
            
            if ($result) {
                // Log activity
                logActivity($_SESSION['admin_id'], 'update', 'administration', $memberId, "Updated administration member: {$name}");
                
                // Set success message
                setFlashMessage('success', 'Administration member updated successfully!');
                
                // Refresh data
                $stmt = $db->prepare("SELECT * FROM administration WHERE admin_member_id = ?");
                $stmt->execute([$memberId]);
                $member = $stmt->fetch();
            }
        } catch (PDOException $e) {
            $errors[] = "Database error: " . $e->getMessage();
        }
    }
}

$pageTitle = 'Edit Administration Member';
include '../includes/admin-header.php';
?>

<div class="page-header">
    <div>
        <h1>Edit Administration Member</h1>
        <p class="subtitle">Last updated: <?php echo formatDate($member['updated_at'], 'F j, Y g:i A'); ?></p>
    </div>
    <div class="page-actions">
        <a href="../../pages/administration.php" class="btn btn-info" target="_blank">
            <i class="fas fa-eye"></i> View Page
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

<form method="POST" action="" enctype="multipart/form-data" class="admin-form">
    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
    <input type="hidden" name="remove_image" id="removeImageFlag" value="0">
    
    <div class="form-grid">
        <!-- Main Content Column -->
        <div class="form-main">
            <div class="form-card">
                <h3>Member Information</h3>
                
                <!-- Name -->
                <div class="form-group">
                    <label for="name" class="required">Full Name</label>
                    <input 
                        type="text" 
                        id="name" 
                        name="name" 
                        class="form-control" 
                        required
                        maxlength="100"
                        value="<?php echo escapeHtml($member['name']); ?>"
                    >
                </div>
                
                <!-- Position -->
                <div class="form-group">
                    <label for="position" class="required">Position / Title</label>
                    <input 
                        type="text" 
                        id="position" 
                        name="position" 
                        class="form-control" 
                        required
                        maxlength="100"
                        value="<?php echo escapeHtml($member['position']); ?>"
                    >
                </div>
                
                <!-- Description -->
                <div class="form-group">
                    <label for="description" class="required">Description / Responsibilities</label>
                    <textarea 
                        id="description" 
                        name="description" 
                        class="form-control" 
                        rows="5"
                        required
                    ><?php echo escapeHtml($member['description']); ?></textarea>
                </div>
            </div>
            
            <div class="form-card">
                <h3>Contact Information</h3>
                
                <!-- Email -->
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        class="form-control"
                        maxlength="100"
                        value="<?php echo escapeHtml($member['email'] ?? ''); ?>"
                    >
                </div>
                
                <!-- Phone -->
                <div class="form-group">
                    <label for="phone">Phone Number</label>
                    <input 
                        type="text" 
                        id="phone" 
                        name="phone" 
                        class="form-control"
                        maxlength="20"
                        value="<?php echo escapeHtml($member['phone'] ?? ''); ?>"
                    >
                </div>
                
                <!-- Office Location -->
                <div class="form-group">
                    <label for="office_location">Office Location</label>
                    <input 
                        type="text" 
                        id="office_location" 
                        name="office_location" 
                        class="form-control"
                        maxlength="100"
                        value="<?php echo escapeHtml($member['office_location'] ?? ''); ?>"
                    >
                </div>
            </div>
        </div>
        
        <!-- Sidebar Column -->
        <div class="form-sidebar">
            <!-- Display Settings -->
            <div class="form-card">
                <h3>Display Settings</h3>
                
                <div class="form-group">
                    <label for="display_order">Display Order</label>
                    <input 
                        type="number" 
                        id="display_order" 
                        name="display_order" 
                        class="form-control"
                        value="<?php echo $member['display_order']; ?>"
                        min="0"
                    >
                    <small class="form-text">Lower numbers appear first</small>
                </div>
                
                <div class="form-group">
                    <label class="checkbox-label">
                        <input 
                            type="checkbox" 
                            name="is_active" 
                            value="1"
                            <?php echo $member['is_active'] ? 'checked' : ''; ?>
                        >
                        <span>✓ Active / Visible</span>
                    </label>
                    <small class="form-text">Only active members appear on the website</small>
                </div>
            </div>
            
            <!-- Profile Photo -->
            <div class="form-card">
                <h3>Profile Photo</h3>
                
                <div class="form-group">
                    <?php if (!empty($member['featured_image'])): ?>
                        <div class="current-image">
                            <img src="<?php echo escapeHtml(getImageUrl($member['featured_image'])); ?>" 
                                alt="Current photo"
                                onerror="this.style.display='none'">
                            <button type="button" class="btn-remove-current" onclick="removeCurrentImage()">
                                <i class="fas fa-times"></i> Remove Photo
                            </button>
                        </div>
                    <?php endif; ?>
                    
                    <div class="image-upload-area" id="imageUploadArea">
                        <div class="upload-placeholder">
                            <i class="fas fa-user-circle"></i>
                            <p><?php echo !empty($member['featured_image']) ? 'Change Photo' : 'Upload Photo'; ?></p>
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
                        <i class="fas fa-save"></i> Update Member
                    </button>
                    <a href="delete.php?id=<?php echo $member['admin_member_id']; ?>" class="btn btn-danger btn-block">
                        <i class="fas fa-trash"></i> Delete Member
                    </a>
                </div>
            </div>
        </div>
    </div>
</form>

<script>
// Remove current image
function removeCurrentImage() {
    if (confirm('Are you sure you want to remove the current photo?')) {
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
document.querySelector('.admin-form').addEventListener('input', () => formChanged = true);
window.addEventListener('beforeunload', function(e) {
    if (formChanged) {
        e.preventDefault();
        e.returnValue = '';
    }
});
document.querySelector('.admin-form').addEventListener('submit', () => formChanged = false);
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

.btn-danger {
    background: var(--admin-danger);
}

.btn-danger:hover {
    background: #c82333;
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

.alert {
    padding: 1rem;
    border-radius: 8px;
    margin-bottom: 1.5rem;
}

.alert-danger {
    background: #ffebee;
    border: 1px solid #ef5350;
    color: #c62828;
}

.alert ul {
    margin: 0;
    padding-left: 1.5rem;
}

@media (max-width: 1024px) {
    .form-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<?php include '../includes/admin-footer.php'; ?>
