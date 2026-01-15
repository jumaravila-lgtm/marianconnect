<?php
/**
 * MARIANCONNECT - News Management
 */

// Authentication check
require_once '../includes/auth-check.php';

$db = getDB();

// Get filters
$status = $_GET['status'] ?? 'all';
$category = $_GET['category'] ?? 'all';
$search = $_GET['search'] ?? '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 25;
$offset = ($page - 1) * $perPage;

// Build query
$where = [];
$params = [];

if ($status !== 'all') {
    $where[] = "n.status = ?";
    $params[] = $status;
}

if ($category !== 'all') {
    $where[] = "n.category = ?";
    $params[] = $category;
}

if (!empty($search)) {
    $where[] = "(n.title LIKE ? OR n.content LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}

$whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// Get total count
$countSql = "SELECT COUNT(*) as total FROM news n {$whereClause}";
$stmt = $db->prepare($countSql);
$stmt->execute($params);
$totalItems = $stmt->fetch()['total'];
$totalPages = ceil($totalItems / $perPage);

// Get news list
$sql = "
    SELECT n.*, a.full_name as author_name
    FROM news n
    JOIN admin_users a ON n.author_id = a.admin_id
    {$whereClause}
    ORDER BY n.created_at DESC
    LIMIT {$perPage} OFFSET {$offset}
";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$newsList = $stmt->fetchAll();

$pageTitle = 'News Management';
include '../includes/admin-header.php';
?>

<div class="page-header">
    <h1>News Management</h1>
    <div class="page-actions">
        <a href="create.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Add New Article
        </a>
    </div>
</div>

<!-- Filters -->
<div class="filters-card">
    <form method="GET" action="" class="filters-form">
        <div class="filter-group">
            <label>Status:</label>
            <select name="status" onchange="this.form.submit()">
                <option value="all" <?php echo $status === 'all' ? 'selected' : ''; ?>>All Status</option>
                <option value="published" <?php echo $status === 'published' ? 'selected' : ''; ?>>Published</option>
                <option value="draft" <?php echo $status === 'draft' ? 'selected' : ''; ?>>Draft</option>
                <option value="archived" <?php echo $status === 'archived' ? 'selected' : ''; ?>>Archived</option>
            </select>
        </div>

        <div class="filter-group">
            <label>Category:</label>
            <select name="category" onchange="this.form.submit()">
                <option value="all" <?php echo $category === 'all' ? 'selected' : ''; ?>>All Categories</option>
                <option value="academic" <?php echo $category === 'academic' ? 'selected' : ''; ?>>Academic</option>
                <option value="sports" <?php echo $category === 'sports' ? 'selected' : ''; ?>>Sports</option>
                <option value="events" <?php echo $category === 'events' ? 'selected' : ''; ?>>Events</option>
                <option value="achievements" <?php echo $category === 'achievements' ? 'selected' : ''; ?>>Achievements</option>
                <option value="general" <?php echo $category === 'general' ? 'selected' : ''; ?>>General</option>
            </select>
        </div>

        <div class="filter-group">
            <label>Search:</label>
            <div class="search-box">
                <input type="text" name="search" placeholder="Search news..." value="<?php echo escapeHtml($search); ?>">
                <button type="submit"><i class="fas fa-search"></i></button>
            </div>
        </div>

        <?php if ($status !== 'all' || $category !== 'all' || !empty($search)): ?>
        <a href="index.php" class="btn-clear-filters">Clear Filters</a>
        <?php endif; ?>
    </form>
</div>

<!-- Results Summary -->
<div class="results-summary">
    <p>Showing <?php echo count($newsList); ?> of <?php echo number_format($totalItems); ?> articles</p>
</div>

<!-- News Table -->
<div class="content-card">
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 60px;">Image</th>
                    <th>Title</th>
                    <th>Category</th>
                    <th>Author</th>
                    <th>Status</th>
                    <th>Views</th>
                    <th>Date</th>
                    <th style="width: 150px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($newsList)): ?>
                <tr>
                    <td colspan="8" class="text-center">
                        <div class="empty-state">
                            <i class="fas fa-newspaper"></i>
                            <p>No news articles found</p>
                        </div>
                    </td>
                </tr>
                <?php else: ?>
                    <?php foreach ($newsList as $news): ?>
                        <?php 
                        // DEBUG - Remove this after fixing
                        echo "<!-- DEBUG: featured_image = " . htmlspecialchars($news['featured_image'] ?? 'NULL') . " -->"; 
                        echo "<!-- DEBUG: getImageUrl = " . htmlspecialchars(getImageUrl($news['featured_image'])) . " -->"; 
                        ?>
                    <tr>
                        <td>
                            <div class="table-image">
                                <?php if (!empty($news['featured_image'])): ?>
                                    <img src="<?php echo escapeHtml(getImageUrl($news['featured_image'])); ?>" 
                                        alt="<?php echo escapeHtml($news['title']); ?>">
                                <?php else: ?>
                                    <div class="no-image"><i class="fas fa-image"></i></div>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td>
                            <strong><?php echo escapeHtml($news['title']); ?></strong>
                            <?php if ($news['is_featured']): ?>
                                <span class="badge badge-star">⭐ Featured</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge badge-category-<?php echo $news['category']; ?>">
                                <?php echo ucfirst($news['category']); ?>
                            </span>
                        </td>
                        <td><?php echo escapeHtml($news['author_name']); ?></td>
                        <td>
                            <span class="badge badge-<?php echo $news['status']; ?>">
                                <?php echo ucfirst($news['status']); ?>
                            </span>
                        </td>
                        <td>
                            <i class="fas fa-eye"></i> <?php echo number_format($news['views']); ?>
                        </td>
                        <td>
                            <small><?php echo formatDate($news['created_at'], 'M j, Y'); ?></small>
                        </td>
                        <td>
                            <div class="action-buttons">
                                <a href="edit.php?id=<?php echo $news['news_id']; ?>" class="btn-icon" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="../../pages/news-detail.php?slug=<?php echo $news['slug']; ?>" class="btn-icon btn-view" title="View" target="_blank">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="delete.php?id=<?php echo $news['news_id']; ?>" class="btn-icon btn-danger" title="Delete">
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
            <a href="?page=<?php echo $page - 1; ?>&status=<?php echo $status; ?>&category=<?php echo $category; ?>&search=<?php echo urlencode($search); ?>" class="page-link">
                <i class="fas fa-chevron-left"></i> Previous
            </a>
        <?php endif; ?>

        <div class="page-numbers">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <?php if ($i == 1 || $i == $totalPages || ($i >= $page - 2 && $i <= $page + 2)): ?>
                    <a href="?page=<?php echo $i; ?>&status=<?php echo $status; ?>&category=<?php echo $category; ?>&search=<?php echo urlencode($search); ?>" class="page-link <?php echo $i == $page ? 'active' : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php elseif ($i == $page - 3 || $i == $page + 3): ?>
                    <span class="page-dots">...</span>
                <?php endif; ?>
            <?php endfor; ?>
        </div>

        <?php if ($page < $totalPages): ?>
            <a href="?page=<?php echo $page + 1; ?>&status=<?php echo $status; ?>&category=<?php echo $category; ?>&search=<?php echo urlencode($search); ?>" class="page-link">
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
    min-width: 200px;
}

.filter-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 500;
    font-size: 0.875rem;
}

.filter-group select,
.search-box input {
    width: 100%;
    padding: 0.75rem 1rem;
    border: 1px solid var(--admin-border);
    border-radius: 6px;
    font-size: 0.9rem;
}

.search-box {
    position: relative;
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
    background: var(--admin-hover);
    display: flex;
    align-items: center;
    justify-content: center;
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

.btn-icon.btn-view:hover {
    background: var(--admin-info);
}

.btn-icon.btn-danger:hover {
    background: var(--admin-danger);
}

.badge-star {
    background: linear-gradient(135deg, #ffd700, #ffed4e);
    color: #856404;
    font-size: 0.7rem;
}

.badge-category-academic { background: #d1ecf1; color: #0c5460; }
.badge-category-sports { background: #d4edda; color: #155724; }
.badge-category-events { background: #fff3cd; color: #856404; }
.badge-category-achievements { background: #f8d7da; color: #721c24; }
.badge-category-general { background: #e2e3e5; color: #383d41; }

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
