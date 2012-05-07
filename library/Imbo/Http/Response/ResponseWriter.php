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
     * @var Imbo\Http\ContentNegotiation
     */
    private $cn;

    /**
     * Supported content types and the associated formatter class name or instance
     *
     * @var array
     */
    private $supportedTypes = array(
        'application/json' => 'Imbo\Http\Response\Formatter\Json',
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
     * @param Imbo\Http\ContentNegotiation $cn Content negotiation instance
     */
    public function __construct(ContentNegotiation $cn = null) {
        if ($cn === null) {
            $cn = new ContentNegotiation();
        }

        $this->cn = $cn;
    }

    /**
     * @see Imbo\Http\Response\ResponseWriterInterface::write()
     */
    public function write(array $data, RequestInterface $request, ResponseInterface $response) {
        $acceptableTypes = array_keys($request->getAcceptableContentTypes());
        $match = false;

        foreach ($this->supportedTypes as $mime => $formatterClass) {
            if ($this->cn->isAcceptable($mime, $acceptableTypes)) {
                $match = true;
                break;
            }
        }

        if (!$match && $response->isError()) {
            // There was no match but this time it's an error message that is supposed to be
            // formatted. Send a response anyway (allowed according to RFC2616, section 10.4.7)
            $formatterClass = $this->supportedTypes[$this->defaultMimeType];
        } else if (!$match) {
            // No types matched. The client does not want any of Imbo's supported types
            throw new RuntimeException('Not acceptable', 406);
        }

        // Create an instance of the formatter
        $formatter = new $formatterClass();
        $formattedData = $formatter->format($data, $request->getResource(), $response->isError());

        $response->getHeaders()->set('Content-Type', $formatter->getContentType())
                               ->set('Content-Length', strlen($formattedData));

        $response->setBody($formattedData);
    }
}
