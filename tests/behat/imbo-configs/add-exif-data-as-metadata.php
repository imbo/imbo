<?php
/**
 * Enable the EXIF metadata listener
 */
return [
    'eventListeners' => [
        'exifMetadataListener' => [
            'listener' => 'Imbo\EventListener\ExifMetadata',
            'params' => [
                'allowedTags' => ['exif:*', 'png:*']
            ]
        ]
    ],
];
