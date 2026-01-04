<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "Starting index.php dependency check...\n";

// 1. Check Files
$files = ['config/database.php', 'includes/header.php', 'includes/icons.php', 'includes/footer.php', 'uploads/categories.json'];
foreach ($files as $f) {
    if (file_exists(__DIR__ . '/' . $f)) {
        echo "[OK] File found: $f\n";
    } else {
        echo "[WARN] File MISSING: $f\n";
    }
}

// 2. Check Database & Queries
try {
    require_once __DIR__ . '/config/database.php';
    echo "[OK] Database connected.\n";

    // Check Categories
    $stmt = $pdo->query("SELECT COUNT(*) FROM events WHERE event_date >= NOW() AND status = 'published'");
    echo "[OK] Events query successful. Count: " . $stmt->fetchColumn() . "\n";

    $stmt = $pdo->query("SELECT COUNT(*) FROM ticket_types");
    echo "[OK] Ticket Types query successful. Count: " . $stmt->fetchColumn() . "\n";

} catch (Exception $e) {
    echo "[CRITICAL] Database Error: " . $e->getMessage() . "\n";
}
