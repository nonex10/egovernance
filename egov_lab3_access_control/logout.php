<?php
require 'config/db.php';
if (!empty($_SESSION['user_id'])) audit('logout');
$_SESSION = []; session_destroy();
header('Location: login.php?logged_out=1');
