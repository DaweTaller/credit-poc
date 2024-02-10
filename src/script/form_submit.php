<?php

require_once __DIR__ . '/include/functions.php';
require_once __DIR__ . '/include/db-connection.php';

if (isset($_POST['reset-data'])) {
    $_GET['no-output'] = true;
    include "data-init.php";
} elseif (isset($_POST['add-credit'])) {
    addCredit($pdo, $_POST['userId'], $_POST['creditTypeId'], $_POST['amount']);
} elseif (isset($_POST['use-credit'])) {
    useCredit($pdo, $_POST['userId'], $_POST['amount']);
}

$protocol = $_SERVER['PROTOCOL'] = isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && !empty($_SERVER['HTTP_X_FORWARDED_PROTO']) ? "https://" : "http://";
$url = $protocol . $_SERVER['HTTP_HOST'];

header('Location: '. $url);

exit;