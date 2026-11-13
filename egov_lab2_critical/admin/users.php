<?php
require '../config/db.php'; require_admin();
$users = $pdo->query("SELECT id,full_name,email,age,gender,province,district FROM users ORDER BY id")->fetchAll();
?>
<!DOCTYPE html><html><head><meta charset="utf-8"><title>Citizens</title><link rel="stylesheet" href="../assets/style.css"></head><body>
<header><strong>Registered Citizens (target audience)</strong><nav><a href="index.php">Back</a></nav></header>
<main><table><tr><th>Name</th><th>Email</th><th>Age</th><th>Gender</th><th>Province</th><th>District</th></tr>
<?php foreach ($users as $u): ?><tr><td><?= e($u['full_name']) ?></td><td><?= e($u['email']) ?></td><td><?= (int)$u['age'] ?></td>
<td><?= e($u['gender']) ?></td><td><?= e($u['province']) ?></td><td><?= e($u['district']) ?></td></tr><?php endforeach; ?></table></main></body></html>
