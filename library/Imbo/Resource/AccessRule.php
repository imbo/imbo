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
    Imbo\Exception\RuntimeException,
    Imbo\Model\AccessRule as AccessRuleModel;

/**
 * Access rule resource
 *
 * @author Kristoffer Brabrand <kristoffer@brabrand.no>
 * @package Resources
 */
class AccessRule implements ResourceInterface {
    /**
     * {@inheritdoc}
     */
    public function getAllowedMethods() {
        return array('GET', 'HEAD', 'DELETE');
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents() {
        return [
            'accessrule.get' => 'getRule',
            'accessrule.head' => 'getRule',
            'accessrule.delete' => 'deleteRule'
        ];
    }

    public function getRule(EventInterface $event) {
        $acl = $event->getAccessControl();

        $request = $event->getRequest();
        $publicKey = $request->getRoute()->get('publickey');
        $accessRuleId = $request->getRoute()->get('accessRuleId');

        $keyExists = $acl->publicKeyExists($publicKey);

        if (!$keyExists) {
            throw new RuntimeException('Public key not found', 404);
        }

        $accessRule = $acl->getAccessRule($publicKey, $accessRuleId);

        if (!$accessRule) {
            throw new RuntimeException('Access rule not found', 404);
        }

        $model = new AccessRuleModel();
        $model->setData($accessRule);

        $event->getResponse()->setModel($model);
    }

    public function deleteRule(EventInterface $event) {
        $acl = $event->getAccessControl();

        $request = $event->getRequest();
        $publicKey = $request->getRoute()->get('publickey');
        $accessRuleId = $request->getRoute()->get('accessRuleId');

        $keyExists = $acl->publicKeyExists($publicKey);

        if (!$keyExists) {
            throw new RuntimeException('Public key not found', 404);
        }

        $accessRule = $acl->getAccessRule($publicKey, $accessRuleId);

        if (!$accessRule) {
            throw new RuntimeException('Access rule not found', 404);
        }

        $acl->deleteAccessRule($publicKey, $accessRuleId);
    }
}
