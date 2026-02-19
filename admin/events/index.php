<?php
/**
 * MARIANCONNECT - Events Management
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
    $where[] = "status = ?";
    $params[] = $status;
}

if ($category !== 'all') {
    $where[] = "category = ?";
    $params[] = $category;
}

if (!empty($search)) {
    $where[] = "(title LIKE ? OR description LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}

$whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// Get total count
$countSql = "SELECT COUNT(*) as total FROM events {$whereClause}";
$stmt = $db->prepare($countSql);
$stmt->execute($params);
$totalItems = $stmt->fetch()['total'];
$totalPages = ceil($totalItems / $perPage);

// Get events list
$sql = "
    SELECT e.*, a.full_name as creator_name
    FROM events e
    JOIN admin_users a ON e.created_by = a.admin_id
    {$whereClause}
    ORDER BY e.event_date DESC
    LIMIT {$perPage} OFFSET {$offset}
";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$eventsList = $stmt->fetchAll();


$pageTitle = 'Events Management';
include '../includes/admin-header.php';
?>

<div class="page-header">
    <h1>Events Management</h1>
    <div class="page-actions">
        <a href="create.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Add New Event
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
                <option value="upcoming" <?php echo $status === 'upcoming' ? 'selected' : ''; ?>>Upcoming</option>
                <option value="ongoing" <?php echo $status === 'ongoing' ? 'selected' : ''; ?>>Ongoing</option>
                <option value="completed" <?php echo $status === 'completed' ? 'selected' : ''; ?>>Completed</option>
                <option value="cancelled" <?php echo $status === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
            </select>
        </div>

        <div class="filter-group">
            <label>Category:</label>
            <select name="category" onchange="this.form.submit()">
                <option value="all" <?php echo $category === 'all' ? 'selected' : ''; ?>>All Categories</option>
                <option value="academic" <?php echo $category === 'academic' ? 'selected' : ''; ?>>Academic</option>
                <option value="sports" <?php echo $category === 'sports' ? 'selected' : ''; ?>>Sports</option>
                <option value="cultural" <?php echo $category === 'cultural' ? 'selected' : ''; ?>>Cultural</option>
                <option value="religious" <?php echo $category === 'religious' ? 'selected' : ''; ?>>Religious</option>
                <option value="seminar" <?php echo $category === 'seminar' ? 'selected' : ''; ?>>Seminar</option>
                <option value="other" <?php echo $category === 'other' ? 'selected' : ''; ?>>Other</option>
            </select>
        </div>

        <div class="filter-group">
            <label>Search:</label>
            <div class="search-box">
                <input type="text" name="search" placeholder="Search events..." value="<?php echo escapeHtml($search); ?>">
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
    <p>Showing <?php echo count($eventsList); ?> of <?php echo number_format($totalItems); ?> events</p>
</div>

<!-- Events Table -->
<div class="content-card">
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 60px;">Image</th>
                    <th>Title</th>
                    <th>Date & Time</th>
                    <th>Location</th>
                    <th>Category</th>
                    <th>Status</th>
                    <th>Created By</th>
                    <th style="width: 150px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($eventsList)): ?>
                <tr>
                    <td colspan="8" class="text-center">
                        <div class="empty-state">
                            <p>No events found</p>
                            <?php if (empty($search) && $status === 'all' && $category === 'all'): ?>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php else: ?>
                    <?php foreach ($eventsList as $event): ?>
                    <tr>
                        <td>
                            <div class="table-image">
                                <?php if (!empty($event['featured_image'])): ?>
                                    <img src="<?php echo escapeHtml(getImageUrl($event['featured_image'])); ?>" 
                                    alt="<?php echo escapeHtml($event['title']); ?>">
                                <?php else: ?>
                                    <div class="no-image"><i class="fas fa-calendar"></i></div>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td>
                            <strong><?php echo escapeHtml($event['title']); ?></strong>
                            <?php if ($event['is_featured']): ?>
                                <span class="badge badge-star">⭐ Featured</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div><i class="fas fa-calendar"></i> <?php echo formatDate($event['event_date'], 'M j, Y'); ?></div>
                            <?php if (!empty($event['event_time'])): ?>
                            <small><i class="fas fa-clock"></i> <?php echo date('g:i A', strtotime($event['event_time'])); ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <small><i class="fas fa-map-marker-alt"></i> <?php echo escapeHtml($event['location']); ?></small>
                        </td>
                        <td>
                            <span class="badge badge-category-<?php echo $event['category']; ?>">
                                <?php echo ucfirst($event['category']); ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge badge-<?php echo $event['status']; ?>">
                                <?php echo ucfirst($event['status']); ?>
                            </span>
                        </td>
                        <td><?php echo escapeHtml($event['creator_name']); ?></td>
                        <td>
                            <div class="action-buttons">
                                <a href="edit.php?id=<?php echo $event['event_id']; ?>" class="btn-icon" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="../../pages/event-detail.php?slug=<?php echo $event['slug']; ?>" class="btn-icon btn-view" title="View" target="_blank">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="delete.php?id=<?php echo $event['event_id']; ?>" class="btn-icon btn-danger" title="Delete">
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

.filter-group select {
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

.filter-group select:hover {
    border-color: #c5cdd8;
}

.filter-group select:focus {
    outline: none;
    border-color: var(--admin-primary);
    box-shadow: 0 0 0 4px rgba(0, 63, 135, 0.08);
}

/* Search Box */
.search-box {
    position: relative;
}

.search-box input {
    width: 100%;
    padding: 0.875rem 3rem 0.875rem 1rem;
    border: 2px solid var(--admin-border);
    border-radius: 8px;
    font-size: 0.95rem;
    transition: all 0.3s;
}

.search-box input:hover {
    border-color: #c5cdd8;
}

.search-box input:focus {
    outline: none;
    border-color: var(--admin-primary);
    box-shadow: 0 0 0 4px rgba(0, 63, 135, 0.08);
}

.search-box button {
    position: absolute;
    right: 0;
    top: 0;
    height: 100%;
    padding: 0 1.25rem;
    background: none;
    border: none;
    color: var(--admin-text-muted);
    cursor: pointer;
    font-size: 1rem;
    transition: all 0.3s;
}

.search-box button:hover {
    color: var(--admin-primary);
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

/* Title Styling */
.data-table td strong {
    display: block;
    font-size: 1rem;
    font-weight: 600;
    color: var(--admin-text);
    margin-bottom: 0.25rem;
}

.data-table td small {
    color: var(--admin-text-muted);
    font-size: 0.85rem;
    display: flex;
    align-items: center;
    gap: 0.35rem;
    margin-top: 0.25rem;
}

.data-table td small i {
    font-size: 0.75rem;
}

.data-table td div {
    display: flex;
    align-items: center;
    gap: 0.35rem;
    margin-bottom: 0.25rem;
}

.data-table td div i {
    font-size: 0.85rem;
    color: var(--admin-primary);
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

.badge-star {
    background: linear-gradient(135deg, #ffd700, #ffed4e);
    color: #856404;
    margin-left: 0.5rem;
    box-shadow: 0 2px 4px rgba(255, 215, 0, 0.3);
}

/* Category Badges */
.badge-category-academic {
    background: linear-gradient(135deg, #d1ecf1, #bee5eb);
    color: #0c5460;
}

.badge-category-sports {
    background: linear-gradient(135deg, #d4edda, #c3e6cb);
    color: #155724;
}

.badge-category-cultural {
    background: linear-gradient(135deg, #f8d7da, #f5c6cb);
    color: #721c24;
}

.badge-category-religious {
    background: linear-gradient(135deg, #d4edda, #c3e6cb);
    color: #155724;
}

.badge-category-seminar {
    background: linear-gradient(135deg, #fff3cd, #ffeaa7);
    color: #856404;
}

.badge-category-other {
    background: linear-gradient(135deg, #e2e3e5, #d6d8db);
    color: #383d41;
}

/* Status Badges */
.badge-upcoming {
    background: linear-gradient(135deg, #d1ecf1, #bee5eb);
    color: #0c5460;
}

.badge-ongoing {
    background: linear-gradient(135deg, #d4edda, #c3e6cb);
    color: #155724;
}

.badge-completed {
    background: linear-gradient(135deg, #e2e3e5, #d6d8db);
    color: #383d41;
}

.badge-cancelled {
    background: linear-gradient(135deg, #f8d7da, #f5c6cb);
    color: #721c24;
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

.btn-icon:hover i {
    color: white;
}

.btn-icon.btn-view:hover {
    background: var(--admin-info);
    box-shadow: 0 4px 8px rgba(23, 162, 184, 0.25);
}

.btn-icon.btn-view:hover i {
    color: white;
}

.btn-icon.btn-danger:hover {
    background: var(--admin-danger);
    box-shadow: 0 4px 8px rgba(220, 53, 69, 0.25);
}

.btn-icon.btn-danger:hover i {
    color: white;
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
