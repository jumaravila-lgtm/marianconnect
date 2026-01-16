<?php
require_once '../includes/auth-check.php';

// Restrict to Super Admin only
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'super_admin') {
    setFlashMessage('error', 'Access denied. Only Super Admins can manage Departments.');
    redirect('../index.php');
}

$db = getDB();
$search = $_GET['search'] ?? '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 25;
$offset = ($page - 1) * $perPage;

$where = [];
$params = [];

if (!empty($search)) {
    $where[] = "(name LIKE ? OR head_name LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}

$whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

$countSql = "SELECT COUNT(*) as total FROM departments {$whereClause}";
$stmt = $db->prepare($countSql);
$stmt->execute($params);
$totalItems = $stmt->fetch()['total'];
$totalPages = ceil($totalItems / $perPage);

$sql = "SELECT * FROM departments {$whereClause} ORDER BY display_order ASC LIMIT {$perPage} OFFSET {$offset}";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$departments = $stmt->fetchAll();

$pageTitle = 'Departments Management';
include '../includes/admin-header.php';
?>

<div class="page-header">
    <h1>Departments Management</h1>
    <div class="page-actions">
        <a href="create.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Add New Department
        </a>
    </div>
</div>

<div class="filters-card">
    <form method="GET" action="" class="filters-form">
        <div class="filter-group">
            <label>Search:</label>
            <div class="search-box">
                <input type="text" name="search" placeholder="Search departments..." value="<?php echo escapeHtml($search); ?>">
                <button type="submit"><i class="fas fa-search"></i></button>
            </div>
        </div>
        <?php if (!empty($search)): ?>
        <a href="index.php" class="btn-clear-filters">Clear Filters</a>
        <?php endif; ?>
    </form>
</div>

<div class="results-summary">
    <p>Showing <?php echo count($departments); ?> of <?php echo number_format($totalItems); ?> departments</p>
</div>

<div class="content-card">
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Icon</th>
                    <th>Department Name</th>
                    <th>Department Head</th>
                    <th>Order</th>
                    <th>Status</th>
                    <th style="width: 150px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($departments)): ?>
                <tr>
                    <td colspan="6" class="text-center">
                        <div class="empty-state">
                            <i class="fas fa-building"></i>
                            <p>No departments found</p>
                        </div>
                    </td>
                </tr>
                <?php else: ?>
                    <?php foreach ($departments as $dept): ?>
                    <tr>
                        <td>
                            <?php if (!empty($dept['image'])): ?>
                                <img src="<?php echo escapeHtml(getImageUrl($dept['image'])); ?>" 
                                    alt="<?php echo escapeHtml($dept['name']); ?>"
                                    style="width:40px;height:40px;border-radius:50%;object-fit:cover;">
                            <?php else: ?>
                                <div style="width:40px;height:40px;background:#e3f2fd;border-radius:50%;display:flex;align-items:center;justify-content:center;color:#1976d2;">
                                    <i class="fas fa-building"></i>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td><strong><?php echo escapeHtml($dept['name']); ?></strong></td>
                        <td><?php echo escapeHtml($dept['head_name']); ?></td>
                        <td><span class="badge badge-order"><?php echo $dept['display_order']; ?></span></td>
                        <td>
                            <span class="badge badge-<?php echo $dept['is_active'] ? 'active' : 'inactive'; ?>">
                                <?php echo $dept['is_active'] ? 'Active' : 'Inactive'; ?>
                            </span>
                        </td>
                        <td>
                            <div class="action-buttons">
                                <a href="edit.php?id=<?php echo $dept['department_id']; ?>" class="btn-icon" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="delete.php?id=<?php echo $dept['department_id']; ?>" class="btn-icon btn-danger" title="Delete">
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
.page-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:2rem}
.filters-card{background:white;padding:1.5rem;border-radius:12px;margin-bottom:1.5rem;box-shadow:var(--admin-shadow)}
.filters-form{display:flex;gap:1rem;flex-wrap:wrap;align-items:flex-end}
.filter-group{flex:1;min-width:300px}
.filter-group label{display:block;margin-bottom:0.5rem;font-weight:500;font-size:0.875rem}
.search-box{position:relative}
.search-box input{width:100%;padding:0.75rem 1rem;border:1px solid var(--admin-border);border-radius:6px}
.search-box button{position:absolute;right:0;top:0;height:100%;padding:0 1rem;background:none;border:none;color:var(--admin-text-muted);cursor:pointer}
.btn-clear-filters{padding:0.75rem 1.5rem;background:var(--admin-hover);color:var(--admin-text);border-radius:6px;text-decoration:none;font-size:0.875rem}
.results-summary{margin-bottom:1rem;color:var(--admin-text-muted);font-size:0.9rem}
.content-card{background:white;border-radius:12px;box-shadow:var(--admin-shadow);overflow:hidden}
.badge-order{background:#e3f2fd;color:#1976d2;font-weight:600}
.badge-active{background:#e8f5e9;color:#2e7d32}
.badge-inactive{background:#ffebee;color:#c62828}
.action-buttons{display:flex;gap:0.5rem;justify-content:center}
.btn-icon{width:32px;height:32px;display:flex;align-items:center;justify-content:center;border-radius:6px;background:var(--admin-hover);color:var(--admin-text);text-decoration:none;transition:all 0.3s}
.btn-icon:hover{background:var(--admin-primary);color:white}
.btn-icon.btn-danger:hover{background:var(--admin-danger)}
.empty-state{padding:3rem 2rem;text-align:center}
.empty-state i{font-size:4rem;color:var(--admin-border);margin-bottom:1rem}
.btn{padding:0.75rem 1.5rem;background:var(--admin-primary);color:white;border:none;border-radius:8px;text-decoration:none;font-weight:500;display:inline-flex;align-items:center;gap:0.5rem;transition:all 0.3s}
.btn:hover{background:var(--admin-primary-dark);transform:translateY(-2px)}
</style>

<?php include '../includes/admin-footer.php'; ?>
