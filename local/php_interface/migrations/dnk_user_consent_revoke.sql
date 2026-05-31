-- Реестр отозванных пользовательских соглашений (дополнение к b_user_consent).
-- Выполнить вручную на БД сайта (один раз на окружение).

CREATE TABLE IF NOT EXISTS b_dnk_user_consent_revoke (
    ID int NOT NULL AUTO_INCREMENT,
    USER_ID int NOT NULL,
    AGREEMENT_ID int NOT NULL,
    DATE_REVOKE datetime NOT NULL,
    PRIMARY KEY (ID),
    UNIQUE KEY ux_dnk_ucr_user_agreement (USER_ID, AGREEMENT_ID),
    KEY ix_dnk_ucr_user (USER_ID)
) ENGINE=InnoDB;
