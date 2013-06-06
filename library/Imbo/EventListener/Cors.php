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
            'image'    => array('GET', 'HEAD'),
            'images'   => array('GET', 'HEAD'),
            'metadata' => array('GET', 'HEAD'),
            'status'   => array('GET', 'HEAD'),
            'user'     => array('GET', 'HEAD'),
        ),
        'maxAge'         => 3600,
    );

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
    public function getDefinition() {
        $definition = array();
        $priority = 20;

        // Enable the event listener only for resources and methods specified
        foreach ($this->params['allowedMethods'] as $resource => $methods) {
            foreach ($methods as $method) {
                $event = $resource . '.' . strtolower($method);
                $definition[] = new ListenerDefinition($event, array($this, 'invoke'), 20);
            }

            // Always enable the listener for the OPTIONS method
            $event = $resource . '.options';
            $definition[] = new ListenerDefinition($event, array($this, 'options'), 20);
        }

        return $definition;
    }

    /**
     * Handle the OPTIONS requests
     *
     * @param EventInterface $event The event instance
     */
    public function options(EventInterface $event) {
        $request = $event->getRequest();
        $origin = $request->headers->get('Origin', '*');

        // Fall back if the passed origin is not allowed
        if (!$this->originIsAllowed($origin)) {
            return;
        }

        $response = $event->getResponse();
        $resource = $request->getResource();

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
        $response->setStatusCode(204);
        $event->stopPropagation(true);
    }

    /**
     * Handle other requests
     *
     * @param EventInterface $event The event instance
     */
    public function invoke(EventInterface $event) {
        $origin = $event->getRequest()->headers->get('Origin', '*');

        // Fall back if the passed origin is not allowed
        if (!$this->originIsAllowed($origin)) {
            return;
        }

        $event->getResponse()->headers->add(array(
            'Access-Control-Allow-Origin' => $origin,
            'Access-Control-Expose-Headers' => 'X-Imbo-Error-Internalcode'
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
