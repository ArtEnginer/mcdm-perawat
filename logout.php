<?php
require_once __DIR__ . '/inc/bootstrap.php';
app_logout();
header('Location: login.php');
exit;
