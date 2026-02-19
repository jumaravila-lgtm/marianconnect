<?php
/**
 * MARIANCONNECT - Create Administration Member
 */

// Authentication check
require_once '../includes/auth-check.php';

$db = getDB();
$errors = [];

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
    $featuredImage = null;
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
            $featuredImage = '/assets/uploads/administration/' . $uploadResult['filename'];
        } else {
            $errors = array_merge($errors, $uploadResult['errors']);
        }
    }
    
    // If no errors, insert into database
    if (empty($errors)) {
        try {
            $sql = "
                INSERT INTO administration 
                (name, position, description, email, phone, office_location, featured_image, display_order, is_active)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
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
                $isActive
            ]);
            
            if ($result) {
                $memberId = $db->lastInsertId();
                
                // Log activity
                logActivity($_SESSION['admin_id'], 'create', 'administration', $memberId, "Created administration member: {$name}");
                
                // Set success message
                setFlashMessage('success', 'Administration member added successfully!');
                
                // Redirect to edit page
                redirect('edit.php?id=' . $memberId);
            }
        } catch (PDOException $e) {
            $errors[] = "Database error: " . $e->getMessage();
        }
    }
}

$pageTitle = 'Add Administration Member';
include '../includes/admin-header.php';
?>

<div class="page-header">
    <div>
        <h1>Add Administration Member</h1>
        <p class="subtitle">Add a new member to your administration team</p>
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

<form method="POST" action="" enctype="multipart/form-data" class="admin-form">
    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
    
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
                        value="<?php echo escapeHtml($_POST['name'] ?? ''); ?>"
                        placeholder="e.g., Dr. Juan dela Cruz"
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
                        value="<?php echo escapeHtml($_POST['position'] ?? ''); ?>"
                        placeholder="e.g., School President, Vice President for Academic Affairs"
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
                        placeholder="Describe the member's role and responsibilities..."
                    ><?php echo escapeHtml($_POST['description'] ?? ''); ?></textarea>
                    <small class="form-text">Brief description of duties and responsibilities</small>
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
                        value="<?php echo escapeHtml($_POST['email'] ?? ''); ?>"
                        placeholder="email@smcc.edu.ph"
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
                        value="<?php echo escapeHtml($_POST['phone'] ?? ''); ?>"
                        placeholder="(055) 123-4567"
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
                        value="<?php echo escapeHtml($_POST['office_location'] ?? ''); ?>"
                        placeholder="e.g., Administration Building, 2nd Floor"
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
                        value="<?php echo escapeHtml($_POST['display_order'] ?? '0'); ?>"
                        min="0"
                    >
                    <small class="form-text">Lower numbers appear first. Use 0 for automatic ordering.</small>
                </div>
                
                <div class="form-group">
                    <label class="checkbox-label">
                        <input 
                            type="checkbox" 
                            name="is_active" 
                            value="1"
                            <?php echo isset($_POST['is_active']) || !isset($_POST['name']) ? 'checked' : ''; ?>
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
                    <div class="image-upload-area" id="imageUploadArea">
                        <div class="upload-placeholder">
                            <i class="fas fa-user-circle"></i>
                            <p>Click to upload photo</p>
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
                    <small class="form-text">Recommended: Square image, at least 400x400px</small>
                </div>
            </div>
            
            <!-- Submit Buttons -->
            <div class="form-card">
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary btn-block">
                        <i class="fas fa-check"></i> Add Member
                    </button>
                    <a href="index.php" class="btn btn-secondary btn-block">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                </div>
            </div>
        </div>
    </div>
</form>

<script>
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
/* ═══════════════════════════════════════════════════════════════
   UPDATED STYLING FOR ADMINISTRATION CREATE.PHP
   (To match announcements/create.php styling)
   Replace the <style> section in your create.php with this
   ═══════════════════════════════════════════════════════════════ */

/* ─── PAGE HEADER ─── */
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

.subtitle {
    color: var(--admin-text-muted);
    margin-top: 0.5rem;
    font-size: 0.95rem;
}

.page-actions {
    display: flex;
    gap: 0.75rem;
}

/* ─── BUTTONS ─── */
.btn {
    padding: 0.75rem 1.5rem;
    border: none;
    border-radius: 8px;
    font-weight: 500;
    font-size: 0.95rem;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    text-decoration: none;
    transition: all 0.3s;
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
    box-shadow: 0 8px 16px rgba(0, 63, 135, 0.25);
}

.btn-primary {
    padding: 1rem 1.5rem;
    background: var(--admin-primary);
    color: white;
    border: none;
    font-weight: 600;
    font-size: 1rem;
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
    justify-content: center;
}

/* ─── FORM LAYOUT ─── */
.form-grid {
    display: grid;
    grid-template-columns: 1fr 380px;
    gap: 1.5rem;
    align-items: start;
}

.form-sidebar {
    position: sticky;
    top: 2rem;
}

/* ─── FORM CARDS ─── */
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

/* ─── FORM GROUPS ─── */
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

/* ─── FORM CONTROLS ─── */
.form-control {
    width: 100%;
    padding: 0.875rem 1rem;
    border: 2px solid var(--admin-border);
    border-radius: 8px;
    font-size: 0.95rem;
    font-family: inherit;
    transition: all 0.3s ease;
    background: white;
    box-sizing: border-box;
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
    color: var(--admin-text-muted);
}

textarea.form-control {
    resize: vertical;
    min-height: 120px;
    line-height: 1.6;
}

input[type="number"].form-control {
    appearance: textfield;
}

input[type="number"].form-control::-webkit-outer-spin-button,
input[type="number"].form-control::-webkit-inner-spin-button {
    -webkit-appearance: none;
    margin: 0;
}

/* ─── IMAGE UPLOAD ─── */
.image-upload-area {
    position: relative;
    border: 2px dashed var(--admin-border);
    border-radius: 8px;
    padding: 2rem;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s;
    background: #fafbfc;
}

.image-upload-area:hover {
    border-color: var(--admin-primary);
    background: #f0f4ff;
    transform: translateY(-2px);
}

.upload-placeholder {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.75rem;
}

.upload-placeholder i {
    font-size: 3rem;
    color: var(--admin-text-muted);
    transition: all 0.3s;
}

.image-upload-area:hover .upload-placeholder i {
    color: var(--admin-primary);
    transform: scale(1.1);
}

.upload-placeholder p {
    margin: 0;
    font-weight: 500;
    color: var(--admin-text);
}

.upload-placeholder small {
    color: var(--admin-text-muted);
    font-size: 0.825rem;
}

.file-input {
    display: none;
}

.image-preview {
    position: relative;
}

.image-preview img {
    width: 100%;
    border-radius: 8px;
    display: block;
}

.remove-image {
    position: absolute;
    top: 10px;
    right: 10px;
    width: 32px;
    height: 32px;
    background: rgba(220, 53, 69, 0.95);
    color: white;
    border: none;
    border-radius: 50%;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    transition: all 0.3s;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
}

.remove-image:hover {
    background: #c82333;
    transform: scale(1.15);
    box-shadow: 0 4px 12px rgba(220, 53, 69, 0.4);
}

/* ─── FORM TEXT ─── */
.form-text {
    display: block;
    margin-top: 0.5rem;
    font-size: 0.825rem;
    color: var(--admin-text-muted);
    line-height: 1.4;
}

/* ─── CHECKBOX ─── */
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

/* ─── FORM ACTIONS ─── */
.form-actions {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

/* ─── ALERT STYLING ─── */
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

.alert strong {
    font-weight: 600;
}

.alert ul {
    margin: 0.5rem 0 0;
    padding-left: 1.25rem;
}

.alert li {
    margin: 0.25rem 0;
}

/* ─── RESPONSIVE ─── */
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
    
    .page-actions {
        width: 100%;
    }
    
    .btn-secondary {
        width: 100%;
        justify-content: center;
    }
}

@media (max-width: 640px) {
    .form-card {
        padding: 1.5rem;
    }
}
</style>

<?php include '../includes/admin-footer.php'; ?>
