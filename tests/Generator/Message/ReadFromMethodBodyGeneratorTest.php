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
}
