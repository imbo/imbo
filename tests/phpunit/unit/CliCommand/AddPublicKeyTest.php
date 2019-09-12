<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace ImboUnitTest\CliCommand;

use Imbo\CliCommand\AddPublicKey;
use Imbo\Auth\AccessControl\Adapter\ArrayAdapter;
use Imbo\Resource;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * @covers Imbo\CliCommand\AddPublicKey
 * @group unit-cli
 * @group cli-commands
 */
class AddPublicKeyTest extends TestCase {
    /**
     * @var Imbo\CliCommand\AddPublicKey
     */
    private $command;

    /**
     * @var Imbo\Auth\AccessControl\Adapter\MutableAdapterInterface
     */
    private $adapter;

    /**
     * Set up the command
     *
     * @covers Imbo\CliCommand\AddPublicKey::__construct
     */
    public function setUp() {
        $this->adapter = $this->createMock('Imbo\Auth\AccessControl\Adapter\MutableAdapterInterface');

        $this->command = new AddPublicKey();
        $this->command->setConfig([
            'accessControl' => $this->adapter
        ]);

        $application = new Application();
        $application->add($this->command);
    }

    /**
     * @covers Imbo\CliCommand\AddPublicKey::getAclAdapter
     */
    public function testThrowsWhenAccessControlIsNotValid() {
        $command = new AddPublicKey();
        $command->setConfig([
            'accessControl' => new \Exception()
        ]);

        $commandTester = new CommandTester($command);
        $this->expectExceptionObject(new RuntimeException('Invalid access control adapter'));
        $commandTester->execute(['publicKey' => 'foo']);
    }

    /**
     * @covers Imbo\CliCommand\AddPublicKey::getAclAdapter
     */
    public function testThrowsWhenCallableReturnsInvalidAccessControl() {
        $command = new AddPublicKey();
        $command->setConfig([
            'accessControl' => function() {
                return new \Exception();
            }
        ]);

        $commandTester = new CommandTester($command);
        $this->expectExceptionObject(new RuntimeException('Invalid access control adapter'));
        $commandTester->execute(['publicKey' => 'foo']);
    }

    /**
     * @covers Imbo\CliCommand\AddPublicKey::getAclAdapter
     */
    public function testThrowsOnImmutableAdapter() {
        $command = new AddPublicKey();
        $command->setConfig([
            'accessControl' => $this->createMock('Imbo\Auth\AccessControl\Adapter\AdapterInterface')
        ]);

        $commandTester = new CommandTester($command);
        $this->expectExceptionObject(new RuntimeException('The configured access control adapter is not mutable'));
        $commandTester->execute(['publicKey' => 'foo']);
    }

    /**
     * @covers Imbo\CliCommand\AddPublicKey::execute
     */
    public function testThrowsOnDuplicatePublicKeyName() {
        $this->adapter
            ->expects($this->once())
            ->method('publicKeyExists')
            ->with('foo')
            ->will($this->returnValue(true));

        $commandTester = new CommandTester($this->command);
        $this->expectExceptionObject(new RuntimeException('Public key with that name already exists'));
        $commandTester->execute(['publicKey' => 'foo']);
    }

    /**
     * @covers Imbo\CliCommand\AddPublicKey::askForPrivateKey
     */
    public function testWillAskForPrivateKeyIfNotSpecified() {
        $this->adapter
            ->expects($this->once())
            ->method('addKeyPair')
            ->with('foo', 'ZiePublicKey');

        $helper = $this->command->getHelper('question');
        $helper->setInputStream($this->getInputStream([
            'ZiePublicKey',
            '0',
            'rexxars',
            'n'
        ]));

        $commandTester = new CommandTester($this->command);
        $commandTester->execute(['publicKey' => 'foo']);
    }

    /**
     * @covers Imbo\CliCommand\AddPublicKey::askForUsers
     */
    public function testWillNotAcceptEmptyUserSpecification() {
        $helper = $this->command->getHelper('question');
        $helper->setInputStream($this->getInputStream([
            '0',
            '',
            '*',
            'n',
        ]));

        $commandTester = new CommandTester($this->command);
        $commandTester->execute(['publicKey' => 'foo', 'privateKey' => 'bar']);

        $this->assertRegExp('/at least one user/', $commandTester->getDisplay(true));
    }

    /**
     * @covers Imbo\CliCommand\AddPublicKey::askForCustomResources
     */
    public function testWillNotAcceptEmptyCustomResourceSpecification() {
        $helper = $this->command->getHelper('question');
        $helper->setInputStream($this->getInputStream([
            '4',
            '',
            'foo.bar,bar.foo',
            '*',
            'n'
        ]));

        $commandTester = new CommandTester($this->command);
        $commandTester->execute(['publicKey' => 'foo', 'privateKey' => 'bar']);

        $this->assertRegExp(
            '/must specify at least one resource/',
            $commandTester->getDisplay(true)
        );
    }

    /**
     * @covers Imbo\CliCommand\AddPublicKey::execute
     * @covers Imbo\CliCommand\AddPublicKey::askForAnotherAclRule
     * @covers Imbo\CliCommand\AddPublicKey::askForResources
     * @covers Imbo\CliCommand\AddPublicKey::askForUsers
     */
    public function testContinuesAskingForAclRulesIfUserSaysThereAreMoreRulesToAdd() {
        $this->adapter
            ->expects($this->exactly(3))
            ->method('addAccessRule')
            ->withConsecutive(
                [$this->equalTo('foo'), $this->callback(function($rule) {
                    $diff = array_diff($rule['resources'], Resource::getReadOnlyResources());
                    return (
                        count($rule['users']) === 2 &&
                        in_array('espenh', $rule['users']) &&
                        in_array('kribrabr', $rule['users']) &&
                        empty($diff)
                    );
                })],
                [$this->equalTo('foo'), $this->callback(function($rule) {
                    $diff = array_diff($rule['resources'], Resource::getReadWriteResources());
                    return (
                        count($rule['users']) === 2 &&
                        in_array('rexxars', $rule['users']) &&
                        in_array('kbrabrand', $rule['users']) &&
                        empty($diff)
                    );
                })],
                [$this->equalTo('foo'), $this->callback(function($rule) {
                    $diff = array_diff($rule['resources'], Resource::getAllResources());
                    return (
                        $rule['users'] === '*' &&
                        empty($diff)
                    );
                })]
            );

        $helper = $this->command->getHelper('question');
        $helper->setInputStream($this->getInputStream([
            '0', 'espenh,kribrabr',    'y',
            '1', 'rexxars, kbrabrand', 'y',
            '2', '*',                  'n',
        ]));

        $commandTester = new CommandTester($this->command);
        $commandTester->execute(['publicKey' => 'foo', 'privateKey' => 'bar']);

        $this->assertSame(
            3,
            substr_count(
                $commandTester->getDisplay(true),
                'Create more ACL-rules for this public key?'
            )
        );
    }

    /**
     * @covers Imbo\CliCommand\AddPublicKey::execute
     * @covers Imbo\CliCommand\AddPublicKey::askForAnotherAclRule
     * @covers Imbo\CliCommand\AddPublicKey::askForResources
     * @covers Imbo\CliCommand\AddPublicKey::askForUsers
     */
    public function testPromptsForListOfSpecificResourcesIfOptionIsSelected() {
        $allResources = Resource::getAllResources();
        sort($allResources);

        $this->adapter
            ->expects($this->once())
            ->method('addAccessRule')
            ->with('foo', $this->callback(function($rule) use ($allResources) {
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

        $helper = $this->command->getHelper('question');
        $helper->setInputStream($this->getInputStream(['3', '0,5', '*', 'n']));

        $commandTester = new CommandTester($this->command);
        $commandTester->execute(['publicKey' => 'foo', 'privateKey' => 'bar']);
    }

    /**
     * @covers Imbo\CliCommand\AddPublicKey::execute
     * @covers Imbo\CliCommand\AddPublicKey::askForAnotherAclRule
     * @covers Imbo\CliCommand\AddPublicKey::askForCustomResources
     */
    public function testPromtpsForListOfCustomResourcesIfOptionIsSelected() {
        $allResources = Resource::getAllResources();
        sort($allResources);

        $this->adapter
            ->expects($this->once())
            ->method('addAccessRule')
            ->with('foo', [
                'resources' => [
                    'foo.read',
                    'bar.write'
                ],
                'users' => '*'
            ]);

        $this->adapter
            ->expects($this->once())
            ->method('addKeyPair')
            ->with('foo', 'bar');

        $helper = $this->command->getHelper('question');
        $helper->setInputStream($this->getInputStream([
            '4',
            'foo.read,bar.write',
            '*',
            'n'
        ]));

        $commandTester = new CommandTester($this->command);
        $commandTester->execute(['publicKey' => 'foo', 'privateKey' => 'bar']);
    }

    protected function getInputStream($input) {
        if (is_array($input)) {
            $input = implode("\n", $input) . "\n";
        }

        $stream = fopen('php://memory', 'r+', false);
        fputs($stream, $input);
        rewind($stream);

        return $stream;
    }
}
