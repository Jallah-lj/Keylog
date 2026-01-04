<?php
session_start();
require_once '../../config/database.php';

// Auth Check
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

// Handle Actions (Approve/Reject/Delete) - Same as dashboard for convenience
if (isset($_GET['approve_event'])) {
    $stmt = $pdo->prepare("UPDATE events SET status='published' WHERE id=?");
    $stmt->execute([$_GET['approve_event']]);
    header("Location: events.php?msg=approved"); exit();
}
if (isset($_GET['reject_event'])) {
    $stmt = $pdo->prepare("UPDATE events SET status='rejected' WHERE id=?");
    $stmt->execute([$_GET['reject_event']]);
    header("Location: events.php?msg=rejected"); exit();
}
if (isset($_GET['delete_event'])) {
    $stmt = $pdo->prepare("DELETE FROM events WHERE id=?");
    $stmt->execute([$_GET['delete_event']]);
    header("Location: events.php?msg=deleted"); exit();
}

// Fetch All Events
$stmt = $pdo->query("SELECT e.*, u.name as organizer FROM events e JOIN users u ON e.organizer_id = u.id ORDER BY e.created_at DESC");
$events = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Events - Admin Panel</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
        .status-pill {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        .status-published { background: rgba(52, 211, 153, 0.2); color: #34d399; }
        .status-pending { background: rgba(250, 204, 21, 0.2); color: #facc15; }
        .status-rejected { background: rgba(248, 113, 113, 0.2); color: #f87171; }
    </style>
</head>
<body style="background-color: var(--dark-bg);">

<div class="admin-layout">
    <?php include 'sidebar.php'; ?>

    <main class="main-content">
        <?php include 'admin_header.php'; ?>


        <div class="dashboard-panel">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                <h3 class="panel-title" style="margin:0;">Global Event Management</h3>
            </div>

            <?php if(isset($_GET['msg'])): ?>
                <div style="background:rgba(255, 255, 255, 0.05); color:var(--accent); padding:12px; border-radius:8px; margin-bottom:20px; border:1px solid var(--border);">
                    Action completed: <?php echo htmlspecialchars($_GET['msg']); ?>
                </div>
            <?php endif; ?>

            <table class="modern-table">
                <thead>
                    <tr>
                        <th>Event</th>
                        <th>Organizer</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th style="text-align:right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($events as $e): ?>
                    <tr class="table-row-hover">
                        <td>
                            <div style="font-weight:600; color:#fff;"><?php echo htmlspecialchars($e['title']); ?></div>
                            <div style="font-size:0.8rem; color:var(--text-muted);"><?php echo htmlspecialchars($e['location']); ?></div>
                        </td>
                        <td><?php echo htmlspecialchars($e['organizer']); ?></td>
                        <td><?php echo date('M j, Y', strtotime($e['event_date'])); ?></td>
                        <td>
                            <span class="status-pill status-<?php echo $e['status']; ?>">
                                <?php echo $e['status']; ?>
                            </span>
                        </td>
                        <td style="text-align:right; display:flex; justify-content:flex-end; gap:10px;">
                            <a href="../events/attendees.php?id=<?php echo $e['id']; ?>" style="color:var(--accent); text-decoration:none; display:inline-flex;" title="View Attendees">
                                <?php echo Icons::get('users', 'width:20px; height:20px;'); ?>
                            </a>
                            <a href="../events/edit.php?id=<?php echo $e['id']; ?>" style="color:var(--primary-light); text-decoration:none; display:inline-flex;" title="Edit">
                                <?php echo Icons::get('edit', 'width:20px; height:20px;'); ?>
                            </a>
                            <?php if($e['status'] == 'pending'): ?>
                                <a href="?approve_event=<?php echo $e['id']; ?>" style="color:var(--success); text-decoration:none; display:inline-flex;" title="Approve">
                                    <?php echo Icons::get('check', 'width:20px; height:20px;'); ?>
                                </a>
                                <a href="?reject_event=<?php echo $e['id']; ?>" style="color:var(--warning); text-decoration:none; display:inline-flex;" title="Reject">
                                    <?php echo Icons::get('x', 'width:20px; height:20px;'); ?>
                                </a>
                            <?php endif; ?>
                            <a href="?delete_event=<?php echo $e['id']; ?>" style="color:var(--danger); text-decoration:none; display:inline-flex;" onclick="return confirm('Delete event?')" title="Delete">
                                <?php echo Icons::get('trash', 'width:10px; height:10px;'); ?>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>

</body>
</html>
