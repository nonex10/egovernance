<?php
require '../config/db.php'; require_admin();
$id = (int)($_GET['id'] ?? 0);
function load($pdo, $id) {
    $st = $pdo->prepare("SELECT a.*, s.name AS service, u.full_name, u.email, u.phone, u.address, u.district FROM applications a
      JOIN service_types s ON s.id=a.service_type_id JOIN users u ON u.id=a.user_id WHERE a.id=?");
    $st->execute([$id]); return $st->fetch();
}
$a = load($pdo, $id) ?: die('Not found');
$msg = ''; $err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $act = $_POST['action'] ?? ''; $rem = trim($_POST['remarks'] ?? ''); $s = $a['status']; $adm = $_SESSION['admin_id'];
    if ($act === 'review' && $s === 'submitted') {
        $pdo->prepare("UPDATE applications SET status='under_review', handled_by=? WHERE id=?")->execute([$adm, $id]); $msg = 'Marked under review.';
    } elseif ($act === 'approve' && in_array($s, ['submitted','under_review'])) {
        $pdo->prepare("UPDATE applications SET status='approved', admin_remarks=?, handled_by=? WHERE id=?")->execute([$rem ?: null, $adm, $id]); $msg = 'Request approved.';
    } elseif ($act === 'reject' && in_array($s, ['submitted','under_review','approved'])) {
        if ($rem === '') $err = 'Remarks are required when rejecting.';
        else { $pdo->prepare("UPDATE applications SET status='rejected', admin_remarks=?, handled_by=? WHERE id=?")->execute([$rem, $adm, $id]); $msg = 'Request rejected.'; }
    } elseif ($act === 'deliver' && $s === 'approved') {
        if ($a['amount_due'] > 0 && $a['payment_status'] !== 'paid') $err = 'Cannot deliver: payment is still pending.';
        else {
            $doc = 'DOC-' . date('Y') . '-' . str_pad($id, 5, '0', STR_PAD_LEFT);
            $pdo->prepare("UPDATE applications SET status='delivered', delivered_at=NOW(), document_no=?, delivery_note=?, handled_by=? WHERE id=?")
                ->execute([$doc, trim($_POST['delivery_note'] ?? '') ?: 'Issued electronically.', $adm, $id]);
            $msg = 'Service delivered to citizen.';
        }
    } elseif ($act === 'remarks') {
        $pdo->prepare("UPDATE applications SET admin_remarks=?, handled_by=? WHERE id=?")->execute([$rem ?: null, $adm, $id]); $msg = 'Remarks saved.';
    } else $err = 'That action is not allowed for the current status.';
    $a = load($pdo, $id);
}
$s = $a['status'];
head('Review ' . $a['ref_no'], '', '<a href="index.php">Back</a>', '../');
?>
<?php if ($msg): ?><div class="ok"><?= e($msg) ?></div><?php endif; ?>
<?php if ($err): ?><div class="err"><?= e($err) ?></div><?php endif; ?>
<div class="panel"><h3 style="margin-top:0"><?= e($a['service']) ?> <?= badge($s) ?></h3>
<p class="meta">Citizen: <b><?= e($a['full_name']) ?></b> | <?= e($a['email']) ?> | <?= e($a['phone']) ?> | <?= e($a['address']) ?>, <?= e($a['district']) ?></p>
<p><?= nl2br(e($a['details'])) ?></p>
<p><b>Amount:</b> <?= $a['amount_due'] > 0 ? money($a['amount_due']) . ' ' . badge($a['payment_status']) : 'No fee' ?></p>
<?php if ($a['document_no']): ?><p><b>Document no:</b> <?= e($a['document_no']) ?></p><?php endif; ?></div>
<div class="panel"><form method="post"><?= csrf_field() ?>
<label>Remarks (shown to citizen)</label><textarea name="remarks" rows="3"><?= e($a['admin_remarks']) ?></textarea>
<label>Delivery note (used when delivering)</label><input name="delivery_note" value="<?= e($a['delivery_note']) ?>" placeholder="e.g. Collect original from Ward Office 4">
<p class="actions">
<?php if ($s === 'submitted'): ?><button class="btn amber" name="action" value="review">Start review</button><?php endif; ?>
<?php if (in_array($s, ['submitted','under_review'])): ?><button class="btn green" name="action" value="approve">Approve</button><?php endif; ?>
<?php if (in_array($s, ['submitted','under_review','approved'])): ?><button class="btn red" name="action" value="reject">Reject</button><?php endif; ?>
<?php if ($s === 'approved'): ?><button class="btn" name="action" value="deliver">Deliver to citizen</button><?php endif; ?>
<button class="btn gray" name="action" value="remarks">Save remarks only</button></p></form></div>
<?php foot();
