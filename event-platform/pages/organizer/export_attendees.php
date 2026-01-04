<?php
session_start();
require_once '../../config/database.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') != 'organizer') {
    die("Access denied");
}

$user_id = $_SESSION['user_id'];
$event_id = $_POST['event_id'] ?? null;

if (!$event_id) {
    die("Event ID required");
}

// Build Query
$query = "
    SELECT u.name, u.email, b.ticket_code, b.status, t.name as ticket_type
    FROM bookings b
    JOIN users u ON b.user_id = u.id
    LEFT JOIN ticket_types t ON b.ticket_type_id = t.id
    WHERE b.event_id = ?
";
// Security check: ensure event belongs to organizer
$check = $pdo->prepare("SELECT id FROM events WHERE id = ? AND organizer_id = ?");
$check->execute([$event_id, $user_id]);
if (!$check->fetch()) {
    die("Permission denied for this event");
}

$stmt = $pdo->prepare($query);
$stmt->execute([$event_id]);
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="attendees_list.csv"');
$output = fopen('php://output', 'w');
fputcsv($output, ['Name', 'Email', 'Ticket Code', 'Status', 'Ticket Type']);
foreach ($data as $row) {
    fputcsv($output, $row);
}
fclose($output);
exit();
