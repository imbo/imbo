<?php
/**
 * Imbo
 *
 * Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to
 * deal in the Software without restriction, including without limitation the
 * rights to use, copy, modify, merge, publish, distribute, sublicense, and/or
 * sell copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * * The above copyright notice and this permission notice shall be included in
 *   all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 *
 * @package EventListener
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @author Espen Hovlandsdal <espen@hovlandsdal.com>
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/imbo/imbo
 */

namespace Imbo\EventListener;

use Imbo\EventManager\EventInterface,
    Imbo\Http\Request\RequestInterface,
    Imbo\Exception\RuntimeException;

/**
 * Cross-Origin Resource Sharing (CORS) event listener
 *
 * This event listener will listen to all incoming OPTIONS requests
 * and adds the correct headers required for CORS to function properly -
 * all configured on a per-user/resource base.
 *
 * @package EventListener
 * @author Espen Hovlandsdal <espen@hovlandsdal.com>
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/imbo/imbo
 */
class Cors extends Listener implements ListenerInterface {
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
    public function getEvents() {
        $events = array();

        // Enable the event listener only for resources and methods specified
        foreach ($this->params['allowedMethods'] as $resource => $methods) {
            foreach ($methods as $method) {
                $events[] = $resource . '.' . strtolower($method) . '.pre';
            }

            // Always enable the listener for the OPTIONS method
            $events[] = $resource . '.options.pre';
        }

        return $events;
    }

    /**
     * {@inheritdoc}
     */
    public function invoke(EventInterface $event) {
        $container = $event->getContainer();
        $request   = $container->get('request');
        $origin    = $request->getHeaders()->get('Origin', '*');

        // Fall back if the passed origin is not allowed
        if (!$this->originIsAllowed($origin)) {
            return;
        }

        $response  = $container->get('response');
        $resource  = $request->getResource();
        $isOptions = $request->getMethod() == RequestInterface::METHOD_OPTIONS;

        $allowedMethods = array('OPTIONS');
        if (isset($this->params['allowedMethods'][$resource])) {
            $allowedMethods = array_merge($allowedMethods, $this->params['allowedMethods'][$resource]);
        }

        $headers = $response->getHeaders();
        $headers->set('Access-Control-Allow-Origin', $origin);

        if ($isOptions) {
            $headers->set('Access-Control-Allow-Methods', implode(', ', $allowedMethods));
            $headers->set('Access-Control-Allow-Headers', 'Content-Type, Accept');
            $headers->set('Access-Control-Max-Age', (int) $this->params['maxAge']);

            // Since this is an OPTIONS-request, there is no need for further parsing
            $response->setStatusCode('204', 'No Content');
            $event->haltApplication(true);
        }
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
     * @param  string $origin Origin to validate
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
