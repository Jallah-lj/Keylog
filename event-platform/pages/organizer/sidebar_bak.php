<?php
// Get current page name for active state
$current_page = basename($_SERVER['PHP_SELF']);
require_once __DIR__ . '/../../includes/icons.php';
?>
<aside class="sidebar">
    <div class="sidebar-logo">Organizer</div>
    <nav class="nav-links">
        <a href="dashboard.php" class="nav-item <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">
            <span class="nav-icon"><?php echo Icons::get('dashboard', 'width:18px; height:18px;'); ?></span> Dashboard
        </a>
        <a href="events.php" class="nav-item <?php echo $current_page == 'events.php' ? 'active' : ''; ?>">
            <span class="nav-icon"><?php echo Icons::get('calendar', 'width:18px; height:18px;'); ?></span> My Events
        </a>
        <a href="attendees.php" class="nav-item <?php echo $current_page == 'attendees.php' ? 'active' : ''; ?>">
             <span class="nav-icon"><?php echo Icons::get('users', 'width:18px; height:18px;'); ?></span> Attendees
        </a>
        <a href="settings.php" class="nav-item <?php echo $current_page == 'settings.php' ? 'active' : ''; ?>">
            <span class="nav-icon"><?php echo Icons::get('settings', 'width:18px; height:18px;'); ?></span> Settings
        </a>
        
        <a href="../../index.php" class="nav-item">
            <span class="nav-icon"><?php echo Icons::get('globe', 'width:18px; height:18px;'); ?></span> View Site
        </a>

        <div style="margin-top:auto; padding-top:20px; border-top:1px solid var(--border)">
            <a href="../auth/logout.php" class="nav-item" style="color:var(--danger)">
                <span class="nav-icon"><?php echo Icons::get('logout', 'width:18px; height:18px;'); ?></span> Logout
            </a>
        </div>
    </nav>
</aside>
