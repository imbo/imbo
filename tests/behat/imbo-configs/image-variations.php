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
 * Enable the image variations event listener
 */
return [
    'eventListeners' => [
        'imageVariationsListener' => [
            'listener' => 'Imbo\EventListener\ImageVariations',
            'params' => [
                'database' => [
                    'adapter' => 'Imbo\EventListener\ImageVariations\Database\MongoDB',
                    'params'  => ['databaseName' => 'imbo_testing'],
                ],
                'storage' => [
                    'adapter' => 'Imbo\EventListener\ImageVariations\Storage\GridFS',
                    'params'  => ['databaseName' => 'imbo_testing'],
                ],
                'widths'    => [320],
                'minWidth'  => 100,
                'maxWidth'  => 2048,
                'minDiff'   => 100,
                'autoScale' => true,
                'lossless'  => false,
                'scaleFactor' => .5,
            ],
        ]
    ],
];
