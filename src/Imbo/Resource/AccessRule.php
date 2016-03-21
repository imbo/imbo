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
    Imbo\Exception\ResourceException,
    Imbo\Auth\AccessControl\Adapter\MutableAdapterInterface,
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
        return ['GET', 'HEAD', 'DELETE'];
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

    /**
     * Get an access control rule specified by ID
     *
     * @param EventInterface $event The current event
     */
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
        $model->setId($accessRule['id'])
              ->setUsers($accessRule['users']);

        if (isset($accessRule['group'])) {
            $model->setGroup($accessRule['group']);
        }

        if (isset($accessRule['resources'])) {
            $model->setResources($accessRule['resources']);
        }

        $event->getResponse()->setModel($model);
    }

    /**
     * Delete the specified access control rule
     *
     * @param EventInterface $event The current event
     */
    public function deleteRule(EventInterface $event) {
        $acl = $event->getAccessControl();

        if (!($acl instanceof MutableAdapterInterface)) {
            throw new ResourceException('Access control adapter is immutable', 405);
        }

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
