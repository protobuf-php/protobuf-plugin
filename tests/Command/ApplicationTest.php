<?php

namespace ProtobufCompilerTest;

use ProtobufCompilerTest\TestCase;
use Protobuf\Compiler\Command\Application;

class ApplicationTest extends TestCase
{
     /**
     * @var \Protobuf\Compiler\Command\GenerateCommand
     */
    private $generateCommand;

    /**
     * @var \Protobuf\Compiler\Command\PluginCommand
     */
    private $pluginCommand;

     /**
     * @var \Symfony\Component\Console\Input\InputInterface
     */
    private $input;

    public function setUp()
    {
        $this->input   = $this->getMock('Symfony\Component\Console\Input\InputInterface');

        $this->generateCommand = $this->getMockBuilder('Symfony\Component\Console\Command\Command')
            ->disableOriginalConstructor()
            ->getMock();

        $this->pluginCommand = $this->getMockBuilder('Symfony\Component\Console\Command\Command')
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testSingleCommandApplication()
    {
        $this->generateCommand->expects($this->once())
            ->method('getName')
            ->willReturn('my-command');

        $application     = new Application($this->generateCommand, $this->pluginCommand);
        $definition      = $this->invokeMethod($application, 'getDefinition');
        $defaultCommands = $this->invokeMethod($application, 'getDefaultCommands');
        $commandName     = $this->invokeMethod($application, 'getCommandName', [$this->input]);

        $this->assertEquals('my-command', $commandName);
        $this->assertEquals([], $definition->getArguments());
        $this->assertSame($this->generateCommand, $defaultCommands[2]);
        $this->assertSame($this->pluginCommand, $defaultCommands[3]);
    }
}
