<?php

namespace ProtobufCompilerTest\Generator\Message;

use Protobuf\Compiler\Generator\Message\WriteFieldStatementGenerator;
use google\protobuf\FieldDescriptorProto;
use google\protobuf\DescriptorProto;
use ProtobufCompilerTest\TestCase;
use google\protobuf\FieldOptions;
use Protobuf\Field;

class WriteFieldStatementGeneratorTest extends TestCase
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
$writer->writeVarint($stream, 8);
$writer->writeVarint($stream, $this->count);
CODE
            ],

            // repeated string
            [
                [
                    1  => ['lines', Field::TYPE_STRING, Field::LABEL_REPEATED]
                ],
                <<<'CODE'
foreach ($this->lines as $val) {
    $writer->writeVarint($stream, 10);
    $writer->writeString($stream, $val);
}
CODE
            ],

            // required int32 packed
            [
                [
                    1  => ['tags', Field::TYPE_INT32, Field::LABEL_REPEATED, null, [ 'options' => ['packed' => true] ]]
                ],
                <<<'CODE'
$innerSize   = 0;
$calculator  = $sizeContext->getSizeCalculator();

foreach ($this->tags as $val) {
    $innerSize += $calculator->computeVarintSize($val);
}

$writer->writeVarint($stream, 10);
$writer->writeVarint($stream, $innerSize);

foreach ($this->tags as $val) {
    $writer->writeVarint($stream, $val);
}
CODE
            ],

            // required message
            [
                [
                    1  => ['phone', Field::TYPE_MESSAGE, Field::LABEL_REQUIRED, 'ProtobufCompiler.Proto.PhoneNumber']
                ],
                <<<'CODE'
$writer->writeVarint($stream, 10);
$writer->writeVarint($stream, $this->phone->serializedSize($sizeContext));
$this->phone->writeTo($context);
CODE
            ],

            // repeated message
            [
                [
                    1  => ['files', Field::TYPE_MESSAGE, Field::LABEL_REPEATED, 'ProtobufCompiler.Proto.File']
                ],
                <<<'CODE'
foreach ($this->files as $val) {
    $writer->writeVarint($stream, 10);
    $writer->writeVarint($stream, $val->serializedSize($sizeContext));
    $val->writeTo($context);
}
CODE
            ],
        ];
    }

    /**
     * @dataProvider descriptorProvider
     */
    public function testGenerateFieldWriteStatement($fields, $expected, $fieldIndex = 0)
    {
        $context   = $this->createMessagesContext($fields);
        $entity    = $context->getEntity('ProtobufCompilerTest.Protos.Simple');
        $generator = new WriteFieldStatementGenerator($context);
        $descritor = $entity->getDescriptor();
        $field     = $descritor->getFieldList()[$fieldIndex];
        $actual    = $generator->generateFieldWriteStatement($entity, $field);

        $this->assertEquals($expected, implode(PHP_EOL, $actual));
    }

    public function testGenerateWriteInt32FromVariableStatement()
    {
        $fields = [
            1  => ['count', Field::TYPE_INT32, Field::LABEL_REQUIRED]
        ];

        $context   = $this->createMessagesContext($fields);
        $entity    = $context->getEntity('ProtobufCompilerTest.Protos.Simple');
        $generator = new WriteFieldStatementGenerator($context);
        $descritor = $entity->getDescriptor();
        $field     = $descritor->getFieldList()[0];

        $generator->setTargetVar('$count');

        $actual   = $generator->generateFieldWriteStatement($entity, $field);
        $expected = <<<'CODE'
$writer->writeVarint($stream, 8);
$writer->writeVarint($stream, $count);
CODE;

        $this->assertEquals($expected, implode(PHP_EOL, $actual));
    }

    public function testGenerateWriteEnumRepeatedStatement()
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
        $generator = new WriteFieldStatementGenerator($context);
        $descritor = $entity->getDescriptor();
        $field     = $descritor->getFieldList()[0];

        $actual   = $generator->generateFieldWriteStatement($entity, $field);
        $expected = <<<'CODE'
foreach ($this->status as $val) {
    $writer->writeVarint($stream, 8);
    $writer->writeVarint($stream, $val->value());
}
CODE;

        $this->assertEquals($expected, implode(PHP_EOL, $actual));
    }

    public function testGenerateWritePackedEnumRepeatedStatement()
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
        $generator = new WriteFieldStatementGenerator($context);
        $descritor = $entity->getDescriptor();
        $field     = $descritor->getFieldList()[0];

        $options->setPacked(true);
        $field->setOptions($options);

        $actual   = $generator->generateFieldWriteStatement($entity, $field);
        $expected = <<<'CODE'
$innerSize   = 0;
$calculator  = $sizeContext->getSizeCalculator();

foreach ($this->status as $val) {
    $innerSize += $calculator->computeVarintSize($val->value());
}

$writer->writeVarint($stream, 10);
$writer->writeVarint($stream, $innerSize);

foreach ($this->status as $val) {
    $writer->writeVarint($stream, $val->value());
}
CODE;

        $this->assertEquals($expected, implode(PHP_EOL, $actual));
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Unknown field type : -123
     */
    public function testGenerateReadScalarStatementException()
    {
        $context   = $this->createMessagesContext([]);
        $generator = new WriteFieldStatementGenerator($context);

        $this->invokeMethod($generator, 'generateWriteScalarStatement', [-123, 123]);
    }
}
