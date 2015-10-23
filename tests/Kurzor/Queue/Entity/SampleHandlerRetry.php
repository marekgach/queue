<?php
namespace Kurzor\Queue;

use Kurzor\Queue\Exception\Retry;

class SampleHandlerRetry
{
    public function perform()
    {
        throw new Retry(100);
    }
}
