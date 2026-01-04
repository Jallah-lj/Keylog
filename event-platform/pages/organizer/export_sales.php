<?php
session_start();
require_once '../../config/database.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') != 'organizer') {
    die("Access denied");
}

$user_id = $_SESSION['user_id'];
$event_id = $_POST['event_id'] ?? 'all';
$format = $_POST['format'] ?? 'csv';

// Build Query
$query = "
    SELECT e.title as event, b.booking_date, b.ticket_code, b.status, t.price, u.name as attendee, u.email
    FROM bookings b
    JOIN events e ON b.event_id = e.id
    JOIN users u ON b.user_id = u.id
    LEFT JOIN ticket_types t ON b.ticket_type_id = t.id
    WHERE e.organizer_id = ?
";
$params = [$user_id];

if ($event_id != 'all') {
    $query .= " AND e.id = ?";
    $params[] = $event_id;
}

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($format == 'json') {
    header('Content-Type: application/json');
    header('Content-Disposition: attachment; filename="sales_report.json"');
    echo json_encode($data, JSON_PRETTY_PRINT);
} else {
    // CSV
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="sales_report.csv"');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Event', 'Date', 'Ticket Code', 'Status', 'Price', 'Attendee', 'Email']);
    foreach ($data as $row) {
        fputcsv($output, $row);
    }
    fclose($output);
}
exit();
