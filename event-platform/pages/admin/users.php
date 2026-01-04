<?php
session_start();
require_once '../../config/database.php';

// Auth Check
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

require_once '../../includes/icons.php';

// Handle User Deletion
if (isset($_GET['delete_user'])) {
    $user_id = $_GET['delete_user'];
    
    // Prevent self-deletion if possible (optional but recommended)
    if ($user_id == $_SESSION['user_id']) {
        header("Location: users.php?error=self_delete");
        exit();
    }

    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    header("Location: users.php?msg=deleted");
    exit();
}

// Handle Search and Filter
$search = $_GET['search'] ?? '';
$role_filter = $_GET['role'] ?? '';

$query = "SELECT * FROM users WHERE 1=1";
$params = [];

if ($search) {
    $query .= " AND (name LIKE ? OR email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($role_filter) {
    $query .= " AND role = ?";
    $params[] = $role_filter;
}

$query .= " ORDER BY created_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Count stats
$total_users = count($users);
$organizer_count = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'organizer'")->fetchColumn();
$attendee_count = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'attendee'")->fetchColumn();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Users - Admin Panel</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
        .user-stats-card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 20px;
            text-align: center;
        }
        .user-stats-card .value {
            display: block;
            font-size: 2rem;
            font-weight: 700;
            color: #fff;
        }
        .user-stats-card .label {
            color: var(--text-muted);
            font-size: 0.9rem;
        }

        .filter-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            gap: 20px;
            flex-wrap: wrap;
        }

        .search-input {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--border);
            color: #fff;
            padding: 10px 15px;
            border-radius: 8px;
            width: 300px;
        }

        .role-badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        .role-organizer { background: rgba(168, 85, 247, 0.2); color: #a855f7; }
        .role-attendee { background: rgba(14, 165, 233, 0.2); color: #0ea5e9; }
        .role-admin { background: rgba(248, 113, 113, 0.2); color: #f87171; }

        .btn-delete {
            background: rgba(248, 113, 113, 0.1);
            color: var(--danger);
            border: 1px solid rgba(248, 113, 113, 0.2);
            padding: 6px 12px;
            border-radius: 6px;
            text-decoration: none;
            transition: all 0.3s;
        }
        .btn-delete:hover {
            background: var(--danger);
            color: #fff;
        }
    </style>
</head>
<body style="background-color: var(--dark-bg);">

<div class="admin-layout">
    <?php include 'sidebar.php'; ?>

    <main class="main-content">
        <?php include 'admin_header.php'; ?>


        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 30px;">
            <div class="user-stats-card">
                <span class="value"><?php echo $total_users; ?></span>
                <span class="label">Total Users</span>
            </div>
            <div class="user-stats-card">
                <span class="value"><?php echo $organizer_count; ?></span>
                <span class="label">Organizers</span>
            </div>
            <div class="user-stats-card">
                <span class="value"><?php echo $attendee_count; ?></span>
                <span class="label">Attendees</span>
            </div>
            <div class="user-stats-card" style="background: linear-gradient(135deg, rgba(99, 102, 241, 0.1), rgba(217, 70, 239, 0.1));">
                <span class="value">New</span>
                <span class="label">Last 24h</span>
            </div>
        </div>

        <div class="dashboard-panel">
            <div class="filter-header">
                <h3 class="panel-title" style="margin:0;">User Management</h3>
                <form action="users.php" method="GET" style="display:flex; gap:10px;">
                    <input type="text" name="search" placeholder="Search by name or email..." class="search-input" value="<?php echo htmlspecialchars($search); ?>">
                    <select name="role" class="search-input" style="width:150px;" onchange="this.form.submit()">
                        <option value="">All Roles</option>
                        <option value="organizer" <?php if($role_filter == 'organizer') echo 'selected'; ?>>Organizers</option>
                        <option value="attendee" <?php if($role_filter == 'attendee') echo 'selected'; ?>>Attendees</option>
                        <option value="admin" <?php if($role_filter == 'admin') echo 'selected'; ?>>Admins</option>
                    </select>
                    <button type="submit" class="action-btn" style="background:var(--primary); padding:8px 15px; border-radius:8px;">Filter</button>
                    <?php if($search || $role_filter): ?>
                        <a href="users.php" class="action-btn" style="background:rgba(255,255,255,0.1); padding:8px 15px; border-radius:8px; text-decoration:none; color:#fff;">Clear</a>
                    <?php endif; ?>
                </form>
            </div>

            <?php if(isset($_GET['msg']) && $_GET['msg'] == 'deleted'): ?>
                <div style="background:rgba(52, 211, 153, 0.1); color:var(--success); padding:12px; border-radius:8px; margin-bottom:20px; border:1px solid rgba(52, 211, 153, 0.2);">
                    User successfully deleted.
                </div>
            <?php endif; ?>
            
            <?php if(isset($_GET['error']) && $_GET['error'] == 'self_delete'): ?>
                <div style="background:rgba(248, 113, 113, 0.1); color:var(--danger); padding:12px; border-radius:8px; margin-bottom:20px; border:1px solid rgba(248, 113, 113, 0.2);">
                    You cannot delete your own admin account.
                </div>
            <?php endif; ?>

            <table class="modern-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Joined</th>
                        <th style="text-align:right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($users as $user): ?>
                    <tr class="table-row-hover">
                        <td><span style="color:var(--text-muted); font-size:0.85rem;">#<?php echo $user['id']; ?></span></td>
                        <td>
                            <div style="font-weight:600; color:#fff;"><?php echo htmlspecialchars($user['name']); ?></div>
                        </td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td>
                            <span class="role-badge role-<?php echo $user['role']; ?>">
                                <?php echo ucfirst($user['role']); ?>
                            </span>
                        </td>
                        <td style="color:var(--text-muted); font-size:0.85rem;"><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                        <td style="text-align:right;">
                            <a href="?delete_user=<?php echo $user['id']; ?>" class="btn-delete" onclick="return confirm('Are you sure you want to delete this user? This will also delete all their events and bookings!')" aria-label="Delete User">
                                <?php echo Icons::get('trash', 'width:10px; height:10px;'); ?>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if(empty($users)) echo "<tr><td colspan='6' style='text-align:center; padding:40px; color:var(--text-muted);'>No users found.</td></tr>"; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>

</body>
</html>
