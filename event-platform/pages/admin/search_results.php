<?php
session_start();
require_once '../../config/database.php';
require_once '../../includes/icons.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

$query = $_GET['q'] ?? '';
$results = [
    'events' => [],
    'users' => [],
    'transactions' => []
];

if (!empty($query)) {
    $search = "%$query%";
    
    // Search Events
    $stmt = $pdo->prepare("SELECT * FROM events WHERE title LIKE ? OR location LIKE ? LIMIT 10");
    $stmt->execute([$search, $search]);
    $results['events'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Search Users
    $stmt = $pdo->prepare("SELECT * FROM users WHERE name LIKE ? OR email LIKE ? LIMIT 10");
    $stmt->execute([$search, $search]);
    $results['users'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Search Bookings/Transactions
    $stmt = $pdo->prepare("
        SELECT b.*, u.name as user_name, e.title as event_title 
        FROM bookings b 
        JOIN users u ON b.user_id = u.id 
        JOIN events e ON b.event_id = e.id 
        WHERE u.name LIKE ? OR e.title LIKE ? LIMIT 10
    ");
    $stmt->execute([$search, $search]);
    $results['transactions'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Search Results - Admin Dashboard</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body style="background-color: var(--dark-bg);">

<div class="admin-layout">
    <?php include 'sidebar.php'; ?>

    <main class="main-content">
        <?php include 'admin_header.php'; ?>


        <div style="margin-bottom: 30px;">
            <h2 style="font-size: 1.75rem; font-weight: 700; color: #fff; margin-bottom: 5px;">Search Results</h2>
            <p style="color: var(--text-muted); font-size: 0.95rem;">Found matches in events, users, and transactions.</p>
        </div>

        <?php if (empty($results['events']) && empty($results['users']) && empty($results['transactions'])): ?>
            <div class="dashboard-panel" style="text-align:center; padding:50px;">
                <div style="font-size:3rem; margin-bottom:20px; opacity:0.3;"><?php echo Icons::get('search', 'width:64px; height:64px;'); ?></div>
                <h3 style="color:#fff;">No results found for "<?php echo htmlspecialchars($query); ?>"</h3>
                <p style="color:var(--text-muted);">Try searching with different keywords.</p>
            </div>
        <?php else: ?>
            
            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:30px;">
                
                <!-- Events Results -->
                <?php if (!empty($results['events'])): ?>
                    <div class="dashboard-panel">
                        <h3 class="panel-title">Events</h3>
                        <table class="modern-table">
                            <thead><tr><th>Event</th><th>Status</th><th>Action</th></tr></thead>
                            <tbody>
                                <?php foreach($results['events'] as $e): ?>
                                    <tr class="table-row-hover">
                                        <td><?php echo htmlspecialchars($e['title']); ?></td>
                                        <td><span class="badge-outline" style="color:<?php echo $e['status'] == 'published' ? 'var(--success)' : 'var(--warning)'; ?>;"><?php echo ucfirst($e['status']); ?></span></td>
                                        <td><a href="events.php?id=<?php echo $e['id']; ?>" style="color:var(--primary-light);">View</a></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>

                <!-- Users Results -->
                <?php if (!empty($results['users'])): ?>
                    <div class="dashboard-panel">
                        <h3 class="panel-title">Users</h3>
                        <table class="modern-table">
                            <thead><tr><th>Name</th><th>Email</th><th>Role</th></tr></thead>
                            <tbody>
                                <?php foreach($results['users'] as $u): ?>
                                    <tr class="table-row-hover">
                                        <td><?php echo htmlspecialchars($u['name']); ?></td>
                                        <td style="font-size:0.85rem; color:var(--text-muted);"><?php echo htmlspecialchars($u['email']); ?></td>
                                        <td><span style="font-size:0.8rem;"><?php echo ucfirst($u['role']); ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>

            </div>

            <!-- Transactions Results -->
            <?php if (!empty($results['transactions'])): ?>
                <div class="dashboard-panel" style="margin-top:30px;">
                    <h3 class="panel-title">Transactions</h3>
                    <table class="modern-table">
                        <thead><tr><th>User</th><th>Event</th><th>Date</th></tr></thead>
                        <tbody>
                            <?php foreach($results['transactions'] as $t): ?>
                                <tr class="table-row-hover">
                                    <td><?php echo htmlspecialchars($t['user_name']); ?></td>
                                    <td><?php echo htmlspecialchars($t['event_title']); ?></td>
                                    <td style="color:var(--text-muted);"><?php echo date('M j, Y', strtotime($t['booking_date'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

        <?php endif; ?>
    </main>
</div>
</body>
</html>
