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
    $where[] = "(name LIKE ? OR location LIKE ?)";
    $searchTerm = "%{$search}%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

$whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

$countSql = "SELECT COUNT(*) FROM facilities {$whereClause}";
$countStmt = $db->prepare($countSql);
$countStmt->execute($params);
$totalRecords = $countStmt->fetchColumn();
$totalPages = ceil($totalRecords / $perPage);

$sql = "SELECT * FROM facilities {$whereClause} ORDER BY display_order ASC, name ASC LIMIT {$perPage} OFFSET {$offset}";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$facilities = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stats = [
    'total' => $db->query("SELECT COUNT(*) FROM facilities")->fetchColumn(),
    'available' => $db->query("SELECT COUNT(*) FROM facilities WHERE is_available = 1")->fetchColumn(),
    'classroom' => $db->query("SELECT COUNT(*) FROM facilities WHERE category = 'classroom'")->fetchColumn(),
    'laboratory' => $db->query("SELECT COUNT(*) FROM facilities WHERE category = 'laboratory'")->fetchColumn(),
];

$pageTitle = 'Facilities';
include '../includes/admin-header.php';
?>

<div class="page-header">
    <h1>Facilities</h1>
    <a href="create.php" class="btn btn-primary"><i class="fas fa-plus"></i> Add Facility</a>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon" style="background:#e3f2fd;color:#1976d2"><i class="fas fa-building"></i></div>
        <div class="stat-content">
            <div class="stat-value"><?php echo $stats['total']; ?></div>
            <div class="stat-label">Total Facilities</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#e8f5e9;color:#388e3c"><i class="fas fa-check-circle"></i></div>
        <div class="stat-content">
            <div class="stat-value"><?php echo $stats['available']; ?></div>
            <div class="stat-label">Available</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#fff3e0;color:#f57c00"><i class="fas fa-chalkboard"></i></div>
        <div class="stat-content">
            <div class="stat-value"><?php echo $stats['classroom']; ?></div>
            <div class="stat-label">Classrooms</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#f3e5f5;color:#7b1fa2"><i class="fas fa-flask"></i></div>
        <div class="stat-content">
            <div class="stat-value"><?php echo $stats['laboratory']; ?></div>
            <div class="stat-label">Laboratories</div>
        </div>
    </div>
</div>

<div class="filter-bar">
    <form method="GET" action="" class="filter-form">
        <div class="filter-group">
            <select name="category" class="filter-select" onchange="this.form.submit()">
                <option value="all" <?php echo $category === 'all' ? 'selected' : ''; ?>>All Categories</option>
                <option value="classroom" <?php echo $category === 'classroom' ? 'selected' : ''; ?>>Classroom</option>
                <option value="laboratory" <?php echo $category === 'laboratory' ? 'selected' : ''; ?>>Laboratory</option>
                <option value="library" <?php echo $category === 'library' ? 'selected' : ''; ?>>Library</option>
                <option value="sports" <?php echo $category === 'sports' ? 'selected' : ''; ?>>Sports</option>
                <option value="chapel" <?php echo $category === 'chapel' ? 'selected' : ''; ?>>Chapel</option>
                <option value="office" <?php echo $category === 'office' ? 'selected' : ''; ?>>Office</option>
                <option value="other" <?php echo $category === 'other' ? 'selected' : ''; ?>>Other</option>
            </select>
        </div>
        
        <div class="search-box">
            <input type="text" name="search" placeholder="Search facilities..." value="<?php echo escapeHtml($search); ?>" class="search-input">
            <button type="submit" class="search-btn"><i class="fas fa-search"></i></button>
        </div>
    </form>
</div>

<div class="table-card">
    <table class="data-table">
        <thead>
            <tr>
                <th style="width:80px">Image</th>
                <th>Facility Name</th>
                <th>Category</th>
                <th>Location</th>
                <th>Capacity</th>
                <th style="width:100px">Status</th>
                <th style="width:150px">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($facilities)): ?>
            <tr>
                <td colspan="7" class="text-center">No facilities found</td>
            </tr>
            <?php else: ?>
                <?php foreach ($facilities as $facility): ?>
                <tr>
                    <td>
                        <?php if ($facility['featured_image']): ?>
                            <img src="/marianconnect<?php echo escapeHtml($facility['featured_image']); ?>" alt="" class="facility-thumb">
                        <?php else: ?>
                            <div class="facility-thumb-placeholder"><i class="fas fa-building"></i></div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="facility-name"><?php echo escapeHtml($facility['name']); ?></div>
                    </td>
                    <td>
                        <?php
                        $categoryBadges = [
                            'classroom' => ['Classroom', '#ff9800'],
                            'laboratory' => ['Laboratory', '#9c27b0'],
                            'library' => ['Library', '#3f51b5'],
                            'sports' => ['Sports', '#4caf50'],
                            'chapel' => ['Chapel', '#673ab7'],
                            'office' => ['Office', '#607d8b'],
                            'other' => ['Other', '#757575']
                        ];
                        $badge = $categoryBadges[$facility['category']];
                        ?>
                        <span class="badge badge-category" style="background:<?php echo $badge[1]; ?>"><?php echo $badge[0]; ?></span>
                    </td>
                    <td><?php echo escapeHtml($facility['location'] ?? '-'); ?></td>
                    <td><?php echo escapeHtml($facility['capacity'] ?? '-'); ?></td>
                    <td>
                        <?php if ($facility['is_available']): ?>
                            <span class="badge badge-success">Available</span>
                        <?php else: ?>
                            <span class="badge badge-unavailable">Unavailable</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="action-buttons">
                            <a href="edit.php?id=<?php echo $facility['facility_id']; ?>" class="btn-action btn-edit" title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a href="delete.php?id=<?php echo $facility['facility_id']; ?>" class="btn-action btn-delete" title="Delete">
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
.page-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:2rem}
.stats-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:1.5rem;margin-bottom:2rem}
.stat-card{background:white;border-radius:12px;padding:1.5rem;box-shadow:var(--admin-shadow);display:flex;gap:1rem;align-items:center}
.stat-icon{width:60px;height:60px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.5rem}
.stat-value{font-size:2rem;font-weight:700;color:var(--admin-text)}
.stat-label{font-size:0.9rem;color:var(--admin-text-muted);margin-top:0.25rem}
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
.facility-thumb{width:60px;height:60px;object-fit:cover;border-radius:8px}
.facility-thumb-placeholder{width:60px;height:60px;background:#f0f0f0;border-radius:8px;display:flex;align-items:center;justify-content:center;color:#999}
.facility-name{font-weight:500}
.badge{display:inline-block;padding:0.35rem 0.75rem;border-radius:6px;font-size:0.85rem;font-weight:500}
.badge-category{color:white}
.badge-success{background:#e8f5e9;color:#2e7d32}
.badge-unavailable{background:#ffebee;color:#c62828}
.action-buttons{display:flex;gap:0.5rem}
.btn-action{width:36px;height:36px;border-radius:8px;display:flex;align-items:center;justify-content:center;border:none;cursor:pointer;transition:all 0.2s}
.btn-edit{background:#e3f2fd;color:#1976d2}
.btn-edit:hover{background:#1976d2;color:white}
.btn-delete{background:#ffebee;color:#d32f2f}
.btn-delete:hover{background:#d32f2f;color:white}
.pagination{display:flex;gap:0.5rem;justify-content:center;margin-top:2rem}
.page-link{padding:0.5rem 1rem;border:2px solid var(--admin-border);border-radius:8px;text-decoration:none;color:var(--admin-text)}
.page-link.active{background:var(--admin-primary);color:white;border-color:var(--admin-primary)}
.text-center{text-align:center;padding:3rem!important;color:var(--admin-text-muted)}
.btn{padding:0.6rem 1.25rem;border:none;border-radius:8px;font-weight:500;cursor:pointer;display:inline-flex;align-items:center;gap:0.5rem;text-decoration:none;transition:all 0.2s}
.btn-primary{background:var(--admin-primary);color:white}
.btn-primary:hover{background:#004a99;transform:translateY(-2px)}
</style>

<?php include '../includes/admin-footer.php'; ?>
