<?php
require_once '../includes/auth-check.php';
$db = getDB();

$level = $_GET['level'] ?? 'all';
$search = $_GET['search'] ?? '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 25;
$offset = ($page - 1) * $perPage;

// Build query
$where = [];
$params = [];

if ($level !== 'all') {
    $where[] = "level = ?";
    $params[] = $level;
}

if (!empty($search)) {
    $where[] = "(program_name LIKE ? OR program_code LIKE ? OR department LIKE ?)";
    $searchTerm = "%{$search}%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

$whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// Get total count
$countSql = "SELECT COUNT(*) FROM academic_programs {$whereClause}";
$countStmt = $db->prepare($countSql);
$countStmt->execute($params);
$totalRecords = $countStmt->fetchColumn();
$totalPages = ceil($totalRecords / $perPage);

// Get programs
$sql = "SELECT * FROM academic_programs {$whereClause} ORDER BY created_at DESC LIMIT {$perPage} OFFSET {$offset}";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$programs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get statistics
$stats = [
    'total' => $db->query("SELECT COUNT(*) FROM academic_programs")->fetchColumn(),
    'elementary' => $db->query("SELECT COUNT(*) FROM academic_programs WHERE level = 'elementary'")->fetchColumn(),
    'junior_high' => $db->query("SELECT COUNT(*) FROM academic_programs WHERE level = 'junior_high'")->fetchColumn(),
    'senior_high' => $db->query("SELECT COUNT(*) FROM academic_programs WHERE level = 'senior_high'")->fetchColumn(),
    'college' => $db->query("SELECT COUNT(*) FROM academic_programs WHERE level = 'college'")->fetchColumn(),
];

$pageTitle = 'Academic Programs';
include '../includes/admin-header.php';
?>

<div class="page-header">
    <h1>Academic Programs</h1>
    <div class="page-actions">
        <a href="create.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Add Program
        </a>
    </div>
</div>

<!-- Statistics Cards -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-content">
            <div class="stat-value"><?php echo $stats['total']; ?></div>
            <div class="stat-label">Total Programs</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-content">
            <div class="stat-value"><?php echo $stats['elementary']; ?></div>
            <div class="stat-label">Elementary</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-content">
            <div class="stat-value"><?php echo $stats['junior_high'] + $stats['senior_high']; ?></div>
            <div class="stat-label">High School</div>
        </div>
    </div>
    <div class="stat-card">

        <div class="stat-content">
            <div class="stat-value"><?php echo $stats['college']; ?></div>
            <div class="stat-label">College</div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="filters-card">
    <form method="GET" action="" class="filters-form">
        <div class="filter-group">
            <label>Level:</label>
            <select name="level" class="form-control" onchange="this.form.submit()">
                <option value="all" <?php echo $level === 'all' ? 'selected' : ''; ?>>All Levels</option>
                <option value="elementary" <?php echo $level === 'elementary' ? 'selected' : ''; ?>>Elementary</option>
                <option value="junior_high" <?php echo $level === 'junior_high' ? 'selected' : ''; ?>>Junior High</option>
                <option value="senior_high" <?php echo $level === 'senior_high' ? 'selected' : ''; ?>>Senior High</option>
                <option value="college" <?php echo $level === 'college' ? 'selected' : ''; ?>>College</option>
            </select>
        </div>
        
        <div class="filter-group">
            <label>Search:</label>
            <div class="search-box">
                <input type="text" name="search" placeholder="Search programs..." value="<?php echo escapeHtml($search); ?>" class="form-control">
                <button type="submit" class="search-btn"><i class="fas fa-search"></i></button>
            </div>
        </div>
        
        <?php if ($level !== 'all' || !empty($search)): ?>
        <a href="index.php" class="btn-clear-filters">Clear Filters</a>
        <?php endif; ?>
    </form>
</div>

<!-- Results Summary -->
<div class="results-summary">
    <p>Showing <?php echo count($programs); ?> of <?php echo number_format($totalRecords); ?> programs</p>
</div>

<!-- Programs Table -->
<div class="content-card">
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 80px;">Image</th>
                    <th>Program Code</th>
                    <th>Program Name</th>
                    <th>Level</th>
                    <th>Department</th>
                    <th>Duration</th>
                    <th style="width: 100px;">Status</th>
                    <th style="width: 150px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($programs)): ?>
                <tr>
                    <td colspan="8" class="text-center">
                        <div class="empty-state">
                            <i class="fas fa-graduation-cap"></i>
                            <p>No programs found</p>
                        </div>
                    </td>
                </tr>
                <?php else: ?>
                    <?php foreach ($programs as $program): ?>
                    <tr>
                        <td>
                            <div class="table-image">
                                <?php if (!empty($program['featured_image'])): ?>
                                    <img src="<?php echo escapeHtml(getImageUrl($program['featured_image'])); ?>" alt="<?php echo escapeHtml($program['program_name']); ?>">
                                <?php else: ?>
                                    <div class="no-image"><i class="fas fa-image"></i></div>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td>
                            <span class="badge badge-code"><?php echo escapeHtml($program['program_code']); ?></span>
                        </td>
                        <td>
                            <strong><?php echo escapeHtml($program['program_name']); ?></strong>
                            <?php if (!empty($program['brochure_pdf'])): ?>
                                <br><small class="text-muted"><i class="fas fa-file-pdf"></i> Has brochure</small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php
                            $levelBadges = [
                                'elementary' => ['Elementary', 'badge-elementary'],
                                'junior_high' => ['Junior High', 'badge-junior'],
                                'senior_high' => ['Senior High', 'badge-senior'],
                                'college' => ['College', 'badge-college']
                            ];
                            $badge = $levelBadges[$program['level']] ?? ['Unknown', 'badge-default'];
                            ?>
                            <span class="badge <?php echo $badge[1]; ?>"><?php echo $badge[0]; ?></span>
                        </td>
                        <td><?php echo escapeHtml($program['department'] ?? 'N/A'); ?></td>
                        <td><?php echo escapeHtml($program['duration'] ?? 'N/A'); ?></td>
                        <td>
                            <?php if ($program['is_active']): ?>
                                <span class="badge badge-published">Active</span>
                            <?php else: ?>
                                <span class="badge badge-draft">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="action-buttons">
                                <a href="edit.php?id=<?php echo $program['program_id']; ?>" class="btn-icon" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="delete.php?id=<?php echo $program['program_id']; ?>" class="btn-icon btn-danger" title="Delete">
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
            <a href="?page=<?php echo $page - 1; ?>&level=<?php echo $level; ?>&search=<?php echo urlencode($search); ?>" class="page-link">
                <i class="fas fa-chevron-left"></i> Previous
            </a>
        <?php endif; ?>

        <div class="page-numbers">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <?php if ($i == 1 || $i == $totalPages || ($i >= $page - 2 && $i <= $page + 2)): ?>
                    <a href="?page=<?php echo $i; ?>&level=<?php echo $level; ?>&search=<?php echo urlencode($search); ?>" class="page-link <?php echo $i == $page ? 'active' : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php elseif ($i == $page - 3 || $i == $page + 3): ?>
                    <span class="page-dots">...</span>
                <?php endif; ?>
            <?php endfor; ?>
        </div>

        <?php if ($page < $totalPages): ?>
            <a href="?page=<?php echo $page + 1; ?>&level=<?php echo $level; ?>&search=<?php echo urlencode($search); ?>" class="page-link">
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
.filters-card {
    background: white;
    padding: 1.75rem;
    border-radius: 12px;
    margin-bottom: 1.5rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
    border: 1px solid var(--admin-border);
}

.filters-form {
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

.form-control {
    width: 100%;
    padding: 0.875rem 1rem;
    border: 2px solid var(--admin-border);
    border-radius: 8px;
    font-size: 0.95rem;
    background: white;
    cursor: pointer;
    transition: all 0.3s;
}

.form-control:hover {
    border-color: #c5cdd8;
}

.form-control:focus {
    outline: none;
    border-color: var(--admin-primary);
    box-shadow: 0 0 0 4px rgba(0, 63, 135, 0.08);
}

select.form-control {
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23333' d='M6 9L1 4h10z'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 1rem center;
    background-size: 12px;
    padding-right: 2.5rem;
}

/* Search Box */
.search-box {
    position: relative;
    display: flex;
    gap: 0.5rem;
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

.btn-clear-filters {
    padding: 0.875rem 1.5rem;
    background: #f8f9fa;
    color: var(--admin-text);
    border: 2px solid var(--admin-border);
    border-radius: 8px;
    text-decoration: none;
    font-size: 0.9rem;
    font-weight: 600;
    transition: all 0.3s;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    align-self: flex-end;
}

.btn-clear-filters:hover {
    background: white;
    border-color: var(--admin-danger);
    color: var(--admin-danger);
}

.btn-clear-filters::before {
    content: '×';
    font-size: 1.5rem;
    font-weight: 700;
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

/* Content Card */
.content-card {
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

/* Table Image */
.table-image {
    width: 55px;
    height: 55px;
    border-radius: 8px;
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

.badge-code {
    background: linear-gradient(135deg, #e3f2fd, #bbdefb);
    color: #1565c0;
}

.badge-elementary {
    background: linear-gradient(135deg, #fff3e0, #ffe0b2);
    color: #e65100;
}

.badge-junior {
    background: linear-gradient(135deg, #f3e5f5, #e1bee7);
    color: #6a1b9a;
}

.badge-senior {
    background: linear-gradient(135deg, #e8eaf6, #c5cae9);
    color: #283593;
}

.badge-college {
    background: linear-gradient(135deg, #e0f2f1, #b2dfdb);
    color: #00695c;
}

.badge-published {
    background: linear-gradient(135deg, #e8f5e9, #c8e6c9);
    color: #2e7d32;
}

.badge-draft {
    background: linear-gradient(135deg, #f5f5f5, #eeeeee);
    color: #616161;
}

.text-muted {
    color: var(--admin-text-muted);
    font-size: 0.85rem;
}

/* Action Buttons */
.action-buttons {
    display: flex;
    gap: 0.5rem;
    justify-content: center;
}

.btn-icon {
    width: 36px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
    background: #f8f9fa;
    color: var(--admin-text);
    text-decoration: none;
    transition: all 0.3s;
    border: 2px solid transparent;
}

.btn-icon:hover {
    background: var(--admin-primary);
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0, 63, 135, 0.25);
}

.btn-icon.btn-danger:hover {
    background: var(--admin-danger);
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
    .filters-form {
        grid-template-columns: 1fr;
    }
    
    .btn-clear-filters {
        width: 100%;
        justify-content: center;
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
