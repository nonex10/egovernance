<?php
require 'config/db.php';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    [$u, $error] = attempt_login('citizen', $_POST['email'] ?? '', $_POST['password'] ?? '');
    if ($u) { start_login('citizen', $u); header('Location: dashboard.php'); exit; }
}
head('Citizen Login', 'user', '<a href="register.php">Register</a><a href="index.php">Home</a>');
?>
<div class="box">
<?= login_notice() ?>
<?php if (isset($_GET['registered'])): ?><div class="ok">Account created. Please login.</div><?php endif; ?>
<?php if ($error): ?><div class="err"><?= e($error) ?></div><?php endif; ?>
<form method="post"><?= csrf_field() ?>
<label>Email</label><input type="email" name="email" required>
<label>Password</label><input type="password" name="password" required>
<p><button class="btn green">Login</button></p></form></div>
<?php foot();
