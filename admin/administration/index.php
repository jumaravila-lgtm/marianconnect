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
    <a href="create.php" class="btn btn-primary"><i class="fas fa-plus"></i> Add New Member</a>
</div>

<!-- Stats -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-content">
            <div class="stat-value"><?php echo $stats['total']; ?></div>
            <div class="stat-label">Total Members</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-content">
            <div class="stat-value"><?php echo $stats['active']; ?></div>
            <div class="stat-label">Active</div>
        </div>
    </div>
</div>

<div class="search-section">
    <label>Search:</label>
    <form method="GET" action="">
        <div class="search-box">
            <input type="text" name="search" placeholder="Search by name, position, or email..." value="<?php echo escapeHtml($search ?? ''); ?>" class="search-input">
            <button type="submit" class="search-btn"><i class="fas fa-search"></i></button>
        </div>
    </form>
</div>

<!-- Results Summary -->
<div class="results-summary">
    <p>Showing <?php echo count($adminList); ?> of <?php echo number_format($totalItems); ?> members</p>
</div>

<!-- Administration Table -->
<div class="table-card">
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
                                <a href="edit.php?id=<?php echo $member['admin_member_id']; ?>" class="btn-action btn-edit" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="delete.php?id=<?php echo $member['admin_member_id']; ?>" class="btn-action btn-delete" title="Delete">
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

/* Buttons */
.btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.875rem 1.75rem;
    background: var(--admin-primary);
    color: white;
    border: none;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 600;
    font-size: 0.95rem;
    transition: all 0.3s;
    cursor: pointer;
    box-shadow: 0 2px 4px rgba(0, 63, 135, 0.15);
}

.btn:hover {
    background: var(--admin-primary-dark);
    transform: translateY(-2px);
    box-shadow: 0 6px 12px rgba(0, 63, 135, 0.25);
}

.btn-primary {
    background: var(--admin-primary);
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

/* Search Section */
.search-section {
    background: white;
    padding: 1.75rem;
    border-radius: 12px;
    margin-bottom: 1.5rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
    border: 1px solid var(--admin-border);
}

.search-section label {
    display: block;
    margin-bottom: 0.75rem;
    font-weight: 600;
    font-size: 0.875rem;
    color: var(--admin-text);
}

.search-box {
    position: relative;
    display: flex;
    gap: 0;
}

.search-input {
    flex: 1;
    padding: 0.875rem 1rem;
    padding-right: 3rem;
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
    position: absolute;
    right: 0.5rem;
    top: 50%;
    transform: translateY(-50%);
    padding: 0.5rem 1rem;
    background: transparent;
    color: var(--admin-text-muted);
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: 1rem;
    transition: all 0.3s;
}

.search-btn:hover {
    color: var(--admin-primary);
    background: #f8f9fa;
}

/* Results Summary */
.results-summary {
    margin-bottom: 1rem;
    padding: 0.75rem 1rem;
    background: #f8f9fa;
    border-radius: 8px;
    border-left: 4px solid var(--admin-primary);
}

.results-summary p {
    color: var(--admin-text-muted);
    font-size: 0.9rem;
    font-weight: 500;
    margin: 0;
}

/* Table Card */
.table-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
    border: 1px solid var(--admin-border);
    overflow: hidden;
}
/* Table Image Styles */
.table-image {
    width: 55px;
    height: 55px;
    border-radius: 50%;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    border: 2px solid var(--admin-border);
}

.table-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.no-image {
    width: 100%;
    height: 100%;
    background: linear-gradient(135deg, #e9ecef 0%, #f8f9fa 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--admin-text-muted);
    font-size: 1.25rem;
    font-weight: 600;
}

/* Text Utilities */
.text-muted {
    color: var(--admin-text-muted);
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

/* Member Image */
.member-image {
    width: 55px;
    height: 55px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid var(--admin-border);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.member-image-placeholder {
    width: 55px;
    height: 55px;
    border-radius: 50%;
    background: linear-gradient(135deg, #e9ecef 0%, #f8f9fa 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--admin-text-muted);
    font-size: 1.25rem;
    font-weight: 600;
    border: 2px solid var(--admin-border);
}

/* Member Info */
.member-name {
    font-weight: 600;
    color: var(--admin-text);
    font-size: 0.95rem;
}

.member-position {
    color: var(--admin-text-muted);
    font-size: 0.85rem;
    margin-top: 0.25rem;
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

.badge-order {
    background: linear-gradient(135deg, #e3f2fd, #bbdefb);
    color: #1565c0;
    min-width: 40px;
    justify-content: center;
}

.badge-active {
    background: linear-gradient(135deg, #e8f5e9, #c8e6c9);
    color: #2e7d32;
}

.badge-inactive {
    background: linear-gradient(135deg, #ffebee, #ffcdd2);
    color: #c62828;
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

.btn-edit {
    background: #f8f9fa;
    color: var(--admin-text);
}

.btn-edit:hover {
    background: var(--admin-primary);
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0, 63, 135, 0.25);
}

.btn-delete {
    background: #f8f9fa;
    color: var(--admin-text);
}

.btn-delete:hover {
    background: var(--admin-danger);
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(220, 53, 69, 0.25);
}

/* Empty State */
.empty-state {
    padding: 4rem 2rem;
    text-align: center;
}

.empty-state i {
    font-size: 5rem;
    color: #dee2e6;
    margin-bottom: 1.5rem;
    opacity: 0.5;
}

.empty-state p {
    color: var(--admin-text-muted);
    font-size: 1.1rem;
    margin: 0;
}

/* Responsive */
@media (max-width: 768px) {
    .page-header {
        flex-direction: column;
        gap: 1rem;
        align-items: flex-start;
    }
    
    .btn {
        width: 100%;
        justify-content: center;
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
}
</style>

<?php include '../includes/admin-footer.php'; ?>
