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
 * Enable the metadata cache event listener, using a memcached on localhost:11211 for storage, and
 * a request header value for the namespace
 */
return [
    'eventListeners' => [
        'metadataCache' => function() {
            $memcached = new Memcached();
            $memcached->addServer('localhost', 11211);

            $adapter = new Imbo\Cache\Memcached($memcached, $_SERVER['HTTP_X_TEST_SESSION_ID']);

            return new Imbo\EventListener\MetadataCache([
                'cache' => $adapter,
            ]);
        },
    ],
];
