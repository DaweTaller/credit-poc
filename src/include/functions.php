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
 * @return array<int, array{
 *     id: int,
 *     creditTypeId: int,
 *     amount: int
 * }>
 */
function getUserCreditsByPriority(PDO $pdo, int $userId): array {
    $query = $pdo->prepare(
        'SELECT c.id, c.credit_type_id AS creditTypeId, remaining_amount AS amount
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
function addTransaction(PDO $pdo, int $userId, int $creditTypeId, int $amount) {
    if ($amount === 0) {
        throw new ZeroAmountException('Cant add transaction with zero amount');
    }

    $pdo->beginTransaction();

    try {
        if ($amount < 0) {
            $userCredit = getUserCredit($pdo, $userId, $creditTypeId);

            if ($userCredit < abs($amount)) {
                throw new NotEnoughtCreditsException(sprintf('User %d does not have amount %d.', $userId, abs($amount)));
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
            $credits = getUserCreditsByPriority($pdo, $userId);
            $remainingAmount = abs($amount);

            foreach ($credits as $credit) {
                if ($remainingAmount <= 0) {
                    break;
                }

                $creditId = $credit['id'];
                $creditAmount = $credit['amount'];
                $creditTypeId = $credit['creditTypeId'];
                $creditsToUse = $creditAmount >= $remainingAmount ? $remainingAmount : $creditAmount;

                // updated credit
                $query = $pdo->prepare('UPDATE credit SET remaining_amount = remaining_amount - ? WHERE id = ?');
                $query->execute([$creditsToUse, $creditId]);

                // insert to credit_transaction
                $query = $pdo->prepare('INSERT INTO credit_transaction (credit_id, transaction_id) VALUES (?, ?)');
                $query->execute([$creditId, $transactionId]);
                $remainingAmount -= $creditsToUse;
            }

            if ($remainingAmount !== 0) {
                throw new Exception(sprintf('Remaining amount is not 0, got %d', $remainingAmount));
            }
        }

        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();

        throw $e;
    }
}