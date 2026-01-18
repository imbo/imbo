<?php declare(strict_types=1);

namespace Imbo;

use Closure;
use Imbo\Auth\AccessControl\Adapter\AdapterInterface as AccessControlAdapter;
use Imbo\Database\DatabaseInterface as DatabaseAdapter;
use Imbo\EventListener\Initializer\Imagick as ImagickInitializer;
use Imbo\Http\Request\Request;
use Imbo\Image\Identifier\Generator\GeneratorInterface;
use Imbo\Image\Identifier\Generator\RandomString;
use Imbo\Image\InputLoader\Basic as BasicInput;
use Imbo\Image\OutputConverter\Basic as BasicOutput;
use Imbo\Image\Transformation;
use Imbo\Storage\StorageInterface as StorageAdapter;

return [
    /**
     * The main database adapter for storing images metadata.
     *
     * @var DatabaseAdapter|Closure(Request):DatabaseAdapter|null
     */
    'database' => null,

    /**
     * The main storage adapter for storing images.
     *
     * @var StorageAdapter|Closure(Request):StorageAdapter|null
     */
    'storage' => null,

    /**
     * Access Control adapter.
     *
     * @var AccessControlAdapter|Closure(Request):AccessControlAdapter|null
     */
    'accessControl' => null,

    /**
     * Image identifier generator.
     *
     * @var GeneratorInterface|Closure(Request):GeneratorInterface|null
     */
    'imageIdentifierGenerator' => new RandomString(),

    /**
     * Default HTTP cache headers.
     *
     * @var array{maxAge:int,mustRevalidate:bool,public:bool}
     */
    'httpCacheHeaders' => [
        'maxAge' => 0,
        'mustRevalidate' => true,
        'public' => true,
    ],

    /**
     * Content negotiation for images.
     *
     * @var bool
     */
    'contentNegotiateImages' => true,

    /**
     * Rethrow final exceptions.
     *
     * @var bool
     */
    'rethrowFinalException' => false,

    /**
     * Trusted proxies.
     *
     * @var array<string>
     */
    'trustedProxies' => [],

    /**
     * Authentication options.
     *
     * @var array{protocol:string}
     */
    'authentication' => [
        'protocol' => 'incoming',
    ],

    /**
     * Event listeners.
     *
     * @var array<string,mixed>
     */
    'eventListeners' => [
        'accessControl' => EventListener\AccessControl::class,
        'accessToken' => EventListener\AccessToken::class,
        'auth' => EventListener\Authenticate::class,
        'statsAccess' => [
            'listener' => EventListener\StatsAccess::class,
            'params' => [
                'allow' => ['127.0.0.1', '::1'],
            ],
        ],
        'imagick' => EventListener\Imagick::class,
        'outputConverter' => EventListener\LoaderOutputConverterImagick::class,
    ],

    /**
     * Image transformations.
     *
     * @var array<string,mixed>
     */
    'transformations' => [
        'autoRotate' => Transformation\AutoRotate::class,
        'blur' => Transformation\Blur::class,
        'border' => Transformation\Border::class,
        'canvas' => Transformation\Canvas::class,
        'clip' => Transformation\Clip::class,
        'compress' => Transformation\Compress::class,
        'contrast' => Transformation\Contrast::class,
        'convert' => Transformation\Convert::class,
        'crop' => Transformation\Crop::class,
        'desaturate' => Transformation\Desaturate::class,
        'drawPois' => Transformation\DrawPois::class,
        'flipHorizontally' => Transformation\FlipHorizontally::class,
        'flipVertically' => Transformation\FlipVertically::class,
        'histogram' => Transformation\Histogram::class,
        'level' => Transformation\Level::class,
        'maxSize' => Transformation\MaxSize::class,
        'modulate' => Transformation\Modulate::class,
        'progressive' => Transformation\Progressive::class,
        'resize' => Transformation\Resize::class,
        'rotate' => Transformation\Rotate::class,
        'sepia' => Transformation\Sepia::class,
        'sharpen' => Transformation\Sharpen::class,
        'smartSize' => Transformation\SmartSize::class,
        'strip' => Transformation\Strip::class,
        'thumbnail' => Transformation\Thumbnail::class,
        'transpose' => Transformation\Transpose::class,
        'transverse' => Transformation\Transverse::class,
        'vignette' => Transformation\Vignette::class,
        'watermark' => Transformation\Watermark::class,
    ],

    /**
     * Initializers for event listeners.
     *
     * @var array<string,mixed>
     */
    'eventListenerInitializers' => [
        'imagick' => ImagickInitializer::class,
    ],

    /**
     * Transformation presets.
     *
     * @var array<string,mixed>
     */
    'transformationPresets' => [],

    /**
     * Index redirect.
     *
     * @var ?string
     */
    'indexRedirect' => null,

    /**
     * Input loaders.
     *
     * @var array<string,mixed>
     */
    'inputLoaders' => [
        'basic' => BasicInput::class,
    ],

    /**
     * Custom output converters.
     *
     * @var array<string,mixed>
     */
    'outputConverters' => [
        'basic' => BasicOutput::class,
    ],

    /**
     * Optimizations.
     *
     * @var array<string,mixed>
     */
    'optimizations' => [
        'jpegSizeHint' => true,
    ],

    /**
     * Custom routes for Imbo.
     *
     * TODO: remove
     */
    'routes' => [],
];
