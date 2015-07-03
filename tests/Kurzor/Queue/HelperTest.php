<?php
namespace Kurzor\Queue;

use Kurzor\Tests\DbTestCase;
use PHPUnit_Extensions_Database_DataSet_IDataSet;

/**
 *
 */
class HelperTest extends DbTestCase
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
        //$this->helper = new Helper();
        // $this->initDatabase();

        parent::setUp();

        // $this->helper->setConnection($this->getConnection()->getConnection());
    }

    public function test_construct_withoutUsername()
    {
        $options = (object) array('dbName' => 'database_name', 'host' => '127.0.0.1',
            'charset' => 'utf-8', 'retries' => 5);

        $helper = new Helper($options);

        $this->assertEquals($helper->getDsn(), 'mysql:dbname=database_name;host=127.0.0.1;charset=utf-8');
        $this->assertNull($helper->getUser());
        $this->assertNull($helper->getPassword());
        $this->assertEquals($helper->getRetries(), 5);
    }

    public function test_construct_withUsername()
    {
        $options = (object) array('dbName' => 'database_name', 'host' => '127.0.0.1',
            'charset' => 'utf-8', 'username' => 'marek', 'password' => 'pass123');

        $helper = new Helper($options);

        $this->assertEquals($helper->getDsn(), 'mysql:dbname=database_name;host=127.0.0.1;charset=utf-8');
        $this->assertEquals($helper->getUser(), $options->username);
        $this->assertEquals($helper->getPassword(), $options->password);
        $this->assertEquals($helper->getRetries(), 3);
    }

    public function test_construct_onlyDnsSupplied()
    {
        $options = (object) array('dsn' => 'sqlite::memory:', 'retries' => 5);

        $helper = new Helper($options);

        $this->assertEquals($helper->getDsn(), $options->dsn);
        $this->assertNull($helper->getUser());
        $this->assertNull($helper->getPassword());
        $this->assertEquals($helper->getRetries(), 5);
    }

    /**
     * @expectedException \Kurzor\Queue\Exception
     */
    public function test_construct_withCheckRequiredParams()
    {
        $options = new \stdClass();

        new Helper($options);
    }



    public function test_log()
    {
        $this->expectOutputRegex('/\[.*\] foo is bar!/');
        $helper = $this->getHelperInstance();

        $helper->log('foo is bar!');
    }

    public function test_log_bellowSetSeverity()
    {
        $this->expectOutputRegex('/^$/');
        $helper = $this->getHelperInstance();

        $helper->log('foo is bar!', Helper::DEBUG);
    }

    public function test_log_aboveSetSeverity()
    {
        $this->expectOutputRegex('/\[.*\] foo is bar!/');
        $helper = $this->getHelperInstance();

        $helper->log('foo is bar!', Helper::INFO);
    }

    public function test_getName()
    {
        $this->assertEquals('queue', $this->getHelperInstance()->getName());

    }

    public function test_getDsn()
    {
        $dsn = 'sqlite::memory:';

        $helper = $this->getHelperInstance();
        \TestHelper::setPrivateField($helper, 'dsn', $dsn);

        $this->assertEquals($dsn, $helper->getDsn());
    }

    public function test_getUser()
    {
        $username = 'foo';

        $helper = $this->getHelperInstance();
        \TestHelper::setPrivateField($helper, 'user', $username);

        $this->assertEquals($username, $helper->getUser());
    }

    public function test_getPassword()
    {
        $password = 'bar';

        $helper = $this->getHelperInstance();
        \TestHelper::setPrivateField($helper, 'password', $password);

        $this->assertEquals($password, $helper->getPassword());
    }

    public function test_getRetries()
    {
        $retries = 10;

        $helper = $this->getHelperInstance();
        \TestHelper::setPrivateField($helper, 'retries', $retries);

        $this->assertEquals($retries, $helper->getRetries());
    }

    /**
     * Returns the test dataset.
     *
     * @return PHPUnit_Extensions_Database_DataSet_IDataSet
     */
    protected function getDataSet()
    {
        return $this->createArrayDataSet(array());
    }


    protected function getHelperInstance()
    {
        $options = (object) array('dsn' => 'sqlite::memory:', 'retries' => 5);

        return new Helper($options);
    }

    public function test_getAndSetLevel()
    {
        $loglevel = Helper::DEBUG;

        $helper = $this->getHelperInstance();
        $helper->setLogLevel($loglevel);

        $this->assertEquals($loglevel, $helper->getLogLevel());
    }

    public function test_getConnection()
    {
        $helper = $this->getHelperInstance();

        $pdo = $helper->getConnection();

        $this->assertInstanceOf('\Pdo', $pdo);
    }

    /**
     * @expectedException \Kurzor\Queue\Exception
     * @expectedExceptionMessage [Queue] Couldn't connect to the database. PDO said [SQLSTATE[HY000] [2005] Unknown MySQL server host 'bar' (2)]
     */
    public function test_getConnection_BadCredentials()
    {
        $helper = new Helper((object) array('dsn' =>"mysql:dbname=foo;host=bar"));

        $pdo = $helper->getConnection();

        $this->assertInstanceOf('\Pdo', $pdo);
    }
}
