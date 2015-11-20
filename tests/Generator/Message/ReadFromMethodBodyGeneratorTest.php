<?php

namespace ProtobufCompilerTest\Generator\Message;

use Protobuf\Compiler\Generator\Message\ReadFromMethodBodyGenerator;
use google\protobuf\FieldDescriptorProto;
use google\protobuf\DescriptorProto;
use ProtobufCompilerTest\TestCase;
use google\protobuf\FieldOptions;

class ReadFromMethodBodyGeneratorTest extends TestCase
{
    /**
     * @var \Protobuf\Compiler\Options
     */
    protected $options;

    /**
     * @var string
     */
    protected $package;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->package = 'ProtobufCompiler.Proto';
        $this->options = $this->getMock('Protobuf\Compiler\Options');
    }

    public function testGenerateReadInt32Statement()
    {
        $proto     = new DescriptorProto();
        $field     = new FieldDescriptorProto();
        $generator = new ReadFromMethodBodyGenerator($proto, $this->options, $this->package);

        $field->setNumber(1);
        $field->setName('count');
        $field->setType(FieldDescriptorProto\Type::TYPE_INT32());
        $field->setLabel(FieldDescriptorProto\Label::LABEL_REQUIRED());

        $actual   = $this->invokeMethod($generator, 'generateFieldReadStatement', [$field]);
        $expected = <<<'CODE'
\Protobuf\WireFormat::assertWireType($wire, 5);

$this->count = $reader->readVarint($stream);

continue;
CODE;

        $this->assertEquals($expected, implode(PHP_EOL, $actual));
    }

    public function testGenerateReadStringRepeatedStatement()
    {
        $proto     = new DescriptorProto();
        $field     = new FieldDescriptorProto();
        $generator = new ReadFromMethodBodyGenerator($proto, $this->options, $this->package);

        $field->setNumber(1);
        $field->setName('lines');
        $field->setType(FieldDescriptorProto\Type::TYPE_INT32());
        $field->setLabel(FieldDescriptorProto\Label::LABEL_REPEATED());

        $actual   = $this->invokeMethod($generator, 'generateFieldReadStatement', [$field]);
        $expected = <<<'CODE'
\Protobuf\WireFormat::assertWireType($wire, 5);

if ($this->lines === null) {
    $this->lines = new \Protobuf\ScalarCollection();
}

$this->lines->add($reader->readVarint($stream));

continue;
CODE;

        $this->assertEquals($expected, implode(PHP_EOL, $actual));
    }

    public function testGenerateReadPackedInt32Statement()
    {
        $options   = new FieldOptions();
        $proto     = new DescriptorProto();
        $field     = new FieldDescriptorProto();
        $generator = new ReadFromMethodBodyGenerator($proto, $this->options, $this->package);

        $options->setPacked(true);

        $field->setNumber(1);
        $field->setName('tags');
        $field->setOptions($options);
        $field->setType(FieldDescriptorProto\Type::TYPE_INT32());
        $field->setLabel(FieldDescriptorProto\Label::LABEL_REPEATED());

        $actual   = $this->invokeMethod($generator, 'generateFieldReadStatement', [$field]);
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

    public function testGenerateReadMessageStatement()
    {
        $proto     = new DescriptorProto();
        $field     = new FieldDescriptorProto();
        $generator = new ReadFromMethodBodyGenerator($proto, $this->options, $this->package);

        $field->setNumber(1);
        $field->setName('phone');
        $field->setType(FieldDescriptorProto\Type::TYPE_MESSAGE());
        $field->setLabel(FieldDescriptorProto\Label::LABEL_REQUIRED());
        $field->setTypeName('ProtobufCompiler.Proto.PhoneNumber');

        $actual   = $this->invokeMethod($generator, 'generateFieldReadStatement', [$field]);
        $expected = <<<'CODE'
\Protobuf\WireFormat::assertWireType($wire, 11);

$innerSize  = $reader->readVarint($stream);
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
        $proto     = new DescriptorProto();
        $field     = new FieldDescriptorProto();
        $generator = new ReadFromMethodBodyGenerator($proto, $this->options, $this->package);

        $field->setNumber(1);
        $field->setName('files');
        $field->setTypeName('ProtobufCompiler.Proto.File');
        $field->setType(FieldDescriptorProto\Type::TYPE_MESSAGE());
        $field->setLabel(FieldDescriptorProto\Label::LABEL_REPEATED());

        $actual   = $this->invokeMethod($generator, 'generateFieldReadStatement', [$field]);
        $expected = <<<'CODE'
\Protobuf\WireFormat::assertWireType($wire, 11);

$innerSize    = $reader->readVarint($stream);
$innerMessage = new \ProtobufCompiler\Proto\File();

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

    public function testGenerateBody()
    {
        $proto     = new DescriptorProto();
        $field     = new FieldDescriptorProto();
        $generator = new ReadFromMethodBodyGenerator($proto, $this->options, $this->package);

        $field->setNumber(1);
        $field->setName('lines');
        $field->setType(FieldDescriptorProto\Type::TYPE_INT32());
        $field->setLabel(FieldDescriptorProto\Label::LABEL_REPEATED());

        $proto->addField($field);

        $actual   = $generator->generateBody();
        $expected = <<<'CODE'
$reader = $context->getReader();
$length = $context->getLength();
$stream = $context->getStream();

$limit = ($length !== null)
    ? ($stream->tell() + $length)
    : null;

while ($limit === null || $stream->tell() < $limit) {

    if ($stream->eof()) {
        break;
    }

    $key  = $reader->readVarint($stream);
    $wire = \Protobuf\WireFormat::getTagWireType($key);
    $tag  = \Protobuf\WireFormat::getTagFieldNumber($key);

    if ($stream->eof()) {
        break;
    }

    if ($tag === 1) {
        \Protobuf\WireFormat::assertWireType($wire, 5);

        if ($this->lines === null) {
            $this->lines = new \Protobuf\ScalarCollection();
        }

        $this->lines->add($reader->readVarint($stream));

        continue;
    }

    if ($this->unknownFieldSet === null) {
        $this->unknownFieldSet = new \Protobuf\UnknownFieldSet();
    }

    $data    = $reader->readUnknown($stream, $wire);
    $unknown = new \Protobuf\Unknown($tag, $wire, $data);

    $this->unknownFieldSet->add($unknown);

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
        $proto     = new DescriptorProto();
        $generator = new ReadFromMethodBodyGenerator($proto, $this->options, $this->package);

        $this->invokeMethod($generator, 'generateReadScalarStatement', [-123]);
    }
}
