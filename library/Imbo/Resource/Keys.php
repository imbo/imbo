<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace Imbo\Resource;

use Imbo\EventManager\EventInterface;

/**
 * Keys resource
 *
 * This resource can be used to manipulate the public keys for an instance,
 * given that a mutable access control adapter is used.
 *
 * @author Kristoffer Brabrand <kristoffer@brabrand.no>
 * @package Resources
 */
class Keys implements ResourceInterface {
    /**
     * {@inheritdoc}
     */
    public function getAllowedMethods() {
        return array('GET', 'HEAD', 'PUT', 'DELETE');
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents() {
        return [
            'keys.get' => 'func',
            'keys.head' => 'func',
            'keys.put' => 'func',
            'keys.delete' => 'func'
        ];
    }

    public function func(EventInterface $event) {
        throw new \Imbo\Exception\RuntimeException('Foobar', 456);
    }
}
