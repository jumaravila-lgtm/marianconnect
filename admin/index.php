<?php
/**
 * MARIANCONNECT - Admin Dashboard
 */

// Authentication check
require_once 'includes/auth-check.php';

$db = getDB();
$adminId = $_SESSION['admin_id'];

// Get statistics
$stats = [];

// Total News
$stmt = $db->query("SELECT COUNT(*) as total FROM news");
$stats['total_news'] = $stmt->fetch()['total'];

// Total Events
$stmt = $db->query("SELECT COUNT(*) as total FROM events");
$stats['total_events'] = $stmt->fetch()['total'];

// Total Messages
$stmt = $db->query("SELECT COUNT(*) as total FROM contact_messages WHERE status = 'new'");
$stats['new_messages'] = $stmt->fetch()['total'];

// Total Visitors (Today)
$stmt = $db->query("SELECT COUNT(*) as total FROM visitor_analytics WHERE DATE(visited_at) = CURDATE()");
$stats['visitors_today'] = $stmt->fetch()['total'];

// Total Visitors (This Week)
$stmt = $db->query("SELECT COUNT(*) as total FROM visitor_analytics WHERE visited_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
$stats['visitors_week'] = $stmt->fetch()['total'];

// Total Visitors (This Month)
$stmt = $db->query("SELECT COUNT(*) as total FROM visitor_analytics WHERE visited_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
$stats['visitors_month'] = $stmt->fetch()['total'];

// Recent News
$recentNews = $db->query("
    SELECT n.*, a.full_name as author 
    FROM news n 
    JOIN admin_users a ON n.author_id = a.admin_id 
    ORDER BY n.created_at DESC 
    LIMIT 5
")->fetchAll();

// Recent Messages
$recentMessages = $db->query("
    SELECT * FROM contact_messages 
    ORDER BY created_at DESC 
    LIMIT 5
")->fetchAll();

// Upcoming Events
$upcomingEvents = $db->query("
    SELECT * FROM events 
    WHERE event_date >= CURDATE() 
    ORDER BY event_date ASC 
    LIMIT 5
")->fetchAll();

// Active Announcements
$activeAnnouncements = $db->query("
    SELECT * FROM announcements 
    WHERE is_active = 1 
    AND CURDATE() BETWEEN DATE(start_date) AND DATE(end_date)
    ORDER BY priority DESC, start_date DESC 
    LIMIT 5
")->fetchAll();

// Visitor Analytics (Last 7 days)
$visitorData = $db->query("
    SELECT DATE(visited_at) as date, COUNT(*) as count 
    FROM visitor_analytics 
    WHERE visited_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    GROUP BY DATE(visited_at)
    ORDER BY date ASC
")->fetchAll();

$pageTitle = 'Dashboard';
include 'includes/admin-header.php';
?>

<div class="dashboard-content">
    <div class="dashboard-header">
        <h1>Dashboard</h1>
        <p>Welcome back, <?php echo escapeHtml($_SESSION['full_name']); ?>!</p>
    </div>

<!-- Statistics Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-content">
                <h3><?php echo number_format($stats['visitors_today']); ?></h3>
                <p>Visitors Today</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-content">
                <h3><?php echo number_format($stats['total_news']); ?></h3>
                <p>Total News</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-content">
                <h3><?php echo number_format($stats['total_events']); ?></h3>
                <p>Total Events</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-content">
                <h3><?php echo number_format($stats['new_messages']); ?></h3>
                <p>New Messages</p>
            </div>
        </div>
    </div>

<!-- Visitor Chart -->
    <div class="dashboard-section">
        <div class="section-header">
            <h2>Visitor Analytics (Last 7 Days)</h2>
        </div>
        <div class="chart-container">
            <canvas id="visitorChart"></canvas>
        </div>
    </div>

<!-- Two Column Layout -->
    <div class="dashboard-grid">
        <!-- Recent News -->
        <div class="dashboard-section">
            <div class="section-header">
                <h2>Recent News</h2>
                <a href="news/index.php" class="btn-sm">View All</a>
            </div>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Author</th>
                            <th>Status</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recentNews)): ?>
                            <tr>
                                <td colspan="4" class="text-center">No news articles yet</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($recentNews as $news): ?>
                            <tr>
                                <td>
                                    <a href="news/edit.php?id=<?php echo $news['news_id']; ?>">
                                        <?php echo escapeHtml(truncateText($news['title'], 50)); ?>
                                    </a>
                                </td>
                                <td><?php echo escapeHtml($news['author']); ?></td>
                                <td>
                                    <span class="badge badge-<?php echo $news['status']; ?>">
                                        <?php echo ucfirst($news['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo timeAgo($news['created_at']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

<!-- Recent Messages -->
        <div class="dashboard-section">
            <div class="section-header">
                <h2>Recent Messages</h2>
                <a href="messages/index.php" class="btn-sm">View All</a>
            </div>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Subject</th>
                            <th>Status</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recentMessages)): ?>
                            <tr>
                                <td colspan="4" class="text-center">No messages yet</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($recentMessages as $msg): ?>
                            <tr>
                                <td>
                                    <a href="messages/view.php?id=<?php echo $msg['message_id']; ?>">
                                        <?php echo escapeHtml($msg['full_name']); ?>
                                    </a>
                                </td>
                                <td><?php echo escapeHtml(truncateText($msg['subject'], 40)); ?></td>
                                <td>
                                    <span class="badge badge-<?php echo $msg['status']; ?>">
                                        <?php echo ucfirst($msg['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo timeAgo($msg['created_at']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Upcoming Events and Announcements -->
    <div class="dashboard-grid">
        <!-- Upcoming Events -->
        <div class="dashboard-section">
            <div class="section-header">
                <h2>Upcoming Events</h2>
                <a href="events/index.php" class="btn-sm">View All</a>
            </div>
            <div class="event-list">
                <?php if (empty($upcomingEvents)): ?>
                    <p class="text-muted">No upcoming events</p>
                <?php else: ?>
                    <?php foreach ($upcomingEvents as $event): ?>
                    <div class="event-item">
                        <div class="event-date">
                            <span class="day"><?php echo date('d', strtotime($event['event_date'])); ?></span>
                            <span class="month"><?php echo date('M', strtotime($event['event_date'])); ?></span>
                        </div>
                        <div class="event-info">
                            <h4><?php echo escapeHtml($event['title']); ?></h4>
                            <p><?php echo escapeHtml($event['location']); ?></p>
                        </div>
                        <a href="events/edit.php?id=<?php echo $event['event_id']; ?>" class="btn-link">Edit</a>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

    <!-- Active Announcements -->
        <div class="dashboard-section">
            <div class="section-header">
                <h2>Active Announcements</h2>
                <a href="announcements/index.php" class="btn-sm">View All</a>
            </div>
            <div class="announcement-list">
                <?php if (empty($activeAnnouncements)): ?>
                    <p class="text-muted">No active announcements</p>
                <?php else: ?>
                    <?php foreach ($activeAnnouncements as $announcement): ?>
                    <div class="announcement-item priority-<?php echo $announcement['priority']; ?>">
                        <div class="announcement-header">
                            <span class="badge badge-<?php echo $announcement['type']; ?>">
                                <?php echo ucfirst($announcement['type']); ?>
                            </span>
                            <span class="badge badge-<?php echo $announcement['priority']; ?>">
                                <?php echo ucfirst($announcement['priority']); ?>
                            </span>
                        </div>
                        <h4><?php echo escapeHtml($announcement['title']); ?></h4>
                        <p><?php echo escapeHtml(truncateText($announcement['content'], 100)); ?></p>
                        <small>Ends: <?php echo formatDate($announcement['end_date']); ?></small>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script>
// Visitor Analytics Chart
const ctx = document.getElementById('visitorChart').getContext('2d');
const visitorChart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: <?php echo json_encode(array_column($visitorData, 'date')); ?>,
        datasets: [{
            label: 'Visitors',
            data: <?php echo json_encode(array_column($visitorData, 'count')); ?>,
            backgroundColor: 'rgba(0, 63, 135, 0.1)',
            borderColor: 'rgba(0, 63, 135, 1)',
            borderWidth: 2,
            fill: true,
            tension: 0.4
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    precision: 0
                }
            }
        }
    }
});
</script>

<?php include 'includes/admin-footer.php'; ?>
