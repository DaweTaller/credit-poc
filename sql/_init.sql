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
    priority SMALLINT NOT NULL COMMENT "Priority in which is credits used. 1 for first use.",
    active ENUM('yes', 'no') DEFAULT 'yes' NOT NULL
) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB;

-- credit audit log
DROP TABLE IF EXISTS credit_audit_log;
CREATE TABLE credit_audit_log (
    id INT(11) UNSIGNED AUTO_INCREMENT NOT NULL PRIMARY KEY,
    user_id INT(11) UNSIGNED NOT NULL,
    credit_type_id INT(11) UNSIGNED NOT NULL,
    amount INT(11) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    expired_at TIMESTAMP,
    note TEXT,
    CONSTRAINT fk_credit_audit_log_user_id FOREIGN KEY (user_id)
        REFERENCES user(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_credit_audit_log_credit_type_id FOREIGN KEY (credit_type_id)
        REFERENCES credit_type(id) ON DELETE RESTRICT ON UPDATE CASCADE
) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB;

-- user_credit
DROP TABLE IF EXISTS user_credit;
CREATE TABLE user_credit (
    id INT(11) UNSIGNED AUTO_INCREMENT NOT NULL PRIMARY KEY,
    user_id INT(11) UNSIGNED NOT NULL,
    credit_type_id INT(11) UNSIGNED NOT NULL,
    amount INT(11) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP,
    CONSTRAINT fk_user_credit_user_id FOREIGN KEY (user_id)
      REFERENCES user(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_user_credit_credit_type_id FOREIGN KEY (credit_type_id)
      REFERENCES credit_type(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT uq_user_credit_credit_type UNIQUE (user_id, credit_type_id)
) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB;
