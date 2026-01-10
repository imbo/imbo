<?php declare(strict_types=1);

namespace Imbo\CliCommand;

use Exception;
use Imbo\Auth\AccessControl\Adapter\AdapterInterface;
use Imbo\Auth\AccessControl\Adapter\MutableAdapterInterface;
use Imbo\Exception\RuntimeException;
use Imbo\Resource;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

use function count;
use function in_array;
use function is_array;
use function is_string;

#[CoversClass(AddPublicKey::class)]
class AddPublicKeyTest extends TestCase
{
    private Application $application;
    private AddPublicKey $command;
    private MutableAdapterInterface&MockObject $adapter;

    protected function setUp(): void
    {
        $this->adapter = $this->createMock(MutableAdapterInterface::class);

        $this->command = new AddPublicKey();
        $this->command->setConfig([
            'accessControl' => $this->adapter,
        ]);

        $this->application = new Application();
        $this->application->addCommand($this->command);
    }

    #[DataProvider('getInvalidAccessControlConfig')]
    #[AllowMockObjectsWithoutExpectations]
    public function testThrowsWhenAccessControlIsNotValid(array $config, string $errorMessage): void
    {
        if (is_string($config['accessControl'])) {
            /** @var class-string */
            $impl = $config['accessControl'];
            $config['accessControl'] = $this->createMock($impl);
        }

        $command = new AddPublicKey();
        $command->setConfig($config);

        $commandTester = new CommandTester($command);
        $this->expectExceptionObject(new RuntimeException($errorMessage));
        $commandTester->execute(['publicKey' => 'foo']);
    }

    public function testThrowsOnDuplicatePublicKeyName(): void
    {
        $this->adapter
            ->expects($this->once())
            ->method('publicKeyExists')
            ->with('foo')
            ->willReturn(true);

        $commandTester = new CommandTester($this->command);
        $this->expectExceptionObject(new RuntimeException('Public key with that name already exists'));
        $commandTester->execute(['publicKey' => 'foo']);
    }

    public function testWillAskForPrivateKeyIfNotSpecified(): void
    {
        $this->adapter
            ->expects($this->once())
            ->method('addKeyPair')
            ->with('foo', 'ZiePublicKey');

        $commandTester = new CommandTester($this->command);
        $commandTester->setInputs([
            'ZiePublicKey',
            '0',
            'rexxars',
            'n',
        ]);
        $commandTester->execute(['publicKey' => 'foo']);
    }

    public function testWillNotAcceptEmptyUserSpecification(): void
    {
        $this->adapter
            ->expects($this->never())
            ->method('addKeyPair');

        $commandTester = new CommandTester($this->command);
        $commandTester->setInputs([
            '0',
            '',
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/at least one user/');
        $commandTester->execute(['publicKey' => 'foo', 'privateKey' => 'bar']);
    }

    public function testWillNotAcceptEmptyCustomResourceSpecification(): void
    {
        $this->adapter
            ->expects($this->never())
            ->method('addKeyPair');

        $commandTester = new CommandTester($this->command);
        $commandTester->setInputs([
            '4',
            '',
        ]);
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/must specify at least one resource/');
        $commandTester->execute(['publicKey' => 'foo', 'privateKey' => 'bar']);
    }

    public function testContinuesAskingForAclRulesIfUserSaysThereAreMoreRulesToAdd(): void
    {
        $this->adapter
            ->expects($this->exactly(3))
            ->method('addAccessRule')
            ->with('foo', $this->callback(
                /**
                 * @param array{users:array<string>|string,resources:array} $accessRule
                 */
                static function (array $accessRule): bool {
                    /** @var int */
                    static $i = 0;
                    switch ($i++) {
                        case 0:
                            $diff = array_diff($accessRule['resources'], Resource::getReadOnlyResources());

                            return
                                is_array($accessRule['users'])
                                && 2 === count($accessRule['users'])
                                && in_array('espenh', $accessRule['users'])
                                && in_array('kribrabr', $accessRule['users'])
                                && empty($diff)
                            ;

                        case 1:
                            $diff = array_diff($accessRule['resources'], Resource::getReadWriteResources());

                            return
                                is_array($accessRule['users'])
                                && 2 === count($accessRule['users'])
                                && in_array('rexxars', $accessRule['users'])
                                && in_array('kbrabrand', $accessRule['users'])
                                && empty($diff)
                            ;

                        case 2:
                            $diff = array_diff($accessRule['resources'], Resource::getAllResources());

                            return
                                is_string($accessRule['users'])
                                && '*' === $accessRule['users']
                                && empty($diff)
                            ;
                    }

                    return false;
                },
            ));

        $commandTester = new CommandTester($this->command);
        $commandTester->setInputs([
            '0', 'espenh,kribrabr',    'y',
            '1', 'rexxars, kbrabrand', 'y',
            '2', '*',                  'n',
        ]);
        $commandTester->execute(['publicKey' => 'foo', 'privateKey' => 'bar']);

        $this->assertSame(
            3,
            substr_count(
                $commandTester->getDisplay(true),
                'Create more ACL-rules for this public key?',
            ),
        );
    }

    public function testPromptsForListOfSpecificResourcesIfOptionIsSelected(): void
    {
        $allResources = Resource::getAllResources();
        sort($allResources);

        $this->adapter
            ->expects($this->once())
            ->method('addAccessRule')
            ->with('foo', $this->callback(
                /**
                 * @param array{users:string,resources:array} $rule
                 */
                fn (array $rule): bool => '*' === $rule['users']
                    && $rule['resources'][0] === $allResources[0]
                    && $rule['resources'][1] === $allResources[5],
            ));

        $this->adapter
            ->expects($this->once())
            ->method('addKeyPair')
            ->with('foo', 'bar');

        $commandTester = new CommandTester($this->command);
        $commandTester->setInputs(['3', '0,5', '*', 'n']);
        $commandTester->execute(['publicKey' => 'foo', 'privateKey' => 'bar']);
    }

    public function testPromtpsForListOfCustomResourcesIfOptionIsSelected(): void
    {
        $allResources = Resource::getAllResources();
        sort($allResources);

        $this->adapter
            ->expects($this->once())
            ->method('addAccessRule')
            ->with('foo', [
                'resources' => [
                    'foo.read',
                    'bar.write',
                ],
                'users' => '*',
            ]);

        $this->adapter
            ->expects($this->once())
            ->method('addKeyPair')
            ->with('foo', 'bar');

        $commandTester = new CommandTester($this->command);
        $commandTester->setInputs([
            '4',
            'foo.read,bar.write',
            '*',
            'n',
        ]);
        $commandTester->execute(['publicKey' => 'foo', 'privateKey' => 'bar']);
    }

    public function testConfiguresCommand(): void
    {
        $this->adapter
            ->expects($this->never())
            ->method('addKeyPair');

        $this->assertSame('Add a public key', $this->command->getDescription());
        $this->assertSame('add-public-key', $this->command->getName());
        $this->assertSame('Add a public key to the configured access control adapter', $this->command->getHelp());
    }

    /**
     * @return array<array{config:array,errorMessage:string}>
     */
    public static function getInvalidAccessControlConfig(): array
    {
        return [
            [
                'config' => ['accessControl' => new Exception()],
                'errorMessage' => 'Invalid access control adapter',
            ],
            [
                'config' => ['accessControl' => fn (): Exception => new Exception()],
                'errorMessage' => 'Invalid access control adapter',
            ],
            [
                'config' => ['accessControl' => AdapterInterface::class],
                'errorMessage' => 'The configured access control adapter is not mutable',
            ],
        ];
    }
}
