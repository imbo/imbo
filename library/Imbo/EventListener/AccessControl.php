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
    Imbo\Http\Request\Request,
    Imbo\Exception\RuntimeException;

/**
 * Access control event listener
 *
 * This event listener will listen to all access-controlled resources and check if the public key
 * has access to the requested resource. If the public key does not have access to the resource,
 * the listener will throw an exception resulting in a HTTP response with 400 Bad Request.
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
        ];
    }

    public function subscribe(EventInterface $event) {
        $resources = $event->getAccessControl()->getReadWriteResources();

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
     * {@inheritdoc}
     */
    public function checkAccess(EventInterface $event) {
        $request = $event->getRequest();
        $response = $event->getResponse();
        $accessControl = $event->getAccessControl();
        $query = $request->query;
        $resource = $event->getName();

        $hasAccess = $accessControl->hasAccess(
            $request->getPublicKey(),
            $resource,
            $request->getUser()
        );

        if (!$hasAccess) {
            throw new RuntimeException('Permission denied (public key)', 400);
        }
    }
}
