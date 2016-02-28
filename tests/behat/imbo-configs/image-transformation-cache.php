<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

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
