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

use Imbo\Http\Request\RequestInterface,
    Imbo\Http\Response\Formatter,
    Imbo\Http\ContentNegotiation,
    Imbo\Exception\RuntimeException,
    Imbo\Container,
    Imbo\ContainerAware;

/**
 * Response writer
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Http
 */
class ResponseWriter implements ContainerAware, ResponseWriterInterface {
    /**
     * Service container
     *
     * @var Container
     */
    private $container;

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
     * {@inheritdoc}
     */
    public function setContainer(Container $container) {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function write(array $data, RequestInterface $request, ResponseInterface $response, $strict = true) {
        // The formatter to use
        $formatter = null;

        if ($extension = $request->getExtension()) {
            // The user agent wants a specific type. Skip content negotiation completely
            $mime = $this->defaultMimeType;

            if (isset($this->extensionsToMimeType[$extension])) {
                $mime = $this->extensionsToMimeType[$extension];
            }
            $formatter = $this->supportedTypes[$mime];
        } else {
            // Try to find the best match
            $acceptableTypes = $request->getAcceptableContentTypes();
            $match = false;
            $maxQ = 0;

            foreach ($this->supportedTypes as $mime => $formatterClass) {
                if (($q = $this->container->get('contentNegotiation')->isAcceptable($mime, $acceptableTypes)) && ($q > $maxQ)) {
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

        if ($request->getMethod() === RequestInterface::METHOD_HEAD) {
            $response->setBody(null);
        } else {
            $response->setBody($formattedData);
        }
    }
}
