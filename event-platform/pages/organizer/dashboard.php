<?php
session_start();
require_once '../../config/database.php';
require_once '../../includes/icons.php';

// Auth Check
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') != 'organizer') {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

// --- Analytics Data (Placeholders/Real) ---
// 1. Total Events
$stmt = $pdo->prepare("SELECT COUNT(*) FROM events WHERE organizer_id = ?");
$stmt->execute([$user_id]);
$total_events = $stmt->fetchColumn();

// 2. Total Tickets Sold (Across all events)
$stmt = $pdo->prepare("
    SELECT COUNT(*) 
    FROM bookings b 
    JOIN events e ON b.event_id = e.id 
    WHERE e.organizer_id = ?
");
$stmt->execute([$user_id]);
$total_sold = $stmt->fetchColumn();

// 3. Total Revenue
$stmt = $pdo->prepare("
    SELECT SUM(COALESCE(tt.price, e.price)) 
    FROM bookings b 
    JOIN events e ON b.event_id = e.id 
    LEFT JOIN ticket_types tt ON b.ticket_type_id = tt.id
    WHERE e.organizer_id = ?
");
$stmt->execute([$user_id]);
$total_revenue = $stmt->fetchColumn() ?: 0;

// 4. Upcoming Events
$stmt = $pdo->prepare("SELECT * FROM events WHERE organizer_id = ? AND event_date >= NOW() ORDER BY event_date ASC LIMIT 3");
$stmt->execute([$user_id]);
$upcoming_events = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Organizer Dashboard - Event Platform</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body style="background-color: var(--dark-bg);">

<div class="admin-layout"> <!-- Reusing admin layout structure -->
    <?php include 'sidebar.php'; ?>

    <main class="main-content">
        <div class="top-bar">
            <div class="breadcrumbs">Organizer / Dashboard</div>
        </div>

        <!-- Welcome Banner -->
        <div style="margin-bottom: 30px;">
            <h2 style="font-size: 2rem; color: #fff; margin-bottom: 5px;">Welcome back, Organizer!</h2>
            <p style="color: var(--text-muted);">Here's what's happening with your events today.</p>
        </div>

        <!-- Analytics Grid -->
        <div class="metrics-grid">
            <div class="metric-card">
                <span class="metric-title">Total Revenue</span>
                <span class="metric-value">$<?php echo number_format($total_revenue, 2); ?></span>
                <div class="metric-trend trend-up">
                    <span style="display:inline-flex; align-items:center;"><?php echo Icons::get('trend-up', 'width:16px; height:16px;'); ?></span> Lifetime
                </div>
            </div>
            
            <div class="metric-card">
                <span class="metric-title">Tickets Sold</span>
                <span class="metric-value"><?php echo number_format($total_sold); ?></span>
                <div class="metric-trend" style="color:var(--secondary)">
                    Across <?php echo $total_events; ?> events
                </div>
            </div>

            <div class="metric-card" style="background: linear-gradient(135deg, rgba(16, 185, 129, 0.1), rgba(6, 182, 212, 0.1)); border: 1px solid rgba(16, 185, 129, 0.2);">
                <span class="metric-title">Active Events</span>
                <span class="metric-value"><?php echo count($upcoming_events); ?></span>
                <div class="metric-trend" style="color:#fff;">
                    Upcoming
                </div>
            </div>
        </div>

        <!-- Quick Actions & Upcoming -->
        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 30px;">
            
            <!-- Upcoming Events Table -->
            <div class="dashboard-panel">
                <div class="panel-header">
                    <h3 class="panel-title">Upcoming Events</h3>
                    <a href="events.php" style="font-size:0.85rem; color:var(--primary-light); text-decoration:none;">View All</a>
                </div>
                
                <?php if (count($upcoming_events) > 0): ?>
                <table class="modern-table">
                    <thead>
                        <tr>
                            <th>Event</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($upcoming_events as $e): ?>
                        <tr class="table-row-hover">
                            <td>
                                <div style="font-weight:600; color:#fff;"><?php echo htmlspecialchars($e['title']); ?></div>
                                <div style="font-size:0.8rem; color:var(--text-muted);"><?php echo htmlspecialchars($e['location']); ?></div>
                            </td>
                            <td><?php echo date('M j, g:ia', strtotime($e['event_date'])); ?></td>
                            <td>
                                <span class="status-pill status-<?php echo $e['status']; ?>"><?php echo ucfirst($e['status']); ?></span>
                            </td>
                            <td>
                                <a href="/pages/events/edit.php?id=<?php echo $e['id']; ?>" style="color:var(--text-muted); padding:5px;">Edit</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                    <div style="text-align:center; padding:30px; color:var(--text-muted);">
                        No upcoming events.
                    </div>
                <?php endif; ?>
            </div>

            <!-- Quick Actions -->
            <div style="display: flex; flex-direction: column; gap: 20px;">
                <a href="/pages/events/create.php" class="dashboard-panel" style="text-decoration:none; display:flex; align-items:center; gap:15px; transition: transform 0.2s;">
                    <div style="background:var(--primary); width:50px; height:50px; border-radius:12px; display:flex; align-items:center; justify-content:center; color:#fff;">
                        <span style="font-size:24px;">+</span>
                    </div>
                    <div>
                        <h4 style="margin:0; color:#fff;">Create New Event</h4>
                        <p style="margin:5px 0 0 0; color:var(--text-muted); font-size:0.9rem;">Launch a new experience</p>
                    </div>
                </a>
                
                <div class="dashboard-panel">
                    <h3 class="panel-title" style="margin-bottom:20px;">Pro Tips</h3>
                    <div style="display: flex; flex-direction: column; gap: 15px;">
                        <div style="display: flex; gap: 12px; align-items: flex-start;">
                            <span style="color:var(--primary-light); background:rgba(99, 102, 241, 0.1); padding:6px; border-radius:8px; line-height:0;">
                                <?php echo Icons::get('settings', 'width:18px; height:18px;'); ?>
                            </span>
                            <span style="color:var(--text-muted); font-size:0.9rem; line-height:1.4;">Customize your event page in Settings.</span>
                        </div>
                        <div style="display: flex; gap: 12px; align-items: flex-start;">
                            <span style="color:var(--success); background:rgba(16, 185, 129, 0.1); padding:6px; border-radius:8px; line-height:0;">
                                <?php echo Icons::get('globe', 'width:18px; height:18px;'); ?>
                            </span>
                            <span style="color:var(--text-muted); font-size:0.9rem; line-height:1.4;">Share your event URL on social media.</span>
                        </div>
                        <div style="display: flex; gap: 12px; align-items: flex-start;">
                            <span style="color:var(--warning); background:rgba(245, 158, 11, 0.1); padding:6px; border-radius:8px; line-height:0;">
                                <?php echo Icons::get('users', 'width:18px; height:18px;'); ?>
                            </span>
                            <span style="color:var(--text-muted); font-size:0.9rem; line-height:1.4;">Check attendees list regularly.</span>
                        </div>
                    </div>
                </div>
            </div>

        </div>

    </main>
</div>

</body>
</html>
