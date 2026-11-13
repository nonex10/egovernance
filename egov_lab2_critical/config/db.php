<?php
session_start();
$pdo = new PDO('mysql:host=localhost;dbname=egov_lab2_critical;charset=utf8mb4', 'root', '', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);

const PROVINCES = ['Koshi','Madhesh','Bagmati','Gandaki','Lumbini','Karnali','Sudurpashchim'];

function e($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function csrf_token() {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
    return $_SESSION['csrf'];
}
function csrf_check() {
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) die('Invalid CSRF token');
}
function require_admin() { if (empty($_SESSION['admin_id'])) { header('Location: login.php'); exit; } }
function require_user()  { if (empty($_SESSION['user_id']))  { header('Location: login.php'); exit; } }
