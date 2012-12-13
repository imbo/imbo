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
 * @package Database
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/imbo/imbo
 */

namespace Imbo\Database;

use Imbo\Image\Image,
    Imbo\Resource\Images\Query,
    Imbo\Exception\DatabaseException,
    Imbo\Exception,
    Mongo,
    MongoCollection,
    MongoException,
    InvalidArgumentException,
    DateTime;

/**
 * MongoDB database driver
 *
 * A MongoDB database driver for Imbo
 *
 * Valid parameters for this driver:
 *
 * - (string) databaseName Name of the database. Defaults to 'imbo'
 * - (string) collectionName Name of the collection to store data in. Defaults to 'images'
 * - (string) server The server string to use when connecting to MongoDB. Defaults to
 *                              'mongodb://localhost:27017'
 * - (array) options Options to use when creating the Mongo instance. Defaults to
 *                              array('connect' => true, 'timeout' => 1000).
 * - (string) slaveOk If reads should be sent to secondary members of a replica set for all
 *                    possible queries. Defaults to false.
 *
 * @package Database
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/imbo/imbo
 */
class MongoDB implements DatabaseInterface {
    /**
     * Mongo instance
     *
     * @var \Mongo
     */
    private $mongo;

    /**
     * The collection instance used by the driver
     *
     * @var \MongoCollection
     */
    private $collection;

    /**
     * Parameters for the driver
     *
     * @var array
     */
    private $params = array(
        // Database and collection names
        'databaseName'   => 'imbo',
        'collectionName' => 'images',

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
     * @param \MongoCollection $collection MongoCollection instance
     */
    public function __construct(array $params = null, Mongo $mongo = null, MongoCollection $collection = null) {
        if ($params !== null) {
            $this->params = array_replace_recursive($this->params, $params);
        }

        if ($mongo !== null) {
            $this->mongo = $mongo;
        }

        if ($collection !== null) {
            $this->collection = $collection;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function insertImage($publicKey, $imageIdentifier, Image $image) {
        $now = time();

        if ($this->imageExists($publicKey, $imageIdentifier)) {
            try {
                $this->getCollection()->update(
                    array('publicKey' => $publicKey, 'imageIdentifier' => $imageIdentifier),
                    array('$set' => array('updated' => $now)),
                    array('safe' => true, 'multiple' => false)
                );

                return true;
            } catch (MongoException $e) {
                throw new DatabaseException('Unable to save image data', 500, $e);
            }
        }

        $data = array(
            'size'            => $image->getFilesize(),
            'publicKey'       => $publicKey,
            'imageIdentifier' => $imageIdentifier,
            'extension'       => $image->getExtension(),
            'mime'            => $image->getMimeType(),
            'metadata'        => array(),
            'added'           => $now,
            'updated'         => $now,
            'width'           => $image->getWidth(),
            'height'          => $image->getHeight(),
            'checksum'        => $image->getChecksum(),
        );

        try {
            $this->getCollection()->insert($data, array('safe' => true));
        } catch (MongoException $e) {
            throw new DatabaseException('Unable to save image data', 500, $e);
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteImage($publicKey, $imageIdentifier) {
        try {
            $data = $this->getCollection()->findOne(array(
                'publicKey' => $publicKey,
                'imageIdentifier' => $imageIdentifier,
            ));

            if ($data === null) {
                throw new DatabaseException('Image not found', 404);
            }

            $this->getCollection()->remove(
                array('publicKey' => $publicKey, 'imageIdentifier' => $imageIdentifier),
                array('justOne' => true, 'safe' => true)
            );
        } catch (MongoException $e) {
            throw new DatabaseException('Unable to delete image data', 500, $e);
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function updateMetadata($publicKey, $imageIdentifier, array $metadata) {
        try {
            // Fetch existing metadata and merge with the incoming data
            $existing = $this->getMetadata($publicKey, $imageIdentifier);
            $updatedMetadata = array_merge($existing, $metadata);

            $this->getCollection()->update(
                array('publicKey' => $publicKey, 'imageIdentifier' => $imageIdentifier),
                array('$set' => array('updated' => time(), 'metadata' => $updatedMetadata)),
                array('safe' => true, 'multiple' => false)
            );
        } catch (MongoException $e) {
            throw new DatabaseException('Unable to update meta data', 500, $e);
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getMetadata($publicKey, $imageIdentifier) {
        try {
            $data = $this->getCollection()->findOne(array(
                'publicKey' => $publicKey,
                'imageIdentifier' => $imageIdentifier,
            ));
        } catch (MongoException $e) {
            throw new DatabaseException('Unable to fetch meta data', 500, $e);
        }

        if ($data === null) {
            throw new DatabaseException('Image not found', 404);
        }

        return isset($data['metadata']) ? $data['metadata'] : array();
    }

    /**
     * {@inheritdoc}
     */
    public function deleteMetadata($publicKey, $imageIdentifier) {
        try {
            $data = $this->getCollection()->findOne(array(
                'publicKey' => $publicKey,
                'imageIdentifier' => $imageIdentifier,
            ));

            if ($data === null) {
                throw new DatabaseException('Image not found', 404);
            }

            $this->getCollection()->update(
                array('publicKey' => $publicKey, 'imageIdentifier' => $imageIdentifier),
                array('$set' => array('metadata' => array())),
                array('safe' => true, 'multiple' => false)
            );
        } catch (MongoException $e) {
            throw new DatabaseException('Unable to delete meta data', 500, $e);
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getImages($publicKey, Query $query) {
        // Initialize return value
        $images = array();

        // Query data
        $queryData = array(
            'publicKey' => $publicKey,
        );

        $from = $query->from();
        $to = $query->to();

        if ($from || $to) {
            $tmp = array();

            if ($from !== null) {
                $tmp['$gte'] = $from;
            }

            if ($to !== null) {
                $tmp['$lte'] = $to;
            }

            $queryData['added'] = $tmp;
        }

        $metadataQuery = $query->metadataQuery();

        if (!empty($metadataQuery)) {
            foreach ($metadataQuery as $key => $value) {
                $queryData['metadata.' . $key] = $value;
            }
        }

        // Fields to fetch
        $fields = array('extension', 'added', 'checksum', 'updated', 'publicKey', 'imageIdentifier', 'mime', 'size', 'width', 'height');

        if ($query->returnMetadata()) {
            $fields[] = 'metadata';
        }

        try {
            $cursor = $this->getCollection()->find($queryData, $fields)
                                            ->limit($query->limit())
                                            ->sort(array('added' => -1));

            // Skip some images if a page has been set
            if (($page = $query->page()) > 1) {
                $skip = $query->limit() * ($page - 1);
                $cursor->skip($skip);
            }

            foreach ($cursor as $image) {
                unset($image['_id']);
                $image['added'] = new DateTime('@' . $image['added']);
                $image['updated'] = new DateTime('@' . $image['updated']);
                $images[] = $image;
            }
        } catch (MongoException $e) {
            throw new DatabaseException('Unable to search for images', 500, $e);
        }

        return $images;
    }

    /**
     * {@inheritdoc}
     */
    public function load($publicKey, $imageIdentifier, Image $image) {
        try {
            $data = $this->getCollection()->findOne(
                array('publicKey' => $publicKey, 'imageIdentifier' => $imageIdentifier),
                array('size', 'width', 'height', 'mime', 'extension')
            );
        } catch (MongoException $e) {
            throw new DatabaseException('Unable to fetch image data', 500, $e);
        }

        if ($data === null) {
            throw new DatabaseException('Image not found', 404);
        }

        $image->setWidth($data['width'])
              ->setHeight($data['height'])
              ->setMimeType($data['mime'])
              ->setExtension($data['extension']);

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getLastModified($publicKey, $imageIdentifier = null) {
        try {
            // Query on the public key
            $query = array('publicKey' => $publicKey);

            if ($imageIdentifier) {
                // We want information about a single image. Add the identifier to the query
                $query['imageIdentifier'] = $imageIdentifier;
            }

            // Create the cursor
            $cursor = $this->getCollection()->find($query, array('updated'))
                                            ->limit(1)
                                            ->sort(array(
                                                'updated' => MongoCollection::DESCENDING,
                                            ));

            // Fetch the next row
            $data = $cursor->getNext();
        } catch (MongoException $e) {
            throw new DatabaseException('Unable to fetch image data', 500, $e);
        }

        if ($data === null && $imageIdentifier) {
            throw new DatabaseException('Image not found', 404);
        } else if ($data === null) {
            $data = array('updated' => time());
        }

        return new DateTime('@' . $data['updated']);
    }

    /**
     * {@inheritdoc}
     */
    public function getNumImages($publicKey) {
        try {
            $query = array(
                'publicKey' => $publicKey,
            );

            $result = (int) $this->getCollection()->find($query)->count();

            return $result;
        } catch (MongoException $e) {
            throw new DatabaseException('Unable to fetch information from the database', 500, $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getStatus() {
        return $this->getMongo()->connect();
    }

    /**
     * {@inheritdoc}
     */
    public function getImageMimeType($publicKey, $imageIdentifier) {
        try {
            $data = $this->getCollection()->findOne(array(
                'publicKey' => $publicKey,
                'imageIdentifier' => $imageIdentifier,
            ));
        } catch (MongoException $e) {
            throw new DatabaseException('Unable to fetch image meta data', 500, $e);
        }

        if ($data === null) {
            throw new DatabaseException('Image not found', 404);
        }

        return $data['mime'];
    }

    /**
     * {@inheritdoc}
     */
    public function imageExists($publicKey, $imageIdentifier) {
        $data = $this->getCollection()->findOne(array(
            'publicKey' => $publicKey,
            'imageIdentifier' => $imageIdentifier,
        ));

        return $data !== null;
    }

    /**
     * Get the mongo collection instance
     *
     * @return \MongoCollection
     */
    protected function getCollection() {
        if ($this->collection === null) {
            try {
                $this->collection = $this->getMongo()->selectCollection(
                    $this->params['databaseName'],
                    $this->params['collectionName']
                );
            } catch (InvalidArgumentException $d) {
                throw new DatabaseException('Could not select collection', 500);
            }
        }

        return $this->collection;
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
                throw new DatabaseException('Could not connect to database', 500);
            }
        }

        return $this->mongo;
    }
}
