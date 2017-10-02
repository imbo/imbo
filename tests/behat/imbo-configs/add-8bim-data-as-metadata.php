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
 * Enable the 8BIM metadata listener
 */
return [
    'eventListeners' => [
        '8BIMMetadataListener' => [
            'listener' => 'Imbo\EventListener\EightbimMetadata',
        ]
    ],
];
