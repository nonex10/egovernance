<?php
require 'config/db.php'; require_user(); require_perm('application.own');
$st = $pdo->prepare("SELECT a.*, s.name AS service, u.full_name, u.address, u.district FROM applications a
  JOIN service_types s ON s.id=a.service_type_id JOIN users u ON u.id=a.user_id WHERE a.id=? AND a.user_id=?");
$st->execute([(int)($_GET['id'] ?? 0), $_SESSION['user_id']]);
$a = $st->fetch() ?: die('Not found');
$editable = $a['status'] === 'submitted' && $a['payment_status'] === 'unpaid';
$payable  = $a['amount_due'] > 0 && $a['payment_status'] === 'unpaid' && $a['status'] !== 'rejected';
head('Application ' . $a['ref_no'], 'user', '<a href="dashboard.php">Back</a>');
?>
<?php if (isset($_GET['paid'])): ?><div class="ok">Payment recorded. Thank you.</div><?php endif; ?>
<div class="panel">
<h3 style="margin-top:0"><?= e($a['service']) ?> <?= badge($a['status']) ?></h3>
<p class="meta">Submitted <?= date('d M Y, h:i A', strtotime($a['created_at'])) ?></p>
<p><?= nl2br(e($a['details'])) ?></p>
<p><b>Amount:</b> <?= $a['amount_due'] > 0 ? money($a['amount_due']) . ' ' . badge($a['payment_status']) : 'No fee' ?>
<?= $a['paid_at'] ? '<span class="meta"> (paid ' . date('d M Y', strtotime($a['paid_at'])) . ')</span>' : '' ?></p>
<?php if ($a['admin_remarks']): ?><p><b>Officer remarks:</b> <?= nl2br(e($a['admin_remarks'])) ?></p><?php endif; ?>
<div class="actions">
<?php if ($payable): ?><a class="btn green" href="pay.php?id=<?= $a['id'] ?>">Pay now</a><?php endif; ?>
<?php if ($editable): ?><a class="btn amber" href="apply.php?id=<?= $a['id'] ?>">Edit</a>
<form method="post" action="cancel.php" onsubmit="return confirm('Delete this application?')"><?= csrf_field() ?>
<input type="hidden" name="id" value="<?= $a['id'] ?>"><button class="btn red">Delete</button></form><?php endif; ?>
<?php if ($a['status'] === 'delivered'): ?><button class="btn" onclick="window.print()">Print</button><?php endif; ?>
</div></div>
<?php if ($a['status'] === 'delivered'): ?>
<div class="cert"><h2>Government of Nepal: <?= e($a['service']) ?></h2>
<p>This is to certify that the request of <b><?= e($a['full_name']) ?></b> (<?= e($a['address']) ?>, <?= e($a['district']) ?>) has been processed and delivered.</p>
<p><b>Document no:</b> <?= e($a['document_no']) ?> &nbsp; | &nbsp; <b>Reference:</b> <?= e($a['ref_no']) ?></p>
<p><?= e($a['delivery_note']) ?></p>
<p class="meta">Issued on <?= date('d M Y', strtotime($a['delivered_at'])) ?></p></div>
<?php endif; ?>
<?php foot();
