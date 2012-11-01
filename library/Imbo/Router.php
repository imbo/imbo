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

use Imbo\Resource\ResourceInterface,
    Imbo\Exception\RuntimeException;

/**
 * Router class containing supported routes
 *
 * @package Core
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/imbo/imbo
 */
class Router implements RouterInterface {
    /**
     * The different routes that imbo handles
     *
     * @var array
     */
    public $routes = array(
        ResourceInterface::IMAGE    => '#^/users/(?<publicKey>[a-z0-9_-]{3,})/images/(?<imageIdentifier>[a-f0-9]{32})(/|.(?<extension>gif|jpg|png))?$#',
        ResourceInterface::STATUS   => '#^/status(/|(\.(?<extension>json|html|xml)))?$#',
        ResourceInterface::IMAGES   => '#^/users/(?<publicKey>[a-z0-9_-]{3,})/images(/|(\.(?<extension>json|html|xml)))?$#',
        ResourceInterface::METADATA => '#^/users/(?<publicKey>[a-z0-9_-]{3,})/images/(?<imageIdentifier>[a-f0-9]{32})/meta(/|\.(?<extension>json|html|xml))?$#',
        ResourceInterface::USER     => '#^/users/(?<publicKey>[a-z0-9_-]{3,})(/|\.(?<extension>json|html|xml))?$#',
    );

    /**
     * @var Container
     */
    private $container;

    /**
     * Class constructor
     *
     * @param Container $container An instance of the Container
     */
    public function __construct(Container $container) {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function resolve($path, array &$matches) {
        foreach ($this->routes as $resourceName => $route) {
            if (preg_match($route, $path, $matches)) {
                break;
            }
        }

        // Path matched no route
        if (!$matches) {
            throw new RuntimeException('Not Found', 404);
        }

        $matches['resourceName'] = $resourceName;
        $entry = $resourceName . 'Resource';

        if (!$this->container->has($entry)) {
            throw new RuntimeException('Unknown Resource', 500);
        }

        return $this->container->$entry;
    }
}
