<?php
require_once '../includes/auth-check.php';
$db = getDB();
$errors = [];

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    setFlashMessage('error', 'Invalid organization ID');
    redirect('index.php');
}

try {
    $stmt = $db->prepare("SELECT * FROM student_organizations WHERE org_id = ?");
    $stmt->execute([$id]);
    $org = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$org) {
        setFlashMessage('error', 'Organization not found');
        redirect('index.php');
    }
} catch (PDOException $e) {
    setFlashMessage('error', 'Database error');
    redirect('index.php');
}

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
    
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $orgName)));
    
    if (empty($orgName)) $errors[] = "Organization name is required";
    if (empty($description)) $errors[] = "Description is required";
    if (empty($category)) $errors[] = "Category is required";
    
    $logo = $org['logo'];
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../../assets/uploads/organizations/';
        if (!file_exists($uploadDir)) mkdir($uploadDir, 0777, true);
        
        $fileExtension = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
        
        if (in_array($fileExtension, $allowedExtensions)) {
            $fileName = uniqid() . '_' . time() . '.' . $fileExtension;
            $filePath = $uploadDir . $fileName;
            
            if (move_uploaded_file($_FILES['logo']['tmp_name'], $filePath)) {
                if ($org['logo'] && file_exists('../../' . $org['logo'])) {
                    unlink('../../' . $org['logo']);
                }
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
            $sql = "UPDATE student_organizations SET org_name = ?, slug = ?, acronym = ?, description = ?, logo = ?, category = ?, adviser_name = ?, president_name = ?, contact_email = ?, established_year = ?, display_order = ?, is_active = ? WHERE org_id = ?";
            $stmt = $db->prepare($sql);
            $result = $stmt->execute([$orgName, $slug, $acronym, $description, $logo, $category, $adviserName, $presidentName, $contactEmail, $establishedYear, $displayOrder, $isActive, $id]);
            
            if ($result) {
                logActivity($_SESSION['admin_id'], 'update', 'student_organizations', $id, "Updated organization: {$orgName}");
                setFlashMessage('success', 'Organization updated successfully!');
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
    
    $org = array_merge($org, $_POST);
}

$pageTitle = 'Edit Organization';
include '../includes/admin-header.php';
?>

<div class="page-header">
    <h1>Edit Organization</h1>
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
                
                <div class="form-group">
                    <label for="org_name" class="required">Organization Name</label>
                    <input type="text" id="org_name" name="org_name" class="form-control" required maxlength="100" value="<?php echo escapeHtml($org['org_name']); ?>">
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="acronym">Acronym</label>
                        <input type="text" id="acronym" name="acronym" class="form-control" maxlength="20" value="<?php echo escapeHtml($org['acronym'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="established_year">Established Year</label>
                        <input type="number" id="established_year" name="established_year" class="form-control" min="1900" max="<?php echo date('Y'); ?>" value="<?php echo escapeHtml($org['established_year'] ?? ''); ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="description" class="required">Description</label>
                    <textarea id="description" name="description" class="form-control" rows="4" required><?php echo escapeHtml($org['description']); ?></textarea>
                </div>
            </div>
            
            <div class="form-card">
                <h3>Organization Leaders</h3>
                
                <div class="form-group">
                    <label for="adviser_name">Adviser Name</label>
                    <input type="text" id="adviser_name" name="adviser_name" class="form-control" maxlength="100" value="<?php echo escapeHtml($org['adviser_name'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="president_name">President Name</label>
                    <input type="text" id="president_name" name="president_name" class="form-control" maxlength="100" value="<?php echo escapeHtml($org['president_name'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="contact_email">Contact Email</label>
                    <input type="email" id="contact_email" name="contact_email" class="form-control" maxlength="100" value="<?php echo escapeHtml($org['contact_email'] ?? ''); ?>">
                </div>
            </div>
        </div>
        
        <div class="form-sidebar">
            <div class="form-card">
                <h3>Logo</h3>
                
                <?php if ($org['logo']): ?>
                    <div class="current-image">
                        <img src="<?php echo escapeHtml($org['logo']); ?>" alt="Logo">
                        <p class="text-muted">Current logo</p>
                    </div>
                <?php endif; ?>
                
                <div class="form-group">
                    <label for="logo">Upload New Logo</label>
                    <input type="file" id="logo" name="logo" class="form-control" accept="image/*">
                    <small class="form-text">Leave empty to keep current logo</small>
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
                        <option value="academic" <?php echo $org['category'] === 'academic' ? 'selected' : ''; ?>>Academic</option>
                        <option value="sports" <?php echo $org['category'] === 'sports' ? 'selected' : ''; ?>>Sports</option>
                        <option value="cultural" <?php echo $org['category'] === 'cultural' ? 'selected' : ''; ?>>Cultural</option>
                        <option value="religious" <?php echo $org['category'] === 'religious' ? 'selected' : ''; ?>>Religious</option>
                        <option value="service" <?php echo $org['category'] === 'service' ? 'selected' : ''; ?>>Service</option>
                        <option value="special_interest" <?php echo $org['category'] === 'special_interest' ? 'selected' : ''; ?>>Special Interest</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="display_order">Display Order</label>
                    <input type="number" id="display_order" name="display_order" class="form-control" value="<?php echo $org['display_order']; ?>">
                </div>
                
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="is_active" value="1" <?php echo $org['is_active'] ? 'checked' : ''; ?>>
                        <span>Active</span>
                    </label>
                </div>
            </div>
            
            <div class="form-card">
                <h3>Meta Info</h3>
                <div class="meta-info">
                    <div class="meta-item">
                        <span class="meta-label">Created:</span>
                        <span class="meta-value"><?php echo date('M d, Y', strtotime($org['created_at'])); ?></span>
                    </div>
                    <div class="meta-item">
                        <span class="meta-label">Updated:</span>
                        <span class="meta-value"><?php echo date('M d, Y', strtotime($org['updated_at'])); ?></span>
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
    min-height: 120px;
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
