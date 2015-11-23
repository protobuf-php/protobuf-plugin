<?php

namespace ProtobufCompilerTest\Generator\Message;

use Protobuf\Compiler\Generator\Message\WriteToMethodBodyGenerator;
use google\protobuf\FieldDescriptorProto;
use google\protobuf\DescriptorProto;
use ProtobufCompilerTest\TestCase;
use google\protobuf\FieldOptions;

class WriteToMethodBodyGeneratorTest extends TestCase
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

    public function testGenerateBody()
    {
        $proto     = new DescriptorProto();
        $field     = new FieldDescriptorProto();
        $generator = new WriteToMethodBodyGenerator($proto, $this->options, $this->package);

        $field->setNumber(1);
        $field->setName('lines');
        $field->setType(FieldDescriptorProto\Type::TYPE_INT32());
        $field->setLabel(FieldDescriptorProto\Label::LABEL_REPEATED());

        $proto->addField($field);

        $actual   = $generator->generateBody();
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
