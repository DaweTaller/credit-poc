-- clear tables
DELETE FROM credit_audit_log WHERE 1=1;
ALTER TABLE credit_audit_log AUTO_INCREMENT = 1;
DELETE FROM user_credit WHERE 1=1;
ALTER TABLE user_credit AUTO_INCREMENT = 1;
DELETE FROM credit_type WHERE 1=1;
ALTER TABLE credit_type AUTO_INCREMENT = 1;
DELETE FROM user WHERE 1=1;
ALTER TABLE user AUTO_INCREMENT = 1;

-- user
SET @maxUsers = 1000;
SET @lastInsertedId = 0;
SET @transactionsCount = 100000;

WHILE (@lastInsertedId < @maxUsers) DO
    INSERT INTO user(first_name, last_name) VALUES ("Pepa", CONCAT("Novák-", @lastInsertedId));
    SET @lastInsertedId = (SELECT LAST_INSERT_ID());
END WHILE;

-- credit type
INSERT INTO credit_type (name, expiration_in_days, priority)
VALUES ('refund', NULL, 1), ('marketing', 30, 2), ('christmas', 10, 3);

-- creadit_audit_log
SET @lastInsertedId = 0;
SET @creditTypes = 3;
SET @minCredit = -5000;
SET @maxCredit = 5000;
SET @missedIf = 0;

WHILE (@lastInsertedId < @transactionsCount) DO
    SET @userId = FLOOR(1 + (RAND() * @maxUsers));
    SET @creditTypeId = FLOOR(1 + (RAND() * @creditTypes));
    SET @userAmount = (SELECT SUM(amount)
                       FROM credit_audit_log
                       WHERE user_id = @userId
                         AND credit_type_id = @creditTypeId
                         AND (expired_at < NOW() OR expired_at IS NULL));

    IF @userAmount IS NULL THEN
        -- Generate a random number between -5000 and 5000
        SET @amount = FLOOR(RAND() * @maxCredit);
ELSE
        -- Generate a random number between -@userAmount and 5000
        SET @amount = FLOOR(-@userAmount + (RAND() * @maxCredit));
end if;


    if (@userAmount IS NULL AND @amount >= 0) OR (@userAmount + @amount >= 0) THEN
        SET @datetimeCreated = (CURRENT_TIMESTAMP - INTERVAL FLOOR(RAND() * 365 * 1) DAY + INTERVAL FLOOR(RAND() * 24) HOUR + INTERVAL FLOOR(RAND() * 60) MINUTE + INTERVAL FLOOR(RAND() * 60) SECOND);

        -- posun času, '2023-03-26 02:00:00' neexistuje
        if DATE(@datetimeCreated) = '2023-03-26' AND HOUR(@datetimeCreated) = '2' THEN
            SET @datetimeCreated = @datetimeCreated + INTERVAL 1 HOUR;
end if;

        SET @expirateAt = IF (
            @amount > 0,
            (@datetimeCreated + INTERVAL (SELECT expiration_in_days FROM credit_type WHERE id = @creditTypeId) DAY),
            NULL
        );

        -- posun času, '2023-03-26 02:00:00' neexistuje
        if DATE(@expirateAt) = '2023-03-26' AND HOUR(@expirateAt) = '2' THEN
            SET @expirateAt = @expirateAt + INTERVAL 1 HOUR;
end if;

INSERT INTO credit_audit_log (user_id, created_at, credit_type_id, amount, expired_at, expiration_processed) VALUES (
                                                                                                                        @userId,
                                                                                                                        @datetimeCreated,
                                                                                                                        @creditTypeId,
                                                                                                                        @amount,
                                                                                                                        @expirateAt,
                                                                                                                        IF (@expirateAt IS NOT NULL, 'no', 'without-expiration')
                                                                                                                    );
SET @lastInsertedId = (SELECT LAST_INSERT_ID());
INSERT INTO user_credit (user_id, created_at, credit_type_id, amount) VALUES (
                                                                                 @userId,
                                                                                 @datetimeCreated,
                                                                                 @creditTypeId,
                                                                                 @amount
                                                                             ) ON DUPLICATE KEY UPDATE updated_at = @datetimeCreated, amount = amount + @amount;
ELSE
SELECT @userAmount, @amount;
SET @missedIF = @missedIF +1;
end if;
END WHILE;

SELECT @missedIF;
