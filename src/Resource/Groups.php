<?php declare(strict_types=1);

namespace Imbo\Resource;

use Imbo\Auth\AccessControl\Adapter\MutableAdapterInterface;
use Imbo\EventManager\EventInterface;
use Imbo\Exception\InvalidArgumentException;
use Imbo\Exception\ResourceException;
use Imbo\Http\Response\Response;
use Imbo\Model\Group;

use function array_key_exists;
use function is_array;
use function is_string;

use const JSON_ERROR_NONE;

class Groups implements ResourceInterface
{
    public function getAllowedMethods(): array
    {
        return ['GET', 'HEAD'];
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'groups.get' => 'listGroups',
            'groups.head' => 'listGroups',
            'groups.post' => 'addGroup',
        ];
    }

    /**
     * Get a list of available resource groups.
     */
    public function listGroups(EventInterface $event): void
    {
        $event->getManager()->trigger('acl.groups.load');
    }

    /**
     * Add a new group.
     */
    public function addGroup(EventInterface $event): void
    {
        $accessControl = $event->getAccessControl();
        if (!$accessControl instanceof MutableAdapterInterface) {
            throw new ResourceException('Access control adapter is immutable', Response::HTTP_METHOD_NOT_ALLOWED);
        }

        $request = $event->getRequest();
        $body = $request->getContent();

        if (empty($body)) {
            throw new InvalidArgumentException('Missing JSON data', Response::HTTP_BAD_REQUEST);
        }

        $body = json_decode($body, true);

        if (null === $body || JSON_ERROR_NONE !== json_last_error()) {
            throw new InvalidArgumentException('Invalid JSON data', Response::HTTP_BAD_REQUEST);
        }

        if (!array_key_exists('name', $body) || '' === trim((string) $body['name'])) {
            throw new InvalidArgumentException('Group name missing', Response::HTTP_BAD_REQUEST);
        }

        if (!array_key_exists('resources', $body) || !is_array($body['resources'])) {
            throw new InvalidArgumentException('Resource list missing', Response::HTTP_BAD_REQUEST);
        }

        if (!preg_match('/^[a-z0-9_-]{1,}$/', $body['name'])) {
            throw new InvalidArgumentException('Invalid group name', Response::HTTP_BAD_REQUEST);
        }

        $name = $body['name'];
        $resources = $body['resources'];

        $group = $accessControl->getGroup($name);

        if (null !== $group) {
            throw new InvalidArgumentException('Group already exists', Response::HTTP_BAD_REQUEST);
        }

        foreach ($resources as $resource) {
            if (!is_string($resource)) {
                throw new ResourceException('Resources must be specified as strings', Response::HTTP_BAD_REQUEST);
            }
        }

        $accessControl->addResourceGroup($name, $resources);

        $model = new Group();
        $model
            ->setName($name)
            ->setResources($resources);

        $event->getResponse()
            ->setModel($model)
            ->setStatusCode(Response::HTTP_CREATED);
    }
}
