<?php

declare(strict_types = 1);

require_once __DIR__ . '/include/db-connection.php';
require_once __DIR__ . '/include/user-names.php';
require_once __DIR__ . '/include/credit-types.php';
require_once __DIR__ . '/include/functions.php';

clearAllData($pdo);
generateTransactions(
    $pdo,
    intval(getenv('NUMBER_OF_TRANSACTIONS')),
    intval(getenv('MAX_TRANSACTION_CREDIT')),
    true
);

function clearAllData(PDO $pdo) {
    $pdo->exec('DELETE FROM transaction_audit');
    $pdo->exec('ALTER TABLE transaction_audit AUTO_INCREMENT = 1');
    $pdo->exec('DELETE FROM credit');
    $pdo->exec('ALTER TABLE credit AUTO_INCREMENT = 1');
    $pdo->exec('DELETE FROM request');
    $pdo->exec('ALTER TABLE request AUTO_INCREMENT = 1');
    $pdo->exec('DELETE FROM transaction');
    $pdo->exec('ALTER TABLE transaction AUTO_INCREMENT = 1');
}
