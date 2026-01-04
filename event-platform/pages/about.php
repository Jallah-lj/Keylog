<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>About Us - Event Platform</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body style="display: flex; flex-direction: column; min-height: 100vh;">
    <header>
        <h1>About Us</h1>
        <nav>
            <a href="/index.php">Home</a>
        </nav>
    </header>
    <main>
        <div style="text-align:center; padding: 40px 0;">
            <h2 style="font-size: 3em; margin-bottom: 20px; background: linear-gradient(to right, #818cf8, #e879f9); -webkit-background-clip: text; color: transparent;">About Us</h2>
            <p style="font-size: 1.2em; color: #cbd5e1; max-width: 700px; margin: 0 auto;">Building connections through unforgettable events.</p>
        </div>

        <div style="background: var(--card-bg); backdrop-filter: blur(10px); padding:40px; border-radius:16px; border: 1px solid var(--border); box-shadow: var(--glass-shadow);">
            <h3 style="color:var(--primary); font-size: 1.5em; border-bottom: 2px solid var(--border); padding-bottom: 15px; margin-bottom: 20px;">Our Mission</h3>
            <p style="margin-bottom: 30px; font-size: 1.05em; line-height: 1.8; color: #e2e8f0;">
                We are dedicated to bringing people together through amazing live experiences. Whether it's a concert, a tech conference, or a local workshop, our platform makes it easy to create, discover, and attend events.
                We believe in the power of shared moments and seamless technology.
            </p>
            
            <h3 style="color:var(--primary); font-size: 1.5em; border-bottom: 2px solid var(--border); padding-bottom: 15px; margin-bottom: 20px;">The Team</h3>
            <p style="font-size: 1.05em; line-height: 1.8; color: #e2e8f0;">
                Founded in 2025, we are a passionate team of developers, designers, and event enthusiasts working hard to build the future of ticketing. We prioritize user experience, security, and beautiful design.
            </p>
        </div>
    </main>
    <?php include '../includes/footer.php'; ?>
</body>
</html>
