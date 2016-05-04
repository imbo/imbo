<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace Imbo\EventListener\ImageVariations\Storage;

use Imbo\Exception\StorageException,
    Aws\S3\S3Client,
    Aws\S3\Exception\NoSuchKeyException,
    Aws\S3\Exception\S3Exception,
    DateTime,
    DateTimeZone;

/**
 * S3 storage driver for the image variations
 *
 * Configuration options supported by this driver:
 * Parameters for this adapter:
 *
 * - (string) key Your AWS access key
 * - (string) secret Your AWS secret key
 * - (string) bucket The name of the bucket to store the files in. The bucket should exist prior
 *                   to using this client. Imbo will not try to automatically add the bucket for
 *                   you.
 *
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
    private $params = [
        // Access key
        'key' => null,

        // Secret key
        'secret' => null,

        // Name of the bucket to store the files in
        'bucket' => null,

        // Region
        'region' => null,
    ];

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
    public function storeImageVariation($user, $imageIdentifier, $imageData, $width) {
        try {
            $this->getClient()->putObject([
                'Bucket' => $this->params['bucket'],
                'Key' => $this->getImagePath($user, $imageIdentifier, $width),
                'Body' => $imageData,
            ]);
        } catch (S3Exception $e) {
            throw new StorageException('Could not store image', 500);
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getImageVariation($user, $imageIdentifier, $width) {
        try {
            $model = $this->getClient()->getObject([
                'Bucket' => $this->params['bucket'],
                'Key' => $this->getImagePath($user, $imageIdentifier, $width),
            ]);
        } catch (NoSuchKeyException $e) {
            return null;
        }

        return (string) $model->get('Body');
    }

    /**
     * {@inheritdoc}
     */
    public function deleteImageVariations($user, $imageIdentifier, $width = null) {
        // If width is specified, delete only the specific image
        if ($width !== null) {
            $this->getClient()->deleteObject([
                'Bucket' => $this->params['bucket'],
                'Key' => $this->getImagePath($user, $imageIdentifier, $width)
            ]);

            return true;
        }

        // If width is not specified, delete every variation. Ask S3 to list all files in the
        // directory.
        $variationsPath = $this->getImagePath($user, $imageIdentifier);

        $varations = $this->getClient()->getIterator('ListObjects', [
            'Bucket' => $this->params['bucket'],
            'Prefix' => $variationsPath
        ]);

        // Note: could also use AWS's deleteMatchingObjects instead of deleting items one by one
        foreach ($varations as $variation) {
            $this->getClient()->deleteObject([
                'Bucket' => $this->params['bucket'],
                'Key' => $variation['Key']
            ]);
        }

        return true;
    }

    /**
     * Get the path to an image
     *
     * @param string $user The user which the image belongs to
     * @param string $imageIdentifier Image identifier
     * @param int $width Width of the image, in pixels
     * @param boolean $includeFilename Whether or not to include the last part of the path
     *                                 (the filename itself)
     * @return string
     */
    private function getImagePath($user, $imageIdentifier, $width = null, $includeFilename = true) {
        $userPath = str_pad($user, 3, '0', STR_PAD_LEFT);
        $parts = [
            'imageVariation',
            $userPath[0],
            $userPath[1],
            $userPath[2],
            $user,
            $imageIdentifier[0],
            $imageIdentifier[1],
            $imageIdentifier[2],
            $imageIdentifier,
        ];

        if ($includeFilename) {
            $parts[] = $width;
        }

        return implode('/', $parts);
    }


    /**
     * Get the S3Client instance
     *
     * @return S3Client
     */
    private function getClient() {
        if ($this->client === null) {
            $params = [
                'key' => $this->params['key'],
                'secret' => $this->params['secret'],
            ];

            if ($this->params['region']) {
                $params['region'] = $this->params['region'];
            }

            $this->client = S3Client::factory($params);
        }

        return $this->client;
    }
}
