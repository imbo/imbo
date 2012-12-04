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
    Imbo\Exception\StorageException,
    Imbo\Container,
    Imbo\ContainerAware,
    Imbo\Storage\StorageInterface;

/**
 * Storage operations event listener
 *
 * @package EventListener
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/imbo/imbo
 */
class StorageOperations implements ContainerAware, ListenerInterface {
    /**
     * @var Container
     */
    private $container;

    /**
     * @var StorageInterface
     */
    private $storage;

    /**
     * Class constructor
     *
     * @param StorageInterface $storage A storage adapter
     */
    public function __construct(StorageInterface $storage) {
        $this->storage = $storage;
    }

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
        $manager->attach('storage.image.delete', array($this, 'deleteImage'))
                ->attach('storage.image.load', array($this, 'loadImage'))
                ->attach('storage.image.insert', array($this, 'insertImage'));
    }

    /**
     * Delete an image
     *
     * @param EventInterface $event An event instance
     */
    public function deleteImage(EventInterface $event) {
        $request = $event->getRequest();
        $this->storage->delete($request->getPublicKey(), $request->getImageIdentifier());
    }

    /**
     * Load an image
     *
     * @param EventInterface $event An event instance
     */
    public function loadImage(EventInterface $event) {
        $request = $event->getRequest();
        $publicKey = $request->getPublicKey();
        $imageIdentifier = $request->getImageIdentifier();

        $imageData = $this->storage->getImage($publicKey, $imageIdentifier);
        $lastModified = $this->container->get('dateFormatter')->formatDate(
            $this->storage->getLastModified($publicKey, $imageIdentifier)
        );

        $event->getResponse()->getHeaders()->set('Last-Modified', $lastModified);
        $event->getResponse()->getImage()->setBlob($imageData);
    }

    /**
     * Insert an image
     *
     * @param EventInterface $event An event instance
     */
    public function insertImage(EventInterface $event) {
        $request = $event->getRequest();
        $image = $request->getImage();
        $response = $event->getResponse();

        try {
            $this->storage->store(
                $request->getPublicKey(),
                $image->getChecksum(),
                $image->getBlob()
            );
        } catch (StorageException $e) {
            $event->getManager()->trigger('db.image.delete', array(
                'imageIdentifier' => $image->getChecksum(),
            ));

            throw $e;
        }
    }
}
