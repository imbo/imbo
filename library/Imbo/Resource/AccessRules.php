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
            'accessrules.get'  => 'getRules',
            'accessrules.head' => 'getRules',
            'accessrules.post' => 'updateRules'
        ];
    }

    /**
     * Get access rules for the specified public key
     *
     * @param EventInterface $event The current event
     */
    public function getRules(EventInterface $event) {
        $request = $event->getRequest();
        $publicKey = $request->getRoute()->get('publickey');

        $accessControl = $event->getAccessControl();
        $keyExists = $accessControl->publicKeyExists($publicKey);

        if (!$keyExists) {
            throw new RuntimeException('Public key not found', 404);
        }

        $accessList = $accessControl->getAccessListForPublicKey($publicKey);

        $model = new AccessRulesModel();
        $model->setData($accessList);

        $event->getResponse()->setModel($model);
    }

    /**
     * Update access rules for the specified public key
     *
     * @param EventInterface $event The current event
     */
    public function updateRules(EventInterface $event) {
        $request = $event->getRequest();
        $publicKey = $request->getRoute()->get('publickey');
        $data = json_decode($request->getContent(), true);

        if (!is_array($data)) {
            throw new InvalidArgumentException('No access rule data provided', 400);
        }

        $accessControl = $event->getAccessControl();
        foreach ($data as $rule) {
            $accessControl->addAccessRule($publicKey, $rule);
        }
    }
}
