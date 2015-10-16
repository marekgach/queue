<?php
namespace Kurzor;

/**
 * Class DateTime
 * Extended class to handle dates. The main benefit is ability to set some fixed testing date using setNow static method.
 * Then if this DateTime class is used in whole application, it can simulate some other date in system then today. It is
 * good for unittests and other types of testing.
 *
 * @package Kurzor
 */
class DateTime extends \DateTime
{
    /**
     * Custom fixed date for app - if set. Defaults to null.
     *
     * @var \Kurzor\DateTime
     */
    private static $now = null;

    // 18.7.1985 18:02:53
    const HUMAN_FULL = 'j.n.Y G:i:s';

    // 18:02
    const HUMAN_TIME = 'G:i';

    // 18.7.1985
    const HUMAN_DATE = 'j.n.Y';

    // 1985-07-18
    const DB_DATE = 'Y-m-d';

    // 1985-07-18 18:02:53
    const DB_FULL = 'Y-m-d H:i:s';

    /**
     * Constants fro parts to be used as a unit for sub and add methods.
     */
    const PART_SECOND = 'second',
        PART_MINUTE = 'minute',
        PART_HOUR   = 'hour',
        PART_DAY    = 'day',
        PART_WEEK   = 'week',
        PART_MONTH  = 'month',
        PART_YEAR   = 'year';


    /**
     * Construct the instance of class.
     *
     * @param null $time time in format for regular \DateTime object
     * @param \DateTimeZone $object time zone
     */
    public function  __construct($time = null, \DateTimeZone $object = null)
    {
        if (!$time && self::$now) {
            $time = self::now()->format(self::DB_FULL);
        }

        if ($object) {
            parent::__construct($time, $object);
        } else {
            parent::__construct($time);
        }
    }


    /**
     * Factory method to get instance \Kurzor\DateTime from db date, time or datetime
     *
     * @static
     * @param string $timeFromDb date, datetime or time in db format
     * @return \Kurzor\DateTime new instance of this class
     */
    public static function fromDb($timeFromDb)
    {
        $format = self::DB_FULL;

        if (strpos($timeFromDb, ':') === false) {
            // only date
            $format = self::DB_DATE;
        }

        if (strpos($timeFromDb, '-') === false) {
            // only time
            $format = 'H:i:s';
        }


        return self::createFromFormat($format, $timeFromDb);
    }


    /**
     * Factory method to get instance \Kurzor\DateTime from \DateTime object
     *
     * @static
     * @param \DateTime $dateTime date time object
     * @return DateTime new instance of this class
     */
    public static function fromDateTime(\DateTime $dateTime)
    {
        return new self($dateTime->format(self::DB_FULL));
    }


    /**
     * Factory method to get instance \Kurzor\DateTime from the timestamp
     *
     * @static
     * @param int $timestamp timestamp as integer
     * @return \Kurzor\DateTime new instance of this class
     */
    public static function fromTimestamp($timestamp)
    {
        return new self("@$timestamp");
    }


    /**
     * Method to allow to use this factory method in one command \Kurzor\DateTime::now()->addPart ...
     *
     * @static
     * @return \Kurzor\DateTime new instance of this class
     */
    public static function now()
    {
        if (isset (self::$now)) {
            return clone(self::$now);
        }

        return new self;
    }


    /**
     * Add some number of units to this object date
     *
     * @param int $number number of units
     * @param string $part unit type
     * @return \Kurzor\DateTime
     */
    public function subPart($number, $part)
    {
        $interval = new \DateInterval($this->getIntervalString($number, $part));

        return $this->sub($interval);
    }


    /**
     * Sub some number of units from this object date
     *
     * @param int $number number of units
     * @param string $part unit type
     * @return \Kurzor\DateTime
     */
    public function addPart($number, $part)
    {
        $interval = new \DateInterval($this->getIntervalString($number, $part));

        return $this->add($interval);
    }


    /**
     * Set time to 00:00:00
     * @return \Kurzor\DateTime
     */
    public function resetTime()
    {
        $this->setTime(0, 0, 0);

        return $this;
    }


    /**
     * Set time to 23:59:59
     * @return \Kurzor\DateTime
     */
    public function maxTime()
    {
        $this->setTime(23, 59, 59);

        return $this;
    }


    /**
     * Set time to 00:00:01
     * @return \Kurzor\DateTime
     */
    public function minTime()
    {
        $this->setTime(0, 0, 1);

        return $this;
    }


    /**
     * Inject some custom date to be our new NOW
     *
     * @param \Kurzor\DateTime|null $now object to inject date from
     */
    public static function setNow(\DateTime $now = null)
    {
        self::$now = null;

        if ($now) {
            self::$now = clone($now);
        }
    }


    /**
     * Helper method allows to get interval string for sub or add methods in this class.
     *
     * @param int $number number of units
     * @param string $part unit type as constant PART_MINUTE, PART_DAY
     * @return string Interval string
     * @throws \InvalidArgumentException
     */
    private function getIntervalString($number, $part)
    {
        switch ($part) {
            case self::PART_SECOND:
                $interval = "PT{$number}S";
                break;
            case self::PART_MINUTE:
                $interval = "PT{$number}M";
                break;
            case self::PART_HOUR:
                $interval = "PT{$number}H";
                break;
            case self::PART_DAY:
                $interval = "P{$number}D";
                break;
            case self::PART_WEEK:
                $interval = "P".(string)($number*7)."D";
                break;
            case self::PART_MONTH:
                $interval = "P{$number}M";
                break;
            case self::PART_YEAR:
                $interval = "P{$number}Y";
                break;
            default:
                throw new \InvalidArgumentException('Chybna date part '. $part);
        }

        return $interval;
    }


    /**
     * Check if date is saturday or sunday
     *
     * @return bool bool if weekend
     */
    public function isWeekend()
    {
        $dayOfWeek = $this->format('w');

        return $dayOfWeek == 0 || $dayOfWeek == 6;
    }


    /**
     * Return date as a readable string.
     *
     * @return string String representation
     */
    public function __toString()
    {
        return $this->format(self::HUMAN_FULL);
    }


    /**
     * Make a copy of the object
     *
     * @return \Kurzor\DateTime
     */
    public function getCopy()
    {
        return clone $this;
    }
}
