<?php

declare(strict_types = 1);

require_once __DIR__ . '/include/db-connection.php';
require_once __DIR__ . '/include/user-names.php';
require_once __DIR__ . '/include/credit-types.php';
require_once __DIR__ . '/include/functions.php';

$noOutput = isset($_GET['no-output']);

clearAllData($pdo);
fillCreditTypes($pdo, $creditTypes, $noOutput);
fillUsers($pdo, intval(getenv('NUMBER_OF_USERS')), $userFirstNames, $userLastNames, $noOutput);

function clearAllData(PDO $pdo) {
    $pdo->exec('DELETE FROM transaction_audit');
    $pdo->exec('ALTER TABLE transaction_audit AUTO_INCREMENT = 1');
    $pdo->exec('DELETE FROM credit');
    $pdo->exec('ALTER TABLE credit AUTO_INCREMENT = 1');
    $pdo->exec('DELETE FROM transaction');
    $pdo->exec('ALTER TABLE transaction AUTO_INCREMENT = 1');
    $pdo->exec('DELETE FROM credit_type');
    $pdo->exec('ALTER TABLE credit_type AUTO_INCREMENT = 1');
    $pdo->exec('DELETE FROM user');
    $pdo->exec('ALTER TABLE user AUTO_INCREMENT = 1');
}

function fillCreditTypes(PDO $pdo, array $creditTypes, bool $noOutput = false) {

    foreach ($creditTypes as $creditType) {
        $query = $pdo->prepare('INSERT INTO credit_type (name, expiration_in_days, expirate_at, priority) VALUES (?, ?, ?, ?)');
        $query->execute(
            [
                $creditType['name'],
                $creditType['expiration'],
                $creditType['expirate_at'] !== null ? $creditType['expirate_at']->format(DATETIME_FORMAT) : null,
                $creditType['priority']
            ]
        );
    }

    if (!$noOutput) {
        echo sprintf('Filled %d credit types' . PHP_EOL, count($creditTypes));
    }
}

function fillUsers(PDO $pdo, int $numberOfUsers, array $firstNames, array $lastNames, bool $noOutput = false) {

    for ($i = 0; $i < $numberOfUsers; $i++) {
        $firstName = $firstNames[rand(0, count($firstNames) - 1)];
        $lastName = $lastNames[rand(0, count($lastNames) - 1)];

        $query = $pdo->prepare('INSERT INTO user (first_name, last_name) VALUES (?, ?)');
        $query->execute([$firstName, $lastName]);
    }

    if (!$noOutput) {
        echo sprintf('Filled %d users' . PHP_EOL, $numberOfUsers);
    }
}