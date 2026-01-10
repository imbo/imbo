<?php declare(strict_types=1);

namespace Imbo\EventListener;

use Imbo\Auth\AccessControl\GroupQuery;
use Imbo\EventManager\EventInterface;
use Imbo\Exception\RuntimeException;
use Imbo\Http\Response\Response;
use Imbo\Model\Groups as GroupsModel;
use Imbo\Resource;

use function in_array;

/**
 * Access control event listener.
 *
 * This event listener will listen to all access-controlled resources and check if the public key
 * has access to the requested resource. If the public key does not have access to the resource,
 * the listener will throw an exception resulting in a HTTP response with 400 Bad Request.
 * It will also handle loading of ACL-related resources such as resource groups.
 */
class AccessControl implements ListenerInterface
{
    /**
     * Parameters for the listener.
     *
     * @var array
     */
    private $params = [
        'additionalResources' => null,
    ];

    /**
     * Certain resources should be allowed when the requested public key
     * is the same as the public key used to sign the request.
     *
     * @var array
     */
    private $ownPublicKeyAllowedResources = [
        Resource::ACCESS_RULE_GET,
        Resource::ACCESS_RULE_HEAD,
        Resource::ACCESS_RULE_OPTIONS,
        Resource::ACCESS_RULES_GET,
        Resource::ACCESS_RULES_HEAD,
        Resource::ACCESS_RULES_OPTIONS,
    ];

    /**
     * The resources that concerns resource group lookups.
     *
     * @var array
     */
    private $groupLookupResources = [
        Resource::GROUP_GET,
        Resource::GROUP_HEAD,
        Resource::GROUP_OPTIONS,
    ];

    /**
     * Class constructor.
     *
     * @param array $params Parameters for the listener
     */
    public function __construct(?array $params = null)
    {
        if ($params) {
            $this->params = array_replace_recursive($this->params, $params);
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'route.match' => 'subscribe',
            'acl.groups.load' => 'loadGroups',
        ];
    }

    /**
     * Figure out which resources we have available and subscribe to them.
     */
    public function subscribe(EventInterface $event): void
    {
        $resources = Resource::getAllResources();

        if ($this->params['additionalResources']) {
            $resources = array_merge($resources, $this->params['additionalResources']);
        }

        $events = [];
        foreach ($resources as $resource) {
            $events[$resource] = ['checkAccess' => 500];
        }

        $manager = $event->getManager();
        $manager->addCallbacks($event->getHandler(), $events);
    }

    /**
     * Check if the public key used has access to this resource for this user.
     *
     * @throws RuntimeException If public key does not have access to the resource
     */
    public function checkAccess(EventInterface $event): void
    {
        if ($event->hasArgument('skipAccessControl')
            && true === $event->getArgument('skipAccessControl')) {
            return;
        }

        $request = $event->getRequest();
        $aclAdapter = $event->getAccessControl();
        $resource = $event->getName();
        $publicKey = (string) $request->getPublicKey();

        if ('' === $publicKey) {
            throw new RuntimeException('Missing public key', Response::HTTP_BAD_REQUEST);
        }

        $user = $request->getUser();
        $hasAccess = $aclAdapter->hasAccess((string) $publicKey, $resource, $user);

        if ($hasAccess) {
            return;
        }

        // If we're asking for info on a public key, and that public key happens to be the one
        // used to sign the request, accept this as a valid request and let the user have access
        // to the resource. Note that this requires the accessToken listener to be in place -
        // if disabled, any user can ask for the access rules for all public keys
        if (in_array($resource, $this->ownPublicKeyAllowedResources)) {
            $routePubKey = $request->getRoute()->get('publickey');
            if ($routePubKey === $publicKey) {
                return;
            }
        }

        // If a public key has access to resources within a resource group, allow the
        // public key to access the group resource to see which resources it contains
        if (in_array($resource, $this->groupLookupResources)) {
            $routeGroup = $request->getRoute()->get('group');
            $aclList = $aclAdapter->getAccessListForPublicKey($publicKey);

            foreach ($aclList as $aclRule) {
                if (isset($aclRule['groups']) && in_array($routeGroup, $aclRule['groups'])) {
                    return;
                }
            }
        }

        throw new RuntimeException('Permission denied (public key)', Response::HTTP_BAD_REQUEST);
    }

    /**
     * Load groups from the configured access control adapter.
     *
     * @param EventInterface $event An event instance
     */
    public function loadGroups(EventInterface $event): void
    {
        $query = new GroupQuery();
        $params = $event->getRequest()->query;

        if ($params->has('page')) {
            $query->setPage((int) $params->get('page'));
        }

        if ($params->has('limit')) {
            $query->setLimit((int) $params->get('limit'));
        }

        $response = $event->getResponse();
        $aclAdapter = $event->getAccessControl();

        // Create the model and set some pagination values
        $model = new GroupsModel();
        $model->setLimit($query->getLimit())
              ->setPage($query->getPage());

        $groups = $aclAdapter->getGroups($query, $model);
        $modelGroups = [];

        foreach ($groups as $groupName => $resources) {
            $modelGroups[] = [
                'name' => $groupName,
                'resources' => $resources,
            ];
        }

        $model->setGroups($modelGroups);
        $response->setModel($model);
    }
}
