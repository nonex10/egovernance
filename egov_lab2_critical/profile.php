<?php
require 'config/db.php'; require_user();
$msg = ''; $errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $name = trim($_POST['full_name'] ?? ''); $ph = trim($_POST['phone'] ?? ''); $ad = trim($_POST['address'] ?? ''); $di = trim($_POST['district'] ?? '');
    if ($name === '') $errors[] = 'Name is required.';
    if ($ph !== '' && !preg_match('/^[0-9+\- ]{7,20}$/', $ph)) $errors[] = 'Invalid phone number.';
    if (!$errors) {
        $pdo->prepare("UPDATE users SET full_name=?,phone=?,address=?,district=? WHERE id=?")->execute([$name,$ph,$ad,$di,$_SESSION['user_id']]);
        $_SESSION['user_name'] = $name; $msg = 'Profile updated.';
    }
}
$st = $pdo->prepare("SELECT * FROM users WHERE id=?"); $st->execute([$_SESSION['user_id']]); $u = $st->fetch();
head('My Profile', 'user', '<a href="dashboard.php">Back</a>');
?>
<div class="box">
<?php if ($msg): ?><div class="ok"><?= e($msg) ?></div><?php endif; ?>
<?php foreach ($errors as $er): ?><div class="err"><?= e($er) ?></div><?php endforeach; ?>
<form method="post"><?= csrf_field() ?>
<label>Email (cannot be changed)</label><input value="<?= e($u['email']) ?>" disabled>
<label>Full name</label><input name="full_name" value="<?= e($u['full_name']) ?>" required>
<label>Phone</label><input name="phone" value="<?= e($u['phone']) ?>">
<label>Address</label><input name="address" value="<?= e($u['address']) ?>">
<label>District</label><input name="district" value="<?= e($u['district']) ?>">
<p><button class="btn green">Save</button></p></form></div>
<?php foot();
