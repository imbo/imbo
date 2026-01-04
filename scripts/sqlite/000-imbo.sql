DROP TABLE IF EXISTS imageinfo;
DROP TABLE IF EXISTS shorturl;
DROP TABLE IF EXISTS imagevariations;

CREATE TABLE imageinfo (
    id INTEGER PRIMARY KEY NOT NULL,
    user TEXT NOT NULL,
    imageIdentifier TEXT NOT NULL,
    size INTEGER NOT NULL,
    extension TEXT NOT NULL,
    mime TEXT NOT NULL,
    added INTEGER NOT NULL,
    updated INTEGER NOT NULL,
    width INTEGER NOT NULL,
    height INTEGER NOT NULL,
    checksum TEXT NOT NULL,
    originalChecksum TEXT NOT NULL,
    metadata TEXT,
    UNIQUE (user,imageIdentifier)
);

CREATE TABLE shorturl (
    shortUrlId TEXT PRIMARY KEY NOT NULL,
    user TEXT NOT NULL,
    imageIdentifier TEXT NOT NULL,
    extension TEXT,
    query TEXT NOT NULL
);

CREATE INDEX shorturlparams ON shorturl (
    user,
    imageIdentifier,
    extension,
    query
);

CREATE TABLE imagevariations (
    user TEXT NOT NULL,
    imageIdentifier TEXT NOT NULL,
    width INTEGER NOT NULL,
    height INTEGER NOT NULL,
    added INTEGER NOT NULL,
    PRIMARY KEY (user,imageIdentifier,width)
);
