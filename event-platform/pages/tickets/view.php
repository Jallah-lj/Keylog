<?php
session_start();
require_once '../../config/database.php';
require_once '../../includes/qr_code.php';
require_once '../../includes/icons.php';

// ... (existing code)

// Handle Support Ticket Submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_issue'])) {
    $booking_id = $_POST['booking_id'];
    $issue_type = $_POST['issue_type'];
    $description = $_POST['description'];
    
    // Verify ownership
    $verify = $pdo->prepare("SELECT event_id, user_id FROM bookings WHERE id = ? AND user_id = ?");
    $verify->execute([$booking_id, $_SESSION['user_id']]);
    $booking_data = $verify->fetch(PDO::FETCH_ASSOC);
    
    if ($booking_data) {
        $stmt = $pdo->prepare("
            INSERT INTO support_tickets (booking_id, user_id, event_id, issue_type, description, status)
            VALUES (?, ?, ?, ?, ?, 'open')
        ");
        $stmt->execute([
            $booking_id,
            $_SESSION['user_id'],
            $booking_data['event_id'],
            $issue_type,
            $description
        ]);
        
        $_SESSION['success_message'] = "Issue reported successfully. The organizer will be notified.";
        header("Location: view.php?code=" . $_POST['ticket_code']);
        exit();
    }
}

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$ticket_code = $_GET['code'] ?? '';
$stmt = $pdo->prepare("
    SELECT b.*, e.title, e.event_date, e.location, e.description, e.image, e.currency, 
           tt.name as ticket_name, tt.price as ticket_price, u.name as attendee_name
    FROM bookings b
    JOIN events e ON b.event_id = e.id
    LEFT JOIN ticket_types tt ON b.ticket_type_id = tt.id
    JOIN users u ON b.user_id = u.id
    WHERE b.ticket_code = ? AND b.user_id = ?
");
$stmt->execute([$ticket_code, $_SESSION['user_id']]);
$booking = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$booking) {
    die("Invalid ticket.");
}

// Check for existing support tickets
$tickets_stmt = $pdo->prepare("
    SELECT * FROM support_tickets 
    WHERE booking_id = ? AND status != 'closed'
    ORDER BY created_at DESC
");
$tickets_stmt->execute([$booking['id']]);
$existing_tickets = $tickets_stmt->fetchAll(PDO::FETCH_ASSOC);

// Generate QR Code
$base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'];
$qr_code_url = QRCodeGenerator::forTicket($ticket_code, $base_url);

$event_date = strtotime($booking['event_date']);

// Color Coding Logic (VIP, GOLDEN, REGULAR)
$tt_name = strtoupper($booking['ticket_name'] ?? 'REGULAR');
$accent_color = '#94a3b8'; // Default Slate
if (stripos($tt_name, 'VIP') !== false) {
    $accent_color = '#a855f7'; // Purple
} elseif (stripos($tt_name, 'GOLD') !== false) {
    $accent_color = '#fbbf24'; // Gold
} elseif (stripos($tt_name, 'REGULAR') !== false) {
    $accent_color = '#2dd4bf'; // Teal
}

// Fetch Site Name
$site_stmt = $pdo->query("SELECT setting_value FROM site_settings WHERE setting_key = 'site_name'");
$site_name = $site_stmt->fetchColumn() ?: 'Event Platform';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Ticket #<?php echo htmlspecialchars($booking['ticket_code']); ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
        body {
            background: var(--dark-bg);
            font-family: 'Inter', sans-serif;
            margin: 0;
            padding: 20px;
        }
        
        .ticket-wrapper {
            max-width: 900px;
            margin: 0 auto;
        }
        
        .ticket-card {
            background: var(--card-bg);
            backdrop-filter: blur(16px);
            border: 1px solid var(--border);
            border-left: 8px solid <?php echo $accent_color; ?>;
            border-radius: 16px;
            padding: 40px;
            margin-bottom: 20px;
        }
        
        .help-button {
            background: linear-gradient(135deg, #9C4DFF, #00C4B4);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
        }
        .help-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(156, 77, 255, 0.3);
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.8);
            backdrop-filter: blur(4px);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        .modal.active { display: flex; }
        
        .modal-content {
            background: rgba(15, 23, 42, 0.98);
            backdrop-filter: blur(20px);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 30px;
            max-width: 500px;
            width: 90%;
            box-shadow: 0 20px 50px rgba(0,0,0,0.5);
        }
        
        .status-indicator {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: 600;
        }
        .status-paid { background: rgba(52, 211, 153, 0.2); color: #34d399; }
        .status-pending { background: rgba(251, 191, 36, 0.2); color: #fbbf24; }
        .status-failed { background: rgba(248, 113, 113, 0.2); color: #f87171; }
        
        .ticket-section {
            margin-bottom: 30px;
        }
        .ticket-section h3 {
            color: #fff;
            margin-bottom: 15px;
            font-size: 1.1em;
        }
        .ticket-detail {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        .ticket-detail:last-child { border-bottom: none; }
        .detail-label {
            color: var(--text-muted);
            font-size: 0.9em;
        }
        .detail-value {
            color: #fff;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="ticket-wrapper">
        <?php if(isset($_SESSION['success_message'])): ?>
            <div style="background: rgba(52, 211, 153, 0.2); border: 1px solid #34d399; color: #34d399; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                <?php echo htmlspecialchars($_SESSION['success_message']); unset($_SESSION['success_message']); ?>
            </div>
        <?php endif; ?>
        
        <div class="ticket-card">
            <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 30px;">
                <div>
                    <div style="font-size: 11px; font-weight: 800; color: <?php echo $accent_color; ?>; text-transform: uppercase; letter-spacing: 2px; margin-bottom: 5px;">
                        <?php echo htmlspecialchars($site_name); ?> OFFICIAL TICKET
                    </div>
                    <h1 style="color: #fff; margin: 0 0 10px 0;"><?php echo htmlspecialchars($booking['title']); ?></h1>
                    <div class="status-indicator status-<?php echo $booking['payment_status'] ?? 'paid'; ?>">
                        <?php 
                        $status_text = [
                            'paid' => Icons::get('check', 'width:18px; color:var(--success); margin-right:4px;') . ' Paid',
                            'pending_payment' => Icons::get('clock', 'width:18px; color:var(--warning); margin-right:4px;') . ' Payment Pending',
                            'failed' => Icons::get('x', 'width:18px; color:var(--danger); margin-right:4px;') . ' Payment Failed'
                        ];
                        echo $status_text[$booking['payment_status'] ?? 'paid'];
                        ?>
                    </div>
                </div>
                <a href="print.php?code=<?php echo htmlspecialchars($ticket_code); ?>" target="_blank" class="help-button" style="text-decoration: none; display: inline-flex; margin-left: 10px; background: linear-gradient(135deg, #6366f1, #d946ef);">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="6 9 6 2 18 2 18 9"></polyline>
                        <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                        <rect x="6" y="14" width="12" height="8"></rect>
                    </svg>
                    Print Ticket
                </a>
                <button class="help-button" onclick="openIssueModal()">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path>
                        <line x1="12" y1="17" x2="12.01" y2="17"></line>
                    </svg>
                    Report Issue
                </button>
            </div>
            
            <div class="ticket-section">
                <h3>Ticket Details</h3>
                <div class="ticket-detail">
                    <span class="detail-label">Ticket Code</span>
                    <span class="detail-value" style="font-family: monospace; color: var(--accent);"><?php echo htmlspecialchars($booking['ticket_code']); ?></span>
                </div>
                <div class="ticket-detail">
                    <span class="detail-label">Ticket Type</span>
                    <span class="detail-value" style="color: <?php echo $accent_color; ?>;"><?php echo htmlspecialchars($booking['ticket_name'] ?? 'Standard'); ?></span>
                </div>
                <div class="ticket-detail">
                    <span class="detail-label">Attendee</span>
                    <span class="detail-value"><?php echo htmlspecialchars($booking['attendee_name']); ?></span>
                </div>
                <div class="ticket-detail">
                    <span class="detail-label">Booking Date</span>
                    <span class="detail-value"><?php echo date('M j, Y, g:ia', strtotime($booking['booking_date'])); ?></span>
                </div>
            </div>
            
            <div class="ticket-section">
                <h3>Ticket QR Code</h3>
                <div style="text-align: center; padding: 20px; background: rgba(255,255,255,0.95); border-radius: 12px;">
                    <img src="<?php echo htmlspecialchars($qr_code_url); ?>" alt="Ticket QR Code" style="max-width: 250px; width: 100%; height: auto; display: block; margin: 0 auto;">
                    <p style="color: #333; font-size: 0.85em; margin-top: 10px; margin-bottom: 0;">Scan this code for quick check-in</p>
                </div>
            </div>
            
            <div class="ticket-section">
                <h3>Event Information</h3>
                <div class="ticket-detail">
                    <span class="detail-label">Date & Time</span>
                    <span class="detail-value"><?php echo date('l, F j, Y \a\t g:i A', $event_date); ?></span>
                </div>
                <div class="ticket-detail">
                    <span class="detail-label">Location</span>
                    <span class="detail-value"><?php echo htmlspecialchars($booking['location']); ?></span>
                </div>
                <?php if($booking['ticket_price']): ?>
                <div class="ticket-detail">
                    <span class="detail-label">Price Paid</span>
                    <span class="detail-value" style="font-size: 0.9em; opacity: 0.8;">$<?php echo number_format($booking['ticket_price'], 2); ?></span>
                </div>
                <?php endif; ?>
            </div>
            
            <?php if(!empty($existing_tickets)): ?>
            <div class="ticket-section">
                <h3>Your Support Requests</h3>
                <?php foreach($existing_tickets as $ticket): ?>
                    <div style="background: rgba(0,0,0,0.2); padding: 15px; border-radius: 8px; margin-bottom: 10px;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                            <strong style="color: #fff;"><?php echo ucfirst(str_replace('_', ' ', $ticket['issue_type'])); ?></strong>
                            <span class="status-indicator" style="background: rgba(251, 191, 36, 0.2); color: #fbbf24;">
                                <?php echo ucfirst($ticket['status']); ?>
                            </span>
                        </div>
                        <p style="color: var(--text-muted); font-size: 0.9em; margin: 0;">
                            <?php echo htmlspecialchars($ticket['description']); ?>
                        </p>
                        <div style="color: var(--text-muted); font-size: 0.8em; margin-top: 8px;">
                            Reported <?php echo date('M j, Y', strtotime($ticket['created_at'])); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            
            <div style="margin-top: 30px; text-align: center;">
                <a href="/pages/dashboard.php" style="color: var(--text-muted); text-decoration: none;">← Back to Dashboard</a>
            </div>
        </div>
    </div>
    
    <!-- Issue Report Modal -->
    <div class="modal" id="issueModal">
        <div class="modal-content">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2 style="color: #fff; margin: 0;">Report an Issue</h2>
                <button onclick="closeIssueModal()" style="background: none; border: none; color: var(--text-muted); font-size: 1.5em; cursor: pointer;">&times;</button>
            </div>
            
            <form method="POST" action="">
                <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                <input type="hidden" name="ticket_code" value="<?php echo htmlspecialchars($booking['ticket_code']); ?>">
                
                <div style="margin-bottom: 20px;">
                    <label style="display: block; color: var(--text-muted); margin-bottom: 8px; font-size: 0.9em;">Issue Type</label>
                    <select name="issue_type" required style="width: 100%; padding: 12px; background: rgba(0,0,0,0.3); border: 1px solid var(--border); border-radius: 8px; color: #fff;">
                        <option value="">Select an issue...</option>
                        <option value="no_email">Didn't receive confirmation email</option>
                        <option value="wrong_ticket">Wrong ticket type</option>
                        <option value="payment_issue">Payment issue</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                
                <div style="margin-bottom: 20px;">
                    <label style="display: block; color: var(--text-muted); margin-bottom: 8px; font-size: 0.9em;">Description</label>
                    <textarea name="description" rows="4" required placeholder="Please describe your issue..." style="width: 100%; padding: 12px; background: rgba(0,0,0,0.3); border: 1px solid var(--border); border-radius: 8px; color: #fff; font-family: inherit; resize: vertical;"></textarea>
                </div>
                
                <button type="submit" name="submit_issue" style="width: 100%; padding: 14px; background: linear-gradient(135deg, #9C4DFF, #00C4B4); color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; font-size: 1em;">
                    Submit Issue Report
                </button>
            </form>
        </div>
    </div>
    
    <script>
        function openIssueModal() {
            document.getElementById('issueModal').classList.add('active');
        }
        function closeIssueModal() {
            document.getElementById('issueModal').classList.remove('active');
        }
        
        // Close modal on outside click
        document.getElementById('issueModal').addEventListener('click', function(e) {
            if (e.target === this) closeIssueModal();
        });
    </script>
</body>
</html>
