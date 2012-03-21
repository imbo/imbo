# Imbo - Image box
Imbo is an image "server" that can be used to add/get/delete images using a REST interface. There is also support for adding meta data to an image. The main idea behind Imbo is to have a place to store high quality original images and to use the REST interface to fetch variations of those images. Imbo will resize, rotate, crop (amongst other features) on the fly so you won't have to store all the different variations.

[![Current build Status](https://secure.travis-ci.org/imbo/imbo.png)](http://travis-ci.org/imbo/imbo)

## Requirements
Imbo requires [PHP-5.3](http://php.net/), the [Imagine](https://github.com/avalanche123/imagine) image manipulation library, a running [MongoDB](http://www.mongodb.org/) and the [Mongo extension for PHP](http://pecl.php.net/package/mongo).

## Installation
Since this is a work in progress there is no automatic installation. Simply clone the repository or make your own fork. Automatic installation using [PEAR](http://pear.php.net/) will be provided later.

## REST API
Imbo uses a REST API to manage the images. Each image will be identified by a public key and an MD5 sum of the file itself and the original file extension. The latter will be referred to as &lt;image&gt; for the remainder of this document.

The resources supporting `GET` also supports `HEAD` which will return only the headers.

### GET /users/&lt;publicKey&gt;/images/&lt;image&gt;

Fetch the image identified by &lt;image&gt;. Read more about applying image transformations later on.

### GET /users/&lt;publicKey&gt;/images/&lt;image&gt;/meta

Get meta data related to the image identified by &lt;image&gt;. The meta data will be JSON encoded.

### GET /users/&lt;publicKey&gt;/images

Get information about the images stored in Imbo for the user with the public key &lt;publicKey&gt;. Supported query parameters are:

* `(int) page` The page number. Defaults to 1.
* `(int) num` Number of images pr. page. Defaults to 20.
* `(boolean) metadata` Wether or not to include metadata in the output. Defaults to false ('0'). Set to '1' to enable.
* `(int) from` Fetch images starting from this unix timestamp.
* `(int) to` Fetch images up until this timestamp.

Example:

* `GET /users/<publicKey>/images?page=1&num=30&metadata=1`

### GET /users/&lt;publicKey&gt;

Fetch information about a specific user.

### PUT /users/&lt;publicKey&gt;/images/&lt;image&gt;

Place a new image on the server. The output from the server is important as the image identifier of the result image might differ from the one on the URL. This is because event listeners such as `Imbo\EventListener\MaxImageSize` changes images before they are added to the database/storage.

### POST /users/&lt;publicKey&gt;/images/&lt;image&gt;/meta

Edit the meta data attached to the image identified by &lt;image&gt;.

### DELETE /users/&lt;publicKey&gt;/images/&lt;image&gt;

Delete the image identified by &lt;image&gt; along with all meta data.

### DELETE /users/&lt;publicKey&gt;/images/&lt;image&gt;/meta

Delete the meta data attached to the image identified by &lt;image&gt;. The image is kept on the server.

## Authentication
All write operations (PUT, POST and DELETE) requires authentication using an Hash-based Message Authentication Code (HMAC). The data Imbo uses when generating this code is:

* HTTP method (PUT, POST or DELETE)
* The URL to request
* Public key (A-Z, a-z, 0-9, minimum 3 characters)
* GMT timestamp (YYYY-MM-DDTHH:MM:SSZ, for instance: 2011-02-01T14:33:03Z)

These elements are concatenated in the above order with | as a delimiter character and a hash is generated using a private key and the sha256 algorithm. The following snippet shows how this can be done using PHP:

```php
<?php
// Auth info
$publicKey  = '<some random value>';
$privateKey = '<secret value>';
$timestamp  = gmdate('Y-m-d\TH:i:s\Z');

// The identifier of the image (MD5 sum of the image file)
$imageIdentifier = '7cf1ec1233fd72896f71094548afb67a';

// The URL to the image
$url = sprintf('http://imbo.example.com/users/%s/images/%s', $publicKey, $imageIdentifier);

// The method to request with
$method = 'DELETE';

// Generate the hash
$data = $method . '|' . $url . '|' . $publicKey . '|' . $timestamp;
$signature = hash_hmac('sha256', $data, $privateKey);
$url = sprintf('%s?signature=%s&timestamp=%s',
               $url,
               rawurlencode($signature),
               rawurlencode($timestamp));
```

The above code will generate a signature that must be sent along the request using the `signature` query parameter. The timestamp used must also be provided using the `timestamp` query parameter so that the signature can be regenerated server-side. A generated signature is only valid for 5 minutes. Both the signature and the timestamp must be url encoded (by using for instance PHPs [rawurlencode](http://php.net/rawurlencode).

The public and private key pair used by clients must be specified in the server configuration. More information on the configuration file can be found later in this document.

## Image transformations
Imbo supports some image transformations out of the box using the [Imagine](https://github.com/avalanche123/Imagine/) image manipulation library.

Transformations are made using the `t[]` query parameter. This GET parameter should be used as an array so that multiple transformations can be made. The transformations are made in the order they are specified in the url.

### border
If you want to add a border around the image, use this transformation.

* `(string) color` Color in hexadecimal. Defaults to '000000' (also supports short values like 'f00' ('ff0000')).
* `(int) width` Width of the border on the left and right sides of the image. Defaults to 1.
* `(int) height` Height of the border on the top and bottoms sides of the image. Defaults to 1.

Examples:

* `t[]=border`
* `t[]=border:color=000`
* `t[]=border:color=f00,width=2,height=2`

### canvas
Adds a canvas around the image,

* `(int) width` Width of the surrounding canvas.
* `(int) height` Height of the surrounding canvas.
* `(string) mode` The placement mode of the original image. "free", "center", "center-x" and "center-y" are available values. Defaults to "free".
* `(int) x` X coordinate of the placement of the upper left corner of the existing image.
* `(int) y` Y coordinate of the placement of the upper left corner of the existing image.
* `(string) $bg` Background color of the canvas. Defaults to `null` which uses the background color of the original image.

Examples:

* `t[]=canvas:width=200,height=200,mode=center`
* `t[]=canvas:width=200,height=200,x=10,y=10,bg=000`
* `t[]=canvas:width=200,height=200,x=10,mode=center-y`
* `t[]=canvas:width=200,height=200,y=10,mode=center-x`

### compress
Compress the image on the fly.

* `(int) quality` Quality of the resulting image. 100 is maximum quality (lowest compression rate)

Example:

* `t[]=compress:quality=40`

### convert
This transformation is not applied like the rest of the transformations. It is triggered when specifying a custom extension to the `<image>`.

Example:

* `GET /users/<publicKey>/images/<image>.gif`
* `GET /users/<publicKey>/images/<image>.jpg`
* `GET /users/<publicKey>/images/<image>.png`

### crop
This transformation will crop the image. All four arguments are required.

* `(int) x` The X coordinate of the cropped region's top left corner.
* `(int) y` The Y coordinate of the cropped region's top left corner.
* `(int) width` The width of the crop.
* `(int) height` The height of the crop.

Examples:

* `t[]=crop:x=10,y=25,width=250,height=150`

### flipHorizontally
Flip the image horizontally.

Example:

* `t[]=flipHorizontally`

### flipVertically
Flip the image vertically.

Example:

* `t[]=flipVertically`

### resize
This transformation will resize the image. Two parameters are supported and at least one of them must be supplied to apply this transformation.

* `(int) width` The width of the resulting image in pixels. If not specified the width will be calculated using the same ratio as the original image.
* `(int) height` The height of the resulting image in pixels. If not specified the height will be calculated using the same ratio as the original image.

Examples:

* `t[]=resize:width=100`
* `t[]=resize:height=100`
* `t[]=resize:width=100,height=50`

### maxSize
This transformation will resize the image using the original aspect ratio. Two parameters are supported and at least one of them must be supplied to apply this transformation.
Note the difference from the resize transformation: given both height and width, the resulting image will not be the same width and height as specified unless the aspect ratio is the same.

* `(int) width` The max width of the resulting image in pixels. If not specified the width will be calculated using the same ratio as the original image.
* `(int) height` The max height of the resulting image in pixels. If not specified the height will be calculated using the same ratio as the original image.

Examples:

* `t[]=maxSize:width=100`
* `t[]=maxSize:height=100`
* `t[]=maxSize:width=100,height=50`

### rotate
Use this transformation to rotate the image.

* `(int) angle` The number of degrees to rotate the image.
* `(string) bg` Background color in hexadecimal. Defaults to '000000' (also supports short values like 'f00' ('ff0000')).

Examples:

* `t[]=rotate:angle=90`
* `t[]=rotate:angle=45,bg=fff`


### thumbnail
Transformation that creates a thumbnail of the image.

* `(int) width` Width of the thumbnail. Defaults to 50.
* `(int) height` Height of the thumbnail. Defaults to 50.
* `(string) fit` Fit style. 'inset' or 'outbound'. Default to 'outbound'.

Examples:

* `t[]=thumbnail`
* `t[]=thumbnail:width=20,height=20,fit=inset`

## Access token
All GET and HEAD requests will need to include an access token in the URL. This is enforced by an event listener (`Imbo\EventListener\AccessToken`). This token must be supplied in the URL using the `accessToken` query parameter. The value of this token is a SHA256 hash using the URL with query parameters as data and must be signed with the private key of the user. Below is an example on how to generate the hash:

```php
<?php
$publicKey  = '<some random value>'; // The public key of the client
$privateKey = '<secret value>';      // The private key of the client
$image      = '<image>';             // The image identifier

// Create the URL to an image
$url = sprintf('http://example.com/users/%s/images/%s', $publicKey, $image);

// Add some transformations
$transformations = array('t[]=thumbnail:width=40,height=40,fit=outbound',
                         't[]=border:width=3,height=3,color=red',
                         't[]=canvas:width=100,height=100,mode=center');
$query = implode('&', $transformations);

// Data for the HMAC
$url .= '?' . $query;

// Generate the token
$accessToken = hash_hmac('sha256', $url, $privateKey);

// Output the URL with the access token
echo $url . '&accessToken' . $accessToken;
```

If you requestn an URL from Imbo without an access token or if the access token is not correct Imbo will respond with `400 Bad Request`. If the event listener enforcing the token check is removed, Imbo will no longer require the `accessToken` query parameter.

## Extra response headers
Imbo will usually inject extra response headers to the different requests. All response headers from Imbo will be prefixed with **X-Imbo-**.

## Configuration
When installing Imbo you need to copy the boostrap/bootstrap.php.dist file to bootstrap/bootstrap.php and change the values to suit your needs.

### Authentication key pairs
Imbo supports several key pairs so several users can store images on your installation of Imbo. To achieve this simply specify several key pairs in the 'auth' value in the container:

```php
<?php
$container->auth = array(
    '<publicKey1>' => '<privateKey1>',
    '<publicKey2>' => '<privateKey2>',
    ...
    '<publicKeyN>' => '<privateKeyN>',
);
```

### Specify database and storage drivers
The database and storage drivers use the 'database' and 'storage' values in the container respectively. The default looks like this:

```php
<?php
// Parameters for the database driver
$dbParams = array(
    'database' => 'imbo',
    'collection' => 'images',
);

// Create the database entry
$container->database = $container->shared(function (Imbo\Container $container) use ($dbParams) {
    return new Imbo\Database\MongoDB($dbParams);
});

// Parameters for the storage driver
$storageParams = array(
    'dataDir' => '/some/path',
);

// Create the storage entry
$container->storage = $container->shared(function (Imbo\Container $container) use ($storageParams) {
    return new Imbo\Storage\Filesystem($storageParams);
});
```

which makes Imbo use MongoDB as database and the local filesystem for storage. You can implement your own drivers and use them here. Remember to implement `Imbo\Database\DatabaseInterface` and `Imbo\Storage\StorageInterface` for database drivers and storage drivers respectively.

### Event manager
Imbo comes with an event manager that can be used to inject custom code in different parts of the application. Attaching code to a given event can be done in the following way in `bootstrap/bootstrap.php`:

```php
<?php
$container->eventManager = $container->shared(function(Imbo\Container $container) {
    $manager = new Imbo\EventManager\EventManager($container->request, $container->response, $container->image);
    $manager->attach('image.get.pre', function(Imbo\EventManager\EventInterface $event) {
        $event->getResponse()->getHeaders()->set('X-Custom-Header', 'some value');
    });

    return $manager;
});
```

The above code will get triggered before all `GET` requests for specific images. Each resource triggers `pre` and `post` events for all supported HTTP methods. The following events are defined for the `image` resource:

* `image.get.pre`
* `image.get.post`
* `image.head.pre`
* `image.head.post`
* `image.delete.pre`
* `image.delete.post`
* `image.put.pre`
* `image.put.post`

Imbo includes the following resources:

* `user`
* `images`
* `image`
* `metadata`

The executable code you attach to an event will receive a single parameter, an instance of `Imbo\EventManager\EventInterface`. This interface defines the following methods:

* `getRequest()`: Returns the current request instance.
* `getResponse()`: Returns the current response instance.
* `getImage()`: Returns the current internal image instance.
* `getName()`: Returns the full name of the event that triggered your code (for instance `metadata.get.post`).

If you want to attach code to several events, you can supply an array of event names to the `attach()` method:

```php
<?php
$container->eventManager = $container->shared(function(Imbo\Container $container) {
    $manager = new Imbo\EventManager\EventManager($container->request, $container->response, $container->image);
    $manager->attach(array('image.get.pre', 'image.head.pre'), function(Imbo\EventManager\EventInterface $event) {
        // some code
    });

    return $manager;
});
```

### The EventListener interface
The event manager also has a method called `attachListener()` that takes an implementation of the `Imbo\EventListener\ListenerInterface` interface as argument. Imbo ships with some implementations of this interface.

## Developer/Contributer notes
Here you will find some notes about how Imbo works internally along with information on what is needed to develop Imbo.

* [Jenkins job](http://ci.starzinger.net/job/Imbo/)
* [API Documentation](http://ci.starzinger.net/job/Imbo/API_Documentation/)
* [Code Coverage](http://ci.starzinger.net/job/Imbo/Code_Coverage/)
* [Code Browser](http://ci.starzinger.net/job/Imbo/Code_Browser/)

Developers who want to contribute will need to do one or more of the following steps:

### Fork Imbo and checkout your fork
Click on the fork button on github and clone your fork:

    git clone git@github.com:<username>/imbo.git

### Software needed
To fully develop Imbo (as in run the complete build process, which most likely you will never do) you will need to have the following software installed:

* [PHPUnit](http://phpunit.de/)
* [vfsStream](http://code.google.com/p/bovigo/wiki/vfsStream)
* [Imagine](https://github.com/avalanche123/Imagine/)
* [MongoDB](http://www.mongodb.org/)
* [Mongo extension for PHP](http://pecl.php.net/package/mongo)

Run the following commands as root to install the software (on Ubuntu):

    pear channel-discover pear.phpunit.de
    pear channel-discover components.ez.no
    pear channel-discover pear.symfony-project.com
    pear channel-discover pear.php-tools.net
    pear channel-discover pear.netpirates.net
    pear channel-discover pear.pdepend.org
    pear channel-discover pear.phpmd.org

    pear install --alldeps phpunit/PHPUnit
    pear install --alldeps phpunit/PHP_CodeBrowser
    pear install pat/vfsStream-beta
    pear install pecl/mongo
    pear install phpDocumentor
    pear install phpunit/phploc
    pear install pdepend/PHP_Depend
    pear install phpunit/phpcpd

    apt-get install ant

For MongoDB I followed the steps on <http://www.mongodb.org/display/DOCS/Ubuntu+and+Debian+packages> when I installed it.

It's worth noting that you don't need all of the above software to just do a quick fix and send me a pull request. If you want to run the complete test suite or the whole build process you will need all of it.

If you send me a pull request I would appreciate it if you include tests for all new code as well and make sure that the test suite passes.

### Front controller
The `Imbo\FrontController` class is responsible for validating the request, and picking the correct resource class for the request. It will create an instance of the resource, execute plugins and the resource logic and then return the response.

### Resources
The following resources are defined in Imbo:

#### Imbo\Resource\Image
This resource delivers the image data.

#### Imbo\Resource\Images
This resource can be used to query Imbo for stored images.

#### Imbo\Resource\Metadata
This resource delivers the metadata associated with an image.

#### Imbo\Resource\User
This resource handles information about a single user.

### Storage drivers
Imbo supports plugable storage drivers. All storage drivers must implement the `Imbo\Storage\StorageInterface` interface.

### Database drivers
Imbo supports plugable database drivers. All database drivers must implement the `Imbo\Database\DatabaseInterface` interface.
