<?php

namespace ProtobufCompilerTest\Generator\Message;

use Protobuf\Compiler\Generator\Message\SerializedSizeFieldStatementGenerator;
use google\protobuf\FieldDescriptorProto;
use google\protobuf\DescriptorProto;
use ProtobufCompilerTest\TestCase;
use google\protobuf\FieldOptions;

class SerializedSizeFieldStatementGeneratorTest extends TestCase
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

    public function testGenerateComputeInt32Statement()
    {
        $proto     = new DescriptorProto();
        $field     = new FieldDescriptorProto();
        $generator = new SerializedSizeFieldStatementGenerator($proto, $this->options, $this->package);

        $field->setNumber(1);
        $field->setName('count');
        $field->setType(FieldDescriptorProto\Type::TYPE_INT32());
        $field->setLabel(FieldDescriptorProto\Label::LABEL_REQUIRED());

        $actual   = $generator->generateFieldSizeStatement($field);
        $expected = <<<'CODE'
$size += 1;
$size += $calculator->computeVarintSize($this->count);
CODE;

        $this->assertEquals($expected, implode(PHP_EOL, $actual));
    }

    public function testGenerateComputeStringRepeatedStatement()
    {
        $proto     = new DescriptorProto();
        $field     = new FieldDescriptorProto();
        $generator = new SerializedSizeFieldStatementGenerator($proto, $this->options, $this->package);

        $field->setNumber(1);
        $field->setName('lines');
        $field->setType(FieldDescriptorProto\Type::TYPE_INT32());
        $field->setLabel(FieldDescriptorProto\Label::LABEL_REPEATED());

        $actual   = $generator->generateFieldSizeStatement($field);
        $expected = <<<'CODE'
foreach ($this->lines as $val) {
    $size += 1;
    $size += $calculator->computeVarintSize($val);
}
CODE;

        $this->assertEquals($expected, implode(PHP_EOL, $actual));
    }

    public function testGenerateComputePackedInt32Statement()
    {
        $options   = new FieldOptions();
        $proto     = new DescriptorProto();
        $field     = new FieldDescriptorProto();
        $generator = new SerializedSizeFieldStatementGenerator($proto, $this->options, $this->package);

        $options->setPacked(true);

        $field->setNumber(1);
        $field->setName('tags');
        $field->setOptions($options);
        $field->setType(FieldDescriptorProto\Type::TYPE_INT32());
        $field->setLabel(FieldDescriptorProto\Label::LABEL_REPEATED());

        $actual   = $generator->generateFieldSizeStatement($field);
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
        $proto     = new DescriptorProto();
        $field     = new FieldDescriptorProto();
        $generator = new SerializedSizeFieldStatementGenerator($proto, $this->options, $this->package);

        $field->setNumber(1);
        $field->setName('phone');
        $field->setType(FieldDescriptorProto\Type::TYPE_MESSAGE());
        $field->setLabel(FieldDescriptorProto\Label::LABEL_REQUIRED());
        $field->setTypeName('ProtobufCompiler.Proto.PhoneNumber');

        $actual   = $generator->generateFieldSizeStatement($field);
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
        $proto     = new DescriptorProto();
        $field     = new FieldDescriptorProto();
        $generator = new SerializedSizeFieldStatementGenerator($proto, $this->options, $this->package);

        $field->setNumber(1);
        $field->setName('files');
        $field->setTypeName('ProtobufCompiler.Proto.File');
        $field->setType(FieldDescriptorProto\Type::TYPE_MESSAGE());
        $field->setLabel(FieldDescriptorProto\Label::LABEL_REPEATED());

        $actual   = $generator->generateFieldSizeStatement($field);
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
