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
    Imbo\Model\AccessList as AccessListModel;

/**
 * Access resource
 *
 * This resource can be used to manipulate the access granted to a user
 *
 * @author Kristoffer Brabrand <kristoffer@brabrand.no>
 * @package Resources
 */
class Access implements ResourceInterface {
    /**
     * {@inheritdoc}
     */
    public function getAllowedMethods() {
        return array('GET', 'HEAD', 'POST', 'DELETE');
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents() {
        return [
            'access.get' => 'getAccessList',
            'access.head' => 'func',
            'access.post' => 'func',
            'access.delete' => 'func'
        ];
    }

    public function getPublicKey(EventInterface $event) {
        return $event->getRequest()->getRoute()->get('publickey');
    }

    public function getAccessList(EventInterface $event) {
        $publicKey = $this->getPublicKey($event);

        $accessList = $event->getAccessControl()->getAccessListForPublicKey($publicKey);

        $model = new AccessListModel();
        $model->setData($accessList);

        $event->getResponse()->setModel($model);
    }

    public function func() {
        throw new \Imbo\Exception\RuntimeException('Foobar', 456);
    }
}
