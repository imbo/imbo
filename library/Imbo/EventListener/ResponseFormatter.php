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
 * @package EventListener
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/imbo/imbo
 */

namespace Imbo\EventListener;

use Imbo\EventManager\EventInterface,
    Imbo\Http\Response\ResponseWriterInterface,
    Imbo\Http\Response\ResponseWriter,
    Imbo\Exception;

/**
 * This event listener will correctly format the response body based on the Accept headers in the
 * request. If the request is for an image resource it will not do anything.
 *
 * @package EventListener
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/imbo/imbo
 */
class ResponseFormatter extends Listener implements ListenerInterface {
    /**
     * Response writer instance
     *
     * @var ResponseWriterInterface
     */
    private $writer;

    /**
     * {@inheritdoc}
     */
    public function getEvents() {
        return array(
            'response.prepare',
        );
    }

    /**
     * Class constructor
     *
     * @param ResponseWriterInterface $writer An instance of a writer
     */
    public function __construct(ResponseWriterInterface $writer = null) {
        if ($writer === null) {
            $writer = new ResponseWriter();
        }

        $this->writer = $writer;
    }

    /**
     * {@inheritdoc}
     */
    public function invoke(EventInterface $event) {
        $container = $event->getContainer();

        // Default mode for the response writer
        $strict = true;

        prepareResponse:

        // Fetch the response body
        $body = $container->response->getBody();

        // If the body is not an array it's an image and we don't need to format that
        if (is_array($body)) {
            // Write the correct response body. This will throw an exception if the client does
            // not accept any of the supported content types and the $strict flag has been set to true.
            try {
                $this->writer->write($body, $container->request, $container->response, $strict);
            } catch (Exception $exception) {
                // Generate an error
                $container->response->createError($exception, $container->request);

                // The response writer could not produce acceptable content. Flip flag and prepare
                // the response one more time
                $strict = false;

                // Go back up and prepare the new response
                goto prepareResponse;
            }
        }
    }
}
