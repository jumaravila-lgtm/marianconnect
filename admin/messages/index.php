<?php
require_once '../includes/auth-check.php';
$db = getDB();

$status = $_GET['status'] ?? 'all';
$search = $_GET['search'] ?? '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 25;
$offset = ($page - 1) * $perPage;

$where = [];
$params = [];

if ($status !== 'all') {
    $where[] = "status = ?";
    $params[] = $status;
}

if (!empty($search)) {
    $where[] = "(full_name LIKE ? OR email LIKE ? OR subject LIKE ?)";
    $searchTerm = "%{$search}%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

$whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

$countSql = "SELECT COUNT(*) FROM contact_messages {$whereClause}";
$countStmt = $db->prepare($countSql);
$countStmt->execute($params);
$totalRecords = $countStmt->fetchColumn();
$totalPages = ceil($totalRecords / $perPage);

$sql = "SELECT * FROM contact_messages {$whereClause} ORDER BY created_at DESC LIMIT {$perPage} OFFSET {$offset}";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stats = [
    'total' => $db->query("SELECT COUNT(*) FROM contact_messages")->fetchColumn(),
    'new' => $db->query("SELECT COUNT(*) FROM contact_messages WHERE status = 'new'")->fetchColumn(),
    'read' => $db->query("SELECT COUNT(*) FROM contact_messages WHERE status = 'read'")->fetchColumn(),
    'replied' => $db->query("SELECT COUNT(*) FROM contact_messages WHERE status = 'replied'")->fetchColumn(),
];

$pageTitle = 'Messages';
include '../includes/admin-header.php';
?>

<div class="page-header">
    <h1>Contact Messages</h1>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon" style="background:#e3f2fd;color:#1976d2"><i class="fas fa-envelope"></i></div>
        <div class="stat-content">
            <div class="stat-value"><?php echo $stats['total']; ?></div>
            <div class="stat-label">Total Messages</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#fff3e0;color:#f57c00"><i class="fas fa-envelope-open"></i></div>
        <div class="stat-content">
            <div class="stat-value"><?php echo $stats['new']; ?></div>
            <div class="stat-label">New</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#f3e5f5;color:#7b1fa2"><i class="fas fa-eye"></i></div>
        <div class="stat-content">
            <div class="stat-value"><?php echo $stats['read']; ?></div>
            <div class="stat-label">Read</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#e8f5e9;color:#388e3c"><i class="fas fa-check-circle"></i></div>
        <div class="stat-content">
            <div class="stat-value"><?php echo $stats['replied']; ?></div>
            <div class="stat-label">Replied</div>
        </div>
    </div>
</div>

<div class="filter-bar">
    <form method="GET" action="" class="filter-form">
        <div class="filter-group">
            <select name="status" class="filter-select" onchange="this.form.submit()">
                <option value="all" <?php echo $status === 'all' ? 'selected' : ''; ?>>All Messages</option>
                <option value="new" <?php echo $status === 'new' ? 'selected' : ''; ?>>New</option>
                <option value="read" <?php echo $status === 'read' ? 'selected' : ''; ?>>Read</option>
                <option value="replied" <?php echo $status === 'replied' ? 'selected' : ''; ?>>Replied</option>
                <option value="archived" <?php echo $status === 'archived' ? 'selected' : ''; ?>>Archived</option>
            </select>
        </div>
        
        <div class="search-box">
            <input type="text" name="search" placeholder="Search messages..." value="<?php echo escapeHtml($search); ?>" class="search-input">
            <button type="submit" class="search-btn"><i class="fas fa-search"></i></button>
        </div>
    </form>
</div>

<div class="table-card">
    <table class="data-table">
        <thead>
            <tr>
                <th style="width:50px"></th>
                <th>Name</th>
                <th>Email</th>
                <th>Subject</th>
                <th>Date</th>
                <th style="width:100px">Status</th>
                <th style="width:120px">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($messages)): ?>
            <tr>
                <td colspan="7" class="text-center">No messages found</td>
            </tr>
            <?php else: ?>
                <?php foreach ($messages as $msg): ?>
                <tr class="<?php echo $msg['status'] === 'new' ? 'unread-row' : ''; ?>">
                    <td>
                        <?php if ($msg['status'] === 'new'): ?>
                            <i class="fas fa-circle text-new" title="New message"></i>
                        <?php endif; ?>
                    </td>
                    <td>
                        <strong><?php echo escapeHtml($msg['full_name']); ?></strong>
                        <?php if ($msg['phone']): ?>
                            <br><small class="text-muted"><?php echo escapeHtml($msg['phone']); ?></small>
                        <?php endif; ?>
                    </td>
                    <td><?php echo escapeHtml($msg['email']); ?></td>
                    <td>
                        <div class="subject-line"><?php echo escapeHtml($msg['subject']); ?></div>
                        <small class="text-muted"><?php echo escapeHtml(substr($msg['message'], 0, 60)); ?>...</small>
                    </td>
                    <td><?php echo date('M d, Y g:i A', strtotime($msg['created_at'])); ?></td>
                    <td>
                        <?php
                        $statusBadges = [
                            'new' => ['New', '#ff9800'],
                            'read' => ['Read', '#9c27b0'],
                            'replied' => ['Replied', '#4caf50'],
                            'archived' => ['Archived', '#757575']
                        ];
                        $badge = $statusBadges[$msg['status']];
                        ?>
                        <span class="badge" style="background:<?php echo $badge[1]; ?>;color:white"><?php echo $badge[0]; ?></span>
                    </td>
                    <td>
                        <div class="action-buttons">
                            <a href="view.php?id=<?php echo $msg['message_id']; ?>" class="btn-action btn-view" title="View">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="reply.php?id=<?php echo $msg['message_id']; ?>" class="btn-action btn-reply" title="Reply">
                                <i class="fas fa-reply"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php if ($totalPages > 1): ?>
<div class="pagination">
    <?php if ($page > 1): ?>
        <a href="?page=<?php echo $page-1; ?>&status=<?php echo $status; ?>&search=<?php echo urlencode($search); ?>" class="page-link">&laquo; Previous</a>
    <?php endif; ?>
    
    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
        <a href="?page=<?php echo $i; ?>&status=<?php echo $status; ?>&search=<?php echo urlencode($search); ?>" class="page-link <?php echo $i === $page ? 'active' : ''; ?>">
            <?php echo $i; ?>
        </a>
    <?php endfor; ?>
    
    <?php if ($page < $totalPages): ?>
        <a href="?page=<?php echo $page+1; ?>&status=<?php echo $status; ?>&search=<?php echo urlencode($search); ?>" class="page-link">Next &raquo;</a>
    <?php endif; ?>
</div>
<?php endif; ?>

<style>
.page-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:2rem}
.stats-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:1.5rem;margin-bottom:2rem}
.stat-card{background:white;border-radius:12px;padding:1.5rem;box-shadow:var(--admin-shadow);display:flex;gap:1rem;align-items:center}
.stat-icon{width:60px;height:60px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.5rem}
.stat-value{font-size:2rem;font-weight:700;color:var(--admin-text)}
.stat-label{font-size:0.9rem;color:var(--admin-text-muted)}
.filter-bar{background:white;border-radius:12px;padding:1.5rem;margin-bottom:2rem;box-shadow:var(--admin-shadow)}
.filter-form{display:flex;gap:1rem;align-items:center}
.filter-select{padding:0.75rem 1rem;border:2px solid var(--admin-border);border-radius:8px;font-size:0.95rem}
.search-box{display:flex;gap:0.5rem;flex:1;max-width:400px}
.search-input{flex:1;padding:0.75rem 1rem;border:2px solid var(--admin-border);border-radius:8px}
.search-btn{padding:0.75rem 1.5rem;background:var(--admin-primary);color:white;border:none;border-radius:8px;cursor:pointer}
.table-card{background:white;border-radius:12px;box-shadow:var(--admin-shadow);overflow:hidden}
.data-table{width:100%;border-collapse:collapse}
.data-table th{background:#f8f9fa;padding:1rem;text-align:left;font-weight:600;border-bottom:2px solid var(--admin-border)}
.data-table td{padding:1rem;border-bottom:1px solid var(--admin-border)}
.unread-row{background:#fff3e0}
.text-new{color:#ff9800;font-size:0.6rem}
.subject-line{font-weight:500;margin-bottom:0.25rem}
.text-muted{color:var(--admin-text-muted);font-size:0.85rem}
.badge{display:inline-block;padding:0.35rem 0.75rem;border-radius:6px;font-size:0.85rem;font-weight:500}
.action-buttons{display:flex;gap:0.5rem}
.btn-action{width:36px;height:36px;border-radius:8px;display:flex;align-items:center;justify-content:center;border:none;cursor:pointer;transition:all 0.2s;text-decoration:none}
.btn-view{background:#e3f2fd;color:#1976d2}
.btn-view:hover{background:#1976d2;color:white}
.btn-reply{background:#e8f5e9;color:#4caf50}
.btn-reply:hover{background:#4caf50;color:white}
.pagination{display:flex;gap:0.5rem;justify-content:center;margin-top:2rem}
.page-link{padding:0.5rem 1rem;border:2px solid var(--admin-border);border-radius:8px;text-decoration:none;color:var(--admin-text)}
.page-link.active{background:var(--admin-primary);color:white;border-color:var(--admin-primary)}
.text-center{text-align:center;padding:3rem!important;color:var(--admin-text-muted)}
</style>

<?php include '../includes/admin-footer.php'; ?>
