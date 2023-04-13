<?php declare(strict_types=1);
namespace Imbo\Auth\AccessControl\Adapter;

use Imbo\Exception\InvalidArgumentException;
use Imbo\Http\Response\Response;
use Imbo\Resource;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass Imbo\Auth\AccessControl\Adapter\SimpleArrayAdapter
 */
class SimpleArrayAdapterTest extends TestCase
{
    /**
     * @covers ::__construct
     * @covers ::getExpandedAclList
     */
    public function testThrowsOnMultiplePrivateKeysPerPublicKey(): void
    {
        $this->expectExceptionObject(new InvalidArgumentException(
            'A public key can only have a single private key (as of 2.0.0)',
            Response::HTTP_INTERNAL_SERVER_ERROR,
        ));
        new SimpleArrayAdapter([
            'publicKey' => ['key1', 'key2'],
        ]);
    }

    /**
     * @covers ::__construct
     * @covers ::getExpandedAclList
     */
    public function testLegacyConfigKeysHaveWriteAccess(): void
    {
        $accessControl = new SimpleArrayAdapter([
            'publicKey' => 'privateKey',
        ]);

        $this->assertTrue(
            $accessControl->hasAccess(
                'publicKey',
                Resource::IMAGES_POST,
                'publicKey',
            ),
        );
    }
}
