<?php declare(strict_types=1);
namespace Imbo\Auth\AccessControl\Adapter;

/**
 * Mutable access control adapter interface
 */
interface MutableAdapterInterface extends AdapterInterface {
    /**
     * Add a new public/private key pair
     *
     * @param string $publicKey  Public key to add
     * @param string $privateKey Corresponding private key
     * @return bool
     */
    function addKeyPair(string $publicKey, string $privateKey): bool;

    /**
     * Delete a public key
     *
     * @param string $publicKey Public key to delete
     * @return bool
     */
    function deletePublicKey(string $publicKey): bool;

    /**
     * Update the private key for a public key
     *
     * @param string $publicKey Public key to update
     * @param string $privateKey Private key to set
     * @return bool
     */
    function updatePrivateKey(string $publicKey, string $privateKey): bool;

    /**
     * Add a new access rule to the given public key
     *
     * @param string $publicKey  Public key to add access rule to
     * @param array{resources:array<string>,users:array<string>} $accessRule Access rule definition
     * @return string Returns a generated access rule ID
     */
    function addAccessRule(string $publicKey, array $accessRule): string;

    /**
     * Delete an access rule
     *
     * @param string $publicKey Public key the access rule belongs to
     * @param string $accessRuleId ID of the access rule
     * @return bool
     */
    function deleteAccessRule(string $publicKey, string $accessRuleId): bool;

    /**
     * Add a new group containing the given resources
     *
     * @param string $groupName Group name
     * @param array<string> $resources Array of resources (eg. 'image.get', 'user.head' etc)
     * @return bool
     */
    function addResourceGroup(string $groupName, array $resources = []): bool;

    /**
     * Update resources for an existing resource group
     *
     * @param string $groupName Group to add resources to
     * @param array<string> $resources Array of resources (eg. 'image.get', 'user.head' etc)
     * @param bool
     */
    function updateResourceGroup(string $groupName, array $resources): bool;

    /**
     * Delete a resource group
     *
     * @param string $groupName Group name of the group to delete
     * @return bool
     */
    function deleteResourceGroup(string $groupName): bool;
}
