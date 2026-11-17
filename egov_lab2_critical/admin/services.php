<?php
require '../config/db.php'; require_admin();
$rows = $pdo->query("SELECT s.*, (SELECT COUNT(*) FROM applications a WHERE a.service_type_id=s.id) AS used FROM service_types s ORDER BY s.name")->fetchAll();
head('Manage Services', '', '<a href="index.php">Back</a>', '../');
?>
<p><a class="btn" href="service_form.php">+ New service</a></p>
<table><tr><th>Name</th><th>Fee</th><th>Active</th><th>Requests</th><th>Actions</th></tr>
<?php foreach ($rows as $r): ?>
<tr><td><b><?= e($r['name']) ?></b><br><span class="meta"><?= e($r['description']) ?></span></td>
<td><?= $r['needs_amount'] ? 'Citizen enters amount' : ($r['fee'] > 0 ? money($r['fee']) : 'Free') ?></td>
<td><?= $r['is_active'] ? 'Yes' : 'No' ?></td><td><?= (int)$r['used'] ?></td>
<td class="actions"><a class="btn gray" href="service_form.php?id=<?= $r['id'] ?>">Edit</a>
<form method="post" action="service_delete.php" onsubmit="return confirm('Delete this service? If it has requests it will be deactivated instead.')"><?= csrf_field() ?>
<input type="hidden" name="id" value="<?= $r['id'] ?>"><button class="btn red">Delete</button></form></td></tr>
<?php endforeach; ?></table>
<?php foot();
