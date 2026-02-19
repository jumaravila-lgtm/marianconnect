<?php
require_once '../includes/auth-check.php';

$db = getDB();
$errors = [];
$eventId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$stmt = $db->prepare("SELECT * FROM events WHERE event_id = ?");
$stmt->execute([$eventId]);
$event = $stmt->fetch();

if (!$event) {
    setFlashMessage('error', 'Event not found.');
    redirect('index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = sanitize($_POST['title'] ?? '');
    $slug = sanitize($_POST['slug'] ?? '');
    $description = $_POST['description'] ?? '';
    $eventDate = $_POST['event_date'] ?? '';
    $eventTime = $_POST['event_time'] ?? '';
    $endDate = !empty($_POST['end_date']) ? $_POST['end_date'] : null;
    $location = sanitize($_POST['location'] ?? '');
    $category = sanitize($_POST['category'] ?? 'other');
    $status = sanitize($_POST['status'] ?? 'upcoming');
    $organizer = sanitize($_POST['organizer'] ?? '');
    $contactInfo = sanitize($_POST['contact_info'] ?? '');
    $maxParticipants = !empty($_POST['max_participants']) ? (int)$_POST['max_participants'] : null;
    $registrationRequired = isset($_POST['registration_required']) ? 1 : 0;
    $isFeatured = isset($_POST['is_featured']) ? 1 : 0;
    
    if (empty($title)) $errors[] = "Title is required";
    if (empty($slug)) $slug = generateSlug($title);
    if (empty($eventDate)) $errors[] = "Event date is required";
    if (empty($location)) $errors[] = "Location is required";
    if (empty($description)) $errors[] = "Description is required";
    
    $slugCheck = $db->prepare("SELECT event_id FROM events WHERE slug = ? AND event_id != ?");
    $slugCheck->execute([$slug, $eventId]);
    if ($slugCheck->rowCount() > 0) $errors[] = "Slug already exists";
    
    $featuredImage = $event['featured_image'];
    
    if (isset($_FILES['featured_image']) && $_FILES['featured_image']['error'] === UPLOAD_ERR_OK) {
        $uploadResult = secureFileUpload($_FILES['featured_image'], '../../assets/uploads/events', ALLOWED_IMAGE_TYPES);
        if ($uploadResult['success']) {
            if (!empty($event['featured_image'])) {
                $oldPath = '../../' . $event['featured_image'];
                if (file_exists($oldPath)) unlink($oldPath);
            }
            $featuredImage = '/assets/uploads/events/' . $uploadResult['filename'];
        } else {
            $errors = array_merge($errors, $uploadResult['errors']);
        }
    }
    
    if (isset($_POST['remove_image']) && $_POST['remove_image'] == '1') {
        if (!empty($event['featured_image'])) {
            $oldPath = '../../' . $event['featured_image'];
            if (file_exists($oldPath)) unlink($oldPath);
        }
        $featuredImage = null;
    }
    
    if (empty($errors)) {
        try {
            $sql = "UPDATE events SET title=?, slug=?, description=?, event_date=?, event_time=?, end_date=?, location=?, featured_image=?, category=?, status=?, organizer=?, contact_info=?, max_participants=?, registration_required=?, is_featured=?, updated_at=NOW() WHERE event_id=?";
            $stmt = $db->prepare($sql);
            $result = $stmt->execute([$title, $slug, $description, $eventDate, $eventTime, $endDate, $location, $featuredImage, $category, $status, $organizer, $contactInfo, $maxParticipants, $registrationRequired, $isFeatured, $eventId]);
            
            if ($result) {
                logActivity($_SESSION['admin_id'], 'update', 'events', $eventId, "Updated event: {$title}");
                setFlashMessage('success', 'Event updated successfully!');
                $stmt = $db->prepare("SELECT * FROM events WHERE event_id = ?");
                $stmt->execute([$eventId]);
                $event = $stmt->fetch();
            }
        } catch (PDOException $e) {
            $errors[] = "Database error: " . $e->getMessage();
        }
    }
}

$pageTitle = 'Edit Event';
include '../includes/admin-header.php';
?>

<div class="page-header">
    <div>
        <h1>Edit Event</h1>
        <p class="subtitle">Last updated: <?php echo formatDate($event['updated_at'], 'F j, Y g:i A'); ?></p>
    </div>
    <div class="page-actions">
        <a href="../../pages/event-detail.php?slug=<?php echo $event['slug']; ?>" class="btn btn-info" target="_blank">
            <i class="fas fa-eye"></i> Preview
        </a>
        <a href="index.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back
        </a>
    </div>
</div>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger">
    <strong>Errors:</strong>
    <ul><?php foreach ($errors as $error): ?><li><?php echo escapeHtml($error); ?></li><?php endforeach; ?></ul>
</div>
<?php endif; ?>

<form method="POST" action="" enctype="multipart/form-data" class="event-form">
    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
    <input type="hidden" name="remove_image" id="removeImageFlag" value="0">
    
    <div class="form-grid">
        <div class="form-main">
            <div class="form-card">
                <h3>Event Details</h3>
                
                <div class="form-group">
                    <label for="title" class="required">Event Title</label>
                    <input type="text" id="title" name="title" class="form-control" required value="<?php echo escapeHtml($event['title']); ?>">
                </div>
                
                <div class="form-group">
                    <label for="slug">URL Slug</label>
                    <input type="text" id="slug" name="slug" class="form-control" value="<?php echo escapeHtml($event['slug']); ?>">
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="event_date" class="required">Event Date</label>
                        <input type="date" id="event_date" name="event_date" class="form-control" required value="<?php echo $event['event_date']; ?>">
                    </div>
                    <div class="form-group">
                        <label for="event_time">Time</label>
                        <input type="time" id="event_time" name="event_time" class="form-control" value="<?php echo $event['event_time']; ?>">
                    </div>
                    <div class="form-group">
                        <label for="end_date">End Date</label>
                        <input type="date" id="end_date" name="end_date" class="form-control" value="<?php echo $event['end_date']; ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="location" class="required">Location</label>
                    <input type="text" id="location" name="location" class="form-control" required value="<?php echo escapeHtml($event['location']); ?>">
                </div>
                
                <div class="form-group">
                    <label for="description" class="required">Description</label>
                    <textarea id="description" name="description" class="form-control tinymce-editor" required><?php echo htmlspecialchars($event['description']); ?></textarea>
                </div>
            </div>
            
            <div class="form-card">
                <h3>Additional Info</h3>
                <div class="form-row">
                    <div class="form-group">
                        <label for="organizer">Organizer</label>
                        <input type="text" id="organizer" name="organizer" class="form-control" value="<?php echo escapeHtml($event['organizer']); ?>">
                    </div>
                    <div class="form-group">
                        <label for="contact_info">Contact</label>
                        <input type="text" id="contact_info" name="contact_info" class="form-control" value="<?php echo escapeHtml($event['contact_info']); ?>">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="max_participants">Max Participants</label>
                        <input type="number" id="max_participants" name="max_participants" class="form-control" value="<?php echo $event['max_participants']; ?>">
                    </div>
                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="registration_required" value="1" <?php echo $event['registration_required'] ? 'checked' : ''; ?>>
                            <span>Registration Required</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="form-sidebar">
            <div class="form-card">
                <h3>Status & Category</h3>
                <div class="form-group">
                    <label>Status</label>
                    <select name="status" class="form-control">
                        <option value="upcoming" <?php echo $event['status'] === 'upcoming' ? 'selected' : ''; ?>>Upcoming</option>
                        <option value="ongoing" <?php echo $event['status'] === 'ongoing' ? 'selected' : ''; ?>>Ongoing</option>
                        <option value="completed" <?php echo $event['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                        <option value="cancelled" <?php echo $event['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Category</label>
                    <select name="category" class="form-control">
                        <option value="other" <?php echo $event['category'] === 'other' ? 'selected' : ''; ?>>Other</option>
                        <option value="academic" <?php echo $event['category'] === 'academic' ? 'selected' : ''; ?>>Academic</option>
                        <option value="sports" <?php echo $event['category'] === 'sports' ? 'selected' : ''; ?>>Sports</option>
                        <option value="cultural" <?php echo $event['category'] === 'cultural' ? 'selected' : ''; ?>>Cultural</option>
                        <option value="religious" <?php echo $event['category'] === 'religious' ? 'selected' : ''; ?>>Religious</option>
                        <option value="seminar" <?php echo $event['category'] === 'seminar' ? 'selected' : ''; ?>>Seminar</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="is_featured" value="1" <?php echo $event['is_featured'] ? 'checked' : ''; ?>>
                        <span>Featured</span>
                    </label>
                </div>
            </div>
            
            <div class="form-card">
                <h3>Featured Image</h3>
                <?php if (!empty($event['featured_image'])): ?>
                <div class="current-image">
                    <img src="<?php echo escapeHtml(getImageUrl($event['featured_image'])); ?>" alt="Current">
                    <button type="button" class="btn-remove-current" onclick="document.getElementById('removeImageFlag').value='1'; this.parentElement.style.display='none';">
                        <i class="fas fa-times"></i> Remove
                    </button>
                </div>
                <?php endif; ?>
                <div class="image-upload-area" id="imageUploadArea">
                    <div class="upload-placeholder">
                        <i class="fas fa-cloud-upload-alt"></i>
                        <p>Upload Image</p>
                    </div>
                    <input type="file" id="featured_image" name="featured_image" accept="image/*" class="file-input">
                </div>
            </div>
            
            <div class="form-card">
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary btn-block">
                        <i class="fas fa-save"></i> Update Event
                    </button>
                    <a href="delete.php?id=<?php echo $event['event_id']; ?>" class="btn btn-danger btn-block">
                        <i class="fas fa-trash"></i> Delete
                    </a>
                </div>
            </div>
        </div>
    </div>
</form>

<script src="https://cdn.tiny.cloud/1/45anirrmzgk362e3h0rf9oosec2cxev5w0atdjl7srwi8wri/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
<script>
tinymce.init({selector:'.tinymce-editor',height:400,menubar:false,plugins:['advlist','autolink','lists','link','image','code','table'],toolbar:'undo redo|blocks|bold italic|bullist numlist|link',branding:false,setup:function(e){e.on('change',function(){e.save()})}});
document.querySelector('.event-form').addEventListener('submit',function(e){tinymce.triggerSave();if(e.submitter){e.submitter.disabled=true;e.submitter.innerHTML='<i class="fas fa-spinner fa-spin"></i> Saving...';}});
document.getElementById('imageUploadArea').addEventListener('click',()=>document.getElementById('featured_image').click());
</script>

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

.btn-info {
    background: white;
    color: var(--admin-info);
    border: 2px solid var(--admin-info);
}

.btn-info:hover {
    background: var(--admin-info);
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(23, 162, 184, 0.25);
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
    min-height: 150px;
    line-height: 1.6;
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

/* Current Image */
.current-image {
    margin-bottom: 1.5rem;
    position: relative;
    border-radius: 10px;
    overflow: hidden;
    border: 2px solid var(--admin-border);
}

.current-image img {
    width: 100%;
    display: block;
    border-radius: 8px;
}

.btn-remove-current {
    margin-top: 0.75rem;
    width: 100%;
    padding: 0.75rem;
    background: var(--admin-danger);
    color: white;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
}

.btn-remove-current:hover {
    background: #c82333;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(220, 53, 69, 0.3);
}

/* Image Upload Area */
.image-upload-area {
    position: relative;
    border: 2px dashed var(--admin-border);
    border-radius: 12px;
    padding: 3rem 2rem;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s;
    background: #fafbfc;
}

.image-upload-area:hover {
    border-color: var(--admin-primary);
    background: #f0f4ff;
    border-style: solid;
}

.upload-placeholder {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.75rem;
}

.upload-placeholder i {
    font-size: 3rem;
    color: var(--admin-primary);
    opacity: 0.5;
}

.upload-placeholder p {
    font-weight: 600;
    color: var(--admin-text);
    margin: 0;
    font-size: 0.95rem;
}

.upload-placeholder small {
    color: var(--admin-text-muted);
    font-size: 0.825rem;
}

.file-input {
    display: none;
}

/* Form Actions */
.form-actions {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.btn-block {
    width: 100%;
    justify-content: center;
}

/* Sidebar Sticky */
.form-sidebar {
    position: sticky;
    top: 2rem;
}

/* Select Styling */
select.form-control {
    cursor: pointer;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23333' d='M6 9L1 4h10z'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 1rem center;
    background-size: 12px;
    appearance: none;
    padding-right: 2.5rem;
}

/* Date Input Styling */
input[type="date"].form-control,
input[type="time"].form-control {
    cursor: pointer;
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
    margin: 0;
    padding-left: 1.25rem;
}

.alert li {
    margin: 0.25rem 0;
}

/* TinyMCE Enhancement */
.tox-tinymce {
    border: 2px solid var(--admin-border) !important;
    border-radius: 8px !important;
}

.tox-tinymce:focus-within {
    border-color: var(--admin-primary) !important;
    box-shadow: 0 0 0 4px rgba(0, 63, 135, 0.08) !important;
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

<?php include '../includes/admin-footer.php'; ?>
