<?php
require 'config/db.php';
$cat = (int)($_GET['cat'] ?? 0);
$q   = trim($_GET['q'] ?? '');

$sql = "SELECT m.*, c.name AS category FROM messages m JOIN categories c ON c.id=m.category_id
        WHERE m.status='published' AND m.publish_date <= NOW()
          AND (m.expiry_date IS NULL OR m.expiry_date >= CURDATE())";
$params = [];
if ($cat) { $sql .= " AND m.category_id=?"; $params[] = $cat; }
if ($q !== '') { $sql .= " AND (m.title LIKE ? OR m.body LIKE ?)"; $params[] = "%$q%"; $params[] = "%$q%"; }
$sql .= " ORDER BY FIELD(m.priority,'urgent','important','normal'), m.publish_date DESC";
$st = $pdo->prepare($sql); $st->execute($params);
$messages = $st->fetchAll();
$cats = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();
?>
<!DOCTYPE html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Citizen Information Dashboard</title><link rel="stylesheet" href="assets/style.css"></head><body>
<header><strong>e-Governance: Citizen Information Board</strong><nav><a href="admin/login.php">Admin Login</a></nav></header>
<main>
<form class="filter" method="get">
  <select name="cat"><option value="0">All categories</option>
  <?php foreach ($cats as $c): ?><option value="<?= $c['id'] ?>" <?= $cat==$c['id']?'selected':'' ?>><?= e($c['name']) ?></option><?php endforeach; ?>
  </select>
  <input name="q" placeholder="Search announcements" value="<?= e($q) ?>">
  <button class="btn">Filter</button>
</form>
<?php if (!$messages): ?><p>No announcements available.</p><?php endif; ?>
<?php foreach ($messages as $m): ?>
  <div class="card <?= e($m['priority']) ?>">
    <span class="badge <?= e($m['priority']) ?>"><?= e(strtoupper($m['priority'])) ?></span>
    <span class="badge"><?= e($m['category']) ?></span>
    <h3><?= e($m['title']) ?></h3>
    <p><?= nl2br(e($m['body'])) ?></p>
    <div class="meta">Published <?= date('d M Y, h:i A', strtotime($m['publish_date'])) ?>
      <?= $m['expiry_date'] ? ' | Valid until '.e($m['expiry_date']) : '' ?></div>
  </div>
<?php endforeach; ?>
</main></body></html>
