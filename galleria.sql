DROP TABLE IF EXISTS galleries;
DROP TABLE IF EXISTS pictures;

CREATE TABLE galleries (
    g_id serial primary key,
    g_dir varchar(100) not null,
    g_name varchar(100) not null,
    g_url varchar(100) not null,
    updated int not null default 0
    );

CREATE TABLE pictures (
    p_id serial primary key,
    g_id int not null,
    updated int not null default 0,
    thumb varchar(100) not null,
    image varchar(100) not null,
    big varchar(100),
    title varchar(100),
    description text
    );

DELIMITER $$

CREATE TRIGGER after_insert_pictures
    AFTER INSERT ON pictures FOR EACH ROW
    BEGIN
        UPDATE galleries SET updated = NEW.updated WHERE g_id = NEW.g_id;
    END$$

DELIMITER ;
