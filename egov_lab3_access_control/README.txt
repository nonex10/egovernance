LAB 3: ACCESS CONTROL FOR E-GOVERNANCE (PHP + MySQL)
Access control = Identification, Authentication, Authorization, Accountability

SETUP (XAMPP/WAMP)
1. Copy this folder (egov_lab3_access_control) into htdocs.
2. Start Apache + MySQL, open phpMyAdmin, import schema.sql (creates its own database: egov_lab3).
3. Edit config/db.php if your MySQL user/password differ.
4. Citizen portal  : http://localhost/egov_lab3_access_control/
   Government panel: http://localhost/egov_lab3_access_control/admin/login.php

LOGINS
   admin    / admin123     role admin   : everything
   officer1 / officer123   role officer : view, review/approve/reject, deliver, view citizens
   auditor1 / auditor123   role auditor : read-only requests + citizens + audit log
   ram@example.com, sita@example.com / citizen123 : role citizen

THE FOUR ACCESS CONTROL SERVICES
1. IDENTIFICATION   Every person has a unique identity: staff username or citizen email, a numeric ID and a role.
                    Unknown identities are logged as "anonymous" attempts.
2. AUTHENTICATION   Password check (bcrypt hashes) + password policy (8+ chars, letters and numbers)
                    + lockout after 5 wrong passwords for 15 minutes (admin can unlock)
                    + auto logout after 15 minutes idle + new session ID on login
                    + HttpOnly/SameSite cookies + CSRF tokens + generic "Invalid credentials" message.
3. AUTHORIZATION    Role Based Access Control: tables roles, permissions, role_permissions.
                    Every page and action calls require_perm('...'); buttons are hidden when not allowed.
                    The role is re-read from the database on every request, so a role change or a disabled
                    account takes effect immediately. Forged requests get 403 Forbidden and are logged.
4. ACCOUNTABILITY   audit_log records who / what / target / details / IP / browser / time for logins (success, failed,
                    blocked), lockouts, logout, timeouts, access denied, registration, applications, payments,
                    review/approve/reject/deliver/delete, service changes and account changes.
                    View: admin/audit_log.php (read-only, no edit or delete option).

ROLE x PERMISSION MATRIX
 Permission          citizen  officer  auditor  admin
 application.own        X
 request.view                   X        X        X
 request.review                 X                 X
 request.deliver                X                 X
 request.delete                                   X
 service.manage                                   X
 citizen.view                   X        X        X
 audit.view                              X        X
 user.manage                                      X

KEY FILES
 config/auth.php      all access control logic (login, lockout, RBAC, audit)
 admin/users.php      create staff, change roles, disable/enable, unlock accounts
 admin/audit_log.php  audit trail viewer with filters

DEMO SCRIPT FOR VIVA
 a) Log in as officer1 and open admin/services.php -> 403 Forbidden; the admin audit log shows access_denied.
 b) Enter a wrong password 5 times for sita@example.com -> locked. Admin > Accounts > Unlock.
 c) Log in as auditor1, open a request -> read-only, no approve button.
 d) Admin > Accounts: change officer1 to auditor while officer1 is logged in -> permissions drop on the next click.
 e) Idle for 15 minutes (or lower IDLE_SECONDS in config/auth.php) -> automatic logout.
