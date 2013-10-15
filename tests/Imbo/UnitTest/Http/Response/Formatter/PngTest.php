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

use Imbo\Http\Response\Formatter\Png;

/**
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Test suite\Unit tests
 */
class PngTest extends ImageFormatterTests {
    /**
     * {@inheritdoc}
     */
    protected function getFormatter() {
        return new Png();
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedContentType() {
        return 'image/png';
    }
}
