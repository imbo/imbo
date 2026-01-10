<?php declare(strict_types=1);

namespace Imbo\Behat;

use Imbo\EventListener\Cors;

return [
    'eventListeners' => [
        'cors' => [
            'listener' => Cors::class,
            'params' => [
                'allowedOrigins' => ['*'],
                'allowedMethods' => [
                    'index' => ['GET', 'HEAD'],
                ],
            ],
        ],
    ],
];
