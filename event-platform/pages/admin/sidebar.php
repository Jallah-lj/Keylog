<?php
// Get current page name for active state
$current_page = basename($_SERVER['PHP_SELF']);
require_once __DIR__ . '/../../includes/icons.php';
?>
<aside class="sidebar">
    <div class="sidebar-logo">EventAdmin</div>
    <nav class="nav-links">
        <a href="/pages/admin/dashboard.php" class="nav-item <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">
            <span class="nav-icon"><?php echo Icons::get('dashboard', 'width:18px; height:18px;'); ?></span> Dashboard
        </a>
        <a href="/index.php" class="nav-item">
            <span class="nav-icon"><?php echo Icons::get('globe', 'width:18px; height:18px;'); ?></span> View Site
        </a>
        <a href="/pages/admin/events.php" class="nav-item <?php echo $current_page == 'events.php' ? 'active' : ''; ?>">
            <span class="nav-icon"><?php echo Icons::get('calendar', 'width:18px; height:18px;'); ?></span> Manage Events
            <?php 
            if (isset($pdo)) {
                $pending_count = $pdo->query("SELECT COUNT(*) FROM events WHERE status='pending'")->fetchColumn();
                if ($pending_count > 0) {
                    echo "<span style='margin-left:auto; background:var(--warning); color:#000; padding:2px 7px; border-radius:10px; font-size:0.7rem; font-weight:700;'>$pending_count</span>";
                }
            }
            ?>
        </a>
        <a href="/pages/admin/users.php" class="nav-item <?php echo $current_page == 'users.php' ? 'active' : ''; ?>">
            <span class="nav-icon"><?php echo Icons::get('users', 'width:18px; height:18px;'); ?></span> Manage Users
        </a>
        <a href="/pages/admin/categories.php" class="nav-item <?php echo $current_page == 'categories.php' ? 'active' : ''; ?>">
            <span class="nav-icon"><?php echo Icons::get('tag', 'width:18px; height:18px;'); ?></span> Categories
        </a>
        <a href="/pages/admin/transactions.php" class="nav-item <?php echo $current_page == 'transactions.php' ? 'active' : ''; ?>">
            <span class="nav-icon"><?php echo Icons::get('currency-dollar', 'width:18px; height:18px;'); ?></span> Transactions
        </a>
        <a href="/pages/admin/cms.php" class="nav-item <?php echo $current_page == 'cms.php' ? 'active' : ''; ?>">
            <span class="nav-icon"><?php echo Icons::get('document-text', 'width:18px; height:18px;'); ?></span> Content (CMS)
        </a>
        <a href="/pages/admin/settings.php" class="nav-item <?php echo $current_page == 'settings.php' ? 'active' : ''; ?>">
            <span class="nav-icon"><?php echo Icons::get('settings', 'width:18px; height:18px;'); ?></span> Site Settings
        </a>
        <div style="margin-top:auto; padding-top:20px; border-top:1px solid var(--border); display: flex; flex-direction: column; gap: 5px;">
            <!-- Profile Block -->
            <div style="display: flex; align-items: center; gap: 12px; padding: 12px; background: rgba(255,255,255,0.03); border-radius: 12px; margin-bottom: 10px; border: 1px solid rgba(255,255,255,0.05);">
                <?php 
                $avatar = $_SESSION['user_avatar'] ?? null;
                $name = $_SESSION['user_name'] ?? 'Admin';
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
                    <div style="color: var(--text-muted); font-size: 0.65rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?php echo htmlspecialchars($_SESSION['user_email'] ?? 'admin@platform.com'); ?></div>
                </div>
            </div>
            <a href="/pages/auth/logout.php" class="nav-item" style="color:var(--danger); padding: 10px 12px;">
                <span class="nav-icon"><?php echo Icons::get('logout', 'width:18px; height:18px;'); ?></span> Logout
            </a>
        </div>
    </nav>
    <button class="nav-item" onclick="toggleSidebar()" style="margin-top: 10px; background: transparent; border: none; cursor: pointer; color: var(--text-muted); display: none; width: 100%;">
        <span class="nav-icon"><?php echo Icons::get('x', 'width:18px; height:18px;'); ?></span> Close Menu
    </button>
</aside>
<div class="sidebar-overlay" onclick="toggleSidebar()"></div>

<script>
function toggleSidebar() {
    document.querySelector('.sidebar').classList.toggle('active');
    document.querySelector('.sidebar-overlay').classList.toggle('active');
}
</script>
<style>
@media (max-width: 768px) {
    .sidebar button[onclick="toggleSidebar()"] {
        display: flex;
    }
}
</style>
