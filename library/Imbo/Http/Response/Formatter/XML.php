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
        return <<<ERROR
<?xml version="1.0" encoding="UTF-8"?>
<imbo>
  <error>
    <code>{$model->getHttpCode()}</code>
    <message>{$model->getErrorMessage()}</message>
    <date>{$this->dateFormatter->formatDate($model->getDate())}</date>
    <imboErrorCode>{$model->getImboErrorCode()}</imboErrorCode>
  </error>
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

        foreach ($model->getImages() as $image) {
            $metadata = '';

            foreach ($image->getMetadata() as $key => $value) {
                $metadata .= '<tag key="' . $key . '">' . $value . '</tag>';
            }

            $images .= <<<IMAGE
<image>
  <publicKey>{$image->getPublicKey()}</publicKey>
  <imageIdentifier>{$image->getImageIdentifier()}</imageIdentifier>
  <checksum>{$image->getChecksum()}</checksum>
  <mime>{$image->getMimetype()}</mime>
  <extension>{$image->getExtension()}</extension>
  <added>{$this->dateFormatter->formatDate($image->getAddedDate())}</added>
  <updated>{$this->dateFormatter->formatDate($image->getUpdatedDate())}</updated>
  <size>{$image->getFilesize()}</size>
  <width>{$image->getWidth()}</width>
  <height>{$image->getHeight()}</height>
  <metadata>{$metadata}</metadata>
</image>
IMAGE;

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
}
