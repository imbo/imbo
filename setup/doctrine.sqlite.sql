CREATE TABLE IF NOT EXISTS imageinfo (
    id INTEGER PRIMARY KEY NOT NULL,
    publicKey TEXT NOT NULL,
    imageIdentifier TEXT NOT NULL,
    size INTEGER NOT NULL,
    extension TEXT NOT NULL,
    mime TEXT NOT NULL,
    added INTEGER NOT NULL,
    updated INTEGER NOT NULL,
    width INTEGER NOT NULL,
    height INTEGER NOT NULL,
    checksum TEXT NOT NULL,
    UNIQUE (publicKey,imageIdentifier)
);

CREATE TABLE IF NOT EXISTS metadata (
    id INTEGER PRIMARY KEY NOT NULL,
    imageId KEY INTEGER NOT NULL,
    tagName TEXT NOT NULL,
    tagValue TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS shorturl (
    shortUrlId TEXT PRIMARY KEY NOT NULL,
    publicKey TEXT NOT NULL,
    imageIdentifier TEXT NOT NULL,
    extension TEXT,
    query TEXT NOT NULL
);

CREATE INDEX shorturlparams ON shorturl (
    publicKey,
    imageIdentifier,
    extension,
    query
);
