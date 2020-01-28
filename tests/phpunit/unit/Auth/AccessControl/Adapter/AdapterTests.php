<?php declare(strict_types=1);
namespace Imbo\Auth\AccessControl\Adapter;

use Imbo\Exception\DatabaseException;
use Imbo\Model\Groups;
use PHPUnit\Framework\TestCase;

abstract class AdapterTests extends TestCase {
    private $adapter;

    /**
     * Get the adapter we want to test
     *
     * @return MutableAdapterInterface
     */
    abstract protected function getAdapter();

    public function setUp() : void {
        $this->adapter = $this->getAdapter();
    }

    /**
     * @covers ::getGroups
     */
    public function testReturnsEmptyArrayWhenThereIsNoGroups() : void {
        $model = $this->createMock(Groups::class);
        $model->expects($this->once())->method('setHits')->with(0);

        $this->assertSame([], $this->adapter->getGroups(null, $model));
    }

    /**
     * @covers ::getGroup
     * @covers ::addResourceGroup
     * @covers ::getGroups
     */
    public function testCanAddAndFetchGroups() : void {
        $this->assertFalse($this->adapter->getGroup('g1'), 'Did not expect group to exist');
        $this->assertFalse($this->adapter->getGroup('g2'), 'Did not expect group to exist');

        $this->adapter->addResourceGroup('g1', ['images.get', 'images.head']);
        $this->adapter->addResourceGroup('g2', ['status.get']);

        $this->assertSame(['images.get', 'images.head'], $this->adapter->getGroup('g1'));
        $this->assertSame(['status.get'], $this->adapter->getGroup('g2'));

        $model = $this->createMock(Groups::class);
        $model
            ->expects($this->once())
            ->method('setHits')
            ->with(2);

        $groups = $this->adapter->getGroups(null, $model);

        $this->assertArrayHasKey('g1', $groups);
        $this->assertArrayHasKey('g2', $groups);

        $this->assertSame(['images.get', 'images.head'], $groups['g1']);
        $this->assertSame(['status.get'], $groups['g2']);
    }

    /**
     * @covers ::groupExists
     * @covers ::addResourceGroup
     */
    public function testCanCheckIfGroupExists() : void {
        $this->assertFalse($this->adapter->groupExists('g1'));
        $this->assertFalse($this->adapter->groupExists('g2'));
        $this->assertFalse($this->adapter->groupExists('g3'));

        $this->adapter->addResourceGroup('g1');
        $this->adapter->addResourceGroup('g2');

        $this->assertTrue($this->adapter->groupExists('g1'));
        $this->assertTrue($this->adapter->groupExists('g2'));
        $this->assertFalse($this->adapter->groupExists('g3'));
    }

    /**
     * @covers ::addResourceGroup
     * @covers ::getGroup
     * @covers ::updateResourceGroup
     */
    public function testCanUpdateResourceGroup() : void {
        $this->adapter->addResourceGroup('g1', ['images.get', 'images.head']);
        $this->adapter->addResourceGroup('g2', ['image.get']);

        $this->assertSame(['images.get', 'images.head'], $this->adapter->getGroup('g1'));
        $this->assertSame(['image.get'], $this->adapter->getGroup('g2'));

        $this->adapter->updateResourceGroup('g1', ['status.get']);
        $this->assertSame(['status.get'], $this->adapter->getGroup('g1'));
        $this->assertSame(['image.get'], $this->adapter->getGroup('g2')); // Has not changed
    }

    /**
     * @covers ::deleteResourceGroup
     * @covers ::addResourceGroup
     * @covers ::getGroup
     */
    public function testCanRemoveGroup() : void {
        $this->assertFalse($this->adapter->deleteResourceGroup('g1'));

        $this->assertTrue($this->adapter->addResourceGroup('g1', ['images.get', 'images.head']));
        $this->assertSame(['images.get', 'images.head'], $this->adapter->getGroup('g1'));
        $this->assertTrue($this->adapter->deleteResourceGroup('g1'));
        $this->assertSame(false, $this->adapter->getGroup('g1'));
    }

    /**
     * @covers ::publicKeyExists
     * @covers ::getPrivateKey
     * @covers ::updatePrivateKey
     * @covers ::addKeyPair
     * @covers ::deletePublicKey
     */
    public function testCanManipulateKeys() : void {
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

    /**
     * @covers ::getAccessRule
     */
    public function testGetAccessRuleThatDoesNotExist() : void {
        $this->assertNull($this->adapter->getAccessRule('publickey', 'id'));
    }

    /**
     * @covers ::addKeyPair
     * @covers ::addAccessRule
     * @covers ::getAccessRule
     * @covers ::deleteAccessRule
     */
    public function testCanManipulateAccessRules() : void {
        $this->adapter->addKeyPair('public', 'private');
        $this->assertIsString($ruleId = $this->adapter->addAccessRule('public', ['resources' => ['image.get'], 'users' => ['user']]));
        $this->assertSame([
            'id' => $ruleId,
            'resources' => ['image.get'],
            'users' => ['user'],
        ], $this->adapter->getAccessRule('public', $ruleId));
        $this->assertTrue($this->adapter->deleteAccessRule('public', $ruleId));
        $this->assertNull($this->adapter->getAccessRule('publickey', $ruleId));
    }

    /**
     * @covers ::deleteAccessRule
     */
    public function testDeleteAccessRuleWithIdThatDoesNotExist() : void {
        $this->expectExceptionObject(new DatabaseException('Could not delete rule from database', 500));
        $this->assertFalse($this->adapter->deleteAccessRule('public', 'asdasd'));
    }
}
