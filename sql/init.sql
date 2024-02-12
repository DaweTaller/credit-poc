-- user
DROP TABLE IF EXISTS user;
CREATE TABLE user (
    id INT(11) UNSIGNED AUTO_INCREMENT NOT NULL PRIMARY KEY,
    first_name VARCHAR(255),
    last_name VARCHAR(255),
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP
) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB;

-- credit type
DROP TABLE IF EXISTS credit_type;
CREATE TABLE credit_type (
    id INT(11) UNSIGNED AUTO_INCREMENT NOT NULL PRIMARY KEY,
    name VARCHAR(255) NOT NULL UNIQUE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP,
    expiration_in_days SMALLINT UNSIGNED,
    expirate_at TIMESTAMP,
    priority SMALLINT NOT NULL COMMENT "Priority in which is credits used. 1 for first use."
) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB;

-- transaction
DROP TABLE IF EXISTS transaction;
CREATE TABLE transaction (
    id INT(11) UNSIGNED AUTO_INCREMENT NOT NULL PRIMARY KEY,
    user_id INT(11) UNSIGNED NOT NULL,
    amount INT(11) NOT NULL,
    type ENUM('regular', 'expiration', 'valid-from') NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    expired_at TIMESTAMP,
    CONSTRAINT fk_credit_audit_log_user_id FOREIGN KEY (user_id)
        REFERENCES user(id) ON DELETE RESTRICT ON UPDATE CASCADE
) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB;

-- credit
DROP TABLE IF EXISTS credit;
CREATE TABLE credit (
    id INT(11) UNSIGNED AUTO_INCREMENT NOT NULL PRIMARY KEY,
    user_id INT(11) UNSIGNED NOT NULL,
    credit_type_id INT(11) UNSIGNED NOT NULL,
    amount INT(11) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP,
    expired_at TIMESTAMP,
    CONSTRAINT fk_credit_user_id FOREIGN KEY (user_id)
      REFERENCES user(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_credit_credit_type_id FOREIGN KEY (credit_type_id)
      REFERENCES credit_type(id) ON DELETE RESTRICT ON UPDATE CASCADE
) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB;

-- transaction_audit
DROP TABLE IF EXISTS transaction_audit;
CREATE TABLE transaction_audit (
    id INT(11) UNSIGNED AUTO_INCREMENT NOT NULL PRIMARY KEY,
    credit_id INT(11) UNSIGNED NOT NULL,
    transaction_id INT(11) UNSIGNED NOT NULL,
    amount INT(11) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_transaction_audit_credit_id FOREIGN KEY (credit_id)
        REFERENCES credit(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_transaction_audit_transaction_id FOREIGN KEY (transaction_id)
        REFERENCES transaction(id) ON DELETE RESTRICT ON UPDATE CASCADE
) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB;


DROP TABLE IF EXISTS request;
CREATE TABLE request (
    id INT(11) UNSIGNED AUTO_INCREMENT NOT NULL PRIMARY KEY,
    request_id VARCHAR(255) NOT NULL UNIQUE,
    referrer VARCHAR(255) NOT NULL,
    user_id INT(11) NOT NULL,
    amount INT(11) NOT NULL,
    credit_type_id INT(11) UNSIGNED,
    transaction_id INT(11) UNSIGNED,
    raw_data json NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP,
    valid_from TIMESTAMP,
    rollback_at TIMESTAMP,
    CONSTRAINT fk_request_transaction_id FOREIGN KEY (transaction_id)
        REFERENCES transaction(id) ON DELETE RESTRICT ON UPDATE CASCADE
) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB;

