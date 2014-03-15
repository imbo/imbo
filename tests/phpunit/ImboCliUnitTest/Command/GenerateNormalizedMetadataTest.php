<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace ImboCliUnitTest\Command;

use ImboCli\Command\GenerateNormalizedMetadata,
    Symfony\Component\Console\Application,
    Symfony\Component\Console\Tester\CommandTester;

/**
 * @covers ImboCli\Command\GenerateNormalizedMetadata
 * @group unit-cli
 * @group cli-commands
 */
class GenerateNormalizedMetadataTest extends \PHPUnit_Framework_TestCase {
    /**
     * @var ImboCli\Command\GenerateNormalizedMetadata
     */
    private $command;

    /**
     * @var MongoClient
     */
    private $client;

    /**
     * Set up the command
     */
    public function setUp() {
        if (!class_exists('MongoClient')) {
            $this->markTestSkipped('ext-mongo is required for this test');
        }

        $this->client = $this->getMock('MongoClient');
        $this->command = new GenerateNormalizedMetadata();
        $this->command->setMongoClient($this->client);

        $application = new Application();
        $application->add($this->command);
    }

    /**
     * Tear down the command
     */
    public function tearDown() {
        $this->command = null;
        $this->client = null;
    }

    public function testWillExitEarlyIfThereAreNoImagesToUpdate() {
        $collection = $this->getMockBuilder('MongoCollection')->disableOriginalConstructor()->getMock();
        $collection->expects($this->once())->method('count')->with(array('metadata_n' => array('$exists' => false)))->will($this->returnValue(0));
        $this->client->expects($this->once())->method('selectCollection')->with('imbo', 'image')->will($this->returnValue($collection));

        $commandTester = new CommandTester($this->command);
        $commandTester->execute(array(
            'command' => $this->command->getName(),
        ));

        $this->assertSame('There are no more images to update.' . PHP_EOL, $commandTester->getDisplay());
    }

    public function testWillExitEarlyIfTheUseDoesNotConfirm() {
        $collection = $this->getMockBuilder('MongoCollection')->disableOriginalConstructor()->getMock();
        $collection->expects($this->once())->method('count')->with(array('metadata_n' => array('$exists' => false)))->will($this->returnValue(10));
        $collection->expects($this->never())->method('find');
        $this->client->expects($this->once())->method('selectCollection')->with('database', 'collection')->will($this->returnValue($collection));

        $dialog = $this->getMock('Symfony\Component\Console\Helper\DialogHelper');
        $dialog
            ->expects($this->once())
            ->method('askConfirmation')
            ->with(
                $this->isInstanceOf('Symfony\Component\Console\Output\OutputInterface'),
                '<question>You are about to update 10 documents. Continue? [yN]</question> '
            )
            ->will($this->returnValue(false));

        $this->command->getHelperSet()->set($dialog, 'dialog');

        $commandTester = new CommandTester($this->command);
        $commandTester->execute(array(
            'command' => $this->command->getName(),
            '--database' => 'database',
            '--collection' => 'collection',
        ));
    }

    public function testWillUpdateImages() {
        $metadata = array('FOO' => 'BAR', 'Bar' => array('Foo' => 'fooBAR'));
        $normalizedMetadata = array('foo' => 'bar', 'bar' => array('foo' => 'foobar'));

        $numImages = 123;

        // Add some extra images to simulate that images have been added while the command is being
        // executed
        $images = array_fill(0, $numImages + 4, array('metadata' => $metadata));
        $query = array('metadata_n' => array('$exists' => false));

        $collection = $this->getMockBuilder('MongoCollection')->disableOriginalConstructor()->getMock();
        $collection->expects($this->once())->method('count')->with($query)->will($this->returnValue($numImages));
        $collection->expects($this->once())->method('find')->with($query)->will($this->returnValue($images));
        $collection->expects($this->exactly($numImages + 4))->method('save')->with(array('metadata' => $metadata, 'metadata_n' => $normalizedMetadata));

        $this->client->expects($this->once())->method('selectCollection')->with('database', 'collection')->will($this->returnValue($collection));

        $dialog = $this->getMock('Symfony\Component\Console\Helper\DialogHelper');
        $dialog
            ->expects($this->once())
            ->method('askConfirmation')
            ->with(
                $this->isInstanceOf('Symfony\Component\Console\Output\OutputInterface'),
                '<question>You are about to update ' . $numImages . ' documents. Continue? [yN]</question> '
            )
            ->will($this->returnValue(true));

        $progress = $this->getMock('Symfony\Component\Console\Helper\ProgressHelper');
        $progress->expects($this->once())->method('start')->with($this->isInstanceOf('Symfony\Component\Console\Output\OutputInterface'), $numImages);
        $progress->expects($this->exactly($numImages))->method('advance');
        $progress->expects($this->once())->method('setCurrent')->with($numImages);


        $this->command->getHelperSet()->set($dialog, 'dialog');
        $this->command->getHelperSet()->set($progress, 'progress');

        $commandTester = new CommandTester($this->command);
        $commandTester->execute(array(
            'command' => $this->command->getName(),
            '--database' => 'database',
            '--collection' => 'collection',
        ));

        $this->assertSame('Done. You should now be able to search for images using a metadata query.' . PHP_EOL, $commandTester->getDisplay());
    }
}
