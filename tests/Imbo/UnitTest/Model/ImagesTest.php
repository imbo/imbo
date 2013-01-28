<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace Imbo\UnitTest\Model;

use Imbo\Model\Images;

/**
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Test suite\Unit tests
 * @covers Imbo\Model\Images
 */
class ImagesTest extends \PHPUnit_Framework_TestCase {
    /**
     * @var Images
     */
    private $model;

    /**
     * Set up the model
     */
    public function setUp() {
        $this->model = new Images();
    }

    /**
     * Tear down the model
     */
    public function tearDown() {
        $this->model = null;
    }

    /**
     * @covers Imbo\Model\Images::getImages
     * @covers Imbo\Model\Images::setImages
     */
    public function testCanSetAndGetImages() {
        $images = array(
            $this->getMock('Imbo\Model\Image'),
            $this->getMock('Imbo\Model\Image'),
            $this->getMock('Imbo\Model\Image'),
        );
        $this->assertSame(array(), $this->model->getImages());
        $this->assertSame($this->model, $this->model->setImages($images));
        $this->assertSame($images, $this->model->getImages());
    }
}
