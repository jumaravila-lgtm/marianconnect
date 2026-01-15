<?php
require_once '../includes/auth-check.php';
$db = getDB();
$errors = [];

// Get program ID
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    setFlashMessage('error', 'Invalid program ID');
    redirect('index.php');
}

// Fetch program details
try {
    $stmt = $db->prepare("SELECT * FROM academic_programs WHERE program_id = ?");
    $stmt->execute([$id]);
    $program = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$program) {
        setFlashMessage('error', 'Program not found');
        redirect('index.php');
    }
} catch (PDOException $e) {
    setFlashMessage('error', 'Database error: ' . $e->getMessage());
    redirect('index.php');
}

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
    $featuredImage = $program['featured_image'];
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
                // Delete old image
                if ($program['featured_image'] && file_exists('../../' . $program['featured_image'])) {
                    unlink('../../' . $program['featured_image']);
                }
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
            $sql = "UPDATE academic_programs SET program_code = ?, program_name = ?, slug = ?, level = ?, department = ?, description = ?, duration = ?, featured_image = ?, admission_requirements = ?, career_opportunities = ?, curriculum_highlights = ?, is_active = ? WHERE program_id = ?";
            $stmt = $db->prepare($sql);
            $result = $stmt->execute([$programCode, $programName, $slug, $level, $department, $description, $duration, $featuredImage, $admissionRequirements, $careerOpportunities, $curriculumHighlights, $isActive, $id]);
            
            if ($result) {
                logActivity($_SESSION['admin_id'], 'update', 'academic_programs', $id, "Updated program: {$programName}");
                setFlashMessage('success', 'Program updated successfully!');
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
    
    // Keep posted values
    $program = array_merge($program, $_POST);
}

$pageTitle = 'Edit Program';
include '../includes/admin-header.php';
?>

<div class="page-header">
    <h1>✏️ Edit Program</h1>
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
                <h3>Basic Information</h3>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="program_code" class="required">Program Code</label>
                        <input type="text" id="program_code" name="program_code" class="form-control" required maxlength="20" value="<?php echo escapeHtml($program['program_code']); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="duration">Duration</label>
                        <input type="text" id="duration" name="duration" class="form-control" maxlength="50" value="<?php echo escapeHtml($program['duration'] ?? ''); ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="program_name" class="required">Program Name</label>
                    <input type="text" id="program_name" name="program_name" class="form-control" required maxlength="255" value="<?php echo escapeHtml($program['program_name']); ?>">
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="level" class="required">Level</label>
                        <select id="level" name="level" class="form-control" required>
                            <option value="">Select Level</option>
                            <option value="elementary" <?php echo $program['level'] === 'elementary' ? 'selected' : ''; ?>>Elementary</option>
                            <option value="junior_high" <?php echo $program['level'] === 'junior_high' ? 'selected' : ''; ?>>Junior High</option>
                            <option value="senior_high" <?php echo $program['level'] === 'senior_high' ? 'selected' : ''; ?>>Senior High</option>
                            <option value="college" <?php echo $program['level'] === 'college' ? 'selected' : ''; ?>>College</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="department">Department</label>
                        <input type="text" id="department" name="department" class="form-control" maxlength="100" value="<?php echo escapeHtml($program['department'] ?? ''); ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="description" class="required">Description</label>
                    <textarea id="description" name="description" class="form-control" rows="4" required><?php echo escapeHtml($program['description']); ?></textarea>
                </div>
            </div>
            
            <div class="form-card">
                <h3>Additional Details</h3>
                
                <div class="form-group">
                    <label for="admission_requirements">Admission Requirements</label>
                    <textarea id="admission_requirements" name="admission_requirements" class="form-control" rows="4"><?php echo escapeHtml($program['admission_requirements'] ?? ''); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="career_opportunities">Career Opportunities</label>
                    <textarea id="career_opportunities" name="career_opportunities" class="form-control" rows="4"><?php echo escapeHtml($program['career_opportunities'] ?? ''); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="curriculum_highlights">Curriculum Highlights</label>
                    <textarea id="curriculum_highlights" name="curriculum_highlights" class="form-control" rows="4"><?php echo escapeHtml($program['curriculum_highlights'] ?? ''); ?></textarea>
                </div>
            </div>
        </div>
        
        <div class="form-sidebar">
            <div class="form-card">
                <h3>Featured Image</h3>
                
                <?php if ($program['featured_image']): ?>
                    <div class="current-image">
                        <img src="<?php echo escapeHtml($program['featured_image']); ?>" alt="Current image">
                        <p class="text-muted">Current image</p>
                    </div>
                <?php endif; ?>
                
                <div class="form-group">
                    <label for="featured_image">Upload New Image</label>
                    <input type="file" id="featured_image" name="featured_image" class="form-control" accept="image/*">
                    <small class="form-text">Leave empty to keep current image</small>
                </div>
                
                <div id="image-preview" class="image-preview" style="display:none">
                    <img id="preview-img" src="" alt="Preview">
                </div>
            </div>
            
            <div class="form-card">
                <h3>Settings</h3>
                
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="is_active" value="1" <?php echo $program['is_active'] ? 'checked' : ''; ?>>
                        <span>Active</span>
                    </label>
                </div>
            </div>
            
            <div class="form-card">
                <h3>Meta Information</h3>
                <div class="meta-info">
                    <div class="meta-item">
                        <span class="meta-label">Created:</span>
                        <span class="meta-value"><?php echo date('M d, Y g:i A', strtotime($program['created_at'])); ?></span>
                    </div>
                    <div class="meta-item">
                        <span class="meta-label">Updated:</span>
                        <span class="meta-value"><?php echo date('M d, Y g:i A', strtotime($program['updated_at'])); ?></span>
                    </div>
                </div>
            </div>
            
            <div class="form-card">
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary btn-block">
                        <i class="fas fa-save"></i> Update Program
                    </button>
                </div>
            </div>
        </div>
    </div>
</form>

<style>
.page-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:2rem}
.header-actions{display:flex;gap:0.75rem}
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
.btn{padding:0.6rem 1.25rem;border:none;border-radius:8px;font-weight:500;cursor:pointer;display:inline-flex;align-items:center;gap:0.5rem;text-decoration:none;transition:all 0.2s}
.btn-primary{background:var(--admin-primary);color:white}
.btn-primary:hover{background:#004a99;transform:translateY(-2px)}
.btn-secondary{background:#6c757d;color:white}
.btn-secondary:hover{background:#5a6268}
.btn-danger{background:#dc3545;color:white}
.btn-danger:hover{background:#c82333}
.alert{padding:1rem;border-radius:8px;margin-bottom:1.5rem}
.alert-danger{background:#ffebee;border:1px solid #ef5350;color:#c62828}
.alert ul{margin:0;padding-left:1.5rem}
.current-image{margin-bottom:1rem}
.current-image img{width:100%;border-radius:8px}
.current-image .text-muted{text-align:center;margin-top:0.5rem;font-size:0.85rem;color:var(--admin-text-muted)}
.image-preview{margin-top:1rem;border-radius:8px;overflow:hidden}
.image-preview img{width:100%;height:auto;display:block}
.meta-info{font-size:0.9rem}
.meta-item{display:flex;justify-content:space-between;padding:0.75rem 0;border-bottom:1px solid var(--admin-border)}
.meta-item:last-child{border-bottom:none}
.meta-label{color:var(--admin-text-muted);font-weight:500}
.meta-value{color:var(--admin-text);font-weight:400}
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
