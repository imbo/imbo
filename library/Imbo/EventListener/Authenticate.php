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
    Imbo\Validate,
    Imbo\Exception\RuntimeException,
    Imbo\Exception;

/**
 * Authentication event listener
 *
 * This listener enforces the usage of the signature and timestamp parameters when the user agent
 * wants to perform write operations (PUT/POST/DELETE).
 *
 * @package EventListener
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/imbo/imbo
 */
class Authenticate extends Listener implements ListenerInterface {
    /**
     * Timestamp validator
     *
     * @var Imbo\Validate\ValidateInterface
     */
    private $timestampValidator;

    /**
     * Signature validator
     *
     * @var Imbo\Validate\SignatureInterface
     */
    private $signatureValidator;

    /**
     * @see Imbo\EventListener\ListenerInterface::getEvents()
     */
    public function getEvents() {
        return array(
            'image.put.pre',
            'image.post.pre',
            'image.delete.pre',

            'metadata.put.pre',
            'metadata.post.pre',
            'metadata.delete.pre',
        );
    }

    /**
     * Class constructor
     *
     * @param Imbo\Validate\ValidateInterface $timestampValidator A timestamp validator
     * @param Imbo\Validate\SignatureInterface $signatureValidator A signature validator
     */
    public function __construct(Validate\ValidateInterface $timestampValidator = null,
                                Validate\SignatureInterface $signatureValidator = null) {
        if ($timestampValidator === null) {
            $timestampValidator = new Validate\Timestamp();
        }

        if ($signatureValidator === null) {
            $signatureValidator = new Validate\Signature();
        }

        $this->timestampValidator = $timestampValidator;
        $this->signatureValidator = $signatureValidator;
    }

    /**
     * @see Imbo\EventListener\ListenerInterface::invoke()
     */
    public function invoke(EventInterface $event) {
        $response = $event->getResponse();
        $request  = $event->getRequest();
        $query    = $request->getQuery();

        // Check for signature and timestamp
        foreach (array('signature', 'timestamp') as $param) {
            if (!$query->has($param)) {
                $e = new RuntimeException('Missing required authentication parameter: ' . $param, 400);
                $e->setImboErrorCode(Exception::AUTH_MISSING_PARAM);

                throw $e;
            }
        }

        // Fetch values we want to validate and remove them from the request
        $timestamp = $query->get('timestamp');
        $signature = $query->get('signature');

        $query->remove('signature')
              ->remove('timestamp');

        if (!$this->timestampValidator->isValid($timestamp)) {
            $e = new RuntimeException('Invalid timestamp: ' . $timestamp, 400);
            $e->setImboErrorCode(Exception::AUTH_INVALID_TIMESTAMP);

            throw $e;
        }

        // Add the URL used for auth to the response headers
        $response->getHeaders()->set('X-Imbo-AuthUrl', $request->getUrl());

        // Prepare the signature validation
        $this->signatureValidator->setHttpMethod($request->getMethod())
                                 ->setUrl($request->getUrl())
                                 ->setTimestamp($timestamp)
                                 ->setPublicKey($request->getPublicKey())
                                 ->setPrivateKey($request->getPrivateKey());

        if (!$this->signatureValidator->isValid($signature)) {
            $e = new RuntimeException('Signature mismatch', 400);
            $e->setImboErrorCode(Exception::AUTH_SIGNATURE_MISMATCH);

            throw $e;
        }
    }
}
