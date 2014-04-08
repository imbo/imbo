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
 * Enable the Varnish hashtwo event listener
 */
return array(
    'eventListeners' => array(
        'varnishHashTwo' => 'Imbo\EventListener\VarnishHashTwo',
        'customVarnishHashTwo' => array(
            'listener' => 'Imbo\EventListener\VarnishHashTwo',
            'params' => array(
                'headerName' => 'X-Imbo-HashTwo',
            ),
        ),
    ),
);
