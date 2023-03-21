<?php declare(strict_types=1);
namespace Imbo\CliCommand;

use Exception;
use Imbo\Auth\AccessControl\Adapter\AdapterInterface;
use Imbo\Auth\AccessControl\Adapter\MutableAdapterInterface;
use Imbo\Exception\RuntimeException;
use Imbo\Resource;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @coversDefaultClass Imbo\CliCommand\AddPublicKey
 */
class AddPublicKeyTest extends TestCase
{
    private $application;
    private $command;
    private $adapter;

    public function setUp(): void
    {
        $this->adapter = $this->createMock(MutableAdapterInterface::class);

        $this->command = new AddPublicKey();
        $this->command->setConfig([
            'accessControl' => $this->adapter,
        ]);

        $this->application = new Application();
        $this->application->add($this->command);
    }

    public static function getInvalidAccessControlConfig(): array
    {
        return [
            [
                ['accessControl' => new Exception()],
                'Invalid access control adapter',
            ],
            [
                ['accessControl' => fn (): Exception => new Exception()],
                'Invalid access control adapter',
            ],
            [
                ['accessControl' => AdapterInterface::class],
                'The configured access control adapter is not mutable',
            ],
        ];
    }

    /**
     * @dataProvider getInvalidAccessControlConfig
     * @covers ::getAclAdapter
     */
    public function testThrowsWhenAccessControlIsNotValid(array $config, string $errorMessage): void
    {
        if (is_string($config['accessControl'])) {
            $config['accessControl'] = $this->createMock($config['accessControl']);
        }

        $command = new AddPublicKey();
        $command->setConfig($config);

        $commandTester = new CommandTester($command);
        $this->expectExceptionObject(new RuntimeException($errorMessage));
        $commandTester->execute(['publicKey' => 'foo']);
    }

    /**
     * @covers ::execute
     * @covers ::getAclAdapter
     */
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

    /**
     * @covers ::execute
     * @covers ::askForPrivateKey
     */
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

    /**
     * @covers ::askForUsers
     */
    public function testWillNotAcceptEmptyUserSpecification(): void
    {
        $commandTester = new CommandTester($this->command);
        $commandTester->setInputs([
            '0',
            '',
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/at least one user/');
        $commandTester->execute(['publicKey' => 'foo', 'privateKey' => 'bar']);
    }

    /**
     * @covers ::askForResources
     * @covers ::askForCustomResources
     */
    public function testWillNotAcceptEmptyCustomResourceSpecification(): void
    {
        $commandTester = new CommandTester($this->command);
        $commandTester->setInputs([
            '4',
            '',
        ]);
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/must specify at least one resource/');
        $commandTester->execute(['publicKey' => 'foo', 'privateKey' => 'bar']);
    }

    /**
     * @covers ::execute
     * @covers ::askForAnotherAclRule
     * @covers ::askForResources
     * @covers ::askForUsers
     */
    public function testContinuesAskingForAclRulesIfUserSaysThereAreMoreRulesToAdd(): void
    {
        $this->adapter
            ->expects($this->exactly(3))
            ->method('addAccessRule')
            ->with('foo', $this->callback(
                static function (array $accessRule): bool {
                    static $i = 0;
                    switch ($i++) {
                        case 0: {
                            $diff = array_diff($accessRule['resources'], Resource::getReadOnlyResources());
                            return (
                                count($accessRule['users']) === 2 &&
                                in_array('espenh', $accessRule['users']) &&
                                in_array('kribrabr', $accessRule['users']) &&
                                empty($diff)
                            );
                        }
                        case 1: {
                            $diff = array_diff($accessRule['resources'], Resource::getReadWriteResources());
                            return (
                                count($accessRule['users']) === 2 &&
                                in_array('rexxars', $accessRule['users']) &&
                                in_array('kbrabrand', $accessRule['users']) &&
                                empty($diff)
                            );
                        }
                        case 2: {
                            $diff = array_diff($accessRule['resources'], Resource::getAllResources());
                            return (
                                $accessRule['users'] === '*' &&
                                empty($diff)
                            );
                        }
                    }
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

    /**
     * @covers ::execute
     * @covers ::askForAnotherAclRule
     * @covers ::askForResources
     * @covers ::askForUsers
     * @covers ::askForSpecificResources
     */
    public function testPromptsForListOfSpecificResourcesIfOptionIsSelected(): void
    {
        $allResources = Resource::getAllResources();
        sort($allResources);

        $this->adapter
            ->expects($this->once())
            ->method('addAccessRule')
            ->with('foo', $this->callback(function ($rule) use ($allResources) {
                return (
                    $rule['users'] === '*' &&
                    $rule['resources'][0] === $allResources[0] &&
                    $rule['resources'][1] === $allResources[5]
                );
            }));

        $this->adapter
            ->expects($this->once())
            ->method('addKeyPair')
            ->with('foo', 'bar');

        $commandTester = new CommandTester($this->command);
        $commandTester->setInputs(['3', '0,5', '*', 'n']);
        $commandTester->execute(['publicKey' => 'foo', 'privateKey' => 'bar']);
    }

    /**
     * @covers ::execute
     * @covers ::askForAnotherAclRule
     * @covers ::askForCustomResources
     */
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

    /**
     * @covers ::__construct
     */
    public function testConfiguresCommand(): void
    {
        $this->assertSame('Add a public key', $this->command->getDescription());
        $this->assertSame('add-public-key', $this->command->getName());
        $this->assertSame('Add a public key to the configured access control adapter', $this->command->getHelp());
    }
}
