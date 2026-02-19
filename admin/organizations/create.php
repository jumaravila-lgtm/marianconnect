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
/* ═══════════════════════════════════════════════════════════════
   UPDATED STYLING FOR ORGANIZATIONS CREATE.PHP
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

input[type="number"].form-control {
    appearance: textfield;
}

input[type="number"].form-control::-webkit-outer-spin-button,
input[type="number"].form-control::-webkit-inner-spin-button {
    -webkit-appearance: none;
    margin: 0;
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
