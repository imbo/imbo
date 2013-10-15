<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace Imbo\UnitTest\Http\Response\Formatter;

use Imbo\Http\Response\Formatter\Jpeg;

/**
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Test suite\Unit tests
 */
class JpegTest extends ImageFormatterTests {
    /**
     * {@inheritdoc}
     */
    protected function getFormatter() {
        return new Jpeg();
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedContentType() {
        return 'image/jpeg';
    }
}
