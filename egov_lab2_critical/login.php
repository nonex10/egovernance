<?php
require 'config/db.php';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $st = $pdo->prepare("SELECT * FROM users WHERE email=?");
    $st->execute([trim($_POST['email'] ?? '')]);
    $u = $st->fetch();
    if ($u && password_verify($_POST['password'] ?? '', $u['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $u['id']; $_SESSION['user_name'] = $u['full_name'];
        header('Location: dashboard.php'); exit;
    }
    $error = 'Invalid email or password.';
}
?>
<!DOCTYPE html><html><head><meta charset="utf-8"><title>Citizen Login</title><link rel="stylesheet" href="assets/style.css"></head><body>
<header class="user"><strong>Critical Information Portal</strong><nav><a href="admin/login.php">Admin Login</a></nav></header>
<main><div class="box"><h2>Citizen Login</h2>
<?php if ($error): ?><div class="err"><?= e($error) ?></div><?php endif; ?>
<form method="post"><input type="hidden" name="csrf" value="<?= csrf_token() ?>">
<label>Email</label><input type="email" name="email" required>
<label>Password</label><input type="password" name="password" required>
<p><button class="btn">Login</button> <a href="register.php">Create account</a></p></form>
<p class="meta">Demo: ram@example.com / citizen123</p></div></main></body></html>
