<?php

namespace ProtobufCompilerTest\Generator\Message;

use Protobuf\Compiler\Generator\Message\ReadFieldStatementGenerator;
use google\protobuf\FieldDescriptorProto;
use google\protobuf\DescriptorProto;
use ProtobufCompilerTest\TestCase;
use google\protobuf\FieldOptions;
use Protobuf\Field;

class ReadFieldStatementGeneratorTest extends TestCase
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

    public function testGenerateReadInt32Statement()
    {
        $context = $this->createMessagesContext([
            1  => ['count', Field::TYPE_INT32, Field::LABEL_REQUIRED]
        ]);

        $generator = new ReadFieldStatementGenerator($context);
        $entity    = $context->getEntity($this->messageClass);
        $descritor = $entity->getDescriptor();
        $field     = $descritor->getFieldList()[0];

        $actual   = $generator->generateFieldReadStatement($entity, $field);
        $expected = <<<'CODE'
\Protobuf\WireFormat::assertWireType($wire, 5);

$this->count = $reader->readVarint($stream);

continue;
CODE;

        $this->assertEquals($expected, implode(PHP_EOL, $actual));
    }

    public function testGenerateReadStringRepeatedStatement()
    {
        $context = $this->createMessagesContext([
            1  => ['lines', Field::TYPE_STRING, Field::LABEL_REPEATED]
        ]);

        $generator = new ReadFieldStatementGenerator($context);
        $entity    = $context->getEntity($this->messageClass);
        $descritor = $entity->getDescriptor();
        $field     = $descritor->getFieldList()[0];

        $actual   = $this->invokeMethod($generator, 'generateFieldReadStatement', [$entity, $field]);
        $expected = <<<'CODE'
\Protobuf\WireFormat::assertWireType($wire, 9);

if ($this->lines === null) {
    $this->lines = new \Protobuf\ScalarCollection();
}

$this->lines->add($reader->readString($stream));

continue;
CODE;

        $this->assertEquals($expected, implode(PHP_EOL, $actual));
    }

    public function testGenerateReadBytesRepeatedStatement()
    {
        $context = $this->createMessagesContext([
            1  => ['lines', Field::TYPE_BYTES, Field::LABEL_REPEATED]
        ]);

        $generator = new ReadFieldStatementGenerator($context);
        $entity    = $context->getEntity($this->messageClass);
        $descritor = $entity->getDescriptor();
        $field     = $descritor->getFieldList()[0];

        $actual   = $this->invokeMethod($generator, 'generateFieldReadStatement', [$entity, $field]);
        $expected = <<<'CODE'
\Protobuf\WireFormat::assertWireType($wire, 12);

if ($this->lines === null) {
    $this->lines = new \Protobuf\StreamCollection();
}

$this->lines->add($reader->readByteStream($stream));

continue;
CODE;

        $this->assertEquals($expected, implode(PHP_EOL, $actual));
    }

    public function testGenerateReadEnumRepeatedStatement()
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

        $generator = new ReadFieldStatementGenerator($context);
        $entity    = $context->getEntity($this->messageClass);
        $descritor = $entity->getDescriptor();
        $field     = $descritor->getFieldList()[0];

        $actual   = $this->invokeMethod($generator, 'generateFieldReadStatement', [$entity, $field]);
        $expected = <<<'CODE'
$innerSize  = $reader->readVarint($stream);
$innerLimit = $stream->tell() + $innerSize;

if ($this->status === null) {
    $this->status = new \Protobuf\EnumCollection();
}

while ($stream->tell() < $innerLimit) {
    $this->status->add(\ProtobufCompilerTest\Protos\Type::valueOf($reader->readVarint($stream)));
}

continue;
CODE;

        $this->assertEquals($expected, implode(PHP_EOL, $actual));
    }

    public function testGenerateReadPackedInt32Statement()
    {
        $options = new FieldOptions();
        $context = $this->createMessagesContext([
            1  => ['tags', Field::TYPE_INT32, Field::LABEL_REPEATED]
        ]);

        $generator = new ReadFieldStatementGenerator($context);
        $entity    = $context->getEntity($this->messageClass);
        $descritor = $entity->getDescriptor();
        $field     = $descritor->getFieldList()[0];

        $options->setPacked(true);
        $field->setOptions($options);

        $actual   = $generator->generateFieldReadStatement($entity, $field);
        $expected = <<<'CODE'
$innerSize  = $reader->readVarint($stream);
$innerLimit = $stream->tell() + $innerSize;

if ($this->tags === null) {
    $this->tags = new \Protobuf\ScalarCollection();
}

while ($stream->tell() < $innerLimit) {
    $this->tags->add($reader->readVarint($stream));
}

continue;
CODE;

        $this->assertEquals($expected, implode(PHP_EOL, $actual));
    }

    public function testGenerateReadPackedEnumStatement()
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

        $generator = new ReadFieldStatementGenerator($context);
        $entity    = $context->getEntity($this->messageClass);
        $descritor = $entity->getDescriptor();
        $field     = $descritor->getFieldList()[0];

        $options->setPacked(true);
        $field->setOptions($options);

        $actual   = $generator->generateFieldReadStatement($entity, $field);
        $expected = <<<'CODE'
$innerSize  = $reader->readVarint($stream);
$innerLimit = $stream->tell() + $innerSize;

if ($this->status === null) {
    $this->status = new \Protobuf\EnumCollection();
}

while ($stream->tell() < $innerLimit) {
    $this->status->add(\ProtobufCompilerTest\Protos\Type::valueOf($reader->readVarint($stream)));
}

continue;
CODE;

        $this->assertEquals($expected, implode(PHP_EOL, $actual));
    }

    public function testGenerateReadMessageStatement()
    {
        return $this->createContext([
            [
                'name'    => 'simple.proto',
                'package' => 'ProtobufCompilerTest.Protos',
                'values'  => [
                    'messages' => [
                        [
                            'name'   => 'Simple',
                            'fields' => [
                                1  => ['phone', Field::TYPE_MESSAGE, Field::LABEL_REQUIRED, 'ProtobufCompiler.Proto.PhoneNumber']
                            ]
                        ],
                        [
                            'name'   => 'PhoneNumber',
                            'fields' => []
                        ]
                    ]
                ]
            ]
        ]);

        $generator = new ReadFieldStatementGenerator($context);
        $entity    = $context->getEntity($this->messageClass);
        $descritor = $entity->getDescriptor();
        $field     = $descritor->getFieldList()[0];

        $actual   = $generator->generateFieldReadStatement($entity, $field);
        $expected = <<<'CODE'
\Protobuf\WireFormat::assertWireType($wire, 11);

$innerSize    = $reader->readVarint($stream);
$innerMessage = new \ProtobufCompiler\Proto\PhoneNumber();

$this->phone = $innerMessage;

$context->setLength($innerSize);
$innerMessage->readFrom($context);
$context->setLength($length);

continue;
CODE;

        $this->assertEquals($expected, implode(PHP_EOL, $actual));
    }

    public function testGenerateReadMessageRepeatedStatement()
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
                                1  => ['files', Field::TYPE_MESSAGE, Field::LABEL_REPEATED, 'ProtobufCompilerTest.Protos.File']
                            ]
                        ],
                        [
                            'name'   => 'File',
                            'fields' => []
                        ]
                    ]
                ]
            ]
        ]);

        $generator = new ReadFieldStatementGenerator($context);
        $entity    = $context->getEntity($this->messageClass);
        $descritor = $entity->getDescriptor();
        $field     = $descritor->getFieldList()[0];

        $actual   = $generator->generateFieldReadStatement($entity, $field);
        $expected = <<<'CODE'
\Protobuf\WireFormat::assertWireType($wire, 11);

$innerSize    = $reader->readVarint($stream);
$innerMessage = new \ProtobufCompilerTest\Protos\File();

if ($this->files === null) {
    $this->files = new \Protobuf\MessageCollection();
}

$this->files->add($innerMessage);

$context->setLength($innerSize);
$innerMessage->readFrom($context);
$context->setLength($length);

continue;
CODE;

        $this->assertEquals($expected, implode(PHP_EOL, $actual));
    }

    public function testGenerateReadInt32IntoVariableStatement()
    {
        $context = $this->createMessagesContext([
            1  => ['count', Field::TYPE_INT32, Field::LABEL_REQUIRED]
        ]);

        $generator = new ReadFieldStatementGenerator($context);
        $entity    = $context->getEntity($this->messageClass);
        $descritor = $entity->getDescriptor();
        $field     = $descritor->getFieldList()[0];

        $generator->setTargetVar('$count');
        $generator->setBreakMode(ReadFieldStatementGenerator::BREAK_MODE_RETURN);

        $actual   = $generator->generateFieldReadStatement($entity, $field);
        $expected = <<<'CODE'
\Protobuf\WireFormat::assertWireType($wire, 5);

$count = $reader->readVarint($stream);

return $count;
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
        $generator = new ReadFieldStatementGenerator($context);

        $this->invokeMethod($generator, 'generateReadScalarStatement', [-123]);
    }

    public function testIsDefaultPacked()
    {
        $context   = $this->createMessagesContext([]);
        $generator = new ReadFieldStatementGenerator($context);

        $this->assertTrue(
            $this->invokeMethod($generator, 'isDefaultPacked',
                [
                    FieldDescriptorProto\Label::LABEL_REPEATED(),
                    FieldDescriptorProto\Type::TYPE_UINT32()
                ]
            )
        );
        $this->assertFalse(
            $this->invokeMethod($generator, 'isDefaultPacked',
                [
                    FieldDescriptorProto\Label::LABEL_OPTIONAL(),
                    FieldDescriptorProto\Type::TYPE_STRING()
                ]
            )
        );
        $this->assertFalse(
            $this->invokeMethod($generator, 'isDefaultPacked',
                [
                    FieldDescriptorProto\Label::LABEL_REPEATED(),
                    FieldDescriptorProto\Type::TYPE_STRING()
                ]
            )
        );
    }
}
