<?php
require 'config/db.php';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $st = $pdo->prepare("SELECT * FROM users WHERE email=?"); $st->execute([trim($_POST['email'] ?? '')]);
    $u = $st->fetch();
    if ($u && password_verify($_POST['password'] ?? '', $u['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $u['id']; $_SESSION['user_name'] = $u['full_name'];
        header('Location: dashboard.php'); exit;
    }
    $error = 'Invalid email or password.';
}
head('Citizen Login', 'user', '<a href="register.php">Register</a><a href="index.php">Home</a>');
?>
<div class="box">
<?php if (isset($_GET['registered'])): ?><div class="ok">Account created. Please login.</div><?php endif; ?>
<?php if ($error): ?><div class="err"><?= e($error) ?></div><?php endif; ?>
<form method="post"><?= csrf_field() ?>
<label>Email</label><input type="email" name="email" required>
<label>Password</label><input type="password" name="password" required>
<p><button class="btn green">Login</button></p></form></div>
<?php foot();
