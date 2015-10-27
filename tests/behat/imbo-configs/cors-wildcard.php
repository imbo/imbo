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
