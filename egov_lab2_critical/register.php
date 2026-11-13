<?php
require 'config/db.php';
$errors = []; $v = ['full_name'=>'','email'=>'','age'=>'','gender'=>'male','province'=>'Bagmati','district'=>''];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    foreach ($v as $k => $_) $v[$k] = trim($_POST[$k] ?? '');
    if ($v['full_name'] === '') $errors[] = 'Name is required.';
    if (!filter_var($v['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required.';
    if (strlen($_POST['password'] ?? '') < 6) $errors[] = 'Password must be at least 6 characters.';
    if ((int)$v['age'] < 1 || (int)$v['age'] > 120) $errors[] = 'Enter a valid age.';
    if (!in_array($v['gender'], ['male','female','other']) || !in_array($v['province'], PROVINCES)) $errors[] = 'Invalid gender/province.';
    if (!$errors) {
        $chk = $pdo->prepare("SELECT id FROM users WHERE email=?"); $chk->execute([$v['email']]);
        if ($chk->fetch()) $errors[] = 'Email already registered.';
    }
    if (!$errors) {
        $pdo->prepare("INSERT INTO users (full_name,email,password_hash,age,gender,province,district) VALUES (?,?,?,?,?,?,?)")
            ->execute([$v['full_name'],$v['email'],password_hash($_POST['password'],PASSWORD_DEFAULT),(int)$v['age'],$v['gender'],$v['province'],$v['district']]);
        header('Location: login.php'); exit;
    }
}
?>
<!DOCTYPE html><html><head><meta charset="utf-8"><title>Register</title><link rel="stylesheet" href="assets/style.css"></head><body>
<header class="user"><strong>Create Citizen Account</strong><nav><a href="login.php">Login</a></nav></header>
<main><div class="box">
<?php foreach ($errors as $er): ?><div class="err"><?= e($er) ?></div><?php endforeach; ?>
<form method="post"><input type="hidden" name="csrf" value="<?= csrf_token() ?>">
<label>Full name</label><input name="full_name" value="<?= e($v['full_name']) ?>" required>
<label>Email</label><input type="email" name="email" value="<?= e($v['email']) ?>" required>
<label>Password</label><input type="password" name="password" required>
<h3>Your target-group attributes</h3>
<div class="row"><div><label>Age</label><input type="number" name="age" value="<?= e($v['age']) ?>" required></div>
<div><label>Gender</label><select name="gender"><?php foreach (['male','female','other'] as $g): ?><option <?= $v['gender']===$g?'selected':'' ?>><?= $g ?></option><?php endforeach; ?></select></div></div>
<div class="row"><div><label>Province</label><select name="province"><?php foreach (PROVINCES as $p): ?><option <?= $v['province']===$p?'selected':'' ?>><?= $p ?></option><?php endforeach; ?></select></div>
<div><label>District</label><input name="district" value="<?= e($v['district']) ?>"></div></div>
<p><button class="btn">Register</button></p></form></div></main></body></html>
