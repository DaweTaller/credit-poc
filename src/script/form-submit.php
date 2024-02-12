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
        $requestId = generateRequestId();
        $referrer = getRandomReferrer();
        $userId = $_POST['userId'];
        $amount = $_POST['amount'];
        $creditTypeId = $_POST['creditTypeId'];
        $validFrom = $_POST['validFrom'] !== "" ? new DateTimeImmutable($_POST['validFrom']) : null;
        $additionalData = [
            'requestId' => $requestId,
            'referrer' => $referrer,
            'userId' => $userId,
            'amount' => $amount,
            'creditTypeId' => $creditTypeId,
        ];

        createTransactionRequest(
            $pdo,
            $requestId,
            $userId,
            $referrer,$amount,
            $creditTypeId,
            $additionalData,
            $validFrom
        );

        // add only valid transactions
        if ($validFrom === null || $validFrom->format(DATETIME_FORMAT) <= (new DateTimeImmutable())->format(DATETIME_FORMAT)) {
            $transactionId = addCredit($pdo, $userId, $creditTypeId, $amount);
            setTransactionIdToRequest($pdo, $requestId, $transactionId);
        }
    } elseif (isset($_POST['use-credit'])) {
        $requestId = generateRequestId();
        $referrer = getRandomReferrer();
        $userId = $_POST['userId'];
        $amount = $_POST['amount'];
        $additionalData = [
            'requestId' => $requestId,
            'referrer' => $referrer,
            'userId' => $userId,
            'amount' => -$amount,
        ];

        createTransactionRequest($pdo, $requestId, $userId, $referrer, -$amount, null, $additionalData);
        $transactionId = useCredit($pdo, $userId, $amount);
        setTransactionIdToRequest($pdo, $requestId, $transactionId);
    } elseif (isset($_POST['expire-credit'])) {
        setExpirationOnCredit($pdo, $_POST['creditId']);
        processExpirations($pdo);
        processValidFrom($pdo);
    } elseif (isset($_POST['valid-from'])) {
        setValidFromOnRequest($pdo, $_POST['requestId']);
        processValidFrom($pdo);
        processExpirations($pdo);
    } elseif (isset($_POST['generate-transactions'])) {
        generateTransactions($pdo, $_POST['transactionsCount'], $_POST['minCredit'], $_POST['maxCredit']);
        processExpirations($pdo);
        processValidFrom($pdo);
    }
} catch (NotEnoughtCreditsException | ZeroAmountException | ExpiredCreditTypeException $e) {
    echo sprintf(
        '<strong>%s</strong><br><br><a href="%s">Go back</a>',
        $e->getMessage(),
        $redirectUrl
    );
    exit;
}

header('Location: '. $redirectUrl);

exit;