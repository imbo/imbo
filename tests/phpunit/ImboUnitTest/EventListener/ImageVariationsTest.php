<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace ImboUnitTest\EventListener;

use Imbo\EventListener\ImageVariations;

/**
 * @covers Imbo\EventListener\ImageVariations
 * @group unit
 * @group listeners
 */
class ImageVariationsTest extends ListenerTests {
    /**
     * @var ImageVariations
     */
    private $listener;

    /**
     * {@inheritdoc}
     */
    protected function getListener() {
        return new ImageVariations(array(
            'database' => array(
                'adapter' => $this->getMock('Imbo\EventListener\ImageVariations\Database\DatabaseInterface'),
            ),
            'storage' => array(
                'adapter' => $this->getMock('Imbo\EventListener\ImageVariations\Storage\StorageInterface'),
            ),
        ));
    }

    /**
     * Set up the listener
     */
    public function setUp() {
        $this->listener = $this->getListener();
    }

    /**
     * Tear down the listener
     */
    public function tearDown() {
        $this->listener = null;
    }

    /**
     * Data provider
     *
     * @return array[]
     */
    public function getTransformations() {
        return array(
            'no transformations' => array(
                800,
                600,
                array(),
                null,
            ),
            'resize with width' => array(
                800,
                600,
                array(
                    array(
                        'name' => 'resize',
                        'params' => array(
                            'width' => 200,
                        ),
                    ),
                ),
                array(200),
            ),
            'resize with height' => array(
                800,
                600,
                array(
                    array(
                        'name' => 'resize',
                        'params' => array(
                            'height' => 200,
                        ),
                    ),
                ),
                array(200 * (800 / 600)), // height * aspect ratio
            ),
            'maxSize with width' => array(
                1024,
                768,
                array(
                    array(
                        'name' => 'maxSize',
                        'params' => array(
                            'width' => 150,
                        ),
                    ),
                ),
                array(150),
            ),
            'maxSize with height' => array(
                1024,
                768,
                array(
                    array(
                        'name' => 'maxSize',
                        'params' => array(
                            'height' => 150,
                        ),
                    ),
                ),
                array(150 * (1024 / 768)), // height * aspect ratio
            ),
            'thumbnail with width' => array(
                500,
                500,
                array(
                    array(
                        'name' => 'thumbnail',
                        'params' => array(
                            'width' => 25,
                        ),
                    ),
                ),
                array(25),
            ),
            'thumbnail with height' => array(
                500,
                500,
                array(
                    array(
                        'name' => 'thumbnail',
                        'params' => array(
                            'height' => 25,
                        ),
                    ),
                ),
                array(50), // default for thumbnail
            ),
            'thumbnail with height and inset fit' => array(
                500,
                500,
                array(
                    array(
                        'name' => 'thumbnail',
                        'params' => array(
                            'height' => 25,
                            'fit' => 'inset',
                        ),
                    ),
                ),
                array(25 * (500 / 500)), // height * aspect ratio
            ),
            'thumbnail with no params' => array(
                500,
                500,
                array(
                    array(
                        'name' => 'thumbnail',
                        'params' => array(),
                    ),
                ),
                array(50), // default for thumbnail
            ),
            'pick a value that is not in the first index' => array(
                800,
                600,
                array(
                    array(
                        'name' => 'thumbnail',
                        'params' => array(),
                    ),
                    array(
                        'name' => 'resize',
                        'params' => array(
                            'width' => 250,
                        ),
                    ),
                    array(
                        'name' => 'maxSize',
                        'params' => array(
                            'width' => 100,
                        ),
                    ),
                ),
                array(1 => 250),
            ),
        );
    }

    /**
     * @dataProvider getTransformations
     */
    public function testCanGetTheMinWidthFromASetOfTransformations($width, $height, array $transformations, $maxWidth) {
        $this->assertSame($maxWidth, $this->listener->getMaxWidth($width, $height, $transformations), 'Could not figure out the minimum width');
    }
}
