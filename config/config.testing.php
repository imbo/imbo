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
require __DIR__ . '/../vendor/autoload.php';

return array(
    'auth' => array('publickey' => 'privatekey'),

    'database' => function() {
        return new Database\MongoDB(array(
            'databaseName' => 'imbo_testing',
        ));
    },

    'storage' => function() {
        return new Storage\GridFS(array(
            'databaseName' => 'imbo_testing',
        ));
    },

    'eventListeners' => array(
        'auth' => function() {
            return new EventListener\Authenticate();
        },
        'accessToken' => function() {
            return new EventListener\AccessToken();
        }
    ),

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
        'convert' => function (array $params) {
            return new Image\Transformation\Convert($params);
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
    ),
);
