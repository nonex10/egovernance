<?php
require '../config/db.php'; require_admin(); require_perm('request.view');
$status = $_GET['status'] ?? ''; $q = trim($_GET['q'] ?? '');
$sql = "SELECT a.*, s.name AS service, u.full_name AS citizen FROM applications a
        JOIN service_types s ON s.id=a.service_type_id JOIN users u ON u.id=a.user_id WHERE 1";
$p = [];
if (isset(STATUS_LABEL[$status])) { $sql .= " AND a.status=?"; $p[] = $status; }
if ($q !== '') { $sql .= " AND (a.ref_no LIKE ? OR u.full_name LIKE ?)"; $p[] = "%$q%"; $p[] = "%$q%"; }
$sql .= " ORDER BY FIELD(a.status,'submitted','under_review','approved','rejected','delivered'), a.created_at DESC";
$st = $pdo->prepare($sql); $st->execute($p); $rows = $st->fetchAll();
$c = $pdo->query("SELECT status, COUNT(*) FROM applications GROUP BY status")->fetchAll(PDO::FETCH_KEY_PAIR);
$collected = (float)$pdo->query("SELECT COALESCE(SUM(amount_due),0) FROM applications WHERE payment_status='paid'")->fetchColumn();
head('Government Dashboard: Service Requests', '',
  'Hi, ' . e($_SESSION['admin_name']) . ' (' . e($_SESSION['admin_role']) . ') ' . admin_nav(), '../');
?>
<div class="stats">
<div><b><?= array_sum($c) ?></b>Total requests</div><div><b><?= $c['submitted'] ?? 0 ?></b>New</div>
<div><b><?= ($c['under_review'] ?? 0) + ($c['approved'] ?? 0) ?></b>In progress</div>
<div><b><?= $c['delivered'] ?? 0 ?></b>Delivered</div><div><b><?= money($collected) ?></b>Collected</div></div>
<form class="filter" method="get"><select name="status"><option value="">All statuses</option>
<?php foreach (STATUS_LABEL as $k => $l): ?><option value="<?= $k ?>" <?= $status === $k ? 'selected' : '' ?>><?= $l ?></option><?php endforeach; ?></select>
<input name="q" placeholder="Reference or citizen name" value="<?= e($q) ?>"><button class="btn">Filter</button></form>
<table><tr><th>Ref no</th><th>Citizen</th><th>Service</th><th>Status</th><th>Payment</th><th>Actions</th></tr>
<?php foreach ($rows as $r): ?>
<tr><td><?= e($r['ref_no']) ?></td><td><?= e($r['citizen']) ?></td><td><?= e($r['service']) ?></td><td><?= badge($r['status']) ?></td>
<td><?= $r['amount_due'] > 0 ? money($r['amount_due']) . ' ' . badge($r['payment_status']) : 'No fee' ?></td>
<td class="actions"><a class="btn gray" href="review.php?id=<?= $r['id'] ?>">Review</a>
<?php if (can('request.delete')): ?><form method="post" action="delete.php" onsubmit="return confirm('Delete this request permanently?')"><?= csrf_field() ?>
<input type="hidden" name="id" value="<?= $r['id'] ?>"><button class="btn red">Delete</button></form><?php endif; ?></td></tr>
<?php endforeach; ?>
<?php if (!$rows): ?><tr><td colspan="6">No requests found.</td></tr><?php endif; ?></table>
<?php foot();
