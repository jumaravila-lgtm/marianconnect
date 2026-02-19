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
        <div class="stat-content">
            <div class="stat-value"><?php echo $stats['total']; ?></div>
            <div class="stat-label">Total Sliders</div>
        </div>
    </div>
    <div class="stat-card">
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

/* ─── STAT CARDS ─── */
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

/* ─── INFO BOX ─── */
.info-box {
    background: white;
    border-left: 4px solid var(--admin-primary);
    padding: 0.9rem 1.25rem;
    border-radius: 0 8px 8px 0;
    margin-bottom: 1.75rem;
    display: flex;
    gap: 0.85rem;
    align-items: center;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
    border-top: 1px solid var(--admin-border);
    border-right: 1px solid var(--admin-border);
    border-bottom: 1px solid var(--admin-border);
    font-size: 0.88rem;
    color: var(--admin-text);
}
.info-box i {
    color: var(--admin-primary);
    font-size: 1.1rem;
    flex-shrink: 0;
}
.info-box a {
    color: var(--admin-primary);
    font-weight: 600;
    text-decoration: none;
}
.info-box a:hover {
    text-decoration: underline;
}

/* ─── SLIDERS GRID ─── */
.sliders-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 1.5rem;
}

/* ─── SLIDER CARD ─── */
.slider-card {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
    border: 1px solid var(--admin-border);
    transition: box-shadow 0.2s, transform 0.2s;
    display: flex;
    flex-direction: column;
}
.slider-card:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    transform: translateY(-2px);
}
.slider-card.inactive {
    opacity: 0.55;
}

/* ─── SLIDER IMAGE + OVERLAY ─── */
.slider-image {
    position: relative;
    height: 200px;
    overflow: hidden;
    background: #eef1f4;
}
.slider-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.35s;
}
.slider-card:hover .slider-image img {
    transform: scale(1.04);
}
.slider-overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(to top, rgba(0, 0, 0, 0.6) 0%, transparent 55%);
    display: flex;
    align-items: flex-end;
    justify-content: flex-end;
    gap: 0.5rem;
    padding: 0.85rem;
    opacity: 0;
    transition: opacity 0.25s;
}
.slider-card:hover .slider-overlay {
    opacity: 1;
}
.overlay-btn {
    padding: 0.45rem 0.85rem;
    background: white;
    color: var(--admin-text);
    border-radius: 6px;
    text-decoration: none;
    font-size: 0.78rem;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15);
    transition: background 0.2s, color 0.2s, transform 0.15s;
}
.overlay-btn:hover {
    background: var(--admin-primary);
    color: white;
    transform: translateY(-1px);
}
.btn-delete-overlay:hover {
    background: #dc3545;
    color: white;
}

/* ─── INACTIVE BADGE (on image) ─── */
.inactive-badge {
    position: absolute;
    top: 10px;
    right: 10px;
    background: white;
    color: var(--admin-text);
    border: 2px solid var(--admin-border);
    padding: 0.2rem 0.6rem;
    border-radius: 6px;
    font-size: 0.72rem;
    font-weight: 600;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    text-transform: uppercase;
    letter-spacing: 0.3px;
}

/* ─── SLIDER INFO ─── */
.slider-info {
    padding: 1.1rem 1.1rem 1.25rem;
    flex: 1;
    display: flex;
    flex-direction: column;
}

/* ─── ORDER TAG ─── */
.slider-order {
    display: inline-block;
    background: white;
    color: var(--admin-text);
    border: 2px solid var(--admin-border);
    padding: 0.2rem 0.6rem;
    border-radius: 6px;
    font-size: 0.72rem;
    font-weight: 600;
    margin-bottom: 0.6rem;
    width: fit-content;
    text-transform: uppercase;
    letter-spacing: 0.3px;
}

/* ─── TITLE + SUBTITLE ─── */
.slider-title {
    font-size: 1rem;
    font-weight: 600;
    color: var(--admin-text);
    margin: 0 0 0.35rem;
    line-height: 1.35;
}
.slider-subtitle {
    font-size: 0.82rem;
    color: var(--admin-text-muted);
    margin: 0 0 0.75rem;
    line-height: 1.5;
}

/* ─── BUTTON BADGE ─── */
.slider-button-info {
    margin-bottom: 0.75rem;
}
.badge-button {
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
    background: white;
    color: var(--admin-text);
    border: 2px solid var(--admin-border);
    padding: 0.25rem 0.6rem;
    border-radius: 6px;
    font-size: 0.75rem;
    font-weight: 600;
}

/* ─── META ROW ─── */
.slider-meta {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
    padding-top: 0.75rem;
    border-top: 1px solid var(--admin-border);
    margin-top: auto;
}
.meta-item {
    font-size: 0.78rem;
    color: var(--admin-text-muted);
    display: flex;
    align-items: center;
    gap: 0.35rem;
}
.meta-item i {
    font-size: 0.7rem;
}
.meta-item.status-active {
    color: #16a34a;
}
.meta-item.status-active i {
    font-size: 0.55rem;
}
.meta-item.status-inactive {
    color: #dc3545;
}
.meta-item.status-inactive i {
    font-size: 0.55rem;
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
    margin: 0 0 0.4rem;
}
.empty-state p {
    font-size: 0.88rem;
    color: var(--admin-text-muted);
    margin: 0;
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
    .sliders-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<?php include '../includes/admin-footer.php'; ?>
