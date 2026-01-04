<?php
session_start();
require_once '../../config/database.php';
require_once '../../includes/icons.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') != 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

$msg = '';

// Handle Updates
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $privacy_content = $_POST['privacy_content'];
    $terms_content = $_POST['terms_content'];

    $stmt = $pdo->prepare("
        INSERT INTO site_settings (setting_key, setting_value) VALUES 
        ('page_privacy', ?), ('page_terms', ?)
        ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
    ");
    // Note: This logic assumes bulk insert/update or multiple queries. 
    // Simplified for PDO:
    
    $s1 = $pdo->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES ('page_privacy', ?) ON DUPLICATE KEY UPDATE setting_value = ?");
    $s1->execute([$privacy_content, $privacy_content]);
    
    $s2 = $pdo->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES ('page_terms', ?) ON DUPLICATE KEY UPDATE setting_value = ?");
    $s2->execute([$terms_content, $terms_content]);
    
    $msg = 'Pages updated successfully!';
}

// Fetch Content
$stmt = $pdo->query("SELECT * FROM site_settings WHERE setting_key IN ('page_privacy', 'page_terms')");
$data = $stmt->fetchAll(PDO::FETCH_KEY_PAIR); // [key => value]

$privacy = $data['page_privacy'] ?? '<h2>Privacy Policy</h2><p>Default privacy policy...</p>';
$terms = $data['page_terms'] ?? '<h2>Terms of Service</h2><p>Default terms...</p>';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>CMS - Edit Pages</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <script src="https://cdn.ckeditor.com/ckeditor5/40.0.0/classic/ckeditor.js"></script>
    <style>
        .ck-editor__editable { min-height: 200px; color: #000; }
        .ck.ck-editor__main>.ck-editor__editable:not(.ck-focused) { background: #f0f0f0; }
    </style>
</head>
<body style="background-color: var(--dark-bg);">

<div class="admin-layout">
    <?php include 'sidebar.php'; ?>

    <main class="main-content">
        <?php include 'admin_header.php'; ?>
    

        <div class="dashboard-panel">
            <h3 class="panel-title">Edit Legal Pages</h3>
            
            <?php if($msg): ?>
                <div style="background: rgba(34, 197, 94, 0.1); border: 1px solid rgba(34, 197, 94, 0.2); color: var(--success); padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                    <?php echo $msg; ?>
                </div>
            <?php endif; ?>

            <form method="post">
                
                <div style="margin-bottom: 40px;">
                    <div style="display:flex; justify-content:space-between; margin-bottom:10px;">
                        <h4 style="color:var(--text-main); margin:0;">Privacy Policy</h4>
                        <a href="/pages/privacy.php" target="_blank" style="color:var(--primary);">View Page ↗</a>
                    </div>
                    <textarea name="privacy_content" id="editor_privacy"><?php echo htmlspecialchars($privacy); ?></textarea>
                </div>

                <div style="margin-bottom: 40px;">
                    <div style="display:flex; justify-content:space-between; margin-bottom:10px;">
                        <h4 style="color:var(--text-main); margin:0;">Terms of Service</h4>
                        <a href="#" style="color:var(--primary);">View Page ↗</a>
                    </div>
                    <textarea name="terms_content" id="editor_terms"><?php echo htmlspecialchars($terms); ?></textarea>
                </div>

                <div style="text-align: right;">
                    <button type="submit" class="hero-btn" style="padding: 12px 30px;">Save All Pages</button>
                </div>
            </form>
        </div>
    </main>
</div>

<script>
    ClassicEditor.create(document.querySelector('#editor_privacy')).catch(error => console.error(error));
    ClassicEditor.create(document.querySelector('#editor_terms')).catch(error => console.error(error));
</script>

</body>
</html>
