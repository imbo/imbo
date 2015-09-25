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
        return ['GET', 'HEAD', 'POST'];
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents() {
        return [
            'accessrules.get' => 'getRules',
            'accessrules.head' => 'getRules',
            'accessrules.post' => 'addRules'
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
     * Add access rules for the specified public key
     *
     * @param EventInterface $event The current event
     */
    public function addRules(EventInterface $event) {
        $accessControl = $event->getAccessControl();

        if (!($accessControl instanceof MutableAdapterInterface)) {
            throw new ResourceException('Access control adapter is immutable', 405);
        }

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
