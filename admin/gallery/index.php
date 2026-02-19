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
        <div class="stat-content">
            <div class="stat-value"><?php echo $stats['total']; ?></div>
            <div class="stat-label">Total Images</div>
        </div>
    </div>
    <div class="stat-card">
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
                    <a href="delete.php?id=<?php echo $item['gallery_id']; ?>" class="overlay-btn"><i class="fas fa-trash"></i></a>
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
/* ─── PAGE HEADER ─── */
.page-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
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

/* ─── STATS GRID ─── */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 1.25rem;
    margin-bottom: 1.75rem;
}
.stat-card {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    display: flex;
    gap: 1.25rem;
    align-items: center;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
    border: 1px solid var(--admin-border);
    transition: box-shadow 0.2s, transform 0.2s;
}
.stat-card:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    transform: translateY(-2px);
}
.stat-icon {
    width: 52px;
    height: 52px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.4rem;
    flex-shrink: 0;
}
.stat-value {
    font-size: 1.75rem;
    font-weight: 700;
    color: var(--admin-text);
    line-height: 1.2;
}
.stat-label {
    font-size: 0.82rem;
    color: var(--admin-text-muted);
    margin-top: 2px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* ─── FILTER BAR ─── */
.filter-bar {
    background: white;
    padding: 1.75rem;
    border-radius: 12px;
    margin-bottom: 1.75rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
    border: 1px solid var(--admin-border);
}
.filter-form {
    display: flex;
    gap: 1rem;
    align-items: center;
    flex-wrap: wrap;
}
.filter-select {
    padding: 0.65rem 2.25rem 0.65rem 1rem;
    border: 2px solid var(--admin-border);
    border-radius: 8px;
    font-size: 0.9rem;
    color: var(--admin-text);
    background: white;
    appearance: auto;
    cursor: pointer;
    transition: border-color 0.2s, box-shadow 0.2s;
}
.filter-select:focus {
    outline: none;
    border-color: var(--admin-primary);
    box-shadow: 0 0 0 3px rgba(0, 63, 135, 0.1);
}
.search-box {
    display: flex;
    flex: 1;
    max-width: 380px;
    gap: 0;
}
.search-input {
    flex: 1;
    padding: 0.65rem 1rem;
    border: 2px solid var(--admin-border);
    border-right: none;
    border-radius: 8px 0 0 8px;
    font-size: 0.9rem;
    color: var(--admin-text);
    transition: border-color 0.2s, box-shadow 0.2s;
}
.search-input:focus {
    outline: none;
    border-color: var(--admin-primary);
    box-shadow: 0 0 0 3px rgba(0, 63, 135, 0.1);
}
.search-input::placeholder {
    color: var(--admin-text-muted);
}
.search-btn {
    padding: 0.65rem 1.25rem;
    background: linear-gradient(135deg, var(--admin-primary), #004a99);
    color: white;
    border: none;
    border-radius: 0 8px 8px 0;
    cursor: pointer;
    font-size: 0.9rem;
    transition: opacity 0.2s;
}
.search-btn:hover {
    opacity: 0.88;
}

/* ─── GALLERY GRID ─── */
.gallery-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
    gap: 1.5rem;
}
.gallery-item {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
    border: 1px solid var(--admin-border);
    transition: box-shadow 0.2s, transform 0.2s;
    position: relative;
}
.gallery-item:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    transform: translateY(-2px);
}

/* ─── FEATURED BADGE ─── */
.featured-badge {
    position: absolute;
    top: 10px;
    left: 10px;
    background: white;
    color: var(--admin-text);
    border: 2px solid var(--admin-border);
    padding: 0.2rem 0.6rem;
    border-radius: 6px;
    font-size: 0.72rem;
    font-weight: 600;
    z-index: 2;
    display: flex;
    align-items: center;
    gap: 0.3rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}
.featured-badge i {
    color: #e6a817;
}

/* ─── GALLERY IMAGE + OVERLAY ─── */
.gallery-image {
    position: relative;
    height: 195px;
    overflow: hidden;
    background: #f0f2f5;
}
.gallery-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.35s;
}
.gallery-item:hover .gallery-image img {
    transform: scale(1.04);
}
.image-overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(to top, rgba(0, 0, 0, 0.55) 0%, transparent 50%);
    display: flex;
    align-items: flex-end;
    justify-content: flex-end;
    gap: 0.5rem;
    padding: 0.75rem;
    opacity: 0;
    transition: opacity 0.25s;
}
.gallery-item:hover .image-overlay {
    opacity: 1;
}
.overlay-btn {
    width: 36px;
    height: 36px;
    background: white;
    color: var(--admin-text);
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
    font-size: 0.85rem;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15);
    transition: background 0.2s, color 0.2s, transform 0.15s;
}
.overlay-btn:hover {
    background: var(--admin-primary);
    color: white;
    transform: translateY(-1px);
}
.overlay-btn.delete-btn:hover {
    background: #dc3545;
    color: white;
}

/* ─── GALLERY INFO ─── */
.gallery-info {
    padding: 1rem 1rem 1.1rem;
}
.gallery-title {
    font-size: 0.92rem;
    font-weight: 600;
    color: var(--admin-text);
    margin: 0 0 0.5rem;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.gallery-meta {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

/* ─── BADGES (unified clean style) ─── */
.badge {
    display: inline-block;
    padding: 0.25rem 0.65rem;
    border-radius: 6px;
    font-size: 0.72rem;
    font-weight: 600;
    background: white;
    color: var(--admin-text);
    border: 2px solid var(--admin-border);
    text-transform: capitalize;
    letter-spacing: 0.2px;
}

/* ─── EMPTY STATE ─── */
.empty-state {
    grid-column: 1 / -1;
    text-align: center;
    padding: 5rem 2rem;
    background: white;
    border-radius: 12px;
    border: 1px solid var(--admin-border);
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
}
.empty-state i {
    font-size: 3.5rem;
    color: var(--admin-text-muted);
    margin-bottom: 1rem;
    display: block;
    opacity: 0.45;
}
.empty-state h3 {
    font-size: 1.15rem;
    font-weight: 600;
    color: var(--admin-text);
    margin: 0;
}

/* ─── PAGINATION ─── */
.pagination {
    display: flex;
    gap: 0.4rem;
    justify-content: center;
    margin-top: 2.5rem;
}
.page-link {
    padding: 0.5rem 0.95rem;
    border: 2px solid var(--admin-border);
    border-radius: 8px;
    text-decoration: none;
    color: var(--admin-text);
    font-size: 0.88rem;
    font-weight: 500;
    background: white;
    transition: all 0.2s;
}
.page-link:hover {
    border-color: var(--admin-primary);
    color: var(--admin-primary);
    box-shadow: 0 2px 6px rgba(0, 63, 135, 0.12);
}
.page-link.active {
    background: var(--admin-primary);
    color: white;
    border-color: var(--admin-primary);
}

/* ─── BUTTONS ─── */
.btn {
    padding: 0.6rem 1.25rem;
    border: none;
    border-radius: 8px;
    font-weight: 500;
    font-size: 0.9rem;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    text-decoration: none;
    transition: all 0.2s;
}
.btn-primary {
    background: linear-gradient(135deg, var(--admin-primary), #004a99);
    color: white;
    box-shadow: 0 2px 6px rgba(0, 63, 135, 0.25);
}
.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 63, 135, 0.3);
}

/* ─── RESPONSIVE ─── */
@media (max-width: 768px) {
    .filter-form { flex-direction: column; align-items: stretch; }
    .search-box { max-width: 100%; }
    .gallery-grid { grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 1rem; }
}
</style>

<?php include '../includes/admin-footer.php'; ?>
