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
    Imbo\EventManager\EventManager,
    Imbo\Http\Request\RequestInterface,
    Imbo\EventListener\ListenerInterface,
    Imbo\Container,
    Imbo\ContainerAware,
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
class Metadata implements ContainerAware, ResourceInterface, ListenerInterface {
    /**
     * @var Container
     */
    private $container;

    /**
     * {@inheritdoc}
     */
    public function setContainer(Container $container) {
        $this->container = $container;
    }

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
    public function attach(EventManager $manager) {
        $manager->attach('metadata.get', array($this, 'get'))
                ->attach('metadata.post', array($this, 'post'))
                ->attach('metadata.put', array($this, 'put'))
                ->attach('metadata.delete', array($this, 'delete'))
                ->attach('metadata.head', array($this, 'head'))
                ->attach('metadata.post', array($this, 'validateMetadata'), 10)
                ->attach('metadata.put', array($this, 'validateMetadata'), 10);
    }

    /**
     * {@inheritdoc}
     */
    public function delete(EventInterface $event) {
        $request = $event->getRequest();
        $imageIdentifier = $request->getImageIdentifier();

        $event->getManager()->trigger('db.metadata.delete', array(
            'publicKey' => $request->getPublicKey(),
            'imageIdentifier' => $imageIdentifier,
        ));

        $event->getResponse()->setBody(array(
            'imageIdentifier' => $imageIdentifier,
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function put(EventInterface $event) {
        $request = $event->getRequest();

        $event->getManager()->trigger('db.metadata.delete', array(
            'publicKey' => $request->getPublicKey(),
            'imageIdentifier' => $request->getImageIdentifier(),
        ));

        $this->post($event);
    }

    /**
     * {@inheritdoc}
     */
    public function post(EventInterface $event) {
        $request = $event->getRequest();
        $metadata = $request->getRawData();

        $imageIdentifier = $request->getImageIdentifier();

        $event->getManager()->trigger('db.metadata.update', array(
            'publicKey' => $request->getPublicKey(),
            'imageIdentifier' => $imageIdentifier,
            'metadata' => json_decode($metadata, true),
        ));

        $event->getResponse()->setBody(array(
            'imageIdentifier' => $imageIdentifier,
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function get(EventInterface $event) {
        $request = $event->getRequest();
        $database = $event->getDatabase();
        $response = $event->getResponse();

        $publicKey = $request->getPublicKey();
        $imageIdentifier = $request->getImageIdentifier();
        $responseHeaders = $response->getHeaders();

        // See when this particular image was last updated
        $lastModified = $this->container->get('dateFormatter')->formatDate(
            $database->getLastModified($publicKey, $imageIdentifier)
        );

        // Generate an etag for the content
        $etag = '"' . md5($publicKey . $imageIdentifier . $lastModified) . '"';
        $responseHeaders->set('ETag', $etag);
        $responseHeaders->set('Last-Modified', $lastModified);

        $response->setBody($database->getMetadata($publicKey, $imageIdentifier));
    }

    /**
     * {@inheritdoc}
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
