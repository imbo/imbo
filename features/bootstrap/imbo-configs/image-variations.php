<?php declare(strict_types=1);

namespace Imbo\Behat;

use Imbo\EventListener\ImageVariations;
use Imbo\EventListener\ImageVariations\Database\MongoDB;
use Imbo\EventListener\ImageVariations\Storage\GridFS;

/**
 * Enable the image variations event listener.
 */
return [
    'eventListeners' => [
        'imageVariationsListener' => [
            'listener' => ImageVariations::class,
            'params' => [
                'database' => [
                    'adapter' => new MongoDB('imbo_testing', 'mongodb://localhost:27017', ['username' => 'admin', 'password' => 'password']),
                ],
                'storage' => [
                    'adapter' => new GridFS('imbo_testing', 'mongodb://localhost:27017', ['username' => 'admin', 'password' => 'password']),
                ],
                'widths' => [320],
                'minWidth' => 100,
                'maxWidth' => 2048,
                'minDiff' => 100,
                'autoScale' => true,
                'lossless' => false,
                'scaleFactor' => .5,
            ],
        ],
    ],
];
