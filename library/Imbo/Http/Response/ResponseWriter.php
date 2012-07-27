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
 * @package Http
 * @subpackage Response
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/imbo/imbo
 */

namespace Imbo\Http\Response;

use Imbo\Http\Request\RequestInterface,
    Imbo\Http\Response\Formatter,
    Imbo\Http\ContentNegotiation,
    Imbo\Exception\RuntimeException;

/**
 * Response writer
 *
 * @package Http
 * @subpackage Response
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/imbo/imbo
 */
class ResponseWriter implements ResponseWriterInterface {
    /**
     * Content negotiation instance
     *
     * @var ContentNegotiation
     */
    private $cn;

    /**
     * Supported content types and the associated formatter class name or instance
     *
     * @var array
     */
    private $supportedTypes = array(
        'application/json' => 'Imbo\Http\Response\Formatter\JSON',
        'application/xml'  => 'Imbo\Http\Response\Formatter\XML',
        'text/html'        => 'Imbo\Http\Response\Formatter\HTML',
    );

    /**
     * Mapping from extensions to mime types
     *
     * @var array
     */
    private $extensionsToMimeType = array(
        'json' => 'application/json',
        'xml'  => 'application/xml',
        'html' => 'text/html',
    );

    /**
     * The default mime type to use when formatting a response
     *
     * @var string
     */
    private $defaultMimeType = 'application/json';

    /**
     * Class constructor
     *
     * @param ContentNegotiation $cn Content negotiation instance
     */
    public function __construct(ContentNegotiation $cn = null) {
        if ($cn === null) {
            $cn = new ContentNegotiation();
        }

        $this->cn = $cn;
    }

    /**
     * {@inheritdoc}
     */
    public function write(array $data, RequestInterface $request, ResponseInterface $response, $strict = true) {
        // The formatter to use
        $formatter = null;

        if ($extension = $request->getExtension()) {
            // The user agent wants a specific type. Skip content negotiation completely
            $mime = $this->extensionsToMimeType[$extension];
            $formatter = $this->supportedTypes[$mime];
        } else {
            // Try to find the best match
            $acceptableTypes = $request->getAcceptableContentTypes();
            $match = false;
            $maxQ = 0;

            foreach ($this->supportedTypes as $mime => $formatterClass) {
                if (($q = $this->cn->isAcceptable($mime, $acceptableTypes)) && ($q > $maxQ)) {
                    $maxQ = $q;
                    $match = true;
                    $formatter = $formatterClass;
                }
            }

            if (!$match && $strict) {
                // No types matched with strict mode enabled. The client does not want any of Imbo's
                // supported types. Y U NO ACCEPT MY TYPES?! FFFFUUUUUUU!
                throw new RuntimeException('Not acceptable', 406);
            } else if (!$match) {
                // There was no match but we don't want to be an ass about it. Send a response
                // anyway (allowed according to RFC2616, section 10.4.7)
                $formatter = $this->supportedTypes[$this->defaultMimeType];
            }
        }

        // Create an instance of the formatter
        $formatter = new $formatter();
        $formattedData = $formatter->format($data, $request, $response);

        $response->getHeaders()->set('Content-Type', $formatter->getContentType())
                               ->set('Content-Length', strlen($formattedData));

        $response->setBody($formattedData);
    }
}
