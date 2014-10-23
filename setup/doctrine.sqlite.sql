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
    originalChecksum TEXT NOT NULL,
    UNIQUE (publicKey,imageIdentifier)
);

CREATE TABLE IF NOT EXISTS metadata (
    id INTEGER PRIMARY KEY NOT NULL,
    imageId KEY INTEGER NOT NULL,
    tagName TEXT NOT NULL COLLATE NOCASE,
    tagValue TEXT NOT NULL COLLATE NOCASE
);

CREATE INDEX metadatatagname ON metadata (tagName);
CREATE INDEX metadatatagvalue ON metadata (tagValue);

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

CREATE TABLE IF NOT EXISTS storage_images (
    publicKey TEXT NOT NULL,
    imageIdentifier TEXT NOT NULL,
    data BLOB NOT NULL,
    updated INTEGER NOT NULL,
    PRIMARY KEY (publicKey,imageIdentifier)
);
