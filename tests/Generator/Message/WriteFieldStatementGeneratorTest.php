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

    public function testGenerateWriteInt32Statement()
    {
        $context = $this->createMessagesContext([
            1  => ['count', Field::TYPE_INT32, Field::LABEL_REQUIRED]
        ]);

        $generator = new WriteFieldStatementGenerator($context);
        $entity    = $context->getEntity($this->messageClass);
        $descritor = $entity->getDescriptor();
        $field     = $descritor->getFieldList()[0];

        $actual   = $generator->generateFieldWriteStatement($entity, $field);
        $expected = <<<'CODE'
$writer->writeVarint($stream, 8);
$writer->writeVarint($stream, $this->count);
CODE;

        $this->assertEquals($expected, implode(PHP_EOL, $actual));
    }

    public function testGenerateWriteStringRepeatedStatement()
    {
        $context = $this->createMessagesContext([
            1  => ['lines', Field::TYPE_STRING, Field::LABEL_REPEATED]
        ]);

        $generator = new WriteFieldStatementGenerator($context);
        $entity    = $context->getEntity($this->messageClass);
        $descritor = $entity->getDescriptor();
        $field     = $descritor->getFieldList()[0];

        $actual   = $generator->generateFieldWriteStatement($entity, $field);
        $expected = <<<'CODE'
foreach ($this->lines as $val) {
    $writer->writeVarint($stream, 10);
    $writer->writeString($stream, $val);
}
CODE;

        $this->assertEquals($expected, implode(PHP_EOL, $actual));
    }

    public function testGenerateWritePackedInt32Statement()
    {
        $options = new FieldOptions();
        $context = $this->createMessagesContext([
            1  => ['tags', Field::TYPE_INT32, Field::LABEL_REPEATED]
        ]);

        $generator = new WriteFieldStatementGenerator($context);
        $entity    = $context->getEntity($this->messageClass);
        $descritor = $entity->getDescriptor();
        $field     = $descritor->getFieldList()[0];

        $options->setPacked(true);
        $field->setOptions($options);

        $actual   = $generator->generateFieldWriteStatement($entity, $field);
        $expected = <<<'CODE'
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
CODE;

        $this->assertEquals($expected, implode(PHP_EOL, $actual));
    }

    public function testGenerateWriteMessageStatement()
    {
        $context = $this->createMessagesContext([
            1  => ['phone', Field::TYPE_MESSAGE, Field::LABEL_REQUIRED, 'ProtobufCompiler.Proto.PhoneNumber']
        ]);

        $generator = new WriteFieldStatementGenerator($context);
        $entity    = $context->getEntity($this->messageClass);
        $descritor = $entity->getDescriptor();
        $field     = $descritor->getFieldList()[0];

        $actual   = $generator->generateFieldWriteStatement($entity, $field);
        $expected = <<<'CODE'
$writer->writeVarint($stream, 10);
$writer->writeVarint($stream, $this->phone->serializedSize($sizeContext));
$this->phone->writeTo($context);
CODE;

        $this->assertEquals($expected, implode(PHP_EOL, $actual));
    }

    public function testGenerateWriteMessageRepeatedStatement()
    {
        $context = $this->createMessagesContext([
            1  => ['files', Field::TYPE_MESSAGE, Field::LABEL_REPEATED, 'ProtobufCompiler.Proto.File']
        ]);

        $generator = new WriteFieldStatementGenerator($context);
        $entity    = $context->getEntity($this->messageClass);
        $descritor = $entity->getDescriptor();
        $field     = $descritor->getFieldList()[0];

        $actual   = $generator->generateFieldWriteStatement($entity, $field);
        $expected = <<<'CODE'
foreach ($this->files as $val) {
    $writer->writeVarint($stream, 10);
    $writer->writeVarint($stream, $val->serializedSize($sizeContext));
    $val->writeTo($context);
}
CODE;

        $this->assertEquals($expected, implode(PHP_EOL, $actual));
    }

    public function testGenerateWriteInt32FromVariableStatement()
    {
        $context = $this->createMessagesContext([
            1  => ['count', Field::TYPE_INT32, Field::LABEL_REQUIRED]
        ]);

        $generator = new WriteFieldStatementGenerator($context);
        $entity    = $context->getEntity($this->messageClass);
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
