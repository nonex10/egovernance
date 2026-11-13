<?php
require 'config/db.php'; require_user();
$msg = ''; $errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $age = (int)($_POST['age'] ?? 0); $g = $_POST['gender'] ?? ''; $pr = $_POST['province'] ?? ''; $d = trim($_POST['district'] ?? '');
    if ($age < 1 || $age > 120) $errors[] = 'Enter a valid age.';
    if (!in_array($g, ['male','female','other']) || !in_array($pr, PROVINCES)) $errors[] = 'Invalid gender/province.';
    if (!$errors) {
        $pdo->prepare("UPDATE users SET age=?,gender=?,province=?,district=? WHERE id=?")->execute([$age,$g,$pr,$d,$_SESSION['user_id']]);
        $msg = 'Target group updated. Your alerts will now match these attributes.';
    }
}
$st = $pdo->prepare("SELECT * FROM users WHERE id=?"); $st->execute([$_SESSION['user_id']]); $u = $st->fetch();
?>
<!DOCTYPE html><html><head><meta charset="utf-8"><title>My Target Group</title><link rel="stylesheet" href="assets/style.css"></head><body>
<header class="user"><strong>My Target Group</strong><nav><a href="dashboard.php">Back to alerts</a></nav></header>
<main><div class="box">
<?php if ($msg): ?><div class="ok"><?= e($msg) ?></div><?php endif; ?>
<?php foreach ($errors as $er): ?><div class="err"><?= e($er) ?></div><?php endforeach; ?>
<form method="post"><input type="hidden" name="csrf" value="<?= csrf_token() ?>">
<div class="row"><div><label>Age</label><input type="number" name="age" value="<?= e($u['age']) ?>" required></div>
<div><label>Gender</label><select name="gender"><?php foreach (['male','female','other'] as $g): ?><option <?= $u['gender']===$g?'selected':'' ?>><?= $g ?></option><?php endforeach; ?></select></div></div>
<div class="row"><div><label>Province</label><select name="province"><?php foreach (PROVINCES as $p): ?><option <?= $u['province']===$p?'selected':'' ?>><?= $p ?></option><?php endforeach; ?></select></div>
<div><label>District</label><input name="district" value="<?= e($u['district']) ?>"></div></div>
<p><button class="btn">Save</button></p></form></div></main></body></html>
