<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace Imbo\Http\Response\Formatter;

use Imbo\Model,
    stdClass;

/**
 * JSON formatter
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Response\Formatters
 */
class JSON extends Formatter implements FormatterInterface {
    /**
     * {@inheritdoc}
     */
    public function getContentType() {
        return 'application/json';
    }

    /**
     * {@inheritdoc}
     */
    public function formatError(Model\Error $model) {
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

    /**
     * {@inheritdoc}
     */
    public function formatStatus(Model\Status $model) {
        return $this->encode([
            'date' => $this->dateFormatter->formatDate($model->getDate()),
            'database' => $model->getDatabaseStatus(),
            'storage' => $model->getStorageStatus(),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function formatUser(Model\User $model) {
        return $this->encode([
            'user' => $model->getUserId(),
            'numImages' => $model->getNumImages(),
            'lastModified' => $this->dateFormatter->formatDate($model->getLastModified()),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function formatImages(Model\Images $model) {
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

                if (is_array($metadata)) {
                    if (empty($metadata)) {
                        $metadata = new stdClass();
                    }

                    $entry['metadata'] = $metadata;
                }
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

    /**
     * {@inheritdoc}
     */
    public function formatMetadataModel(Model\Metadata $model) {
        return $this->encode($model->getData() ?: new stdClass());
    }

    /**
     * {@inheritdoc}
     */
    public function formatArrayModel(Model\ArrayModel $model) {
        return $this->encode($model->getData() ?: new stdClass());
    }

    /**
     * {@inheritdoc}
     */
    public function formatListModel(Model\ListModel $model) {
        return $this->encode([$model->getContainer() => $model->getList()]);
    }

    /**
     * {@inheritdoc}
     */
    public function formatGroups(Model\Groups $model) {
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

    /**
     * {@inheritdoc}
     */
    public function formatGroup(Model\Group $model) {
        return $this->encode([
            'name' => $model->getName(),
            'resources' => $model->getResources(),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function formatAccessRule(Model\AccessRule $model) {
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

    /**
     * {@inheritdoc}
     */
    public function formatAccessRules(Model\AccessRules $model) {
        return $this->encode($model->getRules());
    }

    /**
     * {@inheritdoc}
     */
    public function formatStats(Model\Stats $model) {
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
     * @return string
     */
    private function encode($data) {
        return json_encode($data);
    }
}
