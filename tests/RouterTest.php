<?php declare(strict_types=1);
namespace Imbo;

use Imbo\Exception\RuntimeException;
use Imbo\Http\Request\Request;
use Imbo\Http\Response\Response;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass Imbo\Router
 */
class RouterTest extends TestCase
{
    /**
     * @covers ::route
     */
    public function testCanBeATeaPot(): void
    {
        $request = $this->createConfiguredMock(Request::class, [
            'getMethod' => 'BREW',
        ]);
        $this->expectExceptionObject(new RuntimeException('I\'m a teapot', Response::HTTP_I_AM_A_TEAPOT));

        (new Router())->route($request);
    }

    /**
     * @covers ::route
     */
    public function testThrowsExceptionOnUnsupportedHttpMethod(): void
    {
        $request = $this->createConfiguredMock(Request::class, [
            'getMethod' => 'TRACE',
        ]);
        $this->expectExceptionObject(new RuntimeException('Unsupported HTTP method', Response::HTTP_NOT_IMPLEMENTED));

        (new Router())->route($request);
    }

    public static function getInvalidRoutes(): array
    {
        return [
            ['/foobar'],
            ['/status.json/'],
            ['/users/Christer'],
            ['/users/christer.json/'],
            ['/users/Christer.json/'],
            ['/users/christer/images.json/'],
            ['/users/christer/images/a9b80ed42957fd508c617549cad07d6c/'],
            ['/users/christer/images/a9b80ed42957fd508c617549cad07d6c.gif/'],
            ['/users/christer/images/a9b80ed42957fd508c617549cad07d6c/meta.json/'],
            ['/s/asdfghjk'],
            ['/s/asdfghj.jpg'],
        ];
    }

    /**
     * @dataProvider getInvalidRoutes
     * @covers ::route
     */
    public function testThrowsExceptionWhenNoRouteMatches(string $route): void
    {
        $request = $this->createConfiguredMock(Request::class, [
            'getMethod'   => 'GET',
            'getPathInfo' => $route,
        ]);
        $this->expectExceptionObject(new RuntimeException('Not Found', Response::HTTP_NOT_FOUND));

        (new Router())->route($request);
    }

    public static function getValidRoutes(): array
    {
        return [
            // Global short URL resource
            ['/s/asdfghj', 'globalshorturl'],
            ['/s/1234567', 'globalshorturl'],
            ['/s/1234asd', 'globalshorturl'],
            ['/s/aAbB012', 'globalshorturl'],

            // Short URLs
            ['/users/christer/images/a9b80ed42957fd508c617549cad07d6c/shorturls', 'shorturls', 'christer', 'a9b80ed42957fd508c617549cad07d6c'],
            ['/users/christer/images/a9b80ed42957fd508c617549cad07d6c/shorturls/', 'shorturls', 'christer', 'a9b80ed42957fd508c617549cad07d6c'],
            ['/users/christer/images/a9b80ed42957fd508c617549cad07d6c/shorturls.json', 'shorturls', 'christer', 'a9b80ed42957fd508c617549cad07d6c', 'json'],

            // Short URL
            ['/users/christer/images/a9b80ed42957fd508c617549cad07d6c/shorturls/asdfghj', 'shorturl', 'christer', 'a9b80ed42957fd508c617549cad07d6c'],
            ['/users/christer/images/a9b80ed42957fd508c617549cad07d6c/shorturls/1234567', 'shorturl', 'christer', 'a9b80ed42957fd508c617549cad07d6c'],
            ['/users/christer/images/a9b80ed42957fd508c617549cad07d6c/shorturls/1234asd', 'shorturl', 'christer', 'a9b80ed42957fd508c617549cad07d6c'],
            ['/users/christer/images/a9b80ed42957fd508c617549cad07d6c/shorturls/aAbB012', 'shorturl', 'christer', 'a9b80ed42957fd508c617549cad07d6c'],

            // Status resource
            ['/status', 'status'],
            ['/status/', 'status'],
            ['/status.json', 'status', null, null, 'json'],

            // User resource
            ['/users/christer', 'user', 'christer'],
            ['/users/christer/', 'user', 'christer'],
            ['/users/christer.json', 'user', 'christer', null, 'json'],
            ['/users/user_name', 'user', 'user_name'],
            ['/users/user-name', 'user', 'user-name'],

            // Images resource
            ['/users/christer/images', 'images', 'christer'],
            ['/users/christer/images/', 'images', 'christer'],
            ['/users/christer/images.json', 'images', 'christer', null, 'json'],
            ['/users/user_name/images', 'images', 'user_name'],
            ['/users/user-name/images', 'images', 'user-name'],

            // Image resource
            ['/users/christer/images/a9b80ed42957fd508c617549cad07d6c', 'image', 'christer', 'a9b80ed42957fd508c617549cad07d6c'],
            ['/users/christer/images/a9b80ed42957fd508c617549cad07d6c.png', 'image', 'christer', 'a9b80ed42957fd508c617549cad07d6c', 'png'],
            ['/users/christer/images/a9b80ed42957fd508c617549cad07d6c.jpg', 'image', 'christer', 'a9b80ed42957fd508c617549cad07d6c', 'jpg'],
            ['/users/christer/images/a9b80ed42957fd508c617549cad07d6c.gif', 'image', 'christer', 'a9b80ed42957fd508c617549cad07d6c', 'gif'],
            ['/users/user_name/images/a9b80ed42957fd508c617549cad07d6c', 'image', 'user_name', 'a9b80ed42957fd508c617549cad07d6c'],
            ['/users/user-name/images/a9b80ed42957fd508c617549cad07d6c', 'image', 'user-name', 'a9b80ed42957fd508c617549cad07d6c'],

            // Metadata resource
            ['/users/christer/images/a9b80ed42957fd508c617549cad07d6c/meta', 'metadata', 'christer', 'a9b80ed42957fd508c617549cad07d6c'],
            ['/users/christer/images/a9b80ed42957fd508c617549cad07d6c/meta/', 'metadata', 'christer', 'a9b80ed42957fd508c617549cad07d6c'],
            ['/users/christer/images/a9b80ed42957fd508c617549cad07d6c/meta.json', 'metadata', 'christer', 'a9b80ed42957fd508c617549cad07d6c', 'json'],
            ['/users/user_name/images/a9b80ed42957fd508c617549cad07d6c/meta', 'metadata', 'user_name', 'a9b80ed42957fd508c617549cad07d6c'],
            ['/users/user-name/images/a9b80ed42957fd508c617549cad07d6c/meta', 'metadata', 'user-name', 'a9b80ed42957fd508c617549cad07d6c'],
            ['/users/christer/images/a9b80ed42957fd508c617549cad07d6c/metadata', 'metadata', 'christer', 'a9b80ed42957fd508c617549cad07d6c'],
            ['/users/christer/images/a9b80ed42957fd508c617549cad07d6c/metadata/', 'metadata', 'christer', 'a9b80ed42957fd508c617549cad07d6c'],
            ['/users/christer/images/a9b80ed42957fd508c617549cad07d6c/metadata.json', 'metadata', 'christer', 'a9b80ed42957fd508c617549cad07d6c', 'json'],
            ['/users/user_name/images/a9b80ed42957fd508c617549cad07d6c/metadata', 'metadata', 'user_name', 'a9b80ed42957fd508c617549cad07d6c'],
            ['/users/user-name/images/a9b80ed42957fd508c617549cad07d6c/metadata', 'metadata', 'user-name', 'a9b80ed42957fd508c617549cad07d6c'],
        ];
    }

    /**
     * @dataProvider getValidRoutes
     * @covers ::route
     */
    public function testCanMatchValidRoutes(string $route, string $resource, ?string $user = null, ?string $imageIdentifier = null, ?string $extension = null): void
    {
        $request = $this
            ->getMockBuilder(Request::class)
            ->onlyMethods(['getPathInfo', 'getMethod'])
            ->getMock();

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

    /**
     * @covers ::route
     * @covers ::__construct
     */
    public function testCanMatchCustomRoute(): void
    {
        $request = $this
            ->getMockBuilder(Request::class)
            ->onlyMethods(['getPathInfo', 'getMethod'])
            ->getMock();

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
}
