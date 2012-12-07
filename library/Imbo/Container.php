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
 *     $container->set('imageResource', new Resource\Image());
 *
 *     $dbParams = array('some' => 'params');
 *     $container->set('database', new Database\MongoDB($dbParams));
 *
 *     $storageParams = array('some' => 'params');
 *     $container->set('storage', new Storage\Filesystem($storageParams));
 *
 *     // or
 *
 *     namespace Imbo;
 *
 *     $container = new Container();
 *     $container->setStatic('imageResource', function (Container $container) {
 *         return new Resource\Image();
 *     });
 *
 *     $dbParams = array('some' => 'params');
 *     $container->setStatic('database', function (Container $container) use ($dbParams) {
 *         return new Database\MongoDB($dbParams);
 *     });
 *
 *     $storageParams = array('some' => 'params');
 *     $container->setStatic('storage', function (Container $container) use ($storageParams) {
 *         return new Storage\Filesystem($storageParams);
 *     });
 *
 * If you provide a callable to the set method, the callable will be executed every time you access
 * the value. This is handy when you want the container to create new instances every time you
 * fetch a property. For instance:
 *
 *     <?php
 *     namespace Imbo;
 *
 *     $container = new Container();
 *     $container->set('event', function(Container $container) {
 *         return new Event();
 *     });
 *     $someEvent = $container->get('event');
 *     $someOtherEvent = $container->get('event');
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
    public function set($id, $value) {
        $this->values[$id] = $value;
    }

    /**
     * Get a property
     *
     * @param string $id The accessed property
     * @return mixed
     * @throws InvalidArgumentException Throws an exception when trying to get a value that does not
     *                                  exist.
     */
    public function get($id) {
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
     * Set a static value
     *
     * @param callback $callable A closure that will be executed when the value is accessed
     */
    public function setStatic($id, $callable) {
        $this->set($id, function ($container) use ($callable) {
            static $value;

            if (is_null($value)) {
                $value = $callable($container);
            }

            return $value;
        });
    }
}
