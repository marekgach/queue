<?php
namespace Kurzor\Queue;

/**
 *
 * Class Service to modify Task queue - enqueue and statistics.
 * @package Kurzor\Queue
 */
class Service
{
    /**
     * @var \Kurzor\Queue\Helper Helper class for queue properties manipulation and PDO queries
     */
    protected $helper = null;

    /**
     * @param Helper $helper
     */
    public function __construct(Helper $helper)
    {
        $this->helper = $helper;
    }

    /**
     * @return Helper
     */
    public function getHelper()
    {
        return $this->helper;
    }

    /**
     * @param Helper $helper
     */
    public function setHelper(Helper $helper)
    {
        $this->helper = $helper;
    }

    /**
     * Enqueue task handler into task queue. Handler is concrete instance of class Task.
     *
     * @param $handler instance of class based on Task abstract class
     * @param string $queue queue name. There may be more then 1 queue in system.
     * @param null $run_at datetime when the task should be run. NOW by defaultt
     * @return bool|Int false on error, task Id in db queue on success
     */
    public function enqueue($handler, $queue = "default", \DateTime $run_at = null)
    {
        // @todo check class is instance of Task class

        if ($run_at && $run_at->getTimestamp() < time()) {
            throw new Exception('Cannot set job to be run in the past!');
        }

        $created_at = date('Y-m-d H:i:s'); // need to use instead of NOW() statement to work also in SQLite
        $run_at = $run_at ? $run_at->format('Y-m-d H:i:s') : null; // if not null format as string, else leave null

        $affected = $this->helper->runUpdate(
            "INSERT INTO " . $this->helper->jobsTable . " (handler, queue, run_at, created_at) VALUES(?, ?, ?, ?)",
            array(serialize($handler), (string) $queue, $run_at, $created_at)
        );

        // @codeCoverageIgnoreStart
        if ($affected < 1) {
            $this->helper->log("[JOB] failed to enqueue new job", Helper::ERROR);
            return false;
        }
        // @codeCoverageIgnoreEnd

        return $this->helper->getConnection()->lastInsertId(); // return the job ID, for manipulation later
    }


    /**
     * Enqueue array of task handlers into task queue. Handler is concrete instance of class Task. May not be the
     * instance if the same class.
     *
     * @param array $handlers array of instances of class based on Task abstract class. May not be the same class.
     * @param string $queue queue name. There may be more then 1 queue in system. The name must be valid name for queue
     * and worker (bin/worker_default.php) must be run for it.
     * @param null $run_at datetime when the task should be run. ASAP by default.
     * @return bool if success
     */
    public function bulkEnqueue(array $handlers, $queue = "default", \DateTime $run_at = null)
    {
        if ($run_at && $run_at->getTimestamp() < time()) {
            throw new Exception('Cannot set job to be run in the past!');
        }

        $created_at = date('Y-m-d H:i:s'); // need to use instead of NOW() statement to work also in SQLite
        $run_at = $run_at ? $run_at->format('Y-m-d H:i:s') : null; // if not null format as string, else leave null

        $affected = 0;
        $sql = "INSERT INTO {$this->helper->jobsTable} (handler, queue, run_at, created_at)" .
               "VALUES (?, ?, ?, '$created_at')";

        foreach ($handlers as $handler) {
            $parameters = array();
            $parameters[] = serialize(($handler));
            $parameters[] = (string)$queue;
            $parameters[] = $run_at;

            $affected = $this->helper->runUpdate($sql, $parameters);

            // @codeCoverageIgnoreStart
            if ($affected < 1) {
                $this->helper->log("[JOB] failed to enqueue new jobs", Helper::ERROR);
            } else {
                $affected++;
            }
        }

        if ($affected != count($handlers)) {
            $this->helper->log("[JOB] failed to enqueue some new jobs", Helper::ERROR);
        }
        // @codeCoverageIgnoreEnd

        return true;
    }


    /**
     * Easy stats about given queue. They are made only of not executed or failed tasks - those stored in db at the
     * moment.
     *
     * @param string $queue queue name to get stats
     * @return array Array containing stats.
     */
    public function getStatus($queue = "default")
    {
        $rs = $this->helper->runQuery(
            "SELECT COUNT(*) as total, COUNT(failed_at) as failed, COUNT(locked_at) as locked " .
            "FROM `" . $this->helper->jobsTable . "` " .
            "WHERE queue = ?",
            array($queue)
        );
        $rs = $rs[0];

        $failed = (int) $rs["failed"];
        $locked = (int) $rs["locked"];
        $total  = (int) $rs["total"];
        $outstanding = $total - $locked - $failed;

        return array(
            "outstanding" => $outstanding,
            "locked" => $locked,
            "failed" => $failed,
            "total"  => $total
        );
    }
}
