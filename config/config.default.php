<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace Imbo;

// Require composer autoloader
if (is_file(__DIR__ . '/../../../autoload.php')) {
    // Someone has installed Imbo via a custom composer.json, so the Imbo installation is inside a
    // vendor dir
    require __DIR__ . '/../../../autoload.php';
} else {
    // Someone has installed Imbo via a simple "git clone"
    require __DIR__ . '/../vendor/autoload.php';
}

$config = [
    /**
     * Authentication
     *
     * This value must be set to either:
     * 1) An array mapping public and private keys of the users of this installation.
     *    The array can take two different forms:
     *
     * 'auth' => [
     *     '<publicKey>' => '<read+write private key>',
     *     '<differentPublicKey>' => '<different read+write private key>'
     * ]
     *
     * Or:
     * 'auth' => [
     *     '<publicKey>' => [
     *         'ro' => '<read-only private key>',
     *         'rw' => '<read-write private key>',
     *     ]
     * ]
     *
     * You can also specify multiple private keys for both read-only and read+write:
     *
     * 'auth' => [
     *     '<publicKey>' => [
     *         'ro' => ['<readKey1>', '<readKey2>'],
     *         'rw' => ['<readWriteKey1>', '<readWriteKey2>']
     *     ]
     * ]
     *
     * 2) An instance of the Imbo\Auth\UserLookupInterface interface.
     *
     * Public keys must match the following case sensitive regular expression:
     *
     * ^[a-z0-9_-]{3,}$
     *
     * @var array|Auth\UserLookupInterface
     */
    'auth' => [],

    'accessControl' => function() {
        return new Auth\AccessControl\ArrayAdapter();
    },

    /**
     * Database adapter
     *
     * See the different adapter implementations for possible configuration parameters. The value
     * must be set to a closure returning an instance of Imbo\Database\DatabaseInterface, or an
     * implementation of said interface.
     *
     * @var Imbo\Database\DatabaseInterface|Closure
     */
    'database' => function() {
        return new Database\MongoDB();
    },

    /**
     * Storage adapter
     *
     * See the different adapter implementations for possible configuration parameters. The value
     * must be set to a closure returning an instance of Imbo\Storage\StorageInterface, or an
     * implementation of said interface.
     *
     * @var Imbo\Storage\StorageInterface|Closure
     */
    'storage' => function() {
        return new Storage\GridFS();
    },

    /**
     * Whether to content negotiate images or not. If set to true, Imbo will try to find a
     * suitable image format based on the Accept-header received. If set to false, it will
     * deliver the image in the format it was originally added as. Note that this does not
     * affect images requested with a specific extension (.jpg/.png/.gif etc).
     *
     * @var boolean
     */
    'contentNegotiateImages' => true,

    /**
     * Event listeners
     *
     * An associative array where the keys are short names for the event listeners (not really used
     * for anything, but exists so you can override/unset some helpers from config.php). The values
     * of each element in this array can be one of the following:
     *
     * 1) A string representing a class name of a class implementing the
     *    Imbo\EventListener\ListenerInteface interface
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
        'accessToken' => 'Imbo\EventListener\AccessToken',
        'auth' => 'Imbo\EventListener\Authenticate',
        'statsAccess' => [
            'listener' => 'Imbo\EventListener\StatsAccess',
            'params' => [
                'allow' => ['127.0.0.1', '::1'],
            ],
        ],

        // Image transformations
        'autoRotate' => 'Imbo\Image\Transformation\AutoRotate',
        'border' => 'Imbo\Image\Transformation\Border',
        'canvas' => 'Imbo\Image\Transformation\Canvas',
        'compress' => 'Imbo\Image\Transformation\Compress',
        'contrast' => 'Imbo\Image\Transformation\Contrast',
        'convert' => 'Imbo\Image\Transformation\Convert',
        'crop' => 'Imbo\Image\Transformation\Crop',
        'desaturate' => 'Imbo\Image\Transformation\Desaturate',
        'flipHorizontally' => 'Imbo\Image\Transformation\FlipHorizontally',
        'flipVertically' => 'Imbo\Image\Transformation\FlipVertically',
        'histogram' => 'Imbo\Image\Transformation\Histogram',
        'level' => 'Imbo\Image\Transformation\Level',
        'maxSize' => 'Imbo\Image\Transformation\MaxSize',
        'modulate' => 'Imbo\Image\Transformation\Modulate',
        'progressive' => 'Imbo\Image\Transformation\Progressive',
        'resize' => 'Imbo\Image\Transformation\Resize',
        'rotate' => 'Imbo\Image\Transformation\Rotate',
        'sepia' => 'Imbo\Image\Transformation\Sepia',
        'sharpen' => 'Imbo\Image\Transformation\Sharpen',
        'strip' => 'Imbo\Image\Transformation\Strip',
        'thumbnail' => 'Imbo\Image\Transformation\Thumbnail',
        'transpose' => 'Imbo\Image\Transformation\Transpose',
        'transverse' => 'Imbo\Image\Transformation\Transverse',
        'vignette' => 'Imbo\Image\Transformation\Vignette',
        'watermark' => 'Imbo\Image\Transformation\Watermark',

        // Imagick-specific event listener for the built in image transformations
        'imagick' => 'Imbo\EventListener\Imagick',
    ],

    /**
     * Initializers for event listeners
     *
     * If some of your event handlers requires extra initializing you can create initializer
     * classes. These classes must implement the Imbo\EventListener\Initializer\InitializerInterface
     * interface, and will be instantiated by Imbo.
     *
     * @var array
     */
    'eventListenerInitializers' => [
        'imagick' => 'Imbo\EventListener\Initializer\Imagick',
    ],

    /**
     * Transformation presets
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
     * Custom resources for Imbo
     *
     * @link http://docs.imbo-project.org
     * @var array
     */
    'resources' => [],

    /**
     * Custom routes for Imbo
     *
     * @link http://docs.imbo-project.org
     * @var array
     */
    'routes' => [],

    /**
     * Trusted proxies
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
];

// See if a custom config path has been defined. If so, don't require the custom one as this is
// most likely a Behat test run
if (!defined('IMBO_CONFIG_PATH')) {
    if (is_dir(__DIR__ . '/../../../../config')) {
        // Someone has installed Imbo via a custom composer.json, so the custom config is outside of
        // the vendor dir. Loop through all available php files in the config dir
        foreach (glob(__DIR__ . '/../../../../config/*.php') as $file) {
            $extraConfig = require $file;

            if (!is_array($extraConfig)) {
                continue;
            }

            $config = array_replace_recursive($config, $extraConfig);
        }
    } else if (file_exists(__DIR__ . '/config.php')) {
        $config = array_replace_recursive($config, require __DIR__ . '/config.php');
    }
}

return $config;
