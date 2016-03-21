<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace Imbo\Auth\AccessControl\Adapter;

use Imbo\Auth\AccessControl\GroupQuery,
    Imbo\Model\Groups as GroupsModel;

/**
 * Access control interface
 *
 * @author Espen Hovlandsdal <espen@hovlandsdal.com>
 * @package Core\Auth\AccessControl
 */
interface AdapterInterface {
    /**
     * Get a list of users the public key has access for on a given resource
     *
     * @param  string $publicKey Public key to check access for
     * @param  string $resource  Resource identifier (e.g. image.get, images.post)
     * @return array             List of users the public key kan access the given resource for
     */
    function getUsersForResource($publicKey, $resource);

    /**
     * Check if a given public key has access to a given resource
     *
     * @param  string  $publicKey Public key to check access for
     * @param  string  $resource  Resource identifier (e.g. image.get, images.post)
     * @param  string  $user      Optional user which the resource belongs to
     * @return boolean            True if public key has access, false otherwise
     */
    function hasAccess($publicKey, $resource, $user = null);

    /**
     * Fetch a list of available resource groups
     *
     * @param GroupQuery $query A query object used to filter the groups returned
     * @param GroupsModel $model Groups model to populate total number of hits with
     * @return array
     */
    function getGroups(GroupQuery $query = null, GroupsModel $model);

    /**
     * Check whether or not a group exists
     *
     * @param string $groupName Name of the group
     * @return boolean
     */
    function groupExists($groupName);

    /**
     * Fetch a resource group with the given name
     *
     * @param string $groupName Name of the group
     * @return array Array of resources the group consists of
     */
    function getGroup($groupName);

    /**
     * Return the private key for a given public key
     *
     * @param  string $publicKey The public key to fetch matching private key for
     * @return string Returns the private key for the public key
     */
    function getPrivateKey($publicKey);

    /**
     * Get whether a public key exists or not
     *
     * @param string $publicKey Public key to check
     * @return boolean
     */
    function publicKeyExists($publicKey);

    /**
     * Get the access control list for a given public key
     *
     * @param  string $publicKey
     * @return array
     */
    function getAccessListForPublicKey($publicKey);

    /**
     * Get an access rule by id
     *
     * @param  string $publicKey    Public key to add access rule to
     * @param  array  $accessRuleId Access rule id
     * @return array Access rule
     */
    function getAccessRule($publicKey, $accessRuleId);
}
