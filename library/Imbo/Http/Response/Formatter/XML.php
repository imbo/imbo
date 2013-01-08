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
    Imbo\Http\Response\ResponseInterface,
    XMLWriter;

/**
 * XML formatter
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Http
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
        return 'application/xml';
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
        if (!isset($data['error'])) {
            // If the $data array does not have an error key, this is simply the status resource
            // reporting that the system is not stable, which is not a regular error
            return $this->formatStatus($data);
        }

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
        $data['database'] = (int) $data['database'];
        $data['storage'] = (int) $data['storage'];

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
