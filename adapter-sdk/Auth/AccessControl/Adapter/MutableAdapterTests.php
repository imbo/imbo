<?php declare(strict_types=1);

namespace ImboSDK\Auth\AccessControl\Adapter;

use Imbo\Auth\AccessControl\Adapter\MutableAdapterInterface;
use Imbo\Auth\AccessControl\GroupQuery;
use Imbo\Model\Groups;
use PHPUnit\Framework\TestCase;

abstract class MutableAdapterTests extends TestCase
{
    private MutableAdapterInterface $adapter;

    abstract protected function getAdapter(): MutableAdapterInterface;

    protected function setUp(): void
    {
        $this->adapter = $this->getAdapter();
    }

    public function testReturnsEmptyArrayWhenThereIsNoGroups(): void
    {
        $model = $this->createMock(Groups::class);
        $model->expects($this->once())->method('setHits')->with(0);

        $this->assertSame([], $this->adapter->getGroups(new GroupQuery(), $model));
    }

    public function testCanAddAndFetchGroups(): void
    {
        $this->assertNull($this->adapter->getGroup('g1'), 'Did not expect group to exist');
        $this->assertNull($this->adapter->getGroup('g2'), 'Did not expect group to exist');

        $this->adapter->addResourceGroup('g1', ['images.get', 'images.head']);
        $this->adapter->addResourceGroup('g2', ['status.get']);

        $this->assertSame(['images.get', 'images.head'], $this->adapter->getGroup('g1'));
        $this->assertSame(['status.get'], $this->adapter->getGroup('g2'));

        $model = $this->createMock(Groups::class);
        $model
            ->expects($this->once())
            ->method('setHits')
            ->with(2);

        $groups = $this->adapter->getGroups(new GroupQuery(), $model);

        $this->assertArrayHasKey('g1', $groups);
        $this->assertArrayHasKey('g2', $groups);

        $this->assertSame(['images.get', 'images.head'], $groups['g1']);
        $this->assertSame(['status.get'], $groups['g2']);
    }

    public function testCanCheckIfGroupExists(): void
    {
        $this->assertFalse($this->adapter->groupExists('g1'));
        $this->assertFalse($this->adapter->groupExists('g2'));
        $this->assertFalse($this->adapter->groupExists('g3'));

        $this->adapter->addResourceGroup('g1');
        $this->adapter->addResourceGroup('g2');

        $this->assertTrue($this->adapter->groupExists('g1'));
        $this->assertTrue($this->adapter->groupExists('g2'));
        $this->assertFalse($this->adapter->groupExists('g3'));
    }

    public function testCanUpdateResourceGroup(): void
    {
        $this->adapter->addResourceGroup('g1', ['images.get', 'images.head']);
        $this->adapter->addResourceGroup('g2', ['image.get']);

        $this->assertSame(['images.get', 'images.head'], $this->adapter->getGroup('g1'));
        $this->assertSame(['image.get'], $this->adapter->getGroup('g2'));

        $this->adapter->updateResourceGroup('g1', ['status.get']);
        $this->assertSame(['status.get'], $this->adapter->getGroup('g1'));
        $this->assertSame(['image.get'], $this->adapter->getGroup('g2')); // Has not changed
    }

    public function testCanRemoveGroup(): void
    {
        $this->assertFalse($this->adapter->deleteResourceGroup('g1'));

        $this->assertTrue($this->adapter->addResourceGroup('g1', ['images.get', 'images.head']));
        $this->assertSame(['images.get', 'images.head'], $this->adapter->getGroup('g1'));
        $this->assertTrue($this->adapter->deleteResourceGroup('g1'));
        $this->assertNull($this->adapter->getGroup('g1'));
    }

    public function testCanManipulateKeys(): void
    {
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

    public function testGetAccessRuleThatDoesNotExist(): void
    {
        $this->assertNull($this->adapter->getAccessRule('publickey', 'id'));
    }

    public function testCanManipulateAccessRules(): void
    {
        $this->adapter->addKeyPair('public', 'private');
        $ruleId = $this->adapter->addAccessRule('public', ['resources' => ['image.get'], 'users' => ['user']]);
        $this->assertSame([
            'resources' => ['image.get'],
            'users' => ['user'],
            'id' => $ruleId,
        ], $this->adapter->getAccessRule('public', $ruleId));
        $this->assertTrue($this->adapter->deleteAccessRule('public', $ruleId));
        $this->assertNull($this->adapter->getAccessRule('publickey', $ruleId));
        $this->assertFalse($this->adapter->deleteAccessRule('public', $ruleId));
        $this->assertNull($this->adapter->getAccessRule('public', 'id-does-not-exist'));
    }

    public function testCanGetAccessListForPublicKey(): void
    {
        $this->adapter->addKeyPair('public1', 'private1');
        $this->adapter->addKeyPair('public2', 'private2');

        $id = $this->adapter->addAccessRule('public1', ['resources' => ['image.get'], 'users' => ['user']]);

        $this->assertSame([[
            'resources' => ['image.get'],
            'users' => ['user'],
            'id' => $id,
        ]], $this->adapter->getAccessListForPublicKey('public1'));

        $this->assertSame([], $this->adapter->getAccessListForPublicKey('public2'));
        $this->assertSame([], $this->adapter->getAccessListForPublicKey('unknown-key'));
    }
}
