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
        <div class="stat-icon" style="background:#e3f2fd;color:#1976d2"><i class="fas fa-file-alt"></i></div>
        <div class="stat-content">
            <div class="stat-value"><?php echo $stats['total']; ?></div>
            <div class="stat-label">Total Pages</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#e8f5e9;color:#388e3c"><i class="fas fa-check-circle"></i></div>
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
        <div class="page-icon">
            <?php
            $icons = [
                'about' => 'fas fa-info-circle',
                'mission_vision' => 'fas fa-bullseye',
                'history' => 'fas fa-history',
                'administration' => 'fas fa-users-cog',
                'contact' => 'fas fa-envelope',
                'custom' => 'fas fa-file'
            ];
            ?>
            <i class="<?php echo $icons[$page['page_type']] ?? 'fas fa-file'; ?>"></i>
        </div>
        
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
.page-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:2rem}
.stats-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:1.5rem;margin-bottom:2rem}
.stat-card{background:white;border-radius:12px;padding:1.5rem;box-shadow:var(--admin-shadow);display:flex;gap:1rem;align-items:center}
.stat-icon{width:60px;height:60px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.5rem}
.stat-value{font-size:2rem;font-weight:700;color:var(--admin-text)}
.stat-label{font-size:0.9rem;color:var(--admin-text-muted)}
.info-box{background:#e3f2fd;border-left:4px solid #1976d2;padding:1rem 1.5rem;border-radius:8px;margin-bottom:2rem;display:flex;gap:1rem;align-items:center}
.info-box i{color:#1976d2;font-size:1.5rem}
.pages-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(350px,1fr));gap:1.5rem}
.page-card{background:white;border-radius:12px;padding:1.5rem;box-shadow:var(--admin-shadow);display:flex;flex-direction:column;gap:1rem;transition:transform 0.2s}
.page-card:hover{transform:translateY(-4px);box-shadow:0 8px 16px rgba(0,0,0,0.1)}
.page-card.unpublished{opacity:0.7;border:2px dashed #ff9800}
.page-icon{width:60px;height:60px;background:#e3f2fd;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.5rem;color:#1976d2}
.page-content{flex:1}
.page-header-inline{display:flex;align-items:center;gap:1rem;margin-bottom:0.75rem}
.page-title{font-size:1.25rem;font-weight:600;margin:0}
.page-meta{display:flex;flex-direction:column;gap:0.5rem;margin-bottom:1rem;font-size:0.85rem}
.meta-item{color:var(--admin-text-muted);display:flex;align-items:center;gap:0.5rem}
.meta-item strong{color:var(--admin-text)}
.page-excerpt{color:var(--admin-text-muted);line-height:1.5;margin:0}
.badge{display:inline-block;padding:0.35rem 0.75rem;border-radius:6px;font-size:0.8rem;font-weight:500}
.badge-published{background:#e8f5e9;color:#2e7d32}
.badge-draft{background:#fff3e0;color:#f57c00}
.page-actions{display:flex;gap:0.75rem}
.btn{padding:0.75rem 1.5rem;border:none;border-radius:8px;font-weight:500;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;gap:0.5rem;text-decoration:none;transition:all 0.2s;width:100%}
.btn-primary{background:var(--admin-primary);color:white}
.btn-primary:hover{background:#004a99;transform:translateY(-2px)}
@media(max-width:768px){.pages-grid{grid-template-columns:1fr}}
</style>

<?php include '../includes/admin-footer.php'; ?>
