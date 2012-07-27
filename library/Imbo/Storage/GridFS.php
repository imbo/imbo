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
 * @package Storage
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/imbo/imbo
 */

namespace Imbo\Storage;

use Imbo\Image\ImageInterface,
    Imbo\Exception\StorageException,
    Imbo\Exception,
    Mongo,
    MongoGridFS,
    MongoCursorException,
    DateTime;

/**
 * GridFS (MongoDB) database driver
 *
 * A GridFS storage driver for Imbo
 *
 * Valid parameters for this driver:
 *
 * - <pre>(string) databaseName</pre> Name of the database. Defaults to 'imbo_storage'
 * - <pre>(string) server</pre> The server string to use when connecting to MongoDB. Defaults to
 *                              'mongodb://localhost:27017'
 * - <pre>(array) options</pre> Options to use when creating the Mongo instance. Defaults to
 *                              array('connect' => true, 'timeout' => 1000).
 * - <pre>(string) slaveOk</pre> If reads should be sent to secondary members of a replica set for
 *                               all possible queries. Defaults to false.
 *
 * @package Storage
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/imbo/imbo
 */
class GridFS implements StorageInterface {
    /**
     * Mongo instance
     *
     * @var \Mongo
     */
    private $mongo;

    /**
     * The grid instance
     *
     * @var \MongoGridFS
     */
    private $grid;

    /**
     * Parameters for the driver
     *
     * @var array
     */
    private $params = array(
        // Database name
        'databaseName' => 'imbo_storage',

        // Server string and ctor options
        'server'  => 'mongodb://localhost:27017',
        'options' => array('connect' => true, 'timeout' => 1000),

        // Other options
        'slaveOk' => true,
    );

    /**
     * Class constructor
     *
     * @param array $params Parameters for the driver
     * @param \Mongo $mongo Mongo instance
     * @param \MongoGridFS $grid MongoGridFS instance
     */
    public function __construct(array $params = null, Mongo $mongo = null, MongoGridFS $grid = null) {
        if ($params !== null) {
            $this->params = array_replace_recursive($this->params, $params);
        }

        if ($mongo !== null) {
            $this->mongo = $mongo;
        }

        if ($grid !== null) {
            $this->grid = $grid;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function store($publicKey, $imageIdentifier, $imageData) {
        if ($this->imageExists($publicKey, $imageIdentifier, true) !== false) {
            $e = new StorageException('Image already exists', 400);
            $e->setImboErrorCode(Exception::IMAGE_ALREADY_EXISTS);

            throw $e;
        }

        $extra = array(
            'publicKey' => $publicKey,
            'imageIdentifier' => $imageIdentifier,
            'created' => time(),
        );

        $options = array('safe' => true);

        try {
            $this->getGrid()->storeBytes($imageData, $extra, $options);
        } catch (MongoCursorException $e) {
            throw new StorageException('Could not store file', 500, $e);
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function delete($publicKey, $imageIdentifier) {
        if (($file = $this->imageExists($publicKey, $imageIdentifier)) === false) {
            throw new StorageException('File not found', 404);
        }

        return $this->getGrid()->delete($file->file['_id']);
    }

    /**
     * {@inheritdoc}
     */
    public function getImage($publicKey, $imageIdentifier) {
        if (($file = $this->imageExists($publicKey, $imageIdentifier)) === false) {
            throw new StorageException('File not found', 404);
        }

        return $file->getBytes();
    }

    /**
     * {@inheritdoc}
     */
    public function getLastModified($publicKey, $imageIdentifier) {
        if (($file = $this->imageExists($publicKey, $imageIdentifier)) === false) {
            throw new StorageException('File not found', 404);
        }

        $timestamp = $file->file['created'];

        return new DateTime('@' . $timestamp);
    }

    /**
     * {@inheritdoc}
     */
    public function getStatus() {
        return $this->getMongo()->connect();
    }

    /**
     * Get the grid instance
     *
     * @return \MongoGridFS
     */
    protected function getGrid() {
        if ($this->grid === null) {
            try {
                $database = $this->getMongo()->selectDB($this->params['databaseName']);
                $this->grid = $database->getGridFS();
            } catch (InvalidArgumentException $d) {
                throw new DatabaseException('Could not fetch grid FS', 500);
            }
        }

        return $this->grid;
    }

    /**
     * Get the mongo instance
     *
     * @return \Mongo
     */
    protected function getMongo() {
        if ($this->mongo === null) {
            try {
                $this->mongo = new Mongo($this->params['server'], $this->params['options']);

                if ($this->params['slaveOk'] === true) {
                    $this->mongo->setSlaveOkay(true);
                }
            } catch (MongoException $e) {
                throw new StorageException('Could not connect to database', 500);
            }
        }

        return $this->mongo;
    }

    /**
     * Check if an image exists
     *
     * @param string $publicKey The public key of the user
     * @param string $imageIdentifier The image identifier
     * @return boolean|\MongoGridFSFile Returns false if the file does not exist or an instance of
     *                                  MongoGridFSFile if the file exists
     */
    private function imageExists($publicKey, $imageIdentifier) {
        $cursor = $this->getGrid()->find(array(
            'publicKey' => $publicKey,
            'imageIdentifier' => $imageIdentifier
        ));

        if ($cursor->count()) {
            return $cursor->getNext();
        }

        return false;
    }
}
