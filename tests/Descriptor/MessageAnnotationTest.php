<?php

namespace ProtobufCompilerTest\Descriptor;

use ProtobufCompilerTest\TestCase;
use ProtobufCompilerTest\Protos\AddressBook;

use google\protobuf\DescriptorProto;
use google\protobuf\FieldDescriptorProto;
use google\protobuf\FieldDescriptorProto\Type;
use google\protobuf\FieldDescriptorProto\Label;

/**
 * @group functional
 */
class MessageDescriptorTest extends TestCase
{
    public function setUp()
    {
        $this->markTestIncompleteIfProtoClassNotFound();

        parent::setUp();
    }

    public function testComplexMessageDescriptor()
    {
        $descriptor = AddressBook::descriptor();

        $this->assertInstanceOf(DescriptorProto::CLASS, $descriptor);
        $this->assertEquals('AddressBook', $descriptor->getName());

        $this->assertCount(1, $descriptor->getFieldList());

        $this->assertInstanceOf(FieldDescriptorProto::CLASS, $descriptor->getFieldList()[0]);
        $this->assertEquals(1, $descriptor->getFieldList()[0]->getNumber());
        $this->assertEquals('person', $descriptor->getFieldList()[0]->getName());
        $this->assertSame(Type::TYPE_MESSAGE(), $descriptor->getFieldList()[0]->getType());
        $this->assertSame(Label::LABEL_REPEATED(), $descriptor->getFieldList()[0]->getLabel());
        $this->assertEquals('.ProtobufCompilerTest.Protos.Person', $descriptor->getFieldList()[0]->getTypeName());
    }
}
