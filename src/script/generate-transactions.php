<?php

declare(strict_types = 1);

require_once __DIR__ . '/include/db-connection.php';
require_once __DIR__ . '/include/user-names.php';
require_once __DIR__ . '/include/credit-types.php';
require_once __DIR__ . '/include/functions.php';

clearAllData($pdo);
generateTransactions($pdo, intval(getenv('NUMBER_OF_TRANSACTIONS')), intval(getenv('MIN_CREDIT')), intval(getenv('MAX_CREDIT')));

function clearAllData(PDO $pdo) {
    $pdo->exec('DELETE FROM transaction_audit');
    $pdo->exec('ALTER TABLE transaction_audit AUTO_INCREMENT = 1');
    $pdo->exec('DELETE FROM credit');
    $pdo->exec('ALTER TABLE credit AUTO_INCREMENT = 1');
    $pdo->exec('DELETE FROM transaction');
    $pdo->exec('ALTER TABLE transaction AUTO_INCREMENT = 1');
}

/**
 * @throws Exception
 */
function generateTransactions(PDO $pdo, int $numberOfTransactions, int $minCredit, int $maxCredit) {
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
        $userCredit = getUserCredit($pdo, $userId, $creditTypeId);
        $amount = $userCredit === 0
            ? rand(0, $maxCredit)
            : rand(-$userCredit, $maxCredit);

        if ($userCredit + $amount >= 0) {
            $datetimeCreated = (new DateTimeImmutable())
                ->modify('-' . rand(0, 365 * 2) . ' day')
                ->setTime(rand(0, 23), rand(0, 59), rand(0, 59));

            try {
                if ($amount > 0) {
                    addCredit($pdo, $userId, $creditTypeId, $amount, $datetimeCreated);
                } else {
                    useCredit($pdo, $userId, abs($amount), $datetimeCreated);
                }

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