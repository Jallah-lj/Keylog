<?php
session_start();
require_once '../../config/database.php';

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $role = $_POST['role'];

    if (empty($name) || empty($email) || empty($password)) {
        $error = "All fields are required.";
    } else {
        // Check if email exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->rowCount() > 0) {
            $error = "Email already registered.";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
            if ($stmt->execute([$name, $email, $hashed_password, $role])) {
                $_SESSION['success'] = "Registration successful. Please login.";
                header("Location: login.php");
                exit();
            } else {
                $error = "Something went wrong. Please try again.";
            }
        }
    }
}
?>
<?php require_once '../../includes/header.php'; ?>
<main>
    <div style="max-width: 500px; margin: 40px auto;">
        <div class="glass-card" style="padding: 40px;">
            <div style="text-align: center; margin-bottom: 30px;">
                <h2 style="font-size: 2rem; margin-bottom: 10px; color: #fff;">Join the Platform</h2>
                <p style="color: var(--text-muted);">Create your account to start booking or hosting events</p>
            </div>

            <?php if($error): ?>
                <div style="background: rgba(248, 113, 113, 0.1); border: 1px solid rgba(248, 113, 113, 0.2); color: var(--danger); padding: 12px; border-radius: 8px; margin-bottom: 20px; text-align: center;">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="post" action="" class="modern-form">
                <div style="margin-bottom: 20px;">
                    <label style="display: block; color: var(--text-muted); margin-bottom: 8px; font-size: 0.9rem;">Full Name</label>
                    <input type="text" name="name" required placeholder="John Doe" style="width: 100%; padding: 12px; background: rgba(255,255,255,0.05); border: 1px solid var(--border); border-radius: 10px; color: #fff;">
                </div>

                <div style="margin-bottom: 20px;">
                    <label style="display: block; color: var(--text-muted); margin-bottom: 8px; font-size: 0.9rem;">Email Address</label>
                    <input type="email" name="email" required placeholder="name@example.com" style="width: 100%; padding: 12px; background: rgba(255,255,255,0.05); border: 1px solid var(--border); border-radius: 10px; color: #fff;">
                </div>
                
                <div style="margin-bottom: 20px;">
                    <label style="display: block; color: var(--text-muted); margin-bottom: 8px; font-size: 0.9rem;">Password</label>
                    <input type="password" name="password" required placeholder="••••••••" style="width: 100%; padding: 12px; background: rgba(255,255,255,0.05); border: 1px solid var(--border); border-radius: 10px; color: #fff;">
                </div>

                <div style="margin-bottom: 30px;">
                    <label style="display: block; color: var(--text-muted); margin-bottom: 8px; font-size: 0.9rem;">I want to...</label>
                    <select name="role" style="width: 100%; padding: 12px; background: rgba(15, 23, 42, 0.9); border: 1px solid var(--border); border-radius: 10px; color: #fff; cursor: pointer;">
                        <option value="attendee">Attend Events</option>
                        <option value="organizer">Organize Events</option>
                    </select>
                </div>

                <button type="submit" class="hero-btn" style="width: 100%; padding: 14px; border: none; font-size: 1rem;">Create Account</button>
            </form>

            <div style="text-align: center; margin-top: 25px; color: var(--text-muted); font-size: 0.9rem;">
                Already have an account? <a href="login.php" style="color: var(--primary-light); text-decoration: none; font-weight: 600;">Sign in</a>
            </div>
        </div>
    </div>
</main>
<?php require_once '../../includes/footer.php'; ?>
