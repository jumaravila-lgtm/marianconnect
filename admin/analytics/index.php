<?php
require_once '../includes/auth-check.php';
$db = getDB();

// Date range filter
$dateRange = $_GET['range'] ?? '7';
$startDate = date('Y-m-d', strtotime("-{$dateRange} days"));

// Total visits
$totalVisits = $db->query("SELECT COUNT(*) FROM visitor_analytics WHERE visited_at >= '{$startDate}'")->fetchColumn();

// Unique visitors (by IP)
$uniqueVisitors = $db->query("SELECT COUNT(DISTINCT ip_address) FROM visitor_analytics WHERE visited_at >= '{$startDate}'")->fetchColumn();

// Page views
$pageViews = $db->query("SELECT COUNT(*) FROM visitor_analytics WHERE visited_at >= '{$startDate}'")->fetchColumn();

// Top pages
$topPages = $db->query("SELECT page_url, COUNT(*) as views FROM visitor_analytics WHERE visited_at >= '{$startDate}' GROUP BY page_url ORDER BY views DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);

// Device breakdown
$devices = $db->query("SELECT device_type, COUNT(*) as count FROM visitor_analytics WHERE visited_at >= '{$startDate}' GROUP BY device_type")->fetchAll(PDO::FETCH_ASSOC);

// Daily visits for chart
$dailyVisits = $db->query("SELECT DATE(visited_at) as date, COUNT(*) as visits FROM visitor_analytics WHERE visited_at >= '{$startDate}' GROUP BY DATE(visited_at) ORDER BY date ASC")->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Analytics';
include '../includes/admin-header.php';
?>

<div class="page-header">
    <h1>Analytics Dashboard</h1>
    <div class="header-actions">
        <a href="reports.php" class="btn btn-secondary"><i class="fas fa-file-alt"></i> Reports</a>
        <a href="visitors.php" class="btn btn-secondary"><i class="fas fa-users"></i> Visitors</a>
    </div>
</div>

<div class="filter-bar">
    <div class="filter-buttons">
        <a href="?range=7" class="filter-btn <?php echo $dateRange === '7' ? 'active' : ''; ?>">Last 7 Days</a>
        <a href="?range=30" class="filter-btn <?php echo $dateRange === '30' ? 'active' : ''; ?>">Last 30 Days</a>
        <a href="?range=90" class="filter-btn <?php echo $dateRange === '90' ? 'active' : ''; ?>">Last 90 Days</a>
    </div>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon" style="background:#e3f2fd;color:#1976d2"><i class="fas fa-eye"></i></div>
        <div class="stat-content">
            <div class="stat-value"><?php echo number_format($totalVisits); ?></div>
            <div class="stat-label">Total Visits</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#e8f5e9;color:#388e3c"><i class="fas fa-users"></i></div>
        <div class="stat-content">
            <div class="stat-value"><?php echo number_format($uniqueVisitors); ?></div>
            <div class="stat-label">Unique Visitors</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#fff3e0;color:#f57c00"><i class="fas fa-file-alt"></i></div>
        <div class="stat-content">
            <div class="stat-value"><?php echo number_format($pageViews); ?></div>
            <div class="stat-label">Page Views</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#f3e5f5;color:#7b1fa2"><i class="fas fa-chart-line"></i></div>
        <div class="stat-content">
            <div class="stat-value"><?php echo $uniqueVisitors > 0 ? number_format($pageViews / $uniqueVisitors, 1) : '0'; ?></div>
            <div class="stat-label">Pages per Visit</div>
        </div>
    </div>
</div>

<div class="charts-grid">
    <div class="chart-card">
        <h3><i class="fas fa-chart-line"></i> Visits Over Time</h3>
        <div class="chart-container">
            <canvas id="visitsChart"></canvas>
        </div>
    </div>
    
    <div class="chart-card">
        <h3><i class="fas fa-mobile-alt"></i> Device Breakdown</h3>
        <div class="chart-container">
            <canvas id="devicesChart"></canvas>
        </div>
    </div>
</div>

<div class="table-card">
    <h3><i class="fas fa-fire"></i> Top Pages</h3>
    <table class="data-table">
        <thead>
            <tr>
                <th>Page URL</th>
                <th style="width:100px;text-align:right">Views</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($topPages)): ?>
            <tr>
                <td colspan="2" class="text-center">No data available</td>
            </tr>
            <?php else: ?>
                <?php foreach ($topPages as $page): ?>
                <tr>
                    <td><code><?php echo escapeHtml($page['page_url']); ?></code></td>
                    <td style="text-align:right"><strong><?php echo number_format($page['views']); ?></strong></td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<style>
.page-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:2rem}
.header-actions{display:flex;gap:0.75rem}
.filter-bar{background:white;border-radius:12px;padding:1rem;margin-bottom:2rem;box-shadow:var(--admin-shadow)}
.filter-buttons{display:flex;gap:0.5rem}
.filter-btn{padding:0.5rem 1.25rem;border:2px solid var(--admin-border);border-radius:8px;text-decoration:none;color:var(--admin-text);font-weight:500;transition:all 0.2s}
.filter-btn:hover{border-color:var(--admin-primary);background:#e3f2fd}
.filter-btn.active{background:var(--admin-primary);color:white;border-color:var(--admin-primary)}
.stats-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:1.5rem;margin-bottom:2rem}
.stat-card{background:white;border-radius:12px;padding:1.5rem;box-shadow:var(--admin-shadow);display:flex;gap:1rem;align-items:center}
.stat-icon{width:60px;height:60px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.5rem}
.stat-value{font-size:2rem;font-weight:700;color:var(--admin-text)}
.stat-label{font-size:0.9rem;color:var(--admin-text-muted)}
.charts-grid{display:grid;grid-template-columns:2fr 1fr;gap:1.5rem;margin-bottom:2rem}
.chart-card{background:white;border-radius:12px;padding:1.5rem;box-shadow:var(--admin-shadow)}
.chart-card h3{font-size:1.1rem;font-weight:600;margin-bottom:1.5rem;display:flex;align-items:center;gap:0.5rem;color:var(--admin-text)}
.chart-container{position:relative;height:300px;width:100%}
.table-card{background:white;border-radius:12px;padding:1.5rem;box-shadow:var(--admin-shadow)}
.table-card h3{font-size:1.1rem;font-weight:600;margin-bottom:1.5rem;display:flex;align-items:center;gap:0.5rem;color:var(--admin-text)}
.data-table{width:100%;border-collapse:collapse}
.data-table th{background:#f8f9fa;padding:1rem;text-align:left;font-weight:600;border-bottom:2px solid var(--admin-border)}
.data-table td{padding:1rem;border-bottom:1px solid var(--admin-border)}
.data-table code{background:#f5f5f5;padding:0.25rem 0.5rem;border-radius:4px;font-size:0.9rem}
.text-center{text-align:center;padding:2rem;color:var(--admin-text-muted)}
.btn{padding:0.6rem 1.25rem;border:none;border-radius:8px;font-weight:500;cursor:pointer;display:inline-flex;align-items:center;gap:0.5rem;text-decoration:none;transition:all 0.2s}
.btn-secondary{background:#6c757d;color:white}
.btn-secondary:hover{background:#5a6268}
@media(max-width:1024px){.charts-grid{grid-template-columns:1fr}}
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
<script>
// Visits Over Time Chart
const visitsCtx = document.getElementById('visitsChart');
if (visitsCtx) {
    new Chart(visitsCtx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode(array_column($dailyVisits, 'date')); ?>,
            datasets: [{
                label: 'Visits',
                data: <?php echo json_encode(array_map('intval', array_column($dailyVisits, 'visits'))); ?>,
                borderColor: '#1976d2',
                backgroundColor: 'rgba(25, 118, 210, 0.1)',
                tension: 0.4,
                fill: true,
                pointRadius: 3,
                pointHoverRadius: 5,
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: {
                duration: 750
            },
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: { 
                    beginAtZero: true,
                    ticks: { precision: 0 }
                }
            }
        }
    });
}

// Device Breakdown Chart
const devicesCtx = document.getElementById('devicesChart');
if (devicesCtx) {
    new Chart(devicesCtx, {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode(array_map('ucfirst', array_column($devices, 'device_type'))); ?>,
            datasets: [{
                data: <?php echo json_encode(array_map('intval', array_column($devices, 'count'))); ?>,
                backgroundColor: ['#1976d2', '#388e3c', '#f57c00'],
                borderWidth: 2,
                borderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: {
                duration: 750
            },
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { padding: 15 }
                }
            }
        }
    });
}
</script>

<?php include '../includes/admin-footer.php'; ?>
