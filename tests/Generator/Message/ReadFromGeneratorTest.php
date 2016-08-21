<?php

namespace ProtobufCompilerTest\Generator\Message;

use Protobuf\Compiler\Generator\Message\ReadFromGenerator;
use google\protobuf\FieldDescriptorProto;
use google\protobuf\DescriptorProto;
use ProtobufCompilerTest\TestCase;
use google\protobuf\FieldOptions;
use Protobuf\Field;

class ReadFromGeneratorTest extends TestCase
{
    public function testGenerateBody()
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
                                1  => ['lines', Field::TYPE_INT32, Field::LABEL_REPEATED]
                            ]
                        ]
                    ]
                ]
            ]
        ]);

        $entity    = $context->getEntity('ProtobufCompilerTest.Protos.Simple');
        $generator = new ReadFromGenerator($context);
        $descritor = $entity->getDescriptor();
        $field     = $descritor->getFieldList()[0];

        $actual   = $this->invokeMethod($generator, 'generateBody', [$entity]);
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
        $innerSize  = $reader->readVarint($stream);
        $innerLimit = $stream->tell() + $innerSize;

        if ($this->lines === null) {
            $this->lines = new \Protobuf\ScalarCollection();
        }

        while ($stream->tell() < $innerLimit) {
            $this->lines->add($reader->readVarint($stream));
        }

        continue;
    }

    $extensions = $context->getExtensionRegistry();
    $extension  = $extensions ? $extensions->findByNumber(__CLASS__, $tag) : null;

    if ($extension !== null) {
        $this->extensions()->add($extension, $extension->readFrom($context, $wire));

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
