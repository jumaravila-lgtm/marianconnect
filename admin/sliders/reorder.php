<?php
require_once '../includes/auth-check.php';
$db = getDB();

// Handle AJAX reorder request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order'])) {
    header('Content-Type: application/json');
    
    $order = $_POST['order'];
    
    try {
        $db->beginTransaction();
        
        foreach ($order as $position => $sliderId) {
            $stmt = $db->prepare("UPDATE homepage_sliders SET display_order = ? WHERE slider_id = ?");
            $stmt->execute([$position, $sliderId]);
        }
        
        $db->commit();
        logActivity($_SESSION['admin_id'], 'update', 'homepage_sliders', null, "Reordered sliders");
        
        echo json_encode(['success' => true]);
        exit;
    } catch (PDOException $e) {
        $db->rollBack();
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
}

// Get all sliders
$sql = "SELECT * FROM homepage_sliders ORDER BY display_order ASC, slider_id ASC";
$stmt = $db->query($sql);
$sliders = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Reorder Sliders';
include '../includes/admin-header.php';
?>

<div class="page-header">
    <h1>Reorder Sliders</h1>
    <a href="index.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back</a>
</div>

<div class="info-box">
    <i class="fas fa-info-circle"></i>
    <div>
        <strong>How to reorder:</strong> Drag and drop the sliders to change their display order. Changes are saved automatically.
    </div>
</div>

<div class="reorder-container">
    <div id="sortable-sliders" class="sortable-list">
        <?php foreach ($sliders as $slider): ?>
        <div class="sortable-item" data-id="<?php echo $slider['slider_id']; ?>">
            <div class="drag-handle">
                <i class="fas fa-grip-vertical"></i>
            </div>
            
            <div class="slider-thumb">
                <img src="<?php echo escapeHtml($slider['image_path']); ?>" alt="">
            </div>
            
            <div class="slider-info">
                <h3><?php echo escapeHtml($slider['title']); ?></h3>
                <div class="slider-meta">
                    <span class="order-badge">Order: <?php echo $slider['display_order']; ?></span>
                    <?php if ($slider['is_active']): ?>
                        <span class="badge badge-active">Active</span>
                    <?php else: ?>
                        <span class="badge badge-inactive">Inactive</span>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="slider-actions">
                <a href="edit.php?id=<?php echo $slider['slider_id']; ?>" class="btn btn-sm btn-edit">
                    <i class="fas fa-edit"></i> Edit
                </a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<div id="save-message" class="save-message" style="display:none">
    <i class="fas fa-check-circle"></i> Order saved successfully!
</div>

<style>
.page-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:2rem}
.info-box{background:#e3f2fd;border-left:4px solid #1976d2;padding:1rem 1.5rem;border-radius:8px;margin-bottom:2rem;display:flex;gap:1rem;align-items:center}
.info-box i{color:#1976d2;font-size:1.5rem}
.reorder-container{background:white;border-radius:12px;padding:2rem;box-shadow:var(--admin-shadow)}
.sortable-list{display:flex;flex-direction:column;gap:1rem}
.sortable-item{background:#f8f9fa;border:2px solid var(--admin-border);border-radius:12px;padding:1rem;display:flex;align-items:center;gap:1rem;cursor:move;transition:all 0.2s}
.sortable-item:hover{border-color:var(--admin-primary);box-shadow:0 4px 8px rgba(0,0,0,0.1)}
.sortable-item.dragging{opacity:0.5;transform:scale(0.95)}
.drag-handle{color:var(--admin-text-muted);font-size:1.5rem;cursor:grab;padding:0 0.5rem}
.drag-handle:active{cursor:grabbing}
.slider-thumb{width:120px;height:60px;border-radius:8px;overflow:hidden;flex-shrink:0}
.slider-thumb img{width:100%;height:100%;object-fit:cover}
.slider-info{flex:1}
.slider-info h3{font-size:1rem;font-weight:600;margin-bottom:0.5rem}
.slider-meta{display:flex;gap:0.75rem;align-items:center}
.order-badge{background:#e3f2fd;color:#1976d2;padding:0.25rem 0.75rem;border-radius:20px;font-size:0.85rem;font-weight:600}
.badge{display:inline-block;padding:0.25rem 0.75rem;border-radius:20px;font-size:0.85rem;font-weight:500}
.badge-active{background:#e8f5e9;color:#2e7d32}
.badge-inactive{background:#ffebee;color:#c62828}
.slider-actions{display:flex;gap:0.5rem}
.btn{padding:0.6rem 1.25rem;border:none;border-radius:8px;font-weight:500;cursor:pointer;display:inline-flex;align-items:center;gap:0.5rem;text-decoration:none;transition:all 0.2s}
.btn-sm{padding:0.5rem 1rem;font-size:0.9rem}
.btn-edit{background:#e3f2fd;color:#1976d2}
.btn-edit:hover{background:#1976d2;color:white}
.btn-secondary{background:#6c757d;color:white}
.btn-secondary:hover{background:#5a6268}
.save-message{position:fixed;top:20px;right:20px;background:#4caf50;color:white;padding:1rem 1.5rem;border-radius:8px;box-shadow:0 4px 8px rgba(0,0,0,0.2);display:flex;align-items:center;gap:0.5rem;font-weight:500;z-index:1000;animation:slideIn 0.3s ease-out}
@keyframes slideIn{from{transform:translateX(100%);opacity:0}to{transform:translateX(0);opacity:1}}
</style>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const el = document.getElementById('sortable-sliders');
    
    const sortable = new Sortable(el, {
        animation: 150,
        handle: '.drag-handle',
        ghostClass: 'dragging',
        onEnd: function(evt) {
            // Get new order
            const items = el.querySelectorAll('.sortable-item');
            const order = [];
            
            items.forEach((item, index) => {
                order.push(item.dataset.id);
                // Update order badge
                const orderBadge = item.querySelector('.order-badge');
                orderBadge.textContent = 'Order: ' + index;
            });
            
            // Save order via AJAX
            fetch('reorder.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'order=' + JSON.stringify(order)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showSaveMessage();
                } else {
                    alert('Failed to save order: ' + (data.error || 'Unknown error'));
                }
            })
            .catch(error => {
                alert('Error saving order: ' + error);
            });
        }
    });
});

function showSaveMessage() {
    const msg = document.getElementById('save-message');
    msg.style.display = 'flex';
    
    setTimeout(() => {
        msg.style.display = 'none';
    }, 3000);
}
</script>

<?php include '../includes/admin-footer.php'; ?>
