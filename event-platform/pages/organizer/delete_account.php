<?php
session_start();
require_once '../../config/database.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') != 'organizer') {
    die("Access denied");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_delete'])) {
    $user_id = $_SESSION['user_id'];
    
    try {
        $pdo->beginTransaction();

        // 1. Delete all bookings for this organizer's events
        // Find all event IDs first
        $stmt = $pdo->prepare("SELECT id FROM events WHERE organizer_id = ?");
        $stmt->execute([$user_id]);
        $event_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if (!empty($event_ids)) {
            $in_query = implode(',', array_fill(0, count($event_ids), '?'));
            $delete_bookings = $pdo->prepare("DELETE FROM bookings WHERE event_id IN ($in_query)");
            $delete_bookings->execute($event_ids);
        }

        // 2. Delete all events
        $delete_events = $pdo->prepare("DELETE FROM events WHERE organizer_id = ?");
        $delete_events->execute([$user_id]);

        // 3. Delete the user
        $delete_user = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $delete_user->execute([$user_id]);

        $pdo->commit();

        // Destroy session and redirect
        session_destroy();
        header("Location: ../../index.php?msg=account_deleted");
        exit();

    } catch (Exception $e) {
        $pdo->rollBack();
        die("Error deleting account: " . $e->getMessage());
    }
} else {
    header("Location: settings.php");
    exit();
}
