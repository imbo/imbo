<?php declare(strict_types=1);
namespace Imbo\EventListener;

use Imbo\EventManager\EventInterface;
use Imbo\EventManager\EventManager;
use Imbo\Exception\RuntimeException;
use Imbo\Http\Request\Request;
use Imbo\Http\Response\Response;
use Imbo\Resource\Stats as StatsResource;

/**
 * @coversDefaultClass Imbo\EventListener\StatsAccess
 */
class StatsAccessTest extends ListenerTests
{
    private $listener;
    private $event;
    private $request;

    public function setUp(): void
    {
        $this->request = $this->createMock(Request::class);

        $this->event = $this->createConfiguredMock(EventInterface::class, [
            'getRequest' => $this->request,
        ]);

        $this->listener = new StatsAccess();
    }

    protected function getListener(): StatsAccess
    {
        return $this->listener;
    }

    /**
     * @covers ::checkAccess
     */
    public function testDoesNotAllowAnyIpAddressPerDefault(): void
    {
        $this->expectExceptionObject(new RuntimeException('Access denied', Response::HTTP_FORBIDDEN));
        $this->request
            ->expects($this->once())
            ->method('getClientIp')
            ->willReturn('1.2.3.4');

        $this->listener->checkAccess($this->event);
    }

    public function getFilterData(): array
    {
        return [
            'IPv4 in whitelist' => [
                '127.0.0.1',
                ['127.0.0.1'],
                true,
            ],
            'IPv4 not in whitelist' => [
                '127.0.0.2',
                ['127.0.0.1'],
                false,
            ],
            'IPv4 in whitelist range' => [
                '192.168.1.10',
                ['192.168.1.0/24'],
                true,
            ],
            'IPv4 outside of whitelist range' => [
                '192.168.1.64',
                ['192.168.1.32/27'],
                false,
            ],
            'IPv6 in whitelist (in short format)' => [
                '2a00:1b60:1011:0000:0000:0000:0000:1338',
                ['2a00:1b60:1011::1338'],
                true,
            ],
            'IPv6 in whitelist (in full format)' => [
                '2a00:1b60:1011:0000:0000:0000:0000:1338',
                ['2a00:1b60:1011:0000:0000:0000:0000:1338'],
                true,
            ],
            'IPv6 in whitelist range' => [
                '2001:0db8:0000:0000:0000:0000:0000:0000',
                ['2001:db8::/48'],
                true,
            ],
            'IPv6 in whitelist range (3)' => [
                '2001:0db8:0000:0000:0000:0000:0000:0000',
                ['2001:db8::/47'],
                true,
            ],
            'IPv6 in whitelist range (2)' => [
                '2001:0db8:0000:0000:0000:0000:0000:0000',
                ['2001:db8::/46'],
                true,
            ],
            'IPv6 in whitelist range (1)' => [
                '2001:0db8:0000:0000:0000:0000:0000:0000',
                ['2001:db8::/45'],
                true,
            ],
            'IPv6 outside of whitelist range' => [
                '2001:0db9:0000:0000:0000:0000:0000:0000',
                ['2001:db8::/48'],
                false,
            ],
            'IPv6 in whitelist (in short format in both fields)' => [
                '2a00:1b60:1011::1338',
                ['2a00:1b60:1011::1338'],
                true,
            ],
            'Blaclisted IPv4 client and both types in allow' => [
                '1.2.3.4',
                ['127.0.0.1', '::1'],
                false,
            ],
            'Whitelitsed IPv6 client and both types in allow' => [
                '::1',
                ['127.0.0.1', '::1'],
                true,
            ],
            'Wildcard allows all clients' => [
                '::1',
                ['*'],
                true,
            ],
        ];
    }

    /**
     * @dataProvider getFilterData
     * @covers ::checkAccess
     * @covers ::isIPv6
     * @covers ::isIPv4
     * @covers ::expandIPv6
     * @covers ::isAllowed
     * @covers ::cidrMatch
     * @covers ::cidr6Match
     * @covers ::cidr4Match
     * @covers ::getBinaryMask
     * @covers ::__construct
     * @covers ::expandIPv6InFilters
     */
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
     * @covers ::getSubscribedEvents
     */
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
     * @covers ::getSubscribedEvents
     */
    public function testHasHigherPriorityThanTheStatsResource(): void
    {
        $eventManager = (new EventManager())
            ->setEventTemplate($this->createConfiguredMock(EventInterface::class, [
                'getRequest' => $this->createMock(Request::class),
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
}
