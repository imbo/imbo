# PHP Image Server
PHP Image Server (**PHPIMS**) is an image "server" that can be used to add/get/delete images using a REST interface. There is also support for adding metadata to an image. The main idea behind PHPIMS is to have a place to store high quality original images and to use the REST interface to fetch variations of those images. PHPIMS will resize, rotate, crop, switch formats (amongst other features) on the fly so you won't have to store all the different variations. PHPIMS comes with an administration dashboard that can be used to locate images. The dashboard will also support editing of metadata.

## Installation
Since this is a work in progress there is no automatic installation. Developers who want to contribute will need to do the following steps:

### Fork PHPIMS and checkout your fork
Click on the fork button on github and clone your fork:

    git clone git@github.com:<username>/phpims.git

### Software needed
To fully develop PHPIMS you will need to have the following software installed:

* [PHPUnit](http://phpunit.de/)
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
    
    pear install --alldeps phpunit/PHPUnit
    pear install --alldeps deepend/Mockery
    pear install --alldeps hamcrest/Hamcrest
    pear install pat/vfsStream-beta
    apt-get install mongodb
    pear install pecl/mongo

## REST API
PHPIMS uses a REST API to manage the images. Each image will be identified by an MD5 sum of the image itself and the original file extension, that will be referred to as &lt;image&gt; for the remainder of this document.

### GET /&lt;image&gt;

Fetch the image identified by &lt;image&gt;. The following query parameters are supported:

* `(int) width` Width of the image in pixels.
* `(int) height` Height of the image in pixels.

If no options are specified the original image will be returned.

### GET /&lt;image&gt;/meta

Get metadata related to the image identified by &lt;image&gt;. The metadata will be JSON encoded.

### POST /&lt;image&gt;

Place a new image on the server along with metadata.

### POST /&lt;image&gt;/meta

Edit the metadata attached to the image identified by &lt;image&gt;.

### DELETE /&lt;image&gt;

Delete the image identified by &lt;image&gt; along with all metadata. This action is not reversable.

### DELETE /&lt;image&gt;/meta

Delete the metadata attached to the image identified by &lt;image&gt;. The image is kept on the server. This action is not reversable.

### HEAD /&lt;image&gt;

Fetches extra header information about a single image identified by &lt;image&gt;.

### HEAD /&lt;image&gt;/meta

Fetches extra header information about the metadata attached to the image identified by &lt;image&gt;. 

## Extra response headers
PHPIMS will usually inject extra response headers to the different requests. All response headers from PHPIMS will be prefixed with **X-PHPIMS-**.

## PHP client
A PHP client is included in PHPIMS that supports all the REST methods and includes some convenience methods. The client requires the URL to the PHPIMS server as an argument to the constructor.

### Add an image

    <?php
    require 'PHPIMS/Autoload.php';

    $client = new PHPIMS_Client('http://<hostname>');

    // Path to local image    
    $path = '/path/to/image.png';
    
    // Add some metadata to the image
    $metadata = array(
        'foo' => 'bar', 
        'bar' => 'foo',
    );
    
    $response = $client->addImage($path, $metadata);
    
### Get metadata

    <?php
    require 'PHPIMS/Autoload.php';

    $client = new PHPIMS_Client('http://<hostname>');
    
    $hash = '<hash>';
    $response = $client->getMetadata($hash);

### Delete an image

    <?php
    require 'PHPIMS/Autoload.php';

    $client = new PHPIMS_Client('http://<hostname>');
    
    $hash = '<hash>';
    $response = $client->deleteImage($hash);
    
### Delete all metadata attached to an image

    <?php
    require 'PHPIMS/Autoload.php';

    $client = new PHPIMS_Client('http://<hostname>');
    
    $hash = '<hash>';
    $response = $client->deleteMetadata($hash);

### Client response object    
All client methods returns an instance of `PHPIMS_Client_Response`. In this instance you will find information on the request that was made. 

The response instance includes all response headers and the body, and has the following methods:

* `array` `getHeaders(void)` Get all response headers 
* `array` `asArray(void)` Get the body as a native PHP array instead of a JSON-encoded string
* `stdClass` `asObject(void)` Get the body as an instance of stdClass instead of a JSON-encoded string
* `boolean` `isSuccess(void)` Wether or not the response was a success (true if the HTTP status code is in the 2xx range) 
* `int` `getStatusCode(void)` Get the status code

## Developer notes
Here you will find some notes about how PHPIMS works internally.

### Front controller
The front controller is responsible for validating the request, and picking the correct operation class for the request.
### Operations
The combination of the HTTP method (GET, POST, DELETE, HEAD) and the URL decides which operation to use. All operations extend the base `PHPIMS_Operation_Abstract` class, and implements a main `exec()` method. The `exec()` method typically calls methods in the current database and storage drivers.
### Operation plugins
Plugins contain extra features for the different operations. Plugins can hook in before and/or after the current operation executes its `exec()` method. All plugins must extend the base `PHPIMS_Operation_Plugin_Abstract` class, and must implement an `exec()` method that takes the operation as argument. If plugins want to change the current image or the response object they can fetch these via the operation instance:

    public function exec(PHPIMS_Operation_Abstract $operation) {
        $image = $operation->getImage(); // Fetches the current PHPIMS_Image object
        $response = $operation->getResponse(); // Fetches the current PHPIMS_Server_Response object
    }
    
Each plugin must also specify which operations it wants to hook into along with a priority so that plugins can be executed in a special order. This is done via the `static public $events` property. Consider the ManipulateImage plugin:

    static public $events = array(
        'getImagePostExec' => 101,
    );
    
This plugin will run after the `PHPIMS_Operation_GetImage::exec()` has finished executing. It will run with an index of 101 meaning that 100 plugins can be executed prior to this one for this specific hook. Internal plugins starts with an index of 100, and custom plugins given the index of 1 is the first to be executed. If you want a plugin to run before the GetImage operation, and after DeleteImage and DeleteMetadata the `$events` array can look like:

    static public $events = array(
        'getImagePreExec' => 1,
        'deleteImagePostExec' => 10,
        'deleteMetadataPostExec' => 10,
    );
    
Whenever the GetImage operation is triggered this plugin will be executed before any other plugin *before* the operation executes `exec()` and it will run with index 20 *after* DeleteImage is finished, and with an index of 20 *after* DeleteMetadata is finished. It's important to notice that one request to PHPIMS triggers one operation. A single request can not trigger many operations.    

### Storage drivers
PHPIMS supports plugable storage drivers. All storage drivers must extend the base `PHPIMS_Storage_Driver_Abstract` class (which implements `PHPIMS_Storage_Driver_Interface`. 

### Database drivers
PHPIMS supports plugable database drivers. All database drivers must extend the base `PHPIMS_Database_Driver_Abstract` class (which implements `PHPIMS_Database_Driver_Interface`.

### Configuration
When installing PHPIMS you need to copy the config/server.php.dist file to config/server.php and change the values so they suit your needs.

#### Specify database and storage drivers
The database and storage drivers use the 'database' and 'storage' elements in the configuration array respectively. The default looks like this:

    // Configuration for the database driver
    'database' => array(
        'driver' => 'PHPIMS_Database_Driver_MongoDB',
        'params' => array(
            'database'   => 'phpims',
            'collection' => 'images',
        ),
    ),

    // Configuration for the storage driver
    'storage' => array(
        'driver' => 'PHPIMS_Storage_Driver_Filesystem',
        'params' => array(
            'dataDir' => realpath('/some/path'),
        ),
    ),
    
which makes PHPIMS use MongoDB as database and the filesystem for storage. The 'params' part will be sent to the drivers' constructor.

#### Add custom plugins
To add custom plugins you will need to change the `plugins` element of the configuration array. It can for instance look like this:

    // Custom plugins
    'plugins' => array(
        array('path' => '/some/path'),
        array('path' => '/some/other/path', 'prefix' => 'My_Custom_Plugins_'),
    ),

Each element is an array consisting of one or two elements: `path` and the optional `prefix`. Path is a base path to your plugin classes. The prefix is the "namespace" of your classes. For the example above, you can have plugins stored like this:

* /some/path/SomePlugin.php // Classname: SomePlugin
* /some/path/SomeOtherPlugin.php // Classname: SomeOtherPlugin
* /some/other/path/My/Custom/Plugins/SomePlugin.php // Classname: My_Custom_Plugins_SomePlugin
* /some/other/path/My/Custom/Plugins/SomeOtherPlugin.php // Classname: My_Custom_Plugins_SomeOtherPlugin

PHPIMS does not autoload any other classes than the ones included in PHPIMS itself, so you will have to add an autoloader yourself for your custom plugins. This autoloader can for instance be specified in the server.php script along with the configuration.