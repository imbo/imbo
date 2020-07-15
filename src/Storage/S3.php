<?php declare(strict_types=1);
namespace Imbo\Storage;

use Imbo\Exception\StorageException;
use Imbo\Helpers\Parameters;
use Imbo\Exception\ConfigurationException;
use Aws\S3\S3Client;
use Aws\S3\Exception\S3Exception;
use DateTime;
use DateTimeZone;

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
 */
class S3 implements StorageInterface {
    private S3Client $client;
    private array $params = [
        // Access key
        'key' => null,

        // Secret key
        'secret' => null,

        // Name of the bucket to store the files in
        'bucket' => null,

        // Region
        'region' => null,

        // Version of API
        'version' => '2006-03-01',
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
        } else {
            $missingFields = Parameters::getEmptyOrMissingParamFields(
                ['key', 'secret', 'bucket', 'region'],
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
     * {@inheritdoc}
     */
    public function store(string $user, string $imageIdentifier, string $imageData) : bool {
        try {
            $this->getClient()->putObject([
                'Bucket' => $this->getParams()['bucket'],
                'Key' => $this->getImagePath($user, $imageIdentifier),
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
    public function delete(string $user, string $imageIdentifier) : bool {
        if (!$this->imageExists($user, $imageIdentifier)) {
            throw new StorageException('File not found', 404);
        }

        $this->getClient()->deleteObject([
            'Bucket' => $this->getParams()['bucket'],
            'Key' => $this->getImagePath($user, $imageIdentifier),
        ]);

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getImage(string $user, string $imageIdentifier) : string {
        try {
            $model = $this->getClient()->getObject([
                'Bucket' => $this->getParams()['bucket'],
                'Key' => $this->getImagePath($user, $imageIdentifier),
            ]);
        } catch (S3Exception $e) {
            throw new StorageException('File not found', 404);
        }

        return (string) $model->get('Body');
    }

    /**
     * {@inheritdoc}
     */
    public function getLastModified(string $user, string $imageIdentifier) : DateTime {
        try {
            $model = $this->getClient()->headObject([
                'Bucket' => $this->getParams()['bucket'],
                'Key' => $this->getImagePath($user, $imageIdentifier),
            ]);
        } catch (S3Exception $e) {
            throw new StorageException('File not found', 404);
        }

        return new DateTime($model->get('LastModified'), new DateTimeZone('UTC'));
    }

    /**
     * {@inheritdoc}
     */
    public function getStatus() : bool {
        try {
            $this->getClient()->headBucket([
                'Bucket' => $this->getParams()['bucket'],
            ]);
        } catch (S3Exception $e) {
            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function imageExists(string $user, string $imageIdentifier) : bool {
        try {
            $this->getClient()->headObject([
                'Bucket' => $this->getParams()['bucket'],
                'Key' => $this->getImagePath($user, $imageIdentifier),
            ]);
        } catch (S3Exception $e) {
            return false;
        }

        return true;
    }

    /**
     * Get the path to an image
     *
     * @param string $user The user which the image belongs to
     * @param string $imageIdentifier Image identifier
     * @return string
     */
    protected function getImagePath(string $user, string $imageIdentifier) : string {
        $userPath = str_pad($user, 3, '0', STR_PAD_LEFT);
        return implode('/', [
            $userPath[0],
            $userPath[1],
            $userPath[2],
            $user,
            $imageIdentifier[0],
            $imageIdentifier[1],
            $imageIdentifier[2],
            $imageIdentifier,
        ]);
    }

    /**
     * Get the set of params provided when creating the instance
     *
     * @return array<string>
     */
    protected function getParams() : array {
        return $this->params;
    }

    /**
     * Get the S3Client instance
     *
     * @return S3Client
     */
    protected function getClient() : S3Client {
        if ($this->client === null) {
            $params = [
                'credentials' => [
                    'key' => $this->params['key'],
                    'secret' => $this->params['secret'],
                ],
                'version' => $this->params['version'],
            ];

            if ($this->params['region']) {
                $params['region'] = $this->params['region'];
            }

            $this->client = new S3Client($params);
        }

        return $this->client;
    }
}
