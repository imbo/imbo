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

use Imbo\Exception\StorageException,
    Aws\S3\S3Client,
    Aws\S3\Exception\NoSuchKeyException,
    Aws\S3\Exception\S3Exception,
    DateTime,
    DateTimeZone;

/**
 * Amazon Simple Storage Service storage adapter
 *
 * Parameters for this adapter:
 *
 * - (string) key Your AWS access key
 * - (string) secret Your AWS secret key
 * - (string) bucket The name of the bucket to store the files in. The bucket should exist prior
 *                   to using this client. Imbo will not try to automatically add the bucket for
 *                   you.
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Storage
 */
class S3 implements StorageInterface {
    /**
     * S3 client
     *
     * @var S3Client
     */
    private $client;

    /**
     * Parameters for the driver
     *
     * @var array
     */
    private $params = array(
        // Access key
        'key' => null,

        // Secret key
        'secret' => null,

        // Name of the bucket to store the files in
        'bucket' => null,

        // Region
        'region' => null,
    );

    /**
     * Class constructor
     *
     * @param array $params Parameters for the adapter
     * @param S3Client $client Configured S3Client instance
     */
    public function __construct(array $params = null, S3Client $client = null) {
        if ($params !== null) {
            $this->params = array_replace_recursive($this->params, $params);
        }

        if ($client !== null) {
            $this->client = $client;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function store($publicKey, $imageIdentifier, $imageData) {
        try {
            $this->getClient()->putObject(array(
                'Bucket' => $this->params['bucket'],
                'Key' => $this->getImagePath($publicKey, $imageIdentifier),
                'Body' => $imageData,
            ));
        } catch (S3Exception $e) {
            throw new StorageException('Could not store image', 500);
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function delete($publicKey, $imageIdentifier) {
        if (!$this->imageExists($publicKey, $imageIdentifier)) {
            throw new StorageException('File not found', 404);
        }

        $this->getClient()->deleteObject(array(
            'Bucket' => $this->params['bucket'],
            'Key' => $this->getImagePath($publicKey, $imageIdentifier),
        ));

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getImage($publicKey, $imageIdentifier) {
        try {
            $model = $this->getClient()->getObject(array(
                'Bucket' => $this->params['bucket'],
                'Key' => $this->getImagePath($publicKey, $imageIdentifier),
            ));
        } catch (NoSuchKeyException $e) {
            throw new StorageException('File not found', 404);
        }

        return (string) $model->get('Body');
    }

    /**
     * {@inheritdoc}
     */
    public function getLastModified($publicKey, $imageIdentifier) {
        try {
            $model = $this->getClient()->headObject(array(
                'Bucket' => $this->params['bucket'],
                'Key' => $this->getImagePath($publicKey, $imageIdentifier),
            ));
        } catch (NoSuchKeyException $e) {
            throw new StorageException('File not found', 404);
        }

        return new DateTime($model->get('LastModified'), new DateTimeZone('UTC'));
    }

    /**
     * {@inheritdoc}
     */
    public function getStatus() {
        try {
            $this->getClient()->headBucket(array(
                'Bucket' => $this->params['bucket'],
            ));
        } catch (S3Exception $e) {
            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function imageExists($publicKey, $imageIdentifier) {
        try {
            $this->getClient()->headObject(array(
                'Bucket' => $this->params['bucket'],
                'Key' => $this->getImagePath($publicKey, $imageIdentifier),
            ));
        } catch (NoSuchKeyException $e) {
            return false;
        }

        return true;
    }

    /**
     * Get the path to an image
     *
     * @param string $publicKey The key
     * @param string $imageIdentifier Image identifier
     * @return string
     */
    private function getImagePath($publicKey, $imageIdentifier) {
        return implode('/', array(
            $publicKey[0],
            $publicKey[1],
            $publicKey[2],
            $publicKey,
            $imageIdentifier[0],
            $imageIdentifier[1],
            $imageIdentifier[2],
            $imageIdentifier,
        ));
    }

    /**
     * Get the S3Client instance
     *
     * @return S3Client
     */
    private function getClient() {
        if ($this->client === null) {
            $params = array(
                'key' => $this->params['key'],
                'secret' => $this->params['secret'],
            );

            if ($this->params['region']) {
                $params['region'] = $this->params['region'];
            }

            $this->client = S3Client::factory($params);
        }

        return $this->client;
    }
}
