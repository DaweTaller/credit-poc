<?php

declare(strict_types = 1);

require_once __DIR__ . '/include/db-connection.php';
require_once __DIR__ . '/include/user-names.php';
require_once __DIR__ . '/include/credit-types.php';
require_once __DIR__ . '/include/functions.php';

clearAllData($pdo);
fillCreditTypes($pdo, $creditTypes);
fillUsers($pdo, intval(getenv('NUMBER_OF_USERS')), $userFirstNames, $userLastNames);

function clearAllData(PDO $pdo) {
    $pdo->exec('DELETE FROM credit_transaction');
    $pdo->exec('ALTER TABLE credit_transaction AUTO_INCREMENT = 1');
    $pdo->exec('DELETE FROM credit');
    $pdo->exec('ALTER TABLE credit AUTO_INCREMENT = 1');
    $pdo->exec('DELETE FROM transaction');
    $pdo->exec('ALTER TABLE transaction AUTO_INCREMENT = 1');
    $pdo->exec('DELETE FROM credit_type');
    $pdo->exec('ALTER TABLE credit_type AUTO_INCREMENT = 1');
    $pdo->exec('DELETE FROM user');
    $pdo->exec('ALTER TABLE user AUTO_INCREMENT = 1');
}

function fillCreditTypes(PDO $pdo, array $creditTypes) {

    foreach ($creditTypes as $creditType) {
        $query = $pdo->prepare('INSERT INTO credit_type (name, expiration_in_days, priority) VALUES (?, ?, ?)');
        $query->execute(
            [
                $creditType['name'],
                $creditType['expiration'],
                $creditType['priority']
            ]
        );
    }

    echo sprintf('Filled %d credit types' . PHP_EOL, count($creditTypes));
}

function fillUsers(PDO $pdo, int $numberOfUsers, array $firstNames, array $lastNames) {

    for ($i = 0; $i < $numberOfUsers; $i++) {
        $firstName = $firstNames[rand(0, count($firstNames) - 1)];
        $lastName = $lastNames[rand(0, count($lastNames) - 1)];

        $query = $pdo->prepare('INSERT INTO user (first_name, last_name) VALUES (?, ?)');
        $query->execute([$firstName, $lastName]);
    }

    echo sprintf('Filled %d users' . PHP_EOL, $numberOfUsers);
}