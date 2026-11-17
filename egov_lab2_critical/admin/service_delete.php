<?php
require '../config/db.php'; require_admin();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $id = (int)($_POST['id'] ?? 0);
    $n = $pdo->prepare("SELECT COUNT(*) FROM applications WHERE service_type_id=?"); $n->execute([$id]);
    if ($n->fetchColumn() > 0) $pdo->prepare("UPDATE service_types SET is_active=0 WHERE id=?")->execute([$id]);
    else $pdo->prepare("DELETE FROM service_types WHERE id=?")->execute([$id]);
}
header('Location: services.php');
