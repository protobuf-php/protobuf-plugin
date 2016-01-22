<?php

namespace ProtobufCompilerTest\Descriptor;

use Protobuf\Extension\ExtensionField;

use ProtobufCompilerTest\TestCase;
use ProtobufCompilerTest\Protos\Options;
use ProtobufCompilerTest\Protos\AddressBook;

use google\protobuf\MessageOptions;
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

    public function testMessageDescriptorOptionsExtensions()
    {
        $descriptor = Options\MyMessage::descriptor();

        $this->assertInstanceOf(DescriptorProto::CLASS, $descriptor);
        $this->assertEquals('MyMessage', $descriptor->getName());

        $this->assertInstanceOf(MessageOptions::CLASS, $descriptor->getOptions());

        $extensions = $descriptor->getOptions()->extensions();

        $this->assertCount(2, $extensions);

        $this->assertContains(Options\Extension::myMessageOption(), $extensions);
        $this->assertContains(Options\Extension::myMessageOptionMsg(), $extensions);

        $myMessageOption    = $extensions->get(Options\Extension::myMessageOption());
        $myMessageOptionMsg = $extensions->get(Options\Extension::myMessageOptionMsg());

        $this->assertSame(1234, $myMessageOption);
        $this->assertInstanceOf(Options\MyOption::CLASS, $myMessageOptionMsg);
        $this->assertSame('1234', $myMessageOptionMsg->getValue());
    }
}
