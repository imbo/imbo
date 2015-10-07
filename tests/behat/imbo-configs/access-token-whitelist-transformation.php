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
