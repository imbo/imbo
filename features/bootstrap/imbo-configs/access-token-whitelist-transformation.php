<?php declare(strict_types=1);

namespace Imbo\Behat;

use Imbo\EventListener\AccessToken;

return [
    'eventListeners' => [
        'accessToken' => [
            'listener' => AccessToken::class,
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
            ],
        ],
    ],
];
