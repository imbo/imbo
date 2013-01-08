<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace Imbo\EventManager;

use Imbo\Http\Request\RequestInterface,
    Imbo\Http\Response\ResponseInterface,
    Imbo\Database\DatabaseInterface,
    Imbo\Storage\StorageInterface;

/**
 * Event interface
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Event
 */
interface EventInterface {
    /**
     * Set the name of the event
     *
     * @param string $name The name of the event
     * @return EventInterface
     */
    function setName($name);

    /**
     * Get the name of the event
     *
     * @return string
     */
    function getName();

    /**
     * Get the request instance
     *
     * @return RequestInterface
     */
    function getRequest();

    /**
     * Get the response instance
     *
     * @return ResponseInterface
     */
    function getResponse();

    /**
     * Get the database adapter
     *
     * @return DatabaseInterface
     */
    function getDatabase();

    /**
     * Get the storage adapter
     *
     * @return StorageInterface
     */
    function getStorage();

    /**
     * Get the event manager that triggered the event
     *
     * @return EventManager
     */
    function getManager();

    /**
     * Get the Imbo configuration
     *
     * @return array
     */
    function getConfig();

    /**
     * Whether or not to stop the execution of more listeners for the current event
     *
     * @param boolean $flag True to stop, false to continue
     * @return EventInterface
     */
    function stopPropagation($flag);

    /**
     * Return whether or not the propagation should stop
     *
     * @return boolean
     */
    function propagationIsStopped();
}
