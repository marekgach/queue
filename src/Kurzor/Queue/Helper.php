<?php
namespace Kurzor\Queue;

use Kurzor\Tools\Console\Config\Db;

/**
 * Class Helper offers methods to log and store / get data about queue from db.
 *
 * @todo add methods allowing to access error handler and log errors manually
 *
 * @package Kurzor\Queue
 */
class Helper extends \Symfony\Component\Console\Helper\Helper
{
    /**
     * Error levels
     */
    const CRITICAL = 4, ERROR = 3, WARN = 2, INFO = 1, DEBUG = 0;

    /**
     * @var int Min log level we want to put into log.
     */
    private $log_level = self::INFO;

    /**
     * @var \Pdo instance od database
     */
    private $db = null;

    /**
     * @var string data source for PDO adapter
     */
    private $dsn = "";

    /**
     * @var string database username from config
     */
    private $user = "";

    /**
     * @var string database pass from config
     */
    private $password = "";

    /**
     * @var int default number of retries for db query and db connect
     */
    private $retries = 3;

    /**
     * @var string default queue table name. Table need to be created manually.
     */
    public $jobsTable = "jobs";

    /**
     * @var array required parameters
     */
    protected $requiredParams = array('dbName', 'host', 'charset');

    /**
     * Set db config into class properties and other stuff.
     *
     * @param array $options set of db configuration
     */
    public function __construct($options)
    {
        if (!empty($options->dsn)) {
            $this->dsn = $options->dsn;
        } else {
            $this->assertParams($options);
            $this->dsn = "mysql:dbname={$options->dbName};host={$options->host};charset={$options->charset}";
        }

        $this->user = isset($options->username) ? $options->username : null;
        $this->password = isset($options->password) ? $options->password : null;

        // searches for retries
        if (isset($options->retries)) {
            $this->retries = (int) $options->retries;
        }
    }


    protected function assertParams($options)
    {
        $err = null;

        foreach ($this->requiredParams as $param) {
            if (!isset($options->{$param})) {
                $err .= "[Queue] Please provide the database {$param} in configure options array.\n";
            }
        }

        if (!empty($err)) {
            throw new \Kurzor\Queue\Exception($err);
        }
    }


    /**
     * Set minimum log message level to be shown in log.
     *
     * @param $const log level
     */
    public function setLogLevel($const)
    {
        $this->log_level = $const;
    }

    /**
     * @return int
     */
    public function getLogLevel()
    {
        return $this->log_level;
    }

    /**
     * Set db connection Pdo object.
     *
     * @param \PDO $db connection object
     */
    public function setConnection(\PDO $db)
    {
        $this->db = $db;
    }


    /**
     * Try to connect the database and create Pdo adapter object.
     *
     * @return \PDO instance of db adapter
     * @throws Exception
     */
    public function getConnection()
    {
        if ($this->db === null) {
            try {
                $this->db = new \PDO($this->dsn, $this->user, $this->password);
                $this->db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            } catch (\PDOException $e) {
                throw new Exception("[Queue] Couldn't connect to the database. PDO said [{$e->getMessage()}]");
            }
        }
        return $this->db;
    }


    /**
     * @param $sql string SQL string with prepare statement placeholders
     * @param array $params prepare statement params
     * @return array data get from db
     * @throws \Exception
     * @throws \PDOException
     * @throws Exception
     */
    public function runQuery($sql, $params = array())
    {
        for ($attempts = 0; $attempts < $this->retries; $attempts++) {
            try {
                $stmt = self::getConnection()->prepare($sql);
                $stmt->execute($params);

                $ret = array();

                // NOTE: need to flush output buffer for INSERT, DELETE or UPDATE. Also to get results for SELECT.
                // There is not guaranteed $stmt->rowCount() will return correct result for SELECT statement - do not
                // work in SQLite, however in MySQL does.
                if (preg_match('/SELECT\s/', $sql) || $stmt->rowCount()) {
                    // calling fetchAll on a result set with no rows throws a
                    // "general error" exception
                    foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $r) {
                        $ret[] = $r;
                    }
                }

                $stmt->closeCursor();
                return $ret;
        // @codeCoverageIgnoreStart
            } catch (\PDOException $e) {
                // Catch "MySQL server has gone away" error.
                if ($e->errorInfo[1] == 2006) {
                    self::$db = null;
                } else { // throw some other error
                    throw $e;
                }
            }
        }

        throw new Exception("[Queue] Exhausted retries connecting to database");
        // @codeCoverageIgnoreEnd
    }


    /**
     * @param $sql string SQL statement string to execute update with prepare statement placeholders
     * @param array $params prepare statement params
     * @return int rows affected number
     * @throws \Exception
     * @throws \PDOException
     * @throws Exception
     */
    public function runUpdate($sql, $params = array())
    {
        for ($attempts = 0; $attempts < $this->retries; $attempts++) {
            try {
                $stmt = self::getConnection()->prepare($sql);
                $stmt->execute($params);
                return $stmt->rowCount();
        // @codeCoverageIgnoreStart
            } catch (\PDOException $e) {
                // Catch "MySQL server has gone away" error.
                if ($e->errorInfo[1] == 2006) {
                    self::$db = null;
                } else { // throw some other error
                    throw $e;
                }
            }
        }

        throw new Exception("[Queue] Exhausted retries connecting to database");
        // @codeCoverageIgnoreEnd
    }


    /**
     * Log message onto output.
     *
     * @todo put here our logger and log into log/task.log - task start and end and including WARN level and up
     * @todo send email for ERROR and CRITICAL log messages and log into error log
     * @todo also check error handler is set correctly and set into error.log
     *
     * @param $mesg message text to be logged
     * @param int $severity log severity
     */
    public function log($msg, $severity = self::CRITICAL)
    {
        if ($severity >= $this->log_level) {
            printf("[%s] %s\n", date('c'), $msg);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'queue';
    }

    /**
     * @return string
     */
    public function getDsn()
    {
        return $this->dsn;
    }

    /**
     * @return string
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @return int
     */
    public function getRetries()
    {
        return $this->retries;
    }
}
