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

use Imbo\Exception\InvalidArgumentException,
    Imbo\Resource;

/**
 * Simple array-backed access control adapter
 *
 * @author Espen Hovlandsdal <espen@hovlandsdal.com>
 * @package Core\Auth\AccessControl\Adapter
 */
class SimpleArrayAdapter extends ArrayAdapter implements AdapterInterface {
    /**
     * Class constructor
     *
     * @param array $accessList Array defining the available public/private keys
     */
    public function __construct(array $accessList = []) {
        parent::__construct($this->getExpandedAclList($accessList));
    }

    /**
     * Returns whether the access control list is empty or not
     *
     * @return boolean True if list is empty, false otherwise
     */
    public function isEmpty() {
        return empty($this->accessList);
    }

    /**
     * Converts public => private key pairs into the array format accepted by ArrayAdapter
     *
     * @param array $accessList
     */
    public function getExpandedAclList(array $accessList) {
        $entries = [];

        foreach ($accessList as $publicKey => $privateKey) {
            if (is_array($privateKey)) {
                throw new InvalidArgumentException('A public key can only have a single private key (as of 2.0.0)');
            }

            $entries[] = [
                'publicKey'  => $publicKey,
                'privateKey' => $privateKey,
                'acl' => [[
                    'resources' => Resource::getReadWriteResources(),
                    'users' => [$publicKey]
                ]]
            ];
        }

        return $entries;
    }
}
