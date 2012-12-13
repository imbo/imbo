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
 * @package Resources
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/imbo/imbo
 */

namespace Imbo\Resource;

use Imbo\EventManager\EventInterface,
    Imbo\EventListener\ListenerDefinition,
    Imbo\Http\Request\RequestInterface,
    Imbo\EventListener\ListenerInterface,
    Imbo\Exception\InvalidArgumentException;

/**
 * Metadata resource
 *
 * @package Resources
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/imbo/imbo
 */
class Metadata implements ResourceInterface, ListenerInterface {
    /**
     * {@inheritdoc}
     */
    public function getAllowedMethods() {
        return array(
            RequestInterface::METHOD_GET,
            RequestInterface::METHOD_POST,
            RequestInterface::METHOD_PUT,
            RequestInterface::METHOD_DELETE,
            RequestInterface::METHOD_HEAD,
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getDefinition() {
        return array(
            new ListenerDefinition('metadata.get', array($this, 'get')),
            new ListenerDefinition('metadata.post', array($this, 'post')),
            new ListenerDefinition('metadata.put', array($this, 'put')),
            new ListenerDefinition('metadata.delete', array($this, 'delete')),
            new ListenerDefinition('metadata.head', array($this, 'head')),
            new ListenerDefinition('metadata.post', array($this, 'validateMetadata'), 10),
            new ListenerDefinition('metadata.put', array($this, 'validateMetadata'), 10),
        );
    }

    /**
     * Handle DELETE requests
     *
     * @param EventInterface $event The current event
     */
    public function delete(EventInterface $event) {
        $event->getManager()->trigger('db.metadata.delete');
        $event->getResponse()->setBody(array(
            'imageIdentifier' => $event->getRequest()->getImageIdentifier(),
        ));
    }

    /**
     * Handle PUT requests
     *
     * @param EventInterface $event The current event
     */
    public function put(EventInterface $event) {
        $event->getManager()->trigger('db.metadata.delete')
                            ->trigger('db.metadata.update');
        $event->getResponse()->setBody(array(
            'imageIdentifier' => $event->getRequest()->getImageIdentifier(),
        ));
    }

    /**
     * Handle POST requests
     *
     * @param EventInterface $event The current event
     */
    public function post(EventInterface $event) {
        $event->getManager()->trigger('db.metadata.update');
        $event->getResponse()->setBody(array(
            'imageIdentifier' => $event->getRequest()->getImageIdentifier(),
        ));
    }

    /**
     * Handle GET requests
     *
     * @param EventInterface $event The current event
     */
    public function get(EventInterface $event) {
        $request = $event->getRequest();
        $response = $event->getResponse();

        $event->getManager()->trigger('db.metadata.load');

        $lastModified = $response->getLastModified();

        $hash = md5($request->getPublicKey() . $request->getImageIdentifier() . $lastModified);

        $response->getHeaders()->set('ETag', '"' . $hash . '"');
    }

    /**
     * Handle HEAD requests
     *
     * @param EventInterface $event The current event
     */
    public function head(EventInterface $event) {
        $this->get($event);

        // Remove body from the response, but keep everything else
        $event->getResponse()->setBody(null);
    }

    /**
     * Validate metadata found in the request body
     *
     * @param EventInterface $event The event instance
     * @throws InvalidArgumentException
     */
    public function validateMetadata(EventInterface $event){
        $request = $event->getRequest();
        $metadata = $request->getRawData();

        if (empty($metadata)) {
            throw new InvalidArgumentException('Missing JSON data', 400);
        } else {
            $metadata = json_decode($metadata, true);

            if ($metadata === null) {
                throw new InvalidArgumentException('Invalid JSON data', 400);
            }
        }
    }
}
