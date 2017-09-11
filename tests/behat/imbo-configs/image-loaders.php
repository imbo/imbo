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
 * Add custom image loaders for testing
 */
return [
    'loaders' => [
        'Imbo\Image\Loader\Text',
        'Imbo\Image\Loader\Tiff',
    ],
    'rethrowFinalException' => true,
];
