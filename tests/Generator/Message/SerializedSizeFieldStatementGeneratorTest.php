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
    protected $messageClass = 'ProtobufCompilerTest.Protos.Simple';

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

    public function testGenerateComputeInt32Statement()
    {
        $context = $this->createMessagesContext([
            1  => ['count', Field::TYPE_INT32, Field::LABEL_REQUIRED]
        ]);

        $generator = new SerializedSizeFieldStatementGenerator($context);
        $entity    = $context->getEntity($this->messageClass);
        $descritor = $entity->getDescriptor();
        $field     = $descritor->getFieldList()[0];

        $actual   = $generator->generateFieldSizeStatement($entity, $field);
        $expected = <<<'CODE'
$size += 1;
$size += $calculator->computeVarintSize($this->count);
CODE;

        $this->assertEquals($expected, implode(PHP_EOL, $actual));
    }

    public function testGenerateComputeStringRepeatedStatement()
    {
        $context = $this->createMessagesContext([
            1  => ['lines', Field::TYPE_STRING, Field::LABEL_REPEATED]
        ]);

        $generator = new SerializedSizeFieldStatementGenerator($context);
        $entity    = $context->getEntity($this->messageClass);
        $descritor = $entity->getDescriptor();
        $field     = $descritor->getFieldList()[0];

        $actual   = $generator->generateFieldSizeStatement($entity, $field);
        $expected = <<<'CODE'
foreach ($this->lines as $val) {
    $size += 1;
    $size += $calculator->computeStringSize($val);
}
CODE;

        $this->assertEquals($expected, implode(PHP_EOL, $actual));
    }

    public function testGenerateComputePackedInt32Statement()
    {
        $options = new FieldOptions();
        $context = $this->createMessagesContext([
            1  => ['tags', Field::TYPE_INT32, Field::LABEL_REPEATED]
        ]);

        $generator = new SerializedSizeFieldStatementGenerator($context);
        $entity    = $context->getEntity($this->messageClass);
        $descritor = $entity->getDescriptor();
        $field     = $descritor->getFieldList()[0];

        $options->setPacked(true);
        $field->setOptions($options);

        $actual   = $generator->generateFieldSizeStatement($entity, $field);
        $expected = <<<'CODE'
$innerSize = 0;

foreach ($this->tags as $val) {
    $innerSize += $calculator->computeVarintSize($val);
}

$size += 1;
$size += $innerSize;
$size += $calculator->computeVarintSize($innerSize);
CODE;

        $this->assertEquals($expected, implode(PHP_EOL, $actual));
    }

    public function testGenerateComputeMessageStatement()
    {
        $context = $this->createMessagesContext([
            1  => ['phone', Field::TYPE_MESSAGE, Field::LABEL_REQUIRED, 'ProtobufCompiler.Proto.PhoneNumber']
        ]);

        $generator = new SerializedSizeFieldStatementGenerator($context);
        $entity    = $context->getEntity($this->messageClass);
        $descritor = $entity->getDescriptor();
        $field     = $descritor->getFieldList()[0];

        $actual   = $generator->generateFieldSizeStatement($entity, $field);
        $expected = <<<'CODE'
$innerSize = $this->phone->serializedSize($context);

$size += 1;
$size += $innerSize;
$size += $calculator->computeVarintSize($innerSize);
CODE;

        $this->assertEquals($expected, implode(PHP_EOL, $actual));
    }

    public function testGenerateComputeMessageRepeatedStatement()
    {
        $context = $this->createMessagesContext([
            1  => ['files', Field::TYPE_MESSAGE, Field::LABEL_REPEATED, 'ProtobufCompiler.Proto.File']
        ]);

        $generator = new SerializedSizeFieldStatementGenerator($context);
        $entity    = $context->getEntity($this->messageClass);
        $descritor = $entity->getDescriptor();
        $field     = $descritor->getFieldList()[0];

        $actual   = $generator->generateFieldSizeStatement($entity, $field);
        $expected = <<<'CODE'
foreach ($this->files as $val) {
    $innerSize = $val->serializedSize($context);

    $size += 1;
    $size += $innerSize;
    $size += $calculator->computeVarintSize($innerSize);
}
CODE;

        $this->assertEquals($expected, implode(PHP_EOL, $actual));
    }
}
