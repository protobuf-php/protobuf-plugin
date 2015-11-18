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

class MessageGeneratorTest extends TestCase
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

    public function testGeneratePersonMessage()
    {
        $fileDesc   = new DescriptorProto();
        $protoFile  = new FileDescriptorProto();
        $fieldId    = $this->createFieldDescriptorProto(1, 'name', Field::TYPE_STRING, Field::LABEL_REQUIRED);
        $fieldName  = $this->createFieldDescriptorProto(2, 'id', Field::TYPE_INT32, Field::LABEL_REQUIRED);
        $fieldEmail = $this->createFieldDescriptorProto(3, 'email', Field::TYPE_STRING, Field::LABEL_OPTIONAL);
        $fieldPhone = $this->createFieldDescriptorProto(4, 'phone', Field::TYPE_MESSAGE, Field::LABEL_REPEATED, '.ProtobufTest.Protos.Person.PhoneNumber');

        $fileDesc->setName('Person');
        $fileDesc->addField($fieldId);
        $fileDesc->addField($fieldName);
        $fileDesc->addField($fieldEmail);
        $fileDesc->addField($fieldPhone);

        $protoFile->setName('addressbook.proto');
        $protoFile->setPackage('ProtobufCompilerTest.Protos');

        $options   = Options::fromArray(['package' => 'ProtobufCompilerTest.Protos']);
        $generator = new Generator($protoFile, $options);
        $className = 'ProtobufCompilerTest.Protos.Person';
        $result    = $generator->generateMessages([$fileDesc], 'ProtobufCompilerTest.Protos');
        $expected  = $this->getFixtureFileContent('Person.tpl');

        // file_put_contents(__DIR__ . '/../Fixtures/Person.tpl', $result[$className]);

        $this->assertArrayHasKey($className, $result);
        $this->assertEquals($expected, $result[$className]);
    }

    public function testGeneratePhoneNumberMessage()
    {
        $fileDesc    = new DescriptorProto();
        $protoFile   = new FileDescriptorProto();
        $fieldNumber = $this->createFieldDescriptorProto(1, 'number', Field::TYPE_STRING, Field::LABEL_REQUIRED);
        $fieldType   = $this->createFieldDescriptorProto(2, 'type', Field::TYPE_ENUM, Field::LABEL_OPTIONAL, '.ProtobufTest.Protos.Person.PhoneType');

        $fieldType->setDefaultValue('HOME');

        $fileDesc->setName('PhoneNumber');
        $fileDesc->addField($fieldNumber);
        $fileDesc->addField($fieldType);

        $protoFile->setName('addressbook.proto');
        $protoFile->setPackage('ProtobufCompilerTest.Protos');

        $options   = Options::fromArray(['package' => 'ProtobufCompilerTest.Protos']);
        $generator = new Generator($protoFile, $options);
        $className = 'ProtobufCompilerTest.Protos.PhoneNumber';
        $result    = $generator->generateMessages([$fileDesc], 'ProtobufCompilerTest.Protos');
        $expected  = $this->getFixtureFileContent('PhoneNumber.tpl');

        // file_put_contents(__DIR__ . '/../Fixtures/PhoneNumber.tpl', $result[$className]);

        $this->assertArrayHasKey($className, $result);
        $this->assertEquals($expected, $result[$className]);
    }
}
