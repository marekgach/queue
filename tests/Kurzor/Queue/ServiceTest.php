<?php
namespace Kurzor\Queue;

use Kurzor\Tests\DbTestCase;
use Kurzor\Tools\Console\Config\Db;
use PHPUnit_Extensions_Database_DataSet_IDataSet;

/**
 *
 */
class ServiceTest extends DbTestCase
{
    /**
     * @var \Kurzor\Queue\Helper
     */
    protected $helper = null;

    /**
     * @var Service
     */
    protected $service = null;



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
        $this->service = new Service($this->helper);
    }

    public function test_construct()
    {
        $this->assertNotNull($this->service);
        $this->assertSame($this->helper, $this->service->getHelper());
    }

    public function test_getAndSetHelper()
    {
        $this->service = new Service($this->helper);

        $this->service->setHelper($this->helper);
        $this->assertSame($this->helper, $this->service->getHelper());
    }

    public function test_enqueue()
    {
        $this->service = new Service($this->helper);

        $new_id = $this->service->enqueue(new \stdClass(), 'mail');

        $this->assertEquals(10, $new_id);

        // check data in db
        $stmt = $this->getConnection()->getConnection()->prepare("SELECT * FROM {$this->helper->jobsTable} WHERE id=?");
        $stmt->execute(array($new_id));

        $dbData = $stmt->fetch();

        $this->assertNotNull($dbData);

        $this->assertEquals($dbData['id'], $new_id);
        $this->assertEquals($dbData['handler'], 'O:8:"stdClass":0:{}');
        $this->assertEquals($dbData['queue'], 'mail');
        $this->assertEquals($dbData['attempts'], 0);
        $this->assertNull($dbData['run_at']); // need to be NULL
        $this->assertNull($dbData['locked_at']);
        $this->assertNull($dbData['locked_by']);
        $this->assertNull($dbData['failed_at']);
        $this->assertNull($dbData['error']);
        $this->assertNotNull($dbData['created_at']);
    }

    public function test_enqueue_delayed()
    {
        $this->service = new Service($this->helper);

        $run_at = new \DateTime();
        $run_at = $run_at->modify('+1 day');

        $new_id = $this->service->enqueue(new \stdClass(), 'mail', $run_at);

        $this->assertEquals(10, $new_id);

        // check data in db
        $stmt = $this->getConnection()->getConnection()->prepare("SELECT * FROM {$this->helper->jobsTable} WHERE id=?");
        $stmt->execute(array($new_id));

        $dbData = $stmt->fetch();

        $this->assertNotNull($dbData);

        $this->assertEquals($dbData['id'], $new_id);
        $this->assertEquals($dbData['handler'], 'O:8:"stdClass":0:{}');
        $this->assertEquals($dbData['queue'], 'mail');
        $this->assertEquals($dbData['attempts'], 0);
        $this->assertNotNull($dbData['run_at']);  // must be NOT NULL
        $this->assertNull($dbData['locked_at']);
        $this->assertNull($dbData['locked_by']);
        $this->assertNull($dbData['failed_at']);
        $this->assertNull($dbData['error']);
        $this->assertNotNull($dbData['created_at']);
    }

    /**
     * Try to enqueue job in the past will cause exception.
     * @expectedException \Kurzor\Queue\Exception
     */
    public function test_enqueue_delayedBadDate()
    {
        $this->service = new Service($this->helper);

        $run_at = new \DateTime();
        $run_at = $run_at->modify('-1 day');

        $this->service->enqueue(new \stdClass(), 'mail', $run_at);
    }

    public function test_bulkEnqueue()
    {
        $this->service = new Service($this->helper);

        // enqueue 2 jobs
        $ret = $this->service->bulkEnqueue(array(new \stdClass(),  (object) array('foo' => 'bar')), 'mail');

        $this->assertTrue($ret);

        $new_id = 10;
        $new2_id = 11;

        // check data in db
        // first job check
        $stmt = $this->getConnection()->getConnection()->prepare("SELECT * FROM {$this->helper->jobsTable} WHERE id=?");
        $stmt->execute(array($new_id));

        $dbData = $stmt->fetch();

        $this->assertNotNull($dbData);

        $this->assertEquals($dbData['id'], $new_id);
        $this->assertEquals($dbData['handler'], 'O:8:"stdClass":0:{}');
        $this->assertEquals($dbData['queue'], 'mail');
        $this->assertEquals($dbData['attempts'], 0);
        $this->assertNull($dbData['run_at']); // need to be NULL
        $this->assertNull($dbData['locked_at']);
        $this->assertNull($dbData['locked_by']);
        $this->assertNull($dbData['failed_at']);
        $this->assertNull($dbData['error']);
        $this->assertNotNull($dbData['created_at']);

        // second job check
        $stmt = $this->getConnection()->getConnection()->prepare("SELECT * FROM {$this->helper->jobsTable} WHERE id=?");
        $stmt->execute(array($new2_id));

        $dbData = $stmt->fetch();

        $this->assertNotNull($dbData);

        $this->assertEquals($dbData['id'], $new2_id);
        $this->assertEquals($dbData['handler'], 'O:8:"stdClass":1:{s:3:"foo";s:3:"bar";}');
        $this->assertEquals($dbData['queue'], 'mail');
        $this->assertEquals($dbData['attempts'], 0);
        $this->assertNull($dbData['run_at']); // need to be NULL
        $this->assertNull($dbData['locked_at']);
        $this->assertNull($dbData['locked_by']);
        $this->assertNull($dbData['failed_at']);
        $this->assertNull($dbData['error']);
        $this->assertNotNull($dbData['created_at']);
    }

    public function test_bulkEnqueue_delayed()
    {
        $this->service = new Service($this->helper);

        $run_at = new \DateTime();
        $run_at = $run_at->modify('+1 day');

        $this->service = new Service($this->helper);

        // enqueue 2 jobs
        $ret = $this->service->bulkEnqueue(array(new \stdClass(),  (object) array('foo' => 'bar')), 'mail', $run_at);

        $this->assertTrue($ret);

        $new_id = 10;
        $new2_id = 11;

        // check data in db
        // first job check
        $stmt = $this->getConnection()->getConnection()->prepare("SELECT * FROM {$this->helper->jobsTable} WHERE id=?");
        $stmt->execute(array($new_id));

        $dbData = $stmt->fetch();

        $this->assertNotNull($dbData);

        $this->assertEquals($dbData['id'], $new_id);
        $this->assertEquals($dbData['handler'], 'O:8:"stdClass":0:{}');
        $this->assertEquals($dbData['queue'], 'mail');
        $this->assertEquals($dbData['attempts'], 0);
        $this->assertEquals($dbData['run_at'], $run_at->format('Y-m-d H:i:s'));
        $this->assertNull($dbData['locked_at']);
        $this->assertNull($dbData['locked_by']);
        $this->assertNull($dbData['failed_at']);
        $this->assertNull($dbData['error']);
        $this->assertNotNull($dbData['created_at']);

        // second job check
        $stmt = $this->getConnection()->getConnection()->prepare("SELECT * FROM {$this->helper->jobsTable} WHERE id=?");
        $stmt->execute(array($new2_id));

        $dbData = $stmt->fetch();

        $this->assertNotNull($dbData);

        $this->assertEquals($dbData['id'], $new2_id);
        $this->assertEquals($dbData['handler'], 'O:8:"stdClass":1:{s:3:"foo";s:3:"bar";}');
        $this->assertEquals($dbData['queue'], 'mail');
        $this->assertEquals($dbData['attempts'], 0);
        $this->assertEquals($dbData['run_at'], $run_at->format('Y-m-d H:i:s'));
        $this->assertNull($dbData['locked_at']);
        $this->assertNull($dbData['locked_by']);
        $this->assertNull($dbData['failed_at']);
        $this->assertNull($dbData['error']);
        $this->assertNotNull($dbData['created_at']);
    }

    /**
     * Try to enqueue job in the past will cause exception.
     * @expectedException \Kurzor\Queue\Exception
     */
    public function test_bulkEnqueue_delayedBadDate()
    {
        $this->service = new Service($this->helper);

        $run_at = new \DateTime();
        $run_at = $run_at->modify('-1 day');

        $this->service->bulkEnqueue(array(new \stdClass()), 'mail', $run_at);
    }

    public function test_getStatus()
    {
        $status = $this->service->getStatus();

        $this->assertInternalType('array', $status);

        $this->assertEquals(3, $status['outstanding']);
        $this->assertEquals(1, $status['locked']);
        $this->assertEquals(1, $status['failed']);
        $this->assertEquals(5, $status['total']);
    }

    public function test_getStatus_mailQueue()
    {
        $status = $this->service->getStatus('mail');

        $this->assertInternalType('array', $status);

        $this->assertEquals(2, $status['outstanding']);
        $this->assertEquals(0, $status['locked']);
        $this->assertEquals(2, $status['failed']);
        $this->assertEquals(4, $status['total']);
    }

    /**
     * Will return 0 for all statistics. We are not checking if queue exists in any place - is impossible to do so.
     */
    public function test_getStatus_notExistingQueue()
    {
        $status = $this->service->getStatus('foo');

        $this->assertInternalType('array', $status);

        $this->assertEquals(0, $status['outstanding']);
        $this->assertEquals(0, $status['locked']);
        $this->assertEquals(0, $status['failed']);
        $this->assertEquals(0, $status['total']);
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
                array('id' => 1, 'failed_at' => null, 'locked_at' => '2015-04-25 19:04:48', 'queue' => 'default',
                    'handler' => 'foo:bar', 'created_at' => '2015-04-25 19:02:15'),
                array('id' => 2, 'failed_at' => '2015-04-24 10:55:48', 'failed_at' => '2015-04-24 10:55:46',
                    'queue' => 'default', 'handler' => 'foo:bar', 'created_at' => '2015-04-24 10:52:44'
                ),
                array('id' => 3, 'failed_at' => null, 'failed_at' => null, 'queue' => 'default',
                    'handler' => 'foo:bar', 'created_at' => '2015-04-24 10:52:15'),
                array('id' => 4, 'failed_at' => null, 'failed_at' => null, 'queue' => 'default',
                    'handler' => 'foo:bar', 'created_at' => '2015-04-24 10:52:18'),
                array('id' => 5, 'failed_at' => null, 'failed_at' => null, 'queue' => 'default',
                    'handler' => 'foo:bar', 'created_at' => '2015-04-24 10:52:22'),
                array('id' => 6, 'failed_at' => null, 'failed_at' => null, 'queue' => 'mail',
                    'handler' => 'foo:bar', 'created_at' => '2015-04-24 10:52:25'),
                array('id' => 7, 'failed_at' => null, 'failed_at' => null, 'queue' => 'mail',
                    'handler' => 'foo:bar', 'created_at' => '2015-04-24 10:52:26'),
                array('id' => 8, 'failed_at' => '2015-01-01 09:53:55', 'failed_at' => '2015-01-01 09:53:58',
                    'queue' => 'mail', 'handler' => 'foo:bar', 'created_at' => '2015-01-01 09:52:44'
                ),
                array('id' => 9, 'failed_at' => '2015-01-01 09:53:59', 'failed_at' => '2015-01-01 09:54:05',
                    'queue' => 'mail', 'handler' => 'foo:bar', 'created_at' => '2015-01-01 09:52:44'
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
