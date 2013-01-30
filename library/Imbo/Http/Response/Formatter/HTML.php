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
 * HTML5 formatter
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Response\Formatters
 */
class HTML extends Formatter implements FormatterInterface {
    /**
     * {@inheritdoc}
     */
    public function getContentType() {
        return 'text/html';
    }

    /**
     * {@inheritdoc}
     */
    public function formatError(Model\Error $model) {
        $date = $this->dateFormatter->formatDate($model->getDate());
        $body = <<<ERROR
<dl>
  <dt>Code</dt>
  <dd>{$model->getHttpCode()}</dd>
  <dt>Message</dt>
  <dd>{$model->getErrorMessage()}</dd>
  <dt>Date</dt>
  <dd>{$date}}</dd>
  <dt>Imbo error code</dt>
  <dd>{$model->getImboErrorCode()}</dd>
</dl>
ERROR;

        return $this->getDocument('Error', $body);
    }

    /**
     * {@inheritdoc}
     */
    public function formatStatus(Model\Status $model) {
        $date = $this->dateFormatter->formatDate($model->getDate());
        $database = (int) $model->getDatabaseStatus();
        $storage = (int) $model->getStorageStatus();

        $body = <<<STATUS
<dl>
  <dt>Date</dt>
  <dd>{$date}</dd>
  <dt>Database</dt>
  <dd class="database">{$database}</dd>
  <dt>Storage</dt>
  <dd class="storage">{$storage}</dd>
</dl>
STATUS;

        return $this->getDocument('Status', $body);
    }

    /**
     * {@inheritdoc}
     */
    public function formatUser(Model\User $model) {
        $lastModified = $this->dateFormatter->formatDate($model->getLastModified());

        $body = <<<USER
<dl>
  <dt>Public key</dt>
  <dd>{$model->getPublicKey()}</dd>
  <dt>Num. images</dt>
  <dd>{$model->getNumImages()}</dd>
  <dt>Last modified</dt>
  <dd>{$lastModified}</dd>
</dl>
USER;

        return $this->getDocument('User', $body);
    }

    /**
     * {@inheritdoc}
     */
    public function formatImages(Model\Images $model) {
        $images = '';

        foreach ($model->getImages() as $image) {
            $metadata = $image->getMetadata();
            $metadataHtml = '';

            if (!empty($metadata)) {
                $metadataHtml = '<dt>Metadata</dt><dd><dl>';

                foreach ($metadata as $key => $value) {
                    $metadataHtml .= '<dt>' . $key . '</dt><dd>' . $value . '</dd>';
                }

                $metadataHtml .= '</dl></dd>';
            }

            $entry = <<<IMAGE
<li>
  <dl>
    <dt>Public key</dt>
    <dd>{$image->getPublicKey()}</dd>
    <dt>Image identifier</dt>
    <dd>{$image->getImageIdentifier()}</dd>
    <dt>Checksum</dt>
    <dd>{$image->getChecksum()}</dd>
    <dt>Extension</dt>
    <dd>{$image->getExtension()}</dd>
    <dt>Mime type</dt>
    <dd>{$image->getMimeType()}</dd>
    <dt>Added</dt>
    <dd>{$this->dateFormatter->formatDate($image->getAddedDate())}</dd>
    <dt>Updated</dt>
    <dd>{$this->dateFormatter->formatDate($image->getUpdatedDate())}</dd>
    <dt>Size</dt>
    <dd>{$image->getFilesize()}</dd>
    <dt>Width</dt>
    <dd>{$image->getWidth()}</dd>
    <dt>Height</dt>
    <dd>{$image->getHeight()}</dd>
    {$metadataHtml}
  </dl>
</li>
IMAGE;
            $images[] = $entry;
        }

        if (!empty($images)) {
            $body = '<ul>' . join("\n", $images) . '</ul>';
        } else {
            $body = '<p>No images</p>';
        }

        return $this->getDocument('Images', $body);
    }

    /**
     * {@inheritdoc}
     */
    public function formatMetadata(Model\Metadata $model) {
        $metadata = $model->getData();

        if (empty($metadata)) {
            $body = '<p>No metadata</p>';
        } else {
            $body = '<dl>';

            foreach ($metadata as $key => $value) {
                $body .= '<dt>' . $key . '</dt><dd>' . $value . '</dd>';
            }

            $body .= '</dl>';
        }

        return $this->getDocument('Metadata', $body);
    }

    /**
     * Get an HTML5 document with some placeholders
     *
     * @return string
     */
    private function getDocument($title, $body) {
        return <<<DOCUMENT
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <title>{$title}</title>
    <style type="text/css">
      dt { font-weight: bold; }
    </style>
  </head>
  <body>
    <h1>{$title}</h1>
    {$body}
  </body>
</html>
DOCUMENT;
    }
}
