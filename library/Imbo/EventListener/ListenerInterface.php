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
 * @package Interfaces
 * @subpackage EventListener
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/imbo/imbo
 */

namespace Imbo\EventListener;

use Imbo\EventManager\EventInterface,
    Imbo\Exception;

/**
 * Listener interface
 *
 * @package Interfaces
 * @subpackage EventListener
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/imbo/imbo
 */
interface ListenerInterface {
    /**
     * Return the events this listener wants to listen for
     *
     * @return array
     */
    function getEvents();

    /**
     * Returns a list of public keys this event listener should be triggered for
     *
     * If an empty array is returned the listener is triggered for all users
     *
     * @return array
     */
    function getPublicKeys();

    /**
     * Set the list of public keys the listener should be triggered for
     *
     * @param array $keys An array of public keys
     * @return ListenerInterface
     */
    function setPublicKeys(array $keys);

    /**
     * Invoke method
     *
     * This method will get triggered by the event manager
     *
     * @param EventInterface $event The triggered event
     * @throws Exception This method can throw exceptions implementing the base Imbo exception
     *                   interface.
     */
    function invoke(EventInterface $event);
}
