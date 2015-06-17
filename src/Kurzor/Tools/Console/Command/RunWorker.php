<?php
namespace Kurzor\Tools\Console\Command;

use Kurzor\Queue\Exception;
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
                    new InputOption(
                        'name',
                        null,
                        InputOption::VALUE_OPTIONAL,
                        'Queue name (used `default` if not specified)',
                        'default'
                    ),
                ))
            )
            ->setHelp(<<<EOT
The <info>%command.name%</info> allows to run worker for given queue name
(supplied as a param).
EOT
            );
    }

    /**
     * @param string $queueName
     * @return Worker
     */
    protected function getWorker($queueName = 'default')
    {
        $queueHelper = $this->getHelper('queue');

        return new Worker(null, $queueHelper, $queueName);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $queueName = $input->getOption('name');

        $worker = $this->getWorker($queueName);
        $worker->start();

        return 0;
    }
}
