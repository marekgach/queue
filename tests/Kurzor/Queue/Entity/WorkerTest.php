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
