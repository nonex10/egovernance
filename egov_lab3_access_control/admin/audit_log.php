<?php
require '../config/db.php'; require_admin(); require_perm('audit.view');
$action = trim($_GET['action'] ?? ''); $q = trim($_GET['q'] ?? '');
$sql = "SELECT * FROM audit_log WHERE 1"; $p = [];
if ($action !== '') { $sql .= " AND action=?"; $p[] = $action; }
if ($q !== '') { $sql .= " AND (actor_name LIKE ? OR target LIKE ? OR details LIKE ?)"; array_push($p, "%$q%", "%$q%", "%$q%"); }
$st = $pdo->prepare($sql . " ORDER BY id DESC LIMIT 200"); $st->execute($p); $rows = $st->fetchAll();
$actions = $pdo->query("SELECT DISTINCT action FROM audit_log ORDER BY action")->fetchAll(PDO::FETCH_COLUMN);
$s = $pdo->query("SELECT COUNT(*) total,
  COALESCE(SUM(action='login_failed' AND created_at > NOW() - INTERVAL 1 DAY),0) failed,
  COALESCE(SUM(action='account_locked' AND created_at > NOW() - INTERVAL 1 DAY),0) locked,
  COALESCE(SUM(action='access_denied' AND created_at > NOW() - INTERVAL 1 DAY),0) denied FROM audit_log")->fetch();
head('Audit Log (Accountability)', '', 'Hi, ' . e($_SESSION['admin_name']) . ' (' . e($_SESSION['admin_role']) . ') ' . admin_nav(), '../');
?>
<div class="stats"><div><b><?= (int)$s['total'] ?></b>Total events</div><div><b><?= (int)$s['failed'] ?></b>Failed logins (24h)</div>
<div><b><?= (int)$s['locked'] ?></b>Lockouts (24h)</div><div><b><?= (int)$s['denied'] ?></b>Access denied (24h)</div></div>
<form class="filter" method="get"><select name="action"><option value="">All actions</option>
<?php foreach ($actions as $a): ?><option <?= $action === $a ? 'selected' : '' ?>><?= e($a) ?></option><?php endforeach; ?></select>
<input name="q" placeholder="Search user, target or details" value="<?= e($q) ?>"><button class="btn">Filter</button></form>
<p class="meta">Showing the latest <?= count($rows) ?> events (max 200). The log is read-only: there is no edit or delete option.</p>
<table><tr><th>Time</th><th>Who</th><th>Action</th><th>Target / details</th><th>IP</th></tr>
<?php foreach ($rows as $r): ?>
<tr><td><?= e($r['created_at']) ?></td><td><?= e($r['actor_name'] ?: '-') ?><br><span class="meta"><?= e($r['actor_type']) ?></span></td>
<td><b><?= e($r['action']) ?></b></td><td><?= e($r['target']) ?><br><span class="meta"><?= e($r['details']) ?></span></td><td><?= e($r['ip_address']) ?></td></tr>
<?php endforeach; ?>
<?php if (!$rows): ?><tr><td colspan="5">No events.</td></tr><?php endif; ?></table>
<?php foot();
