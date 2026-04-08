-- Очередь запросов бонусного баланса по телефону после авторизации/регистрации.
-- Выполнить вручную на БД сайта (один раз на окружение).

CREATE TABLE IF NOT EXISTS b_dnk_bonus_balance_queue (
    ID int NOT NULL AUTO_INCREMENT,
    USER_ID int NOT NULL,
    STATUS char(1) NOT NULL DEFAULT 'P' COMMENT 'P=pending,E=error',
    ATTEMPTS int NOT NULL DEFAULT 0,
    LAST_ERROR text DEFAULT NULL,
    DATE_INSERT datetime NOT NULL,
    DATE_UPDATE datetime DEFAULT NULL,
    PRIMARY KEY (ID),
    UNIQUE KEY ux_dnk_bbq_user (USER_ID),
    KEY ix_dnk_bbq_status (STATUS)
) ENGINE=InnoDB;
