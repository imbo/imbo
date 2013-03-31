<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace Imbo\UnitTest\Http\Request;

use Imbo\Http\Request\Request;

/**
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Test suite\Unit tests
 */
class RequestTest extends \PHPUnit_Framework_TestCase {
    /**
     * @covers Imbo\Http\Request\Request::getTransformations
     */
    public function testGetTransformationsWithNoTransformationsPresent() {
        $request = new Request();
        $this->assertEquals(array(), $request->getTransformations());
    }

    /**
     * @covers Imbo\Http\Request\Request::getTransformations
     */
    public function testGetTransformationsWithCorrectOrder() {
        $query = array(
            't' => array(
                'flipHorizontally',
                'flipVertically',
            ),
        );

        $request = new Request($query);
        $transformations = $request->getTransformations();
        $this->assertEquals('flipHorizontally', $transformations[0]['name']);
        $this->assertEquals('flipVertically',   $transformations[1]['name']);
    }

    /**
     * @covers Imbo\Http\Request\Request::getTransformations
     */
    public function testGetTransformations() {
        $query = array(
            't' => array(
                // Valid transformations with all options
                'border:color=fff,width=2,height=2',
                'compress:quality=90',
                'crop:x=1,y=2,width=3,height=4',
                'resize:width=100,height=100',

                // Transformations with no options
                'flipHorizontally',
                'flipVertically',

                // The same transformation can be applied multiple times
                'resize:width=50,height=75',
            ),
        );

        $request = new Request($query);
        $transformations = $request->getTransformations();
        $this->assertInternalType('array', $transformations);
        $this->assertSame(7, count($transformations));

        $this->assertEquals(array('color' => 'fff', 'width' => 2, 'height' => 2), $transformations[0]['params']);
        $this->assertEquals(array('quality' => '90'), $transformations[1]['params']);
        $this->assertEquals(array('x' => 1, 'y' => 2, 'width' => 3, 'height' => 4), $transformations[2]['params']);
        $this->assertEquals(array('width' => 100, 'height' => 100), $transformations[3]['params']);
        $this->assertEquals(array(), $transformations[4]['params']);
        $this->assertEquals(array(), $transformations[5]['params']);
        $this->assertEquals(array('width' => 50, 'height' => 75), $transformations[6]['params']);
    }

    /**
     * @covers Imbo\Http\Request\Request::getImageIdentifier
     * @covers Imbo\Http\Request\Request::setImageIdentifier
     */
    public function testSetGetImageIdentifier() {
        $request = new Request();
        $identifier = md5(microtime());
        $this->assertNull($request->getImageIdentifier());
        $this->assertSame($request, $request->setImageIdentifier($identifier));
        $this->assertSame($identifier, $request->getImageIdentifier());
    }

    /**
     * @covers Imbo\Http\Request\Request::getExtension
     * @covers Imbo\Http\Request\Request::setExtension
     */
    public function testSetGetExtension() {
        $request = new Request();
        $extension = 'gif';
        $this->assertNull($request->getExtension());
        $this->assertSame($request, $request->setExtension($extension));
        $this->assertSame($extension, $request->getExtension());
    }

    /**
     * @covers Imbo\Http\Request\Request::setPublicKey
     * @covers Imbo\Http\Request\Request::getPublicKey
     */
    public function testSetGetPublicKey() {
        $request = new Request();
        $publicKey = 'publicKey';
        $this->assertSame($request, $request->setPublicKey($publicKey));
        $this->assertSame($publicKey, $request->getPublicKey());
    }

    /**
     * @covers Imbo\Http\Request\Request::setPrivateKey
     * @covers Imbo\Http\Request\Request::getPrivateKey
     */
    public function testSetGetPrivateKey() {
        $request = new Request();
        $privateKey = '55b90a334854ac17b91f5c5690944f31';
        $this->assertSame($request, $request->setPrivateKey($privateKey));
        $this->assertSame($privateKey, $request->getPrivateKey());
    }

    /**
     * @covers Imbo\Http\Request\Request::getResource
     * @covers Imbo\Http\Request\Request::setResource
     */
    public function testSetGetResource() {
        $request = new Request();
        $this->assertSame($request, $request->setResource('metadata'));
        $this->assertSame('metadata', $request->getResource());
    }

    /**
     * @covers Imbo\Http\Request\Request::hasTransformations
     */
    public function testHasTransformationsWithExtension() {
        $request = new Request();
        $request->setExtension('png');
        $this->assertTrue($request->hasTransformations());
    }

    /**
     * @covers Imbo\Http\Request\Request::hasTransformations
     */
    public function testHasTransformationsWithTransformationsInQuery() {
        $request = new Request(array('t' => array('flipHorizontally')));
        $this->assertTrue($request->hasTransformations());
    }

    /**
     * @covers Imbo\Http\Request\Request::hasTransformations
     */
    public function testHasTransformationsWithNoTransformations() {
        $request = new Request();
        $this->assertFalse($request->hasTransformations());
    }

    /**
     * @covers Imbo\Http\Request\Request::getImage
     * @covers Imbo\Http\Request\Request::setImage
     */
    public function testCanSetAndGetAnImage() {
        $request = new Request();
        $image = $this->getMock('Imbo\Model\Image');
        $this->assertSame($request, $request->setImage($image));
        $this->assertSame($image, $request->getImage());
    }
}
