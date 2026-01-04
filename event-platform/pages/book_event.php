<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php?msg=auth_required&return=" . urlencode($_SERVER['REQUEST_URI']));
    exit();
}

if ($_SESSION['user_role'] != 'attendee') {
    die("Only attendees can book tickets.");
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['event_id'])) {
    $user_id = $_SESSION['user_id'];
    $event_id = $_POST['event_id'];
    $ticket_type_id = $_POST['ticket_type_id'] ?? null;

    // Check if check-in is required (i.e. if event has ticket types)
    $has_types_stmt = $pdo->prepare("SELECT COUNT(*) FROM ticket_types WHERE event_id = ?");
    $has_types_stmt->execute([$event_id]);
    $has_ticket_types = $has_types_stmt->fetchColumn() > 0;

    if ($has_ticket_types && empty($ticket_type_id)) {
         die("Error: Please select a valid ticket type. <a href='event_details.php?id=$event_id'>Go back</a>");
    }

    $quantity = (int)($_POST['quantity'] ?? 1);
    if ($quantity < 1) $quantity = 1;

    // Check Capacity for this specific Ticket Type
    if ($ticket_type_id) {
        // Get Total Qty for this type
        $t_stmt = $pdo->prepare("SELECT quantity, name FROM ticket_types WHERE id = ?");
        $t_stmt->execute([$ticket_type_id]);
        $ticket_type = $t_stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$ticket_type) die("Invalid Ticket Type");

        // Count Bookings for this type
        $c_stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE ticket_type_id = ?");
        $c_stmt->execute([$ticket_type_id]);
        $sold = $c_stmt->fetchColumn();

        if (($sold + $quantity) > $ticket_type['quantity']) {
            die("Sorry, there are not enough <strong>" . htmlspecialchars($ticket_type['name']) . "</strong> tickets remaining. You requested $quantity. <a href='event_details.php?id=$event_id'>Go back</a>");
        }
    } else {
        // Fallback for legacy events
        $event_stmt = $pdo->prepare("SELECT max_capacity FROM events WHERE id = ?");
        $event_stmt->execute([$event_id]);
        $capacity = $event_stmt->fetchColumn();
        
        $count = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE event_id = ?");
        $count->execute([$event_id]);
        $booked_count = $count->fetchColumn();
        
        if (($booked_count + $quantity) > $capacity) {
             die("Sorry, this event does not have enough capacity.");
        }
    }

    // Book Multiple Tickets
    $insert = $pdo->prepare("INSERT INTO bookings (user_id, event_id, ticket_code, ticket_type_id) VALUES (?, ?, ?, ?)");
    
    for ($i = 0; $i < $quantity; $i++) {
        $ticket_code = strtoupper(uniqid('TKT-'));
        $insert->execute([$user_id, $event_id, $ticket_code, $ticket_type_id]);
    }

    header("Location: dashboard.php?msg=booked&qty=$quantity");

} else {
    header("Location: ../index.php");
}
?>
