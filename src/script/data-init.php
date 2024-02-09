<?php

declare(strict_types = 1);

require_once __DIR__ . '/include/db-connection.php';
require_once __DIR__ . '/include/user-names.php';
require_once __DIR__ . '/include/credit-types.php';
require_once __DIR__ . '/include/functions.php';

const DATETIME_FORMAT = 'Y-m-d H:i:s';

clearAllData($pdo);
fillCreditTypes($pdo, $creditTypes);
fillUsers($pdo, intval(getenv('NUMBER_OF_USERS')), $userFirstNames, $userLastNames);
fillTransactions($pdo, intval(getenv('NUMBER_OF_TRANSACTIONS')), intval(getenv('MIN_CREDIT')), intval(getenv('MAX_CREDIT')));

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

/**
 * @throws Exception
 */
function fillTransactions(PDO $pdo, int $numberOfTransactions, int $minCredit, int $maxCredit) {
    if ($minCredit > $maxCredit) {
        throw new Exception('MinCredit is greather then MaxCredit');
    }

    if ($numberOfTransactions < 0) {
        throw new Exception(sprintf('Number of transactions are less then 0, got %d', $numberOfTransactions));
    }

    $maxUserId = $pdo->query('SELECT MAX(id) FROM user')->fetchColumn();
    $maxCreditTypeId = $pdo->query('SELECT MAX(id) FROM credit_type')->fetchColumn();
    $transactionToProcess = $numberOfTransactions;

    while ($transactionToProcess > 0) {
        $userId = rand(1, $maxUserId);
        $creditTypeId = rand(1, $maxCreditTypeId);

        $creditExpirationInDays = getCreditTypeExpirationInDays($pdo, $creditTypeId);
        $userCredit = getUserCredit($pdo, $userId, $creditTypeId);
        $amount = $userCredit === 0
            ? rand(0, $maxCredit)
            : rand(-$userCredit, $maxCredit);

        if ($userCredit + $amount >= 0) {
            $datetimeCreated = (new DateTimeImmutable())
                ->modify('-' . rand(0, 365 * 2) . ' day')
                ->setTime(rand(0, 23), rand(0, 59), rand(0, 59));
            $expiredAt = $creditExpirationInDays !== null
                ? $datetimeCreated->modify('+ ' . $creditExpirationInDays . ' day')
                : null;

            try {
                addTransaction($pdo, $userId, $creditTypeId, $amount, $datetimeCreated);

                if ($transactionToProcess !== $numberOfTransactions && $transactionToProcess % 100 === 0) {
                    printProcessedTransactions($transactionToProcess, $numberOfTransactions);
                }

                $transactionToProcess--;
            } catch (NotEnoughtCreditsException | ZeroAmountException $e) {
                echo $e->getMessage() . PHP_EOL;
            }
        }
    }

    printProcessedTransactions($transactionToProcess, $numberOfTransactions);
}

function printProcessedTransactions(int $transactionToProcess, int $totalTransactions) {
    $filled = $totalTransactions - $transactionToProcess;

    echo sprintf(
        'Filled %d/%d transactions.' . PHP_EOL,
        $filled,
        $totalTransactions
    );
}