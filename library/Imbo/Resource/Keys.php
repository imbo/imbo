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

use Imbo\EventManager\EventInterface,
    Imbo\Exception\RuntimeException\InvalidArgumentException;

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
        return array('PUT', 'DELETE');
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents() {
        return [
            'keys.put' => 'createKey',
            'keys.delete' => 'deleteKey'
        ];
    }

    public function createKey(EventInterface $event) {
        $request = $event->getRequest();
        $data = json_decode($request->getContent(), true);

        if (!isset($data['privateKey'])) {
            throw new InvalidArgumentException('No privateKey provided', 400);
        }

        $publicKey = $request->getRoute()->get('publickey');
        $privateKey = $data['privateKey'];

        // Create key pair
        $event->getAccessControl()->addKeyPair($publicKey, $privateKey);

        // Return a 201
        $event->getResponse()->setStatusCode(201);
    }

    public function deleteKey(EventInterface $event) {
        throw new \Imbo\Exception\RuntimeException('Not Implemented', 501);
    }
}
