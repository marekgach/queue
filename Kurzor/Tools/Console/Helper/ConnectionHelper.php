<?php
namespace Kurzor\Tools\Console\Helper;

use Symfony\Component\Console\Helper\Helper;

class ConnectionHelper extends Helper
{
    /**
     * The Doctrine database Connection.
     *
     * @var \Doctrine\DBAL\Connection
     */
    protected $_connection;

    /**
     * Constructor.
     *
     * @param \Doctrine\DBAL\Connection $connection The Doctrine database Connection.
     */
    public function __construct($connection)
    {
        $this->_connection = $connection;
    }

    /**
     * Retrieves the Doctrine database Connection.
     *
     * @return \Doctrine\DBAL\Connection
     */
    public function getConnection()
    {
        return $this->_connection;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'connection';
    }
}