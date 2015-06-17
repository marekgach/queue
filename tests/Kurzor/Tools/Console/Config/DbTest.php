<?php
namespace Kurzor\Tools\Console\Config;

/**
 *
 */
class DbTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Db
     */
    protected $db = null;

    /**
     * Hook method executed before each test.
     */
    protected function setUp()
    {
        $this->db = new Db();
    }


    public function test_setGetUsername()
    {
        $username = 'marek';

        $this->db->setUsername($username);
        $this->assertEquals($username, $this->db->getUsername());
    }

    public function test_setGetPassword()
    {
        $password = 'xxx';

        $this->db->setPassword($password);
        $this->assertEquals($password, $this->db->getPassword());
    }

    public function test_setGetHost()
    {
        $host = 'myPC';

        $this->db->setHost($host);
        $this->assertEquals($host, $this->db->getHost());
    }

    public function test_setGetCharset()
    {
        $charset = 'utf16';

        $this->db->setCharset($charset);
        $this->assertEquals($charset, $this->db->getCharset());
    }

    public function test_setGetDbName()
    {
        $dbName = 'database';

        $this->db->setDbName($dbName);
        $this->assertEquals($dbName, $this->db->getDbName());
    }
}
