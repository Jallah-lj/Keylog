<?php
session_start();
require_once '../../config/database.php';
require_once '../../includes/icons.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') != 'organizer') {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$team_file = "../../uploads/team_{$user_id}.json";
$team_members = [];

// Load Team
if (file_exists($team_file)) {
    $team_members = json_decode(file_get_contents($team_file), true) ?? [];
}

// Handle Add Member
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'add') {
    $new_member = [
        'id' => uniqid(),
        'name' => $_POST['name'] ?? 'Unknown',
        'role' => $_POST['role'] ?? 'Staff',
        'email' => $_POST['email'] ?? '',
        'added_at' => date('Y-m-d H:i:s')
    ];
    $team_members[] = $new_member;
    file_put_contents($team_file, json_encode($team_members));
    header("Location: team.php?msg=added"); exit();
}

// Handle Delete Member
if (isset($_GET['delete'])) {
    $id_to_delete = $_GET['delete'];
    $team_members = array_filter($team_members, function($m) use ($id_to_delete) {
        return $m['id'] !== $id_to_delete;
    });
    file_put_contents($team_file, json_encode(array_values($team_members)));
    header("Location: team.php?msg=deleted"); exit();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Team Management - Organizer Panel</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body style="background-color: var(--dark-bg);">

<div class="admin-layout">
    <?php include 'sidebar.php'; ?>

    <main class="main-content">
        <div class="top-bar">
            <div class="breadcrumbs">Organizer / Team</div>
        </div>

        <div class="dashboard-panel" style="margin-bottom: 30px;">
            <div style="display:flex; justify-content:space-between; align-items:center;">
                <div>
                    <h3 class="panel-title" style="margin:0;">Team & Staff</h3>
                    <p style="color:var(--text-muted); font-size:0.9rem; margin-top:5px;">Manage your event staff and their roles.</p>
                </div>
                <button onclick="document.getElementById('addModal').style.display='flex'" class="hero-btn" style="padding: 10px 20px;">+ Add Member</button>
            </div>
        </div>

        <div class="dashboard-panel" style="padding:0; overflow:hidden;">
            <?php if(count($team_members) > 0): ?>
            <table class="modern-table">
                <thead>
                    <tr style="background: rgba(0,0,0,0.2);">
                        <th>Name</th>
                        <th>Role</th>
                        <th>Contact</th>
                        <th>Added Date</th>
                        <th style="text-align:right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($team_members as $member): ?>
                    <tr class="table-row-hover">
                        <td>
                            <div style="font-weight:600; color:#fff;"><?php echo htmlspecialchars($member['name']); ?></div>
                        </td>
                        <td>
                            <span style="background:rgba(255,255,255,0.1); padding:4px 10px; border-radius:12px; font-size:0.85em; color:var(--accent);">
                                <?php echo htmlspecialchars($member['role']); ?>
                            </span>
                        </td>
                        <td style="color:var(--text-muted);">
                            <?php echo htmlspecialchars($member['email']); ?>
                        </td>
                        <td style="color:var(--text-muted);">
                            <?php echo date('M j, Y', strtotime($member['added_at'])); ?>
                        </td>
                        <td style="text-align:right;">
                            <a href="?delete=<?php echo $member['id']; ?>" onclick="return confirm('Remove this member?')" style="color:var(--danger); text-decoration:none; display:flex; align-items:center; justify-content:flex-end; gap:5px;">
                                <?php echo Icons::get('trash', 'width:16px; height:16px;'); ?> Remove
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
                <div style="padding:50px; text-align:center; color:var(--text-muted);">
                    <div style="margin-bottom:20px; color:var(--text-muted); opacity:0.3; display:flex; justify-content:center;">
                        <?php echo Icons::get('users', 'width:64px; height:64px;'); ?>
                    </div>
                    <h3>No team members yet.</h3>
                    <p>Add your staff to keep track of your event team.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Add Modal -->
        <div id="addModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.8); z-index:100; align-items:center; justify-content:center; backdrop-filter:blur(5px);">
            <div style="background:var(--card-bg); width:100%; max-width:400px; padding:30px; border-radius:16px; border:1px solid var(--border);">
                <h3 style="color:#fff; margin-top:0;">Add Team Member</h3>
                <form method="post">
                    <input type="hidden" name="action" value="add">
                    
                    <div style="margin-bottom:15px;">
                        <label style="display:block; color:var(--text-muted); margin-bottom:5px;">Full Name</label>
                        <input type="text" name="name" required class="glass-input" style="width:100%;">
                    </div>
                    
                    <div style="margin-bottom:15px;">
                        <label style="display:block; color:var(--text-muted); margin-bottom:5px;">Role / Position</label>
                        <select name="role" class="glass-input" style="width:100%;">
                            <option>Event Staff</option>
                            <option>Security</option>
                            <option>Box Office</option>
                            <option>Coordinator</option>
                            <option>Volunteer</option>
                        </select>
                    </div>

                    <div style="margin-bottom:25px;">
                        <label style="display:block; color:var(--text-muted); margin-bottom:5px;">Email (Optional)</label>
                        <input type="email" name="email" class="glass-input" style="width:100%;">
                    </div>

                    <div style="display:flex; justify-content:flex-end; gap:10px;">
                        <button type="button" onclick="document.getElementById('addModal').style.display='none'" style="background:transparent; border:1px solid var(--border); color:#fff; padding:10px 20px; border-radius:8px; cursor:pointer;">Cancel</button>
                        <button type="submit" class="hero-btn" style="padding:10px 25px;">Add Member</button>
                    </div>
                </form>
            </div>
        </div>

    </main>
</div>

</body>
</html>
