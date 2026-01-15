<?php
require_once '../includes/auth-check.php';
$db = getDB();

$sql = "SELECT * FROM homepage_sliders ORDER BY display_order ASC, created_at DESC";
$stmt = $db->query($sql);
$sliders = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stats = [
    'total' => $db->query("SELECT COUNT(*) FROM homepage_sliders")->fetchColumn(),
    'active' => $db->query("SELECT COUNT(*) FROM homepage_sliders WHERE is_active = 1")->fetchColumn(),
];

$pageTitle = 'Homepage Sliders';
include '../includes/admin-header.php';
?>

<div class="page-header">
    <h1>Homepage Sliders</h1>
    <a href="create.php" class="btn btn-primary"><i class="fas fa-plus"></i> Add Slider</a>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon" style="background:#e3f2fd;color:#1976d2"><i class="fas fa-images"></i></div>
        <div class="stat-content">
            <div class="stat-value"><?php echo $stats['total']; ?></div>
            <div class="stat-label">Total Sliders</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#e8f5e9;color:#388e3c"><i class="fas fa-check-circle"></i></div>
        <div class="stat-content">
            <div class="stat-value"><?php echo $stats['active']; ?></div>
            <div class="stat-label">Active</div>
        </div>
    </div>
</div>

<div class="info-box">
    <i class="fas fa-info-circle"></i>
    <div>
        <strong>Display Order:</strong> Lower numbers appear first. 
        <a href="reorder.php" style="color:#1976d2;font-weight:600;text-decoration:underline">Click here to reorder sliders</a>
    </div>
</div>

<div class="sliders-grid">
    <?php if (empty($sliders)): ?>
    <div class="empty-state">
        <i class="fas fa-images"></i>
        <h3>No sliders found</h3>
        <p>Add your first homepage slider to get started</p>
    </div>
    <?php else: ?>
        <?php foreach ($sliders as $slider): ?>
        <div class="slider-card <?php echo !$slider['is_active'] ? 'inactive' : ''; ?>">
            <div class="slider-image">
                <img src="<?php echo escapeHtml(getImageUrl($slider['image_path'])); ?>" alt="<?php echo escapeHtml($slider['title']); ?>">
                <div class="slider-overlay">
                    <a href="edit.php?id=<?php echo $slider['slider_id']; ?>" class="overlay-btn">
                        <i class="fas fa-edit"></i> Edit
                    </a>
                    <a href="delete.php?id=<?php echo $slider['slider_id']; ?>" class="overlay-btn btn-delete-overlay">
                        <i class="fas fa-trash"></i> Delete
                    </a>
                </div>
                <?php if (!$slider['is_active']): ?>
                    <div class="inactive-badge">Inactive</div>
                <?php endif; ?>
            </div>
            
            <div class="slider-info">
                <div class="slider-order">Order: <?php echo $slider['display_order']; ?></div>
                <h3 class="slider-title"><?php echo escapeHtml($slider['title']); ?></h3>
                <?php if ($slider['subtitle']): ?>
                    <p class="slider-subtitle"><?php echo escapeHtml(substr($slider['subtitle'], 0, 80)); ?><?php echo strlen($slider['subtitle']) > 80 ? '...' : ''; ?></p>
                <?php endif; ?>
                
                <?php if ($slider['button_text']): ?>
                    <div class="slider-button-info">
                        <span class="badge badge-button">
                            <i class="fas fa-mouse-pointer"></i> <?php echo escapeHtml($slider['button_text']); ?>
                        </span>
                    </div>
                <?php endif; ?>
                
                <div class="slider-meta">
                    <span class="meta-item">
                        <i class="fas fa-calendar"></i> 
                        <?php echo date('M d, Y', strtotime($slider['created_at'])); ?>
                    </span>
                    <span class="meta-item status-<?php echo $slider['is_active'] ? 'active' : 'inactive'; ?>">
                        <i class="fas fa-circle"></i> 
                        <?php echo $slider['is_active'] ? 'Active' : 'Inactive'; ?>
                    </span>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
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
.sliders-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(400px,1fr));gap:1.5rem}
.slider-card{background:white;border-radius:12px;overflow:hidden;box-shadow:var(--admin-shadow);transition:transform 0.2s}
.slider-card:hover{transform:translateY(-4px);box-shadow:0 8px 16px rgba(0,0,0,0.1)}
.slider-card.inactive{opacity:0.6}
.slider-image{position:relative;height:250px;overflow:hidden;background:#f0f0f0}
.slider-image img{width:100%;height:100%;object-fit:cover;transition:transform 0.3s}
.slider-card:hover .slider-image img{transform:scale(1.05)}
.slider-overlay{position:absolute;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.7);display:flex;align-items:center;justify-content:center;gap:1rem;opacity:0;transition:opacity 0.3s}
.slider-card:hover .slider-overlay{opacity:1}
.overlay-btn{padding:0.75rem 1.5rem;background:white;color:var(--admin-text);border-radius:8px;text-decoration:none;font-weight:500;transition:all 0.2s}
.overlay-btn:hover{background:var(--admin-primary);color:white;transform:scale(1.05)}
.btn-delete-overlay:hover{background:#dc3545;color:white}
.inactive-badge{position:absolute;top:10px;right:10px;background:#f44336;color:white;padding:0.5rem 1rem;border-radius:20px;font-size:0.85rem;font-weight:600}
.slider-info{padding:1.5rem}
.slider-order{display:inline-block;background:#e3f2fd;color:#1976d2;padding:0.25rem 0.75rem;border-radius:20px;font-size:0.85rem;font-weight:600;margin-bottom:0.75rem}
.slider-title{font-size:1.25rem;font-weight:600;margin-bottom:0.5rem;color:var(--admin-text)}
.slider-subtitle{color:var(--admin-text-muted);margin-bottom:1rem;line-height:1.5}
.slider-button-info{margin-bottom:1rem}
.badge-button{background:#fff3e0;color:#f57c00;padding:0.5rem 1rem;border-radius:6px;font-size:0.85rem;font-weight:500}
.slider-meta{display:flex;gap:1rem;flex-wrap:wrap;padding-top:1rem;border-top:1px solid var(--admin-border)}
.meta-item{font-size:0.85rem;color:var(--admin-text-muted);display:flex;align-items:center;gap:0.5rem}
.meta-item.status-active{color:#4caf50}
.meta-item.status-inactive{color:#f44336}
.empty-state{grid-column:1/-1;text-align:center;padding:4rem 2rem;background:white;border-radius:12px;box-shadow:var(--admin-shadow)}
.empty-state i{font-size:4rem;color:var(--admin-text-muted);margin-bottom:1rem}
.empty-state h3{font-size:1.5rem;margin-bottom:0.5rem;color:var(--admin-text)}
.empty-state p{color:var(--admin-text-muted);margin-bottom:1.5rem}
.btn{padding:0.6rem 1.25rem;border:none;border-radius:8px;font-weight:500;cursor:pointer;display:inline-flex;align-items:center;gap:0.5rem;text-decoration:none;transition:all 0.2s}
.btn-primary{background:var(--admin-primary);color:white}
.btn-primary:hover{background:#004a99;transform:translateY(-2px)}
@media(max-width:768px){.sliders-grid{grid-template-columns:1fr}}
</style>

<?php include '../includes/admin-footer.php'; ?>
