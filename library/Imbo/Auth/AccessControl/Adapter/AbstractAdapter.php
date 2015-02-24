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

use Imbo\Auth\AccessControl\Adapter\AdapterInterface as ACI,
    Imbo\Auth\AccessControl\UserQuery,
    Imbo\Auth\AccessControl\GroupQuery;

/**
 * Abstract access control adapter
 *
 * @author Espen Hovlandsdal <espen@hovlandsdal.com>
 * @package Core\Auth\AccessControl
 */
abstract class AbstractAdapter implements AdapterInterface {

    /**
     * {@inheritdoc}
     */
    abstract public function hasAccess($publicKey, $resource, $user = null);

    /**
     * {@inheritdoc}
     */
    abstract public function getUsers(UserQuery $query = null);

    /**
     * {@inheritdoc}
     */
    abstract public function getGroups(GroupQuery $query = null);

    /**
     * {@inheritdoc}
     */
    abstract public function getGroup($groupName);

    /**
     * {@inheritdoc}
     */
    abstract public function userExists($user);

    /**
     * {@inheritdoc}
     */
    final public static function getReadOnlyResources() {
        return [
            ACI::RESOURCE_USER_GET,     ACI::RESOURCE_USER_HEAD,     ACI::RESOURCE_USER_OPTIONS,
            ACI::RESOURCE_IMAGE_GET,    ACI::RESOURCE_IMAGE_HEAD,    ACI::RESOURCE_IMAGE_OPTIONS,
            ACI::RESOURCE_GROUPS_GET,   ACI::RESOURCE_GROUPS_HEAD,   ACI::RESOURCE_GROUPS_OPTIONS,
            ACI::RESOURCE_IMAGES_GET,   ACI::RESOURCE_IMAGES_HEAD,   ACI::RESOURCE_IMAGES_OPTIONS,
            ACI::RESOURCE_METADATA_GET, ACI::RESOURCE_METADATA_HEAD, ACI::RESOURCE_METADATA_OPTIONS,
            ACI::RESOURCE_SHORTURL_GET, ACI::RESOURCE_SHORTURL_HEAD, ACI::RESOURCE_SHORTURL_OPTIONS
        ];
    }

    /**
     * {@inheritdoc}
     */
    final public static function getReadWriteResources() {
        return array_merge(
            self::getReadOnlyResources(), [
                ACI::RESOURCE_IMAGE_DELETE,
                ACI::RESOURCE_IMAGES_POST,
                ACI::RESOURCE_METADATA_POST, ACI::RESOURCE_METADATA_DELETE,
                ACI::RESOURCE_SHORTURL_DELETE,
                ACI::RESOURCE_SHORTURLS_DELETE,
            ]
        );
    }
}
