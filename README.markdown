#PHP Image Server
PHP Image Server (**PHPIMS**) is an image "server" that can be used to add/get/delete images using a REST interface. There is also support for adding metadata to an image. The main idea behind PHPIMS is to have a place to store high quality original images and to use the REST interface to fetch variations of those images. PHPIMS will resize, rotate, crop, switch formats (amongst other features) on the fly so you won't have to store all the different variations. PHPIMS comes with an administration dashboard that can be used to locate images. The dashboard will also support editing of metadata.

## Installation
Since this is a work in progress there is no automatic installation. Developers who want to contribute will need to do the following steps:

### Fork PHPIMS and checkout your fork
Click on the fork button on github and clone your fork:

$ git clone git@github.com:<username>/Imagine.git

### Software needed
To fully develop PHPIMS you will need to have the following software installed:

* [PHPUnit](http://phpunit.de/)
* [Mockery](https://github.com/padraic/mockery)
* [vfsStream](http://code.google.com/p/bovigo/wiki/vfsStream)
* [Imagine](https://github.com/avalanche123/Imagine/)
* [mongoDB](http://www.mongodb.org/)
* [Mongo extension for PHP](http://pecl.php.net/package/mongo)

### Copy .dist files
To run the testsuite you should copy `phpunit.xml.dist` to `phpunit.xml`. Some tests will be skipped unless you set up a local working PHPIMS installation with for instance Apache. There is a vhost.conf.dist file that you can use as a base. Customize the paths, and restart Apache.

If you want to run all tests you will have to change the contants in `phpunit.xml`. Set `PHPIMS_ENABLE_CLIENT_TESTS` to true, and change `PHPIMS_CLIENT_TESTS_URL` to point to `tests/PHPIMS/Client/Driver/_files/driver.php`.

### Commands for installing needed files
    
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

##REST API
PHPIMS uses a REST API to manage the images. Each image will be identified by an MD5 sum of the image itself and the original file extension, that will be referred to as &lt;image&gt; for the remainder of this document.

###GET /&lt;image&gt;

Fetch the image identified by &lt;image&gt;. The following query parameters are supported:

* `(int) width` Width of the image in pixels.
* `(int) height` Height of the image in pixels.

If no options are specified the original image will be returned.

###GET /&lt;image&gt;/meta

Get metadata related to the image identified by &lt;image&gt;. The metadata will be JSON encoded.

###POST /&lt;image&gt;

Place a new image on the server along with metadata.

###POST /&lt;image&gt;/meta

Edit the metadata attached to the image identified by &lt;image&gt;.

###DELETE /&lt;image&gt;

Delete the image identified by &lt;image&gt; along with all metadata. This action is not reversable.

###DELETE /&lt;image&gt;/meta

Delete the metadata attached to the image identified by &lt;image&gt;. The image is kept on the server. This action is not reversable.

###HEAD /&lt;image&gt;

Fetches extra header information about a single image identified by &lt;image&gt;.

###HEAD /&lt;image&gt;/meta

Fetches extra header information about the metadata attached to the image identified by &lt;image&gt;. 

##Extra response headers
PHPIMS will usually inject extra response headers to the different requests. All response headers from PHPIMS will be prefixed with **X-PHPIMS-**.

##PHP client
A PHP client is included in PHPIMS that supports all the REST methods and includes some convenience methods. The client requires the URL to the PHPIMS server as an argument to the constructor.

###Add an image

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
    
###Get metadata

    <?php
    require 'PHPIMS/Autoload.php';

    $client = new PHPIMS_Client('http://<hostname>');
    
    $hash = '<hash>';
    $response = $client->getMetadata($hash);

###Delete an image

    <?php
    require 'PHPIMS/Autoload.php';

    $client = new PHPIMS_Client('http://<hostname>');
    
    $hash = '<hash>';
    $response = $client->deleteImage($hash);
    
###Delete all metadata attached to an image

    <?php
    require 'PHPIMS/Autoload.php';

    $client = new PHPIMS_Client('http://<hostname>');
    
    $hash = '<hash>';
    $response = $client->deleteMetadata($hash);

###Client response object    
All client methods returns an instance of `PHPIMS_Client_Response`. In this instance you will find information on the request that was made. 

The response instance includes all response headers and the body, and has the following methods:

* `array` `getHeaders(void)` Get all response headers 
* `array` `asArray(void)` Get the body as a native PHP array instead of a JSON-encoded string
* `stdClass` `asObject(void)` Get the body as an instance of stdClass instead of a JSON-encoded string
* `boolean` `isSuccess(void)` Wether or not the response was a success (true if the HTTP status code is in the 2xx range) 
* `int` `getStatusCode(void)` Get the status code