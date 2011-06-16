<?php
/**
 * PHPIMS
 *
 * Copyright (c) 2011 Christer Edvartsen <cogo@starzinger.net>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to
 * deal in the Software without restriction, including without limitation the
 * rights to use, copy, modify, merge, publish, distribute, sublicense, and/or
 * sell copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * * The above copyright notice and this permission notice shall be included in
 *   all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 *
 * @package PHPIMS
 * @subpackage Unittests
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011, Christer Edvartsen
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/christeredvartsen/phpims
 */

namespace PHPIMS\Operation;

use \Mockery as m;
use PHPIMS\Operation\GetImages\Query;

/**
 * @package PHPIMS
 * @subpackage Unittests
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011, Christer Edvartsen
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/christeredvartsen/phpims
 */
class GetImagesTest extends OperationTests {
    protected function getNewOperation() {
        return new GetImages($this->database, $this->storage);
    }

    public function getExpectedOperationName() {
        return 'getImages';
    }

    public function testExec() {
        $images = array();

        // Set query parameters
        $_GET = array(
            'page'     => 2,
            'num'      => 30,
            'metadata' => 1,
            'query'    => json_encode(array('foo' => 'bar')),
            'from'     => 123123123,
            'to'       => 234234234,
        );

        $this->database->shouldReceive('getImages')->once()->with($this->publicKey, m::on(function(Query $q) {
            return $q->page() === $_GET['page'] &&
                   $q->num() === $_GET['num'] &&
                   $q->returnMetadata() === true &&
                   $q->query() === json_decode($_GET['query'], true) &&
                   $q->from() === $_GET['from'] &&
                   $q->to() === $_GET['to'];
        }))->andReturn($images);

        $response = m::mock('PHPIMS\\Server\\Response');
        $response->shouldReceive('setBody')->once()->with($images);

        $this->operation->setResponse($response);
        $this->operation->exec();
    }
}
