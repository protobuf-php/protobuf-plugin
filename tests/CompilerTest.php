<?php

namespace ProtobufCompilerTest;

use Protobuf\Stream;
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
}
