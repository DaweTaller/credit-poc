-- get users amount
SELECT user_id, SUM(amount) AS amount
FROM credit
WHERE (expired_at IS NULL OR expired_at > NOW())
GROUP BY user_id
ORDER BY amount ASC;

-- get users amount by credit type
SELECT user_id, credit_type_id, SUM(amount) AS amount
FROM credit
WHERE (expired_at IS NULL OR expired_at > NOW())
GROUP BY user_id, credit_type_id
ORDER BY user_id ASC;

-- get expired amount per user
SELECT user_id, credit_type_id, SUM(amount) AS amount
FROM credit
WHERE (expired_at IS NOT NULL AND expired_at < NOW())
GROUP BY user_id, credit_type_id
ORDER BY user_id ASC;

-- get outcome transaction with payed by more credit types
SET @transactionId = (SELECT transaction_id FROM transaction_audit ct GROUP BY transaction_id HAVING COUNT(transaction_id) > 2 LIMIT 1);
SELECT * FROM transaction WHERE id = @transactionId;
SELECT * FROM transaction_audit WHERE transaction_id = @transactionId;