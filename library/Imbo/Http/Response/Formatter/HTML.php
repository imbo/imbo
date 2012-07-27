<?php
/**
 * Imbo
 *
 * Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to
 * deal in the Software without restriction, including without limitation the
 * rights to use, copy, modify, merge, publish, distribute, sublicense, and/or
 * sell copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * * The above copyright notice and this permission notice shall be included in
 *   all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 *
 * @package Http\Response
 * @subpackage Formatters
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/imbo/imbo
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
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/imbo/imbo
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
        $title = 'Status';
        $body = <<<STATUS
<dl>
  <dt>Date</dt>
  <dd>{$data['date']}</dd>
  <dt>Database</dt>
  <dd>{$data['database']}</dd>
  <dt>Storage</dt>
  <dd>{$data['storage']}</dd>
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
