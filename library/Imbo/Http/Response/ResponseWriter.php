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

use Imbo\Http\Request\Request,
    Imbo\Http\Response\Formatter,
    Imbo\Http\ContentNegotiation,
    Imbo\Exception\RuntimeException,
    Imbo\Model,
    Symfony\Component\HttpFoundation\AcceptHeader;

/**
 * Response writer
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Http
 */
class ResponseWriter {
    /**
     * Supported content types and the associated formatter class name or instance
     *
     * @var array
     */
    private $supportedTypes = array(
        'application/json' => 'json',
        'application/xml'  => 'xml',
        'image/gif'        => 'gif',
        'image/png'        => 'png',
        'image/jpeg'       => 'jpeg',
    );

    /**
     * Formatters
     *
     * @var array
     */
    private $formatters;

    /**
     * Mapping from extensions to mime types
     *
     * @var array
     */
    private $extensionsToMimeType = array(
        'json' => 'application/json',
        'xml'  => 'application/xml',
        'gif'  => 'image/gif',
        'jpg'  => 'image/jpeg',
        'png'  => 'image/png',
    );

    /**
     * The default types that models support, in a prioritized order
     *
     * @var array
     */
    private $defaultModelTypes = array(
        'application/json',
        'application/xml',
    );

    /**
     * The types the different models can be expressed as, if they don't support the default ones,
     * in a prioritized order. If the user agent sends "Accept: image/*" the first one will be the
     * one used.
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
            'image/jpeg',
            'image/png',
            'image/gif',
        ),
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
     * @param array $formatters An array of formatters to use
     */
    public function __construct(array $formatters, ContentNegotiation $contentNegotiation) {
        $this->formatters = $formatters;
        $this->contentNegotiation = $contentNegotiation;
    }

    /**
     * Return a formatted message using a chosen formatter based on the request
     *
     * @param Model\ModelInterface $model Model to write in another format
     * @param Request $request A request instance
     * @param Response $response A response instance
     * @param boolean $strict Whether or not the response writer will throw a RuntimeException with
     *                        status code 406 (Not Acceptable) if it can not produce acceptable
     *                        content for the user agent.
     * @throws RuntimeException
     */
    public function write(Model\ModelInterface $model, Request $request, Response $response, $strict = true) {
        $formatter = null;
        $extension = $request->getExtension();
        $routeName = (string) $request->getRoute();

        if ($extension && !($model instanceof Model\Error && $routeName === 'image')) {
            // The user agent wants a specific type. Skip content negotiation completely, but not
            // if the request is against the image resource, and ended up as an error, because then
            // Imbo would try to render the error as an image.
            $mime = $this->defaultMimeType;

            if (isset($this->extensionsToMimeType[$extension])) {
                $mime = $this->extensionsToMimeType[$extension];
            }

            $formatter = $this->supportedTypes[$mime];
        } else {
            // Set Vary to Accept since we are doing content negotiation based on Accept
            $response->setVary('Accept');

            // No extension have been provided
            $acceptableTypes = array();

            foreach (AcceptHeader::fromString($request->headers->get('Accept', '*/*'))->all() as $item) {
                $acceptableTypes[$item->getValue()] = $item->getQuality();
            }

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

            // If we are dealing with images we want to make sure the original mime type of the
            // image is checked first. If the client does not really have any preference with
            // regards to the mime type (*/* or image/*) this results in the original mime type of
            // the image being sent.
            if ($model instanceof Model\Image) {
                $original = $model->getMimeType();

                if ($types[0] !== $original) {
                    $types = array_filter($types, function($type) use ($original) {
                        return $type !== $original;
                    });
                    array_unshift($types, $original);
                }
            }

            foreach ($types as $mime) {
                if (($q = $this->contentNegotiation->isAcceptable($mime, $acceptableTypes)) && ($q > $maxQ)) {
                    $maxQ = $q;
                    $match = true;
                    $formatter = $this->supportedTypes[$mime];
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
        $formatter = $this->formatters[$formatter];
        $formattedData = $formatter->format($model);
        $contentType = $formatter->getContentType();

        if ($contentType === 'application/json') {
            foreach (array('callback', 'jsonp', 'json') as $validParam) {
                if ($request->query->has($validParam)) {
                    $formattedData = sprintf("%s(%s)", $request->query->get($validParam), $formattedData);
                    break;
                }
            }
        }

        $response->headers->add(array(
            'Content-Type' => $contentType,
            'Content-Length' => strlen($formattedData),
        ));

        if ($request->getMethod() !== 'HEAD') {
            $response->setContent($formattedData);
        }
    }
}
