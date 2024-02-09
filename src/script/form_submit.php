<?php

require_once __DIR__ . '/include/functions.php';
require_once __DIR__ . '/include/db-connection.php';

$amount = $_POST['amount'];
$userId = $_POST['userId'];
$creditTypeId = $_POST['creditTypeId'];

addTransaction($pdo, $userId, $creditTypeId, $amount);

$protocol = $_SERVER['PROTOCOL'] = isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && !empty($_SERVER['HTTP_X_FORWARDED_PROTO']) ? "https://" : "http://";
$url = $protocol . $_SERVER['HTTP_HOST'];

header('Location: '. $url);

exit;