<?php
session_start();
require_once '../../config/database.php';
require_once '../../includes/icons.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') != 'organizer') {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch Events for Report Filter
$events_stmt = $pdo->prepare("SELECT id, title FROM events WHERE organizer_id = ? ORDER BY event_date DESC");
$events_stmt->execute([$user_id]);
$my_events = $events_stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reports - Organizer Panel</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body style="background-color: var(--dark-bg);">

<div class="admin-layout">
    <?php include 'sidebar.php'; ?>

    <main class="main-content">
        <div class="top-bar">
            <div class="breadcrumbs">Organizer / Reports</div>
        </div>

        <!-- Sales Report -->
        <div class="dashboard-panel" style="margin-bottom:30px;">
            <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:20px;">
                <div>
                    <h3 class="panel-title" style="margin:0;">Sales Report</h3>
                    <p style="color:var(--text-muted); font-size:0.9rem; margin-top:5px;">Export financial data for your events.</p>
                </div>
                <div style="width:40px; height:40px; background:rgba(16, 185, 129, 0.1); border-radius:8px; display:flex; align-items:center; justify-content:center; color:var(--success);">
                    <?php echo Icons::get('trend-up', 'width:24px; height:24px;'); ?>
                </div>
            </div>
            
            <form action="export_sales.php" method="post" style="display:grid; grid-template-columns: 2fr 1fr 1fr auto; gap:15px; align-items:end;">
                <div>
                    <label style="display:block; color:var(--text-muted); margin-bottom:8px;">Select Event</label>
                    <select name="event_id" style="width:100%; padding:10px; background:rgba(255,255,255,0.05); border:1px solid var(--border); border-radius:8px; color:#fff;">
                        <option value="all">All Events</option>
                        <?php foreach($my_events as $evt): ?>
                            <option value="<?php echo $evt['id']; ?>"><?php echo htmlspecialchars($evt['title']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                     <label style="display:block; color:var(--text-muted); margin-bottom:8px;">Format</label>
                     <select name="format" style="width:100%; padding:10px; background:rgba(255,255,255,0.05); border:1px solid var(--border); border-radius:8px; color:#fff;">
                         <option value="csv">CSV (Excel)</option>
                         <option value="json">JSON</option>
                     </select>
                </div>
                <div>
                    <button type="button" class="hero-btn" onclick="alert('Export logic would go here!')" style="width:100%; padding: 11px;">Download Report</button>
                    <!-- Actual submit would be: <button type="submit" ... -->
                </div>
            </form>
        </div>

        <!-- Attendees Report -->
        <div class="dashboard-panel">
            <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:20px;">
                <div>
                    <h3 class="panel-title" style="margin:0;">Attendee Lists</h3>
                    <p style="color:var(--text-muted); font-size:0.9rem; margin-top:5px;">Download attendee details for check-in or marketing.</p>
                </div>
                <div style="width:40px; height:40px; background:rgba(59, 130, 246, 0.1); border-radius:8px; display:flex; align-items:center; justify-content:center; color:#3b82f6;">
                    <?php echo Icons::get('users', 'width:24px; height:24px;'); ?>
                </div>
            </div>
            
            <form action="export_attendees.php" method="post" style="display:grid; grid-template-columns: 2fr 1fr 1fr auto; gap:15px; align-items:end;">
                <div>
                    <label style="display:block; color:var(--text-muted); margin-bottom:8px;">Select Event</label>
                    <select name="event_id" style="width:100%; padding:10px; background:rgba(255,255,255,0.05); border:1px solid var(--border); border-radius:8px; color:#fff;">
                        <?php foreach($my_events as $evt): ?>
                            <option value="<?php echo $evt['id']; ?>"><?php echo htmlspecialchars($evt['title']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label style="display:block; color:var(--text-muted); margin-bottom:8px;">Include</label>
                    <div style="display:flex; gap:15px; padding:10px 0; color:#fff;">
                        <label><input type="checkbox" checked> Emails</label>
                        <label><input type="checkbox" checked> Ticket Types</label>
                    </div>
                </div>
                <div>
                    <button type="button" class="hero-btn" onclick="alert('Export logic would go here!')" style="width:100%; padding: 11px;">Download List</button>
                </div>
            </form>
        </div>

    </main>
</div>

</body>
</html>
