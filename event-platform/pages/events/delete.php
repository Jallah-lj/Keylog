<?php
session_start();
require_once '../../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'organizer') {
    die("Access denied");
}

if (isset($_GET['id'])) {
    $event_id = $_GET['id'];
    $organizer_id = $_SESSION['user_id'];

    $stmt = $pdo->prepare("DELETE FROM events WHERE id = ? AND organizer_id = ?");
    if ($stmt->execute([$event_id, $organizer_id])) {
        header("Location: ../organizer/events.php?msg=deleted");
    } else {
        echo "Error deleting event.";
    }
} else {
    header("Location: ../organizer/events.php");
}
?>
