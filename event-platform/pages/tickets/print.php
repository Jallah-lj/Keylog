<?php
session_start();
require_once '../../config/database.php';
require_once '../../includes/qr_code.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$ticket_code = $_GET['code'] ?? '';
$stmt = $pdo->prepare("
    SELECT b.*, e.title, e.event_date, e.location, e.description, e.image, 
           tt.name as ticket_name, tt.price as ticket_price, u.name as attendee_name, u.email
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
    <title>Print Ticket - <?php echo htmlspecialchars($booking['title']); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        @media print {
            body { margin: 0; background: #000; }
            .no-print { display: none; }
            .ticket-wrapper { 
                page-break-after: always;
            }
        }
        
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Inter', sans-serif;
            background: #111;
            padding: 40px 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        
        .ticket-wrapper {
            width: 850px;
            background: transparent;
        }
        
        .ticket-container {
            background: #fff;
            border-radius: 20px;
            overflow: hidden;
            display: flex;
            height: 380px;
            box-shadow: 0 30px 100px rgba(0,0,0,0.5);
            position: relative;
            border-top: 6px solid <?php echo $accent_color; ?>;
        }
        
        /* Golden Section */
        .ticket-left {
            width: 320px;
            background: linear-gradient(135deg, #4d3d00 0%, #7a6100 100%);
            padding: 40px 30px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            position: relative;
            color: #fff;
        }
        
        .event-initials {
            font-size: 150px;
            font-weight: 800;
            color: rgba(255, 215, 0, 0.2);
            position: absolute;
            top: 20px;
            left: 20px;
            line-height: 0.8;
            letter-spacing: -10px;
            pointer-events: none;
        }
        
        .event-branding {
            position: relative;
            z-index: 1;
        }
        
        .branding-title {
            font-size: 32px;
            font-weight: 800;
            line-height: 1.1;
            margin-bottom: 20px;
            text-transform: uppercase;
        }
        
        .branding-date {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 5px;
        }
        
        .branding-location {
            font-size: 14px;
            font-weight: 600;
            text-transform: uppercase;
            opacity: 0.9;
        }
        
        /* White Section */
        .ticket-right {
            flex: 1;
            background: #fff;
            padding: 40px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
        }
        
        .ticket-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }
        
        .event-info-top h1 {
            font-size: 26px;
            font-weight: 800;
            color: #000;
            margin-bottom: 5px;
            text-transform: uppercase;
        }
        
        .event-info-top p {
            font-size: 14px;
            color: #666;
            font-weight: 600;
        }
        
        .qr-code-box {
            width: 100px;
            height: 100px;
        }
        
        .qr-code-box img {
            width: 100%;
            height: 100%;
        }
        
        .ticket-meta {
            margin-top: 25px;
            border-top: 1px solid #eee;
            padding-top: 15px;
        }
        
        .ticket-id-label {
            font-size: 10px;
            color: #bbb;
            font-weight: 700;
            text-transform: uppercase;
            margin-bottom: 5px;
            display: block;
        }
        
        .ticket-id-value {
            font-family: monospace;
            font-size: 12px;
            color: #999;
        }
        
        .details-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-top: 25px;
        }
        
        .detail-item label {
            font-size: 10px;
            color: #bbb;
            font-weight: 700;
            text-transform: uppercase;
            margin-bottom: 4px;
            display: block;
        }
        
        .detail-item span {
            font-size: 15px;
            font-weight: 700;
            color: #000;
        }
        
        .price-display {
            font-size: 18px;
            font-weight: 700;
            color: #666;
            margin-top: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .price-display::before {
            content: "TOTAL AMOUNT:";
            font-size: 10px;
            color: #bbb;
            letter-spacing: 1px;
        }
        
        .ticket-footer {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-top: auto;
            border-top: 1px solid #eee;
            padding-top: 15px;
        }
        
        .purchase-info {
            font-size: 11px;
            color: #999;
        }
        
        .platform-name {
            font-size: 12px;
            font-weight: 700;
            color: #ccc;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        /* Buttons */
        .print-button {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: #B8860B;
            color: #fff;
            border: none;
            padding: 15px 35px;
            border-radius: 8px;
            font-weight: 700;
            cursor: pointer;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            z-index: 100;
        }
        
        .back-button {
            position: fixed;
            bottom: 30px;
            left: 30px;
            background: #222;
            color: #fff;
            padding: 15px 35px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 700;
            z-index: 100;
        }
    </style>
</head>
<body>
    <a href="view.php?code=<?php echo htmlspecialchars($ticket_code); ?>" class="back-button no-print">DASHBOARD</a>
    <button onclick="window.print()" class="print-button no-print">PRINT TICKET</button>
    
    <div class="ticket-wrapper">
        <div class="ticket-container">
            <!-- Left Side - Event Flyer -->
            <div class="ticket-left" style="padding: 0; position: relative;">
                <?php if($booking['image']): ?>
                    <img src="../../uploads/<?php echo htmlspecialchars($booking['image']); ?>" style="width: 100%; height: 100%; object-fit: cover;" alt="Event Flyer">
                    <div style="position: absolute; bottom: 0; left: 0; right: 0; background: linear-gradient(to top, rgba(0,0,0,0.95), transparent); padding: 30px; color: #fff;">
                        <div style="font-size: 24px; font-weight: 800; text-transform: uppercase; margin-bottom: 10px; color: #FFD700;"><?php echo htmlspecialchars($booking['title']); ?></div>
                        <div style="font-size: 14px; font-weight: 600; text-transform: uppercase; letter-spacing: 1px;"><?php echo date('M jS', $event_date); ?> • <?php echo htmlspecialchars($booking['location']); ?></div>
                    </div>
                <?php else: ?>
                    <div class="event-initials">
                        <?php 
                        $words = explode(' ', $booking['title']);
                        $initials = '';
                        foreach(array_slice($words, 0, 2) as $word) {
                            $initials .= strtoupper(substr($word, 0, 1));
                        }
                        echo htmlspecialchars($initials);
                        ?>
                    </div>
                    <div class="event-branding">
                        <div class="branding-title"><?php echo htmlspecialchars($booking['title']); ?></div>
                        <div class="branding-date"><?php echo date('M jS', $event_date); ?></div>
                        <div class="branding-location"><?php echo htmlspecialchars($booking['location']); ?></div>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Right White Side -->
            <div class="ticket-right">
                <div class="ticket-top">
                    <div class="ticket-header">
                        <div class="event-info-top">
                            <h1><?php echo htmlspecialchars($booking['title']); ?></h1>
                            <p><?php echo htmlspecialchars($booking['location']); ?> - <?php echo date('l d F Y', $event_date); ?></p>
                        </div>
                        <div class="qr-code-box">
                            <img src="<?php echo htmlspecialchars($qr_code_url); ?>" alt="Ticket QR Code">
                        </div>
                    </div>
                    
                    <div class="ticket-meta">
                        <span class="ticket-id-label">Ticket ID: <?php echo htmlspecialchars($booking['ticket_code']); ?></span>
                    </div>
                    
                    <div class="details-grid">
                        <div class="detail-item">
                            <label>Attendee</label>
                            <span><?php echo htmlspecialchars($booking['attendee_name']); ?></span>
                        </div>
                        <div class="detail-item">
                            <label>Time</label>
                            <span><?php echo date('g:i A', $event_date); ?></span>
                        </div>
                        <div class="detail-item">
                            <label>Class</label>
                            <span style="color: <?php echo $accent_color; ?>;"><?php echo htmlspecialchars($booking['ticket_name'] ?? 'REGULAR'); ?></span>
                        </div>
                        <div class="detail-item">
                            <label>Status</label>
                            <span style="color: #10b981;">CONFIRMED</span>
                        </div>
                    </div>
                </div>

                <div class="ticket-bottom">
                    <div class="price-display">
                        $<?php echo number_format($booking['ticket_price'], 2); ?>
                    </div>
                    
                    <div class="ticket-footer">
                        <div class="purchase-info">
                            Purchased: <?php echo date('F d, Y, g:i a', strtotime($booking['booking_date'])); ?>
                        </div>
                        <div class="platform-name">
                            <?php echo strtoupper(htmlspecialchars($site_name)); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
