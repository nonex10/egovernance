<?php
require 'config/db.php';
$errors = []; $v = ['full_name'=>'','email'=>'','phone'=>'','address'=>'','district'=>''];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    foreach ($v as $k => $_) $v[$k] = trim($_POST[$k] ?? '');
    if ($v['full_name'] === '') $errors[] = 'Name is required.';
    if (!filter_var($v['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required.';
    if ($pe = password_policy($_POST['password'] ?? '')) $errors[] = $pe;
    if ($v['phone'] !== '' && !preg_match('/^[0-9+\- ]{7,20}$/', $v['phone'])) $errors[] = 'Invalid phone number.';
    if (!$errors) {
        $c = $pdo->prepare("SELECT id FROM users WHERE email=?"); $c->execute([$v['email']]);
        if ($c->fetch()) $errors[] = 'Email already registered.';
    }
    if (!$errors) {
        $pdo->prepare("INSERT INTO users (full_name,email,password_hash,phone,address,district) VALUES (?,?,?,?,?,?)")
            ->execute([$v['full_name'],$v['email'],password_hash($_POST['password'],PASSWORD_DEFAULT),$v['phone'],$v['address'],$v['district']]);
        $nid = $pdo->lastInsertId();
        audit('register', 'user#' . $nid, '', 'citizen', $nid, $v['email']);
        header('Location: login.php?registered=1'); exit;
    }
}
head('Create Citizen Account', 'user', '<a href="login.php">Login</a>');
?>
<div class="box">
<?php foreach ($errors as $er): ?><div class="err"><?= e($er) ?></div><?php endforeach; ?>
<form method="post"><?= csrf_field() ?>
<label>Full name</label><input name="full_name" value="<?= e($v['full_name']) ?>" required>
<label>Email</label><input type="email" name="email" value="<?= e($v['email']) ?>" required>
<label>Password</label><input type="password" name="password" required>
<label>Phone</label><input name="phone" value="<?= e($v['phone']) ?>">
<label>Address</label><input name="address" value="<?= e($v['address']) ?>">
<label>District</label><input name="district" value="<?= e($v['district']) ?>">
<p><button class="btn green">Register</button></p></form></div>
<?php foot();
