<?php
require_once '../includes/auth-check.php';
$db = getDB();

// Date range filter
$startDate = $_GET['start'] ?? date('Y-m-d', strtotime('-30 days'));
$endDate = $_GET['end'] ?? date('Y-m-d');

// Browser stats
$browsers = $db->query("SELECT browser, COUNT(*) as count FROM visitor_analytics WHERE visited_at BETWEEN '{$startDate}' AND '{$endDate} 23:59:59' AND browser IS NOT NULL GROUP BY browser ORDER BY count DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);

// OS stats
$operatingSystems = $db->query("SELECT os, COUNT(*) as count FROM visitor_analytics WHERE visited_at BETWEEN '{$startDate}' AND '{$endDate} 23:59:59' AND os IS NOT NULL GROUP BY os ORDER BY count DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);

// Referrer stats
$referrers = $db->query("SELECT referrer, COUNT(*) as count FROM visitor_analytics WHERE visited_at BETWEEN '{$startDate}' AND '{$endDate} 23:59:59' AND referrer != '' GROUP BY referrer ORDER BY count DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);

// Hourly traffic
$hourlyTraffic = $db->query("SELECT HOUR(visited_at) as hour, COUNT(*) as visits FROM visitor_analytics WHERE visited_at BETWEEN '{$startDate}' AND '{$endDate} 23:59:59' GROUP BY HOUR(visited_at) ORDER BY hour")->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Analytics Reports';
include '../includes/admin-header.php';
?>

<div class="page-header">
    <h1>Analytics Reports</h1>
    <a href="index.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back</a>
</div>

<div class="filter-card">
    <form method="GET" action="" class="date-filter-form">
        <div class="form-group">
            <label>Start Date</label>
            <input type="date" name="start" class="form-control" value="<?php echo $startDate; ?>">
        </div>
        <div class="form-group">
            <label>End Date</label>
            <input type="date" name="end" class="form-control" value="<?php echo $endDate; ?>">
        </div>
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-filter"></i> Apply Filter
        </button>
    </form>
</div>

<div class="reports-grid">
    <div class="report-card">
        <h3><i class="fas fa-chrome"></i> Top Browsers</h3>
        <div class="report-list">
            <?php if (empty($browsers)): ?>
                <p class="text-muted">No data available</p>
            <?php else: ?>
                <?php foreach ($browsers as $browser): ?>
                <div class="report-item">
                    <span class="report-label"><?php echo escapeHtml($browser['browser'] ?: 'Unknown'); ?></span>
                    <span class="report-value"><?php echo number_format($browser['count']); ?></span>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="report-card">
        <h3><i class="fas fa-desktop"></i> Operating Systems</h3>
        <div class="report-list">
            <?php if (empty($operatingSystems)): ?>
                <p class="text-muted">No data available</p>
            <?php else: ?>
                <?php foreach ($operatingSystems as $os): ?>
                <div class="report-item">
                    <span class="report-label"><?php echo escapeHtml($os['os'] ?: 'Unknown'); ?></span>
                    <span class="report-value"><?php echo number_format($os['count']); ?></span>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="chart-card">
    <h3><i class="fas fa-clock"></i> Hourly Traffic Pattern</h3>
    <div class="chart-container">
        <canvas id="hourlyChart"></canvas>
    </div>
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
            <?php if (empty($referrers)): ?>
            <tr>
                <td colspan="2" class="text-center">No referrer data available</td>
            </tr>
            <?php else: ?>
                <?php foreach ($referrers as $ref): ?>
                <tr>
                    <td><code><?php echo escapeHtml($ref['referrer']); ?></code></td>
                    <td style="text-align:right"><strong><?php echo number_format($ref['count']); ?></strong></td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<style>
.page-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:2rem}
.filter-card{background:white;border-radius:12px;padding:1.5rem;margin-bottom:2rem;box-shadow:var(--admin-shadow)}
.date-filter-form{display:flex;gap:1rem;align-items:flex-end}
.form-group{flex:1}
.form-group label{display:block;margin-bottom:0.5rem;font-weight:500;font-size:0.9rem}
.form-control{width:100%;padding:0.75rem 1rem;border:2px solid var(--admin-border);border-radius:8px}
.reports-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:1.5rem;margin-bottom:2rem}
.report-card{background:white;border-radius:12px;padding:1.5rem;box-shadow:var(--admin-shadow)}
.report-card h3{font-size:1.1rem;font-weight:600;margin-bottom:1.5rem;display:flex;align-items:center;gap:0.5rem;color:var(--admin-text)}
.report-list{display:flex;flex-direction:column;gap:0.75rem}
.report-item{display:flex;justify-content:space-between;align-items:center;padding:0.75rem;background:#f8f9fa;border-radius:8px}
.report-label{font-weight:500;color:var(--admin-text)}
.report-value{font-weight:700;color:var(--admin-primary);font-size:1.1rem}
.chart-card{background:white;border-radius:12px;padding:1.5rem;box-shadow:var(--admin-shadow);margin-bottom:2rem}
.chart-card h3{font-size:1.1rem;font-weight:600;margin-bottom:1.5rem;display:flex;align-items:center;gap:0.5rem;color:var(--admin-text)}
.chart-container{position:relative;height:350px;width:100%}
.table-card{background:white;border-radius:12px;padding:1.5rem;box-shadow:var(--admin-shadow)}
.table-card h3{font-size:1.1rem;font-weight:600;margin-bottom:1.5rem;display:flex;align-items:center;gap:0.5rem;color:var(--admin-text)}
.data-table{width:100%;border-collapse:collapse}
.data-table th{background:#f8f9fa;padding:1rem;text-align:left;font-weight:600;border-bottom:2px solid var(--admin-border)}
.data-table td{padding:1rem;border-bottom:1px solid var(--admin-border)}
.data-table code{background:#f5f5f5;padding:0.25rem 0.5rem;border-radius:4px;font-size:0.9rem}
.text-center{text-align:center;padding:2rem;color:var(--admin-text-muted)}
.text-muted{color:var(--admin-text-muted);padding:1rem;text-align:center}
.btn{padding:0.6rem 1.25rem;border:none;border-radius:8px;font-weight:500;cursor:pointer;display:inline-flex;align-items:center;gap:0.5rem;text-decoration:none;transition:all 0.2s}
.btn-primary{background:var(--admin-primary);color:white}
.btn-primary:hover{background:#004a99}
.btn-secondary{background:#6c757d;color:white}
.btn-secondary:hover{background:#5a6268}
@media(max-width:768px){.date-filter-form{flex-direction:column;align-items:stretch}.btn{width:100%}}
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
<script>
// Hourly Traffic Chart
const hourlyCtx = document.getElementById('hourlyChart');
if (hourlyCtx) {
    const hourlyData = <?php echo json_encode($hourlyTraffic); ?>;
    const hours = Array.from({length: 24}, (_, i) => i);
    const visits = hours.map(h => {
        const found = hourlyData.find(d => parseInt(d.hour) === h);
        return found ? parseInt(found.visits) : 0;
    });

    new Chart(hourlyCtx, {
        type: 'bar',
        data: {
            labels: hours.map(h => h + ':00'),
            datasets: [{
                label: 'Visits',
                data: visits,
                backgroundColor: '#1976d2',
                borderRadius: 6
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
