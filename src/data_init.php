<?php

require_once __DIR__ . '/include/db_connection.php';
require_once __DIR__ . '/include/user_names.php';
require_once __DIR__ . '/include/credit_types.php';
require_once __DIR__ . '/exception/NotEnoughtCreditsException.php';
require_once __DIR__ . '/exception/ZeroAmountException.php';

const DATETIME_FORMAT = 'Y-m-d H:i:s';

$env = parse_ini_file('.env');

clearAllData($pdo);
fillCreditTypes($pdo, $creditTypes);
fillUsers($pdo, $env['NUMBER_OF_USERS'], $userFirstNames, $userLastNames);
fillTransactions($pdo, $env['NUMBER_OF_TRANSACTIONS'], $env['MIN_CREDIT'], $env['MAX_CREDIT']);

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
                addTransaction($pdo, $userId, $creditTypeId, $amount);

                $transactionToProcess--;
            } catch (NotEnoughtCreditsException | ZeroAmountException $e) {
                echo $e->getMessage() . PHP_EOL;
            }
        }
    }
}

function getUserCredit(PDO $pdo, int $userId, ?int $creditTypeId = null): ?int {
    $sql = '
        SELECT SUM(remaining_amount)
        FROM credit
        WHERE user_id = ?
          AND (expired_at IS NULL OR expired_at > NOW())
';

    $parameters = [$userId];
    if ($creditTypeId !== null) {
        $sql .= ' AND credit_type_id = ?';
        $parameters[] = $creditTypeId;
    }

    $query = $pdo->prepare($sql);
    $query->execute($parameters);
    $sum = $query->fetchColumn();

    return intval($sum);
}

function getCreditTypeExpirationInDays(PDO $pdo, int $creditTypeId): ?int {
    $stmt = $pdo->prepare('SELECT expiration_in_days FROM credit_type WHERE id = ?');
    $stmt->execute([$creditTypeId]);
    $result = $stmt->fetchColumn();

    if ($result === null) {
        return null;
    }

    return intval($result);
}

/**
 * @throws ZeroAmountException
 * @throws NotEnoughtCreditsException
 */
function addTransaction(PDO $pdo, int $userId, int $creditTypeId, int $amount) {
    if ($amount === 0) {
        throw new ZeroAmountException('Cant add transaction with zero amount');
    }

    $pdo->beginTransaction();

    try {
        if ($amount < 0) {
            $userCredit = getUserCredit($pdo, $userId, $creditTypeId);

            if ($userCredit < $amount) {
                throw new NotEnoughtCreditsException(sprintf('User %d does not have amount %d.', $userId, $amount));
            }
        }

        $now = new DateTimeImmutable();
        $expiredInDays = getCreditTypeExpirationInDays($pdo, $creditTypeId);
        $expiredAt = $expiredInDays !== null
            ? $now->modify(sprintf('+%d days', $expiredInDays))->format(DATETIME_FORMAT)
            : null;

        $query = $pdo->prepare('INSERT INTO transaction (user_id, credit_type_id, amount, expired_at) VALUES (?, ?, ?, ?)');
        $query->execute([
            $userId,
            $creditTypeId,
            $amount,
            $expiredAt
        ]);
        $transactionId = $pdo->lastInsertId();

        if ($amount > 0) {
            // add credits
            $query = $pdo->prepare('INSERT INTO credit (user_id, credit_type_id, amount, remaining_amount, expired_at) VALUES (?, ?, ?, ?, ?)');
            $query->execute([$userId, $creditTypeId, $amount, $amount, $expiredAt]);
            $creditId = $pdo->lastInsertId();
            $query = $pdo->prepare('INSERT INTO credit_transaction (credit_id, transaction_id) VALUES (?, ?)');
            $query->execute([$creditId, $transactionId]);
        } else {
            // use credits
            // TODO: implement this
        }

        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();

        throw $e;
    }
}
