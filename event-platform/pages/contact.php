<?php
// Fetch Settings
require_once '../config/database.php';
require_once '../includes/icons.php';
$stmt = $pdo->query("SELECT * FROM site_settings");
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
$settings = [];
foreach ($results as $r) {
    $settings[$r['setting_key']] = $r['setting_value'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Contact Us - <?php echo htmlspecialchars($settings['site_name'] ?? 'Event Platform'); ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body style="display: flex; flex-direction: column; min-height: 100vh;">
    <header>
        <h1>Contact Us</h1>
        <nav>
            <a href="/index.php">Home</a>
        </nav>
    </header>
    <main>
        <div style="text-align:center; padding: 40px 0;">
            <h2 style="font-size: 3em; margin-bottom: 20px; background: linear-gradient(to right, #22c55e, #14b8a6); -webkit-background-clip: text; color: transparent;">Contact Us</h2>
            <p style="font-size: 1.2em; color: #cbd5e1;">We're here to help.</p>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 40px;">
            <!-- Contact Info -->
            <div style="background: var(--card-bg); backdrop-filter: blur(10px); padding:40px; border-radius:16px; border: 1px solid var(--border);">
                <h3 style="color:var(--primary); margin-bottom: 30px;">Get in Touch</h3>
                
                <ul style="list-style:none; padding:0;">
                    <li style="margin-bottom:25px; display:flex; align-items:center;">
                        <span style="background: rgba(255,255,255,0.1); width: 50px; height: 50px; display: flex; align-items: center; justify-content: center; border-radius: 50%; color: #fff;">
                            <?php echo Icons::get('mail', 'width:24px; height:24px;'); ?>
                        </span>
                        <div style="margin-left:20px;">
                            <span style="display:block; color:#888; font-size:0.8em; text-transform:uppercase;">Email</span>
                            <a href="mailto:<?php echo htmlspecialchars($settings['contact_email'] ?? ''); ?>" style="color:#fff; text-decoration:none; font-size:1.1em;"><?php echo htmlspecialchars($settings['contact_email'] ?? ''); ?></a>
                        </div>
                    </li>
                    <li style="margin-bottom:25px; display:flex; align-items:center;">
                        <span style="background: rgba(255,255,255,0.1); width: 50px; height: 50px; display: flex; align-items: center; justify-content: center; border-radius: 50%; color: #fff;">
                            <?php echo Icons::get('phone', 'width:24px; height:24px;'); ?>
                        </span>
                        <div style="margin-left:20px;">
                            <span style="display:block; color:#888; font-size:0.8em; text-transform:uppercase;">Phone</span>
                            <span style="color:#fff; font-size:1.1em;"><?php echo htmlspecialchars($settings['contact_phone'] ?? ''); ?></span>
                        </div>
                    </li>
                    <li style="display:flex; align-items:center;">
                        <span style="background: rgba(255,255,255,0.1); width: 50px; height: 50px; display: flex; align-items: center; justify-content: center; border-radius: 50%; color: #fff;">
                            <?php echo Icons::get('building-office', 'width:24px; height:24px;'); ?>
                        </span>
                        <div style="margin-left:20px;">
                            <span style="display:block; color:#888; font-size:0.8em; text-transform:uppercase;">Office</span>
                            <span style="color:#fff; font-size:1.1em;"><?php echo htmlspecialchars($settings['contact_address'] ?? ''); ?></span>
                        </div>
                    </li>
                </ul>
            </div>

            <!-- Simple Form -->
            <form style="margin:0; max-width:100%;">
                <label>Your Name</label>
                <input type="text" placeholder="John Doe">
                <label>Message</label>
                <textarea rows="4" placeholder="How can we help?"></textarea>
                <button type="button">Send Message</button>
            </form>
        </div>
    </main>
    <?php include '../includes/footer.php'; ?>
</body>
</html>
