<?php declare(strict_types=1);
namespace Imbo;

use Imbo\Exception\RuntimeException;
use Imbo\Http\Request\Request;
use Imbo\Http\Response\Response;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(Router::class)]
class RouterTest extends TestCase
{
    public function testCanBeATeaPot(): void
    {
        $request = $this->createConfiguredMock(Request::class, [
            'getMethod' => 'BREW',
        ]);
        $this->expectExceptionObject(new RuntimeException('I\'m a teapot', Response::HTTP_I_AM_A_TEAPOT));

        (new Router())->route($request);
    }

    public function testThrowsExceptionOnUnsupportedHttpMethod(): void
    {
        $request = $this->createConfiguredMock(Request::class, [
            'getMethod' => 'TRACE',
        ]);
        $this->expectExceptionObject(new RuntimeException('Unsupported HTTP method', Response::HTTP_NOT_IMPLEMENTED));

        (new Router())->route($request);
    }

    #[DataProvider('getInvalidRoutes')]
    public function testThrowsExceptionWhenNoRouteMatches(string $route): void
    {
        $request = $this->createConfiguredMock(Request::class, [
            'getMethod'   => 'GET',
            'getPathInfo' => $route,
        ]);
        $this->expectExceptionObject(new RuntimeException('Not Found', Response::HTTP_NOT_FOUND));

        (new Router())->route($request);
    }

    #[DataProvider('getValidRoutes')]
    public function testCanMatchValidRoutes(string $route, string $resource, ?string $user = null, ?string $imageIdentifier = null, ?string $extension = null): void
    {
        /** @var Request&MockObject */
        $request = $this->createPartialMock(Request::class, ['getPathInfo', 'getMethod']);
        $request
            ->expects($this->once())
            ->method('getPathInfo')
            ->willReturn($route);
        $request
            ->expects($this->once())
            ->method('getMethod')
            ->willReturn('GET');

        (new Router())->route($request);

        $route = $request->getRoute();

        $this->assertSame($user, $route->get('user'));
        $this->assertSame($imageIdentifier, $route->get('imageIdentifier'));
        $this->assertSame($extension, $route->get('extension'));
        $this->assertSame($resource, (string) $route);
    }

    public function testCanMatchCustomRoute(): void
    {
        /** @var Request&MockObject */
        $request = $this->createPartialMock(Request::class, ['getPathInfo', 'getMethod']);
        $request
            ->expects($this->once())
            ->method('getPathInfo')
            ->willReturn('/custom/akira');
        $request
            ->expects($this->once())
            ->method('getMethod')
            ->willReturn('GET');

        (new Router([
            'custom' => '#^/custom/(?<chars>[a-z]{5})$#',
        ]))->route($request);

        $this->assertSame('akira', $request->getRoute()->get('chars'));
    }

    /**
     * @return array<array{route:string}>
     */
    public static function getInvalidRoutes(): array
    {
        return [
            ['route' => '/foobar'],
            ['route' => '/status.json/'],
            ['route' => '/users/Christer'],
            ['route' => '/users/christer.json/'],
            ['route' => '/users/Christer.json/'],
            ['route' => '/users/christer/images.json/'],
            ['route' => '/users/christer/images/a9b80ed42957fd508c617549cad07d6c/'],
            ['route' => '/users/christer/images/a9b80ed42957fd508c617549cad07d6c.gif/'],
            ['route' => '/users/christer/images/a9b80ed42957fd508c617549cad07d6c/meta.json/'],
            ['route' => '/s/asdfghjk'],
            ['route' => '/s/asdfghj.jpg'],
        ];
    }

    /**
     * @return array<array{route:string,resource:string,user?:?string,imageIdentifier?:?string,extension?:string}>
     */
    public static function getValidRoutes(): array
    {
        return [
            // Global short URL resource
            [
                'route' => '/s/asdfghj',
                'resource' => 'globalshorturl',
            ],
            [
                'route' => '/s/1234567',
                'resource' => 'globalshorturl',
            ],
            [
                'route' => '/s/1234asd',
                'resource' => 'globalshorturl',
            ],
            [
                'route' => '/s/aAbB012',
                'resource' => 'globalshorturl',
            ],

            // Short URLs
            [
                'route' => '/users/christer/images/a9b80ed42957fd508c617549cad07d6c/shorturls',
                'resource' => 'shorturls',
                'user' => 'christer',
                'imageIdentifier' => 'a9b80ed42957fd508c617549cad07d6c',
            ],
            [
                'route' => '/users/christer/images/a9b80ed42957fd508c617549cad07d6c/shorturls/',
                'resource' => 'shorturls',
                'user' => 'christer',
                'imageIdentifier' => 'a9b80ed42957fd508c617549cad07d6c',
            ],
            [
                'route' => '/users/christer/images/a9b80ed42957fd508c617549cad07d6c/shorturls.json',
                'resource' => 'shorturls',
                'user' => 'christer',
                'imageIdentifier' => 'a9b80ed42957fd508c617549cad07d6c',
                'extension' => 'json',
            ],

            // Short URL
            [
                'route' => '/users/christer/images/a9b80ed42957fd508c617549cad07d6c/shorturls/asdfghj',
                'resource' => 'shorturl',
                'user' => 'christer',
                'imageIdentifier' => 'a9b80ed42957fd508c617549cad07d6c',
            ],
            [
                'route' => '/users/christer/images/a9b80ed42957fd508c617549cad07d6c/shorturls/1234567',
                'resource' => 'shorturl',
                'user' => 'christer',
                'imageIdentifier' => 'a9b80ed42957fd508c617549cad07d6c',
            ],
            [
                'route' => '/users/christer/images/a9b80ed42957fd508c617549cad07d6c/shorturls/1234asd',
                'resource' => 'shorturl',
                'user' => 'christer',
                'imageIdentifier' => 'a9b80ed42957fd508c617549cad07d6c',
            ],
            [
                'route' => '/users/christer/images/a9b80ed42957fd508c617549cad07d6c/shorturls/aAbB012',
                'resource' => 'shorturl',
                'user' => 'christer',
                'imageIdentifier' => 'a9b80ed42957fd508c617549cad07d6c',
            ],

            // Status resource
            [
                'route' => '/status',
                'resource' => 'status',
            ],
            [
                'route' => '/status/',
                'resource' => 'status',
            ],
            [
                'route' => '/status.json',
                'resource' => 'status',
                'user' => null,
                'imageIdentifier' => null,
                'extension' => 'json',
            ],

            // User resource
            [
                'route' => '/users/christer',
                'resource' => 'user',
                'user' => 'christer',
            ],
            [
                'route' => '/users/christer/',
                'resource' => 'user',
                'user' => 'christer',
            ],
            [
                'route' => '/users/christer.json',
                'resource' => 'user',
                'user' => 'christer',
                'imageIdentifier' => null,
                'extension' => 'json',
            ],
            [
                'route' => '/users/user_name',
                'resource' => 'user',
                'user' => 'user_name',
            ],
            [
                'route' => '/users/user-name',
                'resource' => 'user',
                'user' => 'user-name',
            ],

            // Images resource
            [
                'route' => '/users/christer/images',
                'resource' => 'images',
                'user' => 'christer',
            ],
            [
                'route' => '/users/christer/images/',
                'resource' => 'images',
                'user' => 'christer',
            ],
            [
                'route' => '/users/christer/images.json',
                'resource' => 'images',
                'user' => 'christer',
                'imageIdentifier' => null,
                'extension' => 'json',
            ],
            [
                'route' => '/users/user_name/images',
                'resource' => 'images',
                'user' => 'user_name',
            ],
            [
                'route' => '/users/user-name/images',
                'resource' => 'images',
                'user' => 'user-name',
            ],

            // Image resource
            [
                'route' => '/users/christer/images/a9b80ed42957fd508c617549cad07d6c',
                'resource' => 'image',
                'user' => 'christer',
                'imageIdentifier' => 'a9b80ed42957fd508c617549cad07d6c',
            ],
            [
                'route' => '/users/christer/images/a9b80ed42957fd508c617549cad07d6c.png',
                'resource' => 'image',
                'user' => 'christer',
                'imageIdentifier' => 'a9b80ed42957fd508c617549cad07d6c',
                'extension' => 'png',
            ],
            [
                'route' => '/users/christer/images/a9b80ed42957fd508c617549cad07d6c.jpg',
                'resource' => 'image',
                'user' => 'christer',
                'imageIdentifier' => 'a9b80ed42957fd508c617549cad07d6c',
                'extension' => 'jpg',
            ],
            [
                'route' => '/users/christer/images/a9b80ed42957fd508c617549cad07d6c.gif',
                'resource' => 'image',
                'user' => 'christer',
                'imageIdentifier' => 'a9b80ed42957fd508c617549cad07d6c',
                'extension' => 'gif',
            ],
            [
                'route' => '/users/user_name/images/a9b80ed42957fd508c617549cad07d6c',
                'resource' => 'image',
                'user' => 'user_name',
                'imageIdentifier' => 'a9b80ed42957fd508c617549cad07d6c',
            ],
            [
                'route' => '/users/user-name/images/a9b80ed42957fd508c617549cad07d6c',
                'resource' => 'image',
                'user' => 'user-name',
                'imageIdentifier' => 'a9b80ed42957fd508c617549cad07d6c',
            ],

            // Metadata resource
            [
                'route' => '/users/christer/images/a9b80ed42957fd508c617549cad07d6c/meta',
                'resource' => 'metadata',
                'user' => 'christer',
                'imageIdentifier' => 'a9b80ed42957fd508c617549cad07d6c',
            ],
            [
                'route' => '/users/christer/images/a9b80ed42957fd508c617549cad07d6c/meta/',
                'resource' => 'metadata',
                'user' => 'christer',
                'imageIdentifier' => 'a9b80ed42957fd508c617549cad07d6c',
            ],
            [
                'route' => '/users/christer/images/a9b80ed42957fd508c617549cad07d6c/meta.json',
                'resource' => 'metadata',
                'user' => 'christer',
                'imageIdentifier' => 'a9b80ed42957fd508c617549cad07d6c',
                'extension' => 'json',
            ],
            [
                'route' => '/users/user_name/images/a9b80ed42957fd508c617549cad07d6c/meta',
                'resource' => 'metadata',
                'user' => 'user_name',
                'imageIdentifier' => 'a9b80ed42957fd508c617549cad07d6c',
            ],
            [
                'route' => '/users/user-name/images/a9b80ed42957fd508c617549cad07d6c/meta',
                'resource' => 'metadata',
                'user' => 'user-name',
                'imageIdentifier' => 'a9b80ed42957fd508c617549cad07d6c',
            ],
            [
                'route' => '/users/christer/images/a9b80ed42957fd508c617549cad07d6c/metadata',
                'resource' => 'metadata',
                'user' => 'christer',
                'imageIdentifier' => 'a9b80ed42957fd508c617549cad07d6c',
            ],
            [
                'route' => '/users/christer/images/a9b80ed42957fd508c617549cad07d6c/metadata/',
                'resource' => 'metadata',
                'user' => 'christer',
                'imageIdentifier' => 'a9b80ed42957fd508c617549cad07d6c',
            ],
            [
                'route' => '/users/christer/images/a9b80ed42957fd508c617549cad07d6c/metadata.json',
                'resource' => 'metadata',
                'user' => 'christer',
                'imageIdentifier' => 'a9b80ed42957fd508c617549cad07d6c',
                'extension' => 'json',
            ],
            [
                'route' => '/users/user_name/images/a9b80ed42957fd508c617549cad07d6c/metadata',
                'resource' => 'metadata',
                'user' => 'user_name',
                'imageIdentifier' => 'a9b80ed42957fd508c617549cad07d6c',
            ],
            [
                'route' => '/users/user-name/images/a9b80ed42957fd508c617549cad07d6c/metadata',
                'resource' => 'metadata',
                'user' => 'user-name',
                'imageIdentifier' => 'a9b80ed42957fd508c617549cad07d6c',
            ],
        ];
    }
}
