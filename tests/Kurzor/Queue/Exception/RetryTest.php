<?php
namespace Kurzor\Queue\Exception;

/**
 *
 */
class RetryTest extends \PHPUnit_Framework_TestCase
{
    public function test_setGetDelay()
    {
        $exception = new Retry();

        $delay = '100';

        $exception->setDelay($delay);
        $this->assertEquals($delay, $exception->getDelay());
    }
}
