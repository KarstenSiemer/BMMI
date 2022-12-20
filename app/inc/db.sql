CREATE OR REPLACE TABLE videos (
    id SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
    title VARCHAR(25) NOT NULL,
    filename VARCHAR(25) NOT NULL,
    filetype VARCHAR(25) NOT NULL,
    filesize INT NOT NULL,
    duration SMALLINT NOT NULL,
    actors VARCHAR(25) NOT NULL,
    content LONGBLOB NOT NULL,
    PRIMARY KEY (id)
);
