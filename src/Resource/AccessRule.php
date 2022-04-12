<?php declare(strict_types=1);
namespace Imbo\Resource;

use Imbo\Auth\AccessControl\Adapter\MutableAdapterInterface;
use Imbo\EventManager\EventInterface;
use Imbo\Exception\ResourceException;
use Imbo\Exception\RuntimeException;
use Imbo\Http\Response\Response;
use Imbo\Model\AccessRule as AccessRuleModel;
use Imbo\Model\ArrayModel;

/**
 * Access rule resource
 */
class AccessRule implements ResourceInterface
{
    public function getAllowedMethods(): array
    {
        return ['GET', 'HEAD', 'DELETE'];
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'accessrule.get' => 'getRule',
            'accessrule.head' => 'getRule',
            'accessrule.delete' => 'deleteRule',
        ];
    }

    /**
     * Get an access control rule specified by ID
     *
     * @param EventInterface $event The current event
     */
    public function getRule(EventInterface $event): void
    {
        $acl = $event->getAccessControl();

        $request = $event->getRequest();
        $publicKey = $request->getRoute()->get('publickey');
        $accessRuleId = $request->getRoute()->get('accessRuleId');

        $keyExists = $acl->publicKeyExists($publicKey);

        if (!$keyExists) {
            throw new RuntimeException('Public key not found', Response::HTTP_NOT_FOUND);
        }

        $accessRule = $acl->getAccessRule($publicKey, $accessRuleId);

        if (!$accessRule) {
            throw new RuntimeException('Access rule not found', Response::HTTP_NOT_FOUND);
        }

        $model = new AccessRuleModel();
        $model->setId((int) $accessRule['id'])
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
    public function deleteRule(EventInterface $event): void
    {
        $acl = $event->getAccessControl();

        if (!($acl instanceof MutableAdapterInterface)) {
            throw new ResourceException('Access control adapter is immutable', Response::HTTP_METHOD_NOT_ALLOWED);
        }

        $request = $event->getRequest();
        $publicKey = $request->getRoute()->get('publickey');
        $accessRuleId = $request->getRoute()->get('accessRuleId');

        $keyExists = $acl->publicKeyExists($publicKey);

        if (!$keyExists) {
            throw new RuntimeException('Public key not found', Response::HTTP_NOT_FOUND);
        }

        $accessRule = $acl->getAccessRule($publicKey, $accessRuleId);

        if (!$accessRule) {
            throw new RuntimeException('Access rule not found', Response::HTTP_NOT_FOUND);
        }

        $acl->deleteAccessRule($publicKey, $accessRuleId);

        $event->getResponse()->setModel((new ArrayModel())->setData($accessRule));
    }
}
