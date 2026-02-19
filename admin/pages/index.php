<?php
require_once '../includes/auth-check.php';
$db = getDB();

$sql = "SELECT p.*, a.full_name as updated_by_name 
        FROM pages p 
        LEFT JOIN admin_users a ON p.updated_by = a.admin_id 
        ORDER BY p.display_order ASC, p.page_type ASC";
$stmt = $db->query($sql);
$pages = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stats = [
    'total' => $db->query("SELECT COUNT(*) FROM pages")->fetchColumn(),
    'published' => $db->query("SELECT COUNT(*) FROM pages WHERE is_published = 1")->fetchColumn(),
];

$pageTitle = 'Pages';
include '../includes/admin-header.php';
?>

<div class="page-header">
    <h1>Pages</h1>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-content">
            <div class="stat-value"><?php echo $stats['total']; ?></div>
            <div class="stat-label">Total Pages</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-content">
            <div class="stat-value"><?php echo $stats['published']; ?></div>
            <div class="stat-label">Published</div>
        </div>
    </div>
</div>

<div class="info-box">
    <i class="fas fa-info-circle"></i>
    <div>
        <strong>About Pages:</strong> These are the core pages of your website. Edit the content to update what visitors see.
    </div>
</div>

<div class="pages-grid">
    <?php foreach ($pages as $page): ?>
    <div class="page-card <?php echo !$page['is_published'] ? 'unpublished' : ''; ?>">
    
        <div class="page-content">
            <div class="page-header-inline">
                <h3 class="page-title"><?php echo escapeHtml($page['title']); ?></h3>
                <?php if (!$page['is_published']): ?>
                    <span class="badge badge-draft">Draft</span>
                <?php else: ?>
                    <span class="badge badge-published">Published</span>
                <?php endif; ?>
            </div>
            
            <div class="page-meta">
                <span class="meta-item">
                    <i class="fas fa-link"></i> 
                    /<strong><?php echo escapeHtml($page['slug']); ?></strong>
                </span>
                
                <?php if ($page['updated_by_name']): ?>
                <span class="meta-item">
                    <i class="fas fa-user"></i> 
                    Last edited by <?php echo escapeHtml($page['updated_by_name']); ?>
                </span>
                <?php endif; ?>
                
                <span class="meta-item">
                    <i class="fas fa-calendar"></i> 
                    <?php echo date('M d, Y', strtotime($page['updated_at'])); ?>
                </span>
            </div>
            
            <?php if ($page['meta_description']): ?>
            <p class="page-excerpt"><?php echo escapeHtml(substr($page['meta_description'], 0, 120)); ?><?php echo strlen($page['meta_description']) > 120 ? '...' : ''; ?></p>
            <?php endif; ?>
        </div>
        
        <div class="page-actions">
            <a href="edit.php?id=<?php echo $page['page_id']; ?>" class="btn btn-primary">
                <i class="fas fa-edit"></i> Edit Page
            </a>
        </div>
    </div>
    <?php endforeach; ?>
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

/* Info Box */
.info-box {
    background: linear-gradient(135deg, #e3f2fd 0%, #f8fafd 100%);
    border-left: 4px solid var(--admin-primary);
    padding: 1.25rem 1.5rem;
    border-radius: 8px;
    margin-bottom: 2rem;
    display: flex;
    gap: 1rem;
    align-items: center;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
}

.info-box i {
    color: var(--admin-primary);
    font-size: 1.5rem;
    flex-shrink: 0;
}

.info-box strong {
    color: var(--admin-text);
    font-weight: 600;
}

.info-box div {
    color: var(--admin-text-muted);
    line-height: 1.5;
}

/* Pages Grid */
.pages-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 1.5rem;
}

/* Page Card */
.page-card {
    background: white;
    border-radius: 12px;
    padding: 1.75rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
    border: 1px solid var(--admin-border);
    display: flex;
    flex-direction: column;
    gap: 1.25rem;
    transition: all 0.3s;
}

.page-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
}

.page-card.unpublished {
    opacity: 0.75;
    border: 2px dashed #ff9800;
    background: #fffbf5;
}

/* Page Content */
.page-content {
    flex: 1;
}

.page-header-inline {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    margin-bottom: 1rem;
}

.page-title {
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--admin-text);
    margin: 0;
    line-height: 1.2;
}

/* Page Meta */
.page-meta {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    margin-bottom: 1rem;
    font-size: 0.875rem;
}

.meta-item {
    color: var(--admin-text-muted);
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.meta-item i {
    width: 16px;
    font-size: 0.85rem;
    color: #9ca3af;
}

.meta-item strong {
    color: var(--admin-primary);
    font-weight: 600;
}

/* Page Excerpt */
.page-excerpt {
    color: var(--admin-text-muted);
    line-height: 1.6;
    margin: 0;
    font-size: 0.9rem;
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

.badge-published {
    background: linear-gradient(135deg, #e8f5e9, #c8e6c9);
    color: #2e7d32;
}

.badge-draft {
    background: linear-gradient(135deg, #fff3e0, #ffe0b2);
    color: #e65100;
}

/* Page Actions */
.page-actions {
    display: flex;
    gap: 0.75rem;
}

/* Buttons */
.btn {
    padding: 0.875rem 1.75rem;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    font-size: 0.95rem;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    text-decoration: none;
    transition: all 0.3s;
    width: 100%;
    box-shadow: 0 2px 4px rgba(0, 63, 135, 0.15);
}

.btn-primary {
    background: var(--admin-primary);
    color: white;
}

.btn-primary:hover {
    background: var(--admin-primary-dark);
    transform: translateY(-2px);
    box-shadow: 0 6px 12px rgba(0, 63, 135, 0.25);
}

/* Responsive */
@media (max-width: 1024px) {
    .pages-grid {
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    }
}

@media (max-width: 768px) {
    .page-header {
        flex-direction: column;
        gap: 1rem;
        align-items: flex-start;
    }
    
    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    .pages-grid {
        grid-template-columns: 1fr;
    }
    
    .page-header-inline {
        flex-direction: column;
        align-items: flex-start;
    }
}
</style>

<?php include '../includes/admin-footer.php'; ?>
