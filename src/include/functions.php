<?php

declare(strict_types = 1);

require_once __DIR__ . '/../exception/NotEnoughtCreditsException.php';
require_once __DIR__ . '/../exception/ZeroAmountException.php';

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