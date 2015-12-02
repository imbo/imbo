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
    Imbo\Auth\AccessControl\Adapter\AdapterInterface as AccessControlInterface,
    Imbo\Storage\StorageInterface;

/**
 * Event interface
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Event
 */
interface EventInterface {
    /**
     * Get the request parameter
     *
     * @return Request
     */
    function getRequest();

    /**
     * Get the response parameter
     *
     * @return Response
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
     * Get the access control adapter
     *
     * @return AccessControlInterface
     */
    function getAccessControl();

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
     * Get the handler for the current event
     *
     * @return string
     */
    function getHandler();
}
