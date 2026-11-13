<?php
require '../config/db.php'; require_admin();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $pdo->prepare("DELETE FROM reports WHERE id=?")->execute([(int)$_POST['id']]);
}
header('Location: index.php');
