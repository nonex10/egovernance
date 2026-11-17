<?php
require 'config/db.php'; require_user(); require_perm('application.own');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $id = (int)($_POST['id'] ?? 0);
    $d = $pdo->prepare("DELETE FROM applications WHERE id=? AND user_id=? AND status='submitted' AND payment_status='unpaid'");
    $d->execute([$id, $_SESSION['user_id']]);
    if ($d->rowCount()) audit('application_delete', 'application#' . $id);
}
header('Location: dashboard.php');
