<?php
session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
session_start();
$pdo = new PDO('mysql:host=localhost;dbname=egov_lab3;charset=utf8mb4', 'root', '', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);

const STATUS_LABEL = ['submitted'=>'Submitted','under_review'=>'Under review','approved'=>'Approved','rejected'=>'Rejected','delivered'=>'Delivered'];

function e($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function csrf_token() {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
    return $_SESSION['csrf'];
}
function csrf_check() {
    $t = $_SESSION['csrf'] ?? '';
    if ($t === '' || !hash_equals($t, $_POST['csrf'] ?? '')) die('Invalid CSRF token');
}
function csrf_field() { return '<input type="hidden" name="csrf" value="' . csrf_token() . '">'; }
function require_admin() { if (empty($_SESSION['admin_id'])) { header('Location: login.php'); exit; } }
function require_user()  { if (empty($_SESSION['user_id']))  { header('Location: login.php'); exit; } }
function ref_no() { return 'EG-' . date('Y') . '-' . strtoupper(bin2hex(random_bytes(3))); }
function money($n) { return 'Rs. ' . number_format((float)$n, 2); }
function badge($s) { return '<span class="badge ' . e($s) . '">' . e(STATUS_LABEL[$s] ?? $s) . '</span>'; }

// $nav is trusted HTML written in our own pages; $base is '' or '../' for the stylesheet path
function head($title, $cls = '', $nav = '', $base = '') {
    echo '<!DOCTYPE html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">'
       . '<title>' . e($title) . '</title><link rel="stylesheet" href="' . $base . 'assets/style.css"></head><body>'
       . '<header class="' . $cls . '"><strong>' . e($title) . '</strong><nav>' . $nav . '</nav></header><main>';
}
function foot() { echo '</main></body></html>'; }

require __DIR__ . '/auth.php';   // Lab 3: access control
