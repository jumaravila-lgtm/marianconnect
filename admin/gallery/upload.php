<?php
require_once '../includes/auth-check.php';
$db = getDB();
$errors = [];
$successCount = 0;

// Function to create thumbnail
function createThumbnail($sourcePath, $thumbnailPath, $maxWidth = 300, $maxHeight = 300) {
    $imageInfo = getimagesize($sourcePath);
    if (!$imageInfo) return false;
    
    list($width, $height, $type) = $imageInfo;
    
    // Calculate new dimensions
    $ratio = min($maxWidth / $width, $maxHeight / $height);
    $newWidth = (int)($width * $ratio);
    $newHeight = (int)($height * $ratio);
    
    // Create image from source
    switch ($type) {
        case IMAGETYPE_JPEG:
            $source = imagecreatefromjpeg($sourcePath);
            break;
        case IMAGETYPE_PNG:
            $source = imagecreatefrompng($sourcePath);
            break;
        case IMAGETYPE_GIF:
            $source = imagecreatefromgif($sourcePath);
            break;
        default:
            return false;
    }
    
    if (!$source) return false;
    
    // Create thumbnail
    $thumbnail = imagecreatetruecolor($newWidth, $newHeight);
    
    // Preserve transparency for PNG and GIF
    if ($type == IMAGETYPE_PNG || $type == IMAGETYPE_GIF) {
        imagealphablending($thumbnail, false);
        imagesavealpha($thumbnail, true);
        $transparent = imagecolorallocatealpha($thumbnail, 255, 255, 255, 127);
        imagefilledrectangle($thumbnail, 0, 0, $newWidth, $newHeight, $transparent);
    }
    
    imagecopyresampled($thumbnail, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
    
    // Save thumbnail
    $result = false;
    switch ($type) {
        case IMAGETYPE_JPEG:
            $result = imagejpeg($thumbnail, $thumbnailPath, 85);
            break;
        case IMAGETYPE_PNG:
            $result = imagepng($thumbnail, $thumbnailPath, 8);
            break;
        case IMAGETYPE_GIF:
            $result = imagegif($thumbnail, $thumbnailPath);
            break;
    }
    
    imagedestroy($source);
    imagedestroy($thumbnail);
    
    return $result;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $category = sanitize($_POST['category'] ?? 'other');
    $eventId = !empty($_POST['event_id']) ? (int)$_POST['event_id'] : null;
    $isFeatured = isset($_POST['is_featured']) ? 1 : 0;
    
    // Handle multiple file uploads
    if (isset($_FILES['images']) && !empty($_FILES['images']['name'][0])) {
        $uploadDir = '../../assets/uploads/gallery/';
        $thumbnailDir = '../../assets/uploads/gallery/thumbnails/';
        
        if (!file_exists($uploadDir)) mkdir($uploadDir, 0777, true);
        if (!file_exists($thumbnailDir)) mkdir($thumbnailDir, 0777, true);
        
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
        $maxFileSize = 5 * 1024 * 1024; // 5MB
        
        $totalFiles = count($_FILES['images']['name']);
        
        for ($i = 0; $i < $totalFiles; $i++) {
            if ($_FILES['images']['error'][$i] === UPLOAD_ERR_OK) {
                $fileName = $_FILES['images']['name'][$i];
                $fileTmpName = $_FILES['images']['tmp_name'][$i];
                $fileSize = $_FILES['images']['size'][$i];
                
                $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                
                if (!in_array($fileExtension, $allowedExtensions)) {
                    $errors[] = "File '{$fileName}': Invalid format";
                    continue;
                }
                // MIME type validation (security check)
                $fileMimeType = mime_content_type($fileTmpName);
                $allowedMimeTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];

                if (!in_array($fileMimeType, $allowedMimeTypes)) {
                    $errors[] = "File '{$fileName}': Invalid file type. Allowed types: " . implode(', ', $allowedMimeTypes);
                    continue;
                }

                if ($fileSize > $maxFileSize) {
                    $errors[] = "File '{$fileName}': Too large (max 5MB)";
                    continue;
                }
                if ($fileSize > $maxFileSize) {
                    $errors[] = "File '{$fileName}': Too large (max 5MB)";
                    continue;
                }
                
                $newFileName = uniqid() . '_' . time() . '_' . $i . '.' . $fileExtension;
                $filePath = $uploadDir . $newFileName;
                $thumbnailPath = $thumbnailDir . $newFileName;
                
                if (move_uploaded_file($fileTmpName, $filePath)) {
                    // Create thumbnail
                    $thumbnailCreated = createThumbnail($filePath, $thumbnailPath);
                    
                    // Get title and description from form (if provided for this specific image)
                    $title = sanitize($_POST['titles'][$i] ?? pathinfo($fileName, PATHINFO_FILENAME));
                    $description = sanitize($_POST['descriptions'][$i] ?? '');
                    
                    try {
                        $sql = "INSERT INTO gallery (title, description, image_path, thumbnail_path, category, event_id, uploaded_by, is_featured) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                        $stmt = $db->prepare($sql);
                        $stmt->execute([
                            $title,
                            $description,
                            'uploads/gallery/' . $newFileName,
                            $thumbnailCreated ? 'uploads/gallery/thumbnails/' . $newFileName : null,
                            $category,
                            $eventId,
                            $_SESSION['admin_id'],
                            $isFeatured
                        ]);
                        
                        $successCount++;
                    } catch (PDOException $e) {
                        $errors[] = "Database error for '{$fileName}': " . $e->getMessage();
                        // Delete uploaded file if database insert fails
                        if (file_exists($filePath)) unlink($filePath);
                        if (file_exists($thumbnailPath)) unlink($thumbnailPath);
                    }
                } else {
                    $errors[] = "Failed to upload '{$fileName}'";
                }
            }
        }
        
        if ($successCount > 0) {
            logActivity($_SESSION['admin_id'], 'create', 'gallery', null, "Uploaded {$successCount} images");
            setFlashMessage('success', "{$successCount} image(s) uploaded successfully!");
            if (empty($errors)) {
                redirect('index.php');
            }
        }
    } else {
        $errors[] = "Please select at least one image to upload";
    }
}

// Get events for dropdown
$events = $db->query("SELECT event_id, title FROM events ORDER BY event_date DESC LIMIT 50")->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Upload Images';
include '../includes/admin-header.php';
?>

<div class="page-header">
    <h1>Upload Images</h1>
    <a href="index.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back</a>
</div>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger">
    <strong>Upload Issues:</strong>
    <ul><?php foreach ($errors as $error): ?><li><?php echo escapeHtml($error); ?></li><?php endforeach; ?></ul>
</div>
<?php endif; ?>

<?php if ($successCount > 0 && !empty($errors)): ?>
<div class="alert alert-warning">
    <strong>Partial Success:</strong> <?php echo $successCount; ?> image(s) uploaded, but some failed (see errors above)
</div>
<?php endif; ?>

<form method="POST" action="" enctype="multipart/form-data" id="uploadForm">
    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
    
    <div class="form-grid">
        <div class="form-main">
            <div class="form-card">
                <h3>Select Images</h3>
                
                <div class="form-group">
                    <label for="images" class="required">Choose Images</label>
                    <input type="file" id="images" name="images[]" class="form-control" accept="image/*" multiple required>
                    <small class="form-text">Select one or multiple images (JPG, PNG, GIF - Max 5MB each)</small>
                </div>
                
                <div id="preview-container" class="preview-container"></div>
            </div>
            
            <div class="form-card" id="details-container" style="display:none">
                <h3>Image Details</h3>
                <div id="image-details"></div>
            </div>
        </div>
        
        <div class="form-sidebar">
            <div class="form-card">
                <h3>Settings</h3>
                
                <div class="form-group">
                    <label for="category" class="required">Category</label>
                    <select id="category" name="category" class="form-control" required>
                        <option value="campus">Campus</option>
                        <option value="events">Events</option>
                        <option value="facilities">Facilities</option>
                        <option value="students">Students</option>
                        <option value="achievements">Achievements</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                
                <div class="form-group" id="event-selector" style="display:none">
                    <label for="event_id">Link to Event</label>
                    <select id="event_id" name="event_id" class="form-control">
                        <option value="">-- Select Event --</option>
                        <?php foreach ($events as $event): ?>
                            <option value="<?php echo $event['event_id']; ?>"><?php echo escapeHtml($event['title']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="is_featured" value="1">
                        <span>Mark as Featured</span>
                    </label>
                </div>
            </div>
            
            <div class="form-card">
                <div class="upload-info">
                    <h4>Upload Guidelines</h4>
                    <ul>
                        <li>Maximum file size: 5MB per image</li>
                        <li>Supported formats: JPG, PNG, GIF</li>
                        <li>Thumbnails are auto-generated</li>
                        <li>You can upload multiple images at once</li>
                    </ul>
                </div>
            </div>
            
            <div class="form-card">
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary btn-block">
                        <i class="fas fa-upload"></i> Upload Images
                    </button>
                </div>
            </div>
        </div>
    </div>
</form>
<style>
/* ═══════════════════════════════════════════════════════════════
   UPDATED STYLING FOR GALLERY UPLOAD.PHP
   (To match announcements/create.php styling)
   Replace the <style> section in your upload.php with this
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

/* ─── ALERTS ─── */
.alert {
    padding: 1rem 1.25rem;
    border-radius: 8px;
    margin-bottom: 1.5rem;
    border-left: 4px solid;
}

.alert ul {
    margin: 0.5rem 0 0;
    padding-left: 1.25rem;
}

.alert li {
    margin: 0.25rem 0;
}

.alert-danger {
    background: #f8d7da;
    color: #721c24;
    border-left-color: #dc3545;
}

.alert-warning {
    background: #fffbeb;
    color: #92400e;
    border-left-color: #fcd34d;
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
    min-height: 60px;
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

/* ─── IMAGE PREVIEWS ─── */
.preview-container {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
    gap: 1rem;
    margin-top: 1rem;
}

.preview-item {
    position: relative;
    border-radius: 8px;
    overflow: hidden;
    border: 2px solid var(--admin-border);
    background: #f4f6f8;
    transition: all 0.3s;
}

.preview-item:hover {
    border-color: var(--admin-primary);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    transform: translateY(-2px);
}

.preview-item img {
    width: 100%;
    height: 140px;
    object-fit: cover;
    display: block;
}

.preview-remove {
    position: absolute;
    top: 8px;
    right: 8px;
    width: 28px;
    height: 28px;
    background: rgba(220, 53, 69, 0.95);
    color: white;
    border: none;
    border-radius: 50%;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    line-height: 1;
    transition: all 0.3s;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
}

.preview-remove:hover {
    background: #c82333;
    transform: scale(1.15);
    box-shadow: 0 4px 12px rgba(220, 53, 69, 0.4);
}

/* ─── PER-IMAGE DETAIL FIELDS ─── */
.image-detail-item {
    padding: 1.5rem;
    border: 2px solid var(--admin-border);
    border-radius: 8px;
    margin-bottom: 1rem;
    background: #fafbfc;
    transition: all 0.3s;
}

.image-detail-item:hover {
    border-color: var(--admin-primary);
    box-shadow: 0 2px 8px rgba(0, 63, 135, 0.08);
}

.image-detail-item h4 {
    font-size: 0.9rem;
    font-weight: 600;
    color: var(--admin-text);
    margin: 0 0 1rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.image-detail-item h4::before {
    content: '';
    width: 4px;
    height: 16px;
    background: var(--admin-primary);
    border-radius: 2px;
}

/* ─── UPLOAD INFO / GUIDELINES ─── */
.upload-info h4 {
    font-size: 0.9rem;
    font-weight: 600;
    color: var(--admin-text);
    margin: 0 0 1rem;
}

.upload-info ul {
    margin: 0;
    padding-left: 1.25rem;
    font-size: 0.85rem;
    color: var(--admin-text-muted);
    line-height: 1.8;
}

.upload-info li {
    margin-bottom: 0.4rem;
}

/* ─── FORM ACTIONS ─── */
.form-actions {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
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

/* ─── RESPONSIVE ─── */
@media (max-width: 1024px) {
    .form-grid {
        grid-template-columns: 1fr;
    }
    
    .form-sidebar {
        position: static;
    }
    
    .preview-container {
        grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
    }
    
    .page-header {
        flex-direction: column;
        gap: 1rem;
    }
}

@media (max-width: 640px) {
    .form-card {
        padding: 1.5rem;
    }
    
    .btn-secondary {
        width: 100%;
        justify-content: center;
    }
    
    .preview-container {
        grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
    }
}
</style>
<script>
// Show event selector when category is 'events'
document.getElementById('category').addEventListener('change', function() {
    document.getElementById('event-selector').style.display = this.value === 'events' ? 'block' : 'none';
});

// Image preview functionality
document.getElementById('images').addEventListener('change', function(e) {
    const files = Array.from(e.target.files);
    const previewContainer = document.getElementById('preview-container');
    const detailsContainer = document.getElementById('details-container');
    const imageDetails = document.getElementById('image-details');
    
    previewContainer.innerHTML = '';
    imageDetails.innerHTML = '';
    
    if (files.length > 0) {
        detailsContainer.style.display = 'block';
        
        files.forEach((file, index) => {
            // Preview
            const reader = new FileReader();
            reader.onload = function(e) {
                const previewItem = document.createElement('div');
                previewItem.className = 'preview-item';
                previewItem.innerHTML = `
                    <img src="${e.target.result}" alt="Preview ${index + 1}">
                    <button type="button" class="preview-remove" onclick="removeImage(${index})">&times;</button>
                `;
                previewContainer.appendChild(previewItem);
            };
            reader.readAsDataURL(file);
            
            // Detail fields
            const detailItem = document.createElement('div');
            detailItem.className = 'image-detail-item';
            detailItem.innerHTML = `
                <h4>Image ${index + 1}: ${file.name}</h4>
                <div class="form-group">
                    <label>Title</label>
                    <input type="text" name="titles[]" class="form-control" value="${file.name.replace(/\.[^/.]+$/, '')}" placeholder="Image title">
                </div>
                <div class="form-group">
                    <label>Description (optional)</label>
                    <textarea name="descriptions[]" class="form-control" rows="2" placeholder="Image description"></textarea>
                </div>
            `;
            imageDetails.appendChild(detailItem);
        });
    } else {
        detailsContainer.style.display = 'none';
    }
});

function removeImage(index) {
    // This is a simplified version - in production you'd need to handle the file input array properly
    alert('To remove an image, please reselect your files without the unwanted image.');
}
</script>

<?php include '../includes/admin-footer.php'; ?>
