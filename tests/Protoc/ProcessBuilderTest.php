<?php

namespace ProtobufCompilerTest\Compiler;

use org\bovigo\vfs\vfsStream;
use ProtobufCompilerTest\TestCase;
use Protobuf\Compiler\Protoc\ProcessBuilder;

class ProcessBuilderTest extends TestCase
{
    private $root;

    public function setUp()
    {
        $this->root = vfsStream::setup();
    }

    public function testCreateProcess()
    {
        $path    = $this->root->url();
        $protoc  = $path . '/protoc';
        $plugin  = $path . '/plugin';
        $out     = $this->getFileMock($path . '/php-src');
        $include = $this->getFileMock($path . '/include');
        $proto   = $this->getFileMock($path . '/file.proto');

        $builder = new ProcessBuilder($plugin, $protoc);
        $process = $builder->createProcess($out, [$proto], [$include], ['verbose' => 1]);
        $command = $process->getCommandLine();

        $builder->setIncludeDescriptors(true);

        $this->assertStringStartsWith("'vfs://root/protoc'", $command);
        $this->assertStringEndsWith("'vfs://root/file.proto'", $command);
        $this->assertContains('--plugin=protoc-gen-php=vfs://root/plugin', $command);
        $this->assertContains('--php_out=verbose=1:vfs://root/php-src', $command);
        $this->assertContains('--proto_path=vfs://root/include', $command);
    }

    public function testCreateProcessDefaultInclude()
    {
        $path    = $this->root->url();
        $protoc  = $path . '/protoc';
        $plugin  = $path . '/plugin';
        $out     = $this->getFileMock($path . '/php-src');
        $proto   = $this->getFileMock($path . '/proto/file.proto');

        $proto
            ->method('getBasename')
            ->willReturn('vfs://root/proto');

        $builder = new ProcessBuilder($plugin, $protoc);
        $process = $builder->createProcess($out, [$proto], [], ['verbose' => 1]);
        $command = $process->getCommandLine();

        $builder->setIncludeDescriptors(true);

        $this->assertStringStartsWith("'vfs://root/protoc'", $command);
        $this->assertStringEndsWith("'vfs://root/proto/file.proto'", $command);
        $this->assertContains('--plugin=protoc-gen-php=vfs://root/plugin', $command);
        $this->assertContains('--php_out=verbose=1:vfs://root/php-src', $command);
    }

    public function testCreateProtocVersionProcess()
    {
        $path    = $this->root->url();
        $protoc  = $path . '/protoc';
        $plugin  = $path . '/plugin';

        $builder = new ProcessBuilder($plugin, $protoc);
        $process = $this->invokeMethod($builder, 'createProtocVersionProcess');
        $command = $process->getCommandLine();

        $this->assertInstanceOf('Symfony\Component\Process\Process', $process);
        $this->assertEquals("vfs://root/protoc --version", $process->getCommandLine());
    }

    public function testAssertVersion()
    {
        $path    = $this->root->url();
        $protoc  = 'protoc';
        $plugin  = './bin/protobuf-plugin';

        $process = $this->getMockBuilder('Symfony\Component\Process\Process')
            ->disableOriginalConstructor()
            ->getMock();

        $builder = $this->getMockBuilder(ProcessBuilder::CLASS)
            ->setMethods(['createProtocVersionProcess'])
            ->setConstructorArgs([$plugin, $protoc])
            ->getMock();

        $builder->expects($this->once())
            ->method('createProtocVersionProcess')
            ->willReturn($process);

        $process->expects($this->once())
            ->method('getExitCode')
            ->willReturn(0);

        $process->expects($this->once())
            ->method('getOutput')
            ->willReturn('libprotoc 2.6.1' . PHP_EOL);

        $builder->assertVersion();
    }

    public function testFindDescriptorsPath()
    {
        $path    = $this->root->url();
        $protoc  = 'protoc';
        $plugin  = './bin/protobuf-plugin';
        $builder = new ProcessBuilder($plugin, $protoc);
        $paths   = [
            $path . '/not-found',
            $path . '/found'
        ];

        $this->root->addChild(vfsStream::newDirectory('found'));
        $this->setPropertyValue($builder, 'descriptorsPaths', $paths);
        $this->assertEquals($path . '/found', $this->invokeMethod($builder, 'findDescriptorsPath'));
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage Unable to find "protobuf-php/google-protobuf-proto".
     */

    public function testFindDescriptorsPathException()
    {
        $path    = $this->root->url();
        $protoc  = 'protoc';
        $plugin  = './bin/protobuf-plugin';
        $builder = new ProcessBuilder($plugin, $protoc);
        $paths   = [
            $path . '/not-found',
            $path . '/also-not-found'
        ];

        $this->setPropertyValue($builder, 'descriptorsPaths', $paths);
        $this->invokeMethod($builder, 'findDescriptorsPath');
    }

    /**
     * @expectedException UnexpectedValueException
     * @expectedExceptionMessage Unable to find the protoc command. Please make sure it's installed and available in the path.
     */
    public function testAssertVersionProtocNotFoundException()
    {
        $path    = $this->root->url();
        $protoc  = 'protoc';
        $plugin  = './bin/protobuf-plugin';

        $command = $this->getMockBuilder('Symfony\Component\Process\Process')
            ->disableOriginalConstructor()
            ->getMock();

        $builder = $this->getMockBuilder(ProcessBuilder::CLASS)
            ->setMethods(['createProtocVersionProcess'])
            ->setConstructorArgs([$plugin, $protoc])
            ->getMock();

        $builder->expects($this->once())
            ->method('createProtocVersionProcess')
            ->willReturn($command);

        $command->expects($this->once())
            ->method('getExitCode')
            ->willReturn(127);

        $builder->assertVersion();
    }

    /**
     * @expectedException UnexpectedValueException
     * @expectedExceptionMessage Unable to get protoc command version. Please make sure it's installed and available in the path.
     */
    public function testAssertVersionProtocVersionParseException()
    {
        $path    = $this->root->url();
        $protoc  = 'protoc';
        $plugin  = './bin/protobuf-plugin';

        $command = $this->getMockBuilder('Symfony\Component\Process\Process')
            ->disableOriginalConstructor()
            ->getMock();

        $builder = $this->getMockBuilder(ProcessBuilder::CLASS)
            ->setMethods(['createProtocVersionProcess'])
            ->setConstructorArgs([$plugin, $protoc])
            ->getMock();

        $builder->expects($this->once())
            ->method('createProtocVersionProcess')
            ->willReturn($command);

        $command->expects($this->once())
            ->method('getExitCode')
            ->willReturn(0);

        $command->expects($this->once())
            ->method('getOutput')
            ->willReturn('NOT A VALID VERSION' . PHP_EOL);

        $builder->assertVersion();
    }

    /**
     * @expectedException UnexpectedValueException
     * @expectedExceptionMessage The protoc command in your system is too old. Minimum version required is '2.3.0' but found 'libprotoc 2.0.0'
     */
    public function testAssertVersionProtocMinVersionException()
    {
        $path    = $this->root->url();
        $protoc  = 'protoc';
        $plugin  = './bin/protobuf-plugin';

        $command = $this->getMockBuilder('Symfony\Component\Process\Process')
            ->disableOriginalConstructor()
            ->getMock();

        $builder = $this->getMockBuilder(ProcessBuilder::CLASS)
            ->setMethods(['createProtocVersionProcess'])
            ->setConstructorArgs([$plugin, $protoc])
            ->getMock();

        $builder->expects($this->once())
            ->method('createProtocVersionProcess')
            ->willReturn($command);

        $command->expects($this->once())
            ->method('getExitCode')
            ->willReturn(0);

        $command->expects($this->once())
            ->method('getOutput')
            ->willReturn('libprotoc 2.0.0' . PHP_EOL);

        $builder->assertVersion();
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Proto file list cannot be empty
     */
    public function testCreateProcessInvalidArgumentEmptyProtoList()
    {
        $path    = $this->root->url();
        $protoc  = $path . '/protoc';
        $plugin  = $path . '/plugin';
        $out     = $path . '/php-src';

        $builder = new ProcessBuilder($plugin, $protoc);
        $process = $builder->createProcess($out, [], [], ['verbose' => 1]);
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage The directory "vfs://root/php-src" does not exist.
     */
    public function testCreateProcessInvalidArgumentOut()
    {
        $path    = $this->root->url();
        $protoc  = $path . '/protoc';
        $plugin  = $path . '/plugin';
        $out     = $path . '/php-src';
        $proto   = $path . '/file.proto';

        $builder = new ProcessBuilder($plugin, $protoc);
        $process = $builder->createProcess($out, [$proto], [], ['verbose' => 1]);
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage The directory "vfs://root/include" does not exist
     */
    public function testCreateProcessInvalidArgumentIncludePaths()
    {
        $path    = $this->root->url();
        $protoc  = $path . '/protoc';
        $plugin  = $path . '/plugin';
        $out     = $this->getFileMock($path . '/php-src');
        $proto   = $this->getFileMock($path . '/file.proto');
        $include = $this->getFileMock($path . '/include', false);

        $builder = new ProcessBuilder($plugin, $protoc);
        $process = $builder->createProcess($out, [$proto], [$include], ['verbose' => 1]);
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage The file "vfs://root/file.proto" does not exist.
     */
    public function testCreateProcessInvalidArgumentProtoFile()
    {
        $path    = $this->root->url();
        $protoc  = $path . '/protoc';
        $plugin  = $path . '/plugin';
        $out     = $this->getFileMock($path . '/php-src');
        $include = $this->getFileMock($path . '/include');
        $proto   = $this->getFileMock($path . '/file.proto', false);

        $builder = new ProcessBuilder($plugin, $protoc);
        $process = $builder->createProcess($out, [$proto], [$include], ['verbose' => 1]);
    }

    /**
     * @return \SplFileInfo
     */
    protected function getFileMock($pathname, $realpath = null)
    {
        if ($realpath === null) {
            $realpath = $pathname;
        }

        $mock = $this->getMockBuilder('SplFileInfo')
            ->setConstructorArgs([$realpath])
            ->setMethods(['getPathname', 'getRealPath'])
            ->getMock();

        $mock
            ->method('getPathname')
            ->willReturn($pathname);

        $mock
            ->method('getRealPath')
            ->willReturn($realpath);

         return $mock;
    }
}
