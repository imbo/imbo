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

use Imbo\Resource\ResourceInterface,
    Imbo\Http\Request\RequestInterface,
    Imbo\Http\Response\ResponseInterface;

/**
 * HTML5 formatter
 *
 * @package Http\Response
 * @subpackage Formatters
 * @author Christer Edvartsen <cogo@starzinger.net>
 */
class HTML implements FormatterInterface {
    /**
     * {@inheritdoc}
     */
    public function format(array $data, RequestInterface $request, ResponseInterface $response) {
        // Fetch the name of the resource
        $resource = $request->getResource();

        if ($response->isError()) {
            return $this->formatError($data);
        } else if ($resource === ResourceInterface::STATUS) {
            return $this->formatStatus($data);
        } else if ($resource === ResourceInterface::USER) {
            return $this->formatUser($data);
        } else if ($resource === ResourceInterface::IMAGES) {
            return $this->formatImages($data);
        } else if ($resource === ResourceInterface::METADATA) {
            return $this->formatMetadata($data);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getContentType() {
        return 'text/html';
    }

    /**
     * Get an HTML5 document with some placeholders
     *
     * @return string
     */
    private function getDocument() {
        return <<<DOCUMENT
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <title>%TITLE%</title>
    <style type="text/css">
      dt { font-weight: bold; }
    </style>
  </head>
  <body>
    <h1>%TITLE%</h1>
    %BODY%
  </body>
</html>
DOCUMENT;
    }

    /**
     * Format an error response
     *
     * @param array $data Error information
     * @return string Returns an HTML string
     */
    private function formatError(array $data) {
        if (!isset($data['error'])) {
            // If the $data array does not have an error key, this is simply the status resource
            // reporting that the system is not stable, which is not a regular error
            return $this->formatStatus($data);
        }

        $title = 'Error';
        $body = <<<ERROR
<dl>
  <dt>Code</dt>
  <dd>{$data['error']['code']}</dd>
  <dt>Message</dt>
  <dd>{$data['error']['message']}</dd>
  <dt>Date</dt>
  <dd>{$data['error']['date']}</dd>
  <dt>Imbo error code</dt>
  <dd>{$data['error']['imboErrorCode']}</dd>
</dl>
ERROR;

        return str_replace(array('%TITLE%', '%BODY%'), array($title, $body), $this->getDocument());
    }

    /**
     * Format response for the status resource
     *
     * @param array $data Status information
     * @return string Returns an HTML string
     */
    private function formatStatus(array $data) {
        $database = (int) $data['database'];
        $storage = (int) $data['storage'];

        $title = 'Status';
        $body = <<<STATUS
<dl>
  <dt>Date</dt>
  <dd>{$data['date']}</dd>
  <dt>Database</dt>
  <dd>$database</dd>
  <dt>Storage</dt>
  <dd>$storage</dd>
</dl>
STATUS;

        return str_replace(array('%TITLE%', '%BODY%'), array($title, $body), $this->getDocument());
    }

    /**
     * Format response for the user resource
     *
     * @param array $data User information
     * @return string Returns an HTML string
     */
    private function formatUser(array $data) {
        $title = 'User';
        $body = <<<USER
<dl>
  <dt>Public key</dt>
  <dd>{$data['publicKey']}</dd>
  <dt>Num. images</dt>
  <dd>{$data['numImages']}</dd>
  <dt>Last modified</dt>
  <dd>{$data['lastModified']}</dd>
</dl>
USER;

        return str_replace(array('%TITLE%', '%BODY%'), array($title, $body), $this->getDocument());
    }

    /**
     * Format response for the images resource
     *
     * @param array $data Images array
     * @return string Returns an HTML string
     */
    private function formatImages(array $data) {
        $title = 'Images';
        $images = array();

        foreach ($data as $image) {
            $metadata = null;

            if (isset($image['metadata']) && count($image['metadata'])) {
                $metadata = '<dt>Metadata</dt><dd><dl>';

                foreach ($image['metadata'] as $key => $value) {
                    $metadata .= '<dt>' . $key . '</dt><dd>' . $value . '</dd>';
                }

                $metadata .= '</dl></dd>';
            }

            $entry = <<<IMAGE
<li>
  <dl>
    <dt>Public key</dt>
    <dd>{$image['publicKey']}</dd>
    <dt>Image identifier</dt>
    <dd>{$image['imageIdentifier']}</dd>
    <dt>Extension</dt>
    <dd>{$image['extension']}</dd>
    <dt>Mime type</dt>
    <dd>{$image['mime']}</dd>
    <dt>Added</dt>
    <dd>{$image['added']}</dd>
    <dt>Updated</dt>
    <dd>{$image['updated']}</dd>
    <dt>Size</dt>
    <dd>{$image['size']}</dd>
    <dt>Width</dt>
    <dd>{$image['width']}</dd>
    <dt>Height</dt>
    <dd>{$image['height']}</dd>
    $metadata
  </dl>
</li>
IMAGE;
            $images[] = $entry;
        }

        if ($images) {
            $body = '<ul>' . join("\n", $images) . '</ul>';
        } else {
            $body = '<p>No images</p>';
        }

        return str_replace(array('%TITLE%', '%BODY%'), array($title, $body), $this->getDocument());
    }

    /**
     * Format response for the metadata resource
     *
     * @param array $data Metadata
     * @return string Returns an HTML string
     */
    private function formatMetadata(array $data) {
        $title = 'Metadata';

        if (empty($data)) {
            $body = '<p>No metadata</p>';
        } else {
            $body = '<dl>';

            foreach ($data as $key => $value) {
                $body .= '<dt>' . $key . '</dt><dd>' . $value . '</dd>';
            }

            $body .= '</dl>';
        }

        return str_replace(array('%TITLE%', '%BODY%'), array($title, $body), $this->getDocument());
    }
}
