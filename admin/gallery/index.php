<?php
require_once '../includes/auth-check.php';
$db = getDB();

$category = $_GET['category'] ?? 'all';
$search = $_GET['search'] ?? '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 24;
$offset = ($page - 1) * $perPage;

$where = [];
$params = [];

if ($category !== 'all') {
    $where[] = "category = ?";
    $params[] = $category;
}

if (!empty($search)) {
    $where[] = "(title LIKE ? OR description LIKE ?)";
    $searchTerm = "%{$search}%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

$whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

$countSql = "SELECT COUNT(*) FROM gallery {$whereClause}";
$countStmt = $db->prepare($countSql);
$countStmt->execute($params);
$totalRecords = $countStmt->fetchColumn();
$totalPages = ceil($totalRecords / $perPage);

$sql = "SELECT g.*, a.full_name as uploader_name FROM gallery g LEFT JOIN admin_users a ON g.uploaded_by = a.admin_id {$whereClause} ORDER BY g.is_featured DESC, g.created_at DESC LIMIT {$perPage} OFFSET {$offset}";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$galleryItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fix image paths for all gallery items
foreach ($galleryItems as &$item) {
    if (!empty($item['image_path'])) {
        $item['image_path'] = asset($item['image_path']);
    }
    if (!empty($item['thumbnail_path'])) {
        $item['thumbnail_path'] = asset($item['thumbnail_path']);
    }
}
unset($item); // Break reference

$stats = [
    'total' => $db->query("SELECT COUNT(*) FROM gallery")->fetchColumn(),
    'featured' => $db->query("SELECT COUNT(*) FROM gallery WHERE is_featured = 1")->fetchColumn(),
];

$pageTitle = 'Gallery';
include '../includes/admin-header.php';
?>

<div class="page-header">
    <h1>Gallery</h1>
    <a href="upload.php" class="btn btn-primary"><i class="fas fa-upload"></i> Upload Images</a>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon" style="background:#e3f2fd;color:#1976d2"><i class="fas fa-images"></i></div>
        <div class="stat-content">
            <div class="stat-value"><?php echo $stats['total']; ?></div>
            <div class="stat-label">Total Images</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#e8f5e9;color:#388e3c"><i class="fas fa-star"></i></div>
        <div class="stat-content">
            <div class="stat-value"><?php echo $stats['featured']; ?></div>
            <div class="stat-label">Featured</div>
        </div>
    </div>
</div>

<div class="filter-bar">
    <form method="GET" action="" class="filter-form">
        <select name="category" class="filter-select" onchange="this.form.submit()">
            <option value="all" <?php echo $category === 'all' ? 'selected' : ''; ?>>All Categories</option>
            <option value="campus" <?php echo $category === 'campus' ? 'selected' : ''; ?>>Campus</option>
            <option value="events" <?php echo $category === 'events' ? 'selected' : ''; ?>>Events</option>
            <option value="facilities" <?php echo $category === 'facilities' ? 'selected' : ''; ?>>Facilities</option>
            <option value="students" <?php echo $category === 'students' ? 'selected' : ''; ?>>Students</option>
            <option value="achievements" <?php echo $category === 'achievements' ? 'selected' : ''; ?>>Achievements</option>
            <option value="other" <?php echo $category === 'other' ? 'selected' : ''; ?>>Other</option>
        </select>
        <div class="search-box">
            <input type="text" name="search" placeholder="Search..." value="<?php echo escapeHtml($search); ?>" class="search-input">
            <button type="submit" class="search-btn"><i class="fas fa-search"></i></button>
        </div>
    </form>
</div>

<div class="gallery-grid">
    <?php if (empty($galleryItems)): ?>
    <div class="empty-state">
        <i class="fas fa-images"></i>
        <h3>No images found</h3>
    </div>
    <?php else: ?>
        <?php foreach ($galleryItems as $item): ?>
        <div class="gallery-item">
            <?php if ($item['is_featured']): ?><div class="featured-badge"><i class="fas fa-star"></i></div><?php endif; ?>
            <div class="gallery-image">
                <img src="<?php echo escapeHtml($item['thumbnail_path'] ?? $item['image_path']); ?>" alt="<?php echo escapeHtml($item['title']); ?>">
                <div class="image-overlay">
                    <a href="<?php echo escapeHtml($item['image_path']); ?>" target="_blank" class="overlay-btn"><i class="fas fa-search-plus"></i></a>
                    <a href="delete.php?id=<?php echo $item['gallery_id']; ?>" class="overlay-btn" onclick="return confirm('Delete this image?')"><i class="fas fa-trash"></i></a>
                </div>
            </div>
            <div class="gallery-info">
                <h3 class="gallery-title"><?php echo escapeHtml($item['title']); ?></h3>
                <div class="gallery-meta">
                    <span class="badge badge-<?php echo $item['category']; ?>"><?php echo ucfirst($item['category']); ?></span>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php if ($totalPages > 1): ?>
<div class="pagination">
    <?php if ($page > 1): ?><a href="?page=<?php echo $page-1; ?>&category=<?php echo $category; ?>&search=<?php echo urlencode($search); ?>" class="page-link">&laquo;</a><?php endif; ?>
    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
        <a href="?page=<?php echo $i; ?>&category=<?php echo $category; ?>&search=<?php echo urlencode($search); ?>" class="page-link <?php echo $i === $page ? 'active' : ''; ?>"><?php echo $i; ?></a>
    <?php endfor; ?>
    <?php if ($page < $totalPages): ?><a href="?page=<?php echo $page+1; ?>&category=<?php echo $category; ?>&search=<?php echo urlencode($search); ?>" class="page-link">&raquo;</a><?php endif; ?>
</div>
<?php endif; ?>

<style>
.page-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:2rem}
.stats-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:1.5rem;margin-bottom:2rem}
.stat-card{background:white;border-radius:12px;padding:1.5rem;box-shadow:var(--admin-shadow);display:flex;gap:1rem;align-items:center}
.stat-icon{width:60px;height:60px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.5rem}
.stat-value{font-size:2rem;font-weight:700;color:var(--admin-text)}
.stat-label{font-size:0.9rem;color:var(--admin-text-muted)}
.filter-bar{background:white;border-radius:12px;padding:1.5rem;margin-bottom:2rem;box-shadow:var(--admin-shadow)}
.filter-form{display:flex;gap:1rem}
.filter-select{padding:0.75rem 1rem;border:2px solid var(--admin-border);border-radius:8px}
.search-box{display:flex;gap:0.5rem;flex:1;max-width:400px}
.search-input{flex:1;padding:0.75rem 1rem;border:2px solid var(--admin-border);border-radius:8px}
.search-btn{padding:0.75rem 1.5rem;background:var(--admin-primary);color:white;border:none;border-radius:8px;cursor:pointer}
.gallery-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:1.5rem}
.gallery-item{background:white;border-radius:12px;overflow:hidden;box-shadow:var(--admin-shadow);transition:transform 0.2s;position:relative}
.gallery-item:hover{transform:translateY(-4px)}
.featured-badge{position:absolute;top:10px;left:10px;background:#ffd700;color:#000;padding:0.25rem 0.75rem;border-radius:20px;font-size:0.8rem;font-weight:600;z-index:2}
.gallery-image{position:relative;height:200px;overflow:hidden;background:#f0f0f0}
.gallery-image img{width:100%;height:100%;object-fit:cover}
.image-overlay{position:absolute;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.7);display:flex;align-items:center;justify-content:center;gap:0.5rem;opacity:0;transition:opacity 0.3s}
.gallery-item:hover .image-overlay{opacity:1}
.overlay-btn{width:40px;height:40px;background:white;color:var(--admin-text);border-radius:50%;display:flex;align-items:center;justify-content:center;text-decoration:none}
.overlay-btn:hover{background:var(--admin-primary);color:white}
.gallery-info{padding:1rem}
.gallery-title{font-size:1rem;font-weight:600;margin-bottom:0.5rem;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.gallery-meta{display:flex;gap:0.75rem}
.badge{display:inline-block;padding:0.25rem 0.6rem;border-radius:6px;font-size:0.75rem;font-weight:500}
.badge-campus{background:#e3f2fd;color:#1565c0}
.badge-events{background:#f3e5f5;color:#7b1fa2}
.badge-facilities{background:#fff3e0;color:#f57c00}
.badge-students{background:#e8f5e9;color:#388e3c}
.badge-achievements{background:#fce4ec;color:#c2185b}
.badge-other{background:#f5f5f5;color:#757575}
.empty-state{grid-column:1/-1;text-align:center;padding:4rem 2rem;background:white;border-radius:12px}
.empty-state i{font-size:4rem;color:var(--admin-text-muted);margin-bottom:1rem}
.empty-state h3{font-size:1.5rem;margin-bottom:1.5rem}
.pagination{display:flex;gap:0.5rem;justify-content:center;margin-top:2rem}
.page-link{padding:0.5rem 1rem;border:2px solid var(--admin-border);border-radius:8px;text-decoration:none;color:var(--admin-text)}
.page-link.active{background:var(--admin-primary);color:white;border-color:var(--admin-primary)}
.btn{padding:0.6rem 1.25rem;border:none;border-radius:8px;font-weight:500;cursor:pointer;display:inline-flex;align-items:center;gap:0.5rem;text-decoration:none;transition:all 0.2s}
.btn-primary{background:var(--admin-primary);color:white}
.btn-primary:hover{background:#004a99}
</style>

<?php include '../includes/admin-footer.php'; ?>
