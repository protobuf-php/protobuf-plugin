<?php

namespace ProtobufCompilerTest\Generator\Message;

use Protobuf\Compiler\Generator\Message\SerializedSizeMethodBodyGenerator;
use google\protobuf\FieldDescriptorProto;
use google\protobuf\DescriptorProto;
use ProtobufCompilerTest\TestCase;
use google\protobuf\FieldOptions;

class SerializedSizeMethodBodyGeneratorTest extends TestCase
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
        $generator = new SerializedSizeMethodBodyGenerator($proto, $this->options, $this->package);

        $field->setNumber(1);
        $field->setName('lines');
        $field->setType(FieldDescriptorProto\Type::TYPE_INT32());
        $field->setLabel(FieldDescriptorProto\Label::LABEL_REPEATED());

        $proto->addField($field);
/*
if ($this->extensions !== null) {
    $size += $this->extensions->serializedSize($context);
}
*/
        $actual   = $generator->generateBody();
        $expected = <<<'CODE'
$calculator = $context->getSizeCalculator();
$size       = 0;

if ($this->lines !== null) {
    foreach ($this->lines as $val) {
        $size += 1;
        $size += $calculator->computeVarintSize($val);
    }
}

return $size;
CODE;

        $this->assertEquals($expected, implode(PHP_EOL, $actual));
    }
}
