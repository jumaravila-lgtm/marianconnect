<?php
require_once '../includes/auth-check.php';

$db = getDB();
$type = $_GET['type'] ?? 'all';
$priority = $_GET['priority'] ?? 'all';
$search = $_GET['search'] ?? '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 25;
$offset = ($page - 1) * $perPage;

$where = [];
$params = [];

if ($type !== 'all') {
    $where[] = "type = ?";
    $params[] = $type;
}

if ($priority !== 'all') {
    $where[] = "priority = ?";
    $params[] = $priority;
}

if (!empty($search)) {
    $where[] = "(title LIKE ? OR content LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}

$whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

$countSql = "SELECT COUNT(*) as total FROM announcements {$whereClause}";
$stmt = $db->prepare($countSql);
$stmt->execute($params);
$totalItems = $stmt->fetch()['total'];
$totalPages = ceil($totalItems / $perPage);

$sql = "SELECT a.*, u.full_name as creator_name FROM announcements a JOIN admin_users u ON a.created_by = u.admin_id {$whereClause} ORDER BY a.priority DESC, a.start_date DESC LIMIT {$perPage} OFFSET {$offset}";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$announcementsList = $stmt->fetchAll();

$pageTitle = 'Announcements Management';
include '../includes/admin-header.php';
?>

<div class="page-header">
    <h1>Announcements Management</h1>
    <div class="page-actions">
        <a href="create.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Add Announcement
        </a>
    </div>
</div>

<div class="filters-card">
    <form method="GET" action="" class="filters-form">
        <div class="filter-group">
            <label>Type:</label>
            <select name="type" onchange="this.form.submit()">
                <option value="all" <?php echo $type === 'all' ? 'selected' : ''; ?>>All Types</option>
                <option value="general" <?php echo $type === 'general' ? 'selected' : ''; ?>>General</option>
                <option value="urgent" <?php echo $type === 'urgent' ? 'selected' : ''; ?>>Urgent</option>
                <option value="academic" <?php echo $type === 'academic' ? 'selected' : ''; ?>>Academic</option>
                <option value="event" <?php echo $type === 'event' ? 'selected' : ''; ?>>Event</option>
            </select>
        </div>

        <div class="filter-group">
            <label>Priority:</label>
            <select name="priority" onchange="this.form.submit()">
                <option value="all" <?php echo $priority === 'all' ? 'selected' : ''; ?>>All Priority</option>
                <option value="high" <?php echo $priority === 'high' ? 'selected' : ''; ?>>High</option>
                <option value="medium" <?php echo $priority === 'medium' ? 'selected' : ''; ?>>Medium</option>
                <option value="low" <?php echo $priority === 'low' ? 'selected' : ''; ?>>Low</option>
            </select>
        </div>

        <div class="filter-group">
            <label>Search:</label>
            <div class="search-box">
                <input type="text" name="search" placeholder="Search..." value="<?php echo escapeHtml($search); ?>">
                <button type="submit"><i class="fas fa-search"></i></button>
            </div>
        </div>

        <?php if ($type !== 'all' || $priority !== 'all' || !empty($search)): ?>
        <a href="index.php" class="btn-clear-filters">Clear</a>
        <?php endif; ?>
    </form>
</div>

<div class="results-summary">
    <p>Showing <?php echo count($announcementsList); ?> of <?php echo number_format($totalItems); ?> announcements</p>
</div>

<div class="content-card">
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Type</th>
                    <th>Priority</th>
                    <th>Target</th>
                    <th>Duration</th>
                    <th>Status</th>
                    <th>Creator</th>
                    <th style="width: 150px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($announcementsList)): ?>
                <tr>
                    <td colspan="8" class="text-center">
                        <div class="empty-state">
                            <i class="fas fa-bullhorn"></i>
                            <p>No announcements found</p>
                            <?php if (empty($search) && $type === 'all' && $priority === 'all'): ?>

                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php else: ?>
                    <?php foreach ($announcementsList as $ann): ?>
                    <?php 
                        $now = date('Y-m-d');
                        $isActive = ($ann['is_active'] && $now >= date('Y-m-d', strtotime($ann['start_date'])) && $now <= date('Y-m-d', strtotime($ann['end_date'])));
                    ?>
                    <tr>
                        <td>
                            <strong><?php echo escapeHtml($ann['title']); ?></strong>
                            <br><small><?php echo escapeHtml(truncateText($ann['content'], 60)); ?></small>
                        </td>
                        <td>
                            <span class="badge badge-<?php echo $ann['type']; ?>">
                                <?php echo ucfirst($ann['type']); ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge badge-priority-<?php echo $ann['priority']; ?>">
                                <?php echo ucfirst($ann['priority']); ?>
                            </span>
                        </td>
                        <td><?php echo ucfirst(str_replace('_', ' ', $ann['target_audience'])); ?></td>
                        <td>
                            <small>
                                <i class="fas fa-calendar-check"></i> <?php echo formatDate($ann['start_date'], 'M j'); ?><br>
                                <i class="fas fa-calendar-times"></i> <?php echo formatDate($ann['end_date'], 'M j'); ?>
                            </small>
                        </td>
                        <td>
                            <?php if ($isActive): ?>
                                <span class="badge badge-success">Active</span>
                            <?php elseif ($now < date('Y-m-d', strtotime($ann['start_date']))): ?>
                                <span class="badge badge-info">Scheduled</span>
                            <?php else: ?>
                                <span class="badge badge-secondary">Expired</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo escapeHtml($ann['creator_name']); ?></td>
                        <td>
                            <div class="action-buttons">
                                <a href="edit.php?id=<?php echo $ann['announcement_id']; ?>" class="btn-icon" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="delete.php?id=<?php echo $ann['announcement_id']; ?>" class="btn-icon btn-danger" title="Delete">
                                    <i class="fas fa-trash"></i>
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
            <a href="?page=<?php echo $page - 1; ?>&type=<?php echo $type; ?>&priority=<?php echo $priority; ?>&search=<?php echo urlencode($search); ?>" class="page-link">
                <i class="fas fa-chevron-left"></i> Previous
            </a>
        <?php endif; ?>

        <div class="page-numbers">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <?php if ($i == 1 || $i == $totalPages || ($i >= $page - 2 && $i <= $page + 2)): ?>
                    <a href="?page=<?php echo $i; ?>&type=<?php echo $type; ?>&priority=<?php echo $priority; ?>&search=<?php echo urlencode($search); ?>" class="page-link <?php echo $i == $page ? 'active' : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php elseif ($i == $page - 3 || $i == $page + 3): ?>
                    <span class="page-dots">...</span>
                <?php endif; ?>
            <?php endfor; ?>
        </div>

        <?php if ($page < $totalPages): ?>
            <a href="?page=<?php echo $page + 1; ?>&type=<?php echo $type; ?>&priority=<?php echo $priority; ?>&search=<?php echo urlencode($search); ?>" class="page-link">
                Next <i class="fas fa-chevron-right"></i>
            </a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<style>
.page-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:2rem}
.filters-card{background:white;padding:1.5rem;border-radius:12px;margin-bottom:1.5rem;box-shadow:var(--admin-shadow)}
.filters-form{display:flex;gap:1rem;flex-wrap:wrap;align-items:flex-end}
.filter-group{flex:1;min-width:180px}
.filter-group label{display:block;margin-bottom:0.5rem;font-weight:500;font-size:0.875rem}
.filter-group select,.search-box input{width:100%;padding:0.75rem 1rem;border:1px solid var(--admin-border);border-radius:6px;font-size:0.9rem}
.search-box{position:relative}
.search-box button{position:absolute;right:0;top:0;height:100%;padding:0 1rem;background:none;border:none;cursor:pointer}
.btn-clear-filters{padding:0.75rem 1.5rem;background:var(--admin-hover);color:var(--admin-text);border-radius:6px;text-decoration:none;font-size:0.875rem}
.results-summary{margin-bottom:1rem;color:var(--admin-text-muted);font-size:0.9rem}
.content-card{background:white;border-radius:12px;box-shadow:var(--admin-shadow);overflow:hidden}
.action-buttons {
    display: flex;
    gap: 0.5rem;
    justify-content: center;
}

.btn-primary {
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
.btn-primary:hover {
    background: var(--admin-primary-dark);
    transform: translateY(-2px);
    box-shadow: var(--admin-shadow-lg);
}
.btn-icon {
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 6px;
    background: var(--admin-hover);
    color: var(--admin-text);
    text-decoration: none;
    transition: all 0.3s;
}

.btn-icon:hover {
    background: var(--admin-primary);
    color: white;
}

.btn-icon.btn-view:hover {
    background: var(--admin-info);
}

.btn-icon.btn-danger:hover {
    background: var(--admin-danger);
}

.empty-state{padding:3rem 2rem;text-align:center}
.empty-state i{font-size:4rem;color:var(--admin-border);margin-bottom:1rem}
.badge-priority-high{background:#f8d7da;color:#721c24}
.badge-priority-medium{background:#fff3cd;color:#856404}
.badge-priority-low{background:#d4edda;color:#155724}
.badge-secondary{background:#e2e3e5;color:#383d41}
.badge-info{background:#d1ecf1;color:#0c5460}
.pagination{display:flex;justify-content:center;align-items:center;gap:0.5rem;padding:1.5rem;border-top:1px solid var(--admin-border)}
.page-link{padding:0.5rem 0.75rem;border-radius:6px;background:var(--admin-hover);color:var(--admin-text);text-decoration:none;transition:all 0.3s}
.page-link:hover{background:var(--admin-primary);color:white}
.page-link.active{background:var(--admin-primary);color:white}
</style>

<?php include '../includes/admin-footer.php'; ?>
