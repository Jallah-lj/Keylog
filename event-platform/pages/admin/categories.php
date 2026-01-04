<?php
session_start();
require_once '../../config/database.php';
require_once '../../includes/icons.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') != 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

$file = '../../uploads/categories.json';
$msg = '';

// Load or Seed Categories
if (file_exists($file)) {
    $categories = json_decode(file_get_contents($file), true);
} else {
    $categories = [
        ['name' => 'Music', 'icon' => 'music'],
        ['name' => 'Tech', 'icon' => 'tech'],
        ['name' => 'Sports', 'icon' => 'sports'],
        ['name' => 'Art', 'icon' => 'art'],
        ['name' => 'Workshop', 'icon' => 'workshop'],
        ['name' => 'General', 'icon' => 'star']
    ];
    file_put_contents($file, json_encode($categories));
}

// Handle Add
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'add') {
    $categories[] = [
        'name' => $_POST['name'],
        'icon' => $_POST['icon'] ?? 'star'
    ];
    file_put_contents($file, json_encode($categories));
    $msg = 'Category added!';
}

// Handle Delete
if (isset($_GET['delete'])) {
    $idx = (int)$_GET['delete'];
    if (isset($categories[$idx])) {
        array_splice($categories, $idx, 1);
        file_put_contents($file, json_encode($categories));
        header("Location: categories.php?msg=deleted"); exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Categories - Admin</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body style="background-color: var(--dark-bg);">

<div class="admin-layout">
    <?php include 'sidebar.php'; ?>

    <main class="main-content">
        <?php include 'admin_header.php'; ?>


        <div class="dashboard-panel" style="margin-bottom:30px;">
            <div style="display:flex; justify-content:space-between; align-items:center;">
                <div>
                    <h3 class="panel-title" style="margin:0;">Site Categories</h3>
                    <p style="color:var(--text-muted); font-size:0.9rem; margin-top:5px;">Manage event categories available to organizers.</p>
                </div>
                <button onclick="document.getElementById('addModal').style.display='flex'" class="hero-btn">+ Add Category</button>
            </div>
            
            <?php if(isset($_GET['msg']) || $msg): ?>
                <div style="color:var(--success); margin-top:15px;">
                    <?php echo $_GET['msg'] ?? $msg; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="dashboard-panel" style="padding:0; overflow:hidden;">
            <table class="modern-table">
                <thead>
                    <tr>
                        <th>Icon</th>
                        <th>Name</th>
                        <th>Usage Estimate</th>
                        <th style="text-align:right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($categories as $i => $cat): 
                        // Count usage in actual DB
                        $stmt = $pdo->prepare("SELECT COUNT(*) FROM events WHERE category = ?");
                        $stmt->execute([$cat['name']]);
                        $count = $stmt->fetchColumn();
                    ?>
                    <tr class="table-row-hover">
                        <td style="font-size:1.5rem;">
                            <div style="width: 40px; height: 40px; background: rgba(255,255,255,0.05); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #fff;">
                                <?php echo Icons::get($cat['icon'], 'width:20px; height:20px;'); ?>
                            </div>
                        </td>
                        <td style="font-weight:600; color:#fff;"><?php echo htmlspecialchars($cat['name']); ?></td>
                        <td style="color:var(--text-muted);"><?php echo $count; ?> events</td>
                        <td style="text-align:right;">
                            <a href="?delete=<?php echo $i; ?>" onclick="return confirm('Delete this category?')" style="color:var(--danger); text-decoration:none;">Delete</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Add Modal -->
        <div id="addModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.8); z-index:100; align-items:center; justify-content:center; backdrop-filter:blur(5px);">
            <div style="background:var(--card-bg); width:100%; max-width:400px; padding:30px; border-radius:16px; border:1px solid var(--border);">
                <h3 style="color:#fff; margin-top:0;">New Category</h3>
                <form method="post">
                    <input type="hidden" name="action" value="add">
                    <div style="margin-bottom:15px;">
                        <label style="display:block; color:var(--text-muted); margin-bottom:5px;">Name</label>
                        <input type="text" name="name" required class="glass-input" style="width:100%;">
                    </div>
                    <div style="margin-bottom:25px;">
                        <label style="display:block; color:var(--text-muted); margin-bottom:5px;">Icon Name (from Icons library)</label>
                        <input type="text" name="icon" placeholder="e.g. music, tech, sports..." class="glass-input" style="width:100%;">
                        <small style="color:var(--text-muted); font-size:0.75rem; display:block; margin-top:5px;">Available: home, music, tech, sports, art, workshop, star, fire, etc.</small>
                    </div>
                    <div style="display:flex; justify-content:flex-end; gap:10px;">
                        <button type="button" onclick="document.getElementById('addModal').style.display='none'" style="background:transparent; border:1px solid var(--border); color:#fff; padding:10px 20px; border-radius:8px;">Cancel</button>
                        <button type="submit" class="hero-btn" style="padding:10px 25px;">Add</button>
                    </div>
                </form>
            </div>
        </div>

    </main>
</div>
</body>
</html>
