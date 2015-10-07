CREATE TABLE IF NOT EXISTS `imageinfo` (
    `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `user` varchar(255) COLLATE utf8_danish_ci NOT NULL,
    `imageIdentifier` varchar(255) COLLATE utf8_danish_ci NOT NULL,
    `size` int(10) unsigned NOT NULL,
    `extension` varchar(5) COLLATE utf8_danish_ci NOT NULL,
    `mime` varchar(20) COLLATE utf8_danish_ci NOT NULL,
    `added` int(10) unsigned NOT NULL,
    `updated` int(10) unsigned NOT NULL,
    `width` int(10) unsigned NOT NULL,
    `height` int(10) unsigned NOT NULL,
    `checksum` char(32) COLLATE utf8_danish_ci NOT NULL,
    `originalChecksum` char(32) COLLATE utf8_danish_ci NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `image` (`user`,`imageIdentifier`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_danish_ci AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `metadata` (
    `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `imageId` int(10) unsigned NOT NULL,
    `tagName` varchar(255) COLLATE utf8_danish_ci NOT NULL,
    `tagValue` varchar(255) COLLATE utf8_danish_ci NOT NULL,
    PRIMARY KEY (`id`),
    KEY `imageId` (`imageId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_danish_ci AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `shorturl` (
    `shortUrlId` char(7) COLLATE utf8_danish_ci NOT NULL,
    `user` varchar(255) COLLATE utf8_danish_ci NOT NULL,
    `imageIdentifier` varchar(255) COLLATE utf8_danish_ci NOT NULL,
    `extension` char(3) COLLATE utf8_danish_ci DEFAULT NULL,
    `query` text COLLATE utf8_danish_ci NOT NULL,
    PRIMARY KEY (`shortUrlId`),
    KEY `params` (`user`,`imageIdentifier`,`extension`,`query`(255))
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_danish_ci;

CREATE TABLE IF NOT EXISTS `storage_images` (
    `user` varchar(255) COLLATE utf8_danish_ci NOT NULL,
    `imageIdentifier` varchar(255) COLLATE utf8_danish_ci NOT NULL,
    `data` blob NOT NULL,
    `updated` int(10) unsigned NOT NULL,
    PRIMARY KEY (`user`,`imageIdentifier`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_danish_ci;

CREATE TABLE IF NOT EXISTS `storage_image_variations` (
    `user` varchar(255) COLLATE utf8_danish_ci NOT NULL,
    `imageIdentifier` varchar(255) COLLATE utf8_danish_ci NOT NULL,
    `width` int(10) unsigned NOT NULL,
    `data` blob NOT NULL,
    PRIMARY KEY (`user`,`imageIdentifier`,`width`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_danish_ci;

CREATE TABLE IF NOT EXISTS `imagevariations` (
    `user` varchar(255) COLLATE utf8_danish_ci NOT NULL,
    `imageIdentifier` varchar(255) COLLATE utf8_danish_ci NOT NULL,
    `width` int(10) unsigned NOT NULL,
    `height` int(10) unsigned NOT NULL,
    `added` int(10) unsigned NOT NULL,
    PRIMARY KEY (`user`,`imageIdentifier`,`width`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_danish_ci;
