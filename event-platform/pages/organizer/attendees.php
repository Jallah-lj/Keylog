<?php
session_start();
require_once '../../config/database.php';
require_once '../../includes/icons.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') != 'organizer') {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$search = $_GET['search'] ?? '';
$event_filter = $_GET['event_id'] ?? 'all';

// Fetch Organizer's Events for Filter
$events_stmt = $pdo->prepare("SELECT id, title FROM events WHERE organizer_id = ? ORDER BY event_date DESC");
$events_stmt->execute([$user_id]);
$my_events = $events_stmt->fetchAll(PDO::FETCH_KEY_PAIR);

// Build Query
$query = "
    SELECT b.*, u.name, u.email, u.avatar, e.title as event_title, e.event_date 
    FROM bookings b 
    JOIN users u ON b.user_id = u.id 
    JOIN events e ON b.event_id = e.id 
    WHERE e.organizer_id = ?
";
$params = [$user_id];

if ($event_filter != 'all') {
    $query .= " AND b.event_id = ?";
    $params[] = $event_filter;
}

if ($search) {
    $query .= " AND (u.name LIKE ? OR u.email LIKE ? OR b.ticket_code LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$query .= " ORDER BY b.booking_date DESC LIMIT 50";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$attendees = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Global Attendees - Organizer Panel</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body style="background-color: var(--dark-bg);">

<div class="admin-layout">
    <?php include 'sidebar.php'; ?>

    <main class="main-content">
        <div class="top-bar">
            <div class="breadcrumbs">Organizer / Attendees</div>
        </div>

        <div class="dashboard-panel">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; flex-wrap:wrap; gap:15px;">
                <h3 class="panel-title" style="margin:0;">All Attendees</h3>
                
                <form method="get" style="display:flex; gap:10px; flex:1; max-width:600px; justify-content:flex-end;">
                    <select name="event_id" onchange="this.form.submit()" style="padding:10px; background:rgba(255,255,255,0.05); border:1px solid var(--border); border-radius:8px; color:#fff;">
                        <option value="all">All Events</option>
                        <?php foreach($my_events as $id => $title): ?>
                            <option value="<?php echo $id; ?>" <?php echo $event_filter == $id ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($title); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    
                    <div style="position:relative; flex:1;">
                        <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search name, email, ticket..." style="width:100%; padding:10px 10px 10px 35px; background:rgba(255,255,255,0.05); border:1px solid var(--border); border-radius:8px; color:#fff;">
                        <span style="position:absolute; left:10px; top:50%; transform:translateY(-50%); color:var(--text-muted);">
                            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                        </span>
                    </div>
                    <button type="submit" class="hero-btn" style="padding:0 20px;">Filtrer</button>
                    <?php if($search || $event_filter != 'all'): ?>
                        <a href="attendees.php" style="display:flex; align-items:center; color:var(--text-muted); text-decoration:none; padding:0 10px;">Clear</a>
                    <?php endif; ?>
                </form>
            </div>

            <table class="modern-table">
                <thead>
                    <tr>
                        <th>Attendee</th>
                        <th>Event</th>
                        <th>Ticket Code</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(count($attendees) > 0): ?>
                        <?php foreach($attendees as $row): ?>
                        <tr class="table-row-hover">
                            <td>
                                <div style="display:flex; align-items:center; gap:10px;">
                                    <?php if($row['avatar']): ?>
                                        <img src="../../uploads/<?php echo htmlspecialchars($row['avatar']); ?>" style="width:32px; height:32px; border-radius:50%; object-fit:cover;">
                                    <?php else: ?>
                                        <div style="width:32px; height:32px; border-radius:50%; background:var(--primary); color:#fff; display:flex; align-items:center; justify-content:center; font-weight:bold; font-size:0.8rem;">
                                            <?php echo strtoupper(substr($row['name'], 0, 1)); ?>
                                        </div>
                                    <?php endif; ?>
                                    <div>
                                        <div style="font-weight:600; color:#fff;"><?php echo htmlspecialchars($row['name']); ?></div>
                                        <div style="font-size:0.8rem; color:var(--text-muted);"><?php echo htmlspecialchars($row['email']); ?></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div style="color:#fff;"><?php echo htmlspecialchars($row['event_title']); ?></div>
                                <div style="font-size:0.8rem; color:var(--text-muted);"><?php echo date('M j, Y', strtotime($row['event_date'])); ?></div>
                            </td>
                            <td>
                                <code style="background:rgba(255,255,255,0.1); padding:2px 6px; border-radius:4px; font-family:monospace; color:var(--accent);"><?php echo htmlspecialchars($row['ticket_code']); ?></code>
                            </td>
                            <td>
                                <?php if($row['status'] == 'checked_in'): ?>
                                    <span class="status-pill status-published">Checked In</span>
                                <?php else: ?>
                                    <span class="status-pill" style="background:rgba(255,255,255,0.1); color:var(--text-muted);">Registered</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo date('M j, g:ia', strtotime($row['booking_date'])); ?></td>
                            <td>
                                <a href="/pages/events/attendees.php?id=<?php echo $row['event_id']; ?>&search=<?php echo urlencode($row['ticket_code']); ?>" class="action-btn" style="padding:5px 10px; font-size:0.8rem;">Manage</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="6" style="text-align:center; padding:40px; color:var(--text-muted);">No attendees found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>

</body>
</html>
