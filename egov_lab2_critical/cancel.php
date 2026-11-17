<?php
require 'config/db.php'; require_user();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $pdo->prepare("DELETE FROM applications WHERE id=? AND user_id=? AND status='submitted' AND payment_status='unpaid'")
        ->execute([(int)($_POST['id'] ?? 0), $_SESSION['user_id']]);
}
header('Location: dashboard.php');
