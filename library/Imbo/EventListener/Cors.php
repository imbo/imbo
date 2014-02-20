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

use Imbo\EventManager\EventInterface;

/**
 * Cross-Origin Resource Sharing (CORS) event listener
 *
 * This event listener will listen to all incoming OPTIONS requests
 * and adds the correct headers required for CORS to function properly -
 * all configured on a per-user/resource base.
 *
 * @author Espen Hovlandsdal <espen@hovlandsdal.com>
 * @package Event\Listeners
 */
class Cors implements ListenerInterface {
    /**
     * Parameters for the listener
     *
     * @var array
     */
    private $params = array(
        'allowedOrigins' => array(),
        'allowedMethods' => array(
            'index'    => array('GET', 'HEAD'),
            'image'    => array('GET', 'HEAD'),
            'images'   => array('GET', 'HEAD'),
            'metadata' => array('GET', 'HEAD'),
            'status'   => array('GET', 'HEAD'),
            'stats'    => array('GET', 'HEAD'),
            'user'     => array('GET', 'HEAD'),
            'shorturl' => array('GET', 'HEAD'),
        ),
        'maxAge'         => 3600,
    );

    /**
     * Whether the request matched an allowed method + origin
     *
     * @var boolean
     */
    private $requestAllowed = false;

    /**
     * Class constructor
     *
     * @param array $params Parameters for the listener
     */
    public function __construct(array $params = array()) {
        if ($params) {
            $this->params = array_replace($this->params, $params);

            // Clean up all origins for easier matching
            array_walk($this->params['allowedOrigins'], function(&$origin) {
                $origin = strtolower($origin);
                $origin = rtrim($origin, '/');
            });
        }
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents() {
        return array(
            'route.match' => 'subscribe',
            'response.send' => array('setExposedHeaders' => 5),
        );
    }

    /**
     * Subscribe to events based on configuration parameters
     *
     * @param EventInterface $event The event instance
     */
    public function subscribe(EventInterface $event) {
        $events = array();

        // Enable the event listener only for resources and methods specified
        foreach ($this->params['allowedMethods'] as $resource => $methods) {
            foreach ($methods as $method) {
                $eventName = $resource . '.' . strtolower($method);
                $events[$eventName] = array('invoke' => 100);
            }

            // Always enable the listener for the OPTIONS method
            $eventName = $resource . '.options';
            $events[$eventName] = array('options' => 20);
        }

        $manager = $event->getManager();
        $manager->addCallbacks($event->getHandler(), $events);

        // Add OPTIONS to the Allow header
        $event->getResponse()->headers->set('Allow', 'OPTIONS', false);
    }

    /**
     * Right before the response is sent to the client, whitelist all included Imbo-headers in the
     * "Access-Control-Expose-Headers"-header
     *
     * @param EventInterface $event The event instance
     */
    public function setExposedHeaders(EventInterface $event) {
        // If this request was disallowed, don't expose any headers
        if (!$this->requestAllowed) {
            return;
        }

        $headers = array(
            // The ResponseSender-listener will add this header and send the response,
            // so we have no way to pick it up - instead we'll always whitelist it
            'X-Imbo-ImageIdentifier'
        );

        foreach ($event->getResponse()->headers as $header => $value) {
            if (strpos($header, 'x-imbo') === 0) {
                $headers[] = implode('-', array_map('ucfirst', explode('-', $header)));;
            }
        }

        $event->getResponse()->headers->add(array(
            'Access-Control-Expose-Headers' => implode(', ', $headers)
        ));
    }

    /**
     * Handle the OPTIONS requests
     *
     * @param EventInterface $event The event instance
     */
    public function options(EventInterface $event) {
        $request = $event->getRequest();
        $response = $event->getResponse();
        $origin = $request->headers->get('Origin', '*');

        // This is an OPTIONS request, send 204 since no more content will follow
        $response->setStatusCode(204);

        // Fall back if the passed origin is not allowed
        if (!$this->originIsAllowed($origin)) {
            return;
        }

        $resource = (string) $request->getRoute();

        $allowedMethods = array('OPTIONS');

        if (isset($this->params['allowedMethods'][$resource])) {
            $allowedMethods = array_merge($allowedMethods, $this->params['allowedMethods'][$resource]);
        }

        $response->headers->add(array(
            'Access-Control-Allow-Origin' => $origin,
            'Access-Control-Allow-Methods' => implode(', ', $allowedMethods),
            'Access-Control-Allow-Headers' => 'Content-Type, Accept',
            'Access-Control-Max-Age' => (int) $this->params['maxAge'],
        ));

        // Since this is an OPTIONS-request, there is no need for further parsing
        $event->stopPropagation();
    }

    /**
     * Handle other requests
     *
     * @param EventInterface $event The event instance
     */
    public function invoke(EventInterface $event) {
        $request = $event->getRequest();
        $resource = (string) $request->getRoute();
        $method = $request->getMethod();
        $allowed = $this->params['allowedMethods'];

        if (!isset($allowed[$resource]) || !in_array($method, $allowed[$resource])) {
            // The listener is not configured for the current method/resource combination
            return;
        }

        $origin = $request->headers->get('Origin', '*');

        // Fall back if the passed origin is not allowed
        if (!$this->originIsAllowed($origin)) {
            return;
        }

        // Flag this as an allowed request
        $this->requestAllowed = true;

        $event->getResponse()->headers->add(array(
            'Access-Control-Allow-Origin' => $origin,
        ));
    }

    /**
     * Returns an array of allowed origins
     *
     * @return array The defined allowed origins
     */
    public function getAllowedOrigins() {
        return $this->params['allowedOrigins'];
    }

    /**
     * Check if the given origin is defined as an allowed origin
     *
     * @param string $origin Origin to validate
     * @return boolean True if allowed, false otherwise
     */
    private function originIsAllowed($origin) {
        // Global wildcard defined?
        if (in_array('*', $this->params['allowedOrigins'])) {
            return true;
        }

        // Origin defined?
        if (in_array($origin, $this->params['allowedOrigins'])) {
            return true;
        }

        return false;
    }
}
