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
 * Enable the max image size event listener, setting a max size of w1000 x h600
 */
return [
    'eventListeners' => [
        'maxImageSize' => [
            'listener' => 'Imbo\EventListener\MaxImageSize',
            'params' => [
                'width' => 1000,
                'height' => 600,
            ],
        ],
    ],
];
