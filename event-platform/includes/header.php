<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/icons.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Platform</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
</head>
<body>
    <header>
        <div class="header-logo">
            <a href="/" style="text-decoration: none;">
                <h1>EventPlatform</h1>
            </a>
        </div>
        
        <nav>
            <a href="/">Home</a>
            <a href="/index.php#browse">Browse</a>
            
            <?php if(isset($_SESSION['user_id'])): ?>
                <?php if($_SESSION['user_role'] == 'admin'): ?>
                    <a href="/pages/admin/dashboard.php">Admin Panel</a>
                <?php elseif($_SESSION['user_role'] == 'organizer'): ?>
                    <a href="/pages/organizer/dashboard.php">Dashboard</a>
                <?php else: ?>
                    <a href="/pages/profile.php">My Tickets</a>
                <?php endif; ?>
                <a href="/pages/auth/logout.php" style="color: var(--danger);">Logout</a>
            <?php else: ?>
                <a href="/pages/auth/login.php">Login</a>
                <a href="/pages/auth/register.php" class="nav-btn">Sign Up</a>
            <?php endif; ?>
        </nav>
    </header>
