<?php
require '../config/db.php'; require_admin();
$rows = $pdo->query("SELECT r.*, c.name AS category FROM reports r JOIN categories c ON c.id=r.category_id ORDER BY r.created_at DESC")->fetchAll();

function reach($pdo, $r) {
    $st = $pdo->prepare("SELECT COUNT(*) FROM users WHERE (? IS NULL OR age >= ?) AND (? IS NULL OR age <= ?)
        AND (? = 'all' OR gender = ?) AND (? = 'All' OR province = ?) AND (? = '' OR LOWER(district) = LOWER(?))");
    $st->execute([$r['target_min_age'],$r['target_min_age'],$r['target_max_age'],$r['target_max_age'],
        $r['target_gender'],$r['target_gender'],$r['target_province'],$r['target_province'],$r['target_district'],$r['target_district']]);
    return (int)$st->fetchColumn();
}
function target_text($r) {
    $a = ($r['target_min_age'] !== null || $r['target_max_age'] !== null)
        ? 'Age ' . ($r['target_min_age'] ?? '0') . '-' . ($r['target_max_age'] ?? '+') : 'All ages';
    return $a . ' | ' . ucfirst($r['target_gender']) . ' | ' . $r['target_province'] . ($r['target_district'] ? ' / ' . $r['target_district'] : '');
}
$totalUsers = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$pub = (int)$pdo->query("SELECT COUNT(*) FROM reports WHERE status='published'")->fetchColumn();
?>
<!DOCTYPE html><html><head><meta charset="utf-8"><title>Admin Dashboard</title><link rel="stylesheet" href="../assets/style.css"></head><body>
<header><strong>Government Dashboard: Targeted Reports</strong><nav>Hi, <?= e($_SESSION['admin_name']) ?>
<a href="users.php">Citizens</a><a href="logout.php">Logout</a></nav></header>
<main>
<div class="stats"><div><b><?= $totalUsers ?></b>Registered citizens</div><div><b><?= count($rows) ?></b>Total reports</div><div><b><?= $pub ?></b>Published</div></div>
<p><a class="btn" href="form.php">+ New Targeted Report</a></p>
<table><tr><th>Title</th><th>Category</th><th>Severity</th><th>Target group</th><th>Reach</th><th>Status</th><th>Actions</th></tr>
<?php foreach ($rows as $r): ?>
<tr><td><?= e($r['title']) ?></td><td><?= e($r['category']) ?></td><td><?= e($r['severity']) ?></td>
<td><?= e(target_text($r)) ?></td><td><?= reach($pdo, $r) ?> / <?= $totalUsers ?></td><td><?= e($r['status']) ?></td>
<td><a class="btn gray" href="form.php?id=<?= $r['id'] ?>">Edit</a>
<form method="post" action="delete.php" style="display:inline" onsubmit="return confirm('Delete this report?')">
<input type="hidden" name="csrf" value="<?= csrf_token() ?>"><input type="hidden" name="id" value="<?= $r['id'] ?>">
<button class="btn red">Delete</button></form></td></tr>
<?php endforeach; ?></table>
</main></body></html>
