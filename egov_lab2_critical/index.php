<?php
require 'config/db.php';
$services = $pdo->query("SELECT * FROM service_types WHERE is_active=1 ORDER BY name")->fetchAll();
head('e-Governance G2C2G Portal', 'user', '<a href="login.php">Citizen Login</a><a href="register.php">Register</a><a href="admin/login.php">Government Login</a>');
?>
<div class="hero"><h1>Government services, online</h1>
<p>Apply, pay and track your government services. Officers review, approve and deliver them digitally.</p>
<a class="btn green" href="register.php">Create account</a> <a class="btn" href="login.php">Login</a></div>
<h3>Available services</h3>
<div class="svc"><?php foreach ($services as $s): ?>
<div><b><?= e($s['name']) ?></b><p class="meta"><?= e($s['description']) ?></p>
<span class="badge"><?= $s['needs_amount'] ? 'Amount as assessed' : ($s['fee'] > 0 ? 'Fee ' . money($s['fee']) : 'Free') ?></span></div>
<?php endforeach; ?></div>
<?php foot();
