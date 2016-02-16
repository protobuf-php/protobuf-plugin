<?php

namespace ProtobufCompilerTest;

use Protobuf\Stream;
use Protobuf\Compiler\Entity;
use Protobuf\Compiler\Compiler;
use google\protobuf\compiler\CodeGeneratorResponse;

class CompilerTest extends TestCase
{
    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->logger = $this->getMock('Psr\Log\LoggerInterface');
    }

    public function testGenerateSimpleMessage()
    {
        $binaryRequest   = $this->getFixtureFileContent('compiler/generator-request-simple.bin');
        $expectedContent = $this->getFixtureFileContent('Simple.tpl');

        $compiler       = new Compiler($this->logger);
        $binaryResponse = $compiler->compile(Stream::wrap($binaryRequest));
        $response       = CodeGeneratorResponse::fromStream($binaryResponse);

        $this->assertInstanceOf('google\protobuf\compiler\CodeGeneratorResponse', $response);
        $this->assertInstanceOf('Protobuf\Collection', $response->getFileList());
        $this->assertCount(1, $response->getFileList());

        $file = $response->getFileList()[0];

        $this->assertInstanceOf('google\protobuf\compiler\CodeGeneratorResponse\File', $file);
        $this->assertEquals('ProtobufCompilerTest/Protos/Simple.php', $file->getName());
        $this->assertEquals($expectedContent, $file->getContent());
    }

    public function testLoadEntityClassIgnoreExistinClass()
    {
        $compiler = new Compiler($this->logger);
        $entity   = $this->getMock(Entity::CLASS, [], [], '', false);

        $entity->expects($this->once())
            ->method('getType')
            ->willReturn(Entity::TYPE_MESSAGE);

        $entity->expects($this->once())
            ->method('getContent')
            ->willReturn('<?php /** code */');

        $entity->expects($this->once())
            ->method('getNamespacedName')
            ->willReturn('\\Iterator');

        $this->assertFalse($this->invokeMethod($compiler, 'loadEntityClass', [$entity]));
    }

    public function testLoadEntityExtensionClass()
    {
        $unique   = uniqid();
        $compiler = new Compiler($this->logger);
        $entity   = $this->getMock(Entity::CLASS, [], [], '', false);
        $class    = "ProtobufCompilerTest\CompilerTest$unique\Extension";
        $code     = <<<CODE
<?php
namespace ProtobufCompilerTest\CompilerTest$unique;

class Extension implements \Protobuf\Extension
{
    public static function registerAllExtensions(\Protobuf\Extension\ExtensionRegistry \$registry) {}
}
CODE;

        $entity->expects($this->once())
            ->method('getType')
            ->willReturn(Entity::TYPE_EXTENSION);

        $entity->expects($this->once())
            ->method('getContent')
            ->willReturn($code);

        $entity->expects($this->once())
            ->method('getNamespacedName')
            ->willReturn($class);

        $this->assertFalse(class_exists($class));
        $this->assertTrue($this->invokeMethod($compiler, 'loadEntityClass', [$entity]));
        $this->assertTrue(class_exists($class));
    }
}
