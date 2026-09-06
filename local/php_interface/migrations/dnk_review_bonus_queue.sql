-- Очередь начисления бонусов за отзывы (POST JSON на внешний endpoint).
-- Выполнить вручную на БД сайта (однократно на каждое окружение).

CREATE TABLE IF NOT EXISTS b_dnk_review_bonus_queue (
    ID              int NOT NULL AUTO_INCREMENT,
    USER_ID         int NOT NULL,
    STATUS          char(1) NOT NULL DEFAULT 'P' COMMENT 'P=pending,S=sent,E=error',
    ATTEMPTS        int NOT NULL DEFAULT 0,
    LAST_ERROR      text DEFAULT NULL,
    DATE_LAST_SENT  datetime DEFAULT NULL COMMENT 'Момент последней успешной отправки',
    DATE_INSERT     datetime NOT NULL,
    DATE_UPDATE     datetime DEFAULT NULL,
    PRIMARY KEY (ID),
    UNIQUE KEY ux_dnk_rbxq_user (USER_ID),
    KEY ix_dnk_rbxq_status (STATUS)
) ENGINE=InnoDB;
