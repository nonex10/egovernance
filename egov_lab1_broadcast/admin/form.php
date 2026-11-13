<?php
require '../config/db.php'; require_admin();
$id = (int)($_GET['id'] ?? 0);
$m = ['title'=>'','body'=>'','category_id'=>'','priority'=>'normal','status'=>'draft','publish_date'=>date('Y-m-d\TH:i'),'expiry_date'=>''];
if ($id) {
    $st = $pdo->prepare("SELECT * FROM messages WHERE id=?"); $st->execute([$id]);
    $m = $st->fetch() ?: die('Not found');
    $m['publish_date'] = date('Y-m-d\TH:i', strtotime($m['publish_date']));
}
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $d = [
        trim($_POST['title'] ?? ''), trim($_POST['body'] ?? ''), (int)($_POST['category_id'] ?? 0),
        $_POST['priority'] ?? 'normal', $_POST['status'] ?? 'draft',
        str_replace('T', ' ', $_POST['publish_date'] ?? ''), ($_POST['expiry_date'] ?? '') ?: null,
    ];
    if ($d[0] === '' || $d[1] === '') $errors[] = 'Title and message are required.';
    if (!in_array($d[3], ['normal','important','urgent']) || !in_array($d[4], ['draft','published','archived'])) $errors[] = 'Invalid priority/status.';
    if (!$errors) {
        if ($id) {
            $pdo->prepare("UPDATE messages SET title=?,body=?,category_id=?,priority=?,status=?,publish_date=?,expiry_date=? WHERE id=?")
                ->execute([...$d, $id]);
        } else {
            $pdo->prepare("INSERT INTO messages (title,body,category_id,priority,status,publish_date,expiry_date,created_by) VALUES (?,?,?,?,?,?,?,?)")
                ->execute([...$d, $_SESSION['admin_id']]);
        }
        header('Location: index.php'); exit;
    }
    $m = array_merge($m, $_POST);
}
$cats = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();
?>
<!DOCTYPE html><html><head><meta charset="utf-8"><title><?= $id ? 'Edit' : 'New' ?> Message</title><link rel="stylesheet" href="../assets/style.css"></head><body>
<header><strong><?= $id ? 'Edit' : 'Create' ?> Broadcast Message</strong><nav><a href="index.php">Back</a></nav></header>
<main><div class="box">
<?php foreach ($errors as $er): ?><div class="err"><?= e($er) ?></div><?php endforeach; ?>
<form method="post"><input type="hidden" name="csrf" value="<?= csrf_token() ?>">
<label>Title</label><input name="title" value="<?= e($m['title']) ?>" required>
<label>Message</label><textarea name="body" rows="6" required><?= e($m['body']) ?></textarea>
<label>Category</label><select name="category_id">
<?php foreach ($cats as $c): ?><option value="<?= $c['id'] ?>" <?= $m['category_id']==$c['id']?'selected':'' ?>><?= e($c['name']) ?></option><?php endforeach; ?></select>
<label>Priority</label><select name="priority"><?php foreach (['normal','important','urgent'] as $p): ?><option <?= $m['priority']===$p?'selected':'' ?>><?= $p ?></option><?php endforeach; ?></select>
<label>Status</label><select name="status"><?php foreach (['draft','published','archived'] as $s): ?><option <?= $m['status']===$s?'selected':'' ?>><?= $s ?></option><?php endforeach; ?></select>
<label>Publish date/time</label><input type="datetime-local" name="publish_date" value="<?= e($m['publish_date']) ?>" required>
<label>Expiry date (optional)</label><input type="date" name="expiry_date" value="<?= e($m['expiry_date']) ?>">
<p><button class="btn">Save</button></p>
</form></div></main></body></html>
