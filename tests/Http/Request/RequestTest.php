<?php declare(strict_types=1);
namespace Imbo\Http\Request;

use Imbo\Exception\InvalidArgumentException;
use Imbo\Http\Response\Response;
use Imbo\Model\Image;
use Imbo\Router\Route;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(Request::class)]
class RequestTest extends TestCase
{
    private Request $request;

    public function setUp(): void
    {
        $this->request = new Request();
    }

    public function testGetTransformationsWithNoTransformationsPresent(): void
    {
        $this->assertEquals([], $this->request->getTransformations());
    }

    public function testGetTransformationsWithCorrectOrder(): void
    {
        $query = [
            't' => [
                'flipHorizontally',
                'flipVertically',
            ],
        ];

        $request = new Request($query);
        $transformations = $request->getTransformations();
        $this->assertEquals('flipHorizontally', $transformations[0]['name']);
        $this->assertEquals('flipVertically', $transformations[1]['name']);
    }

    public function testGetTransformations(): void
    {
        $query = [
            't' => [
                // Valid transformations with all options
                'border:color=fff,width=2,height=2,mode=inline',
                'compress:level=90',
                'crop:x=1,y=2,width=3,height=4',
                'resize:width=100,height=100',

                // Transformations with no options
                'flipHorizontally',
                'flipVertically',

                // The same transformation can be applied multiple times
                'resize:width=50,height=75',

                // We handle zero-values appropriately
                'border:color=bf1942,height=100,mode=outbound,width=0',
                'border:color=000,height=5,width=0,mode=outbound',
            ],
        ];

        $request = new Request($query);
        $transformations = $request->getTransformations();
        $this->assertCount(count($query['t']), $transformations);

        $this->assertEquals(['color' => 'fff', 'width' => 2, 'height' => 2, 'mode' => 'inline'], $transformations[0]['params']);
        $this->assertEquals(['level' => '90'], $transformations[1]['params']);
        $this->assertEquals(['x' => 1, 'y' => 2, 'width' => 3, 'height' => 4], $transformations[2]['params']);
        $this->assertEquals(['width' => 100, 'height' => 100], $transformations[3]['params']);
        $this->assertEquals([], $transformations[4]['params']);
        $this->assertEquals([], $transformations[5]['params']);
        $this->assertEquals(['width' => 50, 'height' => 75], $transformations[6]['params']);

        $this->assertEquals([
            'color' => 'bf1942',
            'height' => 100,
            'mode' => 'outbound',
            'width' => 0,
        ], $transformations[7]['params']);

        $this->assertEquals([
            'color' => '000',
            'height' => 5,
            'width' => 0,
            'mode' => 'outbound',
        ], $transformations[8]['params']);
    }

    public function testSetGetImageIdentifier(): void
    {
        $identifier = 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa';
        $this->assertNull($this->request->getImageIdentifier());

        $route = new Route();
        $this->request->setRoute($route);

        $this->assertNull($this->request->getImageIdentifier());
        $route->set('imageIdentifier', $identifier);
        $this->assertSame($identifier, $this->request->getImageIdentifier());
    }

    public function testSetGetExtension(): void
    {
        $extension = 'jpg';
        $this->assertNull($this->request->getExtension());

        $route = new Route();
        $this->request->setRoute($route);

        $this->assertNull($this->request->getExtension());
        $route->set('extension', $extension);
        $this->assertSame($extension, $this->request->getExtension());
    }

    public function testSetGetUser(): void
    {
        $user = 'christer';
        $this->assertNull($this->request->getUser());

        $route = new Route();
        $this->request->setRoute($route);

        $this->assertNull($this->request->getUser());
        $route->set('user', $user);
        $this->assertSame($user, $this->request->getUser());
    }

    #[DataProvider('getUsers')]
    public function testGetUsers(?string $routeUser, ?array $queryUsers, array $expectedUsers): void
    {
        $route = new Route();
        if (null !== $routeUser) {
            $route->set('user', $routeUser);
        }


        $this->request->setRoute($route);
        if (null !== $queryUsers) {
            $this->request->query->set('users', $queryUsers);
        }

        $this->assertSame(
            $expectedUsers,
            $this->request->getUsers(),
        );
    }

    public function testSetGetPublicKeyThroughRoute(): void
    {
        $pubkey = 'pubkey';
        $this->assertNull($this->request->getPublicKey());

        $route = new Route();
        $this->request->setRoute($route);

        $this->assertNull($this->request->getPublicKey());
        $route->set('user', $pubkey);
        $this->assertSame($pubkey, $this->request->getPublicKey());
    }

    public function testSetGetPublicKeyThroughQuery(): void
    {
        $pubkey = 'pubkey';
        $this->assertNull($this->request->getPublicKey());

        $this->request->query->set('publicKey', $pubkey);
        $this->assertSame($pubkey, $this->request->getPublicKey());
    }

    public function testSetGetPublicKeyThroughHeader(): void
    {
        $pubkey = 'pubkey';
        $this->assertNull($this->request->getPublicKey());

        $this->request->headers->set('X-Imbo-PublicKey', $pubkey);
        $this->assertSame($pubkey, $this->request->getPublicKey());
    }

    public function testCanSetAndGetAnImage(): void
    {
        $image = $this->createMock(Image::class);
        $this->assertSame($this->request, $this->request->setImage($image));
        $this->assertSame($image, $this->request->getImage());
    }

    public function testCanSetAndGetARoute(): void
    {
        $this->assertNull($this->request->getRoute());
        $route = $this->createMock(Route::class);
        $this->assertSame($this->request, $this->request->setRoute($route));
        $this->assertSame($route, $this->request->getRoute());
    }

    public function testRequiresTransformationsToBeSpecifiedAsAnArray(): void
    {
        $request = new Request([
            't' => 'desaturate',
        ]);
        $this->expectExceptionObject(new InvalidArgumentException(
            'Transformations must be specifed as an array',
            Response::HTTP_BAD_REQUEST,
        ));
        $request->getTransformations();
    }

    public function testDoesNotGenerateWarningWhenTransformationIsNotAString(): void
    {
        $query = [
            't' => [
                [
                    'flipHorizontally',
                    'flipVertically',
                ],
            ],
        ];

        $request = new Request($query);
        $this->expectExceptionObject(new InvalidArgumentException(
            'Invalid transformation',
            Response::HTTP_BAD_REQUEST,
        ));
        $request->getTransformations();
    }

    #[DataProvider('getQueryStrings')]
    public function testGetRawUriDecodesUri(string $in, string $out): void
    {
        $request = new Request([], [], [], [], [], [
            'SERVER_NAME' => 'imbo',
            'SERVER_PORT' => 80,
            'QUERY_STRING' => $in,
        ]);

        $uri = $request->getRawUri();
        $this->assertSame($out, substr($uri, (int) strpos($uri, '?') + 1));
    }

    /**
     * @return array<string,array{routeUser:?string,queryUsers:?array<string>,expectedUsers:array<string>}>
     */
    public static function getUsers(): array
    {
        return [
            'no user' => [
                'routeUser' => null,
                'queryUsers' => null,
                'expectedUsers' => [],
            ],
            'user only in route' => [
                'routeUser' => 'routeUser',
                'queryUsers' => null,
                'expectedUsers' => [
                    'routeUser',
                ],
            ],
            'user only in query' => [
                'routeUser' => null,
                'queryUsers' => [
                    'user1',
                    'user2',
                ],
                'expectedUsers' => [
                    'user1',
                    'user2',
                ],
            ],
            'user in both route and query' => [
                'routeUser' => 'routeUser',
                'queryUsers' => [
                    'user1',
                    'user2',
                ],
                'expectedUsers' => [
                    'routeUser',
                    'user1',
                    'user2',
                ],
            ],
        ];
    }

    /**
     * @return array<string,array{in:string,out:string}>
     */
    public static function getQueryStrings(): array
    {
        return [
            'transformation with params' => [
                'in' => 't[]=thumbnail:width=100',
                'out' => 't[]=thumbnail:width=100',
            ],
            'transformation with params, encoded' => [
                'in' => 't%5B0%5D%3Dthumbnail%3Awidth%3D100',
                'out' => 't[0]=thumbnail:width=100',
            ],
        ];
    }
}
