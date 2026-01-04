<?php
session_start();
require_once '../config/database.php';
require_once '../includes/icons.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$msg = '';
$error = '';

// Determine active tab
$tab = $_GET['tab'] ?? 'account';

// --- POST HANDLING ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_profile') {
        $name = trim($_POST['name']);
        $phone = trim($_POST['phone'] ?? '');
        $bio = trim($_POST['bio'] ?? '');
        
        $update_query = "UPDATE users SET name = ?, phone = ?, bio = ?";
        $params = [$name, $phone, $bio];

        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] == 0) {
            $upload_dir = '../uploads/';
            $file_name = 'avatar_' . $user_id . '_' . time() . '.jpg';
            $target_file = $upload_dir . $file_name;
            if (move_uploaded_file($_FILES['avatar']['tmp_name'], $target_file)) {
                $update_query .= ", avatar = ?";
                $params[] = $file_name;
                $_SESSION['user_avatar'] = $file_name;
            }
        }
        
        $update_query .= " WHERE id = ?";
        $params[] = $user_id;

        $stmt = $pdo->prepare($update_query);
        if ($stmt->execute($params)) {
            $_SESSION['user_name'] = $name;
            $msg = "Profile updated successfully!";
        } else {
            $error = "Error updating profile.";
        }
    } 
    elseif ($action === 'change_password') {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];

        $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $stored_pw = $stmt->fetchColumn();

        if (password_verify($current_password, $stored_pw)) {
            $hashed_pw = password_hash($new_password, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$hashed_pw, $user_id]);
            $msg = "Password changed successfully!";
            $tab = 'security';
        } else {
            $error = "Incorrect current password.";
            $tab = 'security';
        }
    }
    elseif ($action === 'update_preferences') {
        $email_notif = isset($_POST['email_notifications']) ? 1 : 0;
        $sms_notif = isset($_POST['sms_notifications']) ? 1 : 0;
        $timezone = $_POST['timezone'] ?? 'UTC';
        $language = $_POST['language'] ?? 'en';

        $stmt = $pdo->prepare("UPDATE users SET email_notifications = ?, sms_notifications = ?, timezone = ?, language = ? WHERE id = ?");
        if ($stmt->execute([$email_notif, $sms_notif, $timezone, $language, $user_id])) {
            $msg = "Preferences updated!";
            $tab = 'preferences';
        } else {
            $error = "Error updating preferences.";
            $tab = 'preferences';
        }
    }
}

// Fetch user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

require_once '../includes/header.php'; 
?>

<style>
    :root {
        --sidebar-width: 280px;
        --card-radius: 16px;
        --font-main: 'Outfit', sans-serif;
    }
    .mgmt-container { 
        display: flex; 
        gap: 40px; 
        max-width: 1400px; 
        margin: 40px auto; 
        padding: 0 40px; 
        font-family: var(--font-main);
    }
    .mgmt-sidebar { 
        width: var(--sidebar-width); 
        flex-shrink: 0; 
        position: sticky;
        top: 100px;
        height: fit-content;
    }
    .mgmt-content { flex: 1; }
    
    .tab-link { 
        display: flex; 
        align-items: center; 
        gap: 16px; 
        padding: 14px 20px; 
        color: var(--text-muted); 
        text-decoration: none; 
        border-radius: 12px;
        margin-bottom: 8px; 
        transition: all 0.3s ease; 
        font-weight: 500;
        font-size: 15px;
    }
    .tab-link:hover { background: rgba(255,255,255,0.05); color: #fff; transform: translateX(5px); }
    .tab-link.active { 
        background: var(--primary); 
        color: #fff; 
        box-shadow: 0 8px 20px rgba(99, 102, 241, 0.4); 
    }
    
    .tab-link svg {
        width: 18px;
        height: 18px;
        flex-shrink: 0;
    }
    
    .section-title { 
        font-size: 24px; 
        font-weight: 700;
        color: #fff; 
        margin-bottom: 30px; 
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .mgmt-card {
        background: var(--card-bg);
        border: 1px solid var(--border);
        border-radius: var(--card-radius);
        padding: 30px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.2);
    }

    /* Ticket Table Styles from Spec */
    .ticket-table { width: 100%; border-collapse: separate; border-spacing: 0 12px; }
    .ticket-table th { 
        padding: 0 20px 10px; 
        text-align: left; 
        color: var(--text-muted); 
        font-size: 13px; 
        text-transform: uppercase; 
        letter-spacing: 1px;
    }
    .ticket-row { 
        background: rgba(255,255,255,0.03); 
        transition: all 0.3s ease;
    }
    .ticket-row:hover { background: rgba(255,255,255,0.06); }
    .ticket-row td { 
        padding: 16px 20px; 
        font-size: 15px;
        border-top: 1px solid var(--border);
        border-bottom: 1px solid var(--border);
    }
    .ticket-row td:first-child { border-left: 1px solid var(--border); border-radius: 16px 0 0 16px; }
    .ticket-row td:last-child { border-right: 1px solid var(--border); border-radius: 0 16px 16px 0; }

    .cancel-btn {
        background: transparent;
        border: 1px solid var(--danger);
        color: var(--danger);
        padding: 8px 16px;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
    }
    .cancel-btn:hover { background: rgba(239, 68, 68, 0.1); }
</style>

<main>
    <div class="mgmt-container">
        <!-- Sidebar Navigation -->
        <aside class="mgmt-sidebar">
            <div style="margin-bottom: 30px; text-align: center;">
                <div style="position: relative; display: inline-block;">
                    <?php if($user['avatar']): ?>
                        <img src="../uploads/<?php echo htmlspecialchars($user['avatar']); ?>" style="width: 80px; height: 80px; border-radius: 50%; object-fit: cover; border: 2px solid var(--primary);">
                    <?php else: ?>
                        <div style="width: 80px; height: 80px; background: rgba(255,255,255,0.05); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 2rem; color: var(--text-muted);">
                            <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                        </div>
                    <?php endif; ?>
                </div>
                <h3 style="color: #fff; margin: 10px 0 5px 0;"><?php echo htmlspecialchars($user['name']); ?></h3>
                <p style="color: var(--text-muted); font-size: 0.85rem;"><?php echo htmlspecialchars($user['email']); ?></p>
            </div>

            <nav>
                <a href="?tab=account" class="tab-link <?php echo $tab == 'account' ? 'active' : ''; ?>">
                    <?php echo Icons::get('user'); ?> My Profile
                </a>
                <a href="?tab=security" class="tab-link <?php echo $tab == 'security' ? 'active' : ''; ?>">
                    <?php echo Icons::get('shield-check'); ?> Security
                </a>
                <a href="?tab=history" class="tab-link <?php echo $tab == 'history' ? 'active' : ''; ?>">
                    <?php echo Icons::get('ticket'); ?> My Tickets
                </a>
                <a href="?tab=preferences" class="tab-link <?php echo $tab == 'preferences' ? 'active' : ''; ?>">
                    <?php echo Icons::get('settings'); ?> Preferences
                </a>
                <div style="margin-top: 50px; border-top: 1px solid var(--border); padding-top: 20px;">
                    <a href="auth/logout.php" class="tab-link" style="color: var(--danger);">
                        <?php echo Icons::get('logout'); ?> Logout
                    </a>
                </div>
            </nav>
        </aside>

        <!-- Main Content -->
        <div class="mgmt-content">
            <?php if ($msg): ?>
                <div style="background: rgba(16, 185, 129, 0.1); color: var(--success); padding: 15px; border-radius: 12px; margin-bottom: 25px;"><?php echo $msg; ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div style="background: rgba(239, 68, 68, 0.1); color: var(--danger); padding: 15px; border-radius: 12px; margin-bottom: 25px;"><?php echo $error; ?></div>
            <?php endif; ?>

            <div class="mgmt-card">
                
                <?php if ($tab == 'account'): ?>
                <h2 class="section-title">Profile Information</h2>
                
                <!-- Quick Stats -->
                <?php
                $stmt_stat = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE user_id = ?");
                $stmt_stat->execute([$user_id]);
                $total_bk = $stmt_stat->fetchColumn();

                $stmt_up = $pdo->prepare("SELECT COUNT(*) FROM bookings b JOIN events e ON b.event_id = e.id WHERE b.user_id = ? AND e.event_date >= NOW()");
                $stmt_up->execute([$user_id]);
                $up_bk = $stmt_up->fetchColumn();
                ?>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 40px;">
                    <div style="background: rgba(99, 102, 241, 0.05); padding: 20px; border-radius: 16px; border: 1px solid rgba(99, 102, 241, 0.1);">
                        <div style="color: var(--text-muted); font-size: 0.8rem; text-transform: uppercase; font-weight: 700;">Lifetime Bookings</div>
                        <div style="font-size: 2rem; color: #fff; font-weight: 800;"><?php echo $total_bk; ?></div>
                    </div>
                    <div style="background: rgba(16, 185, 129, 0.05); padding: 20px; border-radius: 16px; border: 1px solid rgba(16, 185, 129, 0.1);">
                        <div style="color: var(--text-muted); font-size: 0.8rem; text-transform: uppercase; font-weight: 700;">Upcoming Events</div>
                        <div style="font-size: 2rem; color: #fff; font-weight: 800;"><?php echo $up_bk; ?></div>
                    </div>
                </div>

                <form method="post" enctype="multipart/form-data" class="modern-form">
                    <input type="hidden" name="action" value="update_profile">
                    
                    <div style="display: flex; gap: 30px; margin-bottom: 30px;">
                        <div style="flex: 1;">
                            <label class="form-label">Full Name</label>
                            <input type="text" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required class="glass-input">
                        </div>
                        <div style="flex: 1;">
                            <label class="form-label">Phone Number</label>
                            <input type="text" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" placeholder="+1 234 567 890" class="glass-input">
                        </div>
                    </div>

                    <div style="margin-bottom: 30px;">
                        <label class="form-label">Bio (Tell us about yourself)</label>
                        <textarea name="bio" rows="4" class="glass-input" style="height: auto;"><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                    </div>

                    <div style="margin-bottom: 40px;">
                        <label class="form-label">Update Avatar</label>
                        <input type="file" name="avatar" accept="image/*" class="glass-input">
                    </div>

                    <button type="submit" class="hero-btn" style="padding: 12px 30px;">Save Changes</button>
                </form>

                <?php elseif ($tab == 'security'): ?>
                <h2 class="section-title">Account Security</h2>
                <div style="margin-bottom: 40px;">
                    <h3 style="color: #fff; font-size: 1.1rem; margin-bottom: 20px;">Change Password</h3>
                    <form method="post" class="modern-form" style="max-width: 450px;">
                        <input type="hidden" name="action" value="change_password">
                        <div style="margin-bottom: 20px;">
                            <label class="form-label">Current Password</label>
                            <input type="password" name="current_password" required class="glass-input">
                        </div>
                        <div style="margin-bottom: 20px;">
                            <label class="form-label">New Password</label>
                            <input type="password" name="new_password" required class="glass-input">
                        </div>
                        <button type="submit" class="hero-btn" style="padding: 12px 30px;">Update Password</button>
                    </form>
                </div>

                <div style="border-top: 1px solid var(--border); padding-top: 30px;">
                    <h3 style="color: var(--danger); font-size: 1.1rem; margin-bottom: 10px;">Danger Zone</h3>
                    <p style="color: var(--text-muted); font-size: 0.85rem; margin-bottom: 20px;">Permanently delete your account and all event history.</p>
                    <button type="button" onclick="confirmDelete()" style="background: transparent; border: 1px solid var(--danger); color: var(--danger); padding: 10px 20px; border-radius: 8px; font-weight: 600; cursor: pointer;">Delete My Account</button>
                </div>

                <script>
                function confirmDelete() {
                    if (confirm('Are you absolutely sure? This action is IRREVERSIBLE.')) {
                        window.location.href = 'organizer/delete_account.php';
                    }
                }
                </script>

                <?php elseif ($tab == 'history'): ?>
                <h2 class="section-title">Purchase History & Tickets</h2>
                <?php
                $booking_stmt = $pdo->prepare("
                    SELECT b.*, e.title, e.event_date, e.location, tt.name as ticket_type_name
                    FROM bookings b 
                    JOIN events e ON b.event_id = e.id 
                    LEFT JOIN ticket_types tt ON b.ticket_type_id = tt.id
                    WHERE b.user_id = ? 
                    ORDER BY e.event_date DESC
                ");
                $booking_stmt->execute([$user_id]);
                $bookings = $booking_stmt->fetchAll(PDO::FETCH_ASSOC);

                if (count($bookings) > 0):
                ?>
                <table class="ticket-table">
                    <thead>
                        <tr>
                            <th>Event</th>
                            <th>Date</th>
                            <th>Type</th>
                            <th>Code</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($bookings as $b): 
                            // Color Coding Logic (VIP, GOLDEN, REGULAR)
                            $tt_name = strtoupper($b['ticket_type_name'] ?? 'REGULAR');
                            $accent_color = '#94a3b8'; // Default Slate
                            
                            if (stripos($tt_name, 'VIP') !== false) {
                                $accent_color = '#a855f7'; // Purple
                            } elseif (stripos($tt_name, 'GOLD') !== false) {
                                $accent_color = '#fbbf24'; // Gold
                            } elseif (stripos($tt_name, 'REGULAR') !== false) {
                                $accent_color = '#2dd4bf'; // Teal
                            }
                        ?>
                        <tr class="ticket-row" style="border-left: 4px solid <?php echo $accent_color; ?>;">
                            <td>
                                <div style="color: #fff; font-weight: 600;"><?php echo htmlspecialchars($b['title']); ?></div>
                                <div style="font-size: 12px; color: var(--text-muted);"><?php echo htmlspecialchars($b['location']); ?></div>
                            </td>
                            <td><?php echo date('M j, Y', strtotime($b['event_date'])); ?></td>
                            <td>
                                <span style="font-size: 12px; font-weight: 700; color: <?php echo $accent_color; ?>; background: rgba(0,0,0,0.2); padding: 4px 8px; border-radius: 6px; text-transform: uppercase;">
                                    <?php echo htmlspecialchars($tt_name); ?>
                                </span>
                            </td>
                            <td style="font-family: monospace; font-size: 14px;"><?php echo strtoupper($b['ticket_code']); ?></td>
                            <td><span class="status-pill status-published" style="font-size: 12px;">Confirmed</span></td>
                            <td>
                                <div style="display: flex; gap: 16px; align-items: center;">
                                    <a href="tickets/view.php?code=<?php echo $b['ticket_code']; ?>" style="color: var(--primary-light); text-decoration: none; font-size: 14px; font-weight: 600;">View Ticket</a>
                                    <?php 
                                    $can_cancel = (strtotime($b['event_date']) - time()) > 86400;
                                    if ($can_cancel): ?>
                                        <form method="POST" action="tickets/cancel.php" onsubmit="return confirm('Cancel this ticket? This cannot be undone.')" style="margin: 0;">
                                            <input type="hidden" name="ticket_code" value="<?php echo $b['ticket_code']; ?>">
                                            <button type="submit" class="cancel-btn">Cancel</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                    <div style="text-align: center; padding: 40px; color: var(--text-muted);">
                        No ticket history found.
                    </div>
                <?php endif; ?>

                <?php elseif ($tab == 'preferences'): ?>
                <h2 class="section-title">Settings & Preferences</h2>
                <form method="post" class="modern-form">
                    <input type="hidden" name="action" value="update_preferences">
                    
                    <div style="margin-bottom: 40px;">
                        <h3 style="color: #fff; font-size: 1.1rem; margin-bottom: 20px;">Notification Channels</h3>
                        <div style="display: flex; flex-direction: column; gap: 15px;">
                            <label style="display: flex; align-items: center; gap: 12px; cursor: pointer;">
                                <input type="checkbox" name="email_notifications" <?php echo ($user['email_notifications'] ?? 1) ? 'checked' : ''; ?> style="width: 20px; height: 20px; accent-color: var(--primary);">
                                <span style="color: #fff;">Email Notifications (Updates & Reminders)</span>
                            </label>
                            <label style="display: flex; align-items: center; gap: 12px; cursor: pointer;">
                                <input type="checkbox" name="sms_notifications" <?php echo ($user['sms_notifications'] ?? 0) ? 'checked' : ''; ?> style="width: 20px; height: 20px; accent-color: var(--primary);">
                                <span style="color: #fff;">SMS Notifications (Urgent Alerts)</span>
                            </label>
                        </div>
                    </div>

                    <div style="display: flex; gap: 30px; margin-bottom: 40px;">
                        <div style="flex: 1;">
                            <label class="form-label">Preferred Language</label>
                            <select name="language" class="glass-input glass-select">
                                <option value="en" <?php echo ($user['language'] ?? 'en') == 'en' ? 'selected' : ''; ?>>English</option>
                                <option value="fr" <?php echo ($user['language'] ?? '') == 'fr' ? 'selected' : ''; ?>>French</option>
                                <option value="es" <?php echo ($user['language'] ?? '') == 'es' ? 'selected' : ''; ?>>Spanish</option>
                            </select>
                        </div>
                        <div style="flex: 1;">
                            <label class="form-label">Time Zone</label>
                            <select name="timezone" class="glass-input glass-select">
                                <option value="UTC" <?php echo ($user['timezone'] ?? 'UTC') == 'UTC' ? 'selected' : ''; ?>>UTC (Coordinated Universal Time)</option>
                                <option value="EST" <?php echo ($user['timezone'] ?? '') == 'EST' ? 'selected' : ''; ?>>EST (Eastern Standard Time)</option>
                                <option value="GMT" <?php echo ($user['timezone'] ?? '') == 'GMT' ? 'selected' : ''; ?>>GMT (Greenwich Mean Time)</option>
                            </select>
                        </div>
                    </div>

                    <button type="submit" class="hero-btn" style="padding: 12px 30px;">Save Preferences</button>
                </form>
                <?php endif; ?>

            </div>
        </div>
    </div>
</main>
<?php require_once '../includes/footer.php'; ?>
