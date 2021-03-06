<?php

namespace Kurzor\Tools\Console;

use Kurzor\Queue\Helper;
use Kurzor\Tools\Console\Config\Db;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Helper\HelperSet;

/**
 * Handles running the Console Tools inside Symfony Console context.
 */
class ConsoleRunner
{
    /**
     * Create a Symfony Console HelperSet
     *
     * @param $dbConfig
     * @return HelperSet
     */
    public function createHelperSet(Db $dbConfig)
    {
        $helpers = array(
            'queue' => new Helper($dbConfig)
        );

        return new HelperSet($helpers);
    }

    /**
     * Runs console with the given helperset.
     *
     * @param \Symfony\Component\Console\Helper\HelperSet  $helperSet
     * @param \Symfony\Component\Console\Command\Command[] $commands
     *
     * @return void
     */
    public function run(HelperSet $helperSet, $commands = array())
    {
        $cli = $this->getApplication();
        $cli->setCatchExceptions(true);
        $cli->setHelperSet($helperSet);
        $this->addCommands($cli);
        $cli->addCommands($commands);
        $cli->run();
    }

    public function getApplication()
    {
        return new Application('Kurzor Command Line Interface');
    }

    /**
     * @param Application $cli
     *
     * @return void
     */
    public function addCommands(Application $cli)
    {
        $cli->addCommands(array(
            new \Kurzor\Tools\Console\Command\RunWorker()
        ));
    }

    public function printCliConfigTemplate()
    {
        echo <<<'HELP'
You are missing a "kurzor-config.php" or "config/kurzor-config.php" file in your
project, which is required to get the Kurzor Console working. You can use the
following sample as a template:

<?php
use Kurzor\Tools\Console\ConsoleRunner;

// replace with file to your own project bootstrap (if needed)
require_once 'bootstrap.php';

$config = new \Kurzor\Tools\Console\Config\Db();

$config->setUsername('root');
$config->setPassword('xxx');
$config->setDbname('databaseNameHere');

$cr = new ConsoleRunner;

return $cr->createHelperSet($config);
HELP;

    }
}
