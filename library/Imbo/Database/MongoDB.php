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

use Imbo\Image\ImageInterface,
    Imbo\Resource\Images\QueryInterface,
    Imbo\Exception\DatabaseException,
    Imbo\Exception,
    Mongo,
    MongoException,
    MongoCollection,
    DateTime;

/**
 * MongoDB database driver
 *
 * A MongoDB database driver for Imbo
 *
 * Valid parameters for this driver:
 *
 * - <pre>(string) database</pre> Name of the database. Defaults to 'imbo'
 * - <pre>(string) collection</pre> Name of the collection to store data in. Defaults to 'images'
 *
 * @package Database
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/imbo/imbo
 */
class MongoDB implements DatabaseInterface {
    /**
     * The collection instance used by the driver
     *
     * @var MongoCollection
     */
    private $collection;

    /**
     * Parameters for the driver
     *
     * @var array
     */
    private $params = array(
        'databaseName'   => 'imbo',
        'collectionName' => 'images',
    );

    /**
     * Class constructor
     *
     * @param array $params Parameters for the driver
     * @param MongoCollection $collection MongoDB collection instance
     */
    public function __construct(array $params = null, MongoCollection $collection = null) {
        if ($params !== null) {
            $this->params = array_merge($this->params, $params);
        }

        if ($collection === null) {
            // @codeCoverageIgnoreStart
            try {
                $mongo      = new Mongo();
                $database   = $mongo->{$this->params['databaseName']};
                $collection = $database->{$this->params['collectionName']};
            } catch (MongoException $e) {
                throw new DatabaseException('Could not connect to database', 500);
            }
        }
        // @codeCoverageIgnoreEnd

        $this->collection = $collection;
    }

    /**
     * @see Imbo\Database\DatabaseInterface::insertImage()
     */
    public function insertImage($publicKey, $imageIdentifier, ImageInterface $image) {
        $now = time();

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
            'checksum'        => md5($image->getBlob()),
        );

        try {
            // See if the image already exists
            $row = $this->collection->findOne(array('publicKey' => $publicKey, 'imageIdentifier' => $imageIdentifier));

            if ($row) {
                $e = new DatabaseException('Image already exists', 400);
                $e->setImboErrorCode(Exception::IMAGE_ALREADY_EXISTS);

                throw $e;
            }

            $this->collection->insert($data, array('safe' => true));
        } catch (MongoException $e) {
            throw new DatabaseException('Unable to save image data', 500, $e);
        }

        return true;
    }

    /**
     * @see Imbo\Database\DatabaseInterface::deleteImage()
     */
    public function deleteImage($publicKey, $imageIdentifier) {
        try {
            $this->collection->remove(
                array('publicKey' => $publicKey, 'imageIdentifier' => $imageIdentifier),
                array('justOne' => true, 'safe' => true)
            );
        } catch (MongoException $e) {
            throw new DatabaseException('Unable to delete image data', 500, $e);
        }

        return true;
    }

    /**
     * @see Imbo\Database\DatabaseInterface::updateMetadata()
     */
    public function updateMetadata($publicKey, $imageIdentifier, array $metadata) {
        try {
            // Fetch existing metadata and merge with the incoming data
            $existing = $this->getMetadata($publicKey, $imageIdentifier);
            $updatedMetadata = array_merge($existing, $metadata);

            $this->collection->update(
                array('publicKey' => $publicKey, 'imageIdentifier' => $imageIdentifier),
                array('$set' => array('updated' => time(), 'metadata' => $updatedMetadata)),
                array('safe' => true, 'multiple' => false)
            );
        } catch (MongoException $e) {
            throw new DatabaseException('Unable to edit image data', 500, $e);
        }

        return true;
    }

    /**
     * @see Imbo\Database\DatabaseInterface::getMetadata()
     */
    public function getMetadata($publicKey, $imageIdentifier) {
        try {
            $data = $this->collection->findOne(array('publicKey' => $publicKey, 'imageIdentifier' => $imageIdentifier));
        } catch (MongoException $e) {
            throw new DatabaseException('Unable to fetch image metadata', 500, $e);
        }

        if ($data === null) {
            throw new DatabaseException('Image not found', 404);
        }

        return isset($data['metadata']) ? $data['metadata'] : array();
    }

    /**
     * @see Imbo\Database\DatabaseInterface::deleteMetadata()
     */
    public function deleteMetadata($publicKey, $imageIdentifier) {
        try {
            $this->collection->update(
                array('publicKey' => $publicKey, 'imageIdentifier' => $imageIdentifier),
                array('$set' => array('metadata' => array())),
                array('safe' => true, 'multiple' => false)
            );
        } catch (MongoException $e) {
            throw new DatabaseException('Unable to remove metadata', 500, $e);
        }

        return true;
    }

    /**
     * @see Imbo\Database\DatabaseInterface::getImages()
     */
    public function getImages($publicKey, QueryInterface $query) {
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
                $tmp['$gt'] = $from;
            }

            if ($to !== null) {
                $tmp['$lt'] = $to;
            }

            $queryData['added'] = $tmp;
        }

        $metadataQuery = $query->metadataQuery();

        if (!empty($metadataQuery)) {
            $queryData['metadata'] = $metadataQuery;
        }

        // Fields to fetch
        $fields = array('extension', 'added', 'checksum', 'updated', 'publicKey', 'imageIdentifier', 'mime', 'name', 'size', 'width', 'height');

        if ($query->returnMetadata()) {
            $fields[] = 'metadata';
        }

        try {
            $cursor = $this->collection->find($queryData, $fields)
                                       ->limit($query->limit())
                                       ->sort(array('added' => -1));

            // Skip some images if a page has been set
            if (($page = $query->page()) > 1) {
                $skip = $query->limit() * ($page - 1);
                $cursor->skip($skip);
            }

            foreach ($cursor as $image) {
                unset($image['_id']);
                $images[] = $image;
            }
        } catch (MongoException $e) {
            throw new DatabaseException('Unable to search for images', 500, $e);
        }

        return $images;
    }

    /**
     * @see Imbo\Database\DatabaseInterface::load()
     */
    public function load($publicKey, $imageIdentifier, ImageInterface $image) {
        try {
            $data = $this->collection->findOne(
                array('publicKey' => $publicKey, 'imageIdentifier' => $imageIdentifier),
                array('name', 'size', 'width', 'height', 'mime', 'extension')
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
     * @see Imbo\Database\DatabaseInterface::getLastModified()
     */
    public function getLastModified($publicKey, $imageIdentifier = null, $formatted = false) {
        try {
            // Query on the public key
            $query = array('publicKey' => $publicKey);

            if ($imageIdentifier) {
                // We want information about a single image. Add the identifier to the query
                $query['imageIdentifier'] = $imageIdentifier;
            }

            // Create the cursor
            $cursor = $this->collection->find($query, array('updated'))
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

        // Create a new datetime instance
        $date = new DateTime('@' . $data['updated']);

        if ($formatted) {
            return $date->format('D, d M Y H:i:s') . ' GMT';
        }

        return $date;
    }

    /**
     * @see Imbo\Database\DatabaseInterface::getNumImages()
     */
    public function getNumImages($publicKey) {
        try {
            $query = array(
                'publicKey' => $publicKey,
            );

            $result = (int) $this->collection->find($query)->count();

            return $result;
        } catch (MongoException $e) {
            throw new DatabaseException('Unable to fetch information from the database', 500, $e);
        }
    }
}
