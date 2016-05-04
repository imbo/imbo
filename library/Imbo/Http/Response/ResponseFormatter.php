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

use Imbo\EventManager\EventInterface,
    Imbo\EventListener\ListenerInterface,
    Imbo\Model,
    Imbo\Exception,
    Symfony\Component\HttpFoundation\AcceptHeader;

/**
 * This event listener will correctly format the response body based on the Accept headers in the
 * request
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Http
 */
class ResponseFormatter implements ListenerInterface {
    /**
     * Content formatters
     *
     * @var array
     */
    private $formatters;

    /**
     * The default mime type to use when formatting a response
     *
     * @var string
     */
    private $defaultMimeType = 'application/json';

    /**
     * Mapping from extensions to mime types
     *
     * @var array
     */
    private $extensionsToMimeType = [
        'json' => 'application/json',
        'xml'  => 'application/xml',
        'gif'  => 'image/gif',
        'jpg'  => 'image/jpeg',
        'png'  => 'image/png',
    ];

    /**
     * Supported content types and the associated formatter class name or instance, or in the
     * case of an image model, the resulting image type
     *
     * @var array
     */
    private $supportedTypes = [
        'application/json' => 'json',
        'application/xml'  => 'xml',
        'image/gif'        => 'gif',
        'image/png'        => 'png',
        'image/jpeg'       => 'jpg',
    ];

    /**
     * The default types that models support, in a prioritized order
     *
     * @var array
     */
    private $defaultModelTypes = [
        'application/json',
        'application/xml',
    ];

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
    private $modelTypes = [
        'image' => [
            'image/jpeg',
            'image/png',
            'image/gif',
        ],
    ];

    /**
     * The formatter to use
     *
     * @var string
     */
    private $formatter;

    /**
     * Class constructor
     *
     * @param array $param Parameters for the event listener
     */
    public function __construct(array $params) {
        $this->formatters = $params['formatters'];
        $this->contentNegotiation = $params['contentNegotiation'];
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents() {
        return [
            'response.send' => ['format' => 20],
            'response.negotiate' => 'negotiate',
        ];
    }

    /**
     * Set the formatter
     *
     * @param string $formatter The formatter to set
     * @return self
     */
    public function setFormatter($formatter) {
        $this->formatter = $formatter;

        return $this;
    }

    /**
     * Get the formatter
     *
     * @return string
     */
    public function getFormatter() {
        return $this->formatter;
    }

    /**
     * Perform content negotiation by looking the the current URL and the Accept request header
     *
     * @param EventInterface $event The event instance
     */
    public function negotiate(EventInterface $event) {
        $request = $event->getRequest();
        $response = $event->getResponse();

        $formatter = null;
        $extension = $request->getExtension();
        $routeName = (string) $request->getRoute();
        $config = $event->getConfig();
        $contentNegotiateImages = $config['contentNegotiateImages'];
        $model = $response->getModel();

        if (!$extension && !$contentNegotiateImages && $model instanceof Model\Image) {
            // Configuration is telling us not to use content negotiation for images,
            // instead we want to use the original format of the image
            $mime = $model->getMimeType();
            $formatter = $this->supportedTypes[$mime];
        } else if ($extension && !($model instanceof Model\Error && ($routeName === 'image' || $routeName === 'globalshorturl'))) {
            // The user agent wants a specific type. Skip content negotiation completely, but not
            // if the request is against the image resource (or the global short url resource), and
            // ended up as an error, because then Imbo would try to render the error as an image.
            $mime = $this->defaultMimeType;

            if (isset($this->extensionsToMimeType[$extension])) {
                $mime = $this->extensionsToMimeType[$extension];
            }

            $formatter = $this->supportedTypes[$mime];
        } else {
            // Set Vary to Accept since we are doing content negotiation based on Accept
            $response->setVary('Accept', false);

            // No extension have been provided
            $acceptableTypes = [];

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

            if (!$match && !$event->hasArgument('noStrict')) {
                // No types matched with strict mode enabled. The client does not want any of Imbo's
                // supported types. Y U NO ACCEPT MY TYPES?! FFFFUUUUUUU!
                throw new Exception\RuntimeException('Not acceptable', 406);
            } else if (!$match) {
                // There was no match but we don't want to be an ass about it. Send a response
                // anyway (allowed according to RFC2616, section 10.4.7)
                $formatter = $this->supportedTypes[$this->defaultMimeType];
            }
        }

        $this->formatter = $formatter;
    }

    /**
     * Response send hook
     *
     * @param EventInterface $event The current event
     */
    public function format(EventInterface $event) {
        $response = $event->getResponse();
        $model = $response->getModel();

        if ($response->getStatusCode() === 204 || !$model) {
            // No content to write
            return;
        }

        $request = $event->getRequest();

        // If we are dealing with an image we want to trigger an event that handles a possible
        // conversion
        if ($model instanceof Model\Image) {
            $eventManager = $event->getManager();

            if ($this->extensionsToMimeType[$this->formatter] !== $model->getMimeType()) {
                $eventManager->trigger('image.transformation.convert', [
                    'image' => $model,
                    'params' => [
                        'type' => $this->formatter,
                    ],
                ]);
            }

            // Finished transforming the image
            $eventManager->trigger('image.transformed', ['image' => $model]);

            $formattedData = $model->getBlob();
            $contentType = $model->getMimeType();
        } else {
            // Create an instance of the formatter
            $formatter = $this->formatters[$this->formatter];

            $formattedData = $formatter->format($model);
            $contentType = $formatter->getContentType();
        }

        if ($contentType === 'application/json') {
            foreach (['callback', 'jsonp', 'json'] as $validParam) {
                if ($request->query->has($validParam)) {
                    $formattedData = sprintf("%s(%s)", $request->query->get($validParam), $formattedData);
                    break;
                }
            }
        }

        $response->headers->add([
            'Content-Type' => $contentType,
            'Content-Length' => strlen($formattedData),
        ]);

        if ($request->getMethod() !== 'HEAD') {
            $response->setContent($formattedData);
        }
    }
}
