<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace ImboUnitTest\Auth\AccessControl\Adapter;

use Imbo\Auth\AccessControl\Adapter\MongoDB,
    MongoException;

/**
 * @covers Imbo\Auth\AccessControl\Adapter\MongoDB
 * @group unit
 * @group mongodb
 */
class MongoDBTest extends \PHPUnit_Framework_TestCase {
    /**
     * @var MongoClient
     */
    private $client;

    /**
     * @var MongoCollection
     */
    private $aclCollection;

    /**
     * @var MongoCollection
     */
    private $aclGroupCollection;

    /**
     * @var MongoDB
     */
    private $adapter;

    /**
     * Set up
     */
    public function setUp() {
        $this->client = $this->getMock('MongoClient');
        $this->aclCollection = $this->getMockBuilder('MongoCollection')->disableOriginalConstructor()->getMock();
        $this->aclGroupCollection = $this->getMockBuilder('MongoCollection')->disableOriginalConstructor()->getMock();
        $this->adapter = new MongoDB(null, $this->client, $this->aclCollection, $this->aclGroupCollection);
    }

    /**
     * Tear down
     */
    public function tearDown() {
        $this->adapter = $this->client = $this->aclCollection = $this->aclGroupCollection = null;
    }

    /**
     * @expectedException Imbo\Exception\DatabaseException
     * @expectedExceptionCode 500
     * @expectedExceptionMessage Could not insert key into database
     */
    public function testThrowsExceptionWhenUnableToAddKeyPair() {
        $this->aclCollection
            ->expects($this->once())
            ->method('insert')
            ->with(['publicKey' => 'public', 'privateKey' => 'private', 'acl' => []])
            ->will($this->throwException(new MongoException()));
        $this->adapter->addKeyPair('public', 'private');
    }

    /**
     * @expectedException Imbo\Exception\DatabaseException
     * @expectedExceptionCode 500
     * @expectedExceptionMessage Could not delete key from database
     */
    public function testThrowsExceptionWhenUnableToDeleteKeyPair() {
        $this->aclCollection
            ->expects($this->once())
            ->method('remove')
            ->with(['publicKey' => 'public'])
            ->will($this->throwException(new MongoException()));
        $this->adapter->deletePublicKey('public');
    }

    /**
     * @expectedException Imbo\Exception\DatabaseException
     * @expectedExceptionCode 500
     * @expectedExceptionMessage Could not update key in database
     */
    public function testThrowsExceptionWhenUnableToUpdatePrivateKey() {
        $this->aclCollection
            ->expects($this->once())
            ->method('update')
            ->with(['publicKey' => 'public'], ['$set' => ['privateKey' => 'private']])
            ->will($this->throwException(new MongoException()));
        $this->adapter->updatePrivateKey('public', 'private');
    }

    /**
     * @expectedException Imbo\Exception\DatabaseException
     * @expectedExceptionCode 500
     * @expectedExceptionMessage Could not update rule in database
     */
    public function testThrowsExceptionWhenUnableToAddAccessRule() {
        $this->aclCollection
            ->expects($this->once())
            ->method('update')
            ->with(['publicKey' => 'public'], $this->isType('array'))
            ->will($this->throwException(new MongoException()));
        $this->adapter->addAccessRule('public', []);
    }

    /**
     * @expectedException Imbo\Exception\DatabaseException
     * @expectedExceptionCode 500
     * @expectedExceptionMessage Could not delete rule from database
     */
    public function testThrowsExceptionWhenUnableToDeleteAccessRule() {
        $this->aclCollection
            ->expects($this->once())
            ->method('update')
            ->with(['publicKey' => 'public'], $this->isType('array'))
            ->will($this->throwException(new MongoException()));
        $this->adapter->deleteAccessRule('public', '49a7011a05c677b9a916612a');
    }

    /**
     * @expectedException Imbo\Exception\DatabaseException
     * @expectedExceptionCode 500
     * @expectedExceptionMessage Could not add resource group to database
     */
    public function testThrowsExceptionWhenUnableToAddResourceGroup() {
        $this->aclGroupCollection
            ->expects($this->once())
            ->method('insert')
            ->with(['name' => 'name', 'resources' => []])
            ->will($this->throwException(new MongoException()));
        $this->adapter->addResourceGroup('name', []);
    }

    /**
     * @expectedException Imbo\Exception\DatabaseException
     * @expectedExceptionCode 500
     * @expectedExceptionMessage Could not update resource group in database
     */
    public function testThrowsExceptionWhenUnableToUpdateResourceGroup() {
        $this->aclGroupCollection
            ->expects($this->once())
            ->method('update')
            ->with(['name' => 'name'], ['$set' => ['resources' => []]])
            ->will($this->throwException(new MongoException()));
        $this->adapter->updateResourceGroup('name', []);
    }

    /**
     * @expectedException Imbo\Exception\DatabaseException
     * @expectedExceptionCode 500
     * @expectedExceptionMessage Could not delete resource group from database
     */
    public function testThrowsExceptionWhenUnableToDeleteResourceGroup() {
        $this->aclGroupCollection
            ->expects($this->once())
            ->method('remove')
            ->with(['name' => 'name'])
            ->will($this->throwException(new MongoException()));
        $this->adapter->deleteResourceGroup('name');
    }

    /**
     * @expectedException Imbo\Exception\DatabaseException
     * @expectedExceptionCode 500
     * @expectedExceptionMessage Could not delete resource group from database
     */
    public function testThrowsExceptionWhenUnableToRemoveAclFromGroup() {
        $this->aclGroupCollection
            ->expects($this->once())
            ->method('remove')
            ->with(['name' => 'name'])
            ->will($this->returnValue(['ok' => true]));

        $this->aclCollection
            ->expects($this->once())
            ->method('update')
            ->with(['acl.group' => 'name'], ['$pull' => ['acl' => ['group' => 'name']]])
            ->will($this->throwException(new MongoException()));

        $this->adapter->deleteResourceGroup('name');
    }

    /**
     * @expectedException Imbo\Exception\DatabaseException
     * @expectedExceptionCode 500
     * @expectedExceptionMessage Could not select collection
     */
    public function testThrowsExceptionWhenNotAbleToGetAclCollection() {
        $adapter = new MongoDB(['databaseName' => 'somename'], $this->client);
        $this->client
            ->expects($this->once())
            ->method('selectCollection')
            ->with('somename', 'accesscontrol')
            ->will($this->throwException(new MongoException()));
        $adapter->addKeyPair('public', 'private');
    }

    /**
     * @expectedException Imbo\Exception\DatabaseException
     * @expectedExceptionCode 500
     * @expectedExceptionMessage Could not select collection
     */
    public function testThrowsExceptionWhenNotAbleToGetAclGroupCollection() {
        $adapter = new MongoDB(['databaseName' => 'somename'], $this->client);
        $this->client
            ->expects($this->once())
            ->method('selectCollection')
            ->with('somename', 'accesscontrolgroup')
            ->will($this->throwException(new MongoException()));
        $adapter->groupExists('name');
    }

    /**
     * @expectedException Imbo\Exception\DatabaseException
     * @expectedExceptionCode 500
     * @expectedExceptionMessage Could not connect to database
     */
    public function testThrowsExceptionWhenNotAbleToGetMongoClient() {
        $adapter = new MongoDB([
            'server' => 'foobar',
        ]);
        $adapter->getGroup('name');
    }
}
