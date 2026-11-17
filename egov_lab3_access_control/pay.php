<?php
require 'config/db.php'; require_user(); require_perm('application.own');
$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$st = $pdo->prepare("SELECT a.*, s.name AS service FROM applications a JOIN service_types s ON s.id=a.service_type_id WHERE a.id=? AND a.user_id=?");
$st->execute([$id, $_SESSION['user_id']]);
$a = $st->fetch() ?: die('Not found');
if ($a['amount_due'] <= 0 || $a['payment_status'] !== 'unpaid' || $a['status'] === 'rejected') die('Nothing to pay for this application.');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $pdo->prepare("UPDATE applications SET payment_status='paid', paid_at=NOW() WHERE id=? AND user_id=? AND payment_status='unpaid'")
        ->execute([$id, $_SESSION['user_id']]);
    audit('payment', 'application#' . $id, money($a['amount_due']) . ' (simulated)');
    header('Location: view.php?id=' . $id . '&paid=1'); exit;
}
head('Pay: ' . $a['service'], 'user', '<a href="view.php?id=' . $id . '">Back</a>');
?>
<div class="box"><p>Reference <b><?= e($a['ref_no']) ?></b><br>Amount due: <b><?= money($a['amount_due']) ?></b></p>
<p class="meta">Simulated payment gateway: no real money is charged.</p>
<form method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>">
<label>Payment method</label><select name="method"><option>eSewa (simulated)</option><option>Khalti (simulated)</option><option>Bank transfer (simulated)</option></select>
<p><button class="btn green">Pay <?= money($a['amount_due']) ?></button></p></form></div>
<?php foot();
