<?php

namespace ProtobufCompilerTest\Generator\Message;

use Protobuf\Compiler\Generator\Message\WriteToGenerator;
use google\protobuf\FieldDescriptorProto;
use google\protobuf\DescriptorProto;
use ProtobufCompilerTest\TestCase;
use google\protobuf\FieldOptions;
use Protobuf\Field;

class WriteToGeneratorTest extends TestCase
{
    public function testGenerateBody()
    {
        $context = $this->createContext([
            [
                'name'    => 'simple.proto',
                'package' => 'ProtobufCompilerTest.Protos',
                'values'  => [
                    'messages' => [
                        [
                            'name'   => 'Simple',
                            'fields' => [
                                1  => ['lines', Field::TYPE_INT32, Field::LABEL_REPEATED]
                            ]
                        ]
                    ]
                ]
            ]
        ]);

        $entity    = $context->getEntity('ProtobufCompilerTest.Protos.Simple');
        $generator = new WriteToGenerator($context);
        $descritor = $entity->getDescriptor();
        $field     = $descritor->getFieldList()[0];

        $actual   = $this->invokeMethod($generator, 'generateBody', [$entity]);
        $expected = <<<'CODE'
$stream      = $context->getStream();
$writer      = $context->getWriter();
$sizeContext = $context->getComputeSizeContext();

if ($this->lines !== null) {
    foreach ($this->lines as $val) {
        $writer->writeVarint($stream, 8);
        $writer->writeVarint($stream, $val);
    }
}

if ($this->extensions !== null) {
    $this->extensions->writeTo($context);
}

return $stream;
CODE;

        $this->assertEquals($expected, implode(PHP_EOL, $actual));
    }
}
