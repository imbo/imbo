<?php declare(strict_types=1);
namespace Imbo\Auth\AccessControl\Adapter;

use Imbo\Auth\AccessControl\GroupQuery;
use Imbo\Model\Groups as GroupsModel;

/**
 * Access control interface
 */
interface AdapterInterface
{
    /**
     * Get a list of users the public key has access for on a given resource
     *
     * @param string $publicKey Public key to check access for
     * @param string $resource  Resource identifier (e.g. image.get, images.post)
     * @return array<string> List of users the public key kan access the given resource for
     */
    public function getUsersForResource(string $publicKey, string $resource): array;

    /**
     * Check if a given public key has access to a given resource
     *
     * @param string $publicKey Public key to check access for
     * @param string $resource Resource identifier (e.g. image.get, images.post)
     * @param string $user Optional user which the resource belongs to
     * @return bool True if public key has access, false otherwise
     */
    public function hasAccess(string $publicKey, string $resource, string $user = null): bool;

    /**
     * Fetch a list of available resource groups
     *
     * @param GroupQuery $query A query object used to filter the groups returned
     * @param GroupsModel $model Groups model to populate total number of hits with
     * @return array<string,array<string>>
     */
    public function getGroups(GroupQuery $query, GroupsModel $model): array;

    /**
     * Check whether or not a group exists
     *
     * @param string $groupName Name of the group
     * @return bool
     */
    public function groupExists(string $groupName): bool;

    /**
     * Fetch a resource group with the given name
     *
     * @param string $groupName Name of the group
     * @return array<string> Array of resources the group consists of
     */
    public function getGroup(string $groupName): ?array;

    /**
     * Return the private key for a given public key
     *
     * @param string $publicKey The public key to fetch matching private key for
     * @return ?string Returns the private key for the public key
     */
    public function getPrivateKey(string $publicKey): ?string;

    /**
     * Get whether a public key exists or not
     *
     * @param string $publicKey Public key to check
     * @return bool
     */
    public function publicKeyExists(string $publicKey): bool;

    /**
     * Get the access control list for a given public key
     *
     * @param string $publicKey
     * @return array<array{id:int|string,users:'*'|array<string>,resources:array<string>,group?:string}>
     */
    public function getAccessListForPublicKey(string $publicKey): array;

    /**
     * Get an access rule by id
     *
     * @param string $publicKey    Public key to add access rule to
     * @param int|string $accessRuleId Access rule id
     * @return array{id:int|string,users:'*'|array<string>,resources:array<string>,group?:string} Access rule
     */
    public function getAccessRule(string $publicKey, int|string $accessRuleId): ?array;
}
