<?php
require 'config/db.php'; require_user();
$st = $pdo->prepare("SELECT * FROM users WHERE id=?"); $st->execute([$_SESSION['user_id']]);
$u = $st->fetch();
$cat = (int)($_GET['cat'] ?? 0);

// Targeted delivery: only reports whose target group matches this citizen's attributes
$sql = "SELECT r.*, c.name AS category FROM reports r JOIN categories c ON c.id=r.category_id
 WHERE r.status='published' AND r.publish_date <= NOW() AND (r.expiry_date IS NULL OR r.expiry_date >= CURDATE())
 AND (r.target_min_age IS NULL OR r.target_min_age <= ?)
 AND (r.target_max_age IS NULL OR r.target_max_age >= ?)
 AND (r.target_gender='all' OR r.target_gender = ?)
 AND (r.target_province='All' OR r.target_province = ?)
 AND (r.target_district='' OR LOWER(r.target_district)=LOWER(?))";
$p = [$u['age'], $u['age'], $u['gender'], $u['province'], $u['district']];
if ($cat) { $sql .= " AND r.category_id=?"; $p[] = $cat; }
$sql .= " ORDER BY FIELD(r.severity,'critical','warning','info'), r.publish_date DESC";
$s = $pdo->prepare($sql); $s->execute($p);
$reports = $s->fetchAll();
$cats = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();
?>
<!DOCTYPE html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>My Critical Alerts</title><link rel="stylesheet" href="assets/style.css"></head><body>
<header class="user"><strong>My Critical Information</strong><nav>Hi, <?= e($u['full_name']) ?>
<a href="profile.php">My Target Group</a><a href="logout.php">Logout</a></nav></header>
<main>
<div class="profile"><strong>Your target group:</strong>
<span class="badge target">Age <?= (int)$u['age'] ?></span><span class="badge target"><?= e(ucfirst($u['gender'])) ?></span>
<span class="badge target"><?= e($u['province']) ?></span><?php if ($u['district']): ?><span class="badge target"><?= e($u['district']) ?></span><?php endif; ?>
<a href="profile.php">Change</a></div>
<form class="filter" method="get"><select name="cat"><option value="0">All categories</option>
<?php foreach ($cats as $c): ?><option value="<?= $c['id'] ?>" <?= $cat==$c['id']?'selected':'' ?>><?= e($c['name']) ?></option><?php endforeach; ?></select>
<button class="btn">Filter</button></form>
<?php if (!$reports): ?><p>No alerts match your target group right now.</p><?php endif; ?>
<?php foreach ($reports as $r): ?>
<div class="card <?= e($r['severity']) ?>">
<span class="badge <?= e($r['severity']) ?>"><?= e(strtoupper($r['severity'])) ?></span><span class="badge"><?= e($r['category']) ?></span>
<h3><?= e($r['title']) ?></h3><p><?= nl2br(e($r['body'])) ?></p>
<div class="meta"><?= date('d M Y, h:i A', strtotime($r['publish_date'])) ?></div></div>
<?php endforeach; ?>
</main></body></html>
