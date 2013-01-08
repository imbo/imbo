<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace Imbo\Http\Response;

use Imbo\Http\HeaderContainer,
    Imbo\EventManager\EventInterface,
    Imbo\EventListener\ListenerInterface,
    Imbo\EventListener\ListenerDefinition,
    Imbo\Exception,
    Imbo\Http\Request\RequestInterface,
    Imbo\Image\Image,
    DateTime;

/**
 * Response object from the server to the client
 *
 * @package Http
 * @author Christer Edvartsen <cogo@starzinger.net>
 */
class Response implements ListenerInterface, ResponseInterface {
    /**
     * Different status codes
     *
     * @var array
     */
    static public $statusCodes = array(
        // 1xx Informational
        100 => 'Continue',
        101 => 'Switching Protocols',

        // 2xx Success
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information', // 1.1
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',

        // 3xx Redirection
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other', // 1.1
        304 => 'Not Modified',
        305 => 'Use Proxy', // 1.1
        306 => 'Switch Proxy',
        307 => 'Temporary Redirect', // 1.1

        // 4xx Client Error
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Requested Range Not Satisfiable',
        417 => 'Expectation Failed',
        418 => 'I\'m a teapot!',

        // 5xx Server Error
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported',
        509 => 'Bandwidth Limit Exceeded',
    );

    /**
     * HTTP protocol version
     *
     * @var string
     */
    private $protocolVersion = '1.1';

    /**
     * HTTP status code
     *
     * @var int
     */
    private $statusCode = 200;

    /**
     * Custom HTTP status message
     *
     * @var string
     */
    private $statusMessage;

    /**
     * Response headers
     *
     * @var HeaderContainer
     */
    private $headers;

    /**
     * The body of the response
     *
     * @var string
     */
    private $body;

    /**
     * Image instance used with the image resource
     *
     * @var Image
     */
    private $image;

    /**
     * Class constructor
     *
     * @param HeaderContainer $headerContainer An optional instance of a header container. An empty
     *                                         one will be created if not specified.
     */
    public function __construct(HeaderContainer $headerContainer = null) {
        if ($headerContainer === null) {
            $headerContainer = new HeaderContainer();
        }

        $this->headers = $headerContainer;
    }

    /**
     * {@inheritdoc}
     */
    public function getStatusCode() {
        return $this->statusCode;
    }

    /**
     * {@inheritdoc}
     */
    public function setStatusCode($code) {
        $this->statusCode = (int) $code;
        $this->statusMessage = null;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getStatusMessage() {
        return $this->statusMessage ?: self::$statusCodes[$this->getStatusCode()];
    }

    /**
     * {@inheritdoc}
     */
    public function setStatusMessage($message) {
        $this->statusMessage = $message;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getHeaders() {
        return $this->headers;
    }

    /**
     * {@inheritdoc}
     */
    public function setHeaders(HeaderContainer $headers) {
        $this->headers = $headers;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getBody() {
        return $this->body;
    }

    /**
     * {@inheritdoc}
     */
    public function setBody($content) {
        $this->body = $content;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getProtocolVersion() {
        return $this->protocolVersion;
    }

    /**
     * {@inheritdoc}
     */
    public function setProtocolVersion($version) {
        $this->protocolVersion = $version;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getImage() {
        return $this->image;
    }

    /**
     * {@inheritdoc}
     */
    public function setImage(Image $image) {
        $this->image = $image;

        return $this;
    }

    /**
     * Send the response to the client, including headers
     *
     * @param EventInterface $event The current event
     */
    public function send(EventInterface $event) {
        $request = $event->getRequest();
        $requestHeaders = $request->getHeaders();

        $ifModifiedSince = $requestHeaders->get('if-modified-since');
        $ifNoneMatch = $requestHeaders->get('if-none-match');
        $lastModified = $this->headers->get('last-modified');
        $etag = $this->headers->get('etag');

        if (
            $ifModifiedSince && $ifNoneMatch && (
                $lastModified === $ifModifiedSince &&
                $etag === $ifNoneMatch
            )
        ) {
            $this->setNotModified();
        }

        // Inject a possible image identifier into the response headers
        $imageIdentifier = null;

        if ($image = $request->getImage()) {
            // The request has an image. This means that an image was just added. Use the image's
            // checksum
            $imageIdentifier = $image->getChecksum();
        } else if ($identifier = $request->getImageIdentifier()) {
            // An image identifier exists in the request, use that one (and not a possible image
            // checksum for an image attached to the response)
            $imageIdentifier = $identifier;
        }

        if ($imageIdentifier) {
            $this->headers->set('X-Imbo-ImageIdentifier', $imageIdentifier);
        }

        $this->sendHeaders();
        $this->sendContent();
    }

    /**
     * {@inheritdoc}
     */
    public function setNotModified() {
        $this->setStatusCode(304);
        $this->setBody(null);
        $headers = $this->getHeaders();

        foreach (array('Allow', 'Content-Encoding', 'Content-Language', 'Content-Length', 'Content-MD5', 'Last-Modified') as $header) {
            $headers->remove($header);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getLastModified() {
        return $this->headers->get('Last-Modified');
    }

    /**
     * {@inheritdoc}
     */
    public function isError() {
        return $this->getStatusCode() >= 400;
    }

    /**
     * {@inheritdoc}
     */
    public function createError(Exception $exception, RequestInterface $request) {
        $date = new DateTime();

        $code         = $exception->getCode();
        $message      = $exception->getMessage();
        $timestamp    = $date->format('D, d M Y H:i:s') . ' GMT';
        $internalCode = $exception->getImboErrorCode();

        if ($internalCode === null) {
            $internalCode = Exception::ERR_UNSPECIFIED;
        }

        $this->setStatusCode($code);

        // Add error information to the response headers and remove the ETag and Last-Modified headers
        $responseHeaders = $this->getHeaders();
        $responseHeaders->set('X-Imbo-Error-Message', $message)
                        ->set('X-Imbo-Error-InternalCode', $internalCode)
                        ->set('X-Imbo-Error-Date', $timestamp)
                        ->remove('ETag')
                        ->remove('Last-Modified');

        // Prepare response data if the request expects a response body
        if ($request->getMethod() !== RequestInterface::METHOD_HEAD) {
            $data = array(
                'error' => array(
                    'code'          => $code,
                    'message'       => $message,
                    'date'          => $timestamp,
                    'imboErrorCode' => $internalCode,
                ),
            );

            if ($image = $request->getImage()) {
                $data['imageIdentifier'] = $image->getChecksum();
            } else if ($identifier = $request->getImageIdentifier()) {
                $data['imageIdentifier'] = $identifier;
            }

            $this->setBody($data);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getDefinition() {
        return array(
            new ListenerDefinition('response.send', array($this, 'send')),
        );
    }

    /**
     * Send all headers to the client
     */
    private function sendHeaders() {
        if (headers_sent()) {
            return;
        }

        $statusCode = $this->getStatusCode();

        $statusLine = sprintf("HTTP/%s %d %s", $this->getProtocolVersion(), $statusCode, $this->getStatusMessage());
        header($statusLine);

        // Fetch all headers
        $headers = $this->headers->getAll();

        // Closure that will translate the normalized header names to a prettier format (HTTP
        // header names are case insensitive anyways (RFC2616, section 4.2))
        $transform = function($name) {
            return preg_replace_callback('/^[a-z]|-[a-z]/', function($match) {
                return strtoupper($match[0]);
            }, $name);
        };

        // Send all headers to the client
        foreach ($headers as $name => $value) {
            header($transform($name) . ': ' . $value);
        }
    }

    /**
     * Send the content to the client
     */
    private function sendContent() {
        $body = $this->getBody();

        if (is_array($body)) {
            if (!headers_sent()) {
                header('Content-Type: application/json');
            }

            $body = json_encode($body);
        }

        echo $body;
    }
}
