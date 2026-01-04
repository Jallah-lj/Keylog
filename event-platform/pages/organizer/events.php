<?php
session_start();
require_once '../../config/database.php';
require_once '../../includes/icons.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') != 'organizer') {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Filter Logic
$filter = $_GET['filter'] ?? 'all';
$query = "SELECT * FROM events WHERE organizer_id = ?";
$params = [$user_id];

if ($filter == 'upcoming') {
    $query .= " AND event_date >= NOW()";
} elseif ($filter == 'past') {
    $query .= " AND event_date < NOW()";
}

$query .= " ORDER BY event_date DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$events = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Events - Organizer Panel</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body style="background-color: var(--dark-bg);">

<div class="admin-layout">
    <?php include 'sidebar.php'; ?>

    <main class="main-content">
        <div class="top-bar">
            <div class="breadcrumbs">Organizer / Events</div>
        </div>

        <div class="dashboard-panel">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; flex-wrap:wrap; gap:15px;">
                <h3 class="panel-title" style="margin:0;">Event Management</h3>
                <div style="display:flex; gap:10px;">
                    <a href="events.php?filter=all" class="action-btn" style="<?php echo $filter=='all' ? 'background:var(--primary); color:#fff;' : 'background:rgba(255,255,255,0.05); color:var(--text-muted);'; ?>">All</a>
                    <a href="events.php?filter=upcoming" class="action-btn" style="<?php echo $filter=='upcoming' ? 'background:var(--primary); color:#fff;' : 'background:rgba(255,255,255,0.05); color:var(--text-muted);'; ?>">Upcoming</a>
                    <a href="events.php?filter=past" class="action-btn" style="<?php echo $filter=='past' ? 'background:var(--primary); color:#fff;' : 'background:rgba(255,255,255,0.05); color:var(--text-muted);'; ?>">Past</a>
                    <a href="/pages/events/create.php" class="hero-btn" style="padding: 8px 16px; font-size: 0.9rem; text-decoration:none;">+ New Event</a>
                </div>
            </div>

            <table class="modern-table">
                <thead>
                    <tr>
                        <th>Event Name</th>
                        <th>Date & Time</th>
                        <th>Status</th>
                        <th>Stats</th>
                        <th style="text-align:right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(count($events) > 0): ?>
                        <?php foreach($events as $e): ?>
                        <tr class="table-row-hover">
                            <td>
                                <div style="font-weight:600; color:#fff;"><?php echo htmlspecialchars($e['title']); ?></div>
                                <div style="font-size:0.8rem; color:var(--text-muted);"><?php echo htmlspecialchars($e['location']); ?></div>
                            </td>
                            <td><?php echo date('M j, Y g:i A', strtotime($e['event_date'])); ?></td>
                            <td>
                                <span class="status-pill status-<?php echo $e['status']; ?>"><?php echo ucfirst($e['status']); ?></span>
                            </td>
                            <td>
                                <!-- Placeholder for sold count if expensive to query in loop -->
                                <span style="font-size:0.85rem; color:var(--text-muted);">- Sold</span>
                            </td>
                            <td style="text-align:right;">
                                <div style="display:flex; justify-content:flex-end; gap:8px;">
                                    <a href="/pages/event_details.php?id=<?php echo $e['id']; ?>" target="_blank" class="action-btn" style="padding:5px 10px; font-size:0.8rem; background:rgba(255, 255, 255, 0.1); color:#fff;" title="View Public Page">Preview</a>
                                    <a href="/pages/events/edit.php?id=<?php echo $e['id']; ?>" class="action-btn" style="padding:5px 10px; font-size:0.8rem;">Edit</a>
                                    <a href="/pages/events/attendees.php?id=<?php echo $e['id']; ?>" class="action-btn" style="padding:5px 10px; font-size:0.8rem; background:rgba(14, 165, 233, 0.1); color:#0ea5e9;">Attendees</a>
                                    <a href="/pages/events/delete.php?id=<?php echo $e['id']; ?>" style="color:var(--danger); display:inline-flex; align-items:center;" onclick="return confirm('Delete event?')">
                                        <?php echo Icons::get('trash', 'width:14px; height:14px;'); ?>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="5" style="text-align:center; padding:40px; color:var(--text-muted);">No events found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>

</body>
</html>
