<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace Imbo\IntegrationTest\EventListener;

use Imbo\EventListener\AutoRotateImage,
    Imbo\Model\Image,
    Imagick;

/**
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Test suite\Integration tests
 */
class AutoRotateImageTest extends \PHPUnit_Framework_TestCase {
    /**
     * @var AutoRotateImage
     */
    private $listener;

    /**
     * Set up the listener
     */
    public function setUp() {
        $this->listener = new AutoRotateImage();
    }

    /**
     * Tear down the listener
     */
    public function tearDown() {
        $this->listener = null;
    }

    /**
     * @covers Imbo\EventListener\AutoRotateImage::getSubscribedEvents
     */
    public function testReturnsCorrectSubscriptionData() {
        $className = get_class($this->listener);
        $events = $className::getSubscribedEvents();

        $this->assertTrue(isset($events['image.put']['autoRotate']));

    }

    /**
     * @return array[]
     */
    public function getFilenames() {
        $files = array();

        for ($i = 1; $i <= 8; $i++) {
            $filename = 'orientation' . $i . '.jpeg';
            $files[$filename] = array(FIXTURES_DIR . '/autoRotate/' . $filename);
        }

        return $files;
    }

    /**
     * @dataProvider getFilenames
     * @covers Imbo\EventListener\AutoRotateImage::autoRotate
     */
    public function testWillAutoRotateImages($file) {
        $colorValues = array(
            array(
                'x' => 0,
                'y' => 0,
                'color' => 'rgb(128,63,193)'
            ),
            array(
                'x' => 0,
                'y' => 1000,
                'color' => 'rgb(254,57,126)'
            ),
            array(
                'x' => 1000,
                'y' => 0,
                'color' => 'rgb(127,131,194)'
            ),
            array(
                'x' => 1000,
                'y' => 1000,
                'color' => 'rgb(249,124,192)'
            ),
        );

        /**
         * Load the image, perform the auto rotate tranformation and check that the color codes in
         * the four corner pixels match the known color values as defined in $colorValues
         */
        $image = new Image();
        $image->setBlob(file_get_contents($file));

        $request = $this->getMock('Imbo\Http\Request\Request');
        $request->expects($this->once())->method('getImage')->will($this->returnValue($image));
        $event = $this->getMock('Imbo\EventManager\EventInterface');
        $event->expects($this->once())->method('getRequest')->will($this->returnValue($request));

        $this->listener->autoRotate($event);

        // Do assertion comparison on the color values
        $imagick = new Imagick();
        $imagick->readImageBlob($image->getBlob());

        foreach ($colorValues as $pixelInfo) {
            $pixelValue = $imagick->getImagePixelColor($pixelInfo['x'], $pixelInfo['y'])
                                  ->getColorAsString();

            $this->assertStringEndsWith($pixelInfo['color'], $pixelValue);
        }
    }
}
