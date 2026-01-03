<?php declare(strict_types=1);
namespace Imbo\EventListener;

use Imbo\EventManager\EventInterface;
use Imbo\EventManager\EventManager;
use Imbo\Exception\RuntimeException;
use Imbo\Http\Request\Request;
use Imbo\Http\Response\Response;
use Imbo\Resource\Stats as StatsResource;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;

#[CoversClass(StatsAccess::class)]
class StatsAccessTest extends ListenerTests
{
    private StatsAccess $listener;
    private EventInterface $event;
    private Request&MockObject $request;

    public function setUp(): void
    {
        $this->request = $this->createMock(Request::class);

        $this->event = $this->createConfiguredStub(EventInterface::class, [
            'getRequest' => $this->request,
        ]);

        $this->listener = new StatsAccess();
    }

    protected function getListener(): StatsAccess
    {
        return $this->listener;
    }

    public function testDoesNotAllowAnyIpAddressPerDefault(): void
    {
        $this->expectExceptionObject(new RuntimeException('Access denied', Response::HTTP_FORBIDDEN));
        $this->request
            ->expects($this->once())
            ->method('getClientIp')
            ->willReturn('1.2.3.4');

        $this->listener->checkAccess($this->event);
    }

    #[DataProvider('getFilterData')]
    public function testCanUseDifferentFilters(string $clientIp, array $allow, bool $hasAccess): void
    {
        $this->request
            ->expects($this->once())
            ->method('getClientIp')
            ->willReturn($clientIp);

        $listener = new StatsAccess([
            'allow' => $allow,
        ]);

        if (!$hasAccess) {
            $this->expectExceptionObject(new RuntimeException('Access denied', 403));
        }

        $listener->checkAccess($this->event);
    }

    /**
     * @see https://github.com/imbo/imbo/issues/249
     */
    #[AllowMockObjectsWithoutExpectations]
    public function testListensToTheSameEventsAsTheStatsResource(): void
    {
        $this->assertSame(
            array_keys(StatsAccess::getSubscribedEvents()),
            array_keys(StatsResource::getSubscribedEvents()),
            'The stats access event listener does not listen to the same events as the stats resource, which it should',
        );
    }

    /**
     * @see https://github.com/imbo/imbo/issues/251
     */
    #[AllowMockObjectsWithoutExpectations]
    public function testHasHigherPriorityThanTheStatsResource(): void
    {
        $eventManager = (new EventManager())
            ->setEventTemplate($this->createConfiguredStub(EventInterface::class, [
                'getRequest' => $this->createStub(Request::class),
            ]))
            ->addEventHandler('statsAccess', function () {
                echo 'stats access';
            })
            ->addCallbacks('statsAccess', StatsAccess::getSubscribedEvents())
            ->addEventHandler('statsResource', function () {
                echo 'stats resource';
            })
            ->addCallbacks('statsResource', StatsResource::getSubscribedEvents());

        $this->expectOutputString('stats accessstats resource');
        $eventManager->trigger('stats.get');
    }

    /**
     * @return array<array{clientIp:string,allow:array<string>,hasAccess:bool}>
     */
    public static function getFilterData(): array
    {
        return [
            'IPv4 in whitelist' => [
                'clientIp' => '127.0.0.1',
                'allow' => ['127.0.0.1'],
                'hasAccess' => true,
            ],
            'IPv4 not in whitelist' => [
                'clientIp' => '127.0.0.2',
                'allow' => ['127.0.0.1'],
                'hasAccess' => false,
            ],
            'IPv4 in whitelist range' => [
                'clientIp' => '192.168.1.10',
                'allow' => ['192.168.1.0/24'],
                'hasAccess' => true,
            ],
            'IPv4 outside of whitelist range' => [
                'clientIp' => '192.168.1.64',
                'allow' => ['192.168.1.32/27'],
                'hasAccess' => false,
            ],
            'IPv6 in whitelist (in short format)' => [
                'clientIp' => '2a00:1b60:1011:0000:0000:0000:0000:1338',
                'allow' => ['2a00:1b60:1011::1338'],
                'hasAccess' => true,
            ],
            'IPv6 in whitelist (in full format)' => [
                'clientIp' => '2a00:1b60:1011:0000:0000:0000:0000:1338',
                'allow' => ['2a00:1b60:1011:0000:0000:0000:0000:1338'],
                'hasAccess' => true,
            ],
            'IPv6 in whitelist range' => [
                'clientIp' => '2001:0db8:0000:0000:0000:0000:0000:0000',
                'allow' => ['2001:db8::/48'],
                'hasAccess' => true,
            ],
            'IPv6 in whitelist range (3)' => [
                'clientIp' => '2001:0db8:0000:0000:0000:0000:0000:0000',
                'allow' => ['2001:db8::/47'],
                'hasAccess' => true,
            ],
            'IPv6 in whitelist range (2)' => [
                'clientIp' => '2001:0db8:0000:0000:0000:0000:0000:0000',
                'allow' => ['2001:db8::/46'],
                'hasAccess' => true,
            ],
            'IPv6 in whitelist range (1)' => [
                'clientIp' => '2001:0db8:0000:0000:0000:0000:0000:0000',
                'allow' => ['2001:db8::/45'],
                'hasAccess' => true,
            ],
            'IPv6 outside of whitelist range' => [
                'clientIp' => '2001:0db9:0000:0000:0000:0000:0000:0000',
                'allow' => ['2001:db8::/48'],
                'hasAccess' => false,
            ],
            'IPv6 in whitelist (in short format in both fields)' => [
                'clientIp' => '2a00:1b60:1011::1338',
                'allow' => ['2a00:1b60:1011::1338'],
                'hasAccess' => true,
            ],
            'Blaclisted IPv4 client and both types in allow' => [
                'clientIp' => '1.2.3.4',
                'allow' => ['127.0.0.1', '::1'],
                'hasAccess' => false,
            ],
            'Whitelitsed IPv6 client and both types in allow' => [
                'clientIp' => '::1',
                'allow' => ['127.0.0.1', '::1'],
                'hasAccess' => true,
            ],
            'Wildcard allows all clients' => [
                'clientIp' => '::1',
                'allow' => ['*'],
                'hasAccess' => true,
            ],
        ];
    }
}
