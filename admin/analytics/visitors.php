<?php
require_once '../includes/auth-check.php';
$db = getDB();

// Date range filter
$dateRange = $_GET['range'] ?? '7';
$startDate = date('Y-m-d', strtotime("-{$dateRange} days"));
$endDate = date('Y-m-d');

// Search and filter parameters
$searchTerm = $_GET['search'] ?? '';
$deviceFilter = $_GET['device'] ?? '';
$pageFilter = $_GET['page_url'] ?? '';

// Pagination
$currentPage = isset($_GET['p']) ? (int)$_GET['p'] : 1;
$itemsPerPage = 25;
$offset = ($currentPage - 1) * $itemsPerPage;

// Build WHERE clause for filters
$whereConditions = ["visited_at >= '{$startDate}' AND visited_at <= '{$endDate} 23:59:59'"];
$params = [];

if (!empty($searchTerm)) {
    $whereConditions[] = "(ip_address LIKE :search OR page_url LIKE :search OR referrer LIKE :search)";
    $params[':search'] = "%{$searchTerm}%";
}

if (!empty($deviceFilter)) {
    $whereConditions[] = "device_type = :device";
    $params[':device'] = $deviceFilter;
}

if (!empty($pageFilter)) {
    $whereConditions[] = "page_url LIKE :page_url";
    $params[':page_url'] = "%{$pageFilter}%";
}

$whereClause = implode(' AND ', $whereConditions);

// Get total count for pagination
$countStmt = $db->prepare("SELECT COUNT(*) FROM visitor_analytics WHERE {$whereClause}");
foreach ($params as $key => $value) {
    $countStmt->bindValue($key, $value);
}
$countStmt->execute();
$totalVisits = $countStmt->fetchColumn();

// Get visitor data
$query = "SELECT * FROM visitor_analytics WHERE {$whereClause} ORDER BY visited_at DESC LIMIT :limit OFFSET :offset";
$stmt = $db->prepare($query);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue(':limit', $itemsPerPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$visitors = $stmt->fetchAll();

// Calculate pagination
$pagination = getPagination($currentPage, $totalVisits, $itemsPerPage);

// Statistics - Use prepared statements for all queries
$uniqueVisitorsStmt = $db->prepare("SELECT COUNT(DISTINCT ip_address) FROM visitor_analytics WHERE {$whereClause}");
foreach ($params as $key => $value) {
    $uniqueVisitorsStmt->bindValue($key, $value);
}
$uniqueVisitorsStmt->execute();
$uniqueVisitors = $uniqueVisitorsStmt->fetchColumn();

$totalPageViewsStmt = $db->prepare("SELECT COUNT(*) FROM visitor_analytics WHERE {$whereClause}");
foreach ($params as $key => $value) {
    $totalPageViewsStmt->bindValue($key, $value);
}
$totalPageViewsStmt->execute();
$totalPageViews = $totalPageViewsStmt->fetchColumn();

$uniqueSessionsStmt = $db->prepare("SELECT COUNT(DISTINCT session_id) FROM visitor_analytics WHERE {$whereClause}");
foreach ($params as $key => $value) {
    $uniqueSessionsStmt->bindValue($key, $value);
}
$uniqueSessionsStmt->execute();
$uniqueSessions = $uniqueSessionsStmt->fetchColumn();

// Device breakdown - Use prepared statement
$devicesQuery = "SELECT device_type, COUNT(*) as count FROM visitor_analytics WHERE {$whereClause} GROUP BY device_type ORDER BY count DESC";
$devicesStmt = $db->prepare($devicesQuery);
foreach ($params as $key => $value) {
    $devicesStmt->bindValue($key, $value);
}
$devicesStmt->execute();
$devices = $devicesStmt->fetchAll();

// Top pages - Use prepared statement
$topPagesQuery = "SELECT page_url, COUNT(*) as views FROM visitor_analytics WHERE {$whereClause} GROUP BY page_url ORDER BY views DESC LIMIT 10";
$topPagesStmt = $db->prepare($topPagesQuery);
foreach ($params as $key => $value) {
    $topPagesStmt->bindValue($key, $value);
}
$topPagesStmt->execute();
$topPages = $topPagesStmt->fetchAll();

// Top referrers - Use prepared statement
$topReferrersQuery = "SELECT referrer, COUNT(*) as count FROM visitor_analytics WHERE {$whereClause} AND referrer != '' GROUP BY referrer ORDER BY count DESC LIMIT 10";
$topReferrersStmt = $db->prepare($topReferrersQuery);
foreach ($params as $key => $value) {
    $topReferrersStmt->bindValue($key, $value);
}
$topReferrersStmt->execute();
$topReferrers = $topReferrersStmt->fetchAll();

// Hourly distribution (for heatmap) - Use prepared statement
$hourlyQuery = "SELECT HOUR(visited_at) as hour, COUNT(*) as count FROM visitor_analytics WHERE {$whereClause} GROUP BY HOUR(visited_at) ORDER BY hour";
$hourlyStmt = $db->prepare($hourlyQuery);
foreach ($params as $key => $value) {
    $hourlyStmt->bindValue($key, $value);
}
$hourlyStmt->execute();
$hourlyData = $hourlyStmt->fetchAll();

// Daily visits for trend chart - Need to use prepared statement for WHERE clause with params
$dailyVisitsQuery = "SELECT DATE(visited_at) as date, COUNT(*) as visits, COUNT(DISTINCT ip_address) as unique_visitors FROM visitor_analytics WHERE {$whereClause} GROUP BY DATE(visited_at) ORDER BY date ASC";
$dailyVisitsStmt = $db->prepare($dailyVisitsQuery);
foreach ($params as $key => $value) {
    $dailyVisitsStmt->bindValue($key, $value);
}
$dailyVisitsStmt->execute();
$dailyVisits = $dailyVisitsStmt->fetchAll();

// Get unique pages for filter dropdown
$uniquePages = $db->query("SELECT DISTINCT page_url FROM visitor_analytics WHERE visited_at >= '{$startDate}' ORDER BY page_url")->fetchAll(PDO::FETCH_COLUMN);

// Export functionality
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="visitors_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['IP Address', 'Page URL', 'Referrer', 'Device Type', 'Browser', 'OS', 'Session ID', 'Visit Time']);
    
    $exportQuery = "SELECT * FROM visitor_analytics WHERE {$whereClause} ORDER BY visited_at DESC";
    $exportStmt = $db->prepare($exportQuery);
    foreach ($params as $key => $value) {
        $exportStmt->bindValue($key, $value);
    }
    $exportStmt->execute();
    
    while ($row = $exportStmt->fetch()) {
        fputcsv($output, [
            $row['ip_address'],
            $row['page_url'],
            $row['referrer'],
            $row['device_type'],
            $row['browser'],
            $row['os'],
            $row['session_id'],
            $row['visited_at']
        ]);
    }
    fclose($output);
    exit();
}

$pageTitle = 'Visitor Analytics';
include '../includes/admin-header.php';
?>

<div class="page-header">
    <div>
        <h1>Visitor Analytics</h1>
        <p class="page-subtitle">Detailed visitor tracking and behavior analysis</p>
    </div>
    <div class="header-actions">
        <a href="index.php" class="btn btn-secondary"><i class="fas fa-chart-line"></i> Dashboard</a>
        <a href="?<?php echo http_build_query(array_merge($_GET, ['export' => 'csv'])); ?>" class="btn btn-success"><i class="fas fa-download"></i> Export CSV</a>
    </div>
</div>

<!-- Filter Bar -->
<div class="filter-section">
    <div class="filter-bar">
        <div class="filter-buttons">
            <a href="?range=1" class="filter-btn <?php echo $dateRange === '1' ? 'active' : ''; ?>">Today</a>
            <a href="?range=7" class="filter-btn <?php echo $dateRange === '7' ? 'active' : ''; ?>">Last 7 Days</a>
            <a href="?range=30" class="filter-btn <?php echo $dateRange === '30' ? 'active' : ''; ?>">Last 30 Days</a>
            <a href="?range=90" class="filter-btn <?php echo $dateRange === '90' ? 'active' : ''; ?>">Last 90 Days</a>
        </div>
    </div>

    <form method="GET" class="filters-form">
        <input type="hidden" name="range" value="<?php echo escapeHtml($dateRange); ?>">
        
        <div class="filter-group">
            <input type="text" 
                   name="search" 
                   placeholder="Search by IP, URL, or referrer..." 
                   value="<?php echo escapeHtml($searchTerm); ?>"
                   class="search-input">
        </div>

        <div class="filter-group">
            <select name="device" class="filter-select">
                <option value="">All Devices</option>
                <option value="desktop" <?php echo $deviceFilter === 'desktop' ? 'selected' : ''; ?>>Desktop</option>
                <option value="mobile" <?php echo $deviceFilter === 'mobile' ? 'selected' : ''; ?>>Mobile</option>
                <option value="tablet" <?php echo $deviceFilter === 'tablet' ? 'selected' : ''; ?>>Tablet</option>
            </select>
        </div>

        <div class="filter-group">
            <select name="page_url" class="filter-select">
                <option value="">All Pages</option>
                <?php foreach (array_slice($uniquePages, 0, 20) as $page): ?>
                    <option value="<?php echo escapeHtml($page); ?>" <?php echo $pageFilter === $page ? 'selected' : ''; ?>>
                        <?php echo escapeHtml(truncateText($page, 50)); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Filter</button>
        <a href="visitors.php" class="btn btn-secondary"><i class="fas fa-redo"></i> Reset</a>
    </form>
</div>

<!-- Statistics Cards -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon" style="background:#e3f2fd;color:#1976d2"><i class="fas fa-eye"></i></div>
        <div class="stat-content">
            <div class="stat-value"><?php echo number_format($totalPageViews); ?></div>
            <div class="stat-label">Total Page Views</div>
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
        <div class="stat-icon" style="background:#fff3e0;color:#f57c00"><i class="fas fa-clipboard-list"></i></div>
        <div class="stat-content">
            <div class="stat-value"><?php echo number_format($uniqueSessions); ?></div>
            <div class="stat-label">Unique Sessions</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#f3e5f5;color:#7b1fa2"><i class="fas fa-percentage"></i></div>
        <div class="stat-content">
            <div class="stat-value"><?php echo $uniqueVisitors > 0 ? number_format($totalPageViews / $uniqueVisitors, 1) : '0'; ?></div>
            <div class="stat-label">Pages per Visitor</div>
        </div>
    </div>
</div>

<!-- Charts Section -->
<div class="charts-section">
    <div class="chart-card full-width">
        <h3><i class="fas fa-chart-area"></i> Visitor Trends</h3>
        <canvas id="visitorTrendsChart" height="80"></canvas>
    </div>
</div>

<div class="charts-grid">
    <div class="chart-card">
        <h3><i class="fas fa-mobile-alt"></i> Device Breakdown</h3>
        <canvas id="devicesChart"></canvas>
    </div>

    <div class="chart-card">
        <h3><i class="fas fa-clock"></i> Hourly Distribution</h3>
        <canvas id="hourlyChart"></canvas>
    </div>
</div>

<!-- Top Pages & Referrers -->
<div class="info-grid">
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
                        <td><code><?php echo escapeHtml(truncateText($page['page_url'], 60)); ?></code></td>
                        <td style="text-align:right"><strong><?php echo number_format($page['views']); ?></strong></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="table-card">
        <h3><i class="fas fa-link"></i> Top Referrers</h3>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Referrer</th>
                    <th style="width:100px;text-align:right">Visits</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($topReferrers)): ?>
                <tr>
                    <td colspan="2" class="text-center">No referrer data</td>
                </tr>
                <?php else: ?>
                    <?php foreach ($topReferrers as $ref): ?>
                    <tr>
                        <td><code><?php echo escapeHtml(truncateText($ref['referrer'], 60)); ?></code></td>
                        <td style="text-align:right"><strong><?php echo number_format($ref['count']); ?></strong></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Visitor Log Table -->
<div class="table-card">
    <div class="table-header">
        <h3><i class="fas fa-list"></i> Visitor Log (<?php echo number_format($totalVisits); ?> total records)</h3>
        <div class="table-info">
            Showing <?php echo number_format($offset + 1); ?> - <?php echo number_format(min($offset + $itemsPerPage, $totalVisits)); ?> of <?php echo number_format($totalVisits); ?>
        </div>
    </div>
    
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width:120px">IP Address</th>
                    <th>Page URL</th>
                    <th>Referrer</th>
                    <th style="width:80px">Device</th>
                    <th style="width:150px">Time</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($visitors)): ?>
                <tr>
                    <td colspan="5" class="text-center">No visitors found</td>
                </tr>
                <?php else: ?>
                    <?php foreach ($visitors as $visitor): ?>
                    <tr>
                        <td>
                            <span class="ip-badge"><?php echo escapeHtml($visitor['ip_address']); ?></span>
                        </td>
                        <td>
                            <code class="url-code"><?php echo escapeHtml(truncateText($visitor['page_url'], 50)); ?></code>
                        </td>
                        <td>
                            <?php if (!empty($visitor['referrer'])): ?>
                                <small class="text-muted"><?php echo escapeHtml(truncateText($visitor['referrer'], 40)); ?></small>
                            <?php else: ?>
                                <span class="badge badge-secondary">Direct</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="device-badge device-<?php echo $visitor['device_type']; ?>">
                                <i class="fas fa-<?php 
                                    echo $visitor['device_type'] === 'mobile' ? 'mobile-alt' : 
                                        ($visitor['device_type'] === 'tablet' ? 'tablet-alt' : 'desktop'); 
                                ?>"></i>
                                <?php echo ucfirst($visitor['device_type']); ?>
                            </span>
                        </td>
                        <td>
                            <small><?php echo date('M j, Y g:i A', strtotime($visitor['visited_at'])); ?></small>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($pagination['total_pages'] > 1): ?>
    <div class="pagination">
        <?php if ($pagination['has_prev']): ?>
            <a href="?<?php echo http_build_query(array_merge($_GET, ['p' => $currentPage - 1])); ?>" class="pagination-btn">
                <i class="fas fa-chevron-left"></i> Previous
            </a>
        <?php endif; ?>

        <div class="pagination-pages">
            <?php
            $startPage = max(1, $currentPage - 2);
            $endPage = min($pagination['total_pages'], $currentPage + 2);
            
            if ($startPage > 1): ?>
                <a href="?<?php echo http_build_query(array_merge($_GET, ['p' => 1])); ?>" class="pagination-page">1</a>
                <?php if ($startPage > 2): ?>
                    <span class="pagination-ellipsis">...</span>
                <?php endif; ?>
            <?php endif; ?>

            <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                <a href="?<?php echo http_build_query(array_merge($_GET, ['p' => $i])); ?>" 
                   class="pagination-page <?php echo $i === $currentPage ? 'active' : ''; ?>">
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>

            <?php if ($endPage < $pagination['total_pages']): ?>
                <?php if ($endPage < $pagination['total_pages'] - 1): ?>
                    <span class="pagination-ellipsis">...</span>
                <?php endif; ?>
                <a href="?<?php echo http_build_query(array_merge($_GET, ['p' => $pagination['total_pages']])); ?>" 
                   class="pagination-page"><?php echo $pagination['total_pages']; ?></a>
            <?php endif; ?>
        </div>

        <?php if ($pagination['has_next']): ?>
            <a href="?<?php echo http_build_query(array_merge($_GET, ['p' => $currentPage + 1])); ?>" class="pagination-btn">
                Next <i class="fas fa-chevron-right"></i>
            </a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<style>
.page-header{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:2rem}
.page-subtitle{color:var(--admin-text-muted);margin-top:0.5rem}
.header-actions{display:flex;gap:0.75rem}

.filter-section{background:white;border-radius:12px;padding:1.5rem;margin-bottom:2rem;box-shadow:var(--admin-shadow)}
.filter-bar{margin-bottom:1.5rem}
.filter-buttons{display:flex;gap:0.5rem;flex-wrap:wrap}
.filter-btn{padding:0.5rem 1.25rem;border:2px solid var(--admin-border);border-radius:8px;text-decoration:none;color:var(--admin-text);font-weight:500;transition:all 0.2s}
.filter-btn:hover{border-color:var(--admin-primary);background:#e3f2fd}
.filter-btn.active{background:var(--admin-primary);color:white;border-color:var(--admin-primary)}

.filters-form{display:flex;gap:1rem;flex-wrap:wrap;align-items:center}
.filter-group{flex:1;min-width:200px}
.search-input,.filter-select{width:100%;padding:0.6rem 1rem;border:2px solid var(--admin-border);border-radius:8px;font-size:0.95rem}
.search-input:focus,.filter-select:focus{outline:none;border-color:var(--admin-primary)}

.stats-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:1.5rem;margin-bottom:2rem}
.stat-card{background:white;border-radius:12px;padding:1.5rem;box-shadow:var(--admin-shadow);display:flex;gap:1rem;align-items:center}
.stat-icon{width:60px;height:60px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.5rem;flex-shrink:0}
.stat-value{font-size:2rem;font-weight:700;color:var(--admin-text)}
.stat-label{font-size:0.9rem;color:var(--admin-text-muted)}

.charts-section{margin-bottom:2rem}
.charts-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(400px,1fr));gap:1.5rem;margin-bottom:2rem}
.chart-card{background:white;border-radius:12px;padding:1.5rem;box-shadow:var(--admin-shadow)}
.chart-card.full-width{grid-column:1/-1}
.chart-card h3{font-size:1.1rem;font-weight:600;margin-bottom:1.5rem;display:flex;align-items:center;gap:0.5rem;color:var(--admin-text)}

.info-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(400px,1fr));gap:1.5rem;margin-bottom:2rem}

.table-card{background:white;border-radius:12px;padding:1.5rem;box-shadow:var(--admin-shadow);margin-bottom:2rem}
.table-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem}
.table-header h3{font-size:1.1rem;font-weight:600;display:flex;align-items:center;gap:0.5rem;color:var(--admin-text);margin:0}
.table-info{color:var(--admin-text-muted);font-size:0.9rem}

.table-responsive{overflow-x:auto}
.data-table{width:100%;border-collapse:collapse}
.data-table th{background:#f8f9fa;padding:1rem;text-align:left;font-weight:600;border-bottom:2px solid var(--admin-border);white-space:nowrap}
.data-table td{padding:1rem;border-bottom:1px solid var(--admin-border)}
.data-table code,.url-code{background:#f5f5f5;padding:0.25rem 0.5rem;border-radius:4px;font-size:0.85rem;font-family:monospace}
.text-center{text-align:center;padding:2rem;color:var(--admin-text-muted)}
.text-muted{color:var(--admin-text-muted)}

.ip-badge{background:#e3f2fd;color:#1976d2;padding:0.25rem 0.75rem;border-radius:6px;font-family:monospace;font-size:0.85rem;font-weight:600}
.device-badge{display:inline-flex;align-items:center;gap:0.35rem;padding:0.25rem 0.75rem;border-radius:6px;font-size:0.85rem;font-weight:500}
.device-desktop{background:#e8f5e9;color:#388e3c}
.device-mobile{background:#fff3e0;color:#f57c00}
.device-tablet{background:#f3e5f5;color:#7b1fa2}

.badge{padding:0.25rem 0.75rem;border-radius:6px;font-size:0.85rem;font-weight:500}
.badge-secondary{background:#e0e0e0;color:#616161}

.pagination{display:flex;justify-content:center;align-items:center;gap:0.5rem;margin-top:1.5rem;padding-top:1.5rem;border-top:1px solid var(--admin-border)}
.pagination-btn,.pagination-page{padding:0.5rem 1rem;border:2px solid var(--admin-border);border-radius:8px;text-decoration:none;color:var(--admin-text);font-weight:500;transition:all 0.2s;background:white}
.pagination-btn:hover,.pagination-page:hover{border-color:var(--admin-primary);background:#e3f2fd}
.pagination-page.active{background:var(--admin-primary);color:white;border-color:var(--admin-primary)}
.pagination-pages{display:flex;gap:0.25rem}
.pagination-ellipsis{padding:0.5rem;color:var(--admin-text-muted)}

.btn{padding:0.6rem 1.25rem;border:none;border-radius:8px;font-weight:500;cursor:pointer;display:inline-flex;align-items:center;gap:0.5rem;text-decoration:none;transition:all 0.2s}
.btn-primary{background:var(--admin-primary);color:white}
.btn-primary:hover{background:#1565c0}
.btn-secondary{background:#6c757d;color:white}
.btn-secondary:hover{background:#5a6268}
.btn-success{background:#28a745;color:white}
.btn-success:hover{background:#218838}

@media(max-width:1024px){
    .charts-grid{grid-template-columns:1fr}
    .info-grid{grid-template-columns:1fr}
    .filter-group{min-width:100%}
}
@media(max-width:768px){
    .page-header{flex-direction:column;gap:1rem}
    .header-actions{width:100%}
    .stats-grid{grid-template-columns:1fr}
}
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
<script>
// Visitor Trends Chart (Line) - Limit data points for performance
const trendsCtx = document.getElementById('visitorTrendsChart');
if (trendsCtx) {
    const trendsChart = trendsCtx.getContext('2d');
    
    // Prepare data
    const trendLabels = <?php echo json_encode(array_column($dailyVisits, 'date')); ?>;
    const trendVisits = <?php echo json_encode(array_map('intval', array_column($dailyVisits, 'visits'))); ?>;
    const trendUnique = <?php echo json_encode(array_map('intval', array_column($dailyVisits, 'unique_visitors'))); ?>;
    
    // Only render if we have data
    if (trendLabels && trendLabels.length > 0) {
        new Chart(trendsChart, {
            type: 'line',
            data: {
                labels: trendLabels,
                datasets: [{
                    label: 'Page Views',
                    data: trendVisits,
                    borderColor: '#1976d2',
                    backgroundColor: 'rgba(25, 118, 210, 0.1)',
                    tension: 0.4,
                    fill: true,
                    pointRadius: 3,
                    pointHoverRadius: 5,
                    borderWidth: 2
                }, {
                    label: 'Unique Visitors',
                    data: trendUnique,
                    borderColor: '#388e3c',
                    backgroundColor: 'rgba(56, 142, 60, 0.1)',
                    tension: 0.4,
                    fill: true,
                    pointRadius: 3,
                    pointHoverRadius: 5,
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                animation: {
                    duration: 750
                },
                plugins: {
                    legend: { 
                        display: true,
                        position: 'top'
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false
                    }
                },
                scales: {
                    y: { 
                        beginAtZero: true,
                        ticks: { precision: 0 }
                    }
                },
                interaction: {
                    mode: 'nearest',
                    axis: 'x',
                    intersect: false
                }
            }
        });
    }
}

// Device Breakdown Chart (Doughnut)
const devicesCtx = document.getElementById('devicesChart');
if (devicesCtx) {
    const devicesChart = devicesCtx.getContext('2d');
    const deviceLabels = <?php echo json_encode(array_map('ucfirst', array_column($devices, 'device_type'))); ?>;
    const deviceData = <?php echo json_encode(array_map('intval', array_column($devices, 'count'))); ?>;
    
    if (deviceLabels && deviceLabels.length > 0) {
        new Chart(devicesChart, {
            type: 'doughnut',
            data: {
                labels: deviceLabels,
                datasets: [{
                    data: deviceData,
                    backgroundColor: ['#1976d2', '#f57c00', '#7b1fa2'],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
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
}

// Hourly Distribution Chart (Bar)
const hourlyCtx = document.getElementById('hourlyChart');
if (hourlyCtx) {
    const hourlyChart = hourlyCtx.getContext('2d');
    const hourlyLabels = Array.from({length: 24}, (_, i) => i);
    const hourlyDataRaw = <?php echo json_encode($hourlyData); ?>;
    const hourlyValues = hourlyLabels.map(hour => {
        const found = hourlyDataRaw.find(d => parseInt(d.hour) === hour);
        return found ? parseInt(found.count) : 0;
    });

    new Chart(hourlyChart, {
        type: 'bar',
        data: {
            labels: hourlyLabels.map(h => h + ':00'),
            datasets: [{
                label: 'Visits',
                data: hourlyValues,
                backgroundColor: '#1976d2',
                borderRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
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
                },
                x: {
                    ticks: {
                        maxRotation: 45,
                        minRotation: 45
                    }
                }
            }
        }
    });
}
</script>

<?php include '../includes/admin-footer.php'; ?>
