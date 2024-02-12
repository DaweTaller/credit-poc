<?php

declare(strict_types = 1);

require_once __DIR__ . '/../../exception/NotEnoughtCreditsException.php';
require_once __DIR__ . '/../../exception/ZeroAmountException.php';
require_once __DIR__ . '/../../exception/EntityNotFoundException.php';
require_once __DIR__ . '/../../exception/ExpiredCreditTypeException.php';
require_once __DIR__ . '/../../enum/TransactionTypeEnum.php';

const DATETIME_FORMAT = 'Y-m-d H:i:s';

function getUserCredit(PDO $pdo, int $userId, ?int $creditTypeId = null): ?int {
    $sql = '
        SELECT SUM(amount)
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

function getCreditTypes(PDO $pdo): array {
    $stmt = $pdo->prepare('SELECT * FROM credit_type');
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getUsers(PDO $pdo): array {
    $stmt = $pdo->prepare('SELECT * FROM user');
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getCreditTypeExpiration(PDO $pdo, int $creditTypeId, ?DateTimeImmutable $now = null): ?DateTimeImmutable {
    $stmt = $pdo->prepare('SELECT expiration_in_days, expirate_at FROM credit_type WHERE id = ?');
    $stmt->execute([$creditTypeId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result === null || ($result['expirate_at'] === null && $result['expiration_in_days'] === null)) {
        return null;
    }


    $now = $now ?? new DateTimeImmutable();
    $expirationInDaysDateTime = null;
    $expiredAtDateTime = null;

    if ($result['expiration_in_days']) {
        $expirationInDaysDateTime = $now->modify(sprintf('+%d days', $result['expiration_in_days']));
    }

    if ($result['expirate_at']) {
        $expiredAtDateTime = new DateTimeImmutable($result['expirate_at']);
    }

    if ($expirationInDaysDateTime !== null && $expiredAtDateTime !== null) {
        return $expiredAtDateTime < $expirationInDaysDateTime ? $expiredAtDateTime : $expirationInDaysDateTime;
    }

    return $expiredAtDateTime ?? $expirationInDaysDateTime;
}

/**
 * @throws EntityNotFoundException
 */
function isCreditTypeExpired(PDO $pdo, int $creditTypeId): bool {
    $stmt = $pdo->prepare('SELECT IF(expirate_at IS NOT NULL AND expirate_at <= NOW(), 1, 0) as isExpired FROM credit_type WHERE id = ?');
    $stmt->execute([$creditTypeId]);
    $result = $stmt->fetchColumn();

    if ($result === false) {
        throw new EntityNotFoundException(sprintf('Credit type with id %d does not exists', $creditTypeId));
    }

    return $result === 1;
}

/**
 * @return array<int, array{
 *     id: int,
 *     creditTypeId: int,
 *     amount: int
 * }>
 */
function getUserCreditsByPriority(PDO $pdo, int $userId): array {
    $query = $pdo->prepare(
        'SELECT c.id, c.credit_type_id AS creditTypeId, amount
        FROM credit c JOIN credit_type ct ON c.credit_type_id = ct.id
        WHERE c.user_id = ?
          AND (c.expired_at IS NULL OR c.expired_at > NOW())
        ORDER BY ct.priority, c.expired_at ASC'
    );

    $query->execute([$userId]);

    $result = $query->fetchAll(PDO::FETCH_ASSOC);

    foreach ($result as &$item) {
        $item['amount'] = intval($item['amount']);
    }

    return $result;
}

/**
 * @throws ZeroAmountException
 * @throws NotEnoughtCreditsException
 */
function useCredit(
    PDO $pdo,
    int $userId,
    int $amount,
    ?DateTimeImmutable $createdAt = null
): int {
    if ($amount <= 0) {
        throw new ZeroAmountException('Cant use credits with zero or less amount');
    }

    processExpirations($pdo, $userId);

    $pdo->beginTransaction();

    try {
        $userCredit = getUserCredit($pdo, $userId);

        if ($userCredit < $amount) {
            throw new NotEnoughtCreditsException(sprintf(
                'User %d does not have %d credits. User has only %d.',
                $userId,
                $amount,
                $userCredit
            ));
        }

        $createdAt = $createdAt ?? new DateTimeImmutable();
        $createdAt = $createdAt->format(DATETIME_FORMAT);

        $query = $pdo->prepare('INSERT INTO transaction (user_id, amount, type, created_at) VALUES (?, ?, ?, ?)');
        $query->execute([
            $userId,
            -$amount,
            TransactionTypeEnum::ACCOUNT_MOVEMENT->value,
            $createdAt,
        ]);
        $transactionId = $pdo->lastInsertId();

        $credits = getUserCreditsByPriority($pdo, $userId);
        $remainingAmount = $amount;

        foreach ($credits as $credit) {
            if ($remainingAmount <= 0) {
                break;
            }

            $creditId = $credit['id'];
            $creditAmount = $credit['amount'];
            $creditTypeId = $credit['creditTypeId'];
            $creditsToUse = $creditAmount >= $remainingAmount ? $remainingAmount : $creditAmount;

            if ($creditsToUse <= 0) {
                continue;
            }

            // updated credit
            $query = $pdo->prepare('UPDATE credit SET amount = amount - ?, updated_at = ? WHERE id = ?');
            $query->execute([$creditsToUse, $createdAt, $creditId]);
            // insert to transaction_audit
            $query = $pdo->prepare('INSERT INTO transaction_audit (credit_id, transaction_id, amount, created_at) VALUES (?, ?, ?, ?)');
            $query->execute([$creditId, $transactionId, -$creditsToUse, $createdAt]);
            $remainingAmount -= $creditsToUse;
        }

        if ($remainingAmount !== 0) {
            throw new Exception(sprintf('Remaining amount is not 0, got %d', $remainingAmount));
        }

        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();

        throw $e;
    }

    return intval($transactionId);
}

/**
 * @throws NotEnoughtCreditsException
 * @throws EntityNotFoundException
 * @throws ZeroAmountException
 * @throws ExpiredCreditTypeException
 * @throws Exception
 */
function addCredit(PDO $pdo, int $userId, int $creditTypeId, int $amount, ?DateTimeImmutable $createdAt = null): int {
    if ($amount <= 0) {
        throw new ZeroAmountException('Cant add credits with zero or less amount');
    }

    if (isCreditTypeExpired($pdo, $creditTypeId)) {
        throw new ExpiredCreditTypeException(sprintf('Credit type %d is expired', $creditTypeId));
    }

    $pdo->beginTransaction();

    try {
        $createdAt = $createdAt ?? new DateTimeImmutable();
        $expiredAt = getCreditTypeExpiration($pdo, $creditTypeId);
        $expiredAt = $expiredAt !== null ? $expiredAt->format(DATETIME_FORMAT) : null;
        $createdAt = $createdAt->format(DATETIME_FORMAT);
        $query = $pdo->prepare('INSERT INTO transaction (user_id, amount, type, created_at, expired_at) VALUES (?, ?, ?, ?, ?)');
        $query->execute([
            $userId,
            $amount,
            TransactionTypeEnum::ACCOUNT_MOVEMENT->value,
            $createdAt,
            $expiredAt
        ]);

        $transactionId = $pdo->lastInsertId();
        $query = $pdo->prepare('INSERT INTO credit (user_id, credit_type_id, amount, created_at, expired_at) VALUES (?, ?, ?, ?, ?)');
        $query->execute([$userId, $creditTypeId, $amount, $createdAt, $expiredAt]);
        $creditId = $pdo->lastInsertId();
        $query = $pdo->prepare('INSERT INTO transaction_audit (credit_id, transaction_id, amount, created_at) VALUES (?, ?, ?, ?)');
        $query->execute([$creditId, $transactionId, $amount, $createdAt]);

        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();

        throw $e;
    }

    return intval($transactionId);
}

function processExpirations(PDO $pdo, ?int $userId = null) {
    // TODO: OR SUM on transaction_audit
    $sql = "SELECT id, user_id, amount FROM credit WHERE expired_at <= NOW() AND amount > 0";

    $parameters = [];
    if ($userId !== null) {
        $sql .= ' AND user_id = ?';
        $parameters[] = $userId;
    }

    $query = $pdo->prepare($sql);
    $query->execute($parameters);
    $results = $query->fetchAll(PDO::FETCH_ASSOC);
    $createdAt = (new DateTimeImmutable())->format(DATETIME_FORMAT);

    foreach ($results as $result) {
        $pdo->beginTransaction();
            $creditId = $result['id'];
            $amount = $result['amount'];
            $userId = $result['user_id'];
            $query = $pdo->prepare('INSERT INTO transaction (user_id, amount, type, created_at) VALUES (?, ?, ?, ?)');
            $query->execute([
                $userId,
                -$amount,
                TransactionTypeEnum::CREDIT_EXPIRATION->value,
                $createdAt,
            ]);
            $transactionId = $pdo->lastInsertId();

            $query = $pdo->prepare('UPDATE credit SET amount = 0, updated_at = ? WHERE id = ?');
            $query->execute([$createdAt, $creditId]);
            $query = $pdo->prepare('INSERT INTO transaction_audit (credit_id, transaction_id, amount, created_at) VALUES (?, ?, ?, ?)');
            $query->execute([$creditId, $transactionId, -$amount, $createdAt]);
        $pdo->commit();
    }
}

function setExpirationOnCredit(PDO $pdo, int $creditId, DateTimeImmutable $expiration = null) {
    $expiration = $expiration ?? new DateTimeImmutable();

    $query = $pdo->prepare('UPDATE credit SET expired_at = ? WHERE id = ?');
    $query->execute([$expiration->format(DATETIME_FORMAT), $creditId]);
}

function createTransactionRequest(
    PDO $pdo,
    string $requestId,
    int $userId,
    string $referrer,
    int $amount,
    ?int $creditTypeId,
    array $additionalData
): void {
    $query = $pdo->prepare('INSERT INTO request (request_id, user_id, referrer, amount, credit_type_id, additional_data) VALUES (?, ?, ?, ?, ?, ?)');
    $query->execute([$requestId, $userId, $referrer, $amount, $creditTypeId, json_encode($additionalData)]);
}

/**
 * @throws EntityNotFoundException
 * @throws RequestAlreadyHaveTransaction
 */
function setTransactionIdToRequest(PDO $pdo, string $requestId, int $transactionId) {
    $query = $pdo->prepare('SELECT transaction_id FROM request WHERE request_id = ?');
    $query->execute([$requestId]);
    $existingTransactionId = $query->fetchColumn();

    if ($existingTransactionId === false) {
        throw new EntityNotFoundException(sprintf('Request entity by request id %s not found', $requestId));
    }

    if ($existingTransactionId !== null) {
        throw new RequestAlreadyHaveTransaction(sprintf('Request with request id %s already has transaction id %s', $requestId, $transactionId));
    }

    $query = $pdo->prepare('UPDATE request SET transaction_id = ?, updated_at = ? WHERE request_id = ?');
    $query->execute([$transactionId, (new DateTimeImmutable())->format(DATETIME_FORMAT), $requestId]);
}

function generateRequestId(): string {
    return uniqid();
}

function getRandomReferrer(): string {
    $referrers = [
        'ftmo.com', 'shop.ftmo.com', 'fapi.ftmo.com', 'affiliate.ftmo.com'
    ];

    return $referrers[rand(0, count($referrers) - 1)];
}


/**
 * @throws Exception
 */
function generateTransactions(PDO $pdo, int $numberOfTransactions, int $minCredit, int $maxCredit, bool $printProcess = false) {
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
        $creditTypeId = rand(1, $maxCreditTypeId);

        if(isCreditTypeExpired($pdo, $creditTypeId)) {
            continue;
        }

        $userId = rand(1, $maxUserId);
        $userCredit = getUserCredit($pdo, $userId, $creditTypeId);
        $amount = $userCredit === 0
            ? rand(0, $maxCredit)
            : rand(-$userCredit, $maxCredit);

        if ($userCredit + $amount >= 0) {
            $datetimeCreated = (new DateTimeImmutable())
                ->modify('-' . rand(0, 365 * 2) . ' day')
                ->setTime(rand(0, 23), rand(0, 59), rand(0, 59));

            try {
                $requestId = generateRequestId();
                $referrer = getRandomReferrer();
                $additionalData = [
                    'requestId' => $requestId,
                    'referrer' => $referrer,
                    'userId' => $userId,
                    'amount' => $amount,
                    'creditTypeId' => $creditTypeId,
                ];
                createTransactionRequest($pdo, $requestId, $userId, $referrer,$amount, $creditTypeId, $additionalData);

                if ($amount > 0) {
                    $transactionId = addCredit($pdo, $userId, $creditTypeId, $amount, $datetimeCreated);
                } else {
                    $transactionId = useCredit($pdo, $userId, abs($amount), $datetimeCreated);
                }

                setTransactionIdToRequest($pdo, $requestId, $transactionId);

                if ($printProcess && $transactionToProcess !== $numberOfTransactions && $transactionToProcess % 100 === 0) {
                    printProcessedTransactions($transactionToProcess, $numberOfTransactions);
                }

                $transactionToProcess--;
            } catch (NotEnoughtCreditsException | ZeroAmountException $e) {
                if ($printProcess) {
                    echo $e->getMessage() . PHP_EOL;
                }
            }
        }
    }

    if ($printProcess) {
        printProcessedTransactions($transactionToProcess, $numberOfTransactions);
    }
}

function printProcessedTransactions(int $transactionToProcess, int $totalTransactions) {
    $filled = $totalTransactions - $transactionToProcess;

    echo sprintf(
        'Filled %d/%d transactions.' . PHP_EOL,
        $filled,
        $totalTransactions
    );
}