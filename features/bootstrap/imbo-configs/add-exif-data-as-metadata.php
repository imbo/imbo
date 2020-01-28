<?php declare(strict_types=1);
namespace Imbo\Behat;

use Imbo\EventListener\ExifMetadata;

return [
    'eventListeners' => [
        'exifMetadataListener' => [
            'listener' => ExifMetadata::class,
            'params' => [
                'allowedTags' => ['exif:*', 'png:*'],
            ],
        ],
    ],
];
