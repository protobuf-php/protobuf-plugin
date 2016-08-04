<?php

namespace ProtobufCompilerTest\Generator\Message;

use Protobuf\Compiler\Generator\Message\SerializedSizeFieldStatementGenerator;
use google\protobuf\FieldDescriptorProto;
use google\protobuf\DescriptorProto;
use ProtobufCompilerTest\TestCase;
use google\protobuf\FieldOptions;
use Protobuf\Field;

class SerializedSizeFieldStatementGeneratorTest extends TestCase
{
    public function createMessagesContext(array $fields)
    {
        return $this->createContext([
            [
                'name'    => 'simple.proto',
                'package' => 'ProtobufCompilerTest.Protos',
                'values'  => [
                    'messages' => [
                        [
                            'name'   => 'Simple',
                            'fields' => $fields
                        ]
                    ]
                ]
            ]
        ]);
    }

    public function descriptorProvider()
    {
        return [

            // required int32
            [
                [
                    1  => ['count', Field::TYPE_INT32, Field::LABEL_REQUIRED]
                ],
                <<<'CODE'
$size += 1;
$size += $calculator->computeVarintSize($this->count);
CODE
            ],

            // repeated string
            [
                [
                    1  => ['lines', Field::TYPE_STRING, Field::LABEL_REPEATED]
                ],
                <<<'CODE'
foreach ($this->lines as $val) {
    $size += 1;
    $size += $calculator->computeStringSize($val);
}
CODE
            ],

            // required int32 packed
            [
                [
                    1  => ['tags', Field::TYPE_INT32, Field::LABEL_REPEATED, null, [ 'options' => ['packed' => true] ]]
                ],
                <<<'CODE'
$innerSize = 0;

foreach ($this->tags as $val) {
    $innerSize += $calculator->computeVarintSize($val);
}

$size += 1;
$size += $innerSize;
$size += $calculator->computeVarintSize($innerSize);
CODE
            ],

            // required message
            [
                [
                    1  => ['phone', Field::TYPE_MESSAGE, Field::LABEL_REQUIRED, 'ProtobufCompiler.Proto.PhoneNumber']
                ],
                <<<'CODE'
$innerSize = $this->phone->serializedSize($context);

$size += 1;
$size += $innerSize;
$size += $calculator->computeVarintSize($innerSize);
CODE
            ],

            // repeated message
            [
                [
                    1  => ['files', Field::TYPE_MESSAGE, Field::LABEL_REPEATED, 'ProtobufCompiler.Proto.File']
                ],
                <<<'CODE'
foreach ($this->files as $val) {
    $innerSize = $val->serializedSize($context);

    $size += 1;
    $size += $innerSize;
    $size += $calculator->computeVarintSize($innerSize);
}
CODE
            ],
        ];
    }

    /**
     * @dataProvider descriptorProvider
     */
    public function testGenerateFieldSizeStatement($fields, $expected, $fieldIndex = 0)
    {
        $context   = $this->createMessagesContext($fields);
        $entity    = $context->getEntity('ProtobufCompilerTest.Protos.Simple');
        $generator = new SerializedSizeFieldStatementGenerator($context);
        $descritor = $entity->getDescriptor();
        $field     = $descritor->getFieldList()[$fieldIndex];
        $actual    = $generator->generateFieldSizeStatement($entity, $field);

        $this->assertEquals($expected, implode(PHP_EOL, $actual));
    }

    public function testGenerateEnumRepeatedFieldSizeStatement()
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
                                1  => ['status', Field::TYPE_ENUM, Field::LABEL_REPEATED, 'ProtobufCompilerTest.Protos.Type']
                            ]
                        ],
                        [
                            'name'   => 'Type',
                            'fields' => []
                        ]
                    ]
                ]
            ]
        ]);

        $entity    = $context->getEntity('ProtobufCompilerTest.Protos.Simple');
        $generator = new SerializedSizeFieldStatementGenerator($context);
        $descritor = $entity->getDescriptor();
        $field     = $descritor->getFieldList()[0];

        $actual   = $generator->generateFieldSizeStatement($entity, $field);
        $expected = <<<'CODE'
foreach ($this->status as $val) {
    $size += 1;
    $size += $calculator->computeVarintSize($val->value());
}
CODE;

        $this->assertEquals($expected, implode(PHP_EOL, $actual));
    }

    public function testGenerateEnumPackadRepeatedFieldSizeStatement()
    {
        $options = new FieldOptions();
        $context = $this->createContext([
            [
                'name'    => 'simple.proto',
                'package' => 'ProtobufCompilerTest.Protos',
                'values'  => [
                    'messages' => [
                        [
                            'name'   => 'Simple',
                            'fields' => [
                                1  => ['status', Field::TYPE_ENUM, Field::LABEL_REPEATED, 'ProtobufCompilerTest.Protos.Type']
                            ]
                        ],
                        [
                            'name'   => 'Type',
                            'fields' => []
                        ]
                    ]
                ]
            ]
        ]);

        $entity    = $context->getEntity('ProtobufCompilerTest.Protos.Simple');
        $generator = new SerializedSizeFieldStatementGenerator($context);
        $descritor = $entity->getDescriptor();
        $field     = $descritor->getFieldList()[0];

        $options->setPacked(true);
        $field->setOptions($options);

        $actual   = $generator->generateFieldSizeStatement($entity, $field);
        $expected = <<<'CODE'
$innerSize = 0;

foreach ($this->status as $val) {
    $innerSize += $calculator->computeVarintSize($val->value());
}

$size += 1;
$size += $innerSize;
$size += $calculator->computeVarintSize($innerSize);
CODE;

        $this->assertEquals($expected, implode(PHP_EOL, $actual));
    }
}
