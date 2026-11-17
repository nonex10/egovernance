<?php
require '../config/db.php'; require_admin();
$rows = $pdo->query("SELECT u.id,u.full_name,u.email,u.phone,u.district,u.created_at,(SELECT COUNT(*) FROM applications a WHERE a.user_id=u.id) AS apps FROM users u ORDER BY u.id")->fetchAll();
head('Registered Citizens', '', '<a href="index.php">Back</a>', '../');
?>
<table><tr><th>Name</th><th>Email</th><th>Phone</th><th>District</th><th>Requests</th><th>Joined</th></tr>
<?php foreach ($rows as $u): ?>
<tr><td><?= e($u['full_name']) ?></td><td><?= e($u['email']) ?></td><td><?= e($u['phone']) ?></td><td><?= e($u['district']) ?></td>
<td><?= (int)$u['apps'] ?></td><td><?= date('d M Y', strtotime($u['created_at'])) ?></td></tr>
<?php endforeach; ?></table>
<?php foot();
