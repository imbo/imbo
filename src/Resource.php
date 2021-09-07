<?php declare(strict_types=1);
namespace Imbo;

class Resource
{
    public const GROUPS_GET              = 'groups.get';
    public const GROUPS_HEAD             = 'groups.head';
    public const GROUPS_OPTIONS          = 'groups.options';

    public const GROUP_GET               = 'group.get';
    public const GROUP_HEAD              = 'group.head';
    public const GROUP_PUT               = 'group.put';
    public const GROUP_DELETE            = 'group.delete';
    public const GROUP_OPTIONS           = 'group.options';

    public const KEYS_PUT                = 'keys.put';
    public const KEYS_HEAD               = 'keys.head';
    public const KEYS_DELETE             = 'keys.delete';
    public const KEYS_OPTIONS            = 'keys.options';

    public const ACCESS_RULE_GET         = 'accessrule.get';
    public const ACCESS_RULE_HEAD        = 'accessrule.head';
    public const ACCESS_RULE_DELETE      = 'accessrule.delete';
    public const ACCESS_RULE_OPTIONS     = 'accessrule.options';

    public const ACCESS_RULES_GET        = 'accessrules.get';
    public const ACCESS_RULES_HEAD       = 'accessrules.head';
    public const ACCESS_RULES_POST       = 'accessrules.post';
    public const ACCESS_RULES_OPTIONS    = 'accessrules.options';

    public const USER_GET                = 'user.get';
    public const USER_HEAD               = 'user.head';
    public const USER_OPTIONS            = 'user.options';

    public const IMAGE_GET               = 'image.get';
    public const IMAGE_HEAD              = 'image.head';
    public const IMAGE_DELETE            = 'image.delete';
    public const IMAGE_OPTIONS           = 'image.options';

    public const IMAGES_GET              = 'images.get';
    public const IMAGES_HEAD             = 'images.head';
    public const IMAGES_POST             = 'images.post';
    public const IMAGES_OPTIONS          = 'images.options';

    public const GLOBAL_IMAGES_GET       = 'globalimages.get';
    public const GLOBAL_IMAGES_HEAD      = 'globalimages.head';
    public const GLOBAL_IMAGES_OPTIONS   = 'globalimages.options';

    public const METADATA_GET            = 'metadata.get';
    public const METADATA_HEAD           = 'metadata.head';
    public const METADATA_PUT            = 'metadata.put';
    public const METADATA_POST           = 'metadata.post';
    public const METADATA_DELETE         = 'metadata.delete';
    public const METADATA_OPTIONS        = 'metadata.options';

    public const SHORTURL_GET            = 'shorturl.get';
    public const SHORTURL_HEAD           = 'shorturl.head';
    public const SHORTURL_DELETE         = 'shorturl.delete';
    public const SHORTURL_OPTIONS        = 'shorturl.options';

    public const SHORTURLS_POST          = 'shorturls.post';
    public const SHORTURLS_DELETE        = 'shorturls.delete';
    public const SHORTURLS_OPTIONS       = 'shorturls.options';

    /**
     * Returns a list of resources which should be accessible for read-only public keys
     *
     * @return array<string>
     */
    final public static function getReadOnlyResources(): array
    {
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
     * @return array<string>
     */
    final public static function getReadWriteResources(): array
    {
        return array_merge(
            self::getReadOnlyResources(),
            [
                self::IMAGE_DELETE,
                self::IMAGES_POST,

                self::METADATA_POST,
                self::METADATA_DELETE,
                self::METADATA_PUT,

                self::SHORTURL_DELETE,

                self::SHORTURLS_POST,
                self::SHORTURLS_DELETE,
            ],
        );
    }

    /**
     * Returns a list of all resources available, including those which involves access control
     *
     * @return array<string>
     */
    final public static function getAllResources(): array
    {
        return array_merge(
            self::getReadWriteResources(),
            [
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
            ],
        );
    }
}
