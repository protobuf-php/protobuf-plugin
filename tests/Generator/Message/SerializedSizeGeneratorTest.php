<?php

namespace ProtobufCompilerTest\Generator\Message;

use Protobuf\Compiler\Generator\Message\SerializedSizeGenerator;
use google\protobuf\FieldDescriptorProto;
use google\protobuf\DescriptorProto;
use ProtobufCompilerTest\TestCase;
use google\protobuf\FieldOptions;
use Protobuf\Field;

class SerializedSizeGeneratorTest extends TestCase
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
        $generator = new SerializedSizeGenerator($context);
        $descritor = $entity->getDescriptor();
        $field     = $descritor->getFieldList()[0];

        $actual   = $this->invokeMethod($generator, 'generateBody', [$entity]);
        $expected = <<<'CODE'
$calculator = $context->getSizeCalculator();
$size       = 0;

if ($this->lines !== null) {
    foreach ($this->lines as $val) {
        $size += 1;
        $size += $calculator->computeVarintSize($val);
    }
}

if ($this->extensions !== null) {
    $size += $this->extensions->serializedSize($context);
}

return $size;
CODE;

        $this->assertEquals($expected, implode(PHP_EOL, $actual));
    }
}
