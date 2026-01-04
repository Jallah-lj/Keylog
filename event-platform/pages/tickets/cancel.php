<?php
session_start();
require_once '../../config/database.php';

if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../dashboard.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$ticket_code = $_POST['ticket_code'] ?? '';

if (empty($ticket_code)) {
    header("Location: ../profile.php?tab=history&error=Invalid ticket");
    exit();
}

// Fetch ticket details
$stmt = $pdo->prepare("
    SELECT b.id, e.event_date 
    FROM bookings b 
    JOIN events e ON b.event_id = e.id 
    WHERE b.user_id = ? AND b.ticket_code = ?
");
$stmt->execute([$user_id, $ticket_code]);
$ticket = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$ticket) {
    header("Location: ../profile.php?tab=history&error=Ticket not found");
    exit();
}

// check 24h policy
$event_time = strtotime($ticket['event_date']);
$now = time();
$diff = $event_time - $now;

if ($diff < 86400) { // 24 hours in seconds
    header("Location: ../profile.php?tab=history&error=Tickets cannot be cancelled within 24 hours of the event.");
    exit();
}

// Delete ticket
$del_stmt = $pdo->prepare("DELETE FROM bookings WHERE id = ?");
if ($del_stmt->execute([$ticket['id']])) {
    header("Location: ../profile.php?tab=history&msg=Ticket cancelled successfully.");
} else {
    header("Location: ../profile.php?tab=history&error=Error cancelling ticket.");
}
exit();
