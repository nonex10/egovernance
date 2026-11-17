<?php
require 'config/db.php'; require_user(); require_perm('application.own');
$uid = $_SESSION['user_id'];
$id = (int)($_GET['id'] ?? 0);
$app = null;
if ($id) {
    $st = $pdo->prepare("SELECT * FROM applications WHERE id=? AND user_id=?"); $st->execute([$id, $uid]);
    $app = $st->fetch() ?: die('Not found');
    if ($app['status'] !== 'submitted' || $app['payment_status'] === 'paid') die('This application can no longer be edited.');
}
$services = $pdo->query("SELECT * FROM service_types WHERE is_active=1 ORDER BY name")->fetchAll();
$byId = []; foreach ($services as $s) $byId[$s['id']] = $s;
$errors = [];
$v = ['service_type_id' => $app['service_type_id'] ?? '', 'details' => $app['details'] ?? '', 'amount' => $app ? $app['amount_due'] : ''];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $v = ['service_type_id' => (int)($_POST['service_type_id'] ?? 0), 'details' => trim($_POST['details'] ?? ''), 'amount' => (float)($_POST['amount'] ?? 0)];
    $svc = $byId[$v['service_type_id']] ?? null;
    if (!$svc) $errors[] = 'Please choose a service.';
    if ($v['details'] === '') $errors[] = 'Application details are required.';
    if ($svc && $svc['needs_amount'] && $v['amount'] <= 0) $errors[] = 'Enter the amount to be paid.';
    if (!$errors) {
        $due = $svc['needs_amount'] ? $v['amount'] : $svc['fee'];
        if ($id) {
            $pdo->prepare("UPDATE applications SET service_type_id=?, details=?, amount_due=? WHERE id=? AND user_id=?")
                ->execute([$svc['id'], $v['details'], $due, $id, $uid]);
            audit('application_update', 'application#' . $id, $svc['name']);
        } else {
            $pdo->prepare("INSERT INTO applications (ref_no,user_id,service_type_id,details,amount_due) VALUES (?,?,?,?,?)")
                ->execute([ref_no(), $uid, $svc['id'], $v['details'], $due]);
            audit('application_create', 'application#' . $pdo->lastInsertId(), $svc['name'] . ', due ' . $due);
        }
        header('Location: dashboard.php'); exit;
    }
}
head($id ? 'Edit Application' : 'New Application', 'user', '<a href="dashboard.php">Back</a>');
?>
<div class="box">
<?php foreach ($errors as $er): ?><div class="err"><?= e($er) ?></div><?php endforeach; ?>
<form method="post"><?= csrf_field() ?>
<label>Service</label>
<select name="service_type_id" id="svc" required><option value="">-- choose --</option>
<?php foreach ($services as $s): ?>
<option value="<?= $s['id'] ?>" data-amount="<?= (int)$s['needs_amount'] ?>" data-hint="<?= e($s['hint']) ?>" data-fee="<?= e($s['fee']) ?>" <?= $v['service_type_id'] == $s['id'] ? 'selected' : '' ?>><?= e($s['name']) ?></option>
<?php endforeach; ?></select>
<p class="meta" id="hint"></p>
<div id="amtBox" style="display:none"><label>Amount to pay (Rs.)</label><input type="number" step="0.01" min="0" name="amount" value="<?= e($v['amount']) ?>"></div>
<p class="meta" id="feeNote"></p>
<label>Application details</label><textarea name="details" rows="6" required><?= e($v['details']) ?></textarea>
<p><button class="btn green"><?= $id ? 'Update' : 'Submit' ?></button></p></form></div>
<script>
const sel = document.getElementById('svc');
function sync() {
  const o = sel.options[sel.selectedIndex];
  document.getElementById('hint').textContent = o && o.dataset.hint ? 'Include: ' + o.dataset.hint : '';
  const amt = o && o.dataset.amount === '1';
  document.getElementById('amtBox').style.display = amt ? 'block' : 'none';
  document.getElementById('feeNote').textContent = o && o.value && !amt ? (parseFloat(o.dataset.fee) > 0 ? 'Service fee: Rs. ' + o.dataset.fee : 'No fee for this service.') : '';
}
sel.addEventListener('change', sync); sync();
</script>
<?php foot();
