<?php
namespace Kurzor\Tests;

use PHPUnit_Extensions_Database_DataSet_IDataSet;

abstract class DbTestCase extends \PHPUnit_Extensions_Database_TestCase
{
    /**
     * @var \PDO
     */
    protected $pdo = null;

    /**
     * @var \PHPUnit_Extensions_Database_DB_IDatabaseConnection
     */
    private $conn = null;

    final public function getPdo()
    {
        if ($this->pdo === null) {
            $this->getConnection();
        }

        return $this->pdo;
    }

    final public function getConnection()
    {
        if ($this->conn === null) {
            if ($this->pdo == null) {
                $this->pdo = new \PDO('sqlite::memory:');
            }
            $this->conn = $this->createDefaultDBConnection($this->pdo, ':memory:');
        }

        return $this->conn;
    }
}
