<?php
namespace Kurzor\Queue;

use Doklad\Service\Locator;

/**
 * @todo rewrite to use doctrine db (Pdo object <- inject to helper constructor)
 *
 * Class Service to modify Task queue - enqueue and statistics.
 * @package Kurzor\Queue
 */
class Service {
    /**
     * @var /Queue/Helper Helper class for queue properties manipulation and PDO queries
     */
    protected static $helper = null;


    /**
     * Create helper class instance.
     */
    private static function initHelper() {
        if(static::$helper == null) {
            static::$helper = new Helper(Locator::getConfig('database'));
        }
    }

    /**
     * Enqueue task handler into task queue. Handler is concrete instance of class Task.
     *
     * @param $handler instance of class based on Task abstract class
     * @param string $queue queue name. There may be more then 1 queue in system. The name must be valid name for queue
     * and worker (bin/worker_default.php) must be run for it.
     * @param null $run_at datetime when the task should be run. ASAP by default.
     * @return bool|Int false on error, task Id in db queue on success
     */
    public static function enqueue($handler, $queue = "default", $run_at = null) {
        static::initHelper();

        // @todo check class is instance of Task class
        // @todo check allowed queue names - set them into config

        $affected = static::$helper->runUpdate(
            "INSERT INTO " . static::$helper->jobsTable . " (handler, queue, run_at, created_at) VALUES(?, ?, ?, NOW())",
            array(serialize($handler), (string) $queue, $run_at)
        );

        if ($affected < 1) {
            static::$helper->log("[JOB] failed to enqueue new job", Helper::ERROR);
            return false;
        }

        return static::$helper->getConnection()->lastInsertId(); // return the job ID, for manipulation later
    }


    /**
     * Enqueue array of task handlers into task queue. Handler is concrete instance of class Task. May not be the instance
     * if the same class.
     *
     * @param array $handlers array of instances of class based on Task abstract class. May not be the same class.
     * @param string $queue queue name. There may be more then 1 queue in system. The name must be valid name for queue
     * and worker (bin/worker_default.php) must be run for it.
     * @param null $run_at datetime when the task should be run. ASAP by default.
     * @return bool if success
     */
    public static function bulkEnqueue(array $handlers, $queue = "default", $run_at = null) {
        static::initHelper();

        $sql = "INSERT INTO " . self::$helper->jobsTable . " (handler, queue, run_at, created_at) VALUES";
        $sql .= implode(",", array_fill(0, count($handlers), "(?, ?, ?, NOW())"));

        $parameters = array();
        foreach ($handlers as $handler) {
            $parameters []= serialize(($handler));
            $parameters []= (string) $queue;
            $parameters []= $run_at;
        }
        $affected = static::$helper->runUpdate($sql, $parameters);

        if ($affected < 1) {
            static::$helper->log("[JOB] failed to enqueue new jobs", Helper::ERROR);
            return false;
        }

        if ($affected != count($handlers))
            static::$helper->log("[JOB] failed to enqueue some new jobs", Helper::ERROR);

        return true;
    }


    /**
     * Easy stats about given queue. They are made only of not executed or failed tasks - those stored in db at the
     * moment.
     *
     * @param string $queue queue name to get stats
     * @return array Array containing stats.
     */
    public static function status($queue = "default") {
        static::initHelper();

        $rs = self::$helper->runQuery("
                SELECT COUNT(*) as total, COUNT(failed_at) as failed, COUNT(locked_at) as locked
                FROM `" . self::$helper->jobsTable . "`
                WHERE queue = ?
            ", array($queue));
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