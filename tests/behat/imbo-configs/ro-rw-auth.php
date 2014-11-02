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
 * Use individual read-only/read+write keys
 */
return [
    'auth' => [
        'publickey' => [
            'ro' => 'read-only-key',
            'rw' => ['read+write-key'],
        ]
    ],
];
