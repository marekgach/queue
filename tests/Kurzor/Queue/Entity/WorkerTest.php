<?php
namespace Kurzor\Queue;

use Kurzor\DateTime;
use Kurzor\Queue\Entity\Job;
use Kurzor\Queue\Entity\Worker;
use Kurzor\Tests\DbTestCase;
use PHPUnit_Extensions_Database_DataSet_IDataSet;

/**
 *
 */
class WorkerTest extends DbTestCase
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
        require_once "DummyHandler.php";

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
        $worker = new Worker(null, $this->helper);

        $this->assertEquals('default', \TestHelper::getPrivateField($worker, 'queue'));
        $this->assertEquals($this->helper, \TestHelper::getPrivateField($worker, 'helper'));
    }

    public function test_constructor_WithOptions()
    {
        $options = array('count' => 10, 'sleep' => 7, 'max_attempts' => 15);
        $worker = new Worker($options, $this->helper);

        $this->assertEquals(10, \TestHelper::getPrivateField($worker, 'count'));
        $this->assertEquals(7, \TestHelper::getPrivateField($worker, 'sleep'));
        $this->assertEquals(15, \TestHelper::getPrivateField($worker, 'max_attempts'));
    }

    public function test_handleSignal()
    {
        $this->expectOutputRegex('/\[.*\] Received SIGTERM... Shutting down/');

        $worker = new Worker(null, $this->helper);

        $worker->handleSignal(SIGTERM);

    }

    /**
     * After release lock for jobs there will be not locked jobs for the queue and host (name).
     */
    public function test_releaseLocks()
    {
        $worker = new Worker(null, $this->helper);

        \TestHelper::setPrivateField($worker, 'name', 'host::localhost pid::1');

        $worker->releaseLocks();

        // there will be no jobs locked by worker left
        $stmt = $this->getConnection()->getConnection()
            ->prepare("SELECT * FROM {$this->helper->jobsTable} WHERE locked_by='host::localhost pid::1'");
        $stmt->execute();

        $this->assertFalse($stmt->fetch());
    }

    /**
     * After release lock for jobs there will be not locked jobs for the queue and host (name).
     */
    public function test_releaseLocks_ReleasesOnlyOwn()
    {
        $worker = new Worker(null, $this->helper);

        \TestHelper::setPrivateField($worker, 'name', 'host::foobar pid::777');

        $worker->releaseLocks();

        // there will be no jobs locked by worker left
        $stmt = $this->getConnection()->getConnection()
           ->prepare("SELECT COUNT(*) as cnt FROM {$this->helper->jobsTable} WHERE locked_by='host::localhost pid::1'");
        $stmt->execute();
        $ret = $stmt->fetch();

        $this->assertEquals(1, $ret['cnt']);
    }

    public function test_getNewJob()
    {
        $this->expectOutputRegex('/\[.*\] attempting to acquire lock for job::/');

        DateTime::setNow(\Kurzor\DateTime::fromDb('2015-04-20 00:00:00'));
        $worker = new Worker(null, $this->helper);

        $job = $worker->getNewJob();

        $this->assertInstanceOf('\Kurzor\Queue\Entity\Job', $job);
    }

    public function test_getNewJob_emptyQueue()
    {
        $stmt = $this->getConnection()->getConnection()
            ->prepare("DELETE FROM {$this->helper->jobsTable}");
        $stmt->execute();

        DateTime::setNow(\Kurzor\DateTime::fromDb('2015-04-20 00:00:00'));
        $worker = new Worker(null, $this->helper);

        $job = $worker->getNewJob();

        $this->assertFalse($job);
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
                array('id' => 1, 'failed_at' => null, 'locked_at' => '2015-04-24 11:22:33',
                    'locked_by' => 'host::localhost pid::1', 'queue' => 'default', 'handler' => 'O:8:"stdClass":0:{}',
                    'created_at' => '2015-04-25 19:02:15', 'attempts' => 2),
                array('id' => 2, 'failed_at' => '2015-04-24 10:55:48', 'locked_at' => null, 'queue' => 'default',
                    'handler' => 'foo:bar', 'created_at' => '2015-04-24 10:52:44', 'attempts' => 0
                ),
                array('id' => 3, 'failed_at' => null, 'locked_at' => null, 'queue' => 'default',
                    'handler' => 'foo:bar', 'created_at' => '2015-04-24 10:52:44', 'attempts' => 0,
                    'run_at' => '2015-04-24 10:52:44'
                ),
                array('id' => 4, 'failed_at' => null, 'locked_at' => null, 'queue' => 'default',
                    'handler' => 'foo:bar', 'created_at' => '2015-04-24 10:52:44', 'attempts' => 0,
                    'run_at' => '2099-04-24 10:52:44'
                )
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
