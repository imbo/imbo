<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace Imbo\Storage;

use Imbo\Exception\ConfigurationException;
use Imbo\Exception\StorageException,
    Imbo\Exception\InvalidArgumentException,
    ChrisWhite\B2\Client,
    ChrisWhite\B2\Exceptions\NotFoundException,
    DateTime,
    DateTimeZone,
    Imbo\Helpers\Parameters;

/**
 * Backblaze B2 Cloud Storage adapter
 *
 * Parameters for this adapter:
 *
 * - (string) accountId Your B2 Account ID
 * - (string) applicationKey Your B2 Application Key
 * - (string) bucket The name of the bucket to store the files in. The bucket must exist prior
 *                   to using the B2 client.
 * - (string) bucketId The id of the bucket referenced by name. We currently need both.
 *
 * @author Mats Lindh <mats@lindh.no>
 * @package Storage
 */
class B2 implements StorageInterface {
    /**
     * B2 Client
     *
     * @var Client
     */
    private $client = null;

    /**
     * Parameters for the driver
     *
     * @var array
     */
    private $params = [
        // B2 Account ID
        'accountId' => null,

        // B2 Application Key
        'applicationKey' => null,

        // Name of the bucket to store the files in
        'bucket' => null,

        // ID of the bucket to store the files in
        'bucketId' => null,
    ];

    /**
     * Class constructor
     *
     * @param array $params Parameters for the adapter
     * @param Client $client A configured client
     */
    public function __construct(array $params = null, Client $client = null) {
        if ($params !== null) {
            $this->params = array_replace_recursive($this->params, $params);
        }

        if ($client !== null) {
            $this->client = $client;
        } else {
            $missingFields = Parameters::getEmptyOrMissingParamFields(
                ['accountId', 'applicationKey', 'bucketId', 'bucket'],
                $this->params
            );

            if ($missingFields) {
                throw new ConfigurationException(
                    sprintf(
                        'Missing required configuration parameters in %s: %s',
                        __CLASS__,
                        join(', ', $missingFields)
                    ),
                    500
                );
            }
        }
    }


    /**
     * @inheritDoc
     */
    public function store($user, $imageIdentifier, $imageData) {
        // Upload a file to a bucket. Returns a File object.
        $file = $this->getClient()->upload([
            'BucketId' => $this->getParam('bucketId'),
            'FileName' => $this->getImagePath($user, $imageIdentifier),
            'Body' => $imageData,
        ]);

        if (!$file) {
            throw new StorageException('Storage backend is not available.', 503);
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function delete($user, $imageIdentifier) {
        try {
            $this->getClient()->deleteFile([
                'BucketId' => $this->getParam('bucketId'),
                'BucketName' => $this->getParam('bucket'),
                'FileName' => $this->getImagePath($user, $imageIdentifier),
            ]);
        } catch (NotFoundException $e) {
            throw new StorageException('File not found.', 404);
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function getImage($user, $imageIdentifier) {
        try {
            return $this->getClient()->download([
                'BucketName' => $this->getParam('bucket'),
                'FileName' => $this->getImagePath($user, $imageIdentifier),
            ]);
        } catch (NotFoundException $e) {
            throw new StorageException('File not found.', 404);
        }
    }

    /**
     * @inheritDoc
     */
    public function getLastModified($user, $imageIdentifier) {
        try {
            $info = $this->getClient()->getFile([
                'BucketName' => $this->getParam('bucket'),
                'FileName' => $this->getImagePath($user, $imageIdentifier),
            ]);
        } catch (NotFoundException $e) {
            throw new StorageException('File not found.', 404);
        }

        return new DateTime('@' . $info->getUploadTimestamp(), new DateTimeZone('UTC'));
    }

    /**
     * @inheritDoc
     */
    public function getStatus() {
        if (!$this->getClient()) {
            return false;
        }

        if (!$this->getClient()->listBuckets()) {
            return false;
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function imageExists($user, $imageIdentifier) {
        try {
            return $this->getClient()->fileExists([
                'BucketId' => $this->getParam('bucketId'),
                'FileName' => $this->getImagePath($user, $imageIdentifier),
            ]);
        } catch (NotFoundException $e) {
            return false;
        }
    }

    /**
     * Get the current B2 client
     */
    protected function getClient() {
        if ($this->client === null) {
            $this->client = new Client($this->getParam('accountId'), $this->getParam('applicationKey'));
        }

        return $this->client;
    }

    /**
     * Get a parameter
     */
    protected function getParam($param) {
        if (!isset($this->params[$param])) {
            throw new InvalidArgumentException('Attempted to read invalid parameter', 500);
        }

        return $this->params[$param];
    }

    /**
     * Get the path to an image
     *
     * @param string $user The user which the image belongs to
     * @param string $imageIdentifier Image identifier
     * @return string
     */
    protected function getImagePath($user, $imageIdentifier) {
        return $user . '/' . $imageIdentifier;
    }
}
