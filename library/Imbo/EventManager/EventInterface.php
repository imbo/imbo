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

use Imbo\Http\Request\Request,
    Imbo\Http\Response\Response,
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
     * Set the request
     *
     * @param Request $request The request instance
     * @return self
     */
    function setRequest(Request $request);

    /**
     * Get the request instance
     *
     * @return Request
     */
    function getRequest();

    /**
     * Set the response
     *
     * @param Response $response The response instance
     * @return self
     */
    function setResponse(Response $response);

    /**
     * Get the response instance
     *
     * @return Response
     */
    function getResponse();

    /**
     * Set the database adapter
     *
     * @param DatabaseInterface $database The database adapter
     * @return self
     */
    function setDatabase(DatabaseInterface $database);

    /**
     * Get the database adapter
     *
     * @return DatabaseInterface
     */
    function getDatabase();

    /**
     * Set the storage adapter
     *
     * @param StorageInterface $storage The storage adapter
     * @return self
     */
    function setStorage(StorageInterface $storage);

    /**
     * Get the storage adapter
     *
     * @return StorageInterface
     */
    function getStorage();

    /**
     * Set the event manager
     *
     * @param EventManager $manager The event manager
     * @return self
     */
    function setManager(EventManager $manager);

    /**
     * Get the event manager that triggered the event
     *
     * @return EventManager
     */
    function getManager();

    /**
     * Set the configuration
     *
     * @param array $config The configuration array
     * @return self
     */
    function setConfig(array $config);

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

    /**
     * Set the handler name
     *
     * @param string $handler The name of the handler for the current event
     * @return self
     */
    function setHandler($handler);

    /**
     * Get the handler for the current event
     *
     * @return string
     */
    function getHandler();
}
