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

use Imbo\Auth\AccessControl\AccessControlAdapter as Adapter;

/**
 * Array-backed access control adapter
 *
 * @author Espen Hovlandsdal <espen@hovlandsdal.com>
 * @package Core\Auth\AccessControl
 */
class ArrayAdapter extends Adapter implements AccessControlInterface {
    /**
     * Access control definitions
     *
     * @var array
     */
    private $accessList = [];

    private $users = [];

    /**
     * Class constructor
     *
     * @param array $accessList
     */
    public function __construct(array $accessList = []) {
        $this->accessList = $accessList;
    }

    /**
     * {@inheritdoc}
     */
    public function hasAccess($publicKey, $resource, $user = null) {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getUsers(UserLookup\Query $query = null) {
        if ($query === null) {
            $query = new UserLookup\Query();
        }

        return array_slice($this->users, $query->offset() ?: 0, $query->limit());
    }

    /**
     * {@inheritdoc}
     */
    public function userExists($user) {
        return in_array($user, $this->accessList);
    }

    /**
     * For compatibility reasons, where the configuration for Imbo has a set of
     * 'public key' => 'private key' pairs - this method converts that config
     * to an AccessControl-compatible format. Public key will equal the user.
     *
     * @param array $authDetails
     */
    public function setAccessListFromAuth(array $authDetails) {
        foreach ($authDetails as $publicKey => $privateKey) {
            if (!in_array($publicKey, $this->users)) {
                $this->users[] = $publicKey;
            }

            $this->accessList[] = [

            ];
        }
    }
}