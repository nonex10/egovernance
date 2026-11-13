<?php
require '../config/db.php';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $st = $pdo->prepare("SELECT * FROM admins WHERE username=?");
    $st->execute([trim($_POST['username'] ?? '')]);
    $a = $st->fetch();
    if ($a && password_verify($_POST['password'] ?? '', $a['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['admin_id'] = $a['id']; $_SESSION['admin_name'] = $a['full_name'];
        header('Location: index.php'); exit;
    }
    $error = 'Invalid username or password.';
}
?>
<!DOCTYPE html><html><head><meta charset="utf-8"><title>Admin Login</title><link rel="stylesheet" href="../assets/style.css"></head><body>
<header><strong>Government Admin Login</strong><nav><a href="../login.php">Citizen Portal</a></nav></header>
<main><div class="box">
<?php if ($error): ?><div class="err"><?= e($error) ?></div><?php endif; ?>
<form method="post"><input type="hidden" name="csrf" value="<?= csrf_token() ?>">
<label>Username</label><input name="username" required>
<label>Password</label><input type="password" name="password" required>
<p><button class="btn">Login</button></p></form></div></main></body></html>
