<?php
require '../config/db.php'; require_admin(); require_perm('user.manage');
$msg = ''; $err = '';
$roles = $pdo->query("SELECT id,name FROM roles WHERE name<>'citizen' ORDER BY id")->fetchAll(PDO::FETCH_KEY_PAIR);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $act = $_POST['action'] ?? ''; $id = (int)($_POST['id'] ?? 0);
    $kind = ($_POST['kind'] ?? 'staff') === 'citizen' ? 'citizen' : 'staff';
    $table = $kind === 'citizen' ? 'users' : 'admins';
    $self = $kind === 'staff' && $id === (int)$_SESSION['admin_id'];
    if ($act === 'create_staff') {
        $un = trim($_POST['username'] ?? ''); $fn = trim($_POST['full_name'] ?? ''); $pw = $_POST['password'] ?? ''; $rid = (int)($_POST['role_id'] ?? 0);
        if (!preg_match('/^[A-Za-z0-9_]{3,30}$/', $un)) $err = 'Username: 3-30 letters, numbers or underscore.';
        elseif ($fn === '') $err = 'Full name is required.';
        elseif (!isset($roles[$rid])) $err = 'Choose a valid role.';
        elseif ($e2 = password_policy($pw)) $err = $e2;
        else {
            $d = $pdo->prepare("SELECT id FROM admins WHERE username=?"); $d->execute([$un]);
            if ($d->fetch()) $err = 'Username already exists.';
            else {
                $pdo->prepare("INSERT INTO admins (username,password_hash,full_name,role_id) VALUES (?,?,?,?)")->execute([$un, password_hash($pw, PASSWORD_DEFAULT), $fn, $rid]);
                audit('staff_create', 'staff#' . $pdo->lastInsertId(), $un . ' as ' . $roles[$rid]); $msg = 'Staff account created.';
            }
        }
    } elseif ($act === 'set_role' && $kind === 'staff') {
        $rid = (int)($_POST['role_id'] ?? 0);
        if ($self) $err = 'You cannot change your own role.';
        elseif (!isset($roles[$rid])) $err = 'Choose a valid role.';
        else { $pdo->prepare("UPDATE admins SET role_id=? WHERE id=?")->execute([$rid, $id]); audit('account_role_change', "staff#$id", 'new role: ' . $roles[$rid]); $msg = 'Role updated.'; }
    } elseif (in_array($act, ['disable', 'enable'])) {
        if ($self) $err = 'You cannot disable your own account.';
        else { $pdo->prepare("UPDATE $table SET is_disabled=? WHERE id=?")->execute([$act === 'disable' ? 1 : 0, $id]); audit('account_' . $act, "$kind#$id"); $msg = 'Account ' . $act . 'd.'; }
    } elseif ($act === 'unlock') {
        $pdo->prepare("UPDATE $table SET failed_attempts=0, locked_until=NULL WHERE id=?")->execute([$id]); audit('account_unlock', "$kind#$id"); $msg = 'Account unlocked.';
    } else $err = 'Unknown action.';
}
$staff = $pdo->query("SELECT a.id,a.username,a.full_name,a.role_id,r.name AS role,a.is_disabled,(a.locked_until IS NOT NULL AND a.locked_until>NOW()) AS locked FROM admins a JOIN roles r ON r.id=a.role_id ORDER BY a.id")->fetchAll();
$cits = $pdo->query("SELECT id,full_name,email,is_disabled,(locked_until IS NOT NULL AND locked_until>NOW()) AS locked FROM users ORDER BY id")->fetchAll();
function status_txt($r) { return $r['is_disabled'] ? '<span class="badge rejected">Disabled</span>' : ($r['locked'] ? '<span class="badge under_review">Locked</span>' : '<span class="badge approved">Active</span>'); }
function acct_btns($kind, $r) {
    $f = function ($act, $label, $cls) use ($kind, $r) {
        return '<form method="post" style="display:inline">' . csrf_field() . '<input type="hidden" name="kind" value="' . $kind . '"><input type="hidden" name="id" value="' . $r['id']
             . '"><button class="btn ' . $cls . '" name="action" value="' . $act . '">' . $label . '</button></form> ';
    };
    return ($r['is_disabled'] ? $f('enable', 'Enable', 'green') : $f('disable', 'Disable', 'red')) . ($r['locked'] ? $f('unlock', 'Unlock', 'amber') : '');
}
head('Account Management', '', 'Hi, ' . e($_SESSION['admin_name']) . ' (' . e($_SESSION['admin_role']) . ') ' . admin_nav(), '../');
?>
<?php if ($msg): ?><div class="ok"><?= e($msg) ?></div><?php endif; ?>
<?php if ($err): ?><div class="err"><?= e($err) ?></div><?php endif; ?>
<h3>Staff accounts</h3>
<table><tr><th>Username</th><th>Name</th><th>Role</th><th>Status</th><th>Actions</th></tr>
<?php foreach ($staff as $r): ?>
<tr><td><?= e($r['username']) ?></td><td><?= e($r['full_name']) ?></td>
<td><form method="post" class="actions"><?= csrf_field() ?><input type="hidden" name="action" value="set_role"><input type="hidden" name="id" value="<?= $r['id'] ?>">
<select name="role_id" style="width:auto"><?php foreach ($roles as $rid => $rn): ?><option value="<?= $rid ?>" <?= $rid == $r['role_id'] ? 'selected' : '' ?>><?= e($rn) ?></option><?php endforeach; ?></select>
<button class="btn gray">Set</button></form></td>
<td><?= status_txt($r) ?></td><td><?= acct_btns('staff', $r) ?></td></tr>
<?php endforeach; ?></table>
<div class="panel" style="margin-top:16px"><h4 style="margin-top:0">Create staff account</h4>
<form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="create_staff">
<div class="row"><div><label>Username</label><input name="username" required></div><div><label>Full name</label><input name="full_name" required></div></div>
<div class="row"><div><label>Password (8+ chars, letters and numbers)</label><input type="password" name="password" required></div>
<div><label>Role</label><select name="role_id"><?php foreach ($roles as $rid => $rn): ?><option value="<?= $rid ?>"><?= e($rn) ?></option><?php endforeach; ?></select></div></div>
<p><button class="btn">Create</button></p></form></div>
<h3>Citizen accounts</h3>
<table><tr><th>Name</th><th>Email</th><th>Status</th><th>Actions</th></tr>
<?php foreach ($cits as $r): ?><tr><td><?= e($r['full_name']) ?></td><td><?= e($r['email']) ?></td><td><?= status_txt($r) ?></td><td><?= acct_btns('citizen', $r) ?></td></tr><?php endforeach; ?></table>
<?php foot();
