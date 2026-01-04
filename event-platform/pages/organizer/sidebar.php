<?php
// Get current page name for active state
$current_page = basename($_SERVER['PHP_SELF']);
$in_organizer_dir = (strpos($_SERVER['PHP_SELF'], '/organizer/') !== false);
$org_path = $in_organizer_dir ? '' : '../organizer/';

require_once __DIR__ . '/../../includes/icons.php';
?>
<aside class="sidebar">
    <div class="sidebar-logo">Organizer</div>
    <nav class="nav-links">
        <?php if(($_SESSION['user_role'] ?? '') == 'admin'): ?>
        <a href="/pages/admin/dashboard.php" class="nav-item" style="color:var(--secondary);">
            <span class="nav-icon"><?php echo Icons::get('settings', 'width:18px; height:18px;'); ?></span> Admin Panel
        </a>
        <?php endif; ?>
        <a href="/pages/organizer/dashboard.php" class="nav-item <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">
            <span class="nav-icon"><?php echo Icons::get('dashboard', 'width:18px; height:18px;'); ?></span> Dashboard
        </a>
        <a href="/pages/organizer/profile.php" class="nav-item <?php echo $current_page == 'profile.php' ? 'active' : ''; ?>">
            <span class="nav-icon"><?php echo Icons::get('users', 'width:18px; height:18px;'); ?></span> Profile
        </a>
        <a href="/pages/organizer/events.php" class="nav-item <?php echo $current_page == 'events.php' ? 'active' : ''; ?>">
            <span class="nav-icon"><?php echo Icons::get('calendar', 'width:18px; height:18px;'); ?></span> My Events
        </a>
        <a href="/pages/organizer/attendees.php" class="nav-item <?php echo $current_page == 'attendees.php' ? 'active' : ''; ?>">
             <span class="nav-icon"><?php echo Icons::get('users', 'width:18px; height:18px;'); ?></span> Attendees
        </a>
        <a href="/pages/organizer/reports.php" class="nav-item <?php echo $current_page == 'reports.php' ? 'active' : ''; ?>">
             <span class="nav-icon"><?php echo Icons::get('trend-up', 'width:18px; height:18px;'); ?></span> Reports
        </a>
        <a href="/pages/organizer/settings.php" class="nav-item <?php echo $current_page == 'settings.php' ? 'active' : ''; ?>">
            <span class="nav-icon"><?php echo Icons::get('settings', 'width:18px; height:18px;'); ?></span> Settings
        </a>
        
        <a href="/index.php" class="nav-item">
            <span class="nav-icon"><?php echo Icons::get('globe', 'width:18px; height:18px;'); ?></span> View Site
        </a>

        <div style="margin-top:auto; padding-top:20px; border-top:1px solid var(--border); display: flex; flex-direction: column; gap: 5px;">
            <!-- Profile Block -->
            <div style="display: flex; align-items: center; gap: 12px; padding: 12px; background: rgba(255,255,255,0.03); border-radius: 12px; margin-bottom: 10px; border: 1px solid rgba(255,255,255,0.05);">
                <?php 
                $avatar = $_SESSION['user_avatar'] ?? null;
                $name = $_SESSION['user_name'] ?? 'Organizer';
                $initials = strtoupper(substr($name, 0, 1));
                
                if($avatar): ?>
                    <img src="/uploads/<?php echo htmlspecialchars($avatar); ?>" style="width: 38px; height: 38px; border-radius: 10px; object-fit: cover; border: 1px solid var(--border);">
                <?php else: ?>
                    <div style="width: 38px; height: 38px; background: linear-gradient(135deg, var(--primary), var(--secondary)); border-radius: 10px; display: flex; align-items: center; justify-content: center; color: #fff; font-weight: 700; font-size: 0.9rem;">
                        <?php echo $initials; ?>
                    </div>
                <?php endif; ?>
                <div style="overflow: hidden; flex: 1;">
                    <div style="color: #fff; font-size: 0.85rem; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?php echo htmlspecialchars($name); ?></div>
                    <div style="color: var(--text-muted); font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.5px;"><?php echo htmlspecialchars($_SESSION['user_role'] ?? 'Organizer'); ?></div>
                </div>
            </div>
            <a href="/pages/auth/logout.php" class="nav-item" style="color:var(--danger); padding: 10px 12px;">
                <span class="nav-icon"><?php echo Icons::get('logout', 'width:18px; height:18px;'); ?></span> Logout
            </a>
        </div>
    </nav>
</aside>
