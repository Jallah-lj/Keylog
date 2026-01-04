<?php
session_start();
require_once '../../config/database.php';
require_once '../../includes/icons.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') != 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

// Fetch All Transactions
$query = "
    SELECT b.booking_date, b.ticket_code, b.status, t.price, t.name as ticket_name,
           e.title as event_title, u.name as attendee_name, o.name as organizer_name
    FROM bookings b
    JOIN events e ON b.event_id = e.id
    JOIN users u ON b.user_id = u.id
    LEFT JOIN ticket_types t ON b.ticket_type_id = t.id
    LEFT JOIN users o ON e.organizer_id = o.id
    ORDER BY b.booking_date DESC
    LIMIT 100
";
$stmt = $pdo->query($query);
$transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate Totals
$total_rev_stmt = $pdo->query("SELECT SUM(t.price) FROM bookings b JOIN ticket_types t ON b.ticket_type_id = t.id WHERE b.status = 'paid'");
$total_revenue = $total_rev_stmt->fetchColumn() ?: 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Global Transactions - Admin</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body style="background-color: var(--dark-bg);">

<div class="admin-layout">
    <?php include 'sidebar.php'; ?>

    <main class="main-content">
        <?php include 'admin_header.php'; ?>


        <div class="dashboard-panel" style="margin-bottom:30px;">
            <h3 class="panel-title">Financial Overview</h3>
            <div class="metric-grid" style="grid-template-columns: repeat(3, 1fr); margin-top:20px;">
                <div class="metric-card">
                    <span class="metric-title">Total Platform Revenue</span>
                    <span class="metric-value">$<?php echo number_format($total_revenue, 2); ?></span>
                </div>
                <div class="metric-card">
                    <span class="metric-title">Recent Transactions</span>
                    <span class="metric-value"><?php echo count($transactions); ?></span>
                    <div class="metric-trend">Last 100 bookings</div>
                </div>
                <div class="metric-card">
                    <span class="metric-title">Status</span>
                    <span class="metric-value" style="color:var(--success);">Active</span>
                </div>
            </div>
        </div>

        <div class="dashboard-panel" style="padding:0; overflow:hidden;">
            <table class="modern-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Event</th>
                        <th>Attendee</th>
                        <th>Organizer</th>
                        <th>Amount</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(count($transactions) > 0): ?>
                        <?php foreach($transactions as $txn): ?>
                        <tr class="table-row-hover">
                            <td style="color:var(--text-muted); font-size:0.9rem;">
                                <?php echo date('M j, Y H:i', strtotime($txn['booking_date'])); ?>
                            </td>
                            <td>
                                <div style="font-weight:600; color:#fff;"><?php echo htmlspecialchars($txn['event_title']); ?></div>
                                <div style="font-size:0.8rem; color:var(--text-muted);"><?php echo htmlspecialchars($txn['ticket_code']); ?></div>
                            </td>
                            <td><?php echo htmlspecialchars($txn['attendee_name']); ?></td>
                            <td><?php echo htmlspecialchars($txn['organizer_name']); ?></td>
                            <td style="font-weight:bold; color:#fff;">
                                $<?php echo number_format($txn['price'] ?? 0, 2); ?>
                            </td>
                            <td>
                                <?php if($txn['status'] == 'paid'): ?>
                                    <span style="color:var(--success); background:rgba(16, 185, 129, 0.1); padding:2px 8px; border-radius:4px; font-size:0.85rem;">Paid</span>
                                <?php else: ?>
                                    <span style="color:var(--warning); background:rgba(245, 158, 11, 0.1); padding:2px 8px; border-radius:4px; font-size:0.85rem;"><?php echo ucfirst($txn['status']); ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="6" style="padding:40px; text-align:center; color:var(--text-muted);">No transactions found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </main>
</div>
</body>
</html>
