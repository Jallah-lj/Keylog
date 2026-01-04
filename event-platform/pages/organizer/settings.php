<?php
session_start();
require_once '../../config/database.php';
require_once '../../includes/icons.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') != 'organizer') {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

$msg = '';
$settings_file = "../../uploads/settings_{$user_id}.json";
$org_settings = [];

// Load existing settings
if (file_exists($settings_file)) {
    $org_settings = json_decode(file_get_contents($settings_file), true);
}

// Handle Settings Update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $new_settings = [
        'org_name' => $_POST['org_name'] ?? '',
        'theme_color' => $_POST['theme_color'] ?? 'indigo',
        'enable_analytics' => isset($_POST['enable_analytics']),
        'enable_mailchimp' => isset($_POST['enable_mailchimp']),
        'public_profile' => isset($_POST['public_profile']),
        'auto_reminders' => isset($_POST['auto_reminders'])
    ];
    
    // Save to file
    file_put_contents($settings_file, json_encode($new_settings));
    $org_settings = $new_settings; // Update current state
    $msg = 'Settings saved successfully!';
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Organizer Settings - Event Platform</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body style="background-color: var(--dark-bg);">

<div class="admin-layout">
    <?php include 'sidebar.php'; ?>

    <main class="main-content">
        <div class="top-bar">
            <div class="breadcrumbs">Organizer / Settings</div>
        </div>

        <div class="dashboard-panel">
            <h3 class="panel-title">Organizer Configuration</h3>
            
            <?php if($msg): ?>
                <div style="background: rgba(34, 197, 94, 0.1); border: 1px solid rgba(34, 197, 94, 0.2); color: var(--success); padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                    <?php echo $msg; ?>
                </div>
            <?php endif; ?>

            <form method="post" class="modern-form">
                
                <!-- Section 1: Branding -->
                <div style="margin-bottom: 40px;">
                    <h4 style="color: #fff; border-bottom: 1px solid var(--border); padding-bottom: 10px; margin-bottom: 20px;">
                        Event Customization & Branding
                    </h4>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">
                        <div>
                            <label style="display:block; color:var(--text-muted); margin-bottom:8px;">Organization Name</label>
                            <input type="text" name="org_name" value="<?php echo htmlspecialchars($org_settings['org_name'] ?? $user_name); ?>" style="width:100%; padding:10px; border-radius:8px; background:rgba(255,255,255,0.05); border:1px solid var(--border); color:#fff;">
                        </div>
                        <div>
                            <label style="display:block; color:var(--text-muted); margin-bottom:8px;">Theme Color</label>
                            <select name="theme_color" style="width:100%; padding:10px; border-radius:8px; background:rgba(255,255,255,0.05); border:1px solid var(--border); color:#fff;">
                                <option value="indigo" <?php echo ($org_settings['theme_color'] ?? '') == 'indigo' ? 'selected' : ''; ?>>Default Indigo</option>
                                <option value="purple" <?php echo ($org_settings['theme_color'] ?? '') == 'purple' ? 'selected' : ''; ?>>Royal Purple</option>
                                <option value="emerald" <?php echo ($org_settings['theme_color'] ?? '') == 'emerald' ? 'selected' : ''; ?>>Emerald Green</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Section 2: Integration & Analytics -->
                <div style="margin-bottom: 40px;">
                    <h4 style="color: #fff; border-bottom: 1px solid var(--border); padding-bottom: 10px; margin-bottom: 20px;">
                        Integrations & Tools
                    </h4>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <label class="toggle-control" style="display:flex; align-items:center; justify-content:space-between; padding:15px; border:1px solid var(--border); border-radius:8px; cursor:pointer; background:rgba(255,255,255,0.02);">
                            <div>
                                <strong style="color:#fff; display:block;">Google Analytics</strong>
                                <span style="font-size:0.8rem; color:var(--text-muted);">Track page views and conversions</span>
                            </div>
                            <input type="checkbox" name="enable_analytics" <?php echo !empty($org_settings['enable_analytics']) ? 'checked' : ''; ?>>
                        </label>

                        <label class="toggle-control" style="display:flex; align-items:center; justify-content:space-between; padding:15px; border:1px solid var(--border); border-radius:8px; cursor:pointer; background:rgba(255,255,255,0.02);">
                            <div>
                                <strong style="color:#fff; display:block;">Mailchimp Integration</strong>
                                <span style="font-size:0.8rem; color:var(--text-muted);">Sync attendees to mailing lists</span>
                            </div>
                            <input type="checkbox" name="enable_mailchimp" <?php echo !empty($org_settings['enable_mailchimp']) ? 'checked' : ''; ?>>
                        </label>
                    </div>
                </div>

                <!-- Section 3: Privacy & Communication -->
                <div style="margin-bottom: 40px;">
                    <h4 style="color: #fff; border-bottom: 1px solid var(--border); padding-bottom: 10px; margin-bottom: 20px;">
                        Privacy & Communication
                    </h4>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <label class="toggle-control" style="display:flex; align-items:center; justify-content:space-between; padding:15px; border:1px solid var(--border); border-radius:8px; cursor:pointer; background:rgba(255,255,255,0.02);">
                            <div>
                                <strong style="color:#fff; display:block;">Public Organizer Profile</strong>
                                <span style="font-size:0.8rem; color:var(--text-muted);">Show your past events on profile</span>
                            </div>
                            <input type="checkbox" name="public_profile" <?php echo !empty($org_settings['public_profile']) ? 'checked' : ''; ?>>
                        </label>

                        <label class="toggle-control" style="display:flex; align-items:center; justify-content:space-between; padding:15px; border:1px solid var(--border); border-radius:8px; cursor:pointer; background:rgba(255,255,255,0.02);">
                            <div>
                                <strong style="color:#fff; display:block;">Auto-Send Reminders</strong>
                                <span style="font-size:0.8rem; color:var(--text-muted);">Email attendees 24h before event</span>
                            </div>
                            <input type="checkbox" name="auto_reminders" <?php echo !empty($org_settings['auto_reminders']) ? 'checked' : ''; ?>>
                        </label>
                    </div>
                </div>

                <!-- Danger Zone -->
                <div style="margin-bottom: 40px; border: 1px solid var(--danger); border-radius: 12px; overflow: hidden;">
                    <div style="background: rgba(248, 113, 113, 0.1); padding: 15px; border-bottom: 1px solid var(--danger);">
                        <h4 style="color: var(--danger); margin: 0;">Danger Zone</h4>
                    </div>
                    <div style="padding: 20px;">
                        <p style="color: var(--text-muted); margin-bottom: 15px;">
                            Permanently delete your account and all associated data. This action cannot be undone.
                            All your events, attendee lists, and sales data will be wiped immediately.
                        </p>
                        <button type="button" onclick="confirmDeletion()" class="hero-btn" style="background: transparent; border: 1px solid var(--danger); color: var(--danger); padding: 10px 20px;">
                            Delete Account
                        </button>
                    </div>
                </div>

                <div style="text-align: right;">
                    <button type="submit" class="hero-btn" style="padding: 12px 30px; font-size: 1rem;">Save Preferences</button>
                </div>
            </form>

            <form id="deleteForm" action="delete_account.php" method="post" style="display:none;">
                <input type="hidden" name="confirm_delete" value="1">
            </form>

            <script>
                function confirmDeletion() {
                    const confirmation = prompt("To confirm deletion, type 'DELETE' below:");
                    if (confirmation === 'DELETE') {
                        document.getElementById('deleteForm').submit();
                    } else if (confirmation !== null) {
                        alert("Incorrect confirmation code. Account not deleted.");
                    }
                }
            </script>
        </div>
    </main>
</div>

</body>
</html>
