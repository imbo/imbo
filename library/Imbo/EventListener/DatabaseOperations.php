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
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/imbo/imbo
 */

namespace Imbo\EventListener;

use Imbo\EventManager\EventInterface,
    Imbo\EventManager\EventManager,
    Imbo\Database\DatabaseInterface,
    Imbo\Container,
    Imbo\ContainerAware,
    DateTime;

/**
 * Database operations event listener
 *
 * @package EventListener
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/imbo/imbo
 */
class DatabaseOperations implements ContainerAware, ListenerInterface {
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
    public function attach(EventManager $manager) {
        $manager->attach('db.image.insert', array($this, 'insertImage'))
                ->attach('db.image.delete', array($this, 'deleteImage'))
                ->attach('db.image.load', array($this, 'loadImage'))
                ->attach('db.images.load', array($this, 'loadImages'))
                ->attach('db.metadata.delete', array($this, 'deleteMetadata'))
                ->attach('db.metadata.update', array($this, 'updateMetadata'))
                ->attach('db.metadata.load', array($this, 'loadMetadata'))
                ->attach('db.user.load', array($this, 'loadUser'));
    }

    /**
     * Insert an image
     *
     * @param EventInterface $event An event instance
     */
    public function insertImage(EventInterface $event) {
        $request = $event->getRequest();
        $response = $event->getResponse();

        $event->getDatabase()->insertImage(
            $request->getPublicKey(),
            $request->getImage()->getChecksum(),
            $request->getImage()
        );
    }

    /**
     * Delete an image
     *
     * @param EventInterface $event An event instance
     */
    public function deleteImage(EventInterface $event) {
        $request = $event->getRequest();

        $event->getDatabase()->deleteImage(
            $request->getPublicKey(),
            $request->getImageIdentifier()
        );
    }

    /**
     * Load an image
     *
     * @param EventInterface $event An event instance
     */
    public function loadImage(EventInterface $event) {
        $request = $event->getRequest();
        $response = $event->getResponse();

        $event->getDatabase()->load(
            $request->getPublicKey(),
            $request->getImageIdentifier(),
            $response->getImage()
        );
    }

    /**
     * Delete metadata
     *
     * @param EventInterface $event An event instance
     */
    public function deleteMetadata(EventInterface $event) {
        $request = $event->getRequest();

        $event->getDatabase()->deleteMetadata(
            $request->getPublicKey(),
            $request->getImageIdentifier()
        );
    }

    /**
     * Update metadata
     *
     * @param EventInterface $event An event instance
     */
    public function updateMetadata(EventInterface $event) {
        $request = $event->getRequest();

        $event->getDatabase()->updateMetadata(
            $request->getPublicKey(),
            $request->getImageIdentifier(),
            json_decode($request->getRawData(), true)
        );
    }

    /**
     * Load metadata
     *
     * @param EventInterface $event An event instance
     */
    public function loadMetadata(EventInterface $event) {
        $request = $event->getRequest();
        $response = $event->getResponse();
        $publicKey = $request->getPublicKey();
        $imageIdentifier = $request->getImageIdentifier();
        $database = $event->getDatabase();

        $response->setBody($database->getMetadata($publicKey, $imageIdentifier));
        $response->getHeaders()->set(
            'Last-Modified',
            $this->formatDate(
                $database->getLastModified($publicKey, $imageIdentifier)
            )
        );
    }

    /**
     * Load images
     *
     * @param EventInterface $event An event instance
     */
    public function loadImages(EventInterface $event) {
        $params = $event->getRequest()->getQuery();
        $query = $this->container->get('imagesQuery');

        if ($params->has('page')) {
            $query->page($params->get('page'));
        }

        if ($params->has('limit')) {
            $query->limit($params->get('limit'));
        }

        if ($params->has('metadata')) {
            $query->returnMetadata($params->get('metadata'));
        }

        if ($params->has('from')) {
            $query->from($params->get('from'));
        }

        if ($params->has('to')) {
            $query->to($params->get('to'));
        }

        if ($params->has('query')) {
            $data = json_decode($params->get('query'), true);

            if (is_array($data)) {
                $query->metadataQuery($data);
            }
        }

        $publicKey = $event->getRequest()->getPublicKey();
        $response = $event->getResponse();
        $database = $event->getDatabase();

        $images = $database->getImages($publicKey, $query);

        foreach ($images as &$image) {
            $image['added'] = $this->formatDate($image['added']);
            $image['updated'] = $this->formatDate($image['updated']);
        }

        $lastModified = $this->formatDate($database->getLastModified($publicKey));

        $response->setBody($images)
                 ->getHeaders()->set('Last-Modified', $lastModified);
    }

    /**
     * Load user data
     *
     * @param EventInterface $event An event instance
     */
    public function loadUser(EventInterface $event) {
        $request = $event->getRequest();
        $response = $event->getResponse();
        $publicKey = $request->getPublicKey();
        $database = $event->getDatabase();

        $numImages = $database->getNumImages($publicKey);
        $lastModified = $this->formatDate($database->getLastModified($publicKey));

        $response->setBody(array(
            'publicKey'    => $publicKey,
            'numImages'    => $numImages,
            'lastModified' => $lastModified,
        ));
        $response->getHeaders()->set('Last-Modified', $lastModified);
    }

    /**
     * Format a DateTime instance
     *
     * @param DateTime $date A DateTime instance
     * @return string A formatted date
     */
    private function formatDate(DateTime $date) {
        return $this->container->get('dateFormatter')->formatDate($date);
    }
}
