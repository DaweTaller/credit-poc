<?php

require_once __DIR__ . '/include/functions.php';
require_once __DIR__ . '/include/db-connection.php';

$protocol = $_SERVER['PROTOCOL'] = isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && !empty($_SERVER['HTTP_X_FORWARDED_PROTO']) ? "https://" : "http://";
$redirectUrl = $protocol . $_SERVER['HTTP_HOST'];

try {
    if (isset($_POST['reset-data'])) {
        $_GET['no-output'] = true;
        include "data-init.php";
    } elseif (isset($_POST['add-credit'])) {
        addCredit($pdo, $_POST['userId'], $_POST['creditTypeId'], $_POST['amount']);
    } elseif (isset($_POST['use-credit'])) {
        useCredit($pdo, $_POST['userId'], $_POST['amount']);
    } elseif (isset($_POST['expire-credit'])) {
        setExpirationOnCredit($pdo, $_POST['creditId']);
        processExpirations($pdo);
    }
} catch (NotEnoughtCreditsException | ZeroAmountException | InactiveCreditTypeException $e) {
    echo sprintf(
        '<strong>%s</strong><br><br><a href="%s">Go back</a>',
        $e->getMessage(),
        $redirectUrl
    );
    exit;
}

header('Location: '. $redirectUrl);

exit;