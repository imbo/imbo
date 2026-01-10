<?php declare(strict_types=1);

namespace Imbo;

use Imbo\Auth\AccessControl\Adapter\AdapterInterface as AccessControlAdapter;
use Imbo\Database\DatabaseInterface as DatabaseAdapter;
use Imbo\EventListener\Initializer\Imagick as ImagickInitializer;
use Imbo\Image\Identifier\Generator\RandomString;
use Imbo\Image\InputLoader\Basic as BasicInput;
use Imbo\Image\OutputConverter\Basic as BasicOutput;
use Imbo\Image\Transformation;
use Imbo\Storage\StorageInterface as StorageAdapter;

return [
    /**
     * Access Control adapter.
     *
     * See the different adapter implementations for possible configuration parameters.
     * The value must be set to a closure returning an instance of
     * Imbo\Auth\AccessControl\Adapter\AdapterInterface, or an implementation of said interface.
     *
     * @var AccessControlAdapter|Closure:AccessControlAdapter
     */
    'accessControl' => null,

    /**
     * Database adapter.
     *
     * See the different adapter implementations for possible configuration parameters. The value
     * must be set to a closure returning an instance of Imbo\Database\DatabaseInterface, or an
     * implementation of said interface.
     *
     * @var DatabaseAdapter|Closure:DatabaseAdapter
     */
    'database' => null,

    /**
     * Storage adapter.
     *
     * See the different adapter implementations for possible configuration parameters. The value
     * must be set to a closure returning an instance of Imbo\Storage\StorageInterface, or an
     * implementation of said interface.
     *
     * @var StorageAdapter|Closure:StorageAdapter
     */
    'storage' => null,

    /**
     * Image identifier generator.
     *
     * See the different adapter implementations for possible configuration parameters.
     * The value must be set to a closure returning an instance of
     * Imbo\Image\Identifier\Generator\GeneratorInterface, or an implementation of said interface.
     *
     * @var Imbo\Image\Identifier\Generator\GeneratorInterface|Closure
     */
    'imageIdentifierGenerator' => new RandomString(),

    /**
     * Keep errors as exceptions.
     *
     * By default Imbo will catch any exceptions thrown internally and instead trigger a
     * user error with the exception message. If you set this option to `true`, Imbo will
     * instead rethrow the generated exception, giving you a full stack trace in your PHP
     * error log. This should not be enabled in production, unless you have configured
     * PHP in the recommended way with a separate error_log and display_errors=Off.
     *
     * @var bool
     */
    'rethrowFinalException' => false,

    /**
     * Whether to content negotiate images or not. If set to true, Imbo will try to find a
     * suitable image format based on the Accept-header received. If set to false, it will
     * deliver the image in the format it was originally added as. Note that this does not
     * affect images requested with a specific extension (.jpg/.png/.gif etc).
     *
     * @var bool
     */
    'contentNegotiateImages' => true,

    /**
     * Various optimizations that might be enabled or disabled. Most of the configuration-
     * exposed optimizations have some trade, be it speed or image quality, which is why
     * it's possible to disable them through configuration.
     *
     * @var array
     */
    'optimizations' => [
        /**
         * Tries to calculate what the transformed output size of images will be before
         * loading the image into Imagick, which set a hint to libjpeg that enables
         * "shrink-on-load", which significantly increases speed or resizing.
         *
         * Tradeoffs: Transformations have to adjust parameters based on new input size,
         * some parameters will be one pixel off. Image quality should be the same for
         * most images, but there is always the possibility of slightly worse quality
         *
         * @var bool
         */
        'jpegSizeHint' => true,
    ],

    /**
     * HTTP cache header settings that are applied to resources that do not explicitly set
     * other values. For instance, the `image` resource sets a very long `max-age`, as it
     * shouldn't change over time. The `metadata` resource however could potentially change
     * much more often. To ensure that clients get fresh responses, the default is to ask
     * the client to always revalidate (ask if there has been any changes since last fetch).
     *
     * @var array
     */
    'httpCacheHeaders' => [
        'maxAge' => 0,
        'mustRevalidate' => true,
        'public' => true,
    ],

    /**
     * Options related to authentication. See documentation for individual settings.
     *
     * @var array
     */
    'authentication' => [
        /**
         * Imbo generates access tokens and authentication signatures based on the incoming URL,
         * and includes the protocol (by default). This can sometimes be problematic, for instance
         * when Imbo is behind a load balancer which doesn't send `X-Forwarded-Proto` header, or
         * if you want to use protocol-less image URLs (`//imbo.host/users/some-user/images/img`).
         *
         * This option allows you to control how Imbo's authentication should behave:
         *
         * - `incoming`
         *     Will try to detect the incoming protocol - this is based on `$_SERVER['HTTPS']` or
         *     the `X-Forwarded-Proto` header (given the `trustedProxies` option is configured).
         *     This is the default.
         *
         * - `both`
         *     Will try to match based on both HTTP and HTTPS protocols and allow the request if
         *     any of them yields the correct signature/access token.
         *
         * - `http`
         *     Will always use `http` as the protocol, replacing `https` with `http` in the
         *     incoming URL, if that is the case.
         *
         * - `https`
         *     Will always use `https` as the protocol, replacing `http` with `https` in the
         *     incoming URL, if that is the case.
         *
         * @var string
         */
        'protocol' => 'incoming',
    ],

    /**
     * Image transformations.
     *
     * An associative array where the keys identify the name used in URLs to trigger the
     * transformation. The value of each element in this array can be on of the following:
     *
     * 1) A string representing a class name of a class extending the
     *    Imbo\Image\Transformation\Transformation abstract class
     *
     * 2) An instance of an object implementing the Imbo\Image\Transformation\Transformation
     *    abstract class
     *
     * 3) A closure returning an instance of an object extending the
     *    Imbo\Image\Transformation\Transformation abstract class
     *
     * @var array
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
     * Event listeners.
     *
     * An associative array where the keys are short names for the event listeners (not really used
     * for anything, but exists so you can override/unset some helpers from config.php). The values
     * of each element in this array can be one of the following:
     *
     * 1) A string representing a class name of a class implementing the
     *    Imbo\EventListener\ListenerInterface interface
     *
     * 2) An instance of an object implementing the Imbo\EventListener\ListenerInterface interface
     *
     * 3) A closure returning an instance of an object implementing the
     *    Imbo\EventListener\ListenerInterface interface
     *
     * 4) An array with the following keys:
     *
     *   - listener (required)
     *   - params
     *   - publicKeys
     *
     *   where 'listener' is one of the following:
     *
     *     1) a string representing a class name of a class implementing the
     *        Imbo\EventListener\ListenerInterface interface
     *
     *     2) an instance of the Imbo\EventListener\ListenerInterface interface
     *
     *     3) a closure returning an instance Imbo\EventListener\ListenerInterface
     *
     *   'params' is an array with parameters for the constructor of the event listener. This is
     *   only used when the 'listener' key is a string containing a class name.
     *
     *   'publicKeys' is an array with one of the following keys:
     *
     *     - whitelist
     *     - blacklist
     *
     *     where 'whitelist' is an array of public keys that the listener *will* trigger for, and
     *     'blacklist' is an array of public keys that the listener *will not* trigger for.
     *
     * 5) An array with the following keys:
     *
     *   - events (required)
     *   - callback (required)
     *   - priority
     *   - publicKeys
     *
     *   where 'events' is an array of events that 'callback' will subscribe to. If your callback
     *   subscribes to several events, and you want to use different priorities for the events,
     *   simply specify an associative array where the keys are the event names, and the values are
     *   the priorities for each event. If you use this method, the 'priority' key will be ignored.
     *
     *   'callback' is any callable function. The function will receive a single argument, which is
     *   an instance of Imbo\EventManager\EventInterface.
     *
     *   'priority' is the priority of your callback. This defaults to 0 (low priority). The
     *   priority can also be a negative number if you want your listeners to be triggered after
     *   Imbo's event listeners.
     *
     *   'publicKeys' is the same as described above.
     *
     * Examples of how to add listeners:
     *
     * 'eventListeners' => [
     *   // 1) A class name in a string
     *   'accessToken' => 'Imbo\EventListener\ListenerInterface',
     *
     *   // 2) Implementation of a listener interface
     *   'auth' => new EventListener\Authenticate(),
     *
     *   // 3) Implementation of a listener interface with a public key filter
     *   'maxImageSize' => [
     *     'listener' => EventListener\MaxImageSize(1024, 768),
     *     'publicKeys' => [
     *       'whitelist' => [ ... ],
     *       // 'blacklist' => [ ... ],
     *       )
     *     )
     *   ),
     *
     *   // 4) A class name in a string with custom parameters for the listener
     *   'statsAccess' => [
     *       'listener' => 'Imbo\EventListener\StatsAccess',
     *       'params' => [
     *           'allow' => ['127.0.0.1', '::1'],
     *       ],
     *   ],
     *
     *   // 5) A closure that will subscribe to two events with different priorities
     *   'anotherCustomCallback' => [
     *       'callback' => function($event) {
     *           // Some code
     *       },
     *       'events' => [
     *           'image.get' => 20, // Trigger BEFORE the internal handler for "image.get"
     *           'image.post' => -20, // Trigger AFTER the internal handler for "image.post"
     *       ],
     *   ),
     *
     * @var array
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

        // Imagick-specific event listener for the built in image transformations
        'imagick' => EventListener\Imagick::class,

        // Pluggable output conversion
        'outputConverter' => EventListener\LoaderOutputConverterImagick::class,
    ],

    /**
     * Initializers for event listeners.
     *
     * If some of your event handlers requires extra initializing you can create initializer
     * classes. These classes must implement the Imbo\EventListener\Initializer\InitializerInterface
     * interface, and will be instantiated by Imbo.
     *
     * @var array
     */
    'eventListenerInitializers' => [
        'imagick' => ImagickInitializer::class,
    ],

    /**
     * Transformation presets.
     *
     * If you want to make custom transformation presets (or transformation collections) you can do
     * so here. The keys used will be the name of the transformation as used in the URI, and the
     * value is an array containing the names of the transformations in the collection.
     *
     * Example:
     *
     * 'transformationPresets' => [
     *     'graythumb' => [
     *         'thumbnail',
     *         'desaturate',
     *     ],
     *     'flipflop' => [
     *         'flipHorizontally',
     *         'flipVertically',
     *     ],
     * ],
     *
     * The above to examples can be triggered by ?t[]=graythumb and ?t[]=flipflop respectively
     *
     * @var array
     */
    'transformationPresets' => [],

    /**
     * Custom resources for Imbo.
     *
     * @see https://docs.imbo.io
     *
     * @var array
     */
    'resources' => [],

    /**
     * Custom routes for Imbo.
     *
     * @see https://docs.imbo.io
     *
     * @var array
     */
    'routes' => [],

    /**
     * Trusted proxies.
     *
     * If you find yourself behind some sort of proxy - like a load balancer - then certain header
     * information may be sent to you using special X-Forwarded-* headers. For example, the Host
     * HTTP header is usually used to return the requested host. But when you're behind a proxy,
     * the true host may be stored in a X-Forwarded-Host header.
     *
     * Since HTTP headers can be spoofed, Imbo does not trust these proxy headers by default.
     * If you are behind a proxy, you should manually whitelist your proxy.
     *
     * Note: Not all proxies set the required X-Forwarded-* headers by default. A search for
     *       "X-Forwarded-Proto <your proxy here>" usually gives helpful answers to how you can
     *       add them to incoming requests.
     *
     * Example:
     *
     * 'trustedProxies' => ['192.0.0.1', '10.0.0.0/8']
     *
     * @var array
     */
    'trustedProxies' => [],

    /**
     * Index redirect.
     *
     * Set this to a URL if you want the front page to do a redirect instead of showing generic
     * information regarding the Imbo-project.
     *
     * @var string
     */
    'indexRedirect' => null,

    /**
     * Input loaders.
     *
     * Each input loader must implement Imbo\Image\InputLoader\InputLoaderInterface.
     *
     * See the Imbo\Image\InputLoader\Basic input loader for the default fallback loader as an
     * example.
     *
     * @var array<string,Imbo\Image\InputLoader\InputLoaderInterface>|array<string,string>
     */
    'inputLoaders' => [
        'basic' => BasicInput::class,
    ],

    /**
     * Custom output converters.
     *
     * An output converter must implement Imbo\Image\OutputConverter\OutputConverterInterface.
     *
     * An output converter work similar to what an input loader does, and configures the current
     * Imagick instance to return the requested image format. If the Imagick instance is updated,
     * the plugin must call `$image->setHasBeenTransformed(true);` to tell Imbo that the content inside
     * the Imagick instance has changed.
     *
     * If your plugin returns binary data directly, call `$image->setBlob($data)` instead and
     * _don't_ call `$image->setHasBeenTransformed(true)` as you've handled the conversion to binary
     * data yourself.
     *
     * @var array<string,Imbo\Image\OutputConverter\OutputConverterInterface>|array<string,string>
     */
    'outputConverters' => [
        'basic' => BasicOutput::class,
    ],
];
