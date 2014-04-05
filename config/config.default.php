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

$config = array(
    /**
     * Authentication
     *
     * This value must be set to an array with key => value pairs mapping to public and private keys
     * of the users of this installation. The public keys must match the following case sensitive
     * expression:
     *
     * ^[a-z0-9_-]{3,}$
     *
     * @var array
     */
    'auth' => array(),

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
     * 'eventListeners' => array(
     *   // 1) A class name in a string
     *   'accessToken' => 'Imbo\EventListener\ListenerInterface',
     *
     *   // 2) Implementation of a listener interface
     *   'auth' => new EventListener\Authenticate(),
     *
     *   // 3) Implementation of a listener interface with a public key filter
     *   'maxImageSize' => array(
     *     'listener' => EventListener\MaxImageSize(1024, 768),
     *     'publicKeys' => array(
     *       'whitelist' => array( ... ),
     *       // 'blacklist' => array( ... ),
     *       )
     *     )
     *   ),
     *
     *   // 4) A class name in a string with custom parameters for the listener
     *   'statsAccess' => array(
     *       'listener' => 'Imbo\EventListener\StatsAccess',
     *       'params' => array(
     *           'allow' => array('127.0.0.1', '::1'),
     *       ),
     *   ),
     *
     *   // 5) A closure that will subscribe to two events with different priorities
     *   'anotherCustomCallback' => array(
     *       'callback' => function($event) {
     *           // Some code
     *       },
     *       'events' => array(
     *           'image.get' => 20, // Trigger BEFORE the internal handler for "image.get"
     *           'image.post' => -20, // Trigger AFTER the internal handler for "image.post"
     *       ),
     *   ),
     *
     * @var array
     */
    'eventListeners' => array(
        'accessToken' => 'Imbo\EventListener\AccessToken',
        'auth' => 'Imbo\EventListener\Authenticate',
        'statsAccess' => array(
            'listener' => 'Imbo\EventListener\StatsAccess',
            'params' => array(
                'allow' => array('127.0.0.1', '::1'),
            ),
        ),

        // Image transformations
        'autoRotate' => 'Imbo\Image\Transformation\AutoRotate',
        'border' => 'Imbo\Image\Transformation\Border',
        'canvas' => 'Imbo\Image\Transformation\Canvas',
        'compress' => 'Imbo\Image\Transformation\Compress',
        'convert' => 'Imbo\Image\Transformation\Convert',
        'crop' => 'Imbo\Image\Transformation\Crop',
        'desaturate' => 'Imbo\Image\Transformation\Desaturate',
        'flipHorizontally' => 'Imbo\Image\Transformation\FlipHorizontally',
        'flipVertically' => 'Imbo\Image\Transformation\FlipVertically',
        'histogram' => 'Imbo\Image\Transformation\Histogram',
        'maxSize' => 'Imbo\Image\Transformation\MaxSize',
        'modulate' => 'Imbo\Image\Transformation\Modulate',
        'progressive' => 'Imbo\Image\Transformation\Progressive',
        'resize' => 'Imbo\Image\Transformation\Resize',
        'rotate' => 'Imbo\Image\Transformation\Rotate',
        'sepia' => 'Imbo\Image\Transformation\Sepia',
        'strip' => 'Imbo\Image\Transformation\Strip',
        'thumbnail' => 'Imbo\Image\Transformation\Thumbnail',
        'transpose' => 'Imbo\Image\Transformation\Transpose',
        'transverse' => 'Imbo\Image\Transformation\Transverse',
        'watermark' => 'Imbo\Image\Transformation\Watermark',

        // Imagick-specific event listener for the built in image transformations
        'imagick' => 'Imbo\EventListener\Imagick',
    ),

    /**
     * Initializers for event listeners
     *
     * If some of your event handlers requires extra initializing you can create initializer
     * classes. These classes must implement the Imbo\EventListener\Initializer\InitializerInterface
     * interface, and will be instantiated by Imbo.
     *
     * @var array
     */
    'eventListenerInitializers' => array(
        'imagick' => 'Imbo\EventListener\Initializer\Imagick',
    ),

    /**
     * Transformation presets
     *
     * If you want to make custom transformation presets (or transformation collections) you can do
     * so here. The keys used will be the name of the transformation as used in the URI, and the
     * value is an array containing the names of the transformations in the collection.
     *
     * Example:
     *
     * 'transformationPresets' => array(
     *     'graythumb' => array(
     *         'thumbnail',
     *         'desaturate',
     *     ),
     *     'flipflop' => array(
     *         'flipHorizontally',
     *         'flipVertically',
     *     ),
     * ),
     *
     * The above to examples can be triggered by ?t[]=graythumb and ?t[]=flipflop respectively
     *
     * @var array
     */
    'transformationPresets' => array(),

    /**
     * Custom resources for Imbo
     *
     * @link http://docs.imbo-project.org
     * @var array
     */
    'resources' => array(),

    /**
     * Custom routes for Imbo
     *
     * @link http://docs.imbo-project.org
     * @var array
     */
    'routes' => array(),
);

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
