<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace Imbo;

/**
 * Resource class
 *
 * @author Espen Hovlandsdal <espen@hovlandsdal.com>
 * @package Core
 */
class Resource {
    const GROUPS_GET              = 'groups.get';
    const GROUPS_HEAD             = 'groups.head';
    const GROUPS_OPTIONS          = 'groups.options';

    const GROUP_GET               = 'group.get';
    const GROUP_HEAD              = 'group.head';
    const GROUP_PUT               = 'group.put';
    const GROUP_DELETE            = 'group.delete';
    const GROUP_OPTIONS           = 'group.options';

    const KEYS_PUT                = 'keys.put';
    const KEYS_HEAD               = 'keys.head';
    const KEYS_DELETE             = 'keys.delete';
    const KEYS_OPTIONS            = 'keys.options';

    const ACCESS_RULE_GET         = 'accessrule.get';
    const ACCESS_RULE_HEAD        = 'accessrule.head';
    const ACCESS_RULE_DELETE      = 'accessrule.delete';
    const ACCESS_RULE_OPTIONS     = 'accessrule.options';

    const ACCESS_RULES_GET        = 'accessrules.get';
    const ACCESS_RULES_HEAD       = 'accessrules.head';
    const ACCESS_RULES_POST       = 'accessrules.post';
    const ACCESS_RULES_OPTIONS    = 'accessrules.options';

    const USER_GET                = 'user.get';
    const USER_HEAD               = 'user.head';
    const USER_OPTIONS            = 'user.options';

    const IMAGE_GET               = 'image.get';
    const IMAGE_HEAD              = 'image.head';
    const IMAGE_DELETE            = 'image.delete';
    const IMAGE_OPTIONS           = 'image.options';

    const IMAGES_GET              = 'images.get';
    const IMAGES_HEAD             = 'images.head';
    const IMAGES_POST             = 'images.post';
    const IMAGES_OPTIONS          = 'images.options';

    const GLOBAL_IMAGES_GET       = 'globalimages.get';
    const GLOBAL_IMAGES_HEAD      = 'globalimages.head';
    const GLOBAL_IMAGES_OPTIONS   = 'globalimages.options';

    const METADATA_GET            = 'metadata.get';
    const METADATA_HEAD           = 'metadata.head';
    const METADATA_PUT            = 'metadata.put';
    const METADATA_POST           = 'metadata.post';
    const METADATA_DELETE         = 'metadata.delete';
    const METADATA_OPTIONS        = 'metadata.options';

    const SHORTURL_GET            = 'shorturl.get';
    const SHORTURL_HEAD           = 'shorturl.head';
    const SHORTURL_DELETE         = 'shorturl.delete';
    const SHORTURL_OPTIONS        = 'shorturl.options';

    const SHORTURLS_POST          = 'shorturls.post';
    const SHORTURLS_DELETE        = 'shorturls.delete';
    const SHORTURLS_OPTIONS       = 'shorturls.options';

    /**
     * Returns a list of resources which should be accessible for read-only public keys
     *
     * @return array
     */
    final public static function getReadOnlyResources() {
        return [
            self::USER_GET,
            self::USER_HEAD,
            self::USER_OPTIONS,

            self::IMAGE_GET,
            self::IMAGE_HEAD,
            self::IMAGE_OPTIONS,

            self::IMAGES_GET,
            self::IMAGES_HEAD,
            self::IMAGES_OPTIONS,

            self::METADATA_GET,
            self::METADATA_HEAD,
            self::METADATA_OPTIONS,

            self::SHORTURL_GET,
            self::SHORTURL_HEAD,
            self::SHORTURL_OPTIONS,

            self::GLOBAL_IMAGES_GET,
            self::GLOBAL_IMAGES_HEAD,
            self::GLOBAL_IMAGES_OPTIONS,

            self::SHORTURLS_OPTIONS,
        ];
    }

    /**
     * Returns a list of resources which should be accessible for read+write public keys
     *
     * @return array
     */
    final public static function getReadWriteResources() {
        return array_merge(
            self::getReadOnlyResources(), [
                self::IMAGE_DELETE,
                self::IMAGES_POST,

                self::METADATA_POST,
                self::METADATA_DELETE,
                self::METADATA_PUT,

                self::SHORTURL_DELETE,

                self::SHORTURLS_POST,
                self::SHORTURLS_DELETE,
            ]
        );
    }

    /**
     * Returns a list of all resources available, including those which involves access control
     *
     * @return array
     */
    final public static function getAllResources() {
        return array_merge(
            self::getReadWriteResources(), [
                self::KEYS_PUT,
                self::KEYS_HEAD,
                self::KEYS_DELETE,
                self::KEYS_OPTIONS,

                self::ACCESS_RULE_GET,
                self::ACCESS_RULE_HEAD,
                self::ACCESS_RULE_DELETE,
                self::ACCESS_RULE_OPTIONS,

                self::ACCESS_RULES_GET,
                self::ACCESS_RULES_HEAD,
                self::ACCESS_RULES_POST,
                self::ACCESS_RULES_OPTIONS,

                self::GROUPS_GET,
                self::GROUPS_HEAD,
                self::GROUPS_OPTIONS,

                self::GROUP_GET,
                self::GROUP_HEAD,
                self::GROUP_PUT,
                self::GROUP_DELETE,
                self::GROUP_OPTIONS,
            ]
        );
    }
}
