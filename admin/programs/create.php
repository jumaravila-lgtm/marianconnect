<?php
require_once '../includes/auth-check.php';
$db = getDB();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $programCode = sanitize($_POST['program_code'] ?? '');
    $programName = sanitize($_POST['program_name'] ?? '');
    $level = sanitize($_POST['level'] ?? '');
    $department = sanitize($_POST['department'] ?? '');
    $description = sanitize($_POST['description'] ?? '');
    $duration = sanitize($_POST['duration'] ?? '');
    $admissionRequirements = sanitize($_POST['admission_requirements'] ?? '');
    $careerOpportunities = sanitize($_POST['career_opportunities'] ?? '');
    $curriculumHighlights = sanitize($_POST['curriculum_highlights'] ?? '');
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    
    // Auto-generate slug from program name
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $programName)));
    
    // Validation
    if (empty($programCode)) $errors[] = "Program code is required";
    if (empty($programName)) $errors[] = "Program name is required";
    if (empty($level)) $errors[] = "Level is required";
    if (empty($description)) $errors[] = "Description is required";
    
    // Handle file upload
    $featuredImage = null;
    if (isset($_FILES['featured_image']) && $_FILES['featured_image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../../assets/uploads/programs/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        $fileExtension = strtolower(pathinfo($_FILES['featured_image']['name'], PATHINFO_EXTENSION));
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
        
        if (in_array($fileExtension, $allowedExtensions)) {
            $fileName = uniqid() . '_' . time() . '.' . $fileExtension;
            $filePath = $uploadDir . $fileName;
            
            if (move_uploaded_file($_FILES['featured_image']['tmp_name'], $filePath)) {
                $featuredImage = '/assets/uploads/programs/' . $fileName;
            } else {
                $errors[] = "Failed to upload image";
            }
        } else {
            $errors[] = "Invalid image format. Only JPG, PNG, and GIF allowed";
        }
    }
    
    if (empty($errors)) {
        try {
            $sql = "INSERT INTO academic_programs (program_code, program_name, slug, level, department, description, duration, featured_image, admission_requirements, career_opportunities, curriculum_highlights, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $db->prepare($sql);
            $result = $stmt->execute([$programCode, $programName, $slug, $level, $department, $description, $duration, $featuredImage, $admissionRequirements, $careerOpportunities, $curriculumHighlights, $isActive]);
            
            if ($result) {
                logActivity($_SESSION['admin_id'], 'create', 'academic_programs', $db->lastInsertId(), "Created program: {$programName}");
                setFlashMessage('success', 'Program created successfully!');
                redirect('index.php');
            }
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $errors[] = "Program code or slug already exists";
            } else {
                $errors[] = "Error: " . $e->getMessage();
            }
        }
    }
}

$pageTitle = 'Create Program';
include '../includes/admin-header.php';
?>

<div class="page-header">
    <h1>Create Academic Program</h1>
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
                <h3>Basic Information</h3>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="program_code" class="required">Program Code</label>
                        <input type="text" id="program_code" name="program_code" class="form-control" required maxlength="20" value="<?php echo escapeHtml($_POST['program_code'] ?? ''); ?>" placeholder="e.g., BSCS, BSBA">
                    </div>
                    
                    <div class="form-group">
                        <label for="duration">Duration</label>
                        <input type="text" id="duration" name="duration" class="form-control" maxlength="50" value="<?php echo escapeHtml($_POST['duration'] ?? ''); ?>" placeholder="e.g., 4 years">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="program_name" class="required">Program Name</label>
                    <input type="text" id="program_name" name="program_name" class="form-control" required maxlength="255" value="<?php echo escapeHtml($_POST['program_name'] ?? ''); ?>" placeholder="Bachelor of Science in Computer Science">
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="level" class="required">Level</label>
                        <select id="level" name="level" class="form-control" required>
                            <option value="">Select Level</option>
                            <option value="elementary" <?php echo ($_POST['level'] ?? '') === 'elementary' ? 'selected' : ''; ?>>Elementary</option>
                            <option value="junior_high" <?php echo ($_POST['level'] ?? '') === 'junior_high' ? 'selected' : ''; ?>>Junior High</option>
                            <option value="senior_high" <?php echo ($_POST['level'] ?? '') === 'senior_high' ? 'selected' : ''; ?>>Senior High</option>
                            <option value="college" <?php echo ($_POST['level'] ?? '') === 'college' ? 'selected' : ''; ?>>College</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="department">Department</label>
                        <input type="text" id="department" name="department" class="form-control" maxlength="100" value="<?php echo escapeHtml($_POST['department'] ?? ''); ?>" placeholder="e.g., College of Computer Studies">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="description" class="required">Description</label>
                    <textarea id="description" name="description" class="form-control" rows="4" required><?php echo escapeHtml($_POST['description'] ?? ''); ?></textarea>
                </div>
            </div>
            
            <div class="form-card">
                <h3>Additional Details</h3>
                
                <div class="form-group">
                    <label for="admission_requirements">Admission Requirements</label>
                    <textarea id="admission_requirements" name="admission_requirements" class="form-control" rows="4"><?php echo escapeHtml($_POST['admission_requirements'] ?? ''); ?></textarea>
                    <small class="form-text">List the requirements for admission</small>
                </div>
                
                <div class="form-group">
                    <label for="career_opportunities">Career Opportunities</label>
                    <textarea id="career_opportunities" name="career_opportunities" class="form-control" rows="4"><?php echo escapeHtml($_POST['career_opportunities'] ?? ''); ?></textarea>
                    <small class="form-text">Describe potential career paths</small>
                </div>
                
                <div class="form-group">
                    <label for="curriculum_highlights">Curriculum Highlights</label>
                    <textarea id="curriculum_highlights" name="curriculum_highlights" class="form-control" rows="4"><?php echo escapeHtml($_POST['curriculum_highlights'] ?? ''); ?></textarea>
                    <small class="form-text">Key subjects and focus areas</small>
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
                    <img src="" id="preview-img" alt="Preview">
                </div>
            </div>
            
            <div class="form-card">
                <h3>Settings</h3>
                
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="is_active" value="1" checked>
                        <span>Active</span>
                    </label>
                    <small class="form-text">Make this program visible on the website</small>
                </div>
            </div>
            
            <div class="form-card">
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary btn-block">
                        <i class="fas fa-check"></i> Create Program
                    </button>
                </div>
            </div>
        </div>
    </div>
</form>

<style>
/* ═══════════════════════════════════════════════════════════════
   UPDATED STYLING FOR PROGRAMS CREATE.PHP
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

/* ─── FORM ROW ─── */
.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.25rem;
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
    min-height: 100px;
    line-height: 1.6;
}

input[type="file"].form-control {
    padding: 0.75rem 1rem;
    font-size: 0.9rem;
    cursor: pointer;
}

/* ─── SELECT STYLING ─── */
select.form-control {
    cursor: pointer;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23333' d='M6 9L1 4h10z'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 1rem center;
    background-size: 12px;
    appearance: none;
    padding-right: 2.5rem;
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

.alert ul {
    margin: 0;
    padding-left: 1.25rem;
}

.alert li {
    margin: 0.25rem 0;
}

/* ─── IMAGE PREVIEW ─── */
.image-preview {
    margin-top: 1rem;
    border-radius: 8px;
    overflow: hidden;
    border: 2px solid var(--admin-border);
    transition: all 0.3s;
}

.image-preview:hover {
    border-color: var(--admin-primary);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
}

.image-preview img {
    width: 100%;
    height: auto;
    display: block;
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
    
    .form-row {
        grid-template-columns: 1fr;
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
