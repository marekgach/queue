<?php
namespace Kurzor\Queue\Exception;

use Kurzor\Queue\Exception;

/**
 * Class Retry implementing exception when task failed and throw this exception it will be rescheduled.
 *
 * @package Kurzor\Queue\Exception
 */
class Retry extends Exception
{
    /**
     * @var int default delay of execution
     */
    protected $delay_seconds = 7200;


    /**
     * Set another delay duration then default into exception.
     *
     * @param $delay delay duration
     */
    public function setDelay($delay)
    {
        $this->delay_seconds = $delay;
    }


    /**
     * Get task delay set into exception
     *
     * @return int delay
     */
    public function getDelay()
    {
        return $this->delay_seconds;
    }
}
