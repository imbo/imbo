<?php declare(strict_types=1);

namespace Imbo\Resource;

use Imbo\Auth\AccessControl\Adapter\MutableAdapterInterface;
use Imbo\EventManager\EventInterface;
use Imbo\Exception\InvalidArgumentException;
use Imbo\Exception\ResourceException;
use Imbo\Http\Response\Response;
use Imbo\Model\Group as GroupModel;

use function array_key_exists;
use function is_array;
use function is_string;

use const JSON_ERROR_NONE;

class Group implements ResourceInterface
{
    public function getAllowedMethods(): array
    {
        return ['GET', 'HEAD', 'PUT', 'DELETE'];
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'group.get' => 'getGroup',
            'group.head' => 'getGroup',
            'group.put' => 'updateGroup',
            'group.delete' => 'deleteGroup',
        ];
    }

    /**
     * Get the resources associated with a specific group.
     */
    public function getGroup(EventInterface $event): void
    {
        $route = $event->getRequest()->getRoute();
        $groupName = $route->get('group');

        $adapter = $event->getAccessControl();

        if (!$adapter->groupExists($groupName)) {
            throw new ResourceException('Resource group not found', Response::HTTP_NOT_FOUND);
        }

        $resources = $adapter->getGroup($groupName);

        $model = new GroupModel();
        $model->setName($groupName);
        $model->setResources($resources);

        $response = $event->getResponse();
        $response->setModel($model);
    }

    /**
     * Add resources to a group.
     */
    public function updateGroup(EventInterface $event): void
    {
        $accessControl = $event->getAccessControl();
        if (!$accessControl instanceof MutableAdapterInterface) {
            throw new ResourceException('Access control adapter is immutable', Response::HTTP_METHOD_NOT_ALLOWED);
        }

        $request = $event->getRequest();
        $route = $request->getRoute();
        $name = $route->get('group');

        $group = $accessControl->getGroup($name);

        if (null === $group) {
            throw new ResourceException('Group does not exist', Response::HTTP_NOT_FOUND);
        }

        $body = json_decode($request->getContent(), true);

        if (null === $body || JSON_ERROR_NONE !== json_last_error()) {
            throw new InvalidArgumentException('Invalid JSON data', Response::HTTP_BAD_REQUEST);
        }

        if (!array_key_exists('resources', $body) || !is_array($body['resources'])) {
            throw new InvalidArgumentException('Resource list missing', Response::HTTP_BAD_REQUEST);
        }

        $resources = $body['resources'];

        foreach ($resources as $resource) {
            if (!is_string($resource)) {
                throw new ResourceException('Resources must be specified as strings', Response::HTTP_BAD_REQUEST);
            }
        }

        $accessControl->updateResourceGroup($name, $resources);

        $model = new GroupModel();
        $model->setName($name);
        $model->setResources($resources);

        $event->getResponse()->setModel($model);
    }

    /**
     * Delete a resource group.
     */
    public function deleteGroup(EventInterface $event): void
    {
        $accessControl = $event->getAccessControl();
        if (!$accessControl instanceof MutableAdapterInterface) {
            throw new ResourceException('Access control adapter is immutable', Response::HTTP_METHOD_NOT_ALLOWED);
        }

        $route = $event->getRequest()->getRoute();
        $groupName = $route->get('group');
        $resources = $accessControl->getGroup($groupName);

        if (!$resources) {
            throw new ResourceException('Resource group not found', Response::HTTP_NOT_FOUND);
        }

        $accessControl->deleteResourceGroup($groupName);

        $model = new GroupModel();
        $model->setName($groupName);
        $model->setResources($resources);

        $event->getResponse()->setModel($model);
    }
}
