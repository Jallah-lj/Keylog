<?php
require_once __DIR__ . '/config/database.php';

try {
    if (isset($pdo)) {
        echo "Database connection successful!\n";
    } else {
        echo "PDO object not found.\n";
    }
} catch (Exception $e) {
    echo "Caught exception: " . $e->getMessage() . "\n";
}
?>
