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
        <div class="stat-content">
            <div class="stat-value"><?php echo number_format($totalVisits); ?></div>
            <div class="stat-label">Total Visits</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-content">
            <div class="stat-value"><?php echo number_format($uniqueVisitors); ?></div>
            <div class="stat-label">Unique Visitors</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-content">
            <div class="stat-value"><?php echo number_format($pageViews); ?></div>
            <div class="stat-label">Page Views</div>
        </div>
    </div>
    <div class="stat-card">
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

.header-actions {
    display: flex;
    gap: 0.75rem;
}

.btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.875rem 1.75rem;
    border: none;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 600;
    font-size: 0.95rem;
    transition: all 0.3s;
    cursor: pointer;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.btn-secondary {
    background: white;
    color: var(--admin-text);
    border: 2px solid var(--admin-border);
}

.btn-secondary:hover {
    background: var(--admin-primary);
    color: white;
    border-color: var(--admin-primary);
    transform: translateY(-2px);
    box-shadow: 0 6px 12px rgba(0, 63, 135, 0.25);
}

/* Filter Bar */
.filter-bar {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    margin-bottom: 2rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
    border: 1px solid var(--admin-border);
}

.filter-buttons {
    display: flex;
    gap: 0.75rem;
}

.filter-btn {
    padding: 0.75rem 1.5rem;
    border: 2px solid var(--admin-border);
    border-radius: 8px;
    text-decoration: none;
    color: var(--admin-text);
    font-weight: 600;
    font-size: 0.95rem;
    transition: all 0.3s;
}

.filter-btn:hover {
    border-color: var(--admin-primary);
    background: #f0f4ff;
    color: var(--admin-primary);
}

.filter-btn.active {
    background: var(--admin-primary);
    color: white;
    border-color: var(--admin-primary);
    box-shadow: 0 2px 8px rgba(0, 63, 135, 0.25);
}

/* Stats Grid - Clean Design */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
    margin-bottom: 3rem;
}

.stat-card {
    background: white;
    border-radius: 12px;
    padding: 2rem 1.5rem;
    border-left: 4px solid var(--admin-primary);
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
    border: 1px solid var(--admin-border);
    transition: all 0.3s;
    overflow: hidden;
}

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
}

.stat-card:nth-child(1) { border-left-color: #1976d2; }
.stat-card:nth-child(2) { border-left-color: #388e3c; }
.stat-card:nth-child(3) { border-left-color: #f57c00; }
.stat-card:nth-child(4) { border-left-color: #7b1fa2; }

.stat-content {
    display: flex;
    flex-direction: column;
}

.stat-value {
    font-size: 2.25rem; 
    font-weight: 700;
    color: var(--admin-primary);
    margin-bottom: 0.5rem;
    line-height: 1.2; 
    word-break: break-word; 
}

.stat-label {
    color: var(--admin-text-muted);
    margin: 0;
    font-size: 0.95rem;
    font-weight: 500;
}

/* Charts Grid */
.charts-grid {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.chart-card {
    background: white;
    border-radius: 12px;
    padding: 2rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
    border: 1px solid var(--admin-border);
}

.chart-card h3 {
    font-size: 1.1rem;
    font-weight: 700;
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    color: var(--admin-text);
    padding-bottom: 1rem;
    border-bottom: 2px solid var(--admin-border);
}

.chart-card h3::before {
    content: '';
    width: 4px;
    height: 20px;
    background: var(--admin-primary);
    border-radius: 2px;
}

.chart-container {
    position: relative;
    height: 300px;
    width: 100%;
}

/* Table Card */
.table-card {
    background: white;
    border-radius: 12px;
    padding: 2rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
    border: 1px solid var(--admin-border);
    overflow: hidden;
}

.table-card h3 {
    font-size: 1.1rem;
    font-weight: 700;
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    color: var(--admin-text);
    padding-bottom: 1rem;
    border-bottom: 2px solid var(--admin-border);
}

.table-card h3::before {
    content: '';
    width: 4px;
    height: 20px;
    background: var(--admin-primary);
    border-radius: 2px;
}

/* Data Table */
.data-table {
    width: 100%;
    border-collapse: collapse;
    background: white;
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
}

.data-table td {
    padding: 1.25rem 1rem;
    border-bottom: 1px solid var(--admin-border);
    background: transparent;
}

.data-table tbody tr {
    transition: all 0.3s;
    background: white;
}

.data-table tbody tr:hover {
    background: #f8f9fa;
}

.data-table code {
    background: #f5f5f5;
    padding: 0.5rem 0.75rem;
    border-radius: 6px;
    font-size: 0.9rem;
    color: var(--admin-primary);
    font-family: 'Courier New', monospace;
    border: 1px solid var(--admin-border);
}

.text-center {
    text-align: center;
    padding: 3rem 2rem;
    color: var(--admin-text-muted);
    font-size: 1.05rem;
}

/* Responsive */
@media (max-width: 1024px) {
    .charts-grid {
        grid-template-columns: 1fr;
    }
    
    .filter-buttons {
        flex-wrap: wrap;
    }
}

@media (max-width: 768px) {
    .page-header {
        flex-direction: column;
        gap: 1rem;
        align-items: flex-start;
    }
    
    .header-actions {
        width: 100%;
        flex-wrap: wrap;
    }
    
    .btn {
        flex: 1;
        justify-content: center;
    }
    
    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    .filter-buttons {
        flex-direction: column;
    }
    
    .filter-btn {
        text-align: center;
    }
}
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
