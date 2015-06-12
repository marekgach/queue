<?php
namespace Kurzor\Tools\Console;

use Kurzor\Tools\Console\Config\Db;

/**
 *
 */
class ConsoleRunnerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ConsoleRunner
     */
    protected $cr = null;

    /**
     * @var
     */
    protected $db = null;

    /**
     * Hook metod executed before each test.
     */
    protected function setUp()
    {
        $this->cr = new ConsoleRunner();
        $this->db = $this->getMockBuilder('Kurzor\Tools\Console\Config\Db')
            ->disableOriginalConstructor()
            ->getMock();

        $this->db->username = 'username';
        $this->db->password = 'pass';
    }

    public function test_createHelperSet()
    {
        $return = $this->cr->createHelperSet($this->db);

        // check return type
        $this->assertInstanceOf('Symfony\Component\Console\Helper\HelperSet', $return);

        // check have registered helper
        $this->assertTrue($return->has('queue'));
        $this->assertInstanceOf('Kurzor\Queue\Helper', $return->get('queue'));
    }

    public function test_printCliConfigTemplate()
    {
        $this->expectOutputRegex('/You are missing .*/');

        $this->cr->printCliConfigTemplate();
    }
}