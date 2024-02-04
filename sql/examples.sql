-- add credit
START TRANSACTION;
    SET @amount = 500;
    SET @userId = 10;
    SET @creditTypeId = 1;

    SET @expirateInDays = (SELECT expiration_in_days FROM credit_type WHERE id = @creditTypeId);

    INSERT INTO credit_audit_log (user_id, credit_type_id, amount, expired_at) VALUES (
        @userId,
        @creditTypeId,
        @amount,
        IF(@expirateInDays IS NOT NULL, NOW() + INTERVAL @expirateInDays DAY, NULL)
    );
    INSERT INTO user_credit (user_id, credit_type_id, amount) VALUES (
        @userId,
        @creditTypeId,
        @amount
    ) ON DUPLICATE KEY UPDATE amount = amount + @amount, updated_at = NOW();
COMMIT;
