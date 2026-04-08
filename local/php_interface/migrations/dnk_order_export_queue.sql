-- Очередь экспорта JSON заказов на внешний endpoint.
-- Выполнить вручную на БД сайта (один раз на окружение).

CREATE TABLE IF NOT EXISTS b_dnk_order_export_queue (
    ID int NOT NULL AUTO_INCREMENT,
    ORDER_ID int NOT NULL,
    PAYLOAD longtext NOT NULL,
    STATUS char(1) NOT NULL DEFAULT 'P' COMMENT 'P=pending,S=sent,E=error',
    ATTEMPTS int NOT NULL DEFAULT 0,
    LAST_ERROR text DEFAULT NULL,
    DATE_INSERT datetime NOT NULL,
    DATE_UPDATE datetime DEFAULT NULL,
    PRIMARY KEY (ID),
    KEY ix_dnk_oexq_order (ORDER_ID),
    KEY ix_dnk_oexq_status (STATUS)
) ENGINE=InnoDB 
