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
 * Enable the CORS event listener
 */
return array(
    'eventListeners' => array(
        'cors' => array(
            'listener' => 'Imbo\EventListener\Cors',
            'params' => array(
                'allowedOrigins' => array('http://allowedhost'),
                'allowedMethods' => array(
                    'index'    => array('GET', 'HEAD'),
                    'image'    => array('GET', 'HEAD'),
                    'images'   => array('GET', 'HEAD', 'POST'),
                    'metadata' => array('GET', 'HEAD'),
                    'status'   => array('GET', 'HEAD'),
                    'stats'    => array('GET', 'HEAD'),
                    'user'     => array('GET', 'HEAD'),
                    'shorturl' => array('GET', 'HEAD'),
                ),
                'maxAge' => 1349,
            ),
        ),
    ),
);
