<?php
// LAB 3: Access control = Identification + Authentication + Authorization + Accountability
const MAX_FAILS = 5;        // failed logins before lockout
const LOCK_MINUTES = 15;    // lockout length
const IDLE_SECONDS = 900;   // auto logout after 15 minutes of inactivity

function in_admin_area() { return strpos($_SERVER['SCRIPT_NAME'] ?? '', '/admin/') !== false; }
function base_path() { return in_admin_area() ? '../' : ''; }
function client_ip() { return substr($_SERVER['REMOTE_ADDR'] ?? '', 0, 45); }

/* ============ 4. ACCOUNTABILITY: every important action is recorded ============ */
function audit($action, $target = '', $details = '', $type = null, $id = null, $name = null) {
    global $pdo;
    if ($type === null) {
        if (in_admin_area() && !empty($_SESSION['admin_id'])) { $type = 'staff'; $id = $_SESSION['admin_id']; $name = $_SESSION['admin_user'] ?? ''; }
        elseif (!in_admin_area() && !empty($_SESSION['user_id'])) { $type = 'citizen'; $id = $_SESSION['user_id']; $name = $_SESSION['user_email'] ?? ''; }
        else { $type = 'anonymous'; }
    }
    try {
        $pdo->prepare("INSERT INTO audit_log (actor_type,actor_id,actor_name,action,target,details,ip_address,user_agent) VALUES (?,?,?,?,?,?,?,?)")
            ->execute([$type, $id, substr((string)$name, 0, 100), $action, substr($target, 0, 100), substr($details, 0, 255), client_ip(), substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255)]);
    } catch (Throwable $e) { /* logging must never break the application */ }
}

/* ============ 1 + 2. IDENTIFICATION and AUTHENTICATION ============ */
function password_policy($p) {
    if (strlen($p) < 8) return 'Password must be at least 8 characters.';
    if (!preg_match('/[A-Za-z]/', $p) || !preg_match('/[0-9]/', $p)) return 'Password must contain both letters and numbers.';
    return '';
}

// $kind: 'staff' (username) or 'citizen' (email). Returns [row|null, error message]
function attempt_login($kind, $ident, $pass) {
    global $pdo;
    $ident = substr(trim($ident), 0, 100);
    if ($kind === 'staff') {
        $table = 'admins';
        $sql = "SELECT t.*, r.name AS role_name, (t.locked_until IS NOT NULL AND t.locked_until > NOW()) AS is_locked
                FROM admins t JOIN roles r ON r.id=t.role_id WHERE t.username=?";
    } else {
        $table = 'users';
        $sql = "SELECT t.*, 'citizen' AS role_name, (t.locked_until IS NOT NULL AND t.locked_until > NOW()) AS is_locked
                FROM users t WHERE t.email=?";
    }
    $st = $pdo->prepare($sql); $st->execute([$ident]); $r = $st->fetch();
    if (!$r) { audit('login_failed', '', 'unknown identity: ' . $ident, 'anonymous', null, $ident); return [null, 'Invalid credentials.']; }
    if ($r['is_disabled']) { audit('login_blocked', '', 'disabled account', $kind, $r['id'], $ident); return [null, 'This account is disabled. Contact the administrator.']; }
    if ($r['is_locked']) { audit('login_blocked', '', 'account locked until ' . $r['locked_until'], $kind, $r['id'], $ident); return [null, 'Account locked after repeated failed logins. Try again later.']; }
    if (password_verify($pass, $r['password_hash'])) {
        $pdo->prepare("UPDATE $table SET failed_attempts=0, locked_until=NULL WHERE id=?")->execute([$r['id']]);
        return [$r, ''];
    }
    $n = $r['failed_attempts'] + 1;
    if ($n >= MAX_FAILS) {
        $pdo->prepare("UPDATE $table SET failed_attempts=0, locked_until=DATE_ADD(NOW(), INTERVAL " . LOCK_MINUTES . " MINUTE) WHERE id=?")->execute([$r['id']]);
        audit('account_locked', '', 'locked for ' . LOCK_MINUTES . ' minutes after ' . MAX_FAILS . ' failed attempts', $kind, $r['id'], $ident);
        return [null, 'Too many failed attempts. Account locked for ' . LOCK_MINUTES . ' minutes.'];
    }
    $pdo->prepare("UPDATE $table SET failed_attempts=? WHERE id=?")->execute([$n, $r['id']]);
    audit('login_failed', '', "wrong password (attempt $n of " . MAX_FAILS . ')', $kind, $r['id'], $ident);
    return [null, 'Invalid credentials.'];
}

function start_login($kind, $r) {
    session_regenerate_id(true);
    if ($kind === 'staff') {
        $_SESSION['admin_id'] = $r['id']; $_SESSION['admin_name'] = $r['full_name'];
        $_SESSION['admin_user'] = $r['username']; $_SESSION['admin_role'] = $r['role_name'];
    } else {
        $_SESSION['user_id'] = $r['id']; $_SESSION['user_name'] = $r['full_name'];
        $_SESSION['user_email'] = $r['email']; $_SESSION['user_role'] = 'citizen';
    }
    $_SESSION['last_activity'] = time();
    audit('login_success', '', $kind === 'staff' ? 'role: ' . $r['role_name'] : '');
}

function end_session($reason) {
    audit($reason);
    $_SESSION = []; session_destroy();
    header('Location: login.php?' . $reason . '=1'); exit;
}

function login_notice() {
    if (isset($_GET['session_timeout'])) return '<div class="err">You were logged out after 15 minutes of inactivity.</div>';
    if (isset($_GET['session_revoked'])) return '<div class="err">Your session was ended because the account was changed or disabled.</div>';
    if (isset($_GET['logged_out'])) return '<div class="ok">You have been logged out.</div>';
    return '';
}

// Runs on every request: idle timeout + re-check the account so role changes / disabling apply immediately
if (!empty($_SESSION['admin_id']) || !empty($_SESSION['user_id'])) {
    if (isset($_SESSION['last_activity']) && time() - $_SESSION['last_activity'] > IDLE_SECONDS) end_session('session_timeout');
    $_SESSION['last_activity'] = time();
    if (in_admin_area() && !empty($_SESSION['admin_id'])) {
        $c = $pdo->prepare("SELECT a.is_disabled, r.name AS role_name FROM admins a JOIN roles r ON r.id=a.role_id WHERE a.id=?");
        $c->execute([$_SESSION['admin_id']]); $row = $c->fetch();
        if (!$row || $row['is_disabled']) end_session('session_revoked');
        $_SESSION['admin_role'] = $row['role_name'];
    } elseif (!in_admin_area() && !empty($_SESSION['user_id'])) {
        $c = $pdo->prepare("SELECT is_disabled FROM users WHERE id=?");
        $c->execute([$_SESSION['user_id']]); $row = $c->fetch();
        if (!$row || $row['is_disabled']) end_session('session_revoked');
    }
}

/* ============ 3. AUTHORIZATION: role based permissions ============ */
function current_role() { return in_admin_area() ? ($_SESSION['admin_role'] ?? null) : ($_SESSION['user_role'] ?? null); }

function can($perm) {
    global $pdo; static $cache = [];
    $role = current_role();
    if (!$role) return false;
    if (!isset($cache[$role])) {
        $st = $pdo->prepare("SELECT p.code FROM role_permissions rp JOIN permissions p ON p.id=rp.permission_id JOIN roles r ON r.id=rp.role_id WHERE r.name=?");
        $st->execute([$role]); $cache[$role] = $st->fetchAll(PDO::FETCH_COLUMN);
    }
    return in_array($perm, $cache[$role], true);
}

function require_perm($perm) {
    if (!current_role()) { header('Location: login.php'); exit; }
    if (can($perm)) return;
    audit('access_denied', $_SERVER['REQUEST_URI'] ?? '', 'missing permission: ' . $perm . ' (role ' . current_role() . ')');
    http_response_code(403);
    head('Access denied', '', '<a href="' . (in_admin_area() ? 'index.php' : 'dashboard.php') . '">Back</a>', base_path());
    echo '<div class="box"><div class="err"><b>403 Forbidden.</b> Your role (' . e(current_role()) . ') does not have permission to do this. The attempt has been logged.</div></div>';
    foot(); exit;
}

function admin_nav() {
    $l = '';
    if (can('request.view'))   $l .= '<a href="index.php">Requests</a>';
    if (can('service.manage')) $l .= '<a href="services.php">Services</a>';
    if (can('citizen.view'))   $l .= '<a href="citizens.php">Citizens</a>';
    if (can('user.manage'))    $l .= '<a href="users.php">Accounts</a>';
    if (can('audit.view'))     $l .= '<a href="audit_log.php">Audit log</a>';
    return $l . '<a href="logout.php">Logout</a>';
}
