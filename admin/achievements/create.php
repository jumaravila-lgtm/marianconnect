<?php
require_once '../includes/auth-check.php';
$db = getDB();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = sanitize($_POST['title'] ?? '');
    $description = sanitize($_POST['description'] ?? '');
    $category = sanitize($_POST['category'] ?? '');
    $achievementDate = $_POST['achievement_date'] ?? '';
    $recipientName = sanitize($_POST['recipient_name'] ?? '');
    $recipientType = sanitize($_POST['recipient_type'] ?? '');
    $awardLevel = sanitize($_POST['award_level'] ?? '');
    $isFeatured = isset($_POST['is_featured']) ? 1 : 0;
    
    if (empty($title)) $errors[] = "Title is required";
    if (empty($description)) $errors[] = "Description is required";
    if (empty($category)) $errors[] = "Category is required";
    if (empty($achievementDate)) $errors[] = "Achievement date is required";
    
    $featuredImage = null;
    if (isset($_FILES['featured_image']) && $_FILES['featured_image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../../assets/uploads/achievements/';
        if (!file_exists($uploadDir)) mkdir($uploadDir, 0777, true);
        
        $fileExtension = strtolower(pathinfo($_FILES['featured_image']['name'], PATHINFO_EXTENSION));
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
        
        if (in_array($fileExtension, $allowedExtensions)) {
            $fileName = uniqid() . '_' . time() . '.' . $fileExtension;
            $filePath = $uploadDir . $fileName;
            
            if (move_uploaded_file($_FILES['featured_image']['tmp_name'], $filePath)) {
                $featuredImage = '/assets/uploads/achievements/' . $fileName;
            } else {
                $errors[] = "Failed to upload image";
            }
        } else {
            $errors[] = "Invalid image format";
        }
    }
    
    if (empty($errors)) {
        try {
            $sql = "INSERT INTO achievements (title, description, category, achievement_date, recipient_name, recipient_type, award_level, featured_image, is_featured) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $db->prepare($sql);
            $result = $stmt->execute([$title, $description, $category, $achievementDate, $recipientName, $recipientType, $awardLevel, $featuredImage, $isFeatured]);
            
            if ($result) {
                logActivity($_SESSION['admin_id'], 'create', 'achievements', $db->lastInsertId(), "Created achievement: {$title}");
                setFlashMessage('success', 'Achievement created successfully!');
                redirect('index.php');
            }
        } catch (PDOException $e) {
            $errors[] = "Error: " . $e->getMessage();
        }
    }
}

$pageTitle = 'Create Achievement';
include '../includes/admin-header.php';
?>

<div class="page-header">
    <h1>Create Achievement</h1>
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
                <h3>Achievement Details</h3>
                
                <div class="form-group">
                    <label for="title" class="required">Title</label>
                    <input type="text" id="title" name="title" class="form-control" required maxlength="255" value="<?php echo escapeHtml($_POST['title'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="description" class="required">Description</label>
                    <textarea id="description" name="description" class="form-control" rows="5" required><?php echo escapeHtml($_POST['description'] ?? ''); ?></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="recipient_name">Recipient Name</label>
                        <input type="text" id="recipient_name" name="recipient_name" class="form-control" maxlength="255" value="<?php echo escapeHtml($_POST['recipient_name'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="achievement_date" class="required">Achievement Date</label>
                        <input type="date" id="achievement_date" name="achievement_date" class="form-control" required value="<?php echo $_POST['achievement_date'] ?? date('Y-m-d'); ?>">
                    </div>
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
                <h3>Classification</h3>
                
                <div class="form-group">
                    <label for="category" class="required">Category</label>
                    <select id="category" name="category" class="form-control" required>
                        <option value="">Select Category</option>
                        <option value="academic" <?php echo ($_POST['category'] ?? '') === 'academic' ? 'selected' : ''; ?>>Academic</option>
                        <option value="sports" <?php echo ($_POST['category'] ?? '') === 'sports' ? 'selected' : ''; ?>>Sports</option>
                        <option value="cultural" <?php echo ($_POST['category'] ?? '') === 'cultural' ? 'selected' : ''; ?>>Cultural</option>
                        <option value="community_service" <?php echo ($_POST['category'] ?? '') === 'community_service' ? 'selected' : ''; ?>>Community Service</option>
                        <option value="research" <?php echo ($_POST['category'] ?? '') === 'research' ? 'selected' : ''; ?>>Research</option>
                        <option value="other" <?php echo ($_POST['category'] ?? '') === 'other' ? 'selected' : ''; ?>>Other</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="recipient_type">Recipient Type</label>
                    <select id="recipient_type" name="recipient_type" class="form-control">
                        <option value="student" <?php echo ($_POST['recipient_type'] ?? 'student') === 'student' ? 'selected' : ''; ?>>Student</option>
                        <option value="faculty" <?php echo ($_POST['recipient_type'] ?? '') === 'faculty' ? 'selected' : ''; ?>>Faculty</option>
                        <option value="institution" <?php echo ($_POST['recipient_type'] ?? '') === 'institution' ? 'selected' : ''; ?>>Institution</option>
                        <option value="alumni" <?php echo ($_POST['recipient_type'] ?? '') === 'alumni' ? 'selected' : ''; ?>>Alumni</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="award_level">Award Level</label>
                    <select id="award_level" name="award_level" class="form-control">
                        <option value="local" <?php echo ($_POST['award_level'] ?? 'local') === 'local' ? 'selected' : ''; ?>>Local</option>
                        <option value="regional" <?php echo ($_POST['award_level'] ?? '') === 'regional' ? 'selected' : ''; ?>>Regional</option>
                        <option value="national" <?php echo ($_POST['award_level'] ?? '') === 'national' ? 'selected' : ''; ?>>National</option>
                        <option value="international" <?php echo ($_POST['award_level'] ?? '') === 'international' ? 'selected' : ''; ?>>International</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="is_featured" value="1">
                        <span>Featured</span>
                    </label>
                </div>
            </div>
            
            <div class="form-card">
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary btn-block">
                        <i class="fas fa-check"></i> Create Achievement
                    </button>
                </div>
            </div>
        </div>
    </div>
</form>

<style>
/* ═══════════════════════════════════════════════════════════════
   UPDATED STYLING FOR ACHIEVEMENTS CREATE.PHP
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
    min-height: 120px;
    line-height: 1.6;
}

input[type="file"].form-control {
    padding: 0.75rem 1rem;
    font-size: 0.9rem;
    cursor: pointer;
}

input[type="date"].form-control {
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
