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

namespace PHPIMS\Operation;

use PHPIMS\Operation;

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
abstract class Plugin {
    /**
     * Array of events this plugin will be triggered for
     *
     * All operations has two entry points for plugins, pre-exec and post-exec. Events use the
     * operations name as prefix with lcfirst(). The PHPIMS\Operation\AddImage entry points will
     * be:
     *
     * - addImagePreExec
     * - addImagePostExec
     *
     * If you want a plugin a be executed for instance after the PHPIMS\Operation\DeleteImage has
     * finished execution, create a plugin and set the $events array to:
     *
     * <code>
     * static public $events = array(
     *     'deleteImagePostExec' => <priority>
     * );
     * </code>
     *
     * where <priority> is a number. This is used to specify a specific execution order. If this
     * value is set to 0, the plugin will not execute when that specific event occurs. This can be
     * used to dynamically disable plugins. Internal plugins will typicall start at 100, so you
     * have a possibility to add 100 custom plugins pr. event and let them execute before the
     * internal plugins will.
     *
     * @var array
     */
    static public $events = array();

    /**
     * Parameters for the plugin
     *
     * @var array
     */
    protected $params = array();

    /**
     * Operation this plugins is attached to
     *
     * @var PHPIMS\Operation
     */
    protected $operation = null;

    /**
     * Class constructor
     *
     * @param array $params Parameters to the plugin
     * @param PHPIMS\Operation $operation Operation that owns this plugin
     * @codeCoverageIgnore
     */
    public function __construct(array $params = null, Operation $operation = null) {
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
     * @return PHPIMS\Operation\Plugin
     */
    public function setParams(array $params) {
        $this->params = $params;

        return $this;
    }
}