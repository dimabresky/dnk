CREATE TABLE IF NOT EXISTS b_dnk_stickers_assignment (
    ID int NOT NULL AUTO_INCREMENT,
    IBLOCK_ID int NOT NULL,
    ELEMENT_ID int NOT NULL,
    STICKER_XML_ID varchar(50) NOT NULL,
    ASSIGNED_AT datetime NOT NULL,
    EXPIRES_AT datetime NOT NULL,
    SOURCE varchar(20) NOT NULL DEFAULT '',
    PRIMARY KEY (ID),
    UNIQUE KEY ux_dnk_stickers_element_sticker (ELEMENT_ID, STICKER_XML_ID),
    KEY ix_dnk_stickers_expire (STICKER_XML_ID, EXPIRES_AT),
    KEY ix_dnk_stickers_iblock (IBLOCK_ID)
);
