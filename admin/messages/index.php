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
        <div class="stat-content">
            <div class="stat-value"><?php echo $stats['total']; ?></div>
            <div class="stat-label">Total Messages</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-content">
            <div class="stat-value"><?php echo $stats['new']; ?></div>
            <div class="stat-label">New</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-content">
            <div class="stat-value"><?php echo $stats['read']; ?></div>
            <div class="stat-label">Read</div>
        </div>
    </div>
    <div class="stat-card">
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
/* Page Header */
.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
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

/* Statistics Grid */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.stat-card {
    background: white;
    border-radius: 12px;
    padding: 1.75rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
    border: 1px solid var(--admin-border);
    transition: all 0.3s;
}

.stat-card:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    transform: translateY(-2px);
}

.stat-content {
    display: flex;
    flex-direction: column;
}

.stat-value {
    font-size: 2rem;
    font-weight: 700;
    color: var(--admin-text);
    line-height: 1;
    margin-bottom: 0.25rem;
}

.stat-label {
    font-size: 0.9rem;
    color: var(--admin-text-muted);
    font-weight: 500;
}

/* Filter Bar */
.filter-bar {
    background: white;
    padding: 1.75rem;
    border-radius: 12px;
    margin-bottom: 1.5rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
    border: 1px solid var(--admin-border);
}

.filter-form {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.25rem;
    align-items: flex-end;
}

.filter-group {
    display: flex;
    flex-direction: column;
}

.filter-select {
    width: 100%;
    padding: 0.875rem 1rem;
    border: 2px solid var(--admin-border);
    border-radius: 8px;
    font-size: 0.95rem;
    background: white;
    cursor: pointer;
    transition: all 0.3s;
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23333' d='M6 9L1 4h10z'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 1rem center;
    background-size: 12px;
    padding-right: 2.5rem;
}

.filter-select:hover {
    border-color: #c5cdd8;
}

.filter-select:focus {
    outline: none;
    border-color: var(--admin-primary);
    box-shadow: 0 0 0 4px rgba(0, 63, 135, 0.08);
}

/* Search Box */
.search-box {
    position: relative;
    display: flex;
    gap: 0.5rem;
}

.search-input {
    flex: 1;
    padding: 0.875rem 1rem;
    border: 2px solid var(--admin-border);
    border-radius: 8px;
    font-size: 0.95rem;
    transition: all 0.3s;
}

.search-input:hover {
    border-color: #c5cdd8;
}

.search-input:focus {
    outline: none;
    border-color: var(--admin-primary);
    box-shadow: 0 0 0 4px rgba(0, 63, 135, 0.08);
}

.search-btn {
    padding: 0.875rem 1.25rem;
    background: var(--admin-primary);
    color: white;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-size: 1rem;
    transition: all 0.3s;
}

.search-btn:hover {
    background: var(--admin-primary-dark);
}

/* Table Card */
.table-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
    border: 1px solid var(--admin-border);
    overflow: hidden;
}

/* Data Table */
.data-table {
    width: 100%;
    border-collapse: collapse;
}

.data-table thead {
    background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
    border-bottom: 2px solid var(--admin-border);
}

.data-table th {
    padding: 1.25rem 1rem;
    text-align: left;
    font-weight: 700;
    font-size: 0.8rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--admin-text);
    white-space: nowrap;
}

.data-table td {
    padding: 1.25rem 1rem;
    border-bottom: 1px solid var(--admin-border);
    vertical-align: middle;
}

.data-table tbody tr {
    transition: all 0.3s;
}

.data-table tbody tr:hover {
    background: #f8f9fa;
}

/* Unread Row */
.unread-row {
    background: linear-gradient(135deg, #fff8f0 0%, #fffbf5 100%);
    border-left: 4px solid #ff9800;
}

.unread-row:hover {
    background: linear-gradient(135deg, #fff3e0 0%, #fff8f0 100%);
}

/* New Message Indicator */
.text-new {
    color: #ff9800;
    font-size: 0.6rem;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}

/* Subject Line */
.subject-line {
    font-weight: 600;
    color: var(--admin-text);
    margin-bottom: 0.25rem;
    font-size: 0.95rem;
}

/* Text Utilities */
.text-muted {
    color: var(--admin-text-muted);
    font-size: 0.85rem;
}

.text-center {
    text-align: center;
    padding: 4rem 2rem !important;
}

.empty-state {
    padding: 4rem 2rem;
    text-align: center;
}

.empty-state p {
    color: var(--admin-text-muted);
    font-size: 1.1rem;
    margin: 0;
}

/* Badges */
.badge {
    display: inline-flex;
    align-items: center;
    padding: 0.35rem 0.75rem;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.02em;
}

/* Action Buttons */
.action-buttons {
    display: flex;
    gap: 0.5rem;
    justify-content: center;
}

.btn-action {
    width: 36px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
    text-decoration: none;
    transition: all 0.3s;
    border: 2px solid transparent;
}

.btn-view {
    background: #f8f9fa;
    color: var(--admin-text);
}

.btn-view:hover {
    background: var(--admin-primary);
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0, 63, 135, 0.25);
}

.btn-reply {
    background: #f8f9fa;
    color: var(--admin-text);
}

.btn-reply:hover {
    background: #4caf50;
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(76, 175, 80, 0.25);
}

/* Pagination */
.pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 0.5rem;
    padding: 1.75rem;
    border-top: 2px solid var(--admin-border);
    background: #fafbfc;
}

.page-link {
    min-width: 40px;
    height: 40px;
    padding: 0 0.875rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
    background: white;
    border: 2px solid var(--admin-border);
    color: var(--admin-text);
    text-decoration: none;
    font-weight: 600;
    font-size: 0.9rem;
    transition: all 0.3s;
}

.page-link:hover {
    background: var(--admin-primary);
    border-color: var(--admin-primary);
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0, 63, 135, 0.2);
}

.page-link.active {
    background: var(--admin-primary);
    border-color: var(--admin-primary);
    color: white;
    box-shadow: 0 2px 8px rgba(0, 63, 135, 0.25);
}

/* Responsive */
@media (max-width: 1024px) {
    .filter-form {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 768px) {
    .page-header {
        flex-direction: column;
        gap: 1rem;
        align-items: flex-start;
    }
    
    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    .data-table {
        font-size: 0.85rem;
    }
    
    .data-table th,
    .data-table td {
        padding: 0.875rem 0.5rem;
    }
    
    .pagination {
        flex-wrap: wrap;
        padding: 1rem;
    }
}
</style>

<?php include '../includes/admin-footer.php'; ?>
