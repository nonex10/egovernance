<?php
require 'config/db.php'; require_user();
$uid = $_SESSION['user_id'];
$st = $pdo->prepare("SELECT a.*, s.name AS service FROM applications a JOIN service_types s ON s.id=a.service_type_id WHERE a.user_id=? ORDER BY a.created_at DESC");
$st->execute([$uid]); $apps = $st->fetchAll();
$n = ['all'=>count($apps),'pending'=>0,'delivered'=>0,'due'=>0];
foreach ($apps as $a) {
    if (in_array($a['status'], ['submitted','under_review','approved'])) $n['pending']++;
    if ($a['status'] === 'delivered') $n['delivered']++;
    if ($a['payment_status'] === 'unpaid' && $a['amount_due'] > 0 && $a['status'] !== 'rejected') $n['due'] += $a['amount_due'];
}
head('My Services', 'user', 'Hi, ' . e($_SESSION['user_name']) . ' <a href="apply.php">+ New application</a><a href="profile.php">Profile</a><a href="logout.php">Logout</a>');
?>
<div class="stats"><div><b><?= $n['all'] ?></b>Applications</div><div><b><?= $n['pending'] ?></b>In progress</div>
<div><b><?= $n['delivered'] ?></b>Delivered</div><div><b><?= money($n['due']) ?></b>Payment due</div></div>
<?php if (!$apps): ?><div class="panel">No applications yet. <a href="apply.php">Start one</a>.</div><?php else: ?>
<table><tr><th>Ref no</th><th>Service</th><th>Status</th><th>Payment</th><th>Submitted</th><th></th></tr>
<?php foreach ($apps as $a): ?>
<tr><td><?= e($a['ref_no']) ?></td><td><?= e($a['service']) ?></td><td><?= badge($a['status']) ?></td>
<td><?= $a['amount_due'] > 0 ? money($a['amount_due']) . ' ' . badge($a['payment_status']) : 'No fee' ?></td>
<td><?= date('d M Y', strtotime($a['created_at'])) ?></td><td><a class="btn gray" href="view.php?id=<?= $a['id'] ?>">Open</a></td></tr>
<?php endforeach; ?></table><?php endif; ?>
<?php foot();
