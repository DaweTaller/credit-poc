-- GET USER CREDIT from user_credit table
SET @userId = 10;
SET @creditTypeId = 1;
SELECT amount FROM user_credit WHERE user_id = @userId AND credit_type_id = @creditTypeId;

-- GET USE CREDIT from credit_audit_log
SET @userId = 10;
SET @creditTypeId = 1;
SELECT SUM(amount) FROM credit_audit_log WHERE user_id = @userId AND credit_type_id = @creditTypeId;

-- ADD CREDIT
START TRANSACTION;
SET @expiredAmount = 500;
    SET @userId = 10;
    SET @creditTypeId = 1;
    SET @now = NOW();
    SET @expirateInDays = (SELECT expiration_in_days FROM credit_type WHERE id = @creditTypeId);
    SET @expirationDate = IF(@expirateInDays IS NOT NULL, @now + INTERVAL @expirateInDays DAY, NULL);

INSERT INTO credit_audit_log (user_id, credit_type_id, amount, expired_at, expiration_processed) VALUES (
                                                                                                            @userId,
                                                                                                            @creditTypeId,
                                                                                                            @expiredAmount,
                                                                                                            @expirationDate,
                                                                                                            IF (@expirationDate IS NOT NULL, 'no', 'without-expiration')
                                                                                                        );
INSERT INTO user_credit (user_id, credit_type_id, amount) VALUES (
                                                                     @userId,
                                                                     @creditTypeId,
                                                                     @expiredAmount
                                                                 ) ON DUPLICATE KEY UPDATE amount = amount + @expiredAmount,updated_at = @now;
COMMIT;

-- USE CREDIT by priority
START TRANSACTION;
SET @expiredAmount = -500;
    SET @userId = 10;
    SET @now = NOW();
    SET @hasAmount = (SELECT SUM(amount) >= ABS(@expiredAmount)FROM user_credit WHERE user_id = @userId);

    IF @hasAmount = 0 THEN
SELECT CONCAT("User ", @userId, " does not have available credits for amount ", ABS(@expiredAmount));
ELSE
        -- FIRST CREDIT TYPE
        SET @creditTypeId = (
            SELECT credit_type_id
            FROM user_credit uc JOIN credit_type ct ON uc.credit_type_id = ct.id
            WHERE user_id = @userId
            ORDER BY ct.priority
            LIMIT 1
        );

        IF @creditTypeId IS NOT NULL THEN
            SET @creditTypeAmount = (SELECT amount FROM user_credit WHERE user_id = @userId AND credit_type_id = @creditTypeId);

            IF @creditTypeAmount > 0 AND @creditTypeAmount >= ABS(@expiredAmount) THEN
                -- we have enought credits to resolve whole amount
                INSERT INTO credit_audit_log (user_id, credit_type_id, amount) VALUES (
                    @userId,
                    @creditTypeId,
                    @expiredAmount
                );
INSERT INTO user_credit (user_id, credit_type_id, amount) VALUES (
                                                                     @userId,
                                                                     @creditTypeId,
                                                                     @expiredAmount
                                                                 ) ON DUPLICATE KEY UPDATE amount = amount + @expiredAmount, updated_at = @now;
ELSE
                -- we have less amount for in one credit type
                INSERT INTO credit_audit_log (user_id, credit_type_id, amount) VALUES (
                    @userId,
                    @creditTypeId,
                    -@creditTypeAmount
                );
                -- TODO: test if no update its wierd
UPDATE user_credit SET amount = amount - @creditTypeAmount, updated_at = @now WHERE user_id = @userId AND credit_type_id = @creditTypeId;

SET @expiredAmount = @expiredAmount + @creditTypeAmount;

                -- NEXT CREDIT TYPE
                SET @creditTypeId = (
                    SELECT credit_type_id
                    FROM user_credit uc JOIN credit_type ct ON uc.credit_type_id = ct.id
                    WHERE user_id = @userId
                    ORDER BY ct.priority
                    LIMIT 1, 1
                );

                IF @creditTypeId IS NOT NULL THEN
                    SET @creditTypeAmount = (SELECT amount FROM user_credit WHERE user_id = @userId AND credit_type_id = @creditTypeId);

                    IF @creditTypeAmount > 0 AND @creditTypeAmount >= ABS(@expiredAmount) THEN
                        -- we have enought credits to resolve whole amount
                        INSERT INTO credit_audit_log (user_id, credit_type_id, amount) VALUES (
                            @userId,
                            @creditTypeId,
                            @expiredAmount
                        );
INSERT INTO user_credit (user_id, credit_type_id, amount) VALUES (
                                                                     @userId,
                                                                     @creditTypeId,
                                                                     @expiredAmount
                                                                 ) ON DUPLICATE KEY UPDATE amount = amount + @expiredAmount, updated_at = @now;
ELSE
                        -- we have less amount for in one credit type
                        INSERT INTO credit_audit_log (user_id, credit_type_id, amount) VALUES (
                            @userId,
                            @creditTypeId,
                            -@creditTypeAmount
                        );
INSERT INTO user_credit (user_id, credit_type_id, amount) VALUES (
                                                                     @userId,
                                                                     @creditTypeId,
                                                                     @creditTypeAmount
                                                                 ) ON DUPLICATE KEY UPDATE amount = amount - @creditTypeAmount, updated_at = @now;

SET @expiredAmount = @expiredAmount + @creditTypeAmount;

                        IF @expiredAmount < 0 THEN
                            -- NEXT CREDIT TYPE
                            SET @creditTypeId = (
                                SELECT credit_type_id
                                FROM user_credit uc JOIN credit_type ct ON uc.credit_type_id = ct.id
                                WHERE user_id = @userId
                                ORDER BY ct.priority
                                LIMIT 2, 1
                            );

                            IF @creditTypeId IS NOT NULL THEN
                                SET @creditTypeAmount = (SELECT amount FROM user_credit WHERE user_id = @userId AND credit_type_id = @creditTypeId);

                                IF @creditTypeAmount >= ABS(@expiredAmount) THEN
                                    -- we have enought credits to resolve whole amount
                                    INSERT INTO credit_audit_log (user_id, credit_type_id, amount) VALUES (
                                        @userId,
                                        @creditTypeId,
                                        @expiredAmount
                                    );
INSERT INTO user_credit (user_id, credit_type_id, amount) VALUES (
                                                                     @userId,
                                                                     @creditTypeId,
                                                                     @expiredAmount
                                                                 ) ON DUPLICATE KEY UPDATE amount = amount + @expiredAmount, updated_at = @now;
ELSE
SELECT 'We dont have enought money for resolve whole amount?!';
END IF;
end if;
end if;
end if;
end if;
end if;
end if;
end if;
COMMIT;

-- EXPIRE credits
START TRANSACTION;
-- SELECT * FROM credit_audit_log WHERE expired_at <= NOW() AND expiration_processed = 'no' ORDER BY expired_at LIMIT 1;

SET @transactionId = (SELECT * FROM credit_audit_log WHERE expired_at <= NOW() AND expiration_processed = 'no' ORDER BY expired_at LIMIT 1);
    SET @createdAt = (SELECT created_at FROM credit_audit_log WHERE id = @transactionId);
    SET @expiredAt = (SELECT expired_at FROM credit_audit_log WHERE id = @transactionId);
    SET @expiredAmount = (SELECT amount FROM credit_audit_log WHERE id = @transactionId);
    SET @creditTypeId = (SELECT credit_type_id FROM credit_audit_log WHERE id = @transactionId);
    SET @userId = (SELECT user_id FROM credit_audit_log WHERE id = @transactionId);

-- transactionId: 38912
-- amount: 1759
-- userId: 548
-- amount: 2824,-
-- UPDATE credit_audit_log SET amount = 40000 WHERE id = 38912;

-- SELECT * FROM user_credit WHERE user_id = 673; -- 2334
SELECT cal.*, SUM(amount) OVER (ORDER BY created_at) AS spent
FROM credit_audit_log cal
WHERE user_id = 875
  AND credit_type_id = 2
--  AND created_at >= @createdAt
--          AND created_at <= @expiredAt
-- AND amount < 0
ORDER BY created_at;

SET @totalSpended = (SELECT aggr.spent FROM (
        SELECT cal.*, SUM(amount) OVER (ORDER BY created_at) AS spent
        FROM credit_audit_log cal
        WHERE user_id = @userId
          AND credit_type_id = @creditTypeId
          AND created_at >= @createdAt
--          AND created_at <= @expiredAt
         -- AND amount < 0
        ORDER BY created_at
    ) as aggr ORDER BY created_at DESC LIMIT 1);

    IF ABS(@totalSpended) >= @expiredAmount THEN
        -- spent all, set transaction as processed
UPDATE credit_audit_log SET expiration_processed = 'yes' WHERE id = @transactionId;
ELSE
        -- does not spent all
        -- resolve that user does not have this amount on wallet
        SET @amountToExpire = @expiredAmount - ABS(@totalSpended);
        SET @actualAmount = (SELECT amount FROM user_credit WHERE user_id = @userId AND credit_type_id = @creditTypeId);

        IF @actualAmount <= @amountToExpire THEN
            SET @amountDiff = -@actualAmount;
ELSE
            SET @amountDiff = -@amountToExpire;
end if;

        INSERT credit_audit_log (user_id, credit_type_id, amount, expiration_processed, note)
            VALUES (@userId, @creditTypeId, @amountDiff, 'without-expiration', CONCAT('Expired from ', @transactionId, ' transaction.'));
UPDATE user_credit SET amount = amount - @amountDiff, updated_at = NOW() WHERE user_id = @userId AND credit_type_id = @creditTypeId;
UPDATE credit_audit_log SET expiration_processed = 'yes' WHERE id = @transactionId;
end if;
COMMIT;


-- new solution

-- 1. aktuální stav
SELECT user_id, credit_type_id, SUM(amount) AS amount
FROM credit_audit_log
WHERE ((expired_at < NOW() AND amount > 0) OR expired_at IS NULL)
GROUP BY user_id, credit_type_id;

-- 2. jestli byl kredit vyčerpanej a kolik případně zbylo
SET @transactionId = 3231;
SET @userId = (select user_id FROM credit_audit_log WHERE id = @transactionId);;
SET @transactionCreated = (select created_at FROM credit_audit_log WHERE id = @transactionId);
SET @transactionAmount = (select amount FROM credit_audit_log WHERE id = @transactionId);
SET @transactionExpired = (select expired_at FROM credit_audit_log WHERE id = @transactionId);
SET @transactionCreditType = (select credit_type_id FROM credit_audit_log WHERE id = @transactionId);

SELECT user_id,
       credit_type_id,
       @transactionCreated AS createdAt,
       @transactionExpired AS expiredAt,
       @transactionAmount AS transactionAmount,
       IF(@transactionAmount + SUM(amount) > 0, @transactionAmount + SUM(amount), 0) AS creditRemain,
       SUM(amount) AS spent
FROM credit_audit_log
WHERE credit_type_id = @transactionCreditType
  AND user_id = @userId
  AND amount < 0
  AND created_at >= @transactionCreated AND created_at < @transactionExpired;

-- 3. jestli byl kredit vyčerpanej a kolik případně zbylo i když jsou dva po sobě



