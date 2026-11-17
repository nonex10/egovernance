<?php
require '../config/db.php';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $st = $pdo->prepare("SELECT * FROM admins WHERE username=?"); $st->execute([trim($_POST['username'] ?? '')]);
    $a = $st->fetch();
    if ($a && password_verify($_POST['password'] ?? '', $a['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['admin_id'] = $a['id']; $_SESSION['admin_name'] = $a['full_name'];
        header('Location: index.php'); exit;
    }
    $error = 'Invalid username or password.';
}
head('Government Login', '', '<a href="../index.php">Citizen Portal</a>', '../');
?>
<div class="box">
<?php if ($error): ?><div class="err"><?= e($error) ?></div><?php endif; ?>
<form method="post"><?= csrf_field() ?>
<label>Username</label><input name="username" required>
<label>Password</label><input type="password" name="password" required>
<p><button class="btn">Login</button></p></form></div>
<?php foot();
