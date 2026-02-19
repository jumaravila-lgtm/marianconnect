<?php
require_once '../includes/auth-check.php';
$db = getDB();

$category = $_GET['category'] ?? 'all';
$search = $_GET['search'] ?? '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 25;
$offset = ($page - 1) * $perPage;

$where = [];
$params = [];

if ($category !== 'all') {
    $where[] = "category = ?";
    $params[] = $category;
}

if (!empty($search)) {
    $where[] = "(org_name LIKE ? OR acronym LIKE ? OR adviser_name LIKE ?)";
    $searchTerm = "%{$search}%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

$whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

$countSql = "SELECT COUNT(*) FROM student_organizations {$whereClause}";
$countStmt = $db->prepare($countSql);
$countStmt->execute($params);
$totalRecords = $countStmt->fetchColumn();
$totalPages = ceil($totalRecords / $perPage);

$sql = "SELECT * FROM student_organizations {$whereClause} ORDER BY display_order ASC, org_name ASC LIMIT {$perPage} OFFSET {$offset}";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$organizations = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stats = [
    'total' => $db->query("SELECT COUNT(*) FROM student_organizations")->fetchColumn(),
    'active' => $db->query("SELECT COUNT(*) FROM student_organizations WHERE is_active = 1")->fetchColumn(),
    'academic' => $db->query("SELECT COUNT(*) FROM student_organizations WHERE category = 'academic'")->fetchColumn(),
    'sports' => $db->query("SELECT COUNT(*) FROM student_organizations WHERE category = 'sports'")->fetchColumn(),
];

$pageTitle = 'Student Organizations';
include '../includes/admin-header.php';
?>

<div class="page-header">
    <h1>Student Organizations</h1>
    <a href="create.php" class="btn btn-primary"><i class="fas fa-plus"></i> Add Organization</a>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-content">
            <div class="stat-value"><?php echo $stats['total']; ?></div>
            <div class="stat-label">Total Organizations</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-content">
            <div class="stat-value"><?php echo $stats['active']; ?></div>
            <div class="stat-label">Active</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-content">
            <div class="stat-value"><?php echo $stats['academic']; ?></div>
            <div class="stat-label">Academic</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-content">
            <div class="stat-value"><?php echo $stats['sports']; ?></div>
            <div class="stat-label">Sports</div>
        </div>
    </div>
</div>

<div class="filter-bar">
    <form method="GET" action="" class="filter-form">
        <div class="filter-group">
            <select name="category" class="filter-select" onchange="this.form.submit()">
                <option value="all" <?php echo $category === 'all' ? 'selected' : ''; ?>>All Categories</option>
                <option value="academic" <?php echo $category === 'academic' ? 'selected' : ''; ?>>Academic</option>
                <option value="sports" <?php echo $category === 'sports' ? 'selected' : ''; ?>>Sports</option>
                <option value="cultural" <?php echo $category === 'cultural' ? 'selected' : ''; ?>>Cultural</option>
                <option value="religious" <?php echo $category === 'religious' ? 'selected' : ''; ?>>Religious</option>
                <option value="service" <?php echo $category === 'service' ? 'selected' : ''; ?>>Service</option>
                <option value="special_interest" <?php echo $category === 'special_interest' ? 'selected' : ''; ?>>Special Interest</option>
            </select>
        </div>
        
        <div class="search-box">
            <input type="text" name="search" placeholder="Search organizations..." value="<?php echo escapeHtml($search); ?>" class="search-input">
            <button type="submit" class="search-btn"><i class="fas fa-search"></i></button>
        </div>
    </form>
</div>

<div class="table-card">
    <table class="data-table">
        <thead>
            <tr>
                <th style="width:80px">Logo</th>
                <th>Organization Name</th>
                <th>Acronym</th>
                <th>Category</th>
                <th>Adviser</th>
                <th>President</th>
                <th>Est. Year</th>
                <th style="width:100px">Status</th>
                <th style="width:150px">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($organizations)): ?>
            <tr>
                <td colspan="9" class="text-center">No organizations found</td>
            </tr>
            <?php else: ?>
                <?php foreach ($organizations as $org): ?>
                <tr>
                    <td>
                        <?php if ($org['logo']): ?>
                            <img src="<?php echo escapeHtml(getImageUrl($org['logo'])); ?>" alt="" class="org-logo">
                        <?php else: ?>
                            <div class="org-logo-placeholder"><i class="fas fa-users"></i></div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="org-name"><?php echo escapeHtml($org['org_name']); ?></div>
                    </td>
                    <td><span class="badge badge-acronym"><?php echo escapeHtml($org['acronym'] ?? '-'); ?></span></td>
                    <td>
                        <?php
                        $categoryBadges = [
                            'academic' => ['Academic', '#9c27b0'],
                            'sports' => ['Sports', '#ff9800'],
                            'cultural' => ['Cultural', '#e91e63'],
                            'religious' => ['Religious', '#673ab7'],
                            'service' => ['Service', '#4caf50'],
                            'special_interest' => ['Special Interest', '#2196f3']
                        ];
                        $badge = $categoryBadges[$org['category']];
                        ?>
                        <span class="badge badge-category" style="background:<?php echo $badge[1]; ?>"><?php echo $badge[0]; ?></span>
                    </td>
                    <td><?php echo escapeHtml($org['adviser_name'] ?? '-'); ?></td>
                    <td><?php echo escapeHtml($org['president_name'] ?? '-'); ?></td>
                    <td><?php echo escapeHtml($org['established_year'] ?? '-'); ?></td>
                    <td>
                        <?php if ($org['is_active']): ?>
                            <span class="badge badge-success">Active</span>
                        <?php else: ?>
                            <span class="badge badge-inactive">Inactive</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="action-buttons">
                            <a href="edit.php?id=<?php echo $org['org_id']; ?>" class="btn-action btn-edit" title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a href="delete.php?id=<?php echo $org['org_id']; ?>" class="btn-action btn-delete" title="Delete">
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
        <a href="?page=<?php echo $page-1; ?>&category=<?php echo $category; ?>&search=<?php echo urlencode($search); ?>" class="page-link">&laquo; Previous</a>
    <?php endif; ?>
    
    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
        <a href="?page=<?php echo $i; ?>&category=<?php echo $category; ?>&search=<?php echo urlencode($search); ?>" class="page-link <?php echo $i === $page ? 'active' : ''; ?>">
            <?php echo $i; ?>
        </a>
    <?php endfor; ?>
    
    <?php if ($page < $totalPages): ?>
        <a href="?page=<?php echo $page+1; ?>&category=<?php echo $category; ?>&search=<?php echo urlencode($search); ?>" class="page-link">Next &raquo;</a>
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

.page-actions {
    display: flex;
    gap: 0.75rem;
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
    display: flex;
    gap: 1.25rem;
    align-items: center;
    transition: all 0.3s;
}

.stat-card:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    transform: translateY(-2px);
}

.stat-content {
    flex: 1;
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

/* Filters Card */
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

.filter-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 600;
    font-size: 0.875rem;
    color: var(--admin-text);
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

.table-responsive {
    overflow-x: auto;
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

/* Table Images */
.org-logo {
    width: 55px;
    height: 55px;
    border-radius: 8px;
    object-fit: cover;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    border: 2px solid var(--admin-border);
}

.org-logo-placeholder {
    width: 55px;
    height: 55px;
    border-radius: 8px;
    background: linear-gradient(135deg, #e9ecef 0%, #f8f9fa 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--admin-text-muted);
    font-size: 1.25rem;
    border: 2px solid var(--admin-border);
}

/* Organization Name */
.org-name {
    font-weight: 600;
    color: var(--admin-text);
    font-size: 0.95rem;
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

.badge-acronym {
    background: linear-gradient(135deg, #e3f2fd, #bbdefb);
    color: #1565c0;
}

.badge-category {
    color: white;
}

.badge-success {
    background: linear-gradient(135deg, #e8f5e9, #c8e6c9);
    color: #2e7d32;
}

.badge-inactive {
    background: linear-gradient(135deg, #f5f5f5, #eeeeee);
    color: #616161;
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
.text-center {
    text-align: center;
    padding: 4rem 2rem !important;
}

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

.page-numbers {
    display: flex;
    gap: 0.35rem;
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

.page-dots {
    padding: 0 0.5rem;
    color: var(--admin-text-muted);
    font-weight: 700;
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
    
    .page-actions {
        width: 100%;
    }
    
    .btn {
        flex: 1;
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
    
    .pagination {
        flex-wrap: wrap;
        padding: 1rem;
    }
}
</style>

<?php include '../includes/admin-footer.php'; ?>
