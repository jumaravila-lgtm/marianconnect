<?php
/**
 * MARIANCONNECT - Create Event
 */

// Authentication check
require_once '../includes/auth-check.php';

$db = getDB();
$errors = [];

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Debug: Log form submission
    error_log("Event form submitted: " . print_r($_POST, true));
    
    // Verify CSRF token
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid security token. Please refresh and try again.';
    }
    
    // Get form data
    $title = sanitize($_POST['title'] ?? '');
    $slug = sanitize($_POST['slug'] ?? '');
    $description = $_POST['description'] ?? '';
    $eventDate = $_POST['event_date'] ?? '';
    $eventTime = $_POST['event_time'] ?? '';
    $endDate = !empty($_POST['end_date']) ? $_POST['end_date'] : null;
    $location = sanitize($_POST['location'] ?? '');
    $category = sanitize($_POST['category'] ?? 'other');
    $organizer = sanitize($_POST['organizer'] ?? '');
    $contactInfo = sanitize($_POST['contact_info'] ?? '');
    $maxParticipants = !empty($_POST['max_participants']) ? (int)$_POST['max_participants'] : null;
    $registrationRequired = isset($_POST['registration_required']) ? 1 : 0;
    $isFeatured = isset($_POST['is_featured']) ? 1 : 0;
    
    // Get status from button
    $status = 'upcoming';
    if (isset($_POST['action'])) {
        $status = $_POST['action'] === 'publish' ? 'upcoming' : 'upcoming';
    }
    
    // Validation
    if (empty($title)) {
        $errors[] = "Title is required";
    }
    
    if (empty($slug)) {
        $slug = generateSlug($title);
    }
    
    // Check if slug exists
    $slugCheck = $db->prepare("SELECT event_id FROM events WHERE slug = ?");
    $slugCheck->execute([$slug]);
    if ($slugCheck->rowCount() > 0) {
        $errors[] = "Slug already exists. Please use a different one.";
    }
    
    if (empty($eventDate)) {
        $errors[] = "Event date is required";
    }
    
    if (empty($location)) {
        $errors[] = "Location is required";
    }
    
    if (empty($description)) {
        $errors[] = "Description is required";
    }
    
    // Handle image upload
    $featuredImage = null;
    if (isset($_FILES['featured_image']) && $_FILES['featured_image']['error'] === UPLOAD_ERR_OK) {
        $uploadResult = secureFileUpload(
            $_FILES['featured_image'],
            '../../assets/uploads/events',
            ALLOWED_IMAGE_TYPES
        );
        
        if ($uploadResult['success']) {
            $featuredImage = '/assets/uploads/events/' . $uploadResult['filename'];
        } else {
            $errors = array_merge($errors, $uploadResult['errors']);
        }
    }
    
    // If no errors, insert
    if (empty($errors)) {
        try {
            $sql = "
                INSERT INTO events 
                (title, slug, description, event_date, event_time, end_date, location, 
                featured_image, category, status, organizer, contact_info, max_participants, 
                registration_required, is_featured, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ";
            
            $stmt = $db->prepare($sql);
            $result = $stmt->execute([
                $title, $slug, $description, $eventDate, $eventTime, $endDate, $location,
                $featuredImage, $category, $status, $organizer, $contactInfo, 
                $maxParticipants, $registrationRequired, $isFeatured, $_SESSION['admin_id']
            ]);
            
            if ($result) {
                $eventId = $db->lastInsertId();
                logActivity($_SESSION['admin_id'], 'create', 'events', $eventId, "Created event: {$title}");
                setFlashMessage('success', 'Event created successfully!');
                redirect('edit.php?id=' . $eventId);
            }
        } catch (PDOException $e) {
            $errors[] = "Database error: " . $e->getMessage();
        }
    }
}

$pageTitle = 'Create Event';
include '../includes/admin-header.php';
?>

<div class="page-header">
    <div>
        <h1>Create Event</h1>
        <p class="subtitle">Add a new event to your calendar</p>
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

<form method="POST" action="" enctype="multipart/form-data" class="event-form">
    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
    
    <div class="form-grid">
        <!-- Main Content -->
        <div class="form-main">
            <div class="form-card">
                <h3>Event Details</h3>
                
                <div class="form-group">
                    <label for="title" class="required">Event Title</label>
                    <input type="text" id="title" name="title" class="form-control" required maxlength="255" value="<?php echo escapeHtml($_POST['title'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="slug">URL Slug</label>
                    <input type="text" id="slug" name="slug" class="form-control" value="<?php echo escapeHtml($_POST['slug'] ?? ''); ?>">
                    <small class="form-text">Leave blank to auto-generate</small>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="event_date" class="required">Event Date</label>
                        <input type="date" id="event_date" name="event_date" class="form-control" required value="<?php echo $_POST['event_date'] ?? ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="event_time">Event Time</label>
                        <input type="time" id="event_time" name="event_time" class="form-control" value="<?php echo $_POST['event_time'] ?? ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="end_date">End Date (Optional)</label>
                        <input type="date" id="end_date" name="end_date" class="form-control" value="<?php echo $_POST['end_date'] ?? ''; ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="location" class="required">Location</label>
                    <input type="text" id="location" name="location" class="form-control" required maxlength="255" value="<?php echo escapeHtml($_POST['location'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="description" class="required">Description</label>
                    <textarea id="description" name="description" class="form-control tinymce-editor" rows="10" required><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                </div>
            </div>
            
            <div class="form-card">
                <h3>Additional Information</h3>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="organizer">Organizer</label>
                        <input type="text" id="organizer" name="organizer" class="form-control" maxlength="100" value="<?php echo escapeHtml($_POST['organizer'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="contact_info">Contact Info</label>
                        <input type="text" id="contact_info" name="contact_info" class="form-control" maxlength="255" value="<?php echo escapeHtml($_POST['contact_info'] ?? ''); ?>">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="max_participants">Max Participants</label>
                        <input type="number" id="max_participants" name="max_participants" class="form-control" min="1" value="<?php echo escapeHtml($_POST['max_participants'] ?? ''); ?>">
                        <small class="form-text">Leave empty for unlimited</small>
                    </div>
                    
                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="registration_required" value="1" <?php echo isset($_POST['registration_required']) ? 'checked' : ''; ?>>
                            <span>Registration Required</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Sidebar -->
        <div class="form-sidebar">
            <div class="form-card">
                <h3>Category</h3>
                <div class="form-group">
                    <select id="category" name="category" class="form-control">
                        <option value="other" <?php echo ($_POST['category'] ?? 'other') === 'other' ? 'selected' : ''; ?>>Other</option>
                        <option value="academic" <?php echo ($_POST['category'] ?? '') === 'academic' ? 'selected' : ''; ?>>Academic</option>
                        <option value="sports" <?php echo ($_POST['category'] ?? '') === 'sports' ? 'selected' : ''; ?>>Sports</option>
                        <option value="cultural" <?php echo ($_POST['category'] ?? '') === 'cultural' ? 'selected' : ''; ?>>Cultural</option>
                        <option value="religious" <?php echo ($_POST['category'] ?? '') === 'religious' ? 'selected' : ''; ?>>Religious</option>
                        <option value="seminar" <?php echo ($_POST['category'] ?? '') === 'seminar' ? 'selected' : ''; ?>>Seminar</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="is_featured" value="1" <?php echo isset($_POST['is_featured']) ? 'checked' : ''; ?>>
                        <span>⭐ Feature this event</span>
                    </label>
                </div>
            </div>
            
            <div class="form-card">
                <h3>Featured Image</h3>
                <div class="form-group">
                    <div class="image-upload-area" id="imageUploadArea">
                        <div class="upload-placeholder">
                            <i class="fas fa-cloud-upload-alt"></i>
                            <p>Click to upload or drag and drop</p>
                            <small>JPG, PNG, GIF or WebP (Max 5MB)</small>
                        </div>
                        <input type="file" id="featured_image" name="featured_image" accept="image/*" class="file-input">
                        <div class="image-preview" id="imagePreview" style="display: none;">
                            <img src="" alt="Preview" id="previewImage">
                            <button type="button" class="remove-image" id="removeImage">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="form-card">
                <div class="form-actions">
                    <button type="submit" name="action" value="publish" class="btn btn-primary btn-block">
                        <i class="fas fa-check"></i> Create Event
                    </button>
                    <a href="index.php" class="btn btn-secondary btn-block">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                </div>
            </div>
        </div>
    </div>
</form>

<script src="https://cdn.tiny.cloud/1/45anirrmzgk362e3h0rf9oosec2cxev5w0atdjl7srwi8wri/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
<script>
tinymce.init({
    selector: '.tinymce-editor',
    height: 400,
    menubar: false,
    plugins: ['advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'searchreplace', 'visualblocks', 'code', 'fullscreen', 'insertdatetime', 'table', 'wordcount'],
    toolbar: 'undo redo | blocks | bold italic underline | alignleft aligncenter alignright | bullist numlist | link image | code',
    content_style: 'body { font-family: Arial, sans-serif; font-size: 14px; padding: 10px; }',
    branding: false,
    promotion: false,
    setup: function(editor) {
        editor.on('change', function() { editor.save(); });
    }
});

document.querySelector('.event-form').addEventListener('submit', function(e) {
    tinymce.triggerSave();
    const description = document.getElementById('description').value.trim();
    if (!description || description === '<p></p>') {
        e.preventDefault();
        alert('Please add a description for the event.');
        return false;
    }
    const submitButton = e.submitter;
    if (submitButton) {
        submitButton.disabled = true;
        submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
    }
});

// Auto-generate slug
document.getElementById('title').addEventListener('input', function() {
    const slugField = document.getElementById('slug');
    if (!slugField.value || slugField.dataset.manual !== 'true') {
        slugField.value = this.value.toLowerCase().replace(/[^a-z0-9\s-]/g, '').replace(/\s+/g, '-').replace(/-+/g, '-').substring(0, 200);
    }
});
document.getElementById('slug').addEventListener('input', function() { this.dataset.manual = 'true'; });

// Image upload
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
});
</script>

<style>
.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
}
.page-actions {
    display: flex;
    gap: 0.5rem;
}

.btn-secondary {
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

.btn-secondary:hover {
    background: var(--admin-primary-dark);
    transform: translateY(-2px);
    box-shadow: var(--admin-shadow-lg);
}
.form-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; }
.form-grid { display: grid; grid-template-columns: 1fr 350px; gap: 2rem; }
.form-card { background: white; border-radius: 12px; padding: 1.5rem; margin-bottom: 1.5rem; box-shadow: var(--admin-shadow); }
.form-card h3 { font-size: 1.1rem; font-weight: 600; margin-bottom: 1.5rem; padding-bottom: 0.75rem; border-bottom: 2px solid var(--admin-border); }
.form-group { margin-bottom: 1.5rem; }
.form-group label { display: block; margin-bottom: 0.5rem; font-weight: 500; }
.form-group label.required::after { content: ' *'; color: var(--admin-danger); }
.form-control { width: 100%; padding: 0.75rem 1rem; border: 2px solid var(--admin-border); border-radius: 8px; font-size: 0.95rem; }
.form-control:focus { outline: none; border-color: var(--admin-primary); box-shadow: 0 0 0 3px rgba(0, 63, 135, 0.1); }
.form-text { display: block; margin-top: 0.5rem; font-size: 0.85rem; color: var(--admin-text-muted); }
.checkbox-label { display: flex; align-items: center; gap: 0.5rem; cursor: pointer; font-weight: 400; }
.checkbox-label input[type="checkbox"] { width: 18px; height: 18px; cursor: pointer; }
.image-upload-area { position: relative; border: 2px dashed var(--admin-border); border-radius: 8px; padding: 2rem; text-align: center; cursor: pointer; transition: all 0.3s; }
.image-upload-area:hover { border-color: var(--admin-primary); background: var(--admin-hover); }
.upload-placeholder { display: flex; flex-direction: column; align-items: center; gap: 0.5rem; }
.upload-placeholder i { font-size: 3rem; color: var(--admin-text-muted); }
.file-input { display: none; }
.image-preview { position: relative; }
.image-preview img { width: 100%; border-radius: 8px; }
.remove-image { position: absolute; top: 10px; right: 10px; width: 32px; height: 32px; background: var(--admin-danger); color: white; border: none; border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center; }
.form-actions { display: flex; flex-direction: column; gap: 0.75rem; }
.btn-block { width: 100%; justify-content: center; }
@media (max-width: 1024px) { .form-grid { grid-template-columns: 1fr; } }
</style>

<?php include '../includes/admin-footer.php';
