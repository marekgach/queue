<?php
namespace Kurzor\Queue;

use Kurzor\DateTime;
use Kurzor\Queue\Entity\Job;
use Kurzor\Tests\DbTestCase;
use PHPUnit_Extensions_Database_DataSet_IDataSet;

/**
 *
 */
class JobTest extends DbTestCase
{
    /**
     * @var \Kurzor\Queue\Helper
     */
    protected $helper = null;

    /**
     * Hook metod executed before each test.
     */
    protected function setUp()
    {
        $this->helper = $this->getMockBuilder('Kurzor\Queue\Helper')
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();

        $this->initDatabase();

        parent::setUp();

        $this->helper->setConnection($this->getConnection()->getConnection());
    }

    public function test_constructor()
    {
        $job = new Job('default', 2, $this->helper);

        $this->assertEquals($this->helper, \TestHelper::getPrivateField($job, 'helper'));
        $this->assertEquals('default', \TestHelper::getPrivateField($job, 'worker_name'));
        $this->assertEquals(2, \TestHelper::getPrivateField($job, 'job_id'));
        $this->assertEquals(Job::MAX_ATTEMPTS, \TestHelper::getPrivateField($job, 'max_attempts'));
    }

    public function test_constructor_WithOptions()
    {
        $options = array('max_attempts' => 10);
        $job = new Job('default', 2, $this->helper, $options);

        $this->assertEquals($this->helper, \TestHelper::getPrivateField($job, 'helper'));
        $this->assertEquals('default', \TestHelper::getPrivateField($job, 'worker_name'));
        $this->assertEquals(2, \TestHelper::getPrivateField($job, 'job_id'));
        $this->assertEquals(10, \TestHelper::getPrivateField($job, 'max_attempts'));
    }

    public function test_getAttempts()
    {
        $job = new Job('default', 1, $this->helper);

        $this->assertEquals(2, $job->getAttempts());
    }

    /**
     * Simulate error - no rows returned from db
     */
    public function test_getAttempts_error()
    {
        $helper = $this->getMockBuilder('Kurzor\Queue\Helper')
            ->setMethods(array('runQuery'))
            ->disableOriginalConstructor()
            ->getMock();

        $helper->expects($this->once())
            ->method('runQuery')
            ->willReturn(false);

        $job = new Job('default', 1, $helper);

        $this->assertFalse($job->getAttempts());
    }

    public function test_getHandler()
    {
        $job = new Job('default', 1, $this->helper);
        $handler = $job->getHandler();

        $this->assertNotNull($handler);
        $this->assertInstanceOf('stdClass', $handler);
    }

    /**
     * Simulate error - no rows returned from db
     */
    public function test_getHandler_error()
    {
        $helper = $this->getMockBuilder('Kurzor\Queue\Helper')
            ->setMethods(array('runQuery'))
            ->disableOriginalConstructor()
            ->getMock();

        $helper->expects($this->once())
            ->method('runQuery')
            ->willReturn(false);

        $job = new Job('default', 1, $helper);

        $this->assertFalse($job->getHandler());
    }

    /**
     * Should reschedule job and release the aquired lock.
     */
    public function test_retryLater()
    {
        $job_id = 5;
        DateTime::setNow(\Kurzor\DateTime::fromDb('2015-04-25 19:25:25'));

        $job = new Job('default', $job_id, $this->helper);
        $this->assertNull($job->retryLater(3600));

        // check data in db
        $stmt = $this->getConnection()->getConnection()
            ->prepare("SELECT * FROM {$this->helper->jobsTable} WHERE id=?");

        $stmt->execute(array($job_id));
        $dbData = $stmt->fetch();

        $this->assertEquals($job_id, $dbData['id']);
        $this->assertEquals('2015-04-25 20:25:25', $dbData['run_at'] );
        $this->assertEquals('2', $dbData['attempts'] );
    }

    public function test_finishWithError()
    {
        $job_id = 6;
        DateTime::setNow(\Kurzor\DateTime::fromDb('2015-04-25 19:25:25'));

        $this->expectOutputRegex('/\[.*\] failure in job::6/');

        $job = new Job('default', $job_id, $this->helper);
        $this->assertNull($job->finishWithError('super error'));

        // check data in db
        $stmt = $this->getConnection()->getConnection()
            ->prepare("SELECT * FROM {$this->helper->jobsTable} WHERE id=?");

        $stmt->execute(array($job_id));
        $dbData = $stmt->fetch();

        $this->assertEquals($job_id, $dbData['id']);
        $this->assertEquals('2015-04-25 19:25:25', $dbData['failed_at']);
        $this->assertEquals('super error', $dbData['error']);
        $this->assertEquals(5, $dbData['attempts']);
    }

    public function test_finish()
    {
        $job_id = 6;
        DateTime::setNow(\Kurzor\DateTime::fromDb('2015-04-25 19:25:25'));
        $this->expectOutputRegex('/\[.*\] completed job::6/');

        $job = new Job('default', $job_id, $this->helper);
        $this->assertNull($job->finish());

        // check data in db
        $stmt = $this->getConnection()->getConnection()
            ->prepare("SELECT * FROM {$this->helper->jobsTable} WHERE id=?");

        $stmt->execute(array($job_id));
        $dbData = $stmt->fetch();

        $this->assertFalse($dbData);
    }

    public function test_acquireLock()
    {
        $job_id = 4;
        DateTime::setNow(\Kurzor\DateTime::fromDb('2015-04-25 19:25:25'));
        $this->expectOutputRegex('/\[.*\] attempting to acquire lock for job::4/');

        $job = new Job('default', $job_id, $this->helper);
        $this->assertTrue($job->acquireLock());

        // check data in db
        $stmt = $this->getConnection()->getConnection()
            ->prepare("SELECT * FROM {$this->helper->jobsTable} WHERE id=?");

        $stmt->execute(array($job_id));
        $dbData = $stmt->fetch();

        $this->assertEquals($job_id, $dbData['id']);
        $this->assertEquals('2015-04-25 19:25:25', $dbData['locked_at']);
        $this->assertEquals('default', $dbData['locked_by']);
    }

    public function test_acquireLock_lockUnlockable()
    {
        $job_id = 3;
        DateTime::setNow(\Kurzor\DateTime::fromDb('2015-04-25 19:25:25'));
        $this->expectOutputRegex('/\[.*\] attempting to acquire lock for job::3/');

        $job = new Job('default', $job_id, $this->helper);
        $this->assertFalse($job->acquireLock());

        // check data in db
        $stmt = $this->getConnection()->getConnection()
            ->prepare("SELECT * FROM {$this->helper->jobsTable} WHERE id=?");

        $stmt->execute(array($job_id));
        $dbData = $stmt->fetch();

        $this->assertEquals($job_id, $dbData['id']);
        $this->assertNull($dbData['locked_at']);
        $this->assertNull($dbData['locked_by']);
    }

    public function test_finishWithError_callHandlerErrorMethod()
    {
        $this->expectOutputRegex('/\[.*\] failure in job::6/');

        $handler = $this->getMockBuilder('Kurzor\Queue\DummyHandler')
            ->disableOriginalConstructor()
            ->setMethods(array('_onJobRetryError'))
            ->getMock();

        $handler->expects($this->once())
            ->method('_onJobRetryError');

        $job_id = 6;
        $job = new Job('default', $job_id, $this->helper);
        $this->assertNull($job->finishWithError('super error', $handler));
    }

    /**
     * Returns the test dataset.
     *
     * @return PHPUnit_Extensions_Database_DataSet_IDataSet
     */
    protected function getDataSet()
    {
        return $this->createArrayDataSet(array(
            $this->helper->jobsTable => array(
                array('id' => 1, 'failed_at' => null, 'locked_at' => null, 'queue' => 'default',
                    'handler' => 'O:8:"stdClass":0:{}', 'created_at' => '2015-04-25 19:02:15', 'attempts' => 2),
                array('id' => 2, 'failed_at' => '2015-04-24 10:55:48', 'locked_at' => null, 'queue' => 'default',
                    'handler' => 'foo:bar', 'created_at' => '2015-04-24 10:52:44', 'attempts' => 0
                ),
                array('id' => 3, 'failed_at' => '2015-01-01 09:53:55', 'locked_at' => null, 'queue' => 'mail',
                    'handler' => 'foo:bar', 'created_at' => '2015-01-01 09:52:44', 'attempts' => 0
                ),
                array('id' => 4, 'failed_at' => null, 'locked_at' => null, 'queue' => 'mail', 'handler' => 'foo:bar',
                    'created_at' => '2015-01-01 09:52:44', 'attempts' => 0
                ),
                array('id' => 5, 'failed_at' => null, 'locked_at' => '2015-04-25 19:25:25', 'queue' => 'mail',
                    'handler' => 'foo:bar', 'created_at' => '2015-04-25 19:25:24', 'attempts' => 1
                ),
                array('id' => 6, 'failed_at' => null, 'locked_at' => '2015-04-25 19:25:25', 'queue' => 'mail',
                    'handler' => 'foo:bar', 'created_at' => '2015-04-25 19:25:24', 'attempts' => 4
                ),
            ),
        ));
    }

    /**
     * Initializes the in-memory database.
     */
    public function initDatabase()
    {
        $query = '
CREATE TABLE `jobs` (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT,
  "handler" text NOT NULL,
  "queue" varchar(255) NOT NULL DEFAULT \'default\',
  "attempts" int(10)  NOT NULL DEFAULT \'0\',
  "run_at" datetime DEFAULT NULL,
  "locked_at" datetime DEFAULT NULL,
  "locked_by" varchar(255) DEFAULT NULL,
  "failed_at" datetime DEFAULT NULL,
  "error" text,
  "created_at" datetime NOT NULL
);';

        $this->getConnection()->getConnection()->query($query);
    }
}

class DummyHandler
{
    public function _onJobRetryError()
    {
        return null;
    }
}
