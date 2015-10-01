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

use Imbo\Auth\AccessControl\UserQuery,
    Imbo\Auth\AccessControl\GroupQuery;

/**
 * Access control interface
 *
 * @author Espen Hovlandsdal <espen@hovlandsdal.com>
 * @package Core\Auth\AccessControl
 */
interface AdapterInterface {
    const RESOURCE_GROUPS_GET              = 'groups.get';
    const RESOURCE_GROUPS_HEAD             = 'groups.head';
    const RESOURCE_GROUPS_OPTIONS          = 'groups.options';

    const RESOURCE_GROUP_GET               = 'group.get';
    const RESOURCE_GROUP_HEAD              = 'group.head';
    const RESOURCE_GROUP_PUT               = 'group.put';
    const RESOURCE_GROUP_DELETE            = 'group.delete';
    const RESOURCE_GROUP_OPTIONS           = 'group.options';

    const RESOURCE_KEYS_PUT                = 'keys.put';
    const RESOURCE_KEYS_DELETE             = 'keys.delete';
    const RESOURCE_KEYS_OPTIONS            = 'keys.options';

    const RESOURCE_ACCESS_RULE_GET         = 'accessrule.get';
    const RESOURCE_ACCESS_RULE_HEAD        = 'accessrule.head';
    const RESOURCE_ACCESS_RULE_DELETE      = 'accessrule.delete';
    const RESOURCE_ACCESS_RULE_OPTIONS     = 'accessrule.options';

    const RESOURCE_ACCESS_RULES_GET        = 'accessrules.get';
    const RESOURCE_ACCESS_RULES_HEAD       = 'accessrules.head';
    const RESOURCE_ACCESS_RULES_POST       = 'accessrules.post';
    const RESOURCE_ACCESS_RULES_OPTIONS    = 'accessrules.options';

    const RESOURCE_USER_GET                = 'user.get';
    const RESOURCE_USER_HEAD               = 'user.head';
    const RESOURCE_USER_OPTIONS            = 'user.options';

    const RESOURCE_IMAGE_GET               = 'image.get';
    const RESOURCE_IMAGE_HEAD              = 'image.head';
    const RESOURCE_IMAGE_DELETE            = 'image.delete';
    const RESOURCE_IMAGE_OPTIONS           = 'image.options';

    const RESOURCE_IMAGES_GET              = 'images.get';
    const RESOURCE_IMAGES_HEAD             = 'images.head';
    const RESOURCE_IMAGES_POST             = 'images.post';
    const RESOURCE_IMAGES_OPTIONS          = 'images.options';

    const RESOURCE_METADATA_GET            = 'metadata.get';
    const RESOURCE_METADATA_HEAD           = 'metadata.head';
    const RESOURCE_METADATA_PUT            = 'metadata.put';
    const RESOURCE_METADATA_POST           = 'metadata.post';
    const RESOURCE_METADATA_DELETE         = 'metadata.delete';
    const RESOURCE_METADATA_OPTIONS        = 'metadata.options';

    const RESOURCE_SHORTURL_GET            = 'shorturl.get';
    const RESOURCE_SHORTURL_HEAD           = 'shorturl.head';
    const RESOURCE_SHORTURL_DELETE         = 'shorturl.delete';
    const RESOURCE_SHORTURL_OPTIONS        = 'shorturl.options';

    const RESOURCE_SHORTURLS_POST          = 'shorturls.post';
    const RESOURCE_SHORTURLS_DELETE        = 'shorturls.delete';

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
     * @return array
     */
    function getGroups(GroupQuery $query = null);

    /**
     * Fetch a resource group with the given name
     *
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

    /**
     * Returns a list of resources which should be accessible for read-only public keys
     *
     * @return array
     */
    static function getReadOnlyResources();

    /**
     * Returns a list of resources which should be accessible for read+write public keys
     *
     * @return array
     */
    static function getReadWriteResources();

    /**
     * Returns a list of all resources available, including those which involves access control
     *
     * @return array
     */
    static function getAllResources();
}
