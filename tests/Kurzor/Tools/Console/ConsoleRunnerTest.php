<?php
namespace Kurzor\Tools\Console;

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

        $this->db->dbName = 'xxx';
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

    public function test_addCommands()
    {
        $cli = $this->getMockBuilder('Symfony\Component\Console\Application')
            ->disableOriginalConstructor()
            ->getMock();

        $cli->expects($this->once())
            ->method('addCommands');

        $this->cr->addCommands($cli);
    }

    public function test_run()
    {
        // mock Application and mock ALL methods
        $app = $this->getMockBuilder('Symfony\Component\Console\Application')
            ->disableOriginalConstructor()
            ->getMock();

        // expect to get invoked to set application helper set
        $app->expects($this->once())
            ->method('setHelperSet');

        // expect to get invoked to set custom commands
        $app->expects($this->once())
            ->method('addCommands');

        // create ConsoleRunner and mock getApplication method
        $cr = $this->getMockBuilder('Kurzor\Tools\Console\ConsoleRunner')
            ->disableOriginalConstructor()
            ->setMethods(array('getApplication', 'addCommands'))
            ->getMock();

        $cr->expects($this->once())
            ->method('getApplication')
            ->will($this->returnValue($app));

        $cr->expects($this->once())
            ->method('addCommands');

        $hs = $this->cr->createHelperSet($this->db);

        $cr->run($hs);
    }

    public function test_getApplication()
    {
        $app = $this->cr->getApplication();

        // check return type
        $this->assertInstanceOf('Symfony\Component\Console\Application', $app);
    }
}
