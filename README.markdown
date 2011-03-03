PHP Image Server
================
PHP Image Server (**PHPIMS**) is an image "server" that can be used to add/get/delete images using a REST interface. There is also support for adding metadata to an image.

REST API
--------
PHPIMS uses a REST API

**GET /&lt;hash&gt;**

Fetch the image identified by &lt;hash&gt;. The following options are supported:
* (int) width Width of the image in pixels
* (int) height Height of the image in pixels
* (string) format The file format (supported formats: jpg, gif and png)
* (int) quality The quality of the resulting image (0-100 where 100 is the best quality. Not all image formats supports this)

**GET /&lt;hash&gt;/meta**

Get metadata related to the image identified by &lt;hash&gt;.

**DELETE /&lt;hash&gt;**

Delete the image identified by &lt;hash&gt;.

**POST /[&lt;hash&gt;]**

Place a new image on the server along with metadata. Can be used to manipulate metadata when used with a hash.

**HEAD /[&lt;hash&gt;]**

Fetches extra header information about a single image or about the site in general when used without &lt;hash&gt;.

All these methods (with the exception of **HEAD** returns JSON encoded data about the triggered operation.

PHP client
----------
A PHP client is included in PHPIMS that supports all the REST methods and includes some convenience methods.

** Add an image **

    <?php
    require 'PHPIMS/Autoload.php';

    $client = new PHPIMS_Client();
    $client->setServerUrl('http://<hostname>');
    
    $image = '/path/to/image.png';
    $metadata = array(
        'foo' => 'bar', 
        'bar' => 'foo',
    );
    $response = $client->add($image, $metadata);
    
In the response you will find the image hash that you will need to identify the image in other operations.

    <?php
    print($response); // {"id":"4d6f3dd32f07a9e605000000"}
    
The response from the client is a `PHPIMS_Client_Response` object that holds all response headers and the body. When used in a string context it will return the body as JSON-encoded data. Other options that can be used are:

* array `getHeaders(void)`
* array `asArray(void)`
* stdClass `asObject(void)`
* boolean `isOk(void)`
* int `getStatusCode(void)`

** Delete an image **

    <?php
    require 'PHPIMS/Autoload.php';

    $client = new PHPIMS_Client();
    $client->setServerUrl('http://<hostname>');
    
    $hash = '<some hash>';
    $response = $client->deleteImage($image); 