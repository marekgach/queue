<?php
namespace Kurzor\Tools\Console\Command;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\StreamOutput;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Input\ArgvInput;

/**
 *
 */
class RunWorkerTest extends \PHPUnit_Framework_TestCase
{
    public function test_configure()
    {
        // we want only to test our method "configure" so mock Symfony base class methods invoked on self
        $command = $this->getMockBuilder('Kurzor\Tools\Console\Command\RunWorker')
            ->disableOriginalConstructor()
            ->setMethods(array('setName', 'setDescription', 'setDefinition', 'setHelp'))
            ->getMock();

        $command->expects($this->once())
            ->method('setName')
            ->willReturnSelf();

        $command->expects($this->once())
            ->method('setDescription')
            ->willReturnSelf();

        $command->expects($this->once())
            ->method('setDefinition')
            ->willReturnSelf();

        $command->expects($this->once())
            ->method('setHelp')
            ->willReturnSelf();

        \TestHelper::callPrivateMethod($command, 'configure');
    }

    public function test_getWorker()
    {
        $command = $this->getMockBuilder('Kurzor\Tools\Console\Command\RunWorker')
            ->disableOriginalConstructor()
            ->setMethods(array('getHelper'))
            ->getMock();

        // mock queue Helper
        $helper = $this->getMockBuilder('\Kurzor\Queue\Helper')
            ->disableOriginalConstructor()
            ->getMock();

        $command->expects($this->once())
            ->method('getHelper')
            ->will($this->returnValue($helper));

        $return = \TestHelper::callPrivateMethod($command, 'getWorker');

        $this->assertInstanceOf('\Kurzor\Queue\Entity\Worker', $return);
    }

    public function test_execute()
    {
        // partialy mock Command
        $command = $this->getMockBuilder('Kurzor\Tools\Console\Command\RunWorker')
            ->disableOriginalConstructor()
            ->setMethods(array('getWorker'))
            ->getMock();

        // mock Worker
        $worker = $this->getMockBuilder('\Kurzor\Queue\Entity\Worker')
            ->disableOriginalConstructor()
            ->getMock();

        // return mocked Worker class from class getter
        $command->expects($this->once())
            ->method('getWorker')
            ->will($this->returnValue($worker));

        // Worker start method must be invoked
        $worker->expects($this->once())
            ->method('start');

        // mock input
        $input = $this->getMockBuilder('Symfony\Component\Console\Input\ArrayInput')
            ->disableOriginalConstructor()
            ->getMock();

        // mock method to get param
        $input->expects($this->once())
            ->method('getOption')
            ->will($this->returnValue('mail'));

        // create null output
        $output = new NullOutput();

        // call execute method and assert return code
        $return = \TestHelper::callPrivateMethod($command, 'execute', $input, $output);
        $this->assertSame(0, $return);
    }
}
