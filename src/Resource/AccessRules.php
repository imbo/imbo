<?php declare(strict_types=1);

namespace Imbo\Resource;

use Imbo\Auth\AccessControl\Adapter\MutableAdapterInterface;
use Imbo\EventManager\EventInterface;
use Imbo\Exception\InvalidArgumentException;
use Imbo\Exception\ResourceException;
use Imbo\Exception\RuntimeException;
use Imbo\Http\Response\Response;
use Imbo\Model\AccessRules as AccessRulesModel;
use Imbo\Model\ArrayModel;

use function count;
use function is_array;
use function is_string;

class AccessRules implements ResourceInterface
{
    public function getAllowedMethods(): array
    {
        return ['GET', 'HEAD', 'POST'];
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'accessrules.get' => 'getRules',
            'accessrules.head' => 'getRules',
            'accessrules.post' => 'addRules',
        ];
    }

    /**
     * Get access rules for the specified public key.
     *
     * @param EventInterface $event The current event
     */
    public function getRules(EventInterface $event): void
    {
        $request = $event->getRequest();
        $publicKey = $request->getRoute()->get('publickey');

        $accessControl = $event->getAccessControl();
        $keyExists = $accessControl->publicKeyExists($publicKey);

        if (!$keyExists) {
            throw new RuntimeException('Public key not found', Response::HTTP_NOT_FOUND);
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
     * Add access rules for the specified public key.
     *
     * @param EventInterface $event The current event
     */
    public function addRules(EventInterface $event): void
    {
        $accessControl = $event->getAccessControl();

        if (!($accessControl instanceof MutableAdapterInterface)) {
            throw new ResourceException('Access control adapter is immutable', Response::HTTP_METHOD_NOT_ALLOWED);
        }

        $request = $event->getRequest();
        $publicKey = $request->getRoute()->get('publickey');
        $data = json_decode($request->getContent(), true);

        if (!is_array($data)) {
            throw new InvalidArgumentException('No access rule data provided', Response::HTTP_BAD_REQUEST);
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
        $rules = [];

        foreach ($data as $rule) {
            $rule['id'] = $accessControl->addAccessRule($publicKey, $rule);
            $rules[] = $rule;
        }

        $event->getResponse()->setModel((new ArrayModel())->setData($rules));
    }

    /**
     * Checks if this is an array containing only strings.
     *
     * @param mixed Values to test
     *
     * @return bool True if all values are strings
     */
    private function isStringArray($values): bool
    {
        if (!is_array($values)) {
            return false;
        }

        return array_reduce($values, fn (bool $res, $value): bool => $res && is_string($value), true);
    }

    /**
     * Validate the contents of an access rule.
     *
     * @param array $rule Access rule to check
     *
     * @throws RuntimeException
     */
    private function validateRule(EventInterface $event, array $rule): void
    {
        $acl = $event->getAccessControl();

        $allowedProperties = ['resources', 'group', 'users'];
        $unknownProperties = array_diff(array_keys($rule), $allowedProperties);

        if (!empty($unknownProperties)) {
            throw new RuntimeException('Found unknown properties in rule: ['.implode(', ', $unknownProperties).']', Response::HTTP_BAD_REQUEST);
        }

        if (isset($rule['resources']) && isset($rule['group'])) {
            throw new RuntimeException('Both resources and group found in rule', Response::HTTP_BAD_REQUEST);
        }

        if (!isset($rule['resources']) && !isset($rule['group'])) {
            throw new RuntimeException('Neither group nor resources found in rule', Response::HTTP_BAD_REQUEST);
        }

        if (isset($rule['resources']) && !$this->isStringArray($rule['resources'])) {
            throw new RuntimeException('Illegal value in resources array. String array expected', Response::HTTP_BAD_REQUEST);
        }

        if (isset($rule['group'])) {
            if (!is_string($rule['group'])) {
                throw new RuntimeException('Group must be specified as a string value', Response::HTTP_BAD_REQUEST);
            }

            if (!$acl->getGroup($rule['group'])) {
                throw new RuntimeException('Group \''.$rule['group'].'\' does not exist', Response::HTTP_BAD_REQUEST);
            }
        }

        if (!isset($rule['users'])) {
            throw new RuntimeException('Users not specified in rule', Response::HTTP_BAD_REQUEST);
        }

        if ('*' !== $rule['users'] && !$this->isStringArray($rule['users'])) {
            throw new RuntimeException('Illegal value for users property. Allowed: \'*\' or array with users', Response::HTTP_BAD_REQUEST);
        }
    }
}
