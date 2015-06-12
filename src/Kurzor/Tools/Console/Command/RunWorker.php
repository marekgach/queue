<?php
namespace Kurzor\Tools\Console\Command;

use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use \Kurzor\Queue\Entity\Worker;

/**
 * Run worker for given queue name.
 *
 */
class RunWorker extends Command
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('queue:run-worker')
            ->setDescription('Run worker for given queue name')
            ->setDefinition(
                new InputDefinition(array(
                    new InputOption('name', null, InputOption::VALUE_OPTIONAL, 'Queue name (used `default` if not specified)', 'default'),
                ))
            )
            ->setHelp(<<<EOT
The <info>%command.name%</info> allows to run worker for given queue name
(supplied as a param).
EOT
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $queueName = $input->getOption('name');

        $worker = new Worker(null, $this->getHelper('queue'), $queueName);
        $worker->start();

        return 0;
    }
}
