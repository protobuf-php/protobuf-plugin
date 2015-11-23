<?php

namespace ProtobufCompilerTest;

use ProtobufCompilerTest\TestCase;
use Symfony\Component\Console\Application;
use Protobuf\Compiler\Command\PluginCommand;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Console\Output\OutputInterface;

class PluginCommandTest extends TestCase
{
    public function testExecute()
    {
        $compiler = $this->getMockBuilder('Protobuf\Compiler\Compiler')
            ->disableOriginalConstructor()
            ->getMock();

        $streamIn = $this->getMockBuilder('Protobuf\Stream')
            ->disableOriginalConstructor()
            ->getMock();

        $streamOut = $this->getMockBuilder('Protobuf\Stream')
            ->disableOriginalConstructor()
            ->getMock();
        $mock = $this->getMockBuilder(PluginCommand::CLASS)
            ->setMethods(['createCompiler', 'writeStream'])
            ->getMock();

        $mock->setStream($streamIn);

        $mock->expects($this->once())
            ->method('createCompiler')
            ->willReturn($compiler);

        $mock->expects($this->once())
            ->method('writeStream')
            ->with($streamOut);

        $compiler->expects($this->once())
            ->method('compile')
            ->willReturn($streamOut)
            ->with($this->equalTo($streamIn));

        $application   = new Application();

        $application->add($mock);

        $command       = $application->find('protobuf:plugin');
        $commandTester = new CommandTester($command);

        $commandTester->execute([]);
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage Unable to read standard input
     */
    public function testExecuteException()
    {
        $application = new Application();
        $mock        = $this->getMockBuilder(PluginCommand::CLASS)
            ->setMethods(['createCompiler', 'createStream'])
            ->getMock();

        $application->add($mock);

        $command       = $application->find('protobuf:plugin');
        $commandTester = new CommandTester($command);

        $commandTester->execute([]);
    }

    public function testCreateProcessBuilder()
    {
        $command  = new PluginCommand();
        $output   = $this->getMock('Symfony\Component\Console\Output\OutputInterface');
        $compiler = $this->invokeMethod($command, 'createCompiler', [$output]);

        $this->assertInstanceOf('Protobuf\Compiler\Compiler', $compiler);
    }

    public function testCreateConsoleLogger()
    {
        $command  = new PluginCommand();
        $output   = $this->getMock('Symfony\Component\Console\Output\OutputInterface');
        $compiler = $this->invokeMethod($command, 'createConsoleLogger', [$output]);

        $this->assertInstanceOf('Symfony\Component\Console\Logger\ConsoleLogger', $compiler);
    }
}
