<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

return array(
    'transformationPresets' => array(
        'graythumb' => array(
            'thumbnail',
            'desaturate',
        ),
        'whitelisted' => array(
            'crop' => array(
                'width' => 100,
                'height' => 50,
                'mode' => 'center',
            )
        ),
    ),
);
