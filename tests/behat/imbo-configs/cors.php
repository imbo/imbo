<?php
/**
 * Enable the CORS event listener
 */
return [
    'eventListeners' => [
        'cors' => [
            'listener' => 'Imbo\EventListener\Cors',
            'params' => [
                'allowedOrigins' => ['http://allowedhost'],
                'allowedMethods' => [
                    'index'    => ['GET', 'HEAD'],
                    'image'    => ['GET', 'HEAD'],
                    'images'   => ['GET', 'HEAD', 'POST'],
                    'metadata' => ['GET', 'HEAD'],
                    'status'   => ['GET', 'HEAD'],
                    'stats'    => ['GET', 'HEAD'],
                    'user'     => ['GET', 'HEAD'],
                    'shorturl' => ['GET', 'HEAD'],
                ],
                'maxAge' => 1349,
            ],
        ],
    ],
];
