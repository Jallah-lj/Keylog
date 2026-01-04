<?php
session_start();
require_once '../../config/database.php';
require_once '../../includes/icons.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') != 'organizer') {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$msg = '';

// Handle Profile Update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $bio = trim($_POST['bio']);
    
    // Avatar Upload
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $ext = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, $allowed)) {
            $new_name = 'avatar_'.$user_id.'_'.time().'.'.$ext;
            $dest = '../../uploads/'.$new_name;
            if (move_uploaded_file($_FILES['avatar']['tmp_name'], $dest)) {
                $upd_av = $pdo->prepare("UPDATE users SET avatar = ? WHERE id = ?");
                $upd_av->execute([$new_name, $user_id]);
                $msg .= " Avatar updated.";
            }
        } else {
            $msg .= " Invalid file type.";
        }
    }

    // Update Core User Data
    $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, bio = ? WHERE id = ?");
    if ($stmt->execute([$name, $email, $bio, $user_id])) {
        $_SESSION['user_name'] = $name;
        $msg = 'Profile updated successfully!';
    } else {
        $msg = 'Error updating profile.';
    }

    // Update Extra Fields (Phone, Social) in JSON
    $settings_file = "../../uploads/settings_{$user_id}.json";
    $extras = [
        'phone' => $_POST['phone'] ?? '',
        'website' => $_POST['website'] ?? '',
        'facebook' => $_POST['facebook'] ?? '',
        'twitter' => $_POST['twitter'] ?? '',
        'instagram' => $_POST['instagram'] ?? ''
    ];
    
    // Merge with existing settings if any
    $current_settings = [];
    if (file_exists($settings_file)) {
        $decoded = json_decode(file_get_contents($settings_file), true);
        if (is_array($decoded)) {
            $current_settings = $decoded;
        }
    }
    $new_settings = array_merge($current_settings, $extras);
    file_put_contents($settings_file, json_encode($new_settings));
}

// Fetch User Data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch Extra Data
$settings_file = "../../uploads/settings_{$user_id}.json";
$extras = file_exists($settings_file) ? json_decode(file_get_contents($settings_file), true) : [];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Organizer Profile - Event Platform</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body style="background-color: var(--dark-bg);">

<div class="admin-layout">
    <?php include 'sidebar.php'; ?>

    <main class="main-content">
        <div class="top-bar">
            <div class="breadcrumbs">Organizer / Profile</div>
        </div>

        <div class="dashboard-panel">
            <h3 class="panel-title">My Profile</h3>
            
            <?php if($msg): ?>
                <div style="background: rgba(34, 197, 94, 0.1); border: 1px solid rgba(34, 197, 94, 0.2); color: var(--success); padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                    <?php echo $msg; ?>
                </div>
            <?php endif; ?>

            <form method="post" class="modern-form" enctype="multipart/form-data">
                <div style="display: flex; gap: 30px; align-items: flex-start; flex-wrap: wrap;">
                    
                    <!-- Avatar Section -->
                    <div style="text-align: center; flex: 0 0 150px;">
                        <div style="width: 120px; height: 120px; border-radius: 50%; background: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 3rem; color: #fff; margin: 0 auto 15px; overflow: hidden; border: 3px solid var(--card-bg); box-shadow: 0 0 20px rgba(0,0,0,0.5);">
                            <?php if($user['avatar']): ?>
                                <img src="../../uploads/<?php echo htmlspecialchars($user['avatar']); ?>" style="width: 100%; height: 100%; object-fit: cover;">
                            <?php else: ?>
                                <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                            <?php endif; ?>
                        </div>
                        <label class="action-btn" style="width: 100%; display: block; cursor: pointer; font-size: 0.9rem;">
                            Change Photo
                            <input type="file" name="avatar" style="display: none;" onchange="this.form.submit()">
                        </label>
                    </div>

                    <!-- Details Section -->
                    <div style="flex: 1; min-width: 300px;">
                        <h4 style="color: var(--text-main); border-bottom: 1px solid var(--border); padding-bottom: 10px; margin-bottom: 20px;">Basic Info</h4>
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                            <div>
                                <label style="display:block; color:var(--text-muted); margin-bottom:8px;">Full Name</label>
                                <input type="text" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required style="width:100%; padding:12px; background:rgba(255,255,255,0.05); border:1px solid var(--border); border-radius:8px; color:#fff;">
                            </div>
                            <div>
                                <label style="display:block; color:var(--text-muted); margin-bottom:8px;">Phone Number</label>
                                <input type="text" name="phone" value="<?php echo htmlspecialchars($extras['phone'] ?? ''); ?>" placeholder="+1 (555) 000-0000" style="width:100%; padding:12px; background:rgba(255,255,255,0.05); border:1px solid var(--border); border-radius:8px; color:#fff;">
                            </div>
                        </div>

                        <div style="margin-bottom: 20px;">
                            <label style="display:block; color:var(--text-muted); margin-bottom:8px;">Email Address</label>
                            <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required style="width:100%; padding:12px; background:rgba(255,255,255,0.05); border:1px solid var(--border); border-radius:8px; color:#fff;">
                        </div>

                        <div style="margin-bottom: 30px;">
                            <label style="display:block; color:var(--text-muted); margin-bottom:8px;">Organizer Bio (Public)</label>
                            <textarea name="bio" rows="4" style="width:100%; padding:12px; background:rgba(255,255,255,0.05); border:1px solid var(--border); border-radius:8px; color:#fff;"><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                        </div>

                        <h4 style="color: var(--text-main); border-bottom: 1px solid var(--border); padding-bottom: 10px; margin-bottom: 20px;">Social & Links</h4>

                        <div style="margin-bottom: 20px;">
                            <label style="display:block; color:var(--text-muted); margin-bottom:8px;">Website URL</label>
                            <input type="url" name="website" value="<?php echo htmlspecialchars($extras['website'] ?? ''); ?>" placeholder="https://your-org.com" style="width:100%; padding:12px; background:rgba(255,255,255,0.05); border:1px solid var(--border); border-radius:8px; color:#fff;">
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; margin-bottom: 30px;">
                            <div>
                                <label style="display:block; color:var(--text-muted); margin-bottom:8px;">Facebook</label>
                                <input type="text" name="facebook" value="<?php echo htmlspecialchars($extras['facebook'] ?? ''); ?>" placeholder="Username" style="width:100%; padding:12px; background:rgba(255,255,255,0.05); border:1px solid var(--border); border-radius:8px; color:#fff;">
                            </div>
                            <div>
                                <label style="display:block; color:var(--text-muted); margin-bottom:8px;">Twitter / X</label>
                                <input type="text" name="twitter" value="<?php echo htmlspecialchars($extras['twitter'] ?? ''); ?>" placeholder="@username" style="width:100%; padding:12px; background:rgba(255,255,255,0.05); border:1px solid var(--border); border-radius:8px; color:#fff;">
                            </div>
                            <div>
                                <label style="display:block; color:var(--text-muted); margin-bottom:8px;">Instagram</label>
                                <input type="text" name="instagram" value="<?php echo htmlspecialchars($extras['instagram'] ?? ''); ?>" placeholder="@username" style="width:100%; padding:12px; background:rgba(255,255,255,0.05); border:1px solid var(--border); border-radius:8px; color:#fff;">
                            </div>
                        </div>
                        
                        <div style="text-align: right;">
                            <button type="submit" class="hero-btn" style="padding: 12px 30px;">Save Profile</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </main>
</div>

</body>
</html>
