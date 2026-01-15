<?php
require_once '../includes/auth-check.php';
$db = getDB();
$errors = [];
$success = false;

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    setFlashMessage('error', 'Invalid message ID');
    redirect('index.php');
}

try {
    $stmt = $db->prepare("SELECT * FROM contact_messages WHERE message_id = ?");
    $stmt->execute([$id]);
    $message = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$message) {
        setFlashMessage('error', 'Message not found');
        redirect('index.php');
    }
} catch (PDOException $e) {
    setFlashMessage('error', 'Database error');
    redirect('index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $replyMessage = sanitize($_POST['reply_message'] ?? '');
    
    if (empty($replyMessage)) {
        $errors[] = "Reply message is required";
    }
    
    if (empty($errors)) {
        // In a real application, you would send an email here
        // For now, we'll just update the status
        
        // TODO: Implement email sending using PHPMailer or similar
        // mail($message['email'], "Re: " . $message['subject'], $replyMessage);
        
        try {
            $stmt = $db->prepare("UPDATE contact_messages SET status = 'replied', replied_at = NOW(), replied_by = ? WHERE message_id = ?");
            $stmt->execute([$_SESSION['admin_id'], $id]);
            
            logActivity($_SESSION['admin_id'], 'update', 'contact_messages', $id, "Replied to message from {$message['full_name']}");
            
            $success = true;
            setFlashMessage('success', 'Reply sent successfully! (Note: Email functionality needs to be configured)');
            
            // Uncomment this to redirect after success
            // redirect('view.php?id=' . $id);
        } catch (PDOException $e) {
            $errors[] = "Error: " . $e->getMessage();
        }
    }
}

$pageTitle = 'Reply to Message';
include '../includes/admin-header.php';
?>

<div class="page-header">
    <h1>Reply to Message</h1>
    <div class="header-actions">
        <a href="view.php?id=<?php echo $id; ?>" class="btn btn-secondary"><i class="fas fa-eye"></i> View</a>
        <a href="index.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back</a>
    </div>
</div>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger">
    <ul><?php foreach ($errors as $error): ?><li><?php echo escapeHtml($error); ?></li><?php endforeach; ?></ul>
</div>
<?php endif; ?>

<?php if ($success): ?>
<div class="alert alert-success">
    <i class="fas fa-check-circle"></i>
    <div>
        <strong>Reply Sent!</strong>
        <p>The message status has been updated to "Replied". In production, this would also send an email to <?php echo escapeHtml($message['email']); ?></p>
        <a href="view.php?id=<?php echo $id; ?>" class="btn btn-sm btn-primary" style="margin-top:0.5rem">View Message</a>
    </div>
</div>
<?php endif; ?>

<div class="reply-container">
    <div class="original-message-card">
        <h3>Original Message</h3>
        <div class="original-header">
            <div class="original-info">
                <strong>From:</strong> <?php echo escapeHtml($message['full_name']); ?> (<?php echo escapeHtml($message['email']); ?>)
            </div>
            <div class="original-info">
                <strong>Date:</strong> <?php echo date('M d, Y g:i A', strtotime($message['created_at'])); ?>
            </div>
            <div class="original-info">
                <strong>Subject:</strong> <?php echo escapeHtml($message['subject']); ?>
            </div>
        </div>
        <div class="original-body">
            <?php echo nl2br(escapeHtml($message['message'])); ?>
        </div>
    </div>
    
    <form method="POST" action="" class="reply-form">
        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
        
        <div class="form-card">
            <h3>Your Reply</h3>
            
            <div class="reply-to-info">
                <i class="fas fa-info-circle"></i>
                <span>Replying to: <strong><?php echo escapeHtml($message['email']); ?></strong></span>
            </div>
            
            <div class="form-group">
                <label for="reply_message" class="required">Message</label>
                <textarea id="reply_message" name="reply_message" class="form-control" rows="10" required placeholder="Type your reply here..."><?php echo escapeHtml($_POST['reply_message'] ?? ''); ?></textarea>
            </div>
            
            <div class="email-notice">
                <i class="fas fa-exclamation-triangle"></i>
                <div>
                    <strong>Note:</strong> Email functionality is not yet configured. This will mark the message as "Replied" but won't actually send an email. 
                    To enable email sending, configure PHPMailer or SMTP settings in your config file.
                </div>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary btn-large">
                    <i class="fas fa-paper-plane"></i> Send Reply
                </button>
                <a href="view.php?id=<?php echo $id; ?>" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancel
                </a>
            </div>
        </div>
    </form>
</div>

<style>
.page-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:2rem}
.header-actions{display:flex;gap:0.75rem}
.reply-container{max-width:900px;margin:0 auto}
.original-message-card{background:#f8f9fa;border:2px solid var(--admin-border);border-radius:12px;padding:1.5rem;margin-bottom:2rem}
.original-message-card h3{font-size:1.1rem;font-weight:600;margin-bottom:1rem;padding-bottom:0.75rem;border-bottom:2px solid var(--admin-border)}
.original-header{display:flex;flex-direction:column;gap:0.5rem;margin-bottom:1rem;padding:1rem;background:white;border-radius:8px}
.original-info{font-size:0.9rem}
.original-info strong{color:var(--admin-text);margin-right:0.5rem}
.original-body{padding:1rem;background:white;border-radius:8px;line-height:1.6;color:var(--admin-text);white-space:pre-wrap}
.reply-form{background:white;border-radius:12px;box-shadow:var(--admin-shadow)}
.form-card{padding:2rem}
.form-card h3{font-size:1.1rem;font-weight:600;margin-bottom:1.5rem;padding-bottom:0.75rem;border-bottom:2px solid var(--admin-border)}
.reply-to-info{background:#e3f2fd;padding:1rem;border-radius:8px;margin-bottom:1.5rem;display:flex;align-items:center;gap:0.75rem;color:var(--admin-text)}
.reply-to-info i{color:#1976d2;font-size:1.25rem}
.form-group{margin-bottom:1.5rem}
.form-group label{display:block;margin-bottom:0.5rem;font-weight:500}
.form-group label.required::after{content:' *';color:var(--admin-danger)}
.form-control{width:100%;padding:0.75rem 1rem;border:2px solid var(--admin-border);border-radius:8px;font-family:inherit}
.form-control:focus{outline:none;border-color:var(--admin-primary);box-shadow:0 0 0 3px rgba(0,63,135,0.1)}
.email-notice{background:#fff3e0;border-left:4px solid #ff9800;padding:1rem 1.5rem;border-radius:8px;margin-bottom:1.5rem;display:flex;gap:1rem}
.email-notice i{color:#ff9800;font-size:1.25rem;margin-top:0.25rem}
.form-actions{display:flex;gap:1rem}
.btn{padding:0.6rem 1.25rem;border:none;border-radius:8px;font-weight:500;cursor:pointer;display:inline-flex;align-items:center;gap:0.5rem;text-decoration:none;transition:all 0.2s}
.btn-large{padding:0.85rem 2rem;font-size:1rem}
.btn-primary{background:var(--admin-primary);color:white}
.btn-primary:hover{background:#004a99;transform:translateY(-2px)}
.btn-secondary{background:#6c757d;color:white}
.btn-secondary:hover{background:#5a6268}
.btn-sm{padding:0.5rem 1rem;font-size:0.9rem}
.alert{padding:1rem 1.5rem;border-radius:8px;margin-bottom:1.5rem;display:flex;gap:1rem}
.alert-danger{background:#ffebee;border:1px solid #ef5350;color:#c62828}
.alert-danger ul{margin:0;padding-left:1.5rem}
.alert-success{background:#e8f5e9;border:1px solid #4caf50;color:#2e7d32;align-items:flex-start}
.alert-success i{font-size:1.5rem;margin-top:0.25rem}
.alert-success p{margin:0.5rem 0 0;font-size:0.9rem}
@media(max-width:768px){
    .original-header{gap:0.75rem}
    .form-actions{flex-direction:column}
    .btn{width:100%;justify-content:center}
}
</style>

<?php include '../includes/admin-footer.php'; ?>
