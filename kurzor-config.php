<?php
use Kurzor\Tools\Console\ConsoleRunner;
use \Kurzor\Tools\Console\Config\Db;

// make composer autoload magic
require_once 'vendor/autoload.php';

require_once 'bin/autoload.php';

$config = new Db();

$config->setUsername('queuetest');
$config->setPassword('queuetest');
$config->setDbname('queuetest');

return ConsoleRunner::createHelperSet($config);