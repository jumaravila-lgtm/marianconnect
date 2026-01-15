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
    $where[] = "(title LIKE ? OR recipient_name LIKE ?)";
    $searchTerm = "%{$search}%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

$whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

$countSql = "SELECT COUNT(*) FROM achievements {$whereClause}";
$countStmt = $db->prepare($countSql);
$countStmt->execute($params);
$totalRecords = $countStmt->fetchColumn();
$totalPages = ceil($totalRecords / $perPage);

$sql = "SELECT * FROM achievements {$whereClause} ORDER BY achievement_date DESC, created_at DESC LIMIT {$perPage} OFFSET {$offset}";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$achievements = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stats = [
    'total' => $db->query("SELECT COUNT(*) FROM achievements")->fetchColumn(),
    'featured' => $db->query("SELECT COUNT(*) FROM achievements WHERE is_featured = 1")->fetchColumn(),
    'academic' => $db->query("SELECT COUNT(*) FROM achievements WHERE category = 'academic'")->fetchColumn(),
    'sports' => $db->query("SELECT COUNT(*) FROM achievements WHERE category = 'sports'")->fetchColumn(),
];

$pageTitle = 'Achievements';
include '../includes/admin-header.php';
?>

<div class="page-header">
    <h1>Achievements</h1>
    <a href="create.php" class="btn btn-primary"><i class="fas fa-plus"></i> Add Achievement</a>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon" style="background:#fff3e0;color:#f57c00"><i class="fas fa-trophy"></i></div>
        <div class="stat-content">
            <div class="stat-value"><?php echo $stats['total']; ?></div>
            <div class="stat-label">Total Achievements</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#e8f5e9;color:#388e3c"><i class="fas fa-star"></i></div>
        <div class="stat-content">
            <div class="stat-value"><?php echo $stats['featured']; ?></div>
            <div class="stat-label">Featured</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#f3e5f5;color:#7b1fa2"><i class="fas fa-graduation-cap"></i></div>
        <div class="stat-content">
            <div class="stat-value"><?php echo $stats['academic']; ?></div>
            <div class="stat-label">Academic</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#e3f2fd;color:#1976d2"><i class="fas fa-medal"></i></div>
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
                <option value="community_service" <?php echo $category === 'community_service' ? 'selected' : ''; ?>>Community Service</option>
                <option value="research" <?php echo $category === 'research' ? 'selected' : ''; ?>>Research</option>
                <option value="other" <?php echo $category === 'other' ? 'selected' : ''; ?>>Other</option>
            </select>
        </div>
        
        <div class="search-box">
            <input type="text" name="search" placeholder="Search achievements..." value="<?php echo escapeHtml($search); ?>" class="search-input">
            <button type="submit" class="search-btn"><i class="fas fa-search"></i></button>
        </div>
    </form>
</div>

<div class="table-card">
    <table class="data-table">
        <thead>
            <tr>
                <th style="width:80px">Image</th>
                <th>Title</th>
                <th>Category</th>
                <th>Recipient</th>
                <th>Type</th>
                <th>Level</th>
                <th>Date</th>
                <th style="width:150px">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($achievements)): ?>
            <tr>
                <td colspan="8" class="text-center">No achievements found</td>
            </tr>
            <?php else: ?>
                <?php foreach ($achievements as $achievement): ?>
                <tr>
                    <td>
                        <?php if ($achievement['featured_image']): ?>
                            <img src="<?php echo escapeHtml(getImageUrl($achievement['featured_image'])); ?>" alt="" class="achievement-thumb">
                        <?php else: ?>
                            <div class="achievement-thumb-placeholder"><i class="fas fa-trophy"></i></div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="achievement-title"><?php echo escapeHtml($achievement['title']); ?></div>
                        <?php if ($achievement['is_featured']): ?>
                            <span class="badge badge-featured"><i class="fas fa-star"></i> Featured</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php
                        $categoryBadges = [
                            'academic' => ['Academic', '#9c27b0'],
                            'sports' => ['Sports', '#2196f3'],
                            'cultural' => ['Cultural', '#e91e63'],
                            'community_service' => ['Community', '#4caf50'],
                            'research' => ['Research', '#ff9800'],
                            'other' => ['Other', '#757575']
                        ];
                        $badge = $categoryBadges[$achievement['category']];
                        ?>
                        <span class="badge badge-category" style="background:<?php echo $badge[1]; ?>"><?php echo $badge[0]; ?></span>
                    </td>
                    <td><?php echo escapeHtml($achievement['recipient_name'] ?? '-'); ?></td>
                    <td><span class="badge badge-type"><?php echo ucfirst($achievement['recipient_type']); ?></span></td>
                    <td><span class="badge badge-level-<?php echo $achievement['award_level']; ?>"><?php echo ucfirst($achievement['award_level']); ?></span></td>
                    <td><?php echo date('M d, Y', strtotime($achievement['achievement_date'])); ?></td>
                    <td>
                        <div class="action-buttons">
                            <a href="edit.php?id=<?php echo $achievement['achievement_id']; ?>" class="btn-action btn-edit" title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a href="delete.php?id=<?php echo $achievement['achievement_id']; ?>" class="btn-action btn-delete" title="Delete">
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
.achievement-thumb{width:60px;height:60px;object-fit:cover;border-radius:8px}
.achievement-thumb-placeholder{width:60px;height:60px;background:#f0f0f0;border-radius:8px;display:flex;align-items:center;justify-content:center;color:#999}
.achievement-title{font-weight:500;margin-bottom:0.25rem}
.badge{display:inline-block;padding:0.35rem 0.75rem;border-radius:6px;font-size:0.85rem;font-weight:500}
.badge-category{color:white}
.badge-featured{background:#ffd700;color:#000}
.badge-type{background:#e3f2fd;color:#1565c0}
.badge-level-local{background:#f5f5f5;color:#757575}
.badge-level-regional{background:#e3f2fd;color:#1976d2}
.badge-level-national{background:#fff3e0;color:#f57c00}
.badge-level-international{background:#e8f5e9;color:#2e7d32}
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
