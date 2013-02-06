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
    Imbo\ContainerAware,
    Imbo\Model;

/**
 * Response writer
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Http
 */
class ResponseWriter implements ContainerAware {
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
        'application/json' => 'jsonFormatter',
        'application/xml'  => 'xmlFormatter',
        'text/html'        => 'htmlFormatter',
        'image/gif'        => 'gifFormatter',
        'image/png'        => 'pngFormatter',
        'image/jpeg'       => 'jpegFormatter',
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
        'gif'  => 'image/gif',
        'jpg'  => 'image/jpeg',
        'png'  => 'image/png',
    );

    /**
     * The default types that models support
     *
     * @var array
     */
    private $defaultModelTypes = array(
        'application/json',
        'application/xml',
        'text/html',
    );

    /**
     * The types the different models can be expressed as, if they don't support the default ones
     *
     * The keys are the last part of the model name, lowercased:
     *
     * Imbo\Model\Image => image
     * Imbo\Model\FooBar => foobar
     *
     * @var array
     */
    private $modelTypes = array(
        'image' => array(
            'image/gif',
            'image/png',
            'image/jpeg',
        ),
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
     * Return a formatted message using a chosen formatter based on the request
     *
     * @param ModelInterface $model Model to write in another format
     * @param RequestInterface $request A request instance
     * @param ResponseInterface $response A response instance
     * @param boolean $strict Whether or not the response writer will throw a RuntimeException with
     *                        status code 406 (Not Acceptable) if it can not produce acceptable
     *                        content for the user agent.
     * @throws RuntimeException
     */
    public function write(Model\ModelInterface $model, RequestInterface $request, ResponseInterface $response, $strict = true) {
        // The entry of the formatter to fetch from the container
        $entry = null;

        if ($extension = $request->getExtension()) {
            // The user agent wants a specific type. Skip content negotiation completely
            $mime = $this->defaultMimeType;

            if (isset($this->extensionsToMimeType[$extension])) {
                $mime = $this->extensionsToMimeType[$extension];
            }

            $entry = $this->supportedTypes[$mime];
        } else {
            // Set Vary to Accept since we are doing content negotiation based on Accept
            $response->getHeaders()->set('Vary', 'Accept');

            // No extension have been provided
            $contentNegotiation = $this->container->get('contentNegotiation');
            $acceptableTypes = $request->getAcceptableContentTypes();

            // If we have an image, see if the client accepts the current format
            if ($model instanceof Model\Image) {
                $mimeType = $model->getMimeType();

                if ($contentNegotiation->isAcceptable($mimeType, $acceptableTypes)) {
                    $entry = $this->supportedTypes[$mimeType];
                }
            }

            if (!$entry) {
                // Try to find the best match since the client does not accept the original mime
                // type
                $match = false;
                $maxQ = 0;

                // Specify which types to check for since all models can't be formatted by all
                // formatters
                $modelClass = get_class($model);
                $modelType = strtolower(substr($modelClass, strrpos($modelClass, '\\') + 1));
                $types = $this->defaultModelTypes;

                if (isset($this->modelTypes[$modelType])) {
                    $types = $this->modelTypes[$modelType];
                }

                foreach ($types as $mime) {
                    if (($q = $contentNegotiation->isAcceptable($mime, $acceptableTypes)) && ($q > $maxQ)) {
                        $maxQ = $q;
                        $match = true;
                        $entry = $this->supportedTypes[$mime];
                    }
                }

                if (!$match && $strict) {
                    // No types matched with strict mode enabled. The client does not want any of Imbo's
                    // supported types. Y U NO ACCEPT MY TYPES?! FFFFUUUUUUU!
                    throw new RuntimeException('Not acceptable', 406);
                } else if (!$match) {
                    // There was no match but we don't want to be an ass about it. Send a response
                    // anyway (allowed according to RFC2616, section 10.4.7)
                    $entry = $this->supportedTypes[$this->defaultMimeType];
                }
            }
        }

        // Create an instance of the formatter
        $formatter = $this->container->get($entry);
        $formattedData = $formatter->format($model);
        $contentType = $formatter->getContentType();

        if ($contentType === 'application/json') {
            $query = $request->getQuery();

            foreach (array('callback', 'jsonp', 'json') as $validParam) {
                if ($query->has($validParam)) {
                    $formattedData = sprintf("%s(%s)", $query->get($validParam), $formattedData);
                    break;
                }
            }
        }

        $response->getHeaders()->set('Content-Type', $contentType)
                               ->set('Content-Length', strlen($formattedData));

        if ($request->getMethod() !== RequestInterface::METHOD_HEAD) {
            $response->setBody($formattedData);
        }
    }
}
