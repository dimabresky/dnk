-- Очередь генерации FEED_PICTURE (DETAIL_PICTURE + фон #F8F8FC).
-- Выполнить вручную на БД сайта (один раз на окружение).

CREATE TABLE IF NOT EXISTS b_dnk_feed_picture_queue (
    ID int NOT NULL AUTO_INCREMENT,
    ELEMENT_ID int NOT NULL,
    IBLOCK_ID int NOT NULL,
    DETAIL_FILE_ID int NOT NULL,
    STATUS char(1) NOT NULL DEFAULT 'P' COMMENT 'P=pending,W=working,E=error',
    ATTEMPTS int NOT NULL DEFAULT 0,
    LAST_ERROR text DEFAULT NULL,
    DATE_INSERT datetime NOT NULL,
    DATE_UPDATE datetime DEFAULT NULL,
    PRIMARY KEY (ID),
    KEY ix_dnk_fpq_element (ELEMENT_ID),
    KEY ix_dnk_fpq_status (STATUS),
    KEY ix_dnk_fpq_status_id (STATUS, ID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
