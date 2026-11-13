<?php
require '../config/db.php'; require_admin();
$rows = $pdo->query("SELECT m.*, c.name AS category FROM messages m JOIN categories c ON c.id=m.category_id ORDER BY m.created_at DESC")->fetchAll();
$count = fn($s) => (int)$pdo->query("SELECT COUNT(*) FROM messages WHERE status='$s'")->fetchColumn();
?>
<!DOCTYPE html><html><head><meta charset="utf-8"><title>Admin Dashboard</title><link rel="stylesheet" href="../assets/style.css"></head><body>
<header><strong>Broadcaster Dashboard</strong><nav>Hi, <?= e($_SESSION['admin_name']) ?>
<a href="../index.php">Citizen View</a><a href="logout.php">Logout</a></nav></header>
<main>
<div class="stats"><div><b><?= $count('published') ?></b>Published</div><div><b><?= $count('draft') ?></b>Drafts</div><div><b><?= $count('archived') ?></b>Archived</div></div>
<p><a class="btn" href="form.php">+ New Message</a></p>
<table><tr><th>Title</th><th>Category</th><th>Priority</th><th>Status</th><th>Publish</th><th>Actions</th></tr>
<?php foreach ($rows as $r): ?>
<tr><td><?= e($r['title']) ?></td><td><?= e($r['category']) ?></td><td><?= e($r['priority']) ?></td><td><?= e($r['status']) ?></td>
<td><?= e($r['publish_date']) ?></td>
<td><a class="btn gray" href="form.php?id=<?= $r['id'] ?>">Edit</a>
<form method="post" action="delete.php" style="display:inline" onsubmit="return confirm('Delete this message?')">
<input type="hidden" name="csrf" value="<?= csrf_token() ?>"><input type="hidden" name="id" value="<?= $r['id'] ?>">
<button class="btn red">Delete</button></form></td></tr>
<?php endforeach; ?></table>
</main></body></html>
