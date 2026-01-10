<?php declare(strict_types=1);

namespace Imbo\Behat;

use Imbo\EventListener\ImageTransformationCache;

use const DIRECTORY_SEPARATOR;

/**
 * Enable the image transformation metadata cache listener,
 * and store the cached images to a temporary directory.
 */
$tmpDir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'imbo-behat-image-transformation-cache';

return [
    'eventListeners' => [
        'imageTransformationCache' => [
            'listener' => ImageTransformationCache::class,
            'params' => [
                'path' => $tmpDir,
            ],
        ],
    ],
];
