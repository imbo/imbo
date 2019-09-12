<?php
/**
 * CORS-config with wildcard origin
 */
return [
    'eventListeners' => [
        'cors' => [
            'listener' => 'Imbo\EventListener\Cors',
            'params' => [
                'allowedOrigins' => ['*'],
                'allowedMethods' => [
                    'index' => ['GET', 'HEAD']
                ],
            ],
        ],
    ],
];
