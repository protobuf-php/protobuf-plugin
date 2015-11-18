<?php

namespace ProtobufCompilerTest;

use Protobuf\Field;
use Protobuf\Compiler;
use Protobuf\Descriptor;
use Protobuf\Compiler\Options;
use Protobuf\Compiler\Generator;
use google\protobuf\SourceCodeInfo;
use google\protobuf\DescriptorProto;
use google\protobuf\EnumDescriptorProto;
use google\protobuf\FileDescriptorProto;
use google\protobuf\FieldDescriptorProto;
use google\protobuf\MethodDescriptorProto;
use google\protobuf\ServiceDescriptorProto;
use google\protobuf\EnumValueDescriptorProto;
use google\protobuf\compiler\CodeGeneratorRequest;

class GeneratorTest extends TestCase
{
    public function testGenerateMessage()
    {
        $fileDesc   = new DescriptorProto();
        $protoFile  = new FileDescriptorProto();

        $fileDesc->setName('Simple');
        $fileDesc->addField($this->createFieldDescriptorProto(1, 'double', Field::TYPE_DOUBLE, Field::LABEL_OPTIONAL));
        $fileDesc->addField($this->createFieldDescriptorProto(2, 'float', Field::TYPE_FLOAT, Field::LABEL_OPTIONAL));
        $fileDesc->addField($this->createFieldDescriptorProto(3, 'int64', Field::TYPE_INT64, Field::LABEL_OPTIONAL));
        $fileDesc->addField($this->createFieldDescriptorProto(4, 'uint64', Field::TYPE_UINT64, Field::LABEL_OPTIONAL));
        $fileDesc->addField($this->createFieldDescriptorProto(5, 'int32', Field::TYPE_INT32, Field::LABEL_OPTIONAL));
        $fileDesc->addField($this->createFieldDescriptorProto(6, 'fixed64', Field::TYPE_FIXED64, Field::LABEL_OPTIONAL));
        $fileDesc->addField($this->createFieldDescriptorProto(7, 'fixed32', Field::TYPE_FIXED32, Field::LABEL_OPTIONAL));
        $fileDesc->addField($this->createFieldDescriptorProto(8, 'bool', Field::TYPE_BOOL, Field::LABEL_OPTIONAL));
        $fileDesc->addField($this->createFieldDescriptorProto(9, 'string', Field::TYPE_STRING, Field::LABEL_OPTIONAL));
        $fileDesc->addField($this->createFieldDescriptorProto(12, 'bytes', Field::TYPE_BYTES, Field::LABEL_OPTIONAL));
        $fileDesc->addField($this->createFieldDescriptorProto(13, 'uint32', Field::TYPE_UINT32, Field::LABEL_OPTIONAL));
        $fileDesc->addField($this->createFieldDescriptorProto(15, 'sfixed32', Field::TYPE_SFIXED32, Field::LABEL_OPTIONAL));
        $fileDesc->addField($this->createFieldDescriptorProto(16, 'sfixed64', Field::TYPE_SFIXED64, Field::LABEL_OPTIONAL));
        $fileDesc->addField($this->createFieldDescriptorProto(17, 'sint32', Field::TYPE_SINT32, Field::LABEL_OPTIONAL));
        $fileDesc->addField($this->createFieldDescriptorProto(18, 'sint64', Field::TYPE_SINT64, Field::LABEL_OPTIONAL));

        $protoFile->setName('simple.proto');
        $protoFile->setPackage('ProtobufCompilerTest.Protos');

        $options   = Options::fromArray(['package' => 'ProtobufCompilerTest.Protos']);
        $generator = new Generator($protoFile, $options);
        $className = 'ProtobufCompilerTest.Protos.Simple';
        $result    = $generator->generateMessages([$fileDesc], 'ProtobufCompilerTest.Protos');
        $expected  = $this->getFixtureFileContent('Simple.tpl');

        // file_put_contents(__DIR__ . '/Fixtures/Simple.tpl', $result[$className]);

        $this->assertArrayHasKey($className, $result);
        $this->assertEquals($expected, $result[$className]);
    }

    public function testEnumMessage()
    {
        $enumDesc   = new EnumDescriptorProto();
        $protoFile  = new FileDescriptorProto();
        $mobileVal  = $this->createEnumValueDesc(0, 'MOBILE');
        $homeVal    = $this->createEnumValueDesc(1, 'HOME');
        $workVal    = $this->createEnumValueDesc(2, 'WORK');

        $enumDesc->setName('PhoneType');
        $enumDesc->addValue($mobileVal);
        $enumDesc->addValue($homeVal);
        $enumDesc->addValue($workVal);

        $protoFile->setName('addressbook.proto');
        $protoFile->setPackage('ProtobufCompilerTest.Protos');

        $options   = Options::fromArray(['package' => 'ProtobufCompilerTest.Protos']);
        $generator = new Generator($protoFile, $options);
        $className = 'ProtobufCompilerTest.Protos.Person.PhoneType';
        $result    = $generator->generateEnums([$enumDesc], 'ProtobufCompilerTest.Protos.Person');
        $expected  = $this->getFixtureFileContent('Person/PhoneType.tpl');

        // file_put_contents(__DIR__ . '/Fixtures/Person/PhoneType.tpl', $result[$className]);

        $this->assertArrayHasKey($className, $result);
        $this->assertEquals($expected, $result[$className]);
    }

    public function testService()
    {
        $serviceDesc = new ServiceDescriptorProto();
        $methodDesc  = new MethodDescriptorProto();
        $protoFile   = new FileDescriptorProto();

        // rpc search (SearchRequest) returns (SearchResponse);

        $methodDesc->setName('search');
        $methodDesc->setInputType('.ProtobufCompilerTest.Protos.Service.SearchRequest');
        $methodDesc->setOutputType('.ProtobufCompilerTest.Protos.Service.SearchResponse');

        $serviceDesc->setName('SearchService');
        $serviceDesc->addMethod($methodDesc);

        $protoFile->setName('service.proto');
        $protoFile->setPackage('ProtobufCompilerTest.Protos.Service');

        $options   = Options::fromArray(['package' => 'ProtobufCompilerTest.Protos.Service']);
        $generator = new Generator($protoFile, $options);
        $className = 'ProtobufCompilerTest.Protos.Service.SearchService';
        $result    = $generator->generateServices([$serviceDesc], 'ProtobufCompilerTest.Protos.Service');
        $expected  = file_get_contents(__DIR__ . '/Fixtures/Service/SearchService.tpl');

        //file_put_contents(__DIR__ . '/Fixtures/Service/SearchService.tpl', $result[$className]);

        $this->assertArrayHasKey($className, $result);
        $this->assertEquals($expected, $result[$className]);
    }

    public function testPsr4ClassName()
    {
        $fileDesc  = new DescriptorProto();
        $protoFile = new FileDescriptorProto();
        $package   = 'ProtobufCompilerTest.Protos';
        $className = 'ProtobufCompilerTest\\Protos\\Foo';
        $options   = Options::fromArray([
            'package' => $package,
            'psr4'    => [
                'Protos',
                'ProtobufCompilerTest'
            ],
        ]);

        $fileDesc->setName('Foo');
        $fileDesc->addField($this->createFieldDescriptorProto(1, 'bar', Field::TYPE_STRING, Field::LABEL_OPTIONAL));

        $protoFile->setName('simple.proto');
        $protoFile->setPackage($package);

        $generator = new Generator($protoFile, $options);

        $expected  = 'Protos\\Foo';
        $actual    = $this->invokeMethod($generator, 'getPsr4ClassName', [$className]);

        $this->assertEquals($expected, $actual);
    }

    protected function createEnumValueDesc($number, $name)
    {
        $field = new EnumValueDescriptorProto();

        $field->setName($name);
        $field->setNumber($number);

        return $field;
    }
}
