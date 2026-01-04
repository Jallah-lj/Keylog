<?php
// Shared Top Bar and Notification Logic for Admin Panel
require_once __DIR__ . '/../../includes/icons.php';

if (!isset($unread_count)) {
    $notif_stmt = $pdo->query("SELECT * FROM notifications WHERE is_read = 0 ORDER BY created_at DESC LIMIT 5");
    $notifications_list = $notif_stmt->fetchAll(PDO::FETCH_ASSOC);
    $unread_count = count($notifications_list);
}
?>
<div class="top-bar">
    <div style="display:flex; align-items:center; gap:15px;">
        <button onclick="toggleSidebar()" style="background:transparent; border:none; color:#fff; cursor:pointer; display:none;" class="mobile-menu-btn">
            <?php echo Icons::get('menu', 'width:24px; height:24px;'); ?>
        </button>
        <div class="breadcrumbs">
        <?php 
        $current_file = basename($_SERVER['PHP_SELF']);
        $breadcrumb_map = [
            'dashboard.php' => 'Admin / Dashboard',
            'events.php' => 'Admin / Management / Events',
            'users.php' => 'Admin / Management / Users',
            'transactions.php' => 'Admin / Transactions',
            'cms.php' => 'Admin / Content Management',
            'settings.php' => 'Admin / Site Settings',
            'categories.php' => 'Admin / Event Categories',
            'search_results.php' => 'Admin / Search Results'
        ];
        echo $breadcrumb_map[$current_file] ?? 'Admin / Panel';
        ?>
    </div>
    
    <div class="top-bar-tools">
        <form action="/pages/admin/search_results.php" method="GET" class="search-wrapper">
            <span style="position:absolute; left:12px; top:50%; transform:translateY(-50%); color:var(--text-muted); line-height:0;">
                <?php echo Icons::get('search', 'width:18px; height:18px;'); ?>
            </span>
            <input type="text" name="q" class="search-input" placeholder="Search users, events, logs..." value="<?php echo htmlspecialchars($_GET['q'] ?? ''); ?>">
        </form>

        <div class="action-icon-btn" onclick="toggleNotifications()">
            <?php echo Icons::get('bell', 'width:20px; height:20px;'); ?>
            <?php if ($unread_count > 0): ?>
                <span class="notification-dot"></span>
            <?php endif; ?>
            
            <div id="notif-dropdown" class="dashboard-panel" style="display:none; position:absolute; top:50px; right:0; width:320px; z-index:100; padding:15px; border-radius:12px; box-shadow:0 10px 40px rgba(0,0,0,0.5); text-align:left;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px; border-bottom:1px solid var(--border); padding-bottom:10px;">
                    <h4 style="margin:0; font-size:1rem; color:#fff;">Notifications</h4>
                </div>
                <div style="display:flex; flex-direction:column; gap:12px;">
                    <?php if ($unread_count > 0): ?>
                            <?php foreach($notifications_list as $n): 
                            $notif_icon = 'bell';
                            switch($n['type']) {
                                case 'system': $notif_icon = 'cog'; break;
                                case 'user': $notif_icon = 'users'; break;
                                case 'security': $notif_icon = 'exclamation-triangle'; break;
                            }
                        ?>
                            <div style="display:flex; gap:12px; align-items:flex-start;">
                                <div style="color:var(--primary); margin-top:2px;">
                                    <?php echo Icons::get($notif_icon, 'width:14px; height:14px;'); ?>
                                </div>
                                <div>
                                    <div style="font-size:0.85rem; font-weight:600; color:#fff;"><?php echo htmlspecialchars($n['title']); ?></div>
                                    <div style="font-size:0.75rem; color:var(--text-muted); line-height:1.4;"><?php echo htmlspecialchars($n['message']); ?></div>
                                    <div style="font-size:0.65rem; color:var(--text-muted); opacity:0.5; margin-top:4px;"><?php echo date('M j, g:ia', strtotime($n['created_at'])); ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div style="text-align:center; padding:20px; color:var(--text-muted); font-size:0.85rem;">No new notifications.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <a href="/pages/events/create.php" class="hero-btn" style="padding: 10px 20px; font-size: 0.85rem; text-decoration: none;">
            + Create Event
        </a>
    </div>
</div>

<script>
function toggleNotifications() {
    const dropdown = document.getElementById('notif-dropdown');
    dropdown.style.display = dropdown.style.display === 'none' ? 'block' : 'none';
    if (dropdown.style.display === 'block') {
        const closer = (e) => {
            if (!e.target.closest('.action-icon-btn')) {
                dropdown.style.display = 'none';
                document.removeEventListener('click', closer);
            }
        };
        setTimeout(() => document.addEventListener('click', closer), 1);
    }
}
</script>
<style>
@media (max-width: 768px) {
    .mobile-menu-btn {
        display: block !important;
    }
}
</style>
