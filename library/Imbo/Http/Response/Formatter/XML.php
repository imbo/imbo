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

use Imbo\Model;

/**
 * XML formatter
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Response\Formatters
 */
class XML extends Formatter implements FormatterInterface {
    /**
     * {@inheritdoc}
     */
    public function getContentType() {
        return 'application/xml';
    }

    /**
     * {@inheritdoc}
     */
    public function formatError(Model\Error $model) {
        $imageIdentifierXml = '';

        if ($imageIdentifier = $model->getImageIdentifier()) {
            $imageIdentifierXml = '<imageIdentifier>' . $imageIdentifier . '</imageIdentifier>';
        }

        return <<<ERROR
<?xml version="1.0" encoding="UTF-8"?>
<imbo>
  <error>
    <code>{$model->getHttpCode()}</code>
    <message>{$model->getErrorMessage()}</message>
    <date>{$this->dateFormatter->formatDate($model->getDate())}</date>
    <imboErrorCode>{$model->getImboErrorCode()}</imboErrorCode>
  </error>
  {$imageIdentifierXml}
</imbo>
ERROR;
    }

    /**
     * {@inheritdoc}
     */
    public function formatStatus(Model\Status $model) {
        $database = (int) $model->getDatabaseStatus();
        $storage = (int) $model->getStorageStatus();

        return <<<STATUS
<?xml version="1.0" encoding="UTF-8"?>
<imbo>
  <status>
    <date>{$this->dateFormatter->formatDate($model->getDate())}</date>
    <database>{$database}</database>
    <storage>{$storage}</storage>
  </status>
</imbo>
STATUS;
    }

    /**
     * {@inheritdoc}
     */
    public function formatUser(Model\User $model) {
        return <<<USER
<?xml version="1.0" encoding="UTF-8"?>
<imbo>
  <user>
    <publicKey>{$model->getPublicKey()}</publicKey>
    <numImages>{$model->getNumImages()}</numImages>
    <lastModified>{$this->dateFormatter->formatDate($model->getLastModified())}</lastModified>
  </user>
</imbo>
USER;
    }

    /**
     * {@inheritdoc}
     */
    public function formatImages(Model\Images $model) {
        $images = '';

        if ($fields = $model->getFields()) {
            $fields = array_fill_keys($fields, 1);
        }

        foreach ($model->getImages() as $image) {
            $images .= '<image>';

            if (empty($fields) || isset($fields['publicKey'])) {
                $images .= '<publicKey>' . $image->getPublicKey() . '</publicKey>';
            }

            if (empty($fields) || isset($fields['imageIdentifier'])) {
                $images .= '<imageIdentifier>' . $image->getImageIdentifier() . '</imageIdentifier>';
            }

            if (empty($fields) || isset($fields['checksum'])) {
                $images .= '<checksum>' . $image->getChecksum() . '</checksum>';
            }

            if (empty($fields) || isset($fields['mime'])) {
                $images .= '<mime>' . $image->getMimeType() . '</mime>';
            }

            if (empty($fields) || isset($fields['extension'])) {
                $images .= '<extension>' . $image->getExtension() . '</extension>';
            }

            if (empty($fields) || isset($fields['added'])) {
                $images .= '<added>' . $this->dateFormatter->formatDate($image->getAddedDate()) . '</added>';
            }

            if (empty($fields) || isset($fields['updated'])) {
                $images .= '<updated>' . $this->dateFormatter->formatDate($image->getUpdatedDate()) . '</updated>';
            }

            if (empty($fields) || isset($fields['size'])) {
                $images .= '<size>' . $image->getFilesize() . '</size>';
            }

            if (empty($fields) || isset($fields['width'])) {
                $images .= '<width>' . $image->getWidth() . '</width>';
            }

            if (empty($fields) || isset($fields['height'])) {
                $images .= '<height>' . $image->getHeight() . '</height>';
            }

            $metadata = $image->getMetadata();

            if (is_array($metadata) && (empty($fields) || isset($fields['metadata']))) {
                $images .= '<metadata>';

                foreach ($metadata as $key => $value) {
                    $images .= '<tag key="' . $key . '">' . $value . '</tag>';
                }

                $images .= '</metadata>';
            }

            $images .= '</image>';
        }

        return <<<IMAGES
<?xml version="1.0" encoding="UTF-8"?>
<imbo>
  <images>{$images}</images>
</imbo>
IMAGES;
    }

    /**
     * {@inheritdoc}
     */
    public function formatMetadata(Model\Metadata $model) {
        $metadata = '';

        foreach ($model->getData() as $key => $value) {
            $metadata .= '<tag key="' . $key . '">' . $value . '</tag>';
        }

        return <<<METADATA
<?xml version="1.0" encoding="UTF-8"?>
<imbo>
  <metadata>{$metadata}</metadata>
</imbo>
METADATA;
    }

    /**
     * {@inheritdoc}
     */
    public function formatArrayModel(Model\ArrayModel $model) {
        $data = $this->formatArray($model->getData());

        return <<<DATA
<?xml version="1.0" encoding="UTF-8"?>
<imbo>{$data}</imbo>
DATA;
    }

    /**
     * {@inheritdoc}
     */
    public function formatListModel(Model\ListModel $model) {
        $data = '';
        $entries = '';

        $container = $model->getContainer();
        $entry = $model->getEntry();
        $list = $model->getList();

        foreach ($list as $element) {
            $entries .= '<' . $entry . '>' . $element . '</' . $entry . '>';
        }

        $data = '<' . $container . '>' . $entries . '</' . $container . '>';

        return <<<DATA
<?xml version="1.0" encoding="UTF-8"?>
<imbo>{$data}</imbo>
DATA;
    }

    /**
     * {@inheritdoc}
     */
    public function formatStats(Model\Stats $model) {
        $users = '';
        $numUsers = 0;

        foreach ($model->getUsers() as $user => $stats) {
            $users .= '<user publicKey="' . $user . '">' . $this->formatArray($stats) . '</user>';
            $numUsers++;
        }

        $total = $this->formatArray(array(
            'numImages' => $model->getNumImages(),
            'numBytes' => $model->getNumBytes(),
            'numUsers' => $numUsers,
        ));
        $custom = $this->formatArray($model->getCustomStats() ?: array());


        return <<<STATUS
<?xml version="1.0" encoding="UTF-8"?>
<imbo>
  <stats>
    <users>{$users}</users>
    <total>{$total}</total>
    <custom>{$custom}</custom>
  </stats>
</imbo>
STATUS;
    }

    /**
     * Format a nested dataset
     *
     * @param array $data A nested array
     * @return string
     */
    private function formatArray(array $data) {
        $xml = '';

        if (isset($data[0])) {
            $xml .= '<list>';

            foreach ($data as $value) {
                $xml .= '<value>' . $value . '</value>';
            }

            $xml .= '</list>';
        } else {
            foreach ($data as $key => $value) {
                $xml .= '<' . $key . '>';

                if (is_array($value)) {
                    $xml .= $this->formatArray($value);
                } else {
                    $xml .= $value;
                }

                $xml .= '</' . $key . '>';
            }
        }

        return $xml;
    }
}
