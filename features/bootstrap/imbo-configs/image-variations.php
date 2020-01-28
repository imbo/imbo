<?php declare(strict_types=1);
namespace Imbo\Behat;

use Imbo\EventListener\ImageVariations;
use Imbo\EventListener\ImageVariations\Database\MongoDB;
use Imbo\EventListener\ImageVariations\Storage\GridFS;

/**
 * Enable the image variations event listener
 */
return [
    'eventListeners' => [
        'imageVariationsListener' => [
            'listener' => ImageVariations::class,
            'params' => [
                'database' => [
                    'adapter' => MongoDB::class,
                    'params'  => ['databaseName' => 'imbo_testing'],
                ],
                'storage' => [
                    'adapter' => GridFS::class,
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
        ],
    ],
];
