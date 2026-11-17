<?php
require '../config/db.php'; require_admin(); require_perm('request.delete');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $id = (int)($_POST['id'] ?? 0);
    $d = $pdo->prepare("DELETE FROM applications WHERE id=?"); $d->execute([$id]);
    if ($d->rowCount()) audit('request_delete', 'application#' . $id);
}
header('Location: index.php');
