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
    Imbo\Model\AccessRules as AccessRulesModel;

/**
 * Access rules resource
 *
 * @author Kristoffer Brabrand <kristoffer@brabrand.no>
 * @package Resources
 */
class AccessRules implements ResourceInterface {
    /**
     * {@inheritdoc}
     */
    public function getAllowedMethods() {
        return array('GET', 'HEAD', 'POST');
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents() {
        return [
            'accessrules.get' => 'getRules',
            'accessrules.head' => 'getRules',
            'accessrules.post' => 'updateRules'
        ];
    }

    public function getPublicKey(EventInterface $event) {
        return $event->getRequest()->getRoute()->get('publickey');
    }

    public function getRules(EventInterface $event) {
        $publicKey = $this->getPublicKey($event);

        $accessList = $event->getAccessControl()->getAccessListForPublicKey($publicKey);

        $model = new AccessRulesModel();
        $model->setData($accessList);

        $event->getResponse()->setModel($model);
    }

    public function updateRules(EventInterface $event) {
        throw new \Imbo\Exception\RuntimeException('Not Implemented', 501);
    }
}
