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
    Imbo\Exception\ResourceException,
    Imbo\Auth\AccessControl\Adapter\MutableAdapterInterface,
    Imbo\Model\Group as GroupModel;

/**
 * Resource group resource
 *
 * @author Espen Hovlandsdal <espen@hovlandsdal.com>
 * @package Resources
 */
class Group implements ResourceInterface {
    /**
     * {@inheritdoc}
     */
    public function getAllowedMethods() {
        return ['GET', 'HEAD', 'PUT', 'DELETE'];
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents() {
        return [
            'group.get'    => 'getGroup',
            'group.head'   => 'getGroup',
            'group.put'    => 'addGroup',
            'group.delete' => 'deleteGroup',
        ];
    }

    /**
     * Get the resources associated with a specific group
     *
     * @param EventInterface $event The current event
     */
    public function getGroup(EventInterface $event) {
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
     *
     * @param EventInterface $event The current event
     */
    public function addGroup(EventInterface $event) {
        $accessControl = $event->getAccessControl();
        if (!($accessControl instanceof MutableAdapterInterface)) {
            throw new ResourceException('Access control adapter is immutable', 405);
        }

        $request = $event->getRequest();
        $route = $request->getRoute();
        $groupName = $route->get('group');

        $group = $accessControl->getGroup($groupName);
        $groupExists = !empty($group);

        $resources = json_decode($request->getContent(), true);

        if (!is_array($resources)) {
            throw new ResourceException('Invalid data. Array of resource strings is expected', 400);
        }

        foreach ($resources as $resource) {
            if (!is_string($resource)) {
               throw new ResourceException('Invalid value in the resources array. Only strings are allowed', 400);
            }
        }

        if ($groupExists) {
            $accessControl->updateResourceGroup($groupName, $resources);
        } else {
            $accessControl->addResourceGroup($groupName, $resources);
        }

        $response = $event->getResponse();
        $response->setStatusCode($groupExists ? 200 : 201);
    }

    /**
     * Delete a resource group
     *
     * @param EventInterface $event The current event
     */
    public function deleteGroup(EventInterface $event) {
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
