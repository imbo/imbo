<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace Imbo\Auth\AccessControl;

/**
 * Access control interface
 *
 * @author Espen Hovlandsdal <espen@hovlandsdal.com>
 * @package Core\Auth\AccessControl
 */
interface AccessControlInterface {
    const PERMISSION_READ                  = 'permission.read';
    const PERMISSION_WRITE                 = 'permission.write';

    const RESOURCE_GROUPS_GET              = 'groups.get';
    const RESOURCE_GROUPS_HEAD             = 'groups.head';
    const RESOURCE_GROUPS_OPTIONS          = 'groups.options';

    const RESOURCE_USER_GET                = 'user.get';
    const RESOURCE_USER_HEAD               = 'user.header';
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
     * Check if a given public key has access to a given resource
     *
     * @param  string  $publicKey Public key to check access for
     * @param  string  $resource  Resource identifier (e.g. image.get, images.post)
     * @param  string  $user      Optional user which the resource belongs to
     * @return boolean            True if public key has access, false otherwise
     */
    function hasAccess($publicKey, $resource, $user = null);

    /**
     * Fetch one or more users
     *
     * @param UserQuery $query A query object used to filter the users returned
     * @return string[] Returns a list of users
     */
    function getUsers(UserQuery $query = null);

    /**
     * Return whether the user given exists or not
     *
     * @param  string $user The user to check
     * @return boolean
     */
    function userExists($user);

    /**
     * Fetch a list of available resource groups
     *
     * @return array
     */
    function getGroups();

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
}
