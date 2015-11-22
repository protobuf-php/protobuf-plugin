<?php

namespace ProtobufCompilerTest\Generator\Message;

use Protobuf\Compiler\Generator\Message\WriteFieldStatementGenerator;
use google\protobuf\FieldDescriptorProto;
use google\protobuf\DescriptorProto;
use ProtobufCompilerTest\TestCase;
use google\protobuf\FieldOptions;

class WriteFieldStatementGeneratorTest extends TestCase
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

    public function testGenerateWriteInt32Statement()
    {
        $proto     = new DescriptorProto();
        $field     = new FieldDescriptorProto();
        $generator = new WriteFieldStatementGenerator($proto, $this->options, $this->package);

        $field->setNumber(1);
        $field->setName('count');
        $field->setType(FieldDescriptorProto\Type::TYPE_INT32());
        $field->setLabel(FieldDescriptorProto\Label::LABEL_REQUIRED());

        $actual   =  $generator->generateFieldWriteStatement($field);
        $expected = <<<'CODE'
$writer->writeVarint($stream, 8);
$writer->writeVarint($stream, $this->count);
CODE;

        $this->assertEquals($expected, implode(PHP_EOL, $actual));
    }

    public function testGenerateWriteStringRepeatedStatement()
    {
        $proto     = new DescriptorProto();
        $field     = new FieldDescriptorProto();
        $generator = new WriteFieldStatementGenerator($proto, $this->options, $this->package);

        $field->setNumber(1);
        $field->setName('lines');
        $field->setType(FieldDescriptorProto\Type::TYPE_INT32());
        $field->setLabel(FieldDescriptorProto\Label::LABEL_REPEATED());

        $actual   =  $generator->generateFieldWriteStatement($field);
        $expected = <<<'CODE'
foreach ($this->lines as $val) {
    $writer->writeVarint($stream, 8);
    $writer->writeVarint($stream, $val);
}
CODE;

        $this->assertEquals($expected, implode(PHP_EOL, $actual));
    }

    public function testGenerateWritePackedInt32Statement()
    {
        $options   = new FieldOptions();
        $proto     = new DescriptorProto();
        $field     = new FieldDescriptorProto();
        $generator = new WriteFieldStatementGenerator($proto, $this->options, $this->package);

        $options->setPacked(true);

        $field->setNumber(1);
        $field->setName('tags');
        $field->setOptions($options);
        $field->setType(FieldDescriptorProto\Type::TYPE_INT32());
        $field->setLabel(FieldDescriptorProto\Label::LABEL_REPEATED());

        $actual   =  $generator->generateFieldWriteStatement($field);
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
        $proto     = new DescriptorProto();
        $field     = new FieldDescriptorProto();
        $generator = new WriteFieldStatementGenerator($proto, $this->options, $this->package);

        $field->setNumber(1);
        $field->setName('phone');
        $field->setType(FieldDescriptorProto\Type::TYPE_MESSAGE());
        $field->setLabel(FieldDescriptorProto\Label::LABEL_REQUIRED());
        $field->setTypeName('ProtobufCompiler.Proto.PhoneNumber');

        $actual   =  $generator->generateFieldWriteStatement($field);
        $expected = <<<'CODE'
$writer->writeVarint($stream, 10);
$writer->writeVarint($stream, $this->phone->serializedSize($sizeContext));
$this->phone->writeTo($context);
CODE;

        $this->assertEquals($expected, implode(PHP_EOL, $actual));
    }

    public function testGenerateWriteMessageRepeatedStatement()
    {
        $proto     = new DescriptorProto();
        $field     = new FieldDescriptorProto();
        $generator = new WriteFieldStatementGenerator($proto, $this->options, $this->package);

        $field->setNumber(1);
        $field->setName('files');
        $field->setTypeName('ProtobufCompiler.Proto.File');
        $field->setType(FieldDescriptorProto\Type::TYPE_MESSAGE());
        $field->setLabel(FieldDescriptorProto\Label::LABEL_REPEATED());

        $actual   =  $generator->generateFieldWriteStatement($field);
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
        $proto     = new DescriptorProto();
        $field     = new FieldDescriptorProto();
        $generator = new WriteFieldStatementGenerator($proto, $this->options, $this->package);

        $generator->setTargetVar('$count');

        $field->setNumber(1);
        $field->setName('count');
        $field->setType(FieldDescriptorProto\Type::TYPE_INT32());
        $field->setLabel(FieldDescriptorProto\Label::LABEL_REQUIRED());

        $actual   =  $generator->generateFieldWriteStatement($field);
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
        $proto     = new DescriptorProto();
        $generator = new WriteFieldStatementGenerator($proto, $this->options, $this->package);

        $this->invokeMethod($generator, 'generateWriteScalarStatement', [-123, 123]);
    }
}
