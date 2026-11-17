<?php
require '../config/db.php';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    [$a, $error] = attempt_login('staff', $_POST['username'] ?? '', $_POST['password'] ?? '');
    if ($a) { start_login('staff', $a); header('Location: index.php'); exit; }
}
head('Government Login', '', '<a href="../index.php">Citizen Portal</a>', '../');
?>
<div class="box">
<?= login_notice() ?>
<?php if ($error): ?><div class="err"><?= e($error) ?></div><?php endif; ?>
<form method="post"><?= csrf_field() ?>
<label>Username</label><input name="username" required>
<label>Password</label><input type="password" name="password" required>
<p><button class="btn">Login</button></p></form></div>
<?php foot();
