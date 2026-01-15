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
        <h1>✏️ Edit Event</h1>
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
                        <span>⭐ Featured</span>
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
.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
}

.page-header h1 {
    font-size: 1.75rem;
    font-weight: 700;
    color: var(--admin-text);
}
.form-row{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1rem}
.form-grid{display:grid;grid-template-columns:1fr 350px;gap:2rem}
.form-card{background:white;border-radius:12px;padding:1.5rem;margin-bottom:1.5rem;box-shadow:var(--admin-shadow)}
.form-card h3{font-size:1.1rem;font-weight:600;margin-bottom:1.5rem;padding-bottom:0.75rem;border-bottom:2px solid var(--admin-border)}
.form-group{margin-bottom:1.5rem}
.form-group label{display:block;margin-bottom:0.5rem;font-weight:500}
.form-group label.required::after{content:' *';color:var(--admin-danger)}
.form-control{width:100%;padding:0.75rem 1rem;border:2px solid var(--admin-border);border-radius:8px}
.form-control:focus{outline:none;border-color:var(--admin-primary);box-shadow:0 0 0 3px rgba(0,63,135,0.1)}
.page-actions {
    display: flex;
    gap: 0.5rem;
}

.btn {
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

.btn:hover {
    background: var(--admin-primary-dark);
    transform: translateY(-2px);
    box-shadow: var(--admin-shadow-lg);
}

.btn-secondary {
    background: #6c757d;
}

.btn-secondary:hover {
    background: #5a6268;
}

.btn-info {
    background: var(--admin-info);
}

.btn-info:hover {
    background: #138496;
}
.checkbox-label{display:flex;align-items:center;gap:0.5rem;cursor:pointer}
.checkbox-label input[type="checkbox"]{width:18px;height:18px}
.current-image img{width:100%;border-radius:8px;margin-bottom:0.5rem}
.btn-remove-current{width:100%;padding:0.5rem;background:var(--admin-danger);color:white;border:none;border-radius:6px;cursor:pointer}
.image-upload-area{border:2px dashed var(--admin-border);border-radius:8px;padding:2rem;text-align:center;cursor:pointer}
.upload-placeholder{display:flex;flex-direction:column;align-items:center}
.upload-placeholder i{font-size:3rem;color:var(--admin-text-muted)}
.file-input{display:none}
.form-actions{display:flex;flex-direction:column;gap:0.75rem}
.btn-block{width:100%;justify-content:center}
@media(max-width:1024px){.form-grid{grid-template-columns:1fr}}
</style>

<?php include '../includes/admin-footer.php'; ?>
