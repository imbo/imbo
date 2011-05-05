# PHP Image Server
PHP Image Server (**PHPIMS**) is an image "server" that can be used to add/get/delete images using a REST interface. There is also support for adding meta data to an image. The main idea behind PHPIMS is to have a place to store high quality original images and to use the REST interface to fetch variations of those images. PHPIMS will resize, rotate, crop (amongst other features) on the fly so you won't have to store all the different variations. PHPIMS comes with an administration dashboard that can be used to locate images. The dashboard will also support editing of meta data.

## Requirements
PHPIMS requires [PHP-5.3](http://php.net/), the [Imagine](https://github.com/avalanche123/imagine) image manipulation library, a running [MongoDB](http://www.mongodb.org/) and the [Mongo extension for PHP](http://pecl.php.net/package/mongo).

## Installation
Since this is a work in progress there is no automatic installation. Simply clone the repository or make your own fork. Automatic installation will be provided later.

## REST API
PHPIMS uses a REST API to manage the images. Each image will be identified by an MD5 sum of the file itself and the original file extension, that will be referred to as &lt;image&gt; for the remainder of this document.

### GET /&lt;image&gt;

Fetch the image identified by &lt;image&gt;. Read more about applying image transformations later on.

### GET /&lt;image&gt;/meta

Get meta data related to the image identified by &lt;image&gt;. The meta data will be JSON encoded.

### POST /&lt;image&gt;

Place a new image on the server along with meta data.

### POST /&lt;image&gt;/meta

Edit the meta data attached to the image identified by &lt;image&gt;.

### DELETE /&lt;image&gt;

Delete the image identified by &lt;image&gt; along with all meta data. This action is not reversible.

### DELETE /&lt;image&gt;/meta

Delete the meta data attached to the image identified by &lt;image&gt;. The image is kept on the server. This action is not reversible.

### HEAD /&lt;image&gt;

Fetches extra header information about a single image identified by &lt;image&gt;.

### HEAD /&lt;image&gt;/meta

Fetches extra header information about the meta data attached to the image identified by &lt;image&gt;.

## Authentication
All write operations (POST and DELETE) requires authentication using an Hash-based Message Authentication Code (HMAC). The data PHPIMS uses when generating this code is:

* HTTP method (POST or DELETE)
* Resource identifier (for instance <image> if your PHPIMS installation answers directly in the document root)
* Public key (random MD5 hash that exists both on the server and the client)
* GMT timestamp (YYYY-MM-DDTHH:MMZ, for instance: 2011-02-01T14:33Z)

These elements are concatenated in the above order and a hash is generated using a private key and the sha256 algorithm. The following snippet shows how this can be done using PHP:

    <?php
    $publicKey  = '<some random MD5 hash>';
    $privateKey = '<some other random MD5 hash>';
    $method     = 'DELETE';
    $resource   = 'b8533858299b04af3afc9a3713e69358.jpeg/meta'
    $timestamp  = gmdate('Y-m-d\TH:i\Z');
    
    $data       = $method . $resource . $publicKey . $timestamp;
    
    $hash       = hash_hmac('sha256', $data, $privateKey, true);
    $signature  = base64_encode($hash);
    $url        = 'http://<hostname>/b8533858299b04af3afc9a3713e69358.jpeg/meta'
                . sprintf('?signature=%s&publicKey=%s&timestamp=%s', 
                          rawurlencode($signature), 
                          $publicKey, 
                          rawurlencode($timestamp));
    
The above code will generate a signature that must be sent along the request using the `signature` query parameter. The public key and timestamp used must also be provided using the `publicKey` and `timestamp` query parameters respectively so that the signature can be regenerated server-side. A generated signature is only valid for 5 minutes. Both the signature and the timestamp must be url encoded (by using for instance PHPs [rawurlencode](http://php.net/rawurlencode).

The public and private key pair used by clients must be specified in the server configuration.

## Image transformations
PHPIMS supports some image transformations out of the box using the [Imagine](https://github.com/avalanche123/Imagine/) image manipulation library.

Transformations are made using the `t[]` query parameter. This GET parameter should be used as an array so that multiple transformations can be made. The transformations are made in the order they are specified in the url.

### resize
This transformation will resize the image. Two parameters are supported and at least one of them must be supplied to apply this transformation.

* `(int) width` The width of the resulting image in pixels. If not specified the width will be calculated using the same ratio as the original image.
* `(int) height` The height of the resulting image in pixels. If not specified the height will be calculated using the same ratio as the original image.

Examples:

* `t[]=resize:width=100`
* `t[]=resize:height=100`
* `t[]=resize:width=100,height=50`
 
### crop
This transformation will crop the image. All four arguments are required.

* `(int) x` The X coordinate of the cropped region's top left corner.
* `(int) y` The Y coordinate of the cropped region's top left corner.
* `(int) width` The width of the crop.
* `(int) height` The height of the crop.

Examples:

* `t[]=crop:x=10,y=25,width=250,height=150`

### rotate
Use this transformation to rotate the image.

* `(int) angle` The number of degrees to rotate the image.
* `(string) bg` Background color in hexadecimal. Defaults to "000000" (also supports short values like "f00" ("ff0000")).

Examples:

* `t[]=rotate:angle=90`
* `t[]=rotate:angle=45,bg=fff`

### border 
If you want to add a border around the image, use this transformation.

* `(string) color` Color in hexadecimal. Defaults to "000000" (also supports short values like "f00" ("ff0000")).
* `(int) width` Width of the border on the left and right sides of the image. Defaults to 1.
* `(int) height` Height of the border on the top and bottoms sides of the image. Defaults to 1.

Examples:

* `t[]=border`
* `t[]=border:color=000`
* `t[]=border:color=f00,width=2,height=2`

### thumbnail
Transformation that creates a thumbnail of the image.

* `(int) width` Width of the thumbnail. Defaults to 50.
* `(int) height` Height of the thumbnail. Defaults to 50.
* `(int) fit` Fit style. 'inset' or 'outbound'. Default to 'outbound'.

Examples:

* `t[]=thumbnail`
* `t[]=thumbnail:width=20,height=20,fit=inset`

### flipHorizontally
Flip the image horizontally.

Example:

* `t[]=flipHorizontally`

### flipVertically
Flip the image vertically.

Example:

* `t[]=flipVertically`

## Extra response headers
PHPIMS will usually inject extra response headers to the different requests. All response headers from PHPIMS will be prefixed with **X-PHPIMS-**.

## PHP client
A PHP client is included in PHPIMS that supports all the REST methods and includes some convenience methods. The client requires the URL to the PHPIMS server as an argument to the constructor.

### Add an image

    <?php
    require 'PHPIMS/Autoload.php';
    
    $client = new PHPIMS\Client('http://<hostname>');

    // Path to local image    
    $path = '/path/to/image.png';
    
    // Add some meta data to the image
    $metadata = array(
        'foo' => 'bar', 
        'bar' => 'foo',
    );
    
    $response = $client->addImage($path, $metadata);
    
### Get meta data

    <?php
    require 'PHPIMS/Autoload.php';

    $client = new PHPIMS\Client('http://<hostname>');
    
    $hash = '<hash>';
    $response = $client->getMetadata($hash);

### Delete an image

    <?php
    require 'PHPIMS/Autoload.php';

    $client = new PHPIMS\Client('http://<hostname>');
    
    $hash = '<hash>';
    $response = $client->deleteImage($hash);
    
### Delete all meta data attached to an image

    <?php
    require 'PHPIMS/Autoload.php';

    $client = new PHPIMS\Client('http://<hostname>');
    
    $hash = '<hash>';
    $response = $client->deleteMetadata($hash);
    
### Get image url

    <?php
    require 'PHPIMS/Autoload.php';

    $client = new PHPIMS\Client('http://<hostname>');
    
    $hash = '<hash>';
    $url = $client->getImageUrl($hash);
    
The `getImageUrl` returns an instance of `PHPIMS\Client\ImageUrl` which, when used in string context, represents an url to an image. 

### Get image url with transformations

    <?php
    require 'PHPIMS/Autoload.php';

    $client = new PHPIMS\Client('http://<hostname>');
    
    $hash = '<hash>';
    $chain = new PHPIMS\Image\TransformationChain();
    $chain->border()->resize(200)->rotate(45);
                   
    $url = $client->getImageUrl($hash, $chain);
    
    // OR
    
    $hash = '<hash>';
    $url = $client->getImageUrl($hash);
    
    $chain = new PHPIMS\Image\TransformationChain();
    $chain->border()->resize(200)->rotate(45)->applyToImageUrl($url);
    
    // OR
    
    $hash = '<hash>';
    $url = $client->getImageUrl($hash);
    
    $chain = new PHPIMS\Image\TransformationChain();
    $chain->add(new PHPIMS\Image\Transformation\Border());
          ->add(new PHPIMS\Image\Transformation\Resize(200));
          ->add(new PHPIMS\Image\Transformation\Rotate(45))
          ->applyToImageUrl($url);
    
    // OR
    
    $hash = '<hash>';
    $url = $client->getImageUrl($hash);
    $transformation = new PHPIMS\Image\Transformation\Border();
    $chain = new PHPIMS\Image\TransformationChain();
    $chain->transformImageUrl($url, $$transformation);
    
    // OR
    
    $hash = '<hash>';
    $url = $client->getImageUrl($hash);
    $chain = new PHPIMS\Image\TransformationChain();
    $chain->thumbnail()->border();
    $url->transform($chain);

#### Image transformations
The `PHPIMS\Image\TransformationChain` class can be used to stack image manipulations. The following transformations can be added to an instance of the `PHPIMS\Image\TransformationChain` class:

* `border(string $color = null, int $width = null, int $height = null)` 
* `crop(int $x, int $y, int $width, int $height)` 
* `rotate(int $angle, string $bg = null)` 
* `resize(int $width = null, int $height = null)` 
* `thumbnail($width = null, $height = null, $fit = null)` 
* `flipHorizontally()` 
* `flipVertically()` 

These methods can be chained. They can also be added using the following method:

* `add(PHPIMS\Image\TransformationInterface $transformation)`

The following transformations are implemented using the same parameters as above in the constructors:

* `PHPIMS\Image\Transformation\Border`
* `PHPIMS\Image\Transformation\Crop`
* `PHPIMS\Image\Transformation\Resize`
* `PHPIMS\Image\Transformation\Rotate`
* `PHPIMS\Image\Transformation\Thumbnail`
* `PHPIMS\Image\Transformation\FlipHorizontally`
* `PHPIMS\Image\Transformation\FlipVertically`

### Client response object    
All client methods returns an instance of `PHPIMS\Client\Response` (with the exception of `getImageUrl` which returns an instance of `PHPIMS\Client\ImageUrl`). In this instance you will find information on the request that was made. 

The response instance includes all response headers and the body, and has the following methods:

* `array getHeaders(void)` Get all response headers 
* `array asArray(void)` Get the body as a native PHP array instead of a JSON-encoded string
* `stdClass asObject(void)` Get the body as an instance of stdClass instead of a JSON-encoded string
* `boolean isSuccess(void)` Wether or not the response was a success (true if the HTTP status code is in the 2xx range) 
* `int getStatusCode(void)` Get the status code

## Developer/Contributer notes
Here you will find some notes about how PHPIMS works internally along with information on what is needed to develop PHPIMS.

* [Jenkins job](http://ci.starzinger.net/job/PHPIMS/)
* [API Documentation](http://ci.starzinger.net/job/PHPIMS/API_Documentation/)
* [Code Coverage](http://ci.starzinger.net/job/PHPIMS/Code_Coverage/)
* [Code Browser](http://ci.starzinger.net/job/PHPIMS/Code_Browser/)

Developers who want to contribute will need to do one or more of the following steps:

### Fork PHPIMS and checkout your fork
Click on the fork button on github and clone your fork:

    git clone git@github.com:<username>/phpims.git

### Software needed
To fully develop PHPIMS (as in run the complete build process, which most likely you will never do) you will need to have the following software installed:

* [PHPUnit](http://phpunit.de/)
* [Autoload (phpab)](https://github.com/theseer/Autoload)
* [Mockery](https://github.com/padraic/mockery)
* [vfsStream](http://code.google.com/p/bovigo/wiki/vfsStream)
* [Imagine](https://github.com/avalanche123/Imagine/)
* [MongoDB](http://www.mongodb.org/)
* [Mongo extension for PHP](http://pecl.php.net/package/mongo)

Run the following commands as root to install the software (on Ubuntu):

    pear channel-discover pear.phpunit.de
    pear channel-discover components.ez.no
    pear channel-discover pear.symfony-project.com
    pear channel-discover pear.survivethedeepend.com
    pear channel-discover hamcrest.googlecode.com/svn/pear
    pear channel-discover pear.php-tools.net
    pear channel-discover pear.netpirates.net
    pear channel-discover pear.pdepend.org 
    pear channel-discover pear.phpmd.org
    
    pear install --alldeps phpunit/PHPUnit
    pear install --alldeps phpunit/PHP_CodeBrowser
    pear install --alldeps deepend/Mockery
    pear install --alldeps hamcrest/Hamcrest
    pear install pat/vfsStream-beta
    pear install theseer/Autoload
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
The `PHPIMS\FrontController` class is responsible for validating the request, and picking the correct operation class for the request. It will create an instance of the operation, execute the operation and then return the response.

### Operations
The combination of the HTTP method (GET, POST, DELETE, HEAD) and the URL decides which operation to use. All operations extend the base `PHPIMS\Operation` class, and implements a main `exec()` method. The `exec()` method typically calls methods in the current database and storage drivers.

An `exec()` method for an operation can for instance look like this:

    public function exec() {
        $image = $this->getImage();

        $this->getStorage()->load($this->getHash(), $image);
        $this->getResponse()->setImage($image);

        return $this;
    }
    
Here we fetch the image, and calls the `load()` method in the storage driver and supplies the current hash and current image object that the driver can work with. Then it adds the image to the response object and returns itself. The above example is from the `PHPIMS\Operation\GetImage` operation.     

### Operation plugins
Plugins contain extra features for the different operations. Plugins can hook in before and/or after the current operation executes its `exec()` method. All plugins must implement the `PHPIMS\Operation\PluginInterface` interface. If plugins want to change the current image or the response objects they can fetch these via the operation instance passed to the `exec()` method:

    public function exec(PHPIMS\Operation $operation) {
        $image = $operation->getImage(); // Fetches the current PHPIMS\Image object
        $response = $operation->getResponse(); // Fetches the current PHPIMS\Server\Response object
    }
    
Each plugin must also specify which operations it wants to hook into along with a priority so that plugins can be executed in a specific order. This is done via the `static public $events` property. Consider the `PHPIMS\Operation\Plugin\ManipulateImage` plugin:

    static public $events = array(
        'getImagePostExec' => 101,
    );
    
This plugin will run after the `PHPIMS\Operation\GetImage::exec()` has finished executing. It will run with an index of 101 meaning that 100 plugins can be executed prior to this one for this specific hook. Internal plugins starts with an index of 100. The name of the operation specified in the array is the last part of the operation class name along with "PostExec" or "PreExec". Here are the different names for all operations:

<table>
<tr><th>Operation class</th><th>Pre exec trigger</th><th>Post exec trigger</th></tr>
<tr><td>PHPIMS\Operation\AddImage</td><td>addImagePreExec</td><td>addImagePostExec</td></tr>
<tr><td>PHPIMS\Operation\DeleteImage</td><td>deleteImagePreExec</td><td>deleteImagePostExec</td></tr>
<tr><td>PHPIMS\Operation\DeleteImageMetadata</td><td>deleteImageMetadataPreExec</td><td>deleteImageMetadataPostExec</td></tr>
<tr><td>PHPIMS\Operation\EditImageMetadata</td><td>editImageMetadataPreExec</td><td>editImageMetadataPostExec</td></tr>
<tr><td>PHPIMS\Operation\GetImage</td><td>getImagePreExec</td><td>getImagePostExec</td></tr>
<tr><td>PHPIMS\Operation\GetImages</td><td>getImagesPreExec</td><td>getImagesPostExec</td></tr>
<tr><td>PHPIMS\Operation\GetImageMetadata</td><td>getImageMetadataPreExec</td><td>getImageMetadataPostExec</td></tr>
<tr><td>PHPIMS\Operation\HeadImage</td><td>headImagePreExec</td><td>headImagePostExec</td></tr>
</table>

It's important to notice that one request to PHPIMS triggers one operation. A single request can not trigger several operations.    

### Storage drivers
PHPIMS supports plugable storage drivers. All storage drivers must implement the `PHPIMS\Storage\DriverInterface` interface. 

### Database drivers
PHPIMS supports plugable database drivers. All database drivers must implement the `PHPIMS\Database\DriverInterface` interface.

### Configuration
When installing PHPIMS you need to copy the config/server.php.dist file to config/server.php and change the values so they suit your needs.

#### Specify database and storage drivers
The database and storage drivers use the 'database' and 'storage' elements in the configuration array respectively. The default looks like this:

    // Database driver
    'database' => new PHPIMS\Database\Driver\MongoDB(array(
        'database'   => 'phpims',
        'collection' => 'images',
    )),

    // Storage driver
    'storage' => new PHPIMS\Storage\Driver\Filesystem(array(
        'dataDir' => realpath('/some/path'),
    )),
    
which makes PHPIMS use MongoDB as database and the local filesystem for storage. You can implement your own drivers and use them here. Remember to implement `PHPIMS\Database\DriverInterface` and `PHPIMS\Storage\DriverInterface` for database drivers and storage drivers respectively. 