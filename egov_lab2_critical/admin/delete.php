<?php
require '../config/db.php'; require_admin();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $pdo->prepare("DELETE FROM applications WHERE id=?")->execute([(int)($_POST['id'] ?? 0)]);
}
header('Location: index.php');
