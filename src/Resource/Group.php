<?php declare(strict_types=1);
namespace Imbo\Resource;

use Imbo\Auth\AccessControl\Adapter\MutableAdapterInterface;
use Imbo\EventManager\EventInterface;
use Imbo\Exception\InvalidArgumentException;
use Imbo\Exception\ResourceException;
use Imbo\Model\Group as GroupModel;

class Group implements ResourceInterface
{
    public function getAllowedMethods(): array
    {
        return ['GET', 'HEAD', 'PUT', 'DELETE'];
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'group.get'    => 'getGroup',
            'group.head'   => 'getGroup',
            'group.put'    => 'updateGroup',
            'group.delete' => 'deleteGroup',
        ];
    }

    /**
     * Get the resources associated with a specific group
     */
    public function getGroup(EventInterface $event): void
    {
        $route = $event->getRequest()->getRoute();
        $groupName = $route->get('group');

        $adapter = $event->getAccessControl();

        if (!$adapter->groupExists($groupName)) {
            throw new ResourceException('Resource group not found', 404);
        }

        $resources = $adapter->getGroup($groupName);

        $model = new GroupModel();
        $model->setName($groupName);
        $model->setResources($resources);

        $response = $event->getResponse();
        $response->setModel($model);
    }

    /**
     * Add resources to a group
     */
    public function updateGroup(EventInterface $event): void
    {
        $accessControl = $event->getAccessControl();
        if (!($accessControl instanceof MutableAdapterInterface)) {
            throw new ResourceException('Access control adapter is immutable', 405);
        }

        $request = $event->getRequest();
        $route = $request->getRoute();
        $name = $route->get('group');

        $group = $accessControl->getGroup($name);

        if (null === $group) {
            throw new ResourceException('Group does not exist', 404);
        }

        $body = json_decode($request->getContent(), true);

        if ($body === null || json_last_error() !== JSON_ERROR_NONE) {
            throw new InvalidArgumentException('Invalid JSON data', 400);
        }

        if (!array_key_exists('resources', $body) || !is_array($body['resources'])) {
            throw new InvalidArgumentException('Resource list missing', 400);
        }

        $resources = $body['resources'];

        foreach ($resources as $resource) {
            if (!is_string($resource)) {
                throw new ResourceException('Resources must be specified as strings', 400);
            }
        }

        $accessControl->updateResourceGroup($name, $resources);

        $model = new GroupModel();
        $model
            ->setName($name)
            ->setResources($resources);

        $event->getResponse()->setModel($model);
    }

    /**
     * Delete a resource group
     */
    public function deleteGroup(EventInterface $event): void
    {
        $accessControl = $event->getAccessControl();
        if (!($accessControl instanceof MutableAdapterInterface)) {
            throw new ResourceException('Access control adapter is immutable', 405);
        }

        $route = $event->getRequest()->getRoute();
        $groupName = $route->get('group');
        $group = $accessControl->getGroup($groupName);

        if (!$group) {
            throw new ResourceException('Resource group not found', 404);
        }

        $accessControl->deleteResourceGroup($groupName);
    }
}
