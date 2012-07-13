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
 * @package Core
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/imbo/imbo
 */

namespace Imbo;

use Imbo\Exception\InvalidArgumentException;

/**
 * Dependency Injection Container
 *
 * This container can be used to inject dependencies in other classes instead of making hard
 * dependencies by instantiating concrete classes in other classes' constructors.
 *
 * The container is typically populated in a bootstrap process. The container can be populated
 * using actual instances of other classes or by supplying a closure that will be called when the
 * property is first accessed.
 *
 *     <?php
 *     namespace Imbo;
 *
 *     $container = new Container();
 *     $container->imageResource = new Resource\Image();
 *
 *     $dbParams = array('some' => 'params');
 *     $container->database = new Database\MongoDB($dbParams);
 *
 *     $storageParams = array('some' => 'params');
 *     $container->storage = new Storage\Filesystem($storageParams);
 *
 *     // or
 *
 *     namespace Imbo;
 *
 *     $container = new Container();
 *     $container->imageResource = $container->shared(function (Container $container) {
 *         return new Resource\Image();
 *     });
 *
 *     $dbParams = array('some' => 'params');
 *     $container->database = $container->shared(function (Container $container) use ($dbParams) {
 *         return new Database\MongoDB($dbParams);
 *     });
 *
 *     $storageParams = array('some' => 'params');
 *     $container->storage = $container->shared(function (Container $container) use ($storageParams) {
 *         return new Storage\Filesystem($storageParams);
 *     });
 *
 * This container is based on code by Fabien Potencier.
 *
 * @package Core
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/imbo/imbo
 */
class Container {
    /**
     * Values in the container
     *
     * @var array
     */
    private $values = array();

    /**
     * See if the container has a given value
     *
     * @param string $key The key to check for
     * @return boolean
     */
    public function has($key) {
        return isset($this->values[$key]);
    }

    /**
     * Set a value
     *
     * @param string $id The accessed property
     * @param mixed $value The value to set
     */
    public function __set($id, $value) {
        $this->values[$id] = $value;
    }

    /**
     * Alias of __set
     *
     * @param string $id The accessed property
     * @param mixed $value The value to set
     * @return Imbo\Container
     */
    public function set($id, $value) {
        $this->$id = $value;

        return $this;
    }

    /**
     * Get a property
     *
     * @param string $id The accessed property
     * @return mixed
     * @throws Imbo\Exception\InvalidArgumentException If someone tries to access a value that is
     *                                                 not yet defined an exception will be thrown.
     */
    public function __get($id) {
        if (!isset($this->values[$id])) {
            throw new InvalidArgumentException(sprintf('Value %s is not defined.', $id));
        }

        // If the property is callable, execute it with the closure as a parameter
        if (is_callable($this->values[$id])) {
            return $this->values[$id]($this);
        }

        return $this->values[$id];
    }

    /**
     * Alias of __get
     *
     * @param string $id The accessed property
     * @return mixed
     */
    public function get($id) {
        return $this->$id;
    }

    /**
     * Helper function used when you want to lazy load a property, and make it static
     *
     * @param callback $callable A closure that will be executed when the value is accessed
     * @return callback
     */
    public function shared($callable) {
        return function ($container) use ($callable) {
            static $value;

            if (is_null($value)) {
                $value = $callable($container);
            }

            return $value;
        };
    }
}
