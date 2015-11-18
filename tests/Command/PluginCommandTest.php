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

        $streamOut->expects($this->once())
            ->method('__toString')
            ->willReturn('bin-response');

        $mock = $this->getMockBuilder(PluginCommand::CLASS)
            ->setMethods(['createCompiler', 'createStream'])
            ->getMock();

        $mock->expects($this->once())
            ->method('createCompiler')
            ->willReturn($compiler);

        $mock->expects($this->once())
            ->method('createStream')
            ->willReturn($streamIn);

        $compiler->expects($this->once())
            ->method('compile')
            ->willReturn($streamOut)
            ->with($this->equalTo($streamIn));

        $application   = new Application();

        $application->add($mock);

        $command       = $application->find('protobuf:plugin');
        $commandTester = new CommandTester($command);

        $commandTester->execute([]);

        $this->assertEquals('bin-response', $commandTester->getDisplay());
    }

    public function testExecuteException()
    {
        $application = new Application();
        $exception   = new \Exception('Test exception message');
        $compiler    = $this->getMockBuilder('Protobuf\Compiler\Compiler')
            ->disableOriginalConstructor()
            ->getMock();

        $streamIn = $this->getMockBuilder('Protobuf\Stream')
            ->disableOriginalConstructor()
            ->getMock();

        $mock = $this->getMockBuilder(PluginCommand::CLASS)
            ->setMethods(['createCompiler', 'createStream'])
            ->getMock();

        $mock->expects($this->once())
            ->method('createCompiler')
            ->willReturn($compiler);

        $mock->expects($this->once())
            ->method('createStream')
            ->willReturn($streamIn);

        $compiler->expects($this->once())
            ->method('compile')
            ->with($this->equalTo($streamIn))
            ->will($this->throwException($exception));

        $application->add($mock);

        $command       = $application->find('protobuf:plugin');
        $commandTester = new CommandTester($command);

        $commandTester->execute([]);

        $this->assertContains('Test exception message', $commandTester->getDisplay());
    }

    public function testCreateStream()
    {
        $command  = new PluginCommand();
        $compiler = $this->invokeMethod($command, 'createStream');

        $this->assertInstanceOf('Protobuf\Stream', $compiler);
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
