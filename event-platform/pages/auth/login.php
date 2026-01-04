<?php
session_start();
require_once '../../config/database.php';

$error = '';

if (isset($_SESSION['success'])) {
    $success_msg = $_SESSION['success'];
    unset($_SESSION['success']);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $error = "Please enter email and password.";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_role'] = $user['role'];
            
            if ($user['role'] == 'admin') {
                header("Location: /pages/admin/dashboard.php");
            } elseif ($user['role'] == 'organizer') {
                header("Location: /pages/organizer/dashboard.php");
            } else {
                header("Location: /index.php");
            }
            exit();
        } else {
            $error = "Invalid email or password.";
        }
    }
}
?>
<?php require_once '../../includes/header.php'; ?>
<main>
    <div style="max-width: 450px; margin: 40px auto;">
        <div class="glass-card" style="padding: 40px;">
            <div style="text-align: center; margin-bottom: 30px;">
                <h2 style="font-size: 2rem; margin-bottom: 10px; color: #fff;">Welcome Back</h2>
                <p style="color: var(--text-muted);">Please enter your details to sign in</p>
            </div>

            <?php if(isset($success_msg)): ?>
                <div style="background: rgba(52, 211, 153, 0.1); border: 1px solid rgba(52, 211, 153, 0.2); color: var(--success); padding: 12px; border-radius: 8px; margin-bottom: 20px; text-align: center;">
                    <?php echo htmlspecialchars($success_msg); ?>
                </div>
            <?php endif; ?>

            <?php if($error): ?>
                <div style="background: rgba(248, 113, 113, 0.1); border: 1px solid rgba(248, 113, 113, 0.2); color: var(--danger); padding: 12px; border-radius: 8px; margin-bottom: 20px; text-align: center;">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="post" action="" class="modern-form">
                <div style="margin-bottom: 20px;">
                    <label style="display: block; color: var(--text-muted); margin-bottom: 8px; font-size: 0.9rem;">Email Address</label>
                    <input type="email" name="email" required placeholder="name@example.com" style="width: 100%; padding: 12px; background: rgba(255,255,255,0.05); border: 1px solid var(--border); border-radius: 10px; color: #fff;">
                </div>
                
                <div style="margin-bottom: 30px;">
                    <label style="display: block; color: var(--text-muted); margin-bottom: 8px; font-size: 0.9rem;">Password</label>
                    <input type="password" name="password" required placeholder="••••••••" style="width: 100%; padding: 12px; background: rgba(255,255,255,0.05); border: 1px solid var(--border); border-radius: 10px; color: #fff;">
                </div>

                <button type="submit" class="hero-btn" style="width: 100%; padding: 14px; border: none; font-size: 1rem;">Sign In</button>
            </form>

            <div style="text-align: center; margin-top: 25px; color: var(--text-muted); font-size: 0.9rem;">
                Don't have an account? <a href="register.php" style="color: var(--primary-light); text-decoration: none; font-weight: 600;">Sign up</a>
            </div>
        </div>
    </div>
</main>
<?php require_once '../../includes/footer.php'; ?>
