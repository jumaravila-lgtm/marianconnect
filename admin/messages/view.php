<?php
require_once '../includes/auth-check.php';
$db = getDB();

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
    
    // Mark as read if it's new
    if ($message['status'] === 'new') {
        $updateStmt = $db->prepare("UPDATE contact_messages SET status = 'read' WHERE message_id = ?");
        $updateStmt->execute([$id]);
        $message['status'] = 'read';
    }
} catch (PDOException $e) {
    setFlashMessage('error', 'Database error');
    redirect('index.php');
}

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action === 'archive') {
        $stmt = $db->prepare("UPDATE contact_messages SET status = 'archived' WHERE message_id = ?");
        $stmt->execute([$id]);
        logActivity($_SESSION['admin_id'], 'update', 'contact_messages', $id, "Archived message from {$message['full_name']}");
        setFlashMessage('success', 'Message archived!');
        redirect('index.php');
    }
}

$pageTitle = 'View Message';
include '../includes/admin-header.php';
?>

<div class="page-header">
    <h1>✉️ View Message</h1>
    <div class="header-actions">
        <a href="reply.php?id=<?php echo $id; ?>" class="btn btn-primary">
            <i class="fas fa-reply"></i> Reply
        </a>
        <a href="index.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back</a>
    </div>
</div>

<div class="message-container">
    <div class="message-card">
        <div class="message-header">
            <div class="sender-info">
                <div class="sender-avatar">
                    <i class="fas fa-user"></i>
                </div>
                <div class="sender-details">
                    <h2 class="sender-name"><?php echo escapeHtml($message['full_name']); ?></h2>
                    <div class="sender-meta">
                        <span><i class="fas fa-envelope"></i> <?php echo escapeHtml($message['email']); ?></span>
                        <?php if ($message['phone']): ?>
                            <span><i class="fas fa-phone"></i> <?php echo escapeHtml($message['phone']); ?></span>
                        <?php endif; ?>
                        <span><i class="fas fa-calendar"></i> <?php echo date('M d, Y g:i A', strtotime($message['created_at'])); ?></span>
                    </div>
                </div>
            </div>
            
            <div class="message-status">
                <?php
                $statusBadges = [
                    'new' => ['New', '#ff9800'],
                    'read' => ['Read', '#9c27b0'],
                    'replied' => ['Replied', '#4caf50'],
                    'archived' => ['Archived', '#757575']
                ];
                $badge = $statusBadges[$message['status']];
                ?>
                <span class="badge-large" style="background:<?php echo $badge[1]; ?>"><?php echo $badge[0]; ?></span>
            </div>
        </div>
        
        <div class="message-subject">
            <h3><?php echo escapeHtml($message['subject']); ?></h3>
        </div>
        
        <div class="message-body">
            <?php echo nl2br(escapeHtml($message['message'])); ?>
        </div>
        
        <div class="message-footer">
            <div class="message-meta">
                <small><i class="fas fa-globe"></i> IP: <?php echo escapeHtml($message['ip_address'] ?? 'N/A'); ?></small>
            </div>
            
            <div class="message-actions">
                <a href="reply.php?id=<?php echo $id; ?>" class="btn btn-primary">
                    <i class="fas fa-reply"></i> Reply to Sender
                </a>
                
                <?php if ($message['status'] !== 'archived'): ?>
                <form method="POST" style="display:inline">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" name="action" value="archive">
                    <button type="submit" class="btn btn-secondary" onclick="return confirm('Archive this message?')">
                        <i class="fas fa-archive"></i> Archive
                    </button>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <?php if ($message['replied_at']): ?>
    <div class="reply-info-card">
        <i class="fas fa-check-circle"></i>
        <div>
            <strong>This message was replied to</strong>
            <p>Replied on <?php echo date('M d, Y g:i A', strtotime($message['replied_at'])); ?></p>
        </div>
    </div>
    <?php endif; ?>
</div>

<style>
.page-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:2rem}
.header-actions{display:flex;gap:0.75rem}
.message-container{max-width:900px;margin:0 auto}
.message-card{background:white;border-radius:12px;box-shadow:var(--admin-shadow);overflow:hidden}
.message-header{padding:2rem;background:#f8f9fa;border-bottom:1px solid var(--admin-border);display:flex;justify-content:space-between;align-items:flex-start}
.sender-info{display:flex;gap:1.5rem}
.sender-avatar{width:60px;height:60px;background:var(--admin-primary);border-radius:50%;display:flex;align-items:center;justify-content:center;color:white;font-size:1.5rem}
.sender-details{flex:1}
.sender-name{font-size:1.5rem;font-weight:600;margin:0 0 0.5rem 0;color:var(--admin-text)}
.sender-meta{display:flex;flex-wrap:wrap;gap:1.5rem;font-size:0.9rem;color:var(--admin-text-muted)}
.sender-meta span{display:flex;align-items:center;gap:0.5rem}
.message-status{text-align:right}
.badge-large{display:inline-block;padding:0.5rem 1.25rem;border-radius:20px;font-size:0.9rem;font-weight:600;color:white}
.message-subject{padding:2rem;border-bottom:1px solid var(--admin-border)}
.message-subject h3{font-size:1.25rem;font-weight:600;margin:0;color:var(--admin-text)}
.message-body{padding:2rem;font-size:1rem;line-height:1.8;color:var(--admin-text);white-space:pre-wrap}
.message-footer{padding:1.5rem 2rem;background:#f8f9fa;border-top:1px solid var(--admin-border);display:flex;justify-content:space-between;align-items:center}
.message-meta{font-size:0.85rem;color:var(--admin-text-muted)}
.message-actions{display:flex;gap:0.75rem}
.reply-info-card{margin-top:1.5rem;background:#e8f5e9;border-left:4px solid #4caf50;padding:1.5rem;border-radius:8px;display:flex;gap:1rem;align-items:center}
.reply-info-card i{font-size:2rem;color:#4caf50}
.reply-info-card strong{display:block;margin-bottom:0.25rem}
.reply-info-card p{margin:0;font-size:0.9rem;color:var(--admin-text-muted)}
.btn{padding:0.6rem 1.25rem;border:none;border-radius:8px;font-weight:500;cursor:pointer;display:inline-flex;align-items:center;gap:0.5rem;text-decoration:none;transition:all 0.2s}
.btn-primary{background:var(--admin-primary);color:white}
.btn-primary:hover{background:#004a99;transform:translateY(-2px)}
.btn-secondary{background:#6c757d;color:white}
.btn-secondary:hover{background:#5a6268}
@media(max-width:768px){
    .message-header{flex-direction:column;gap:1rem}
    .sender-info{flex-direction:column}
    .message-footer{flex-direction:column;gap:1rem;align-items:flex-start}
}
</style>

<?php include '../includes/admin-footer.php'; ?>
