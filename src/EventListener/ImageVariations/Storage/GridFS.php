<?php declare(strict_types=1);
namespace Imbo\EventListener\ImageVariations\Storage;

use Imbo\Exception\StorageException;
use MongoDB\Client;
use MongoDB\Database;
use MongoDB\GridFS\Bucket;
use MongoDB\Driver\Exception\Exception as MongoDBException;

/**
 * GridFS (MongoDB) database driver for the image variations
 *
 * Valid parameters for this driver:
 *
 * - `string uri`: MongoDB connection string. Defaults to 'mongodb://localhost:27017'
 * - `array uriOptions`: Additional connection string options. Defaults to []
 * - `array clientOptions`: Driver-specific options for the internal MongoDB client. Defaults to
 *                          ['connect' => true, 'connectTimeoutMS' => 1000]
 * - `string databaseName`: Name of the database to connect to. Defaults to 'imbo_imagevariation_storage'
 * - `array bucketOptions`: Options for the internal Bucket instance. Defaults to []
 */
class GridFS implements StorageInterface {
    private Client $client;
    private Database $database;
    private Bucket $bucket;

    /**
     * Parameters for the driver
     *
     * @var array
     */
    private array $params = [
        'uri' => 'mongodb://localhost:27017',
        'uriOptions' => [],
        'clientOptions' => [
            'connect' => true,
            'connectTimeoutMS' => 1000,
        ],
        'databaseName' => 'imbo_imagevariation_storage',
        'bucketOptions' => [],
    ];

    /**
     * Class constructor
     *
     * @param array $params Parameters for the driver
     */
    public function __construct(array $params = null) {
        if ($params !== null) {
            $this->params = array_replace_recursive($this->params, $params);
        }

        try {
            $this->client = new Client(
                $this->params['uri'],
                $this->params['uriOptions'],
                $this->params['clientOptions']
            );
        } catch (MongoDBException $e) {
            throw new StorageException('Could not connect to database', 500, $e);
        }

        $this->database = $this->client->selectDatabase($this->params['databaseName']);
        $this->bucket = $this->database->selectGridFSBucket($this->params['bucketOptions']);
    }

    public function storeImageVariation(string $user, string $imageIdentifier, string $blob, int $width) : bool {
        $this->bucket->uploadFromStream(
            $this->getImageFilename($user, $imageIdentifier, (int) $width),
            $this->createStream($blob),
            [
                'metadata' => [
                    'added' => time(),
                    'user' => $user,
                    'imageIdentifier' => $imageIdentifier,
                    'width' => (int) $width,
                ],
            ]
        );

        return true;
    }

    public function getImageVariation(string $user, string $imageIdentifier, int $width) : ?string {
        try {
            return stream_get_contents($this->bucket->openDownloadStreamByName(
                $this->getImageFilename($user, $imageIdentifier, (int) $width)
            ));
        } catch (MongoDBException $e) {
            return null;
        }
    }

    public function deleteImageVariations(string $user, string $imageIdentifier, int $width = null) : bool {
        $filter = [
            'metadata.user' => $user,
            'metadata.imageIdentifier' => $imageIdentifier
        ];

        if ($width !== null) {
            $filter['metadata.width'] = (int) $width;
        }

        foreach ($this->bucket->find($filter) as $file) {
            $this->bucket->delete($file['_id']);
        }

        return true;
    }

    /**
     * Create a stream for a string
     *
     * @param string $data The string to use in the stream
     * @return resource
     */
    private function createStream(string $data) {
        $stream = fopen('php://temp', 'w+b');
        fwrite($stream, $data);
        rewind($stream);

        return $stream;
    }

    /**
     * Get the image variation filename
     *
     * @param string $user
     * @param string $imageIdentifier
     * @param int $width
     * @return string
     */
    private function getImageFilename(string $user, string $imageIdentifier, int $width) : string {
        return $user . '.' . $imageIdentifier . '.' . $width;
    }
}
