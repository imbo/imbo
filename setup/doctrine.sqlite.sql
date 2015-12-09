CREATE TABLE IF NOT EXISTS imageinfo (
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
    UNIQUE (user,imageIdentifier)
);

CREATE TABLE IF NOT EXISTS metadata (
    id INTEGER PRIMARY KEY NOT NULL,
    imageId KEY INTEGER NOT NULL,
    tagName TEXT NOT NULL,
    tagValue TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS shorturl (
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

CREATE TABLE IF NOT EXISTS storage_images (
    user TEXT NOT NULL,
    imageIdentifier TEXT NOT NULL,
    data BLOB NOT NULL,
    updated INTEGER NOT NULL,
    PRIMARY KEY (user,imageIdentifier)
);

CREATE TABLE IF NOT EXISTS storage_image_variations (
    user TEXT NOT NULL,
    imageIdentifier TEXT NOT NULL,
    width INTEGER NOT NULL,
    data BLOB NOT NULL,
    PRIMARY KEY (user,imageIdentifier,width)
);

CREATE TABLE IF NOT EXISTS imagevariations (
    user TEXT NOT NULL,
    imageIdentifier TEXT NOT NULL,
    width INTEGER NOT NULL,
    height INTEGER NOT NULL,
    added INTEGER NOT NULL,
    PRIMARY KEY (user,imageIdentifier,width)
);
