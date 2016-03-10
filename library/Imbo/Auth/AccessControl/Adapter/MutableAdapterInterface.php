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

/**
 * Mutable access control adapter interface
 *
 * @author Espen Hovlandsdal <espen@hovlandsdal.com>
 * @package Core\Auth\AccessControl\Adapter
 */
interface MutableAdapterInterface extends AdapterInterface {
    /**
     * Add a new public/private key pair
     *
     * @param string $publicKey  Public key to add
     * @param string $privateKey Corresponding private key
     * @return boolean
     */
    function addKeyPair($publicKey, $privateKey);

    /**
     * Delete a public key
     *
     * @param  string $publicKey Public key to delete
     * @return boolean
     */
    function deletePublicKey($publicKey);

    /**
     * Update the private key for a public key
     *
     * @param string $publicKey Public key to update
     * @param string $privateKey Private key to set
     * @return boolean
     */
    function updatePrivateKey($publicKey, $privateKey);

    /**
     * Add a new access rule to the given public key
     *
     * @param  string $publicKey  Public key to add access rule to
     * @param  array  $accessRule Access rule definition
     * @return string Returns a generated access ID
     */
    function addAccessRule($publicKey, array $accessRule);

    /**
     * Delete an access rule
     *
     * @param  string $publicKey Public key the access rule belongs to
     * @param  string $accessId  Access ID of the rule
     * @return boolean
     */
    function deleteAccessRule($publicKey, $accessId);

    /**
     * Add a new group containing the given resources
     *
     * @param string $groupName Group name
     * @param array  $resources Array of resources (eg. 'image.get', 'user.head' etc)
     * @return boolean
     */
    function addResourceGroup($groupName, array $resources = []);

    /**
     * Update resources for an existing resource group
     *
     * @param string $groupName Group to add resources to
     * @param array $resources Array of resources (eg. 'image.get', 'user.head' etc)
     * @param boolean
     */
    function updateResourceGroup($groupName, array $resources);

    /**
     * Delete a resource group
     *
     * @param  string $groupName Group name of the group to delete
     * @return boolean
     */
    function deleteResourceGroup($groupName);
}
