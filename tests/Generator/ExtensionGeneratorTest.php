<?php

namespace ProtobufCompilerTest\Generator;

use google\protobuf\FieldDescriptorProto;
use google\protobuf\FileDescriptorProto;
use google\protobuf\DescriptorProto;

use Protobuf\Compiler\Generator\MessageGenerator;
use ProtobufCompilerTest\TestCase;
use Protobuf\Compiler\Generator;
use Protobuf\Compiler\Options;
use Protobuf\Field;

class ExtensionGeneratorTest extends TestCase
{
    /**
     * @var \Protobuf\Compiler\Options
     */
    protected $options;

    /**
     * @var \Protobuf\Message
     */
    protected $proto;

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
        $this->proto   = $this->getMock('Protobuf\Message');
        $this->options = $this->getMock('Protobuf\Compiler\Options');
    }

    public function testGenerateExtension()
    {
        $descriptor        = new DescriptorProto();
        $protoFile         = new FileDescriptorProto();
        $fieldExtAnimal    = $this->createFieldDescriptorProto(101, 'animal', Field::TYPE_MESSAGE, Field::LABEL_OPTIONAL, '.ProtobufTest.Protos.Extension.Dog');
        $fieldExtHabitat   = $this->createFieldDescriptorProto(200, 'habitat', Field::TYPE_STRING, Field::LABEL_OPTIONAL);
        $fieldExtVerbose   = $this->createFieldDescriptorProto(200, 'verbose', Field::TYPE_BOOL, Field::LABEL_OPTIONAL);

        $descriptor->setName('Dog');
        $descriptor->addExtension($fieldExtAnimal);

        $fieldExtHabitat->setExtendee('.ProtobufTest.Protos.Extension.Animal');
        $fieldExtVerbose->setExtendee('.ProtobufTest.Protos.Extension.Command');

        $protoFile->setName('extension.proto');
        $protoFile->setPackage('ProtobufTest.Protos.Extension');
        $protoFile->addExtension($fieldExtHabitat);
        $protoFile->addExtension($fieldExtVerbose);
        $protoFile->addMessageType($descriptor);

        $options   = Options::fromArray(['package' => 'ProtobufTest.Protos.Extension']);
        $generator = new Generator($protoFile, $options);
        $className = 'ProtobufTest.Protos.Extension.Extension';
        $result    = $generator->generateExtension('ProtobufTest.Protos.Extension');
        $expected  = $this->getFixtureFileContent('Extension/Extension.tpl');

        // file_put_contents(__DIR__ . '/../Fixtures/Extension/Extension.tpl', $result[$className]);

        $this->assertArrayHasKey($className, $result);
        $this->assertEquals($expected, $result[$className]);
    }
}
