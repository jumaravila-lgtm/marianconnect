<?php
require_once '../includes/auth-check.php';
$db = getDB();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $orgName = sanitize($_POST['org_name'] ?? '');
    $acronym = sanitize($_POST['acronym'] ?? '');
    $description = sanitize($_POST['description'] ?? '');
    $category = sanitize($_POST['category'] ?? '');
    $adviserName = sanitize($_POST['adviser_name'] ?? '');
    $presidentName = sanitize($_POST['president_name'] ?? '');
    $contactEmail = sanitize($_POST['contact_email'] ?? '');
    $establishedYear = !empty($_POST['established_year']) ? (int)$_POST['established_year'] : null;
    $displayOrder = (int)($_POST['display_order'] ?? 0);
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    
    // Auto-generate slug
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $orgName)));
    
    if (empty($orgName)) $errors[] = "Organization name is required";
    if (empty($description)) $errors[] = "Description is required";
    if (empty($category)) $errors[] = "Category is required";
    
    // Handle logo upload
    $logo = null;
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../../assets/uploads/organizations/';
        if (!file_exists($uploadDir)) mkdir($uploadDir, 0777, true);
        
        $fileExtension = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
        
        if (in_array($fileExtension, $allowedExtensions)) {
            $fileName = uniqid() . '_' . time() . '.' . $fileExtension;
            $filePath = $uploadDir . $fileName;
            
            if (move_uploaded_file($_FILES['logo']['tmp_name'], $filePath)) {
                $logo = '/assets/uploads/organizations/' . $fileName;
            } else {
                $errors[] = "Failed to upload logo";
            }
        } else {
            $errors[] = "Invalid logo format";
        }
    }
    
    if (empty($errors)) {
        try {
            $sql = "INSERT INTO student_organizations (org_name, slug, acronym, description, logo, category, adviser_name, president_name, contact_email, established_year, display_order, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $db->prepare($sql);
            $result = $stmt->execute([$orgName, $slug, $acronym, $description, $logo, $category, $adviserName, $presidentName, $contactEmail, $establishedYear, $displayOrder, $isActive]);
            
            if ($result) {
                logActivity($_SESSION['admin_id'], 'create', 'student_organizations', $db->lastInsertId(), "Created organization: {$orgName}");
                setFlashMessage('success', 'Organization created successfully!');
                redirect('index.php');
            }
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $errors[] = "Organization name or slug already exists";
            } else {
                $errors[] = "Error: " . $e->getMessage();
            }
        }
    }
}

$pageTitle = 'Create Organization';
include '../includes/admin-header.php';
?>

<div class="page-header">
    <h1>Create Organization</h1>
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
                
                <div class="form-group">
                    <label for="org_name" class="required">Organization Name</label>
                    <input type="text" id="org_name" name="org_name" class="form-control" required maxlength="100" value="<?php echo escapeHtml($_POST['org_name'] ?? ''); ?>">
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="acronym">Acronym</label>
                        <input type="text" id="acronym" name="acronym" class="form-control" maxlength="20" value="<?php echo escapeHtml($_POST['acronym'] ?? ''); ?>" placeholder="e.g., CSC, SSG">
                    </div>
                    
                    <div class="form-group">
                        <label for="established_year">Established Year</label>
                        <input type="number" id="established_year" name="established_year" class="form-control" min="1900" max="<?php echo date('Y'); ?>" value="<?php echo escapeHtml($_POST['established_year'] ?? ''); ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="description" class="required">Description</label>
                    <textarea id="description" name="description" class="form-control" rows="4" required><?php echo escapeHtml($_POST['description'] ?? ''); ?></textarea>
                </div>
            </div>
            
            <div class="form-card">
                <h3>Organization Leaders</h3>
                
                <div class="form-group">
                    <label for="adviser_name">Adviser Name</label>
                    <input type="text" id="adviser_name" name="adviser_name" class="form-control" maxlength="100" value="<?php echo escapeHtml($_POST['adviser_name'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="president_name">President Name</label>
                    <input type="text" id="president_name" name="president_name" class="form-control" maxlength="100" value="<?php echo escapeHtml($_POST['president_name'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="contact_email">Contact Email</label>
                    <input type="email" id="contact_email" name="contact_email" class="form-control" maxlength="100" value="<?php echo escapeHtml($_POST['contact_email'] ?? ''); ?>">
                </div>
            </div>
        </div>
        
        <div class="form-sidebar">
            <div class="form-card">
                <h3>Logo</h3>
                
                <div class="form-group">
                    <label for="logo">Upload Logo</label>
                    <input type="file" id="logo" name="logo" class="form-control" accept="image/*">
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
                        <option value="academic" <?php echo ($_POST['category'] ?? '') === 'academic' ? 'selected' : ''; ?>>Academic</option>
                        <option value="sports" <?php echo ($_POST['category'] ?? '') === 'sports' ? 'selected' : ''; ?>>Sports</option>
                        <option value="cultural" <?php echo ($_POST['category'] ?? '') === 'cultural' ? 'selected' : ''; ?>>Cultural</option>
                        <option value="religious" <?php echo ($_POST['category'] ?? '') === 'religious' ? 'selected' : ''; ?>>Religious</option>
                        <option value="service" <?php echo ($_POST['category'] ?? '') === 'service' ? 'selected' : ''; ?>>Service</option>
                        <option value="special_interest" <?php echo ($_POST['category'] ?? '') === 'special_interest' ? 'selected' : ''; ?>>Special Interest</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="display_order">Display Order</label>
                    <input type="number" id="display_order" name="display_order" class="form-control" value="<?php echo escapeHtml($_POST['display_order'] ?? '0'); ?>">
                    <small class="form-text">Lower numbers appear first</small>
                </div>
                
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="is_active" value="1" checked>
                        <span>Active</span>
                    </label>
                </div>
            </div>
            
            <div class="form-card">
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary btn-block">
                        <i class="fas fa-check"></i> Create Organization
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
document.getElementById('logo').addEventListener('change', function(e) {
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
