<?php
namespace Kurzor\DateTime;
use Kurzor\DateTime;

/**
 *
 */
class DateTimeTest extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {

    }

    public function test_construct_default()
    {
        $datetime = new DateTime('2015-01-01 15:15:55');
        $this->assertEquals('2015-01-01 15:15:55', $datetime->format(DateTime::DB_FULL));
    }

    public function test_construct_withNow()
    {
        DateTime::setNow();

        $datetime = new DateTime();
        $this->assertEquals(time(), $datetime->getTimestamp());
    }

    public function test_construct_timezone()
    {
        $datetime = new DateTime('2015-01-01 15:15:55', new \DateTimeZone('America/New_York'));
        $this->assertEquals('2015-01-01 15:15:55-05:00', $datetime->format(DateTime::DB_FULL.'P'));
    }

    public function test_fromDb_partial()
    {
        $date = DateTime::fromDb('2015-01-01');

        // will leave the current time - so is need to have reqexp
        $this->assertRegExp('/2015-01-01 .*/', $date->format(DateTime::DB_FULL));
    }

    public function test_fromDb_full()
    {
        $date = DateTime::fromDb('2015-01-01 15:15:55');

        $this->assertEquals('2015-01-01 15:15:55', $date->format(DateTime::DB_FULL));
    }

    public function test_fromDb_time()
    {
        $date = DateTime::fromDb('15:15:55');

        $this->assertRegExp('/20.* 15:15:55/', $date->format(DateTime::DB_FULL));
    }

    public function test_fromDateTime()
    {
        $datetime = new DateTime('2015-01-01 15:15:55');
        $date = DateTime::fromDateTime($datetime);

        $this->assertEquals($date, $datetime);
    }

    public function test_fromTimestamp()
    {
        $timestamp = 1272509157;
        $date = DateTime::fromTimestamp($timestamp);

        $this->assertEquals('2010-04-29 02:45:57', $date->format(DateTime::DB_FULL));
    }

    public function test_subPart()
    {
        $datetime = new DateTime('2015-01-01 15:15:55');
        $datetime->subPart(1, DateTime::PART_DAY);
        $this->assertEquals('2014-12-31 15:15:55', $datetime->format(DateTime::DB_FULL));
    }

    public function test_addPart()
    {
        $datetime = new DateTime('2015-01-01 15:15:55');
        $datetime->addPart(1, DateTime::PART_DAY);
        $this->assertEquals('2015-01-02 15:15:55', $datetime->format(DateTime::DB_FULL));
    }

    public function test_resetTime()
    {
        $datetime = new DateTime('2015-01-01 15:15:55');
        $datetime->resetTime();
        $this->assertEquals('2015-01-01 00:00:00', $datetime->format(DateTime::DB_FULL));
    }

    public function test_maxTime()
    {
        $datetime = new DateTime('2015-01-01 15:15:55');
        $datetime->maxTime();
        $this->assertEquals('2015-01-01 23:59:59', $datetime->format(DateTime::DB_FULL));
    }

    public function test_minTime()
    {
        $datetime = new DateTime('2015-01-01 15:15:55');
        $datetime->minTime();
        $this->assertEquals('2015-01-01 00:00:01', $datetime->format(DateTime::DB_FULL));
    }

    public function test_setNow()
    {
        DateTime::setNow(new DateTime('2015-01-01 15:15:55'));
        $this->assertEquals('2015-01-01 15:15:55', DateTime::now()->format(DateTime::DB_FULL));
    }

    public function test_setNow_reset()
    {
        DateTime::setNow(new DateTime('2015-01-01 15:15:55'));
        DateTime::setNow();
        $this->assertNotEquals('2015-01-01 15:15:55', DateTime::now()->format(DateTime::DB_FULL));
    }

    public function test_getIntervalString()
    {
        $date = new DateTime;

        $this->assertEquals('PT10S', \TestHelper::callPrivateMethod($date, 'getIntervalString', 10, DateTime::PART_SECOND));
        $this->assertEquals('PT15M', \TestHelper::callPrivateMethod($date, 'getIntervalString', 15, DateTime::PART_MINUTE));
        $this->assertEquals('PT20H', \TestHelper::callPrivateMethod($date, 'getIntervalString', 20, DateTime::PART_HOUR));
        $this->assertEquals('P30D', \TestHelper::callPrivateMethod($date, 'getIntervalString', 30, DateTime::PART_DAY));
        $this->assertEquals('P14D', \TestHelper::callPrivateMethod($date, 'getIntervalString', 2, DateTime::PART_WEEK));
        $this->assertEquals('P2M', \TestHelper::callPrivateMethod($date, 'getIntervalString', 2, DateTime::PART_MONTH));
        $this->assertEquals('P2Y', \TestHelper::callPrivateMethod($date, 'getIntervalString', 2, DateTime::PART_YEAR));
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Not correct date part 15
     */
    public function test_getIntervalString_badPart()
    {
        $date = new DateTime;
        $this->assertEquals('P2Y', \TestHelper::callPrivateMethod($date, 'getIntervalString', 2, 15));
    }

    public function test_isWeekend()
    {
        $date = new DateTime('2015-10-10');
        $this->assertTrue($date->isWeekend());

        $date = new DateTime('2015-10-12');
        $this->assertFalse($date->isWeekend());
    }

    public function test_toString()
    {
        $date = new DateTime('2015-01-04 10:15:15');
        $text = $date->__toString();

        $this->assertSame($text, '4.1.2015 10:15:15');
    }

    public function test_getCopy()
    {
        $date = new DateTime();
        $copy = $date->getCopy();

        $this->assertInstanceOf('\Kurzor\DateTime', $copy);
        $this->assertNotSame($date, $copy);
    }
}
