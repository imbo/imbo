<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace ImboUnitTest\Image\Identifier\Generator;

use Imbo\Image\Identifier\Generator\Md5 as Md5Generator,
    ImagickException;

/**
 * @covers Imbo\Image\Identifier\Generator\Md5
 * @group unit
 */
class Md5Test extends \PHPUnit_Framework_TestCase {
    public function testGeneratesCorrectMd5ForBlob() {
        $image = $this->getMock('Imbo\Model\Image');
        $image->expects($this->any())->method('getBlob')->will($this->returnValue('foobar'));

        $generator = new Md5Generator();

        // Make sure it generates the same MD5 every time
        for ($i = 0; $i < 15; $i++) {
            $imageIdentifier = $generator->generate($image);
            $this->assertSame(md5('foobar'), $imageIdentifier);
        }
    }
}
