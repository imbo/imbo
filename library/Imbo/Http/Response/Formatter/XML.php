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
    Imbo\Http\Response\ResponseInterface,
    XMLWriter;

/**
 * XML formatter
 *
 * @package Http\Response
 * @subpackage Formatters
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/imbo/imbo
 */
class XML implements FormatterInterface {
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
        return 'text/xml';
    }

    /**
     * Get an XMLWriter instance with some basic options set
     *
     * @return XMLWriter
     */
    private function getWriter() {
        $writer = new XMLWriter();
        $writer->openMemory();
        $writer->setIndent(true);
        $writer->setIndentString('  ');
        $writer->startDocument('1.0', 'UTF-8');

        return $writer;
    }

    /**
     * Write a simple document with "imbo" as main tag
     *
     * @param XMLWriter $writer The writer instance
     * @param string $tag The tag to put $data in
     * @param array $data One dimensional associative array
     */
    private function writeSimpleDocument(XMLWriter $writer, $tag, array $data) {
        $writer->startElement('imbo');
        $writer->startElement($tag);

        $this->writeKeyValues($writer, $data);

        $writer->endElement();
        $writer->endElement();
    }

    /**
     * Write a one dimensional associative array to the document
     *
     * @param XMLWriter $writer The writer instance
     * @param array $data One dimensional associative array
     */
    private function writeKeyValues(XMLWriter $writer, array $data) {
        foreach ($data as $key => $value) {
            $writer->writeElement($key, $value);
        }
    }

    /**
     * Format an error response
     *
     * @param array $data Error information
     * @return string Returns an XML string
     */
    private function formatError(array $data) {
        $writer = $this->getWriter();
        $this->writeSimpleDocument($writer, 'error', $data['error']);

        return $writer->outputMemory();
    }

    /**
     * Format response for the status resource
     *
     * @param array $data Status information
     * @return string Returns an XML string
     */
    private function formatStatus(array $data) {
        $writer = $this->getWriter();
        $this->writeSimpleDocument($writer, 'status', $data);

        return $writer->outputMemory();
    }

    /**
     * Format response for the user resource
     *
     * @param array $data User information
     * @return string Returns an XML string
     */
    private function formatUser(array $data) {
        $writer = $this->getWriter();
        $this->writeSimpleDocument($writer, 'user', $data);

        return $writer->outputMemory();
    }

    /**
     * Format response for the images resource
     *
     * @param array $data Images array
     * @return string Returns an XML string
     */
    private function formatImages(array $data) {
        $writer = $this->getWriter();

        $writer->startElement('imbo');
        $writer->startElement('images');

        foreach ($data as $images) {
            $writer->startElement('image');

            foreach ($images as $key => $value) {
                if (is_array($value)) {
                    $writer->startElement('metadata');

                    foreach ($value as $key => $value) {
                        $writer->startElement('tag');
                        $writer->writeAttribute('key', $key);
                        $writer->text($value);
                        $writer->endElement();
                    }

                    $writer->endElement();
                } else {
                    $writer->writeElement($key, $value);
                }
            }

            $writer->endElement();
        }

        $writer->endElement();
        $writer->endElement();

        return $writer->outputMemory();
    }

    /**
     * Format response for the metadata resource
     *
     * @param array $data Metadata
     * @return string Returns an XML string
     */
    private function formatMetadata(array $data) {
        $writer = $this->getWriter();

        $writer->startElement('imbo');
        $writer->startElement('metadata');

        foreach ($data as $key => $value) {
            $writer->startElement('tag');
            $writer->writeAttribute('key', $key);
            $writer->text($value);
            $writer->endElement();
        }

        $writer->endElement();
        $writer->endElement();

        return $writer->outputMemory();
    }
}
