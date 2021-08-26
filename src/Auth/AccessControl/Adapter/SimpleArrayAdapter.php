<?php
namespace Imbo\Auth\AccessControl\Adapter;

use Imbo\Exception\InvalidArgumentException;
use Imbo\Resource;

/**
 * Simple array-backed access control adapter
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
     * Converts public => private key pairs into the array format accepted by ArrayAdapter
     *
     * @param array $accessList
     * @throws InvalidArgumentException
     * @return array
     */
    private function getExpandedAclList(array $accessList): array {
        $entries = [];

        foreach ($accessList as $publicKey => $privateKey) {
            if (is_array($privateKey)) {
                throw new InvalidArgumentException(
                    'A public key can only have a single private key (as of 2.0.0)',
                    500
                );
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
