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
    Imbo\Exception\InvalidArgumentException,
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

        if ($request->query->has('expandGroups')) {
            foreach ($accessList as &$rule) {
                if (!isset($rule['group'])) {
                    continue;
                }

                $rule['resources'] = $accessControl->getGroup($rule['group']);
            }
        }

        $model = new AccessRulesModel();
        $model->setRules($accessList);

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

        // If a single rule was provided, wrap it in an array
        if (!count($data) || !isset($data[0])) {
            $data = [$data];
        }

        $accessControl = $event->getAccessControl();

        // Perform rule validation
        foreach ($data as $rule) {
            $this->validateRule($event, $rule);
        }

        // Insert the rules
        foreach ($data as $rule) {
            $accessControl->addAccessRule($publicKey, $rule);
        }
    }

    /**
     * Checks if this is an array containing only strings
     *
     * @param Mixed Values to test
     * @return boolean True if all values are strings
     */
    private function isStringArray($values) {
        if (!is_array($values)) {
            return false;
        }

        return array_reduce($values, function($res, $value) {
            return $res && is_string($value);
        }, true);
    }

    /**
     * Validate the contents of an access rule.
     *
     * @param array $rule Access rule to check
     * @throws RuntimeException
     */
    private function validateRule(EventInterface $event, array $rule) {
        $acl = $event->getAccessControl();

        $allowedProperties = ['resources', 'group', 'users'];
        $unknownProperties = array_diff(array_keys($rule), $allowedProperties);

        if (!empty($unknownProperties)) {
            throw new RuntimeException('Found unknown properties in rule: [' . implode(', ', $unknownProperties) . ']', 400);
        }

        if (isset($rule['resources']) && isset($rule['group'])) {
            throw new RuntimeException('Both resources and group found in rule', 400);
        }

        if (!isset($rule['resources']) && !isset($rule['group'])) {
            throw new RuntimeException('Neither group nor resources found in rule', 400);
        }

        if (isset($rule['resources']) && !$this->isStringArray($rule['resources'])) {
            throw new RuntimeException('Illegal value in resources array. String array expected', 400);
        }

        if (isset($rule['group'])) {
            if (!is_string($rule['group'])) {
                throw new RuntimeException('Group must be specified as a string value', 400);
            }

            if (!$acl->getGroup($rule['group'])) {
                throw new RuntimeException('Group \'' . $rule['group'] . '\' does not exist', 400);
            }
        }

        if (!isset($rule['users'])) {
            throw new RuntimeException('Users not specified in rule', 400);
        }

        if ($rule['users'] !== '*' && !$this->isStringArray($rule['users'])) {
            throw new RuntimeException('Illegal value for users property. Allowed: \'*\' or array with users', 400);
        }
    }
}
