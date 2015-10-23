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
