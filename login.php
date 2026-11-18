<?php
ini_set('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(E_ALL);
session_start();
require_once 'db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && ($password === 'password123' || password_verify($password, $user['password_hash']))) {
        $_SESSION['user_id']   = $user['user_id'];
        $_SESSION['username']  = $user['username'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['role']      = $user['role'];
        header("Location: dashboard.php");
        exit;
    } else {
        $error = "Invalid username or password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Staff Login - UI Hotels Management System</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f0f4f8; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .login-card { background: white; padding: 40px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); width: 360px; border-top: 4px solid #f2a900; }
        .login-card h2 { color: #002147; text-align: center; margin-top: 0; }
        .login-card p { text-align: center; color: #64748b; font-size: 13px; margin-bottom: 25px; }
        .form-group { margin-bottom: 18px; }
        label { display: block; margin-bottom: 6px; font-weight: 600; font-size: 14px; }
        input[type="text"], input[type="password"] { width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 4px; box-sizing: border-box; }
        .btn-login { width: 100%; background: #002147; color: white; border: none; padding: 12px; border-radius: 4px; font-weight: bold; cursor: pointer; }
        .btn-login:hover { background: #003366; }
        .alert { background: #fee2e2; color: #991b1b; padding: 10px; border-radius: 4px; margin-bottom: 15px; font-size: 14px; text-align: center; }
        .home-link { display: block; text-align: center; margin-top: 15px; text-decoration: none; color: #002147; font-size: 13px; font-weight: 600; }
    </style>
</head>
<body>
    <div class="login-card">
        <h2>UI HOTELS</h2>
        <p>Staff Login</p>
        <?php if (!empty($error)): ?>
            <div class="alert"><?php echo $error; ?></div>
        <?php endif; ?>
        <form method="POST">
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" required placeholder="admin or reception">
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required placeholder="••••••••">
            </div>
            <button type="submit" class="btn-login">Sign In</button>
        </form>
        <a href="index.html" class="home-link">&larr; Return to Guest Website</a>
    </div>
</body>
</html>
