<?php declare(strict_types=1);
/**
 * Whitelist a single transformation in the access token event listener
 */
return [
    'eventListeners' => [
        'accessToken' => [
            'listener' => 'Imbo\EventListener\AccessToken',
            'params' => [
                'transformations' => [
                   'whitelist' => [
                        'whitelisted',
                    ],
                ],
            ],
        ],
    ],
    'transformationPresets' => [
        'whitelisted' => [
            'crop' => [
                'width' => 100,
                'height' => 50,
                'mode' => 'center',
            ]
        ],
    ],
];
