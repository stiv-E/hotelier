<?php
require_once __DIR__ . '/../includes/account_auth.php';

startAccountSession();
unset($_SESSION['admin_id']);
header('Location: login.php');
exit;