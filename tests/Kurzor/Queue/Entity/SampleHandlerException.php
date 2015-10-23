<?php
namespace Kurzor\Queue;

class SampleHandlerException
{
    public function perform()
    {
        throw new \Exception('super exception!');
    }
}