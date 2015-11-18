<?php

namespace ProtobufCompilerTest;

use ProtobufCompilerTest\TestCase;
use Symfony\Component\Console\Application;
use Protobuf\Compiler\Command\GenerateCommand;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateCommandTest extends TestCase
{
    public function testExecute()
    {
        $builder = $this->getMockBuilder('Protobuf\Compiler\Protoc\ProcessBuilder')
            ->disableOriginalConstructor()
            ->getMock();

        $process = $this->getMockBuilder('Symfony\Component\Process\Process')
            ->disableOriginalConstructor()
            ->getMock();

        $mock = $this->getMockBuilder(GenerateCommand::CLASS)
            ->setMethods(['createProcessBuilder'])
            ->setConstructorArgs(['./bin/protobuf'])
            ->getMock();

        $mock->expects($this->once())
            ->method('createProcessBuilder')
            ->willReturn($builder);

        $builder->expects($this->once())
            ->method('createProcess')
            ->willReturn($process)
            ->with(
                $this->equalTo('./src'),
                $this->equalTo(['./file.proto']),
                $this->equalTo(['path-to-include']),
                $this->equalTo([
                    'skip-imported' => 1,
                    'verbose'       => 1,
                    'psr4'          => ['ProtobufTest\Protos']
                ])
            );

        $process->expects($this->once())
            ->method('run');

        $process->expects($this->once())
            ->method('getExitCode')
            ->willReturn(0);

        $process->expects($this->once())
            ->method('getOutput')
            ->willReturn('OK');

        $process->expects($this->once())
            ->method('getCommandLine')
            ->willReturn('"protoc command"');

        $application = new Application();

        $application->add($mock);

        $command       = $application->find('protobuf:generate');
        $commandTester = new CommandTester($command);

        $commandTester->execute([
            '--skip-imported' => true,
            '--out'           => './src',
            'protos'          => ['./file.proto'],
            '--protoc'        => '/usr/bin/protoc',
            '--include'       => ['path-to-include'],
            '--psr4'          => ['ProtobufTest\Protos'],
        ], ['verbosity' => OutputInterface::VERBOSITY_VERBOSE]);

        $this->assertContains('Generating protos with protoc -- "protoc command"', $commandTester->getDisplay());
        $this->assertContains('PHP classes successfully generate.', $commandTester->getDisplay());
    }

    public function testExecuteFail()
    {
        $builder = $this->getMockBuilder('Protobuf\Compiler\Protoc\ProcessBuilder')
            ->disableOriginalConstructor()
            ->getMock();

        $process = $this->getMockBuilder('Symfony\Component\Process\Process')
            ->disableOriginalConstructor()
            ->getMock();

        $mock = $this->getMockBuilder(GenerateCommand::CLASS)
            ->setMethods(['createProcessBuilder'])
            ->setConstructorArgs(['./bin/protobuf-plugin'])
            ->getMock();

        $mock->expects($this->once())
            ->method('createProcessBuilder')
            ->willReturn($builder);

        $builder->expects($this->once())
            ->method('createProcess')
            ->willReturn($process)
            ->with(
                $this->equalTo('./'),
                $this->equalTo(['./file.proto']),
                $this->equalTo([]),
                $this->equalTo([])
            );

        $process->expects($this->once())
            ->method('run');

        $process->expects($this->once())
            ->method('getExitCode')
            ->willReturn(255);

        $process->expects($this->once())
            ->method('getOutput')
            ->willReturn('Fail');

        $process->expects($this->once())
            ->method('getCommandLine')
            ->willReturn('"protoc command"');

        $application   = new Application();

        $application->add($mock);

        $command       = $application->find('protobuf:generate');
        $commandTester = new CommandTester($command);

        $commandTester->execute([
            'protos' => ['./file.proto']
        ]);

        $this->assertContains('protoc exited with an error (255) when executed', $commandTester->getDisplay());
    }

    public function testCreateProcessBuilder()
    {
        $command = new GenerateCommand('./bin/protobuf-plugin');
        $builder = $this->invokeMethod($command, 'createProcessBuilder', ['./bin/protobuf-plugin', '2.3.1']);

        $this->assertInstanceOf('Protobuf\Compiler\Protoc\ProcessBuilder', $builder);
    }
}
