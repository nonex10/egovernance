<?php
require '../config/db.php'; require_admin(); require_perm('service.manage');
$id = (int)($_GET['id'] ?? 0);
$s = ['name'=>'','description'=>'','hint'=>'','fee'=>'0','needs_amount'=>0,'is_active'=>1];
if ($id) { $st = $pdo->prepare("SELECT * FROM service_types WHERE id=?"); $st->execute([$id]); $s = $st->fetch() ?: die('Not found'); }
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $d = [trim($_POST['name'] ?? ''), trim($_POST['description'] ?? ''), trim($_POST['hint'] ?? ''), max(0, (float)($_POST['fee'] ?? 0)),
          isset($_POST['needs_amount']) ? 1 : 0, isset($_POST['is_active']) ? 1 : 0];
    if ($d[0] === '') $errors[] = 'Service name is required.';
    if (!$errors) {
        $dup = $pdo->prepare("SELECT id FROM service_types WHERE name=? AND id<>?"); $dup->execute([$d[0], $id]);
        if ($dup->fetch()) $errors[] = 'A service with this name already exists.';
    }
    if (!$errors) {
        if ($id) { $pdo->prepare("UPDATE service_types SET name=?,description=?,hint=?,fee=?,needs_amount=?,is_active=? WHERE id=?")->execute([...$d, $id]); audit('service_update', 'service#' . $id, $d[0]); }
        else { $pdo->prepare("INSERT INTO service_types (name,description,hint,fee,needs_amount,is_active) VALUES (?,?,?,?,?,?)")->execute($d); audit('service_create', 'service#' . $pdo->lastInsertId(), $d[0]); }
        header('Location: services.php'); exit;
    }
    $s = array_merge($s, ['name'=>$d[0],'description'=>$d[1],'hint'=>$d[2],'fee'=>$d[3],'needs_amount'=>$d[4],'is_active'=>$d[5]]);
}
head($id ? 'Edit Service' : 'New Service', '', '<a href="services.php">Back</a>', '../');
?>
<div class="box">
<?php foreach ($errors as $er): ?><div class="err"><?= e($er) ?></div><?php endforeach; ?>
<form method="post"><?= csrf_field() ?>
<label>Name</label><input name="name" value="<?= e($s['name']) ?>" required>
<label>Description</label><input name="description" value="<?= e($s['description']) ?>">
<label>What the citizen should include</label><input name="hint" value="<?= e($s['hint']) ?>">
<label>Fixed fee (Rs.)</label><input type="number" step="0.01" min="0" name="fee" value="<?= e($s['fee']) ?>">
<label><input type="checkbox" name="needs_amount" <?= $s['needs_amount'] ? 'checked' : '' ?>> Citizen enters the amount (e.g. tax payment)</label>
<label><input type="checkbox" name="is_active" <?= $s['is_active'] ? 'checked' : '' ?>> Active (visible to citizens)</label>
<p><button class="btn">Save</button></p></form></div>
<?php foot();
