-- Очередь пользователей, которых нужно переавторизовать при следующем запросе.
-- Выполнить вручную на БД сайта (один раз на окружение).

CREATE TABLE IF NOT EXISTS b_dnk_user_reauthorize_queue (
    ID int NOT NULL AUTO_INCREMENT,
    USER_ID int NOT NULL,
    PRIMARY KEY (ID),
    UNIQUE KEY ux_dnk_urq_user (USER_ID)
) ENGINE=InnoDB;
