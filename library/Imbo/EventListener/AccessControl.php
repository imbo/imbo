<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace Imbo\EventListener;

use Imbo\EventManager\EventInterface,
    Imbo\Exception\RuntimeException,
    Imbo\Auth\AccessControl\GroupQuery,
    Imbo\Model\Groups as GroupsModel,
    Imbo\Resource;

/**
 * Access control event listener
 *
 * This event listener will listen to all access-controlled resources and check if the public key
 * has access to the requested resource. If the public key does not have access to the resource,
 * the listener will throw an exception resulting in a HTTP response with 400 Bad Request.
 * It will also handle loading of ACL-related resources such as resource groups.
 *
 * @author Espen Hovlandsdal <espen@hovlandsdal.com>
 * @package Event\Listeners
 */
class AccessControl implements ListenerInterface {
    /**
     * Parameters for the listener
     *
     * @var array
     */
    private $params = [
        'additionalResources' => null,
    ];

    /**
     * Certain resources should be allowed when the requested public key
     * is the same as the public key used to sign the request
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
     * The resources that concerns resource group lookups
     *
     * @var array
     */
    private $groupLookupResources = [
        Resource::GROUP_GET,
        Resource::GROUP_HEAD,
        Resource::GROUP_OPTIONS
    ];

    /**
     * Class constructor
     *
     * @param array $params Parameters for the listener
     */
    public function __construct(array $params = null) {
        if ($params) {
            $this->params = array_replace_recursive($this->params, $params);
        }
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents() {
        return [
            'route.match' => 'subscribe',
            'acl.groups.load' => 'loadGroups',
        ];
    }

    /**
     * Figure out which resources we have available and subscribe to them
     *
     * @param EventInterface $event
     */
    public function subscribe(EventInterface $event) {
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
     * Check if the public key used has access to this resource for this user
     *
     * @param EventInterface $event
     * @throws RuntimeException If public key does not have access to the resource
     */
    public function checkAccess(EventInterface $event) {
        if ($event->hasArgument('skipAccessControl') &&
            $event->getArgument('skipAccessControl') === true) {
            return;
        }

        $request = $event->getRequest();
        $aclAdapter = $event->getAccessControl();
        $resource = $event->getName();
        $publicKey = $request->getPublicKey();
        $user = $request->getUser();

        $hasAccess = $aclAdapter->hasAccess($publicKey, $resource, $user);

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

        throw new RuntimeException('Permission denied (public key)', 400);
    }

    /**
     * Load groups from the configured access control adapter
     *
     * @param EventInterface $event An event instance
     */
    public function loadGroups(EventInterface $event) {
        $query = new GroupQuery();
        $params = $event->getRequest()->query;

        if ($params->has('page')) {
            $query->page($params->get('page'));
        }

        if ($params->has('limit')) {
            $query->limit($params->get('limit'));
        }

        $response = $event->getResponse();
        $aclAdapter = $event->getAccessControl();

        // Create the model and set some pagination values
        $model = new GroupsModel();
        $model->setLimit($query->limit())
              ->setPage($query->page());

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
