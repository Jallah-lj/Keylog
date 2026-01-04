<?php
session_start();
require_once '../../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

$msg = '';

// Handle Update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Allowed setting keys whitelist
    $allowed_keys = [
        'site_name', 'copyright_text', 'contact_email', 'contact_phone', 
        'contact_address', 'facebook_url', 'twitter_url', 'instagram_url',
        'currency_symbol', 'date_format'
    ];

    foreach ($_POST as $key => $value) {
        // Skip non-setting fields
        if ($key == 'update_settings') continue;
        
        // Strict Validation
        if (!in_array($key, $allowed_keys)) {
            continue; // Ignore unauthorized keys
        }
        
        $stmt = $pdo->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
        $stmt->execute([$key, $value, $value]);
    }
    $msg = 'Settings updated successfully!';
}

// Fetch Settings
$stmt = $pdo->query("SELECT * FROM site_settings");
$settings_raw = $stmt->fetchAll(PDO::FETCH_ASSOC);
$settings = [];
foreach ($settings_raw as $s) {
    $settings[$s['setting_key']] = $s['setting_value'];
}

// Helper for safe output
function get_val($key, $data) {
    return htmlspecialchars($data[$key] ?? '');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Site Settings - Admin Panel</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body style="background-color: var(--dark-bg);">

<div class="admin-layout">
    <?php include 'sidebar.php'; ?>

    <main class="main-content">
        <?php include 'admin_header.php'; ?>


        <div class="dashboard-panel">
            <h3 class="panel-title">Global Configuration</h3>
            
            <?php if($msg): ?>
                <div style="background: rgba(34, 197, 94, 0.1); border: 1px solid rgba(34, 197, 94, 0.2); color: var(--success); padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                    <?php echo $msg; ?>
                </div>
            <?php endif; ?>

            <form method="post" class="modern-form">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">
                    <div>
                        <h4 style="margin-bottom: 20px; color: #fff; opacity: 0.8;">General Branding</h4>
                        
                        <div style="margin-bottom: 20px;">
                            <label style="display:block; color:var(--text-muted); margin-bottom:8px; font-size:0.9rem;">Site Name</label>
                            <input type="text" name="site_name" value="<?php echo get_val('site_name', $settings); ?>" style="width:100%; background:rgba(255,255,255,0.05); border:1px solid var(--border); color:#fff; padding:10px; border-radius:8px;">
                        </div>
                        
                        <div style="margin-bottom: 20px;">
                            <label style="display:block; color:var(--text-muted); margin-bottom:8px; font-size:0.9rem;">Copyright Text</label>
                            <input type="text" name="copyright_text" value="<?php echo get_val('copyright_text', $settings); ?>" style="width:100%; background:rgba(255,255,255,0.05); border:1px solid var(--border); color:#fff; padding:10px; border-radius:8px;">
                        </div>

                        <h4 style="margin: 20px 0 15px 0; color: #fff; opacity: 0.8;">Localization</h4>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                            <div>
                                <label style="display:block; color:var(--text-muted); margin-bottom:8px; font-size:0.9rem;">Currency</label>
                                <select name="currency_symbol" style="width:100%; background:rgba(255,255,255,0.05); border:1px solid var(--border); color:#fff; padding:10px; border-radius:8px;">
                                    <option value="$" <?php echo get_val('currency_symbol', $settings) == '$' ? 'selected' : ''; ?>>USD ($)</option>
                                    <option value="€" <?php echo get_val('currency_symbol', $settings) == '€' ? 'selected' : ''; ?>>EUR (€)</option>
                                    <option value="£" <?php echo get_val('currency_symbol', $settings) == '£' ? 'selected' : ''; ?>>GBP (£)</option>
                                    <option value="LRD" <?php echo get_val('currency_symbol', $settings) == 'LRD' ? 'selected' : ''; ?>>LRD ($)</option>
                                    <option value="¥" <?php echo get_val('currency_symbol', $settings) == '¥' ? 'selected' : ''; ?>>JPY (¥)</option>
                                </select>
                            </div>
                            <div>
                                <label style="display:block; color:var(--text-muted); margin-bottom:8px; font-size:0.9rem;">Date Format</label>
                                <select name="date_format" style="width:100%; background:rgba(255,255,255,0.05); border:1px solid var(--border); color:#fff; padding:10px; border-radius:8px;">
                                    <option value="M j, Y" <?php echo get_val('date_format', $settings) == 'M j, Y' ? 'selected' : ''; ?>>Dec 31, 2025</option>
                                    <option value="d/m/Y" <?php echo get_val('date_format', $settings) == 'd/m/Y' ? 'selected' : ''; ?>>31/12/2025</option>
                                    <option value="Y-m-d" <?php echo get_val('date_format', $settings) == 'Y-m-d' ? 'selected' : ''; ?>>2025-12-31</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div>
                        <h4 style="margin-bottom: 20px; color: #fff; opacity: 0.8;">Contact Info</h4>
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 20px;">
                            <div>
                                <label style="display:block; color:var(--text-muted); margin-bottom:8px; font-size:0.9rem;">Contact Email</label>
                                <input type="email" name="contact_email" value="<?php echo get_val('contact_email', $settings); ?>" style="width:100%; background:rgba(255,255,255,0.05); border:1px solid var(--border); color:#fff; padding:10px; border-radius:8px;">
                            </div>
                            <div>
                                <label style="display:block; color:var(--text-muted); margin-bottom:8px; font-size:0.9rem;">Contact Phone</label>
                                <input type="text" name="contact_phone" value="<?php echo get_val('contact_phone', $settings); ?>" style="width:100%; background:rgba(255,255,255,0.05); border:1px solid var(--border); color:#fff; padding:10px; border-radius:8px;">
                            </div>
                        </div>
                        
                        <div style="margin-bottom: 20px;">
                            <label style="display:block; color:var(--text-muted); margin-bottom:8px; font-size:0.9rem;">Address</label>
                            <textarea name="contact_address" rows="2" style="width:100%; background:rgba(255,255,255,0.05); border:1px solid var(--border); color:#fff; padding:10px; border-radius:8px; resize:none;"><?php echo get_val('contact_address', $settings); ?></textarea>
                        </div>
                    </div>
                </div>

                <h4 style="margin: 30px 0 20px 0; color: #fff; opacity: 0.8;">Social Networks</h4>
                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; margin-bottom: 30px;">
                    <div>
                        <label style="display:block; color:var(--text-muted); margin-bottom:8px; font-size:0.9rem;">Facebook</label>
                        <input type="text" name="facebook_url" value="<?php echo get_val('facebook_url', $settings); ?>" style="width:100%; background:rgba(255,255,255,0.05); border:1px solid var(--border); color:#fff; padding:10px; border-radius:8px;">
                    </div>
                    <div>
                        <label style="display:block; color:var(--text-muted); margin-bottom:8px; font-size:0.9rem;">Twitter</label>
                        <input type="text" name="twitter_url" value="<?php echo get_val('twitter_url', $settings); ?>" style="width:100%; background:rgba(255,255,255,0.05); border:1px solid var(--border); color:#fff; padding:10px; border-radius:8px;">
                    </div>
                    <div>
                        <label style="display:block; color:var(--text-muted); margin-bottom:8px; font-size:0.9rem;">Instagram</label>
                        <input type="text" name="instagram_url" value="<?php echo get_val('instagram_url', $settings); ?>" style="width:100%; background:rgba(255,255,255,0.05); border:1px solid var(--border); color:#fff; padding:10px; border-radius:8px;">
                    </div>
                </div>

                <button type="submit" name="update_settings" style="background:var(--primary); color:#fff; border:none; padding:12px 30px; border-radius:8px; font-weight:600; cursor:pointer; transition:all 0.3s;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='translateY(0)'">
                    Save Changes
                </button>
            </form>
        </div>
    </main>
</div>

</body>
</html>
