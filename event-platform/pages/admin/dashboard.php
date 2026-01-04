<?php
session_start();
require_once '../../config/database.php';

// Auth Check
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

require_once '../../includes/icons.php';

// --- Action Handling ---
if (isset($_GET['approve_event'])) {
    $stmt = $pdo->prepare("UPDATE events SET status='published' WHERE id=?");
    $stmt->execute([$_GET['approve_event']]);
    header("Location: dashboard.php"); exit();
}
if (isset($_GET['reject_event'])) {
    $stmt = $pdo->prepare("UPDATE events SET status='rejected' WHERE id=?");
    $stmt->execute([$_GET['reject_event']]);
    header("Location: dashboard.php"); exit();
}
if (isset($_GET['delete_event'])) {
    $stmt = $pdo->prepare("DELETE FROM events WHERE id=?");
    $stmt->execute([$_GET['delete_event']]);
    header("Location: dashboard.php"); exit();
}

// --- Data Fetching ---

// 1. Core Metrics
$total_users_count = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();

// Total Revenue & Tickets (Corrected Logic: Bookings table has 1 row per ticket)
// Revenue = Sum of (Ticket Type Price OR Event Price if no type)
$revenue_sql = "
    SELECT SUM(COALESCE(tt.price, e.price)) 
    FROM bookings b 
    JOIN events e ON b.event_id = e.id 
    LEFT JOIN ticket_types tt ON b.ticket_type_id = tt.id
";
$total_revenue = $pdo->query($revenue_sql)->fetchColumn() ?: 0;
$total_tickets = $pdo->query("SELECT COUNT(*) FROM bookings")->fetchColumn() ?: 0;

// 2. Top Event (Most Tickets Sold)
// Group by event_id, count rows
$top_event_stmt = $pdo->query("
    SELECT e.title, COUNT(*) as sold
    FROM bookings b
    JOIN events e ON b.event_id = e.id
    GROUP BY b.event_id
    ORDER BY sold DESC
    LIMIT 1
");
$top_event = $top_event_stmt->fetch(PDO::FETCH_ASSOC);

// 3. Sales Trend (Last 7 Days)
// Revenue per day
$chart_sql = "
    SELECT DATE(b.booking_date) as date, COUNT(*) as count, SUM(COALESCE(tt.price, e.price)) as revenue
    FROM bookings b 
    JOIN events e ON b.event_id = e.id 
    LEFT JOIN ticket_types tt ON b.ticket_type_id = tt.id
    WHERE b.booking_date >= DATE(NOW()) - INTERVAL 6 DAY 
    GROUP BY DATE(b.booking_date) 
    ORDER BY date ASC
";
$chart_stmt = $pdo->query($chart_sql);
$chart_data = $chart_stmt->fetchAll(PDO::FETCH_ASSOC);

// Process Chart Data (Fill holes)
$dates = [];
$counts = [];
$revenues = [];
for ($i = 6; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $dates[] = date('M j', strtotime($d));
    $found = false;
    foreach ($chart_data as $row) {
        if ($row['date'] == $d) {
            $counts[] = $row['count'];
            $revenues[] = $row['revenue'];
            $found = true;
            break;
        }
    }
    if (!$found) { $counts[] = 0; $revenues[] = 0; }
}

// 4. Recent Transactions
$recent_stmt = $pdo->prepare("
    SELECT b.*, u.name as user_name, e.title as event_title, COALESCE(tt.price, e.price) as final_price 
    FROM bookings b 
    JOIN users u ON b.user_id = u.id 
    JOIN events e ON b.event_id = e.id 
    LEFT JOIN ticket_types tt ON b.ticket_type_id = tt.id
    ORDER BY b.booking_date DESC 
    LIMIT 5
");
$recent_stmt->execute();
$transactions = $recent_stmt->fetchAll(PDO::FETCH_ASSOC);

// 5. Pending Events
$pending_stmt = $pdo->query("SELECT e.*, u.name as organizer FROM events e JOIN users u ON e.organizer_id=u.id WHERE e.status='pending'");
$pending_events = $pending_stmt->fetchAll(PDO::FETCH_ASSOC);

// --- Trend Calculations (Current Week vs Last Week) ---

// a. Revenue Trend
$rev_now_sql = "SELECT SUM(COALESCE(tt.price, e.price)) FROM bookings b JOIN events e ON b.event_id = e.id LEFT JOIN ticket_types tt ON b.ticket_type_id = tt.id WHERE b.booking_date >= DATE(NOW()) - INTERVAL 6 DAY";
$rev_last_sql = "SELECT SUM(COALESCE(tt.price, e.price)) FROM bookings b JOIN events e ON b.event_id = e.id LEFT JOIN ticket_types tt ON b.ticket_type_id = tt.id WHERE b.booking_date >= DATE(NOW()) - INTERVAL 13 DAY AND b.booking_date < DATE(NOW()) - INTERVAL 6 DAY";
$rev_now = $pdo->query($rev_now_sql)->fetchColumn() ?: 0;
$rev_last = $pdo->query($rev_last_sql)->fetchColumn() ?: 0;
$rev_trend = $rev_last > 0 ? (($rev_now - $rev_last) / $rev_last) * 100 : ($rev_now > 0 ? 100 : 0);

// b. User Trend
$users_now = $pdo->query("SELECT COUNT(*) FROM users WHERE created_at >= DATE(NOW()) - INTERVAL 6 DAY")->fetchColumn();
$users_last = $pdo->query("SELECT COUNT(*) FROM users WHERE created_at >= DATE(NOW()) - INTERVAL 13 DAY AND created_at < DATE(NOW()) - INTERVAL 6 DAY")->fetchColumn();
$user_trend = $users_last > 0 ? (($users_now - $users_last) / $users_last) * 100 : ($users_now > 0 ? 100 : 0);

// d. New Events this week
$events_now = $pdo->query("SELECT COUNT(*) FROM events WHERE status='published' AND created_at >= DATE(NOW()) - INTERVAL 6 DAY")->fetchColumn();
$events_last = $pdo->query("SELECT COUNT(*) FROM events WHERE status='published' AND created_at >= DATE(NOW()) - INTERVAL 13 DAY AND created_at < DATE(NOW()) - INTERVAL 6 DAY")->fetchColumn();
$event_trend = $events_last > 0 ? (($events_now - $events_last) / $events_last) * 100 : ($events_now > 0 ? 100 : 0);

// --- Fetch Notifications ---
$notif_stmt = $pdo->query("SELECT * FROM notifications WHERE is_read = 0 ORDER BY created_at DESC LIMIT 5");
$notifications_list = $notif_stmt->fetchAll(PDO::FETCH_ASSOC);
$unread_count = count($notifications_list);

// --- Fetch Activity Feed (Hybrid) ---
$recent_activities = [];

// 1. Recent Signups
$stmt = $pdo->query("SELECT name, created_at, 'signup' as type FROM users ORDER BY created_at DESC LIMIT 3");
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $recent_activities[] = [
        'title' => 'New User Registered',
        'desc' => htmlspecialchars($row['name']) . ' joined the platform.',
        'time' => $row['created_at'],
        'color' => 'var(--primary)'
    ];
}

// 2. Recent Events
$stmt = $pdo->query("SELECT title, created_at, 'event' as type FROM events ORDER BY created_at DESC LIMIT 3");
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $recent_activities[] = [
        'title' => 'New Event Created',
        'desc' => '"' . htmlspecialchars($row['title']) . '" was submitted.',
        'time' => $row['created_at'],
        'color' => 'var(--success)'
    ];
}

// 3. Recent Bookings (Simplified)
$stmt = $pdo->query("SELECT e.title, b.booking_date, 'booking' as type FROM bookings b JOIN events e ON b.event_id = e.id ORDER BY b.booking_date DESC LIMIT 3");
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $recent_activities[] = [
        'title' => 'Ticket Purchased',
        'desc' => 'New ticket sold for ' . htmlspecialchars($row['title']),
        'time' => $row['booking_date'],
        'color' => 'var(--accent)'
    ];
}

// Sort by time
usort($recent_activities, function($a, $b) {
    return strtotime($b['time']) - strtotime($a['time']);
});
$recent_activities = array_slice($recent_activities, 0, 5);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard - Event Platform</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body style="background-color: var(--dark-bg);">

<div class="admin-layout">
    
    <?php include 'sidebar.php'; ?>

    <!-- Main Content -->
    <main class="main-content">
        
        <?php include 'admin_header.php'; ?>


        <!-- Welcome & Headline -->
        <div style="margin-bottom: 30px;">
            <h2 style="font-size: 1.75rem; font-weight: 700; color: #fff; margin-bottom: 5px;">Welcome back, Admin</h2>
            <p style="color: var(--text-muted); font-size: 0.95rem;">Here's what's happening on the platform today.</p>
        </div>

        <!-- Metrics Grid -->
        <div class="metrics-grid">
            <!-- Revenue -->
            <a href="transactions.php" class="metric-card" style="text-decoration:none; display:block;">
                <div style="display:flex; justify-content:space-between; align-items:flex-start;">
                    <span class="metric-title">Platform Revenue</span>
                    <span style="color:var(--text-muted); opacity:0.5;"><?php echo Icons::get('currency-dollar', 'width:20px; height:20px;'); ?></span>
                </div>
                <span class="metric-value">$<?php echo number_format($total_revenue, 2); ?></span>
                <div class="metric-trend <?php echo $rev_trend >= 0 ? 'trend-up' : 'trend-down'; ?>">
                    <span style="font-weight:700;"><?php echo ($rev_trend >= 0 ? '+' : '') . round($rev_trend, 1); ?>%</span> 
                    <span style="opacity:0.6; font-weight:400;">vs last week</span>
                </div>
            </a>

            <!-- Active Events -->
            <a href="events.php" class="metric-card" style="text-decoration:none; display:block;">
                <div style="display:flex; justify-content:space-between; align-items:flex-start;">
                    <span class="metric-title">Active Events</span>
                    <span style="color:var(--text-muted); opacity:0.5;"><?php echo Icons::get('calendar', 'width:20px; height:20px;'); ?></span>
                </div>
                <?php 
                $active_events_count = $pdo->query("SELECT COUNT(*) FROM events WHERE status='published'")->fetchColumn();
                ?>
                <span class="metric-value"><?php echo number_format($active_events_count); ?></span>
                <div class="metric-trend <?php echo $event_trend >= 0 ? 'trend-up' : 'trend-down'; ?>">
                    <span style="font-weight:700;"><?php echo ($event_trend >= 0 ? '+' : '') . round($event_trend, 1); ?>%</span> 
                    <span style="opacity:0.6; font-weight:400;">new this week</span>
                </div>
            </a>

            <!-- Pending Approvals -->
            <a href="events.php?status=pending" class="metric-card" style="text-decoration:none; display:block;">
                <div style="display:flex; justify-content:space-between; align-items:flex-start;">
                    <span class="metric-title">Pending Approvals</span>
                    <span style="color:var(--warning); opacity:0.8;"><?php echo Icons::get('exclamation-triangle', 'width:20px; height:20px;'); ?></span>
                </div>
                <span class="metric-value"><?php echo count($pending_events); ?></span>
                <div class="metric-trend" style="color:var(--warning)">
                    <span style="font-weight:700;">Needs Review</span>
                </div>
            </a>

             <!-- Total Users -->
            <a href="users.php" class="metric-card" style="text-decoration:none; display:block;">
                <div style="display:flex; justify-content:space-between; align-items:flex-start;">
                    <span class="metric-title">Total Users</span>
                    <span style="color:var(--text-muted); opacity:0.5;"><?php echo Icons::get('users', 'width:20px; height:20px;'); ?></span>
                </div>
                <span class="metric-value"><?php echo number_format($total_users_count); ?></span>
                <div class="metric-trend <?php echo $user_trend >= 0 ? 'trend-up' : 'trend-down'; ?>">
                   <span style="font-weight:700;"><?php echo ($user_trend >= 0 ? '+' : '') . round($user_trend, 1); ?>%</span> 
                   <span style="opacity:0.6; font-weight:400;">growth</span>
                </div>
            </a>
        </div>

        <!-- Main Dashboard Section: Charts & Tables -->
        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 30px;">
            
            <!-- Left Column -->
            <div>
                <!-- Weekly Trend Chart -->
                <div class="dashboard-panel">
                    <div class="panel-header">
                        <h3 class="panel-title">Weekly Sales Trend</h3>
                        <select style="background:transparent; border:1px solid var(--border); color:var(--text-muted); padding:5px 10px; border-radius:5px;">
                            <option>Last 7 Days</option>
                        </select>
                    </div>
                    <div style="height: 300px; width: 100%;">
                        <canvas id="revenueChart"></canvas>
                    </div>
                </div>

                <!-- Recent Transactions -->
                <div class="dashboard-panel">
                    <div class="panel-header">
                        <h3 class="panel-title">Recent Transactions</h3>
                        <a href="#" style="font-size:0.85rem; color:var(--primary-light); text-decoration:none;">View All</a>
                    </div>
                    <table class="modern-table">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Event</th>
                                <th>Amount</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($transactions as $t): ?>
                            <tr class="table-row-hover">
                                <td><?php echo htmlspecialchars($t['user_name']); ?></td>
                                <td><?php echo htmlspecialchars($t['event_title']); ?></td>
                                <td style="font-weight:600; color:#fff;">$<?php echo number_format($t['final_price'], 2); ?></td>
                                <td style="color:var(--text-muted); font-size:0.85rem;"><?php echo date('M j, g:ia', strtotime($t['booking_date'])); ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if(empty($transactions)) echo "<tr><td colspan='4'>No recent transactions</td></tr>"; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Right Column -->
            <div>
                <!-- Moderation Center (Action Required) -->
                <div class="dashboard-panel">
                    <div class="panel-header">
                        <div style="display:flex; align-items:center; gap:10px;">
                            <h3 class="panel-title">Moderation Center</h3>
                            <span class="badge-outline" style="color:var(--warning); background:rgba(251, 191, 36, 0.1); border:none;"><?php echo count($pending_events); ?></span>
                        </div>
                        <a href="events.php" style="font-size:0.8rem; color:var(--text-muted); text-decoration:none;">View All</a>
                    </div>
                    <?php if(count($pending_events) > 0): ?>
                        <div style="display:flex; flex-direction:column; gap:12px;">
                            <?php foreach($pending_events as $pe): ?>
                            <div style="background:rgba(255,255,255,0.02); padding:12px; border-radius:12px; border:1px solid var(--border);">
                                <div style="display:flex; gap:12px; align-items:center; margin-bottom:10px;">
                                    <div style="width:40px; height:40px; border-radius:8px; background:rgba(255,255,255,0.05); display:flex; align-items:center; justify-content:center; color:var(--text-muted);">
                                        <?php echo Icons::get('calendar', 'width:20px; height:20px;'); ?>
                                    </div>
                                    <div style="overflow:hidden;">
                                        <h4 style="margin:0; font-size:0.9rem; color:#fff; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"><?php echo htmlspecialchars($pe['title']); ?></h4>
                                        <p style="margin:0; font-size:0.75rem; color:var(--text-muted);">By <?php echo htmlspecialchars($pe['organizer']); ?></p>
                                    </div>
                                </div>
                                <div style="display:flex; gap:8px;">
                                    <a href="?approve_event=<?php echo $pe['id']; ?>" class="action-icon-btn" style="flex:1; width:auto; height:32px; background:rgba(52, 211, 153, 0.15); color:var(--success); border-radius:6px; font-size:0.8rem; font-weight:600; text-decoration:none;" title="Approve">
                                        Approve
                                    </a>
                                    <a href="?reject_event=<?php echo $pe['id']; ?>" class="action-icon-btn" style="flex:1; width:auto; height:32px; background:rgba(248, 113, 113, 0.1); color:var(--danger); border-radius:6px; font-size:0.8rem; font-weight:600; text-decoration:none;" title="Reject">
                                        Reject
                                    </a>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div style="text-align:center; padding:30px 0; color:var(--text-muted); font-size:0.9rem;">
                            <span style="font-size:1.5rem; display:block; margin-bottom:8px; opacity:0.5;"><?php echo Icons::get('check-circle', 'width:32px; height:32px;'); ?></span>
                            All events moderated.
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Activity Feed -->
                <div class="dashboard-panel">
                    <div class="panel-header">
                        <h3 class="panel-title">Recent Activity</h3>
                        <a href="#" style="font-size:0.8rem; color:var(--text-muted); text-decoration:none;">Refresh</a>
                    </div>
                    <div class="activity-timeline">
                        <?php foreach($recent_activities as $act): ?>
                            <div class="activity-item">
                                <div class="activity-point" style="background:<?php echo $act['color']; ?>;"></div>
                                <div class="activity-content">
                                    <h4><?php echo $act['title']; ?></h4>
                                    <p><?php echo $act['desc']; ?></p>
                                    <span class="activity-time"><?php echo date('M j, g:ia', strtotime($act['time'])); ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <?php if (empty($recent_activities)): ?>
                            <div style="text-align:center; color:var(--text-muted); padding:20px; font-size:0.85rem;">No recent activity.</div>
                        <?php endif; ?>
                    </div>
                    <button class="hero-btn" style="width:100%; margin-top:15px; background:transparent; border:1px solid var(--border); color:var(--text-muted); font-size:0.8rem;">
                        View Full Logs
                    </button>
                </div>

            </div>

        </div>

    </main>
</div>

<script>
    const ctx = document.getElementById('revenueChart').getContext('2d');

    
    // Gradient Fill
    let gradient = ctx.createLinearGradient(0, 0, 0, 300);
    gradient.addColorStop(0, 'rgba(99, 102, 241, 0.2)'); // Primary Blue/Indigo
    gradient.addColorStop(1, 'rgba(99, 102, 241, 0.0)');

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($dates); ?>,
            datasets: [{
                label: 'Revenue',
                data: <?php echo json_encode($revenues); ?>,
                borderColor: '#6366f1',
                backgroundColor: gradient,
                borderWidth: 3,
                tension: 0.4,
                pointBackgroundColor: '#fff',
                pointBorderColor: '#6366f1',
                pointBorderWidth: 2,
                pointRadius: 0,
                pointHoverRadius: 6,
                pointHoverBackgroundColor: '#6366f1',
                pointHoverBorderColor: '#fff',
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: 'rgba(15, 23, 42, 0.9)',
                    titleColor: '#fff',
                    bodyColor: '#94a3b8',
                    borderColor: 'rgba(255,255,255,0.1)',
                    borderWidth: 1,
                    padding: 12,
                    displayColors: false,
                    callbacks: {
                        label: function(context) {
                            return '$' + context.parsed.y.toFixed(2);
                        }
                    }
                }
            },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: { color: '#64748b', font: { family: 'Inter' } }
                },
                y: {
                    grid: { color: 'rgba(255, 255, 255, 0.05)', borderDash: [5, 5] },
                    ticks: { color: '#64748b', font: { family: 'Inter' }, callback: function(value) { return '$' + value; } },
                    border: { display: false }
                }
            }
        }
    });
</script>
</body>
</html>
