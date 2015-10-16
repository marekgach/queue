<?php
namespace Kurzor\Queue\Entity;

use Kurzor\Queue\Helper;

/**
 * Class Worker run still after execution till it is terminated. It looks for a queue entries and tries to execute them.
 * If nothing in queue found it is put into sleep for $sleep seconds.
 *
 * @package Kurzor\Queue\Entity
 */
class Worker
{
    /**
     * @var array default config options. Also it is the set of all allowed parameters.
     */
    protected $options_default = array('queue', 'count', 'sleep', 'max_attempts');

    /**
     * @var string the name of the queue the worker is working with. It is "default" by default.
     */
    protected $queue = "default";

    /**
     * Limit for main while loop:
     * while ($this->count == 0 || $count < $this->count) {
     *
     * When 0 the task is running as far there is something in the queue. If $count is specified the worker will finish
     * after executing $count tasks.
     *
     * @var int number of tasks to execute by this worker, 0 = unlimited - infinite loop until process termination
     */
    protected $count = 0;

    /**
     * @var int number of seconds to sleep if queue empty
     */
    protected $sleep = 5;

    /**
     * NUmber of attempts to rerun failed task. Also it tries to reschedule last 10 tasks in queue to try to reallocate
     * resources in another way. Sometimes may help.
     *
     * @var int number of attempts to rerun the task to mark it as failed
     */
    protected $max_attempts = 5;

    /**
     * @var String concatenation of name and process pid - also is stored into db
     */
    protected $name = null;

    /**
     * @var String worker hostname
     */
    protected $hostname = null;

    /**
     * @var Int worker prosess id (PID)
     */
    protected $pid = null;

    /**
     * @var \Kurzor\Queue\Helper Queue helper instance
     */
    protected $helper = null;


    /**
     * Worker class constructor. Set up params and try to set up signal handling. WARNING: pcntl_signal is disabled in
     * php.ini by default for security reasons. If enabled the worker will log also its termination - kill or CTRL+C.
     * The handler will release all aquired locks.
     *
     * @param $options
     * @param Helper $helper
     */
    public function __construct($options, Helper $helper, $queue = 'default')
    {
        foreach ($this->options_default as $value) {
            if (isset($options[$value])) {
                $this->{$value} = $options[$value];
            }
        }

        $this->queue = $queue;
        $this->helper = $helper;
        $this->hostname = trim(`hostname`);
        $this->pid = getmypid();
        $this->name = "host::{$this->hostname} pid::{$this->pid}";

        // set functions to handle signals - TERM and INT
        if (function_exists("pcntl_signal")) {
            pcntl_signal(SIGTERM, array($this, "handleSignal"));
            pcntl_signal(SIGINT, array($this, "handleSignal"));
        }
    }


    /**
     * Handle the process termination. Will log it into log. Note pcntl_signal function need to be enabled in php.ini to
     * allow to register the handler. The handler will release all aquired locks.
     *
     * @param $signo signal number form handler - 2 of them for us
     */
    public function handleSignal($signo)
    {
        $signals = array(
            SIGTERM => "SIGTERM",
            SIGINT  => "SIGINT"
        );

        $signal = $signals[$signo];

        $this->helper->log("[Queue WORKER] Received {$signal}... Shutting down", Helper::INFO);
        // release all locks aquired
        $this->releaseLocks();
        die(0);
    }


    /**
     * Release all locks aquired for this worker (hostname + process ID).
     */
    public function releaseLocks()
    {
        $this->helper->runUpdate(
            "UPDATE {$this->helper->jobsTable} SET locked_at = NULL, locked_by = NULL WHERE locked_by = ?",
            array($this->name)
        );
    }


    /**
     * Returns a new job ordered by most recent first. Why this? Run newest first, some jobs get left behind. Run oldest
     * first, all jobs get left behind
     *
     * @return \Kurzor\Queue\Entity\Job
     */
    public function getNewJob()
    {
        // we can grab a locked job if we own the lock
        $rs = $this->helper->runQuery(
            "SELECT id FROM {$this->helper->jobsTable} WHERE  queue = ? AND (run_at IS NULL OR NOW() >= run_at) " .
            "AND (locked_at IS NULL OR locked_by = ?) AND failed_at IS NULL AND attempts < ? " .
            "ORDER BY created_at DESC LIMIT  10",
            array($this->queue, $this->name, $this->max_attempts)
        );

        // randomly order the 10 to prevent lock contention among workers
        shuffle($rs);

        foreach ($rs as $r) {
            $job = new Job($this->name, $r["id"], $this->helper, array(
                "max_attempts" => $this->max_attempts
            ));
            if ($job->acquireLock()) {
                return $job;
            }
        }

        return false;
    }


    /**
     * Start the worker in infinite loop or up to $this->number of executed tasks.
     */
    public function start()
    {
        $this->helper->log("[JOB] Starting worker {$this->name} on queue::{$this->queue}", Helper::INFO);

        $count = 0;
        $job_count = 0;
        try {
            while ($this->count == 0 || $count < $this->count) {
                // Calls signal handlers for pending signals
                if (function_exists("pcntl_signal_dispatch")) {
                    pcntl_signal_dispatch();
                }

                $count += 1;
                $job = $this->getNewJob();

                if (!$job) {
                    $this->helper->log("[JOB] Failed to get a job, queue::{$this->queue} may be empty", Helper::DEBUG);
                    sleep($this->sleep);
                    continue;
                }

                $job_count += 1;
                $job->run();

           // @todo use helper to store stats / also use in task runner (while running single task using bin/task.php)
           // @todo log into stats - taskname, time, avg time, total run count
            }
        } catch (Exception $e) {
            $this->helper->log("[JOB] unhandled exception::\"{$e->getMessage()}\"", Helper::ERROR);
        }

        $this->helper->log(
            "[JOB] worker shutting down after running {$job_count} jobs, over {$count} polling iterations",
            Helper::INFO
        );
    }
}
