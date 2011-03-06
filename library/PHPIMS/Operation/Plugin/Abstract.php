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
 * @subpackage OperationPlugin
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011, Christer Edvartsen
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/christeredvartsen/phpims
 */

/**
 * Abstract class for operation plugins
 *
 * @package PHPIMS
 * @subpackage OperationPlugin
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011, Christer Edvartsen
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/christeredvartsen/phpims
 */
abstract class PHPIMS_Operation_Plugin_Abstract {
    /**
     * Parameters for the plugin
     *
     * @var array
     */
    protected $params = array();

    /**
     * Operation this plugins is attached to
     *
     * @var PHPIMS_Operation_Abstract
     */
    protected $operation = null;

    /**
     * Class constructor
     *
     * @param array $params Parameters to the plugin
     * @param PHPIMS_Operation_Abstract $operation Operation that owns this plugin
     */
    public function __construct(array $params = null, PHPIMS_Operation_Abstract $operation = null) {
        if ($params !== null) {
            $this->setParams($params);
        }

        if ($operation !== null) {
            $this->setOperation($operation);
        }
    }

    /**
     * Get the parameters
     *
     * @return array
     */
    public function getParams() {
        return $this->params;
    }

    /**
     * Set plugin parameters
     *
     * @param array $params Parameters to set
     * @return PHPIMS_Operation_Plugin_Abstract
     */
    public function setParams(array $params) {
        $this->params = $params;

        return $this;
    }

    /**
     * Get the operation
     *
     * @return PHPIMS_Operation_Abstract
     */
    public function getOperation() {
        return $this->operation;
    }

    /**
     * Set the operation
     *
     * @param PHPIMS_Operation_Abstract $operation The operation this plugins is attached to
     * @return PHPIMS_Operation_Plugin_Abstract
     */
    public function setOperation(PHPIMS_Operation_Abstract $operation) {
        $this->operation = $operation;

        return $this;
    }

    /**
     * Method that will be triggered before the operation exec() kicks in
     */
    public function preExec() {
        // Must be implemented by plugins
    }

    /**
     * Method that will be triggered after the operations exec() method is finished
     */
    public function postExec() {
        // Must be implemented by plugins
    }
}