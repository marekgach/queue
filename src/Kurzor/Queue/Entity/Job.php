<?php
namespace Kurzor\Queue\Entity;

use Kurzor\Queue\Exception\Retry;
use Kurzor\Queue\Helper;

/**
 * Class Job encapsulates the task class and allows to run it.
 *
 * @package Kurzor\Queue\Entity
 */
class Job
{
    const MAX_ATTEMPTS = 5;

    /**
     * @var String name of the worker executing the job
     */
    protected $worker_name = null;

    /**
     * @var Int id of the job in db queue table
     */
    protected $job_id = null;


    /**
     * @var Int number of job reschedule after error run after we will give up
     */
    protected $max_attempts = null;


    /**
     * @var \Kurzor\Queue\Helper Queue helper class
     */
    protected $helper = null;

    /**
     * Setup the Job runner class. Set options different then default one.
     *
     * @param $worker_name
     * @param $job_id
     * @param Helper $helper
     * @param array $options
     */
    public function __construct($worker_name, $job_id, Helper $helper, $options = array())
    {
        $this->helper = $helper;
        $this->worker_name = $worker_name;
        $this->job_id = $job_id;
        $this->max_attempts = isset($options["max_attempts"]) ? $options["max_attempts"] : self::MAX_ATTEMPTS;
    }


    /**
     * Try to run the task.
     *
     * @return bool finished ok?
     */
    public function run()
    {
        // pull the handler (serialized class) from the db
        $handler = $this->getHandler();

        // @todo check if is of correct data type - has base class Task
        if (!is_object($handler)) {
            $msg = "[JOB] bad handler for job::{$this->job_id}";
            $this->finishWithError($msg);
            return false;
        }

        // run the task
        try {
            // @todo make if statement / task must return true on success, mark as failed otherwise
            $handler->perform();

            // if finished
            $this->finish();
            return true;
        } catch (Retry $e) { // retry to reschedule
            // attempts hasn't been incremented yet.
            $attempts = $this->getAttempts()+1;

            $msg = "caught Exception\\Retry \"{$e->getMessage()}\" on attempt $attempts/{$this->max_attempts}.";

            if ($attempts == $this->max_attempts) {
                $msg = "[JOB] job::{$this->job_id} $msg Giving up.";
                $this->finishWithError($msg, $handler);
            } else {
                $this->helper->log(
                    "[JOB] job::{$this->job_id} $msg Try again in {$e->getDelay()} seconds.",
                    Helper::WARN
                );
                $this->retryLater($e->getDelay());
            }
            return false;

        } catch (Exception $e) { // we have some exception other the Retry - mark execution as failed
            $this->finishWithError($e->getMessage(), $handler);
            return false;
        }
    }


    /**
     * Try to aquire lock for this Job (task from queue).
     *
     * @return bool can be lock aquired?
     */
    public function acquireLock()
    {
        $this->helper->log(
            "[JOB] attempting to acquire lock for job::{$this->job_id} on {$this->worker_name}",
            Helper::INFO
        );

        $lock = $this->helper->runUpdate(
            "UPDATE {$this->helper->jobsTable} SET locked_at = NOW(), locked_by = ? " .
            "WHERE id = ? AND (locked_at IS NULL OR locked_by = ?) AND failed_at IS NULL",
            array($this->worker_name, $this->job_id, $this->worker_name)
        );

        // some error met
        if (!$lock) {
            $this->helper->log("[JOB] failed to acquire lock for job::{$this->job_id}", Helper::INFO);
            return false;
        }

        return true;
    }


    /**
     * Invoked in methods finishWithError or retryLater. Will release the lock for this Job in db table.
     */
    public function releaseLock()
    {
        $this->helper->runUpdate(
            "UPDATE {$this->helper->jobsTable} SET locked_at = NULL, locked_by = NULL WHERE id = ?",
            array($this->job_id)
        );
    }


    /**
     * Finish the job execution by deleting the entry from db.
     *
     */
    public function finish()
    {
        // @todo store execution stats here into db

        $this->helper->runUpdate(
            "DELETE FROM {$this->helper->jobsTable} WHERE id = ?",
            array($this->job_id)
        );

        $this->helper->log("[JOB] completed job::{$this->job_id}", Helper::INFO);
    }


    /**
     * Mark execution as failed and attach the message.
     *
     * @param $error error message - custom or exception message thrown from the class
     * @param $handler task class instance
     */
    public function finishWithError($error, $handler = null)
    {
        $this->helper->runUpdate(
            "UPDATE {$this->helper->jobsTable} SET attempts = attempts + 1, " .
            "failed_at = IF(attempts >= ?, NOW(), NULL), error = IF(attempts >= ?, ?, NULL) WHERE id = ?",
            array($this->max_attempts, $this->max_attempts, $error, $this->job_id)
        );

        $this->helper->log($error, Helper::ERROR);
        $this->helper->log("[JOB] failure in job::{$this->job_id}", Helper::ERROR);
        $this->releaseLock();

        if ($handler && ($this->getAttempts() == $this->max_attempts) && method_exists($handler, '_onJobRetryError')) {
            $handler->_onJobRetryError($error); // call custom handler when number of reschedules exceeded
        }

        // @todo send here some email message with error? Or put it into logger?
    }


    /**
     * Reschedule the task execution upon some type of Exception from task perform method.
     *
     * @param $delay delay in seconds
     */
    public function retryLater($delay)
    {
        $this->helper->runUpdate(
            "UPDATE {$this->helper->jobsTable} " .
            "SET run_at = DATE_ADD(NOW(), INTERVAL ? SECOND) attempts = attempts + 1 WHERE id = ?",
            array($delay, $this->job_id)
        );

        // release the aquired lock
        $this->releaseLock();
    }


    /**
     * Get unserialized object from queue.
     *
     * @return mixed false on some error
     */
    public function getHandler()
    {
        $rs = $this->helper->runQuery(
            "SELECT handler FROM {$this->helper->jobsTable} WHERE id = ?",
            array($this->job_id)
        );

        return isset($rs[0]["handler"]) ? unserialize($rs[0]["handler"]) : false;
    }


    /**
     * Get the number of attempts to execute the task - task may finish with Exception asking to reschedule
     *
     * @return bool|Int false on error, number of attempts otherwise
     */
    public function getAttempts()
    {
        $rs = $this->helper->runQuery(
            "SELECT attempts FROM {$this->helper->jobsTable} WHERE id = ?",
            array($this->job_id)
        );

        return isset($rs[0]["attempts"]) ? $rs[0]["attempts"] : false;
    }
}
