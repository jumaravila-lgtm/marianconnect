<?php
/**
 * MARIANCONNECT - Administration Management
 */

// Authentication check
require_once '../includes/auth-check.php';

$db = getDB();

// Get filters
$search = $_GET['search'] ?? '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 25;
$offset = ($page - 1) * $perPage;

// Build query
$where = [];
$params = [];

if (!empty($search)) {
    $where[] = "(name LIKE ? OR position LIKE ? OR email LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}

$whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// Get total count
$countSql = "SELECT COUNT(*) as total FROM administration {$whereClause}";
$stmt = $db->prepare($countSql);
$stmt->execute($params);
$totalItems = $stmt->fetch()['total'];
$totalPages = ceil($totalItems / $perPage);

// Get administration list
$sql = "
    SELECT *
    FROM administration
    {$whereClause}
    ORDER BY display_order ASC, name ASC
    LIMIT {$perPage} OFFSET {$offset}
";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$adminList = $stmt->fetchAll();

// Get stats
$stats = [
    'total' => $db->query("SELECT COUNT(*) FROM administration")->fetchColumn(),
    'active' => $db->query("SELECT COUNT(*) FROM administration WHERE is_active = 1")->fetchColumn(),
];

$pageTitle = 'Administration Management';
include '../includes/admin-header.php';
?>

<div class="page-header">
    <h1>Administration Management</h1>
    <div class="page-actions">
        <a href="create.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Add New Member
        </a>
    </div>
</div>

<!-- Stats -->
<div class="stats-grid-horizontal">
    <div class="stat-card">
        <div class="stat-icon" style="background:#e3f2fd;color:#1976d2"><i class="fas fa-users"></i></div>
        <div class="stat-content">
            <div class="stat-value"><?php echo $stats['total']; ?></div>
            <div class="stat-label">Total Members</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#e8f5e9;color:#388e3c"><i class="fas fa-check-circle"></i></div>
        <div class="stat-content">
            <div class="stat-value"><?php echo $stats['active']; ?></div>
            <div class="stat-label">Active</div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="filters-card">
    <form method="GET" action="" class="filters-form">
        <div class="filter-group">
            <label>Search:</label>
            <div class="search-box">
                <input type="text" name="search" placeholder="Search by name, position, or email..." value="<?php echo escapeHtml($search); ?>">
                <button type="submit"><i class="fas fa-search"></i></button>
            </div>
        </div>

        <?php if (!empty($search)): ?>
        <a href="index.php" class="btn-clear-filters">Clear Filters</a>
        <?php endif; ?>
    </form>
</div>

<!-- Results Summary -->
<div class="results-summary">
    <p>Showing <?php echo count($adminList); ?> of <?php echo number_format($totalItems); ?> members</p>
</div>

<!-- Administration Table -->
<div class="content-card">
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 60px;">Image</th>
                    <th>Name</th>
                    <th>Position</th>
                    <th>Email</th>
                    <th>Order</th>
                    <th>Status</th>
                    <th style="width: 150px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($adminList)): ?>
                <tr>
                    <td colspan="7" class="text-center">
                        <div class="empty-state">
                            <i class="fas fa-users"></i>
                            <p>No administration members found</p>
                            <?php if (empty($search)): ?>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php else: ?>
                    <?php foreach ($adminList as $member): ?>
                    <tr>
                        <td>
                            <div class="table-image">
                                <?php if (!empty($member['featured_image'])): ?>
                                    <img src="<?php echo escapeHtml(getImageUrl($member['featured_image'])); ?>" 
                                        alt="<?php echo escapeHtml($member['name']); ?>"
                                        onerror="this.parentElement.innerHTML='<div class=\'no-image\'><?php echo strtoupper(substr($member['name'], 0, 1)); ?></div>'">
                                <?php else: ?>
                                    <div class="no-image"><?php echo strtoupper(substr($member['name'], 0, 1)); ?></div>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td>
                            <strong><?php echo escapeHtml($member['name']); ?></strong>
                        </td>
                        <td><?php echo escapeHtml($member['position']); ?></td>
                        <td>
                            <?php if (!empty($member['email'])): ?>
                                <a href="mailto:<?php echo escapeHtml($member['email']); ?>">
                                    <?php echo escapeHtml($member['email']); ?>
                                </a>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge badge-order"><?php echo $member['display_order']; ?></span>
                        </td>
                        <td>
                            <span class="badge badge-<?php echo $member['is_active'] ? 'active' : 'inactive'; ?>">
                                <?php echo $member['is_active'] ? 'Active' : 'Inactive'; ?>
                            </span>
                        </td>
                        <td>
                            <div class="action-buttons">
                                <a href="edit.php?id=<?php echo $member['admin_member_id']; ?>" class="btn-icon" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="delete.php?id=<?php echo $member['admin_member_id']; ?>" class="btn-icon btn-danger" title="Delete">
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

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
    <div class="pagination">
        <?php if ($page > 1): ?>
            <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>" class="page-link">
                <i class="fas fa-chevron-left"></i> Previous
            </a>
        <?php endif; ?>

        <div class="page-numbers">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <?php if ($i == 1 || $i == $totalPages || ($i >= $page - 2 && $i <= $page + 2)): ?>
                    <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>" class="page-link <?php echo $i == $page ? 'active' : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php elseif ($i == $page - 3 || $i == $page + 3): ?>
                    <span class="page-dots">...</span>
                <?php endif; ?>
            <?php endfor; ?>
        </div>

        <?php if ($page < $totalPages): ?>
            <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>" class="page-link">
                Next <i class="fas fa-chevron-right"></i>
            </a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

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

.stats-grid-horizontal {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.stat-card {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: var(--admin-shadow);
    display: flex;
    gap: 1rem;
    align-items: center;
}

.stat-icon {
    width: 60px;
    height: 60px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
}

.stat-value {
    font-size: 2rem;
    font-weight: 700;
    color: var(--admin-text);
}

.stat-label {
    font-size: 0.9rem;
    color: var(--admin-text-muted);
}

.filters-card {
    background: white;
    padding: 1.5rem;
    border-radius: 12px;
    margin-bottom: 1.5rem;
    box-shadow: var(--admin-shadow);
}

.filters-form {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
    align-items: flex-end;
}

.filter-group {
    flex: 1;
    min-width: 300px;
}

.filter-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 500;
    font-size: 0.875rem;
}

.search-box {
    position: relative;
}

.search-box input {
    width: 100%;
    padding: 0.75rem 1rem;
    border: 1px solid var(--admin-border);
    border-radius: 6px;
    font-size: 0.9rem;
}

.search-box button {
    position: absolute;
    right: 0;
    top: 0;
    height: 100%;
    padding: 0 1rem;
    background: none;
    border: none;
    color: var(--admin-text-muted);
    cursor: pointer;
}

.btn-clear-filters {
    padding: 0.75rem 1.5rem;
    background: var(--admin-hover);
    color: var(--admin-text);
    border-radius: 6px;
    text-decoration: none;
    font-size: 0.875rem;
    transition: background 0.3s;
}

.btn-clear-filters:hover {
    background: var(--admin-border);
}

.results-summary {
    margin-bottom: 1rem;
    color: var(--admin-text-muted);
    font-size: 0.9rem;
}

.content-card {
    background: white;
    border-radius: 12px;
    box-shadow: var(--admin-shadow);
    overflow: hidden;
}

.table-responsive {
    overflow-x: auto;
}

.data-table {
    width: 100%;
    border-collapse: collapse;
}

.data-table thead {
    background: var(--admin-hover);
}

.data-table th {
    padding: 1rem;
    text-align: left;
    font-weight: 600;
    color: var(--admin-text);
    font-size: 0.875rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.data-table td {
    padding: 1rem;
    border-bottom: 1px solid var(--admin-border);
    vertical-align: middle;
}

.table-image {
    width: 50px;
    height: 50px;
    border-radius: 6px;
    overflow: hidden;
}

.table-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.no-image {
    width: 100%;
    height: 100%;
    background: linear-gradient(135deg, var(--admin-primary), var(--admin-primary-light));
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 700;
    font-size: 1.25rem;
}

.badge {
    display: inline-block;
    padding: 0.35rem 0.75rem;
    border-radius: 6px;
    font-size: 0.8rem;
    font-weight: 500;
}

.badge-active {
    background: #e8f5e9;
    color: #2e7d32;
}

.badge-inactive {
    background: #ffebee;
    color: #c62828;
}

.badge-order {
    background: #e3f2fd;
    color: #1976d2;
    font-weight: 600;
}

.text-muted {
    color: var(--admin-text-muted);
}

.action-buttons {
    display: flex;
    gap: 0.5rem;
    justify-content: center;
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

.btn-icon.btn-danger:hover {
    background: var(--admin-danger);
}

.empty-state {
    padding: 3rem 2rem;
    text-align: center;
}

.empty-state i {
    font-size: 4rem;
    color: var(--admin-border);
    margin-bottom: 1rem;
}

.empty-state p {
    color: var(--admin-text-muted);
    margin-bottom: 1.5rem;
}

.text-center {
    text-align: center;
}

.pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 0.5rem;
    padding: 1.5rem;
    border-top: 1px solid var(--admin-border);
}

.page-numbers {
    display: flex;
    gap: 0.25rem;
}

.page-link {
    padding: 0.5rem 0.75rem;
    border-radius: 6px;
    background: var(--admin-hover);
    color: var(--admin-text);
    text-decoration: none;
    transition: all 0.3s;
}

.page-link:hover {
    background: var(--admin-primary);
    color: white;
}

.page-link.active {
    background: var(--admin-primary);
    color: white;
}

.page-dots {
    padding: 0.5rem;
    color: var(--admin-text-muted);
}
</style>

<?php include '../includes/admin-footer.php'; ?>
