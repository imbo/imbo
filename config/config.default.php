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
     * 1) An instance of the Imbo\EventListener\ListenerInterface interface
     *
     * 2) A closure returning an instance of the Imbo\EventListener\ListenerInterface interface
     *
     * 3) An array with the following keys:
     *
     *   - listener
     *   - publicKeys
     *
     *   where 'listener' is an instance of the Imbo\EventListener\ListenerInterface interface or a
     *   closure returning an instance of said interface, and 'publicKeys' is an array with one of
     *   the following keys:
     *
     *     - include
     *     - exclude
     *
     *     where 'include' is an array of public keys that the listener *will* trigger for, and
     *     'exclude' is an array of public keys that the listener *will not* trigger for.
     *
     * 4) An array with the following keys:
     *
     *   - events (required)
     *   - callback (required)
     *   - priority
     *   - publicKeys
     *
     *   where 'events' is an array of events that 'callback' will subscribe to. If you have a
     *   single callback that subscribes to several events, and you want to use different priorities
     *   for the events, simply specify an associative array where the keys are the event names, and
     *   the values are the priorities. If you use this method, the 'priority' key will be ignored.
     *
     *   'callback' is any callable function. The function will receive a single argument, which is
     *   an instance of Imbo\EventManager\EventInterface.
     *
     *   'priority' is the priority of your callback. This defaults to 1 (low priority). The
     *   priority can also be a negative number if you want your listeners to be triggered after
     *   Imbo's event listeners.
     *
     *   'publicKeys' is the same as described above.
     *
     * Example of how to add listeners:
     *
     * 'eventListeners' => array(
     *   // 1) Implementation of a listener interface
     *   'auth' => new EventListener\Authenticate(),
     *
     *   // 2) Implementation of a listener interface with a public key filter
     *   'maxImageSize' => array(
     *     'listener' => EventListener\MaxImageSize(1024, 768),
     *     'publicKeys' => array(
     *       'include' => array( ... ),
     *       // 'exclude' => array( ... ),
     *       )
     *     )
     *   ),
     *
     *   // 3) A closure that will subscribe to two events using one priority
     *   'someCustomCallback' => array(
     *     'callback' => function($event) {
     *       // Some code
     *     },
     *     'events' => array(
     *       'image.get', 'image.post'
     *     ),
     *     'priority' => 20,
     *     'publicKeys' => array(
     *       'include' => array( ... ),
     *       // 'exclude' => array( ... ),
     *     )
     *   ),
     *
     *   // 4) A closure that will subscribe to two events with different priorities
     *   'anotherCustomCallback' => array(
     *     'callback' => function($event) {
     *       // Some code
     *     },
     *     'events' => array(
     *       'image.get' => 20, // Trigger BEFORE the internal handler for "image.get"
     *       'image.post' => -20, // Trigger AFTER the internal handler for "image.post"
     *     ),
     *   ),
     * ),
     *
     * @var array
     */
    'eventListeners' => array(
        'accessToken' => function() {
            return new EventListener\AccessToken();
        },
        'auth' => function() {
            return new EventListener\Authenticate();
        },
        'statsAccess' => function() {
            return new EventListener\StatsAccess(array(
                'whitelist' => array('127.0.0.1', '::1'),
                'blacklist' => array(),
            ));
        },
    ),

    /**
     * Image transformations
     *
     * This array includes all supported image transformations. The keys are the names of the
     * transformations that is used in the URL, and the values are closures that will receive a
     * single parameter: $params, which is the parameters found in the URL associated with the
     * transformation.
     *
     * Example:
     *
     * t[]=border:width=2,height=3
     *
     * will end up doing a new Image\Transformation\Border(array('width' => 2, 'height' => 3))
     *
     * All closures must return an instance of the Imbo\Image\Transformation\TransformationInterface
     * interface or a callable piece of code, that in turn will receive a single parameter:
     *
     * Imbo\Model\Image $image
     *
     * which is the image you want your transformation to modify.
     *
     * All image transformations shipped by Imbo uses imagick, and if you want to use something
     * else, simply supply your own classes in the array below.
     *
     * @var array
     */
    'imageTransformations' => array(
        'border' => function (array $params) {
            return new Image\Transformation\Border($params);
        },
        'canvas' => function (array $params) {
            return new Image\Transformation\Canvas($params);
        },
        'compress' => function (array $params) {
            return new Image\Transformation\Compress($params);
        },
        'crop' => function (array $params) {
            return new Image\Transformation\Crop($params);
        },
        'desaturate' => function (array $params) {
            return new Image\Transformation\Desaturate();
        },
        'flipHorizontally' => function (array $params) {
            return new Image\Transformation\FlipHorizontally();
        },
        'flipVertically' => function (array $params) {
            return new Image\Transformation\FlipVertically();
        },
        'maxSize' => function (array $params) {
            return new Image\Transformation\MaxSize($params);
        },
        'resize' => function (array $params) {
            return new Image\Transformation\Resize($params);
        },
        'rotate' => function (array $params) {
            return new Image\Transformation\Rotate($params);
        },
        'sepia' => function (array $params) {
            return new Image\Transformation\Sepia($params);
        },
        'thumbnail' => function (array $params) {
            return new Image\Transformation\Thumbnail($params);
        },
        'transpose' => function (array $params) {
            return new Image\Transformation\Transpose();
        },
        'transverse' => function (array $params) {
            return new Image\Transformation\Transverse();
        },
        'watermark' => function (array $params) {
            return new Image\Transformation\Watermark($params);
        },
    ),


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

if (file_exists(__DIR__ . '/../../../../config/config.php')) {
    // Someone has installed Imbo via a custom composer.json, so the custom config is outside of
    // the vendor dir
    $config = array_replace_recursive($config, require __DIR__ . '/../../../../config/config.php');
} else if (file_exists(__DIR__ . '/config.php')) {
    $config = array_replace_recursive($config, require __DIR__ . '/config.php');
}

return $config;
