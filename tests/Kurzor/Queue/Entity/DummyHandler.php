<?php
namespace Kurzor\Queue;

class DummyHandler
{
    public function _onJobRetryError()
    {
        return null;
    }
}
