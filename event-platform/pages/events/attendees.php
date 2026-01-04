<?php
session_start();
require_once '../../config/database.php';
require_once '../../includes/icons.php';
require_once '../../includes/issue_detector.php';

// Auth Check
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['organizer', 'admin'])) {
    header("Location: ../auth/login.php"); exit();
}

$event_id = $_GET['id'] ?? null;
if (!$event_id) { header("Location: ../organizer/events.php"); exit(); }

// Event Details & Ownership Check
$stmt = $pdo->prepare("SELECT title, event_date FROM events WHERE id = ? AND organizer_id = ?");
$stmt->execute([$event_id, $_SESSION['user_id']]);
$event = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$event) { die("Event not found or permission denied."); }

// Initialize Issue Detector
$detector = new IssueDetector($pdo);

// Handle Check-in Action
if (isset($_GET['checkin'])) {
    $booking_id = $_GET['checkin'];
    $upd = $pdo->prepare("UPDATE bookings SET status='checked_in' WHERE id=? AND event_id=?");
    $upd->execute([$booking_id, $event_id]);
    
    // Log activity
    $log = $pdo->prepare("INSERT INTO activity_logs (booking_id, action_type, performed_by, details) VALUES (?, 'check_in', ?, 'Manual check-in')");
    $log->execute([$booking_id, $_SESSION['user_id']]);
    
    header("Location: attendees.php?id=$event_id&msg=checked_in"); exit();
}

// Fetch Attendees Data
$filter = $_GET['filter'] ?? 'all';
$bookings_stmt = $pdo->prepare("
    SELECT b.*, u.name, u.email, u.avatar, tt.name as ticket_name, tt.price as ticket_price
    FROM bookings b 
    JOIN users u ON b.user_id = u.id 
    LEFT JOIN ticket_types tt ON b.ticket_type_id = tt.id
    WHERE b.event_id = ?
    ORDER BY u.name ASC, b.booking_date DESC
");
$bookings_stmt->execute([$event_id]);
$raw_bookings = $bookings_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get Issue Stats & Support Tickets
$issue_stats = $detector->getIssueStats($event_id);
$support_tickets = $detector->getSupportTickets($event_id);

// Helper Functions
function maskEmail($email) {
    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $parts = explode('@', $email);
        $name = $parts[0];
        $len = strlen($name);
        return substr($name, 0, 1) . str_repeat('*', max(0, $len - 2)) . substr($name, -1) . '@' . $parts[1];
    }
    return '******';
}

function maskName($name) {
    $parts = explode(' ', trim($name));
    if (count($parts) > 1) {
        return $parts[0] . ' ' . substr(end($parts), 0, 1) . '.';
    }
    return $name;
}

function maskTicket($code) {
    return substr($code, 0, 4) . '-****' . substr($code, -4);
}

function getPaymentBadge($status) {
    $badges = [
        'paid' => ['color' => '#34d399', 'text' => 'Paid', 'icon' => 'check'],
        'pending_payment' => ['color' => '#fbbf24', 'text' => 'Pending', 'icon' => 'clock'],
        'failed' => ['color' => '#f87171', 'text' => 'Failed', 'icon' => 'x'],
        'refunded' => ['color' => '#94a3b8', 'text' => 'Refunded', 'icon' => 'arrow-path'],
        'cancelled' => ['color' => '#94a3b8', 'text' => 'Cancelled', 'icon' => 'no-symbol'],
        'disputed' => ['color' => '#ff6b6b', 'text' => 'Disputed', 'icon' => 'exclamation-triangle']
    ];
    return $badges[$status] ?? $badges['paid'];
}

// Group Bookings by User
$attendees = [];
$total_attendees = count($raw_bookings);
$checked_in = 0;
$total_revenue = 0;

foreach ($raw_bookings as $booking) {
    $uid = $booking['user_id'];
    if (!isset($attendees[$uid])) {
        $attendees[$uid] = [
            'user' => [
                'name' => $booking['name'],
                'masked_name' => maskName($booking['name']),
                'email' => $booking['email'],
                'masked_email' => maskEmail($booking['email']),
                'avatar' => $booking['avatar']
            ],
            'tickets' => [],
            'stats' => ['total' => 0, 'checked_in' => 0],
            'issues' => []
        ];
    }
    
    // Detect issues for this booking
    $booking_issues = $detector->detectIssues($booking);
    if (!empty($booking_issues)) {
        $attendees[$uid]['issues'] = array_merge($attendees[$uid]['issues'], $booking_issues);
    }
    
    $attendees[$uid]['tickets'][] = $booking;
    $attendees[$uid]['stats']['total']++;
    
    if ($booking['status'] == 'checked_in') {
        $attendees[$uid]['stats']['checked_in']++;
        $checked_in++;
    }
    
    $total_revenue += ($booking['ticket_price'] ?? 0);
}

// Apply Filter
if ($filter != 'all') {
    $attendees = array_filter($attendees, function($att) use ($filter) {
        if ($filter == 'issues') return !empty($att['issues']);
        if ($filter == 'pending') {
            foreach ($att['tickets'] as $t) {
                if ($t['payment_status'] == 'pending_payment') return true;
            }
        }
        if ($filter == 'failed') {
            foreach ($att['tickets'] as $t) {
                if ($t['payment_status'] == 'failed') return true;
            }
        }
        return false;
    });
}

$turnout = $total_attendees > 0 ? round(($checked_in / $total_attendees) * 100) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Attendees - Event Platform</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
        .privacy-blur { filter: blur(4px); transition: filter 0.3s; user-select: none; }
        .privacy-revealed { filter: none; user-select: auto; }
        
        .ticket-pill {
            display: inline-flex; align-items: center; gap: 8px;
            background: rgba(255,255,255,0.05); border: 1px solid var(--border);
            padding: 4px 10px; border-radius: 4px; font-size: 0.85em; margin-top: 4px;
            cursor: pointer; transition: all 0.2s;
        }
        .ticket-pill:hover { background: rgba(255,255,255,0.1); }
        .ticket-pill.checked-in { border-color: var(--success); background: rgba(52, 211, 153, 0.1); }
        
        .issue-badge {
            display: inline-flex; align-items: center; gap: 5px;
            padding: 3px 8px; border-radius: 12px; font-size: 0.75em;
            font-weight: 600; margin-right: 4px; margin-top: 4px;
        }
        .issue-high { background: rgba(248, 113, 113, 0.2); color: #f87171; border: 1px solid #f87171; }
        .issue-medium { background: rgba(251, 191, 36, 0.2); color: #fbbf24; border: 1px solid #fbbf24; }
        .issue-critical { background: rgba(255, 107, 107, 0.2); color: #ff6b6b; border: 1px solid #ff6b6b; }
        
        .action-menu {
            position: relative;
            display: inline-block;
        }
        .action-dropdown {
            display: none;
            position: absolute;
            right: 0;
            top: 100%;
            background: rgba(15, 23, 42, 0.98);
            backdrop-filter: blur(20px);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 8px;
            min-width: 200px;
            z-index: 1000;
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
        }
        .action-menu:hover .action-dropdown { display: block; }
        .action-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 12px;
            color: var(--text-main);
            text-decoration: none;
            border-radius: 6px;
            transition: all 0.2s;
            font-size: 0.9em;
        }
        .action-item:hover {
            background: rgba(255,255,255,0.1);
        }
        
        .support-panel {
            position: fixed;
            right: 20px;
            bottom: 20px;
            background: var(--card-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 15px;
            max-width: 300px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            z-index: 100;
        }
        
        .filter-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        .filter-tab {
            padding: 8px 16px;
            background: rgba(255,255,255,0.05);
            border: 1px solid var(--border);
            border-radius: 20px;
            color: var(--text-muted);
            text-decoration: none;
            font-size: 0.9em;
            transition: all 0.2s;
        }
        .filter-tab:hover, .filter-tab.active {
            background: var(--primary);
            color: #fff;
            border-color: var(--primary);
        }
    </style>
</head>
<body style="background-color: var(--dark-bg);">
    
<div class="admin-layout">
    <?php 
    if ($_SESSION['user_role'] == 'admin') {
        include '../admin/sidebar.php';
    } else {
        include '../organizer/sidebar.php';
    }
    ?>

    <main class="main-content">
        <div class="top-bar">
            <div class="breadcrumbs">
                <?php echo $_SESSION['user_role'] == 'admin' ? 'Admin / Management' : 'Organizer / Events'; ?> / Attendees
            </div>
        </div>

        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
            <h2 style="margin:0; font-size:1.5rem; color:#fff;"><?php echo htmlspecialchars($event['title']); ?>: Attendees</h2>
             <label style="display: flex; align-items: center; gap: 10px; font-size: 0.9em; color: var(--text-muted); cursor: pointer;">
                <input type="checkbox" id="privacyToggle" onchange="togglePrivacy()" style="accent-color: var(--primary);">
                Show Sensitive Data
            </label>
        </div>
                <span class="metric-title">Total Tickets</span>
                <span class="metric-value"><?php echo $total_attendees; ?></span>
            </div>
            <div class="metric-card">
                <span class="metric-title">Checked In</span>
                <span class="metric-value"><?php echo $checked_in; ?></span>
                <div class="metric-trend" style="color: var(--warning);"><?php echo $turnout; ?>% Turnout</div>
            </div>
            <div class="metric-card">
                <span class="metric-title">Revenue</span>
                <span class="metric-value">$<?php echo number_format($total_revenue, 2); ?></span>
            </div>
            <?php if ($issue_stats['failed_payments'] > 0 || $issue_stats['pending_payments'] > 0): ?>
            <div class="metric-card" style="border-color: #f87171;">
                <span class="metric-title">Issues</span>
                <span class="metric-value" style="color: #f87171;"><?php echo $issue_stats['failed_payments'] + $issue_stats['pending_payments']; ?></span>
                <div class="metric-trend" style="color: #f87171;">Needs Attention</div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Filters -->
        <div class="filter-tabs">
            <a href="?id=<?php echo $event_id; ?>" class="filter-tab <?php echo $filter == 'all' ? 'active' : ''; ?>">All Attendees</a>
            <a href="?id=<?php echo $event_id; ?>&filter=issues" class="filter-tab <?php echo $filter == 'issues' ? 'active' : ''; ?>" style="display:flex; align-items:center; gap:8px;">
                <?php echo Icons::get('exclamation-triangle', 'width:14px; height:14px;'); ?> With Issues <?php if($issue_stats['failed_payments'] + $issue_stats['pending_payments'] > 0) echo '(' . ($issue_stats['failed_payments'] + $issue_stats['pending_payments']) . ')'; ?>
            </a>
            <a href="?id=<?php echo $event_id; ?>&filter=pending" class="filter-tab <?php echo $filter == 'pending' ? 'active' : ''; ?>" style="display:flex; align-items:center; gap:8px;">
                <?php echo Icons::get('clock', 'width:14px; height:14px;'); ?> Pending Payment
            </a>
            <a href="?id=<?php echo $event_id; ?>&filter=failed" class="filter-tab <?php echo $filter == 'failed' ? 'active' : ''; ?>" style="display:flex; align-items:center; gap:8px;">
                <?php echo Icons::get('x', 'width:14px; height:14px;'); ?> Failed Payment
            </a>
        </div>

        <!-- Controls -->
        <div style="display: flex; justify-content: space-between; flex-wrap: wrap; gap: 20px; margin-bottom: 20px;">
            <div style="position: relative; flex: 1; max-width: 400px;">
                <input type="text" id="searchInput" placeholder="Search attendee..." onkeyup="filterTable()" 
                       style="width: 100%; padding: 12px 15px 12px 45px; border-radius: 50px; background: rgba(255,255,255,0.05); border: 1px solid var(--border); color: #fff; margin-bottom:0;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="var(--text-muted)" stroke-width="2" style="position: absolute; left: 15px; top: 50%; transform: translateY(-50%);">
                    <circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                </svg>
            </div>
            <button onclick="exportTableToCSV('attendees.csv')" style="width: auto; background: rgba(255,255,255,0.1); border: 1px solid var(--border); color: #fff;">
                Download CSV
            </button>
        </div>

        <!-- Table -->
        <div class="dashboard-panel" style="padding: 0; overflow: hidden;">
            <?php if(count($attendees) > 0): ?>
                <div style="overflow-x: auto;">
                    <table class="modern-table" id="attendeesTable">
                        <thead>
                            <tr style="background: rgba(0,0,0,0.2);">
                                <th>Attendee</th>
                                <th>Tickets & Status</th>
                                <th>Issues</th>
                                <th>Check-In</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($attendees as $att): ?>
                            <tr class="table-row-hover">
                                <td>
                                    <div style="display: flex; align-items: center; gap: 15px;">
                                        <?php if($att['user']['avatar']): ?>
                                            <img src="../../uploads/<?php echo htmlspecialchars($att['user']['avatar']); ?>" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">
                                        <?php else: ?>
                                            <div style="width: 40px; height: 40px; border-radius: 50%; background: linear-gradient(135deg, var(--primary), var(--secondary)); color: #fff; display: flex; align-items: center; justify-content: center; font-weight: bold;">
                                                <?php echo strtoupper(substr($att['user']['name'], 0, 1)); ?>
                                            </div>
                                        <?php endif; ?>
                                        <div>
                                            <div style="font-weight: 600; color: #fff;" class="privacy-name" data-full="<?php echo htmlspecialchars($att['user']['name']); ?>">
                                                <?php echo htmlspecialchars($att['user']['masked_name']); ?>
                                            </div>
                                            <div style="font-size: 0.85rem; color: var(--text-muted);" class="privacy-email" data-full="<?php echo htmlspecialchars($att['user']['email']); ?>">
                                                <?php echo htmlspecialchars($att['user']['masked_email']); ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div style="display: flex; flex-direction: column; gap: 5px;">
                                        <?php foreach($att['tickets'] as $t): 
                                            $badge = getPaymentBadge($t['payment_status']);
                                        ?>
                                            <div class="ticket-pill <?php echo $t['status'] == 'checked_in' ? 'checked-in' : ''; ?>" style="border-left: 3px solid <?php echo $badge['color']; ?>;">
                                                <span style="color: var(--accent); font-family: monospace; font-size: 0.85em;" class="privacy-code" data-full="<?php echo htmlspecialchars($t['ticket_code']); ?>">
                                                    <?php echo maskTicket($t['ticket_code']); ?>
                                                </span>
                                                <span style="color: var(--text-muted); font-size: 0.85em;">
                                                    • <?php echo htmlspecialchars($t['ticket_name'] ?? 'Standard'); ?>
                                                </span>
                                                <span style="color: <?php echo $badge['color']; ?>; display: flex; align-items: center;" title="<?php echo $badge['text']; ?>">
                                                    <?php echo Icons::get($badge['icon'], 'width:14px; height:14px;'); ?>
                                                </span>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </td>
                                <td>
                                    <?php if(!empty($att['issues'])): ?>
                                        <?php foreach(array_slice($att['issues'], 0, 2) as $issue): ?>
                                            <div class="issue-badge issue-<?php echo $issue['severity']; ?>" title="<?php echo htmlspecialchars($issue['details'] ?? ''); ?>">
                                                <?php echo htmlspecialchars($issue['message']); ?>
                                            </div>
                                        <?php endforeach; ?>
                                        <?php if(count($att['issues']) > 2): ?>
                                            <div style="font-size: 0.75em; color: var(--text-muted); margin-top: 4px;">
                                                +<?php echo count($att['issues']) - 2; ?> more
                                            </div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span style="color: var(--success); display: flex;">
                                            <?php echo Icons::get('check', 'width:20px; height:20px;'); ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php 
                                        $checked = $att['stats']['checked_in'];
                                        $total = $att['stats']['total'];
                                        $all_done = $checked == $total;
                                    ?>
                                    <?php if($all_done): ?>
                                        <span class="status-badge status-success">All Checked In</span>
                                    <?php elseif($checked > 0): ?>
                                        <span class="status-badge status-warning"><?php echo $checked; ?>/<?php echo $total; ?> Arrived</span>
                                    <?php else: ?>
                                        <span class="status-badge" style="background: rgba(255,255,255,0.1); color: #ccc;">Not Arrived</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div style="display: flex; gap: 8px; align-items: center;">
                                        <?php 
                                            $next_ticket_id = null;
                                            foreach($att['tickets'] as $t) {
                                                if ($t['status'] != 'checked_in') {
                                                    $next_ticket_id = $t['id'];
                                                    break;
                                                }
                                            }
                                        ?>
                                        <?php if($next_ticket_id): ?>
                                            <a href="?id=<?php echo $event_id; ?>&checkin=<?php echo $next_ticket_id; ?>" class="action-btn" style="background: var(--success); text-decoration: none; font-size: 0.85rem; padding: 6px 12px;">
                                                Check In
                                            </a>
                                        <?php endif; ?>
                                        
                                        <div class="action-menu">
                                            <button style="background: rgba(255,255,255,0.1); border: 1px solid var(--border); color: #fff; padding: 6px 12px; border-radius: 6px; cursor: pointer; font-size: 0.85rem;">
                                                ⋮
                                            </button>
                                            <div class="action-dropdown">
                                                <a href="#" class="action-item" onclick="alert('Email feature coming soon'); return false;">
                                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
                                                    Send Help Email
                                                </a>
                                                <a href="#" class="action-item" onclick="alert('Resend feature coming soon'); return false;">
                                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 4 23 10 17 10"></polyline><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"></path></svg>
                                                    Resend Confirmation
                                                </a>
                                                <a href="#" class="action-item" onclick="alert('View history feature coming soon'); return false;">
                                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                                                    View History
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div style="padding: 50px; text-align: center; color: var(--text-muted);">
                    <p>No attendees match the current filter.</p>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <?php if(count($support_tickets) > 0): ?>
    <div class="support-panel">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
            <strong style="color: #fff;">Support Requests</strong>
            <span style="background: #f87171; color: #fff; padding: 2px 8px; border-radius: 12px; font-size: 0.75em;">
                <?php echo count($support_tickets); ?>
            </span>
        </div>
        <?php foreach(array_slice($support_tickets, 0, 3) as $ticket): ?>
            <div style="padding: 8px; background: rgba(0,0,0,0.2); border-radius: 6px; margin-bottom: 8px; font-size: 0.85em;">
                <div style="color: #fff; font-weight: 600;"><?php echo htmlspecialchars($ticket['user_name']); ?></div>
                <div style="color: var(--text-muted); font-size: 0.9em;"><?php echo htmlspecialchars($ticket['issue_type']); ?></div>
            </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <script>
        function togglePrivacy() {
            const isChecked = document.getElementById('privacyToggle').checked;
            document.querySelectorAll('.privacy-name').forEach(el => {
                el.textContent = isChecked ? el.getAttribute('data-full') : maskName(el.getAttribute('data-full'));
            });
            document.querySelectorAll('.privacy-email').forEach(el => {
                el.textContent = isChecked ? el.getAttribute('data-full') : maskEmail(el.getAttribute('data-full'));
            });
            document.querySelectorAll('.privacy-code').forEach(el => {
                el.textContent = isChecked ? el.getAttribute('data-full') : maskTicket(el.getAttribute('data-full'));
            });
        }

        function maskName(str) {
            let parts = str.trim().split(' ');
            if (parts.length > 1) return parts[0] + ' ' + parts[parts.length-1][0] + '.';
            return str;
        }
        function maskEmail(str) {
            let parts = str.split('@');
            if (parts.length < 2) return '******';
            let name = parts[0];
            return name[0] + '*'.repeat(Math.max(0, name.length - 2)) + name[name.length-1] + '@' + parts[1];
        }
        function maskTicket(str) {
            return str.substring(0, 4) + '-****' + str.substring(str.length - 4);
        }

        function filterTable() {
            let input = document.getElementById("searchInput").value.toUpperCase();
            let rows = document.getElementById("attendeesTable").getElementsByTagName("tr");
            for (let i = 1; i < rows.length; i++) {
                let text = rows[i].innerText.toUpperCase();
                rows[i].style.display = text.includes(input) ? "" : "none";
            }
        }

        function exportTableToCSV(filename) {
            var csv = [];
            var rows = document.querySelectorAll("table tr");
            for (var i = 0; i < rows.length; i++) {
                var row = [], cols = rows[i].querySelectorAll("td, th");
                for (var j = 0; j < cols.length; j++) {
                    let data = cols[j].innerText.replace(/(\r\n|\n|\r)/gm, " ").trim();
                    data = data.replace(/\s+/g, ' ');
                    row.push('"' + data.replace(/"/g, '""') + '"');
                }
                csv.push(row.join(","));        
            }
            var blob = new Blob([csv.join("\n")], {type: "text/csv"});
            var link = document.createElement("a");
            link.href = window.URL.createObjectURL(blob);
            link.download = filename;
            link.click();
        }
    </script>
    </main>
</div>
</body>
</html>
