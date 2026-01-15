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
    <a href="create.php" class="btn btn-primary"><i class="fas fa-plus"></i> Add Program</a>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon" style="background:#e3f2fd;color:#1976d2"><i class="fas fa-graduation-cap"></i></div>
        <div class="stat-content">
            <div class="stat-value"><?php echo $stats['total']; ?></div>
            <div class="stat-label">Total Programs</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#fff3e0;color:#f57c00"><i class="fas fa-child"></i></div>
        <div class="stat-content">
            <div class="stat-value"><?php echo $stats['elementary']; ?></div>
            <div class="stat-label">Elementary</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#f3e5f5;color:#7b1fa2"><i class="fas fa-book"></i></div>
        <div class="stat-content">
            <div class="stat-value"><?php echo $stats['junior_high'] + $stats['senior_high']; ?></div>
            <div class="stat-label">High School</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#e8f5e9;color:#388e3c"><i class="fas fa-university"></i></div>
        <div class="stat-content">
            <div class="stat-value"><?php echo $stats['college']; ?></div>
            <div class="stat-label">College</div>
        </div>
    </div>
</div>

<div class="filter-bar">
    <form method="GET" action="" class="filter-form">
        <div class="filter-group">
            <select name="level" class="filter-select" onchange="this.form.submit()">
                <option value="all" <?php echo $level === 'all' ? 'selected' : ''; ?>>All Levels</option>
                <option value="elementary" <?php echo $level === 'elementary' ? 'selected' : ''; ?>>Elementary</option>
                <option value="junior_high" <?php echo $level === 'junior_high' ? 'selected' : ''; ?>>Junior High</option>
                <option value="senior_high" <?php echo $level === 'senior_high' ? 'selected' : ''; ?>>Senior High</option>
                <option value="college" <?php echo $level === 'college' ? 'selected' : ''; ?>>College</option>
            </select>
        </div>
        
        <div class="search-box">
            <input type="text" name="search" placeholder="Search programs..." value="<?php echo escapeHtml($search); ?>" class="search-input">
            <button type="submit" class="search-btn"><i class="fas fa-search"></i></button>
        </div>
    </form>
</div>

<div class="table-card">
    <table class="data-table">
        <thead>
            <tr>
                <th style="width:80px">Image</th>
                <th>Program Code</th>
                <th>Program Name</th>
                <th>Level</th>
                <th>Department</th>
                <th>Duration</th>
                <th style="width:100px">Status</th>
                <th style="width:150px">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($programs)): ?>
            <tr>
                <td colspan="8" class="text-center">No programs found</td>
            </tr>
            <?php else: ?>
                <?php foreach ($programs as $program): ?>
                <tr>
                    <td>
                        <?php if ($program['featured_image']): ?>
                            <img src="<?php echo escapeHtml(getImageUrl($program['featured_image'])); ?>" alt="" class="program-thumb">
                        <?php else: ?>
                            <div class="program-thumb-placeholder"><i class="fas fa-image"></i></div>
                        <?php endif; ?>
                    </td>
                    <td><span class="badge badge-code"><?php echo escapeHtml($program['program_code']); ?></span></td>
                    <td>
                        <div class="program-name"><?php echo escapeHtml($program['program_name']); ?></div>
                        <?php if ($program['brochure_pdf']): ?>
                            <small class="text-muted"><i class="fas fa-file-pdf"></i> Has brochure</small>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php
                        $levelBadges = [
                            'elementary' => ['Elementary', '#ff9800'],
                            'junior_high' => ['Junior High', '#9c27b0'],
                            'senior_high' => ['Senior High', '#673ab7'],
                            'college' => ['College', '#4caf50']
                        ];
                        $badge = $levelBadges[$program['level']];
                        ?>
                        <span class="badge badge-level" style="background:<?php echo $badge[1]; ?>"><?php echo $badge[0]; ?></span>
                    </td>
                    <td><?php echo escapeHtml($program['department'] ?? '-'); ?></td>
                    <td><?php echo escapeHtml($program['duration'] ?? '-'); ?></td>
                    <td>
                        <?php if ($program['is_active']): ?>
                            <span class="badge badge-success">Active</span>
                        <?php else: ?>
                            <span class="badge badge-inactive">Inactive</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="action-buttons">
                            <a href="edit.php?id=<?php echo $program['program_id']; ?>" class="btn-action btn-edit" title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a href="delete.php?id=<?php echo $program['program_id']; ?>" class="btn-action btn-delete" title="Delete">
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
        <a href="?page=<?php echo $page-1; ?>&level=<?php echo $level; ?>&search=<?php echo urlencode($search); ?>" class="page-link">&laquo; Previous</a>
    <?php endif; ?>
    
    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
        <a href="?page=<?php echo $i; ?>&level=<?php echo $level; ?>&search=<?php echo urlencode($search); ?>" class="page-link <?php echo $i === $page ? 'active' : ''; ?>">
            <?php echo $i; ?>
        </a>
    <?php endfor; ?>
    
    <?php if ($page < $totalPages): ?>
        <a href="?page=<?php echo $page+1; ?>&level=<?php echo $level; ?>&search=<?php echo urlencode($search); ?>" class="page-link">Next &raquo;</a>
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
.program-thumb{width:60px;height:60px;object-fit:cover;border-radius:8px}
.program-thumb-placeholder{width:60px;height:60px;background:#f0f0f0;border-radius:8px;display:flex;align-items:center;justify-content:center;color:#999}
.program-name{font-weight:500;margin-bottom:0.25rem}
.text-muted{color:var(--admin-text-muted);font-size:0.85rem}
.badge{display:inline-block;padding:0.35rem 0.75rem;border-radius:6px;font-size:0.85rem;font-weight:500}
.badge-code{background:#e3f2fd;color:#1565c0}
.badge-level{color:white}
.badge-success{background:#e8f5e9;color:#2e7d32}
.badge-inactive{background:#f5f5f5;color:#757575}
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
