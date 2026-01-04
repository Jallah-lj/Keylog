<?php
require_once 'config/database.php';

if ($pdo) {
    echo "SUCCESS: Connected to the database.\n";
} else {
    echo "FAIL: Could not connect to the database.\n";
}
?>