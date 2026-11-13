<?php
require '../config/db.php'; require_admin();
$id = (int)($_GET['id'] ?? 0);
$m = ['title'=>'','body'=>'','category_id'=>'','severity'=>'info','target_min_age'=>'','target_max_age'=>'','target_gender'=>'all',
      'target_province'=>'All','target_district'=>'','status'=>'draft','publish_date'=>date('Y-m-d\TH:i'),'expiry_date'=>''];
if ($id) {
    $st = $pdo->prepare("SELECT * FROM reports WHERE id=?"); $st->execute([$id]);
    $m = $st->fetch() ?: die('Not found');
    $m['publish_date'] = date('Y-m-d\TH:i', strtotime($m['publish_date']));
}
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $minA = ($_POST['target_min_age'] ?? '') === '' ? null : (int)$_POST['target_min_age'];
    $maxA = ($_POST['target_max_age'] ?? '') === '' ? null : (int)$_POST['target_max_age'];
    $d = [trim($_POST['title'] ?? ''), trim($_POST['body'] ?? ''), (int)($_POST['category_id'] ?? 0), $_POST['severity'] ?? 'info',
          $minA, $maxA, $_POST['target_gender'] ?? 'all', $_POST['target_province'] ?? 'All', trim($_POST['target_district'] ?? ''),
          $_POST['status'] ?? 'draft', str_replace('T', ' ', $_POST['publish_date'] ?? ''), ($_POST['expiry_date'] ?? '') ?: null];
    if ($d[0] === '' || $d[1] === '') $errors[] = 'Title and report text are required.';
    if ($minA !== null && $maxA !== null && $minA > $maxA) $errors[] = 'Minimum age cannot exceed maximum age.';
    if (!in_array($d[3], ['info','warning','critical']) || !in_array($d[6], ['all','male','female','other'])
        || !in_array($d[9], ['draft','published','archived']) || ($d[7] !== 'All' && !in_array($d[7], PROVINCES))) $errors[] = 'Invalid selection.';
    if (!$errors) {
        if ($id) {
            $pdo->prepare("UPDATE reports SET title=?,body=?,category_id=?,severity=?,target_min_age=?,target_max_age=?,target_gender=?,target_province=?,target_district=?,status=?,publish_date=?,expiry_date=? WHERE id=?")
                ->execute([...$d, $id]);
        } else {
            $pdo->prepare("INSERT INTO reports (title,body,category_id,severity,target_min_age,target_max_age,target_gender,target_province,target_district,status,publish_date,expiry_date,created_by) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)")
                ->execute([...$d, $_SESSION['admin_id']]);
        }
        header('Location: index.php'); exit;
    }
    $m = array_merge($m, $_POST);
}
$cats = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();
function opts($list, $cur) { foreach ($list as $o) echo '<option value="'.e($o).'" '.($cur===$o?'selected':'').'>'.e($o).'</option>'; }
?>
<!DOCTYPE html><html><head><meta charset="utf-8"><title><?= $id ? 'Edit' : 'New' ?> Report</title><link rel="stylesheet" href="../assets/style.css"></head><body>
<header><strong><?= $id ? 'Edit' : 'Create' ?> Targeted Report</strong><nav><a href="index.php">Back</a></nav></header>
<main><div class="box">
<?php foreach ($errors as $er): ?><div class="err"><?= e($er) ?></div><?php endforeach; ?>
<form method="post"><input type="hidden" name="csrf" value="<?= csrf_token() ?>">
<label>Title</label><input name="title" value="<?= e($m['title']) ?>" required>
<label>Report / message</label><textarea name="body" rows="5" required><?= e($m['body']) ?></textarea>
<div class="row"><div><label>Category</label><select name="category_id">
<?php foreach ($cats as $c): ?><option value="<?= $c['id'] ?>" <?= $m['category_id']==$c['id']?'selected':'' ?>><?= e($c['name']) ?></option><?php endforeach; ?></select></div>
<div><label>Severity</label><select name="severity"><?php opts(['info','warning','critical'], $m['severity']); ?></select></div></div>
<h3>Target group (leave blank = everyone)</h3>
<div class="row"><div><label>Min age</label><input type="number" name="target_min_age" value="<?= e($m['target_min_age']) ?>"></div>
<div><label>Max age</label><input type="number" name="target_max_age" value="<?= e($m['target_max_age']) ?>"></div>
<div><label>Gender</label><select name="target_gender"><?php opts(['all','male','female','other'], $m['target_gender']); ?></select></div></div>
<div class="row"><div><label>Province</label><select name="target_province"><?php opts(array_merge(['All'], PROVINCES), $m['target_province']); ?></select></div>
<div><label>District (optional)</label><input name="target_district" value="<?= e($m['target_district']) ?>"></div></div>
<div class="row"><div><label>Status</label><select name="status"><?php opts(['draft','published','archived'], $m['status']); ?></select></div>
<div><label>Publish date/time</label><input type="datetime-local" name="publish_date" value="<?= e($m['publish_date']) ?>" required></div>
<div><label>Expiry date</label><input type="date" name="expiry_date" value="<?= e($m['expiry_date']) ?>"></div></div>
<p><button class="btn">Save</button></p></form></div></main></body></html>
