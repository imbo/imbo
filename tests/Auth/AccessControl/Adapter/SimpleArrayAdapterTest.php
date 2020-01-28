<?php declare(strict_types=1);
namespace Imbo\Auth\AccessControl\Adapter;

use Imbo\Auth\AccessControl\Adapter\ArrayAdapter;
use Imbo\Resource;
use Imbo\Exception\InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass Imbo\Auth\AccessControl\Adapter\SimpleArrayAdapter
 */
class SimpleArrayAdapterTest extends TestCase {
    public function getAuthConfig() : array {
        $users = [
            'publicKey1' => 'key1',
            'publicKey2' => 'key2',
        ];

        return [
            'no public keys exists' => [[], 'public', null],
            'public key exists' => [$users, 'publicKey2', 'key2'],
            'public key does not exist' => [$users, 'publicKey3', null],
        ];
    }

    /**
     * @dataProvider getAuthConfig
     * @covers ::getPrivateKey
     */
    public function testCanSetKeys(array $users, string $publicKey, ?string $privateKey) : void {
        $accessControl = new SimpleArrayAdapter($users);

        $this->assertSame($privateKey, $accessControl->getPrivateKey($publicKey));
    }

    /**
     * @covers ::__construct
     */
    public function testThrowsOnMultiplePrivateKeysPerPublicKey() : void {
        $this->expectExceptionObject(new InvalidArgumentException(
            'A public key can only have a single private key (as of 2.0.0)',
            500
        ));
        new SimpleArrayAdapter([
            'publicKey' => ['key1', 'key2']
        ]);
    }

    /**
     * @covers ::hasAccess
     */
    public function testLegacyConfigKeysHaveWriteAccess() : void {
        $accessControl = new SimpleArrayAdapter([
            'publicKey' => 'privateKey',
        ]);

        $this->assertTrue(
            $accessControl->hasAccess(
                'publicKey',
                Resource::IMAGES_POST,
                'publicKey'
            )
        );
    }

    /**
     * @covers ::__construct
     */
    public function testExtendsArrayAdapter() : void {
        $accessControl = new SimpleArrayAdapter(['publicKey' => 'key']);
        $this->assertTrue($accessControl instanceof ArrayAdapter);
    }

    /**
     * @covers ::isEmpty
     */
    public function testIsEmpty() : void {
        $accessControl = new SimpleArrayAdapter();
        $this->assertTrue($accessControl->isEmpty());

        $accessControl = new SimpleArrayAdapter(['foo' => 'bar']);
        $this->assertFalse($accessControl->isEmpty());
    }
}
