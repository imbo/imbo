<?php declare(strict_types=1);
/**
 * Enable the image transformation metadata cache listener,
 * and store the cached images to a temporary directory
 */

$tmpDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'imbo-behat-image-transformation-cache';

return [
    'eventListeners' => [
        'imageTransformationCache' => [
            'listener' => 'Imbo\EventListener\ImageTransformationCache',
            'params' => [
                'path' => $tmpDir
            ],
        ],
    ],
];
