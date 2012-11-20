<?php
/**
 * Imbo
 *
 * Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
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
 * @package EventManager
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/imbo/imbo
 */

namespace Imbo\EventManager;

use Imbo\Container,
    Imbo\ContainerAware;

/**
 * Event class
 *
 * @package EventManager
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/imbo/imbo
 */
class Event implements ContainerAware, EventInterface {
    /**
     * Name of the current event
     *
     * @var string
     */
    private $name;

    /**
     * @var Container
     */
    private $container;

    /**
     * Optional parameters
     *
     * @var array
     */
    private $params;

    /**
     * Propagation flag
     *
     * @var boolean
     */
    private $propagationIsStopped = false;

    /**
     * Class contsructor
     *
     * @param string $name The name of the current event
     * @param array $params Optional parameters
     */
    public function __construct($name, array $params = array()) {
        $this->name = $name;
        $this->params = $params;
    }

    /**
     * {@inheritdoc}
     */
    public function setContainer(Container $container) {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function getName() {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function getRequest() {
        return $this->container->get('request');
    }

    /**
     * {@inheritdoc}
     */
    public function getResponse() {
        return $this->container->get('response');
    }

    /**
     * {@inheritdoc}
     */
    public function getDatabase() {
        return $this->container->get('database');
    }

    /**
     * {@inheritdoc}
     */
    public function getStorage() {
        return $this->container->get('storage');
    }

    /**
     * {@inheritdoc}
     */
    public function getManager() {
        return $this->container->get('eventManager');
    }

    /**
     * {@inheritdoc}
     */
    public function getConfig() {
        return $this->container->get('config');
    }

    /**
     * {@inheritdoc}
     */
    public function stopPropagation($flag) {
        $this->propagationIsStopped = $flag;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function propagationIsStopped() {
        return $this->propagationIsStopped;
    }

    /**
     * {@inheritdoc}
     */
    public function getParams() {
        return $this->params;
    }
}
