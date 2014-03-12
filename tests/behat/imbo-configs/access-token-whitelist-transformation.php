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
return array(
    'eventListeners' => array(
        'accessToken' => array(
            'listener' => 'Imbo\EventListener\AccessToken',
            'params' => array(
                'transformations' => array(
                   'whitelist' => array(
                        'whitelisted',
                    ),
                ),
            ),
        ),
    ),
    'transformationPresets' => array(
        'whitelisted' => array(
            'crop' => array(
                'width' => 100,
                'height' => 50,
                'mode' => 'center',
            )
        ),
    ),
);
