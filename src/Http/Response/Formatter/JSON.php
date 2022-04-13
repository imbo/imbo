<?php declare(strict_types=1);
namespace Imbo\Http\Response\Formatter;

use Imbo\Model;
use stdClass;

/**
 * JSON formatter
 */
class JSON extends Formatter implements FormatterInterface
{
    public function getContentType(): string
    {
        return 'application/json';
    }

    public function formatError(Model\Error $model): string
    {
        $data = [
            'error' => [
                'code' => $model->getHttpCode(),
                'message' => $model->getErrorMessage(),
                'date' => $this->dateFormatter->formatDate($model->getDate()),
                'imboErrorCode' => $model->getImboErrorCode(),
            ],
        ];

        if ($imageIdentifier = $model->getImageIdentifier()) {
            $data['imageIdentifier'] = $imageIdentifier;
        }

        return $this->encode($data);
    }

    public function formatStatus(Model\Status $model): string
    {
        return $this->encode([
            'date' => $this->dateFormatter->formatDate($model->getDate()),
            'database' => $model->getDatabaseStatus(),
            'storage' => $model->getStorageStatus(),
        ]);
    }

    public function formatUser(Model\User $model): string
    {
        return $this->encode([
            'user' => $model->getUserId(),
            'numImages' => $model->getNumImages(),
            'lastModified' => $this->dateFormatter->formatDate($model->getLastModified()),
        ]);
    }

    public function formatImages(Model\Images $model): string
    {
        $images = $model->getImages();
        $data = [];

        // Fields to display
        if ($fields = $model->getFields()) {
            $fields = array_fill_keys($fields, 1);
        }

        foreach ($images as $image) {
            $entry = [
                'added' => $this->dateFormatter->formatDate($image->getAddedDate()),
                'updated' => $this->dateFormatter->formatDate($image->getUpdatedDate()),
                'checksum' => $image->getChecksum(),
                'originalChecksum' => $image->getOriginalChecksum(),
                'extension' => $image->getExtension(),
                'size' => $image->getFilesize(),
                'width' => $image->getWidth(),
                'height' => $image->getHeight(),
                'mime' => $image->getMimeType(),
                'imageIdentifier' => $image->getImageIdentifier(),
                'user' => $image->getUser(),
            ];

            // Add metadata if the field is to be displayed
            if (empty($fields) || isset($fields['metadata'])) {
                $metadata = $image->getMetadata();

                if (empty($metadata)) {
                    $metadata = new stdClass();
                }

                $entry['metadata'] = $metadata;
            }

            // Remove elements that should not be displayed
            if (!empty($fields)) {
                foreach (array_keys($entry) as $key) {
                    if (!isset($fields[$key])) {
                        unset($entry[$key]);
                    }
                }
            }

            $data[] = $entry;
        }

        return $this->encode([
            'search' => [
                'hits' => $model->getHits(),
                'page' => $model->getPage(),
                'limit' => $model->getLimit(),
                'count' => $model->getCount(),
            ],
            'images' => $data,
        ]);
    }

    public function formatMetadataModel(Model\Metadata $model): string
    {
        return $this->encode($model->getData() ?: new stdClass());
    }

    public function formatArrayModel(Model\ArrayModel $model): string
    {
        return $this->encode($model->getData() ?: new stdClass());
    }

    public function formatGroups(Model\Groups $model): string
    {
        return $this->encode([
            'search' => [
                'hits' => $model->getHits(),
                'page' => $model->getPage(),
                'limit' => $model->getLimit(),
                'count' => $model->getCount(),
            ],
            'groups' => $model->getGroups(),
        ]);
    }

    public function formatGroup(Model\Group $model): string
    {
        return $this->encode([
            'name' => $model->getName(),
            'resources' => $model->getResources(),
        ]);
    }

    public function formatAccessRule(Model\AccessRule $model): string
    {
        $data = [
            'id' => $model->getId(),
            'users' => $model->getUsers(),
        ];

        if ($group = $model->getGroup()) {
            $data['group'] = $group;
        }

        if ($resources = $model->getResources()) {
            $data['resources'] = $resources;
        }

        return $this->encode($data);
    }

    public function formatAccessRules(Model\AccessRules $model): string
    {
        return $this->encode($model->getRules());
    }

    public function formatStats(Model\Stats $model): string
    {
        $data = [
            'numImages' => $model->getNumImages(),
            'numUsers' => $model->getNumUsers(),
            'numBytes' => $model->getNumBytes(),
            'custom' => $model->getCustomStats() ?: new stdClass(),
        ];

        return $this->encode($data);
    }

    /**
     * JSON encode an array
     *
     * @param mixed $data The data to encode
     */
    private function encode($data): string
    {
        return json_encode($data);
    }
}
