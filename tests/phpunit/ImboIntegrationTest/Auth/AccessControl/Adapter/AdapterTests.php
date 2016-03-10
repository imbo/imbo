<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace ImboIntegrationTest\Auth\AccessControl\Adapter;

/**
 * @group integration
 */
abstract class AdapterTests extends \PHPUnit_Framework_TestCase {
    /**
     * @var Imbo\Auth\AccessControl\Adapter\MutableAdapterInterface
     */
    private $adapter;

    /**
     * Get the adapter we want to test
     *
     * @return Imbo\Auth\AccessControl\Adapter\MutableAdapterInterface
     */
    abstract protected function getAdapter();

    /**
     * Set up
     */
    public function setUp() {
        $this->adapter = $this->getAdapter();
    }

    /**
     * Tear down
     */
    public function tearDown() {
        $this->adapter = null;
    }

    public function testReturnsEmptyArrayWhenThereIsNoGroups() {
        $model = $this->getMock('Imbo\Model\Groups');
        $model->expects($this->once())->method('setHits')->with(0);

        $this->assertSame([], $this->adapter->getGroups(null, $model));
    }

    public function testCanAddAndFetchGroups() {
        $this->assertFalse($this->adapter->getGroup('g1'));
        $this->assertFalse($this->adapter->getGroup('g2'));

        $this->adapter->addResourceGroup('g1', ['images.get', 'images.head']);
        $this->adapter->addResourceGroup('g2', ['status.get']);

        $this->assertSame(['images.get', 'images.head'], $this->adapter->getGroup('g1'));
        $this->assertSame(['status.get'], $this->adapter->getGroup('g2'));

        $model = $this->getMock('Imbo\Model\Groups');
        $model->expects($this->once())->method('setHits')->with(2);

        $groups = $this->adapter->getGroups(null, $model);

        $this->assertArrayHasKey('g1', $groups);
        $this->assertArrayHasKey('g2', $groups);

        $this->assertSame(['images.get', 'images.head'], $groups['g1']);
        $this->assertSame(['status.get'], $groups['g2']);
    }

    public function testCanCheckIfGroupExists() {
        $this->assertFalse($this->adapter->groupExists('g1'));
        $this->assertFalse($this->adapter->groupExists('g2'));
        $this->assertFalse($this->adapter->groupExists('g3'));

        $this->adapter->addResourceGroup('g1');
        $this->adapter->addResourceGroup('g2');

        $this->assertTrue($this->adapter->groupExists('g1'));
        $this->assertTrue($this->adapter->groupExists('g2'));
        $this->assertFalse($this->adapter->groupExists('g3'));
    }

    public function testCanUpdateResourceGroup() {
        $this->adapter->addResourceGroup('g1', ['images.get', 'images.head']);
        $this->adapter->addResourceGroup('g2', ['image.get']);

        $this->assertSame(['images.get', 'images.head'], $this->adapter->getGroup('g1'));
        $this->assertSame(['image.get'], $this->adapter->getGroup('g2'));

        $this->adapter->updateResourceGroup('g1', ['status.get']);
        $this->assertSame(['status.get'], $this->adapter->getGroup('g1'));
        $this->assertSame(['image.get'], $this->adapter->getGroup('g2')); // Has not changed
    }

    public function testCanRemoveGroup() {
        // Try to delete group that does not exist
        $this->assertFalse($this->adapter->deleteResourceGroup('g1'));

        $this->assertTrue($this->adapter->addResourceGroup('g1', ['images.get', 'images.head']));
        $this->assertSame(['images.get', 'images.head'], $this->adapter->getGroup('g1'));
        $this->assertTrue($this->adapter->deleteResourceGroup('g1'));
        $this->assertSame(false, $this->adapter->getGroup('g1'));
    }

    public function testCanManipulateKeys() {
        // Ensure the public key does not exist
        $this->assertFalse($this->adapter->publicKeyExists('publicKey'));

        // Get private key of a public key that does not exist
        $this->assertNull($this->adapter->getPrivateKey('publicKey'));

        // Try to update the private key of a public key that does not exist
        $this->assertFalse($this->adapter->updatePrivateKey('publicKey', 'privateKey'));

        // Add a key pair
        $this->assertTrue($this->adapter->addKeyPair('publicKey', 'privateKey'));

        // Ensure it exists
        $this->assertTrue($this->adapter->publicKeyExists('publicKey'));

        // Fetch the private key
        $this->assertSame('privateKey', $this->adapter->getPrivateKey('publicKey'));

        // Change the public key
        $this->assertTrue($this->adapter->updatePrivateKey('publicKey', 'newPrivateKey'));

        // Make sure the change occured
        $this->assertSame('newPrivateKey', $this->adapter->getPrivateKey('publicKey'));

        // Delete the key
        $this->assertTrue($this->adapter->deletePublicKey('publicKey'));

        // Ensure the key no longer exists
        $this->assertFalse($this->adapter->publicKeyExists('publicKey'));
        $this->assertNull($this->adapter->getPrivateKey('publicKey'));
    }

    public function testGetAccessRuleThatDoesNotExist() {
        $this->assertNull($this->adapter->getAccessRule('publickey', 'id'));
    }

    public function testCanManipulateAccessRules() {
        $this->adapter->addKeyPair('public', 'private');
        $this->assertInternalType('string', $ruleId = $this->adapter->addAccessRule('public', ['resources' => ['image.get'], 'users' => ['user']]));
        $this->assertSame([
            'id' => $ruleId,
            'resources' => ['image.get'],
            'users' => ['user'],
        ], $this->adapter->getAccessRule('public', $ruleId));
        $this->assertTrue($this->adapter->deleteAccessRule('public', $ruleId));
        $this->assertNull($this->adapter->getAccessRule('publickey', $ruleId));
    }

    /**
     * @expectedException Imbo\Exception\DatabaseException
     * @expectedExceptionCode 500
     * @expectedExceptionMessage Could not delete rule from database
     */
    public function testDeleteAccessRuleWithIdThatDoesNotExist() {
        $this->assertFalse($this->adapter->deleteAccessRule('public', 'asdasd'));
    }
}
