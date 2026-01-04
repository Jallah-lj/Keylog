<?php 
session_start();
require_once '../config/database.php';

// Fetch Content
$stmt = $pdo->prepare("SELECT setting_value FROM site_settings WHERE setting_key = 'page_privacy'");
$stmt->execute();
$content = $stmt->fetchColumn();

// Fallback if empty
if (!$content) {
    $content = "
        <section style='margin-bottom: 40px;'>
            <h2 style='color:var(--primary); font-size: 1.5em; margin-bottom: 15px;'>Privacy Policy</h2>
            <p style='color: #cbd5e1; line-height: 1.7;'>Content not yet configured by admin.</p>
        </section>
    ";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Privacy Policy - Event Platform</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
        /* Wysiwyg Content Styles */
        .content-area h2 { color: var(--primary); font-size: 1.5em; margin-bottom: 15px; margin-top: 30px; }
        .content-area p { color: #cbd5e1; line-height: 1.7; margin-bottom: 15px; }
        .content-area ul { list-style: disc; margin-left: 20px; color: #cbd5e1; margin-bottom: 15px; }
    </style>
</head>
<body style="display: flex; flex-direction: column; min-height: 100vh;">
    <header>
        <h1>Privacy Policy</h1>
        <nav>
            <a href="/index.php">Home</a>
        </nav>
    </header>
    <main>
        <div style="text-align:center; padding: 40px 0;">
            <h2 style="font-size: 3em; margin-bottom: 20px; background: linear-gradient(to right, #64748b, #94a3b8); -webkit-background-clip: text; color: transparent;">Privacy Policy</h2>
            <p style="font-size: 1.2em; color: #888;">Updated via CMS</p>
        </div>

        <div class="content-area" style="background: var(--card-bg); backdrop-filter: blur(10px); padding:50px; border-radius:16px; border: 1px solid var(--border); max-width: 900px; margin: 0 auto 50px;">
            <?php echo $content; ?>
        </div>
    </main>
    <?php include '../includes/footer.php'; ?>
</body>
</html>
