<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "Starting generic check...\n";

// 1. Check File Includes
$files = ['sidebar.php', 'admin_header.php', '../../config/database.php', '../../includes/icons.php'];
foreach ($files as $f) {
    if (file_exists(__DIR__ . '/' . $f)) {
        echo "[OK] File found: $f\n";
    } else {
        echo "[ERROR] File MISSING: $f\n";
    }
}

// 2. Check Database Connection & Tables
try {
    require_once __DIR__ . '/../../config/database.php';
    echo "[OK] Database connected.\n";

    $tables = ['notifications', 'ticket_types', 'bookings', 'events', 'users'];
    foreach ($tables as $t) {
        try {
            $pdo->query("SELECT 1 FROM $t LIMIT 1");
            echo "[OK] Table exists: $t\n";
        } catch (PDOException $e) {
            echo "[ERROR] Table MISSING or Error: $t - " . $e->getMessage() . "\n";
        }
    }

} catch (Exception $e) {
    echo "[CRITICAL] Database connection failed: " . $e->getMessage() . "\n";
}
