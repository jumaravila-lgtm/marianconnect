<?php
require_once '../includes/auth-check.php';
$db = getDB();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = sanitize($_POST['title'] ?? '');
    $subtitle = sanitize($_POST['subtitle'] ?? '');
    $buttonText = sanitize($_POST['button_text'] ?? '');
    $buttonLink = sanitize($_POST['button_link'] ?? '');
    $displayOrder = (int)($_POST['display_order'] ?? 0);
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    
    if (empty($title)) $errors[] = "Title is required";
    
    $imagePath = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../../assets/uploads/sliders/';
        if (!file_exists($uploadDir)) mkdir($uploadDir, 0777, true);
        
        $fileExtension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
        
        if (in_array($fileExtension, $allowedExtensions)) {
            $fileName = uniqid() . '_' . time() . '.' . $fileExtension;
            $filePath = $uploadDir . $fileName;
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $filePath)) {
                $imagePath = '/assets/uploads/sliders/' . $fileName;
            } else {
                $errors[] = "Failed to upload image";
            }
        } else {
            $errors[] = "Invalid image format";
        }
    } else {
        $errors[] = "Slider image is required";
    }
    
    if (empty($errors)) {
        try {
            $sql = "INSERT INTO homepage_sliders (title, subtitle, image_path, button_text, button_link, display_order, is_active) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $db->prepare($sql);
            $result = $stmt->execute([$title, $subtitle, $imagePath, $buttonText, $buttonLink, $displayOrder, $isActive]);
            
            if ($result) {
                logActivity($_SESSION['admin_id'], 'create', 'homepage_sliders', $db->lastInsertId(), "Created slider: {$title}");
                setFlashMessage('success', 'Slider created successfully!');
                redirect('index.php');
            }
        } catch (PDOException $e) {
            $errors[] = "Error: " . $e->getMessage();
        }
    }
}

$pageTitle = 'Create Slider';
include '../includes/admin-header.php';
?>

<div class="page-header">
    <h1>Create Homepage Slider</h1>
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
                <h3>Slider Content</h3>
                
                <div class="form-group">
                    <label for="title" class="required">Title</label>
                    <input type="text" id="title" name="title" class="form-control" required maxlength="255" value="<?php echo escapeHtml($_POST['title'] ?? ''); ?>" placeholder="Main heading for the slider">
                </div>
                
                <div class="form-group">
                    <label for="subtitle">Subtitle</label>
                    <textarea id="subtitle" name="subtitle" class="form-control" rows="3"><?php echo escapeHtml($_POST['subtitle'] ?? ''); ?></textarea>
                    <small class="form-text">Optional descriptive text below the title</small>
                </div>
            </div>
            
            <div class="form-card">
                <h3>Call-to-Action Button (Optional)</h3>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="button_text">Button Text</label>
                        <input type="text" id="button_text" name="button_text" class="form-control" maxlength="50" value="<?php echo escapeHtml($_POST['button_text'] ?? ''); ?>" placeholder="e.g., Learn More">
                    </div>
                    
                    <div class="form-group">
                        <label for="button_link">Button Link</label>
                        <input type="text" id="button_link" name="button_link" class="form-control" maxlength="255" value="<?php echo escapeHtml($_POST['button_link'] ?? ''); ?>" placeholder="e.g., /about">
                    </div>
                </div>
                
                <small class="form-text">Leave both empty to hide the button</small>
            </div>
        </div>
        
        <div class="form-sidebar">
            <div class="form-card">
                <h3>Slider Image</h3>
                
                <div class="form-group">
                    <label for="image" class="required">Upload Image</label>
                    <input type="file" id="image" name="image" class="form-control" accept="image/*" required>
                    <small class="form-text">Recommended: 1920x800px (JPG, PNG, GIF - Max 5MB)</small>
                </div>
                
                <div id="image-preview" class="image-preview" style="display:none">
                    <img id="preview-img" src="" alt="Preview">
                </div>
            </div>
            
            <div class="form-card">
                <h3>Settings</h3>
                
                <div class="form-group">
                    <label for="display_order">Display Order</label>
                    <input type="number" id="display_order" name="display_order" class="form-control" value="<?php echo escapeHtml($_POST['display_order'] ?? '0'); ?>">
                    <small class="form-text">Lower numbers appear first (0, 1, 2...)</small>
                </div>
                
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="is_active" value="1" checked>
                        <span>Active</span>
                    </label>
                    <small class="form-text">Only active sliders appear on homepage</small>
                </div>
            </div>
            
            <div class="form-card">
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary btn-block">
                        <i class="fas fa-check"></i> Create Slider
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
document.getElementById('image').addEventListener('change', function(e) {
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
