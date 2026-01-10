<?php declare(strict_types=1);

namespace Imbo\Behat;

use Imbo\EventListener\Cors;

return [
    'eventListeners' => [
        'cors' => [
            'listener' => Cors::class,
            'params' => [
                'allowedOrigins' => ['http://allowedhost'],
                'allowedMethods' => [
                    'index' => ['GET', 'HEAD'],
                    'image' => ['GET', 'HEAD'],
                    'images' => ['GET', 'HEAD', 'POST'],
                    'metadata' => ['GET', 'HEAD'],
                    'status' => ['GET', 'HEAD'],
                    'stats' => ['GET', 'HEAD'],
                    'user' => ['GET', 'HEAD'],
                    'shorturl' => ['GET', 'HEAD'],
                ],
                'maxAge' => 1349,
            ],
        ],
    ],
];
