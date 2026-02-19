<?php
require_once '../includes/auth-check.php';
$db = getDB();
$errors = [];

// Get announcement ID from URL
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    setFlashMessage('error', 'Invalid announcement ID');
    redirect('index.php');
}

// Fetch announcement details
try {
    $stmt = $db->prepare("SELECT * FROM announcements WHERE announcement_id = ?");
    $stmt->execute([$id]);
    $announcement = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$announcement) {
        setFlashMessage('error', 'Announcement not found');
        redirect('index.php');
    }
} catch (PDOException $e) {
    setFlashMessage('error', 'Database error: ' . $e->getMessage());
    redirect('index.php');
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = sanitize($_POST['title'] ?? '');
    $content = sanitize($_POST['content'] ?? '');
    $type = sanitize($_POST['type'] ?? 'general');
    $priority = sanitize($_POST['priority'] ?? 'medium');
    $targetAudience = sanitize($_POST['target_audience'] ?? 'all');
    $startDate = $_POST['start_date'] ?? '';
    $endDate = $_POST['end_date'] ?? '';
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    
    if (empty($title)) $errors[] = "Title is required";
    if (empty($content)) $errors[] = "Content is required";
    if (empty($startDate)) $errors[] = "Start date is required";
    if (empty($endDate)) $errors[] = "End date is required";
    
    if (empty($errors)) {
        try {
            $sql = "UPDATE announcements SET title = ?, content = ?, type = ?, priority = ?, target_audience = ?, start_date = ?, end_date = ?, is_active = ? WHERE announcement_id = ?";
            $stmt = $db->prepare($sql);
            $result = $stmt->execute([$title, $content, $type, $priority, $targetAudience, $startDate, $endDate, $isActive, $id]);
            
            if ($result) {
                logActivity($_SESSION['admin_id'], 'update', 'announcements', $id, "Updated announcement: {$title}");
                setFlashMessage('success', 'Announcement updated successfully!');
                redirect('index.php');
            }
        } catch (PDOException $e) {
            $errors[] = "Error: " . $e->getMessage();
        }
    }
    
    // Keep posted values for form repopulation
    $announcement = array_merge($announcement, $_POST);
}

$pageTitle = 'Edit Announcement';
include '../includes/admin-header.php';
?>

<div class="page-header">
    <h1>Edit Announcement</h1>
    <div class="header-actions">
        <a href="delete.php?id=<?php echo $id; ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this announcement?')">
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

<form method="POST" action="">
    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
    
    <div class="form-grid">
        <div class="form-main">
            <div class="form-card">
                <h3>Announcement Details</h3>
                
                <div class="form-group">
                    <label for="title" class="required">Title</label>
                    <input type="text" id="title" name="title" class="form-control" required maxlength="255" value="<?php echo escapeHtml($announcement['title']); ?>">
                </div>
                
                <div class="form-group">
                    <label for="content" class="required">Content</label>
                    <textarea id="content" name="content" class="form-control" rows="6" required><?php echo escapeHtml($announcement['content']); ?></textarea>
                    <small class="form-text">Plain text announcement message</small>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="start_date" class="required">Start Date</label>
                        <input type="date" id="start_date" name="start_date" class="form-control" required value="<?php echo date('Y-m-d', strtotime($announcement['start_date'])); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="end_date" class="required">End Date</label>
                        <input type="date" id="end_date" name="end_date" class="form-control" required value="<?php echo date('Y-m-d', strtotime($announcement['end_date'])); ?>">
                    </div>
                </div>
            </div>
        </div>
        
        <div class="form-sidebar">
            <div class="form-card">
                <h3>Settings</h3>
                
                <div class="form-group">
                    <label>Type</label>
                    <select name="type" class="form-control">
                        <option value="general" <?php echo $announcement['type'] === 'general' ? 'selected' : ''; ?>>General</option>
                        <option value="urgent" <?php echo $announcement['type'] === 'urgent' ? 'selected' : ''; ?>>Urgent</option>
                        <option value="academic" <?php echo $announcement['type'] === 'academic' ? 'selected' : ''; ?>>Academic</option>
                        <option value="event" <?php echo $announcement['type'] === 'event' ? 'selected' : ''; ?>>Event</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Priority</label>
                    <select name="priority" class="form-control">
                        <option value="low" <?php echo $announcement['priority'] === 'low' ? 'selected' : ''; ?>>Low</option>
                        <option value="medium" <?php echo $announcement['priority'] === 'medium' ? 'selected' : ''; ?>>Medium</option>
                        <option value="high" <?php echo $announcement['priority'] === 'high' ? 'selected' : ''; ?>>High</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Target Audience</label>
                    <select name="target_audience" class="form-control">
                        <option value="all" <?php echo $announcement['target_audience'] === 'all' ? 'selected' : ''; ?>>All</option>
                        <option value="students" <?php echo $announcement['target_audience'] === 'students' ? 'selected' : ''; ?>>Students</option>
                        <option value="faculty" <?php echo $announcement['target_audience'] === 'faculty' ? 'selected' : ''; ?>>Faculty</option>
                        <option value="parents" <?php echo $announcement['target_audience'] === 'parents' ? 'selected' : ''; ?>>Parents</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="is_active" value="1" <?php echo $announcement['is_active'] ? 'checked' : ''; ?>>
                        <span>Active</span>
                    </label>
                </div>
            </div>
            
            <div class="form-card">
                <h3>Meta Information</h3>
                <div class="meta-info">
                    <div class="meta-item">
                        <span class="meta-label">Created:</span>
                        <span class="meta-value"><?php echo date('M d, Y g:i A', strtotime($announcement['created_at'])); ?></span>
                    </div>
                    <div class="meta-item">
                        <span class="meta-label">Last Updated:</span>
                        <span class="meta-value"><?php echo date('M d, Y g:i A', strtotime($announcement['updated_at'])); ?></span>
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

/* Form Grid Layout */
.form-grid {
    display: grid;
    grid-template-columns: 1fr 380px;
    gap: 1.5rem;
    align-items: start;
}

/* Form Row */
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
input[type="date"].form-control {
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
