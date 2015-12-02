<?php

namespace ProtobufCompilerTest\Generator;

use google\protobuf\FieldDescriptorProto;
use google\protobuf\DescriptorProto;

use Protobuf\Compiler\Generator\BaseGenerator;
use Protobuf\Compiler\Entity;

use ProtobufCompilerTest\TestCase;

class BaseGeneratorTest extends TestCase
{
    /**
     * @var \Protobuf\Compiler\Generator\BaseGenerator
     */
    protected $generator;

    /**
     * @var \Protobuf\Compiler\Context
     */
    protected $context;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->context   = $this->getMock('Protobuf\Compiler\Context', [], [], '', false);
        $this->generator = new BaseGenerator($this->context);
    }

    public function testGetDoctype()
    {
        $entity = $this->getMock(Entity::CLASS, [], [], '', false);
        $type   = FieldDescriptorProto\Type::TYPE_ENUM();
        $field  = new FieldDescriptorProto();
        $ref    = 'Reference';

        $this->context->expects($this->once())
            ->method('getEntity')
            ->willReturn($entity)
            ->with($this->equalTo($ref));

        $entity->expects($this->once())
            ->method('getNamespacedName')
            ->willReturn('\\Reference');

        $field->setType($type);
        $field->setTypeName($ref);

        $expected = '\\Reference';
        $actual   = $this->invokeMethod($this->generator, 'getDoctype', [$field]);

        $this->assertEquals($expected, $actual);
    }

    public function testGetDefaultFieldValueEnum()
    {
        $entity  = $this->getMock(Entity::CLASS, [], [], '', false);
        $type    = FieldDescriptorProto\Type::TYPE_ENUM();
        $field   = new FieldDescriptorProto();
        $ref     = 'Reference';
        $default = 'CONST';

        $this->context->expects($this->once())
            ->method('getEntity')
            ->willReturn($entity)
            ->with($this->equalTo($ref));

        $entity->expects($this->once())
            ->method('getNamespacedName')
            ->willReturn('\\Reference');

        $field->setType($type);
        $field->setTypeName($ref);
        $field->setDefaultValue($default);

        $expected = '\\Reference::CONST()';
        $actual   = $this->invokeMethod($this->generator, 'getDefaultFieldValue', [$field]);

        $this->assertEquals($expected, $actual);
    }

    public function testGetDefaultFieldValueBool()
    {
        $field   = new FieldDescriptorProto();
        $type    = FieldDescriptorProto\Type::TYPE_BOOL();
        $default = 1;

        $field->setType($type);
        $field->setDefaultValue($default);

        $expected = 'true';
        $actual   = $this->invokeMethod($this->generator, 'getDefaultFieldValue', [$field]);

        $this->assertEquals($expected, $actual);
    }

    public function testGetDefaultFieldValueInt32()
    {
        $type    = FieldDescriptorProto\Type::TYPE_INT32();
        $field   = new FieldDescriptorProto();
        $default = 12345;

        $field->setType($type);
        $field->setDefaultValue($default);

        $expected = '12345';
        $actual   = $this->invokeMethod($this->generator, 'getDefaultFieldValue', [$field]);

        $this->assertEquals($expected, $actual);
    }

    public function testGetTypeHintMessage()
    {
        $entity = $this->getMock(Entity::CLASS, [], [], '', false);
        $label  = FieldDescriptorProto\Label::LABEL_REQUIRED();
        $type   = FieldDescriptorProto\Type::TYPE_MESSAGE();
        $field  = new FieldDescriptorProto();
        $ref    = 'Package.Reference';

        $field->setType($type);
        $field->setLabel($label);
        $field->setTypeName($ref);

        $this->context->expects($this->once())
            ->method('getEntity')
            ->willReturn($entity)
            ->with($this->equalTo($ref));

        $entity->expects($this->once())
            ->method('getNamespacedName')
            ->willReturn('\\Package\\Reference');

        $expected = '\Package\Reference';
        $actual   = $this->invokeMethod($this->generator, 'getTypeHint', [$field]);

        $this->assertEquals($expected, $actual);
    }

    public function testGetTypeHintMessageRepeated()
    {
        $entity = $this->getMock(Entity::CLASS, [], [], '', false);
        $label  = FieldDescriptorProto\Label::LABEL_REPEATED();
        $type   = FieldDescriptorProto\Type::TYPE_MESSAGE();
        $field  = new FieldDescriptorProto();
        $ref    = 'Package.Reference';

        $field->setType($type);
        $field->setLabel($label);
        $field->setTypeName($ref);

        $this->context->expects($this->once())
            ->method('getEntity')
            ->willReturn($entity)
            ->with($this->equalTo($ref));

        $entity->expects($this->once())
            ->method('getNamespacedName')
            ->willReturn('\\Package\\Reference');

        $expected = '\Protobuf\Collection';
        $actual   = $this->invokeMethod($this->generator, 'getTypeHint', [$field]);

        $this->assertEquals($expected, $actual);
    }

    public function testGetTypeHintScalar()
    {
        $field = new FieldDescriptorProto();
        $label = FieldDescriptorProto\Label::LABEL_REQUIRED();
        $type  = FieldDescriptorProto\Type::TYPE_INT32();

        $field->setType($type);
        $field->setLabel($label);

        $actual = $this->invokeMethod($this->generator, 'getTypeHint', [$field]);

        $this->assertEquals('int', $actual);
    }

    public function testGetUniqueFieldName()
    {
        $default = 'unknownFieldSet';
        $proto   = new DescriptorProto();
        $field1  = new FieldDescriptorProto();
        $field2  = new FieldDescriptorProto();

        $field1->setName('foo');
        $field2->setName('bar');

        $proto->addField($field1);
        $proto->addField($field2);

        $expected = 'unknownFieldSet';
        $actual   = $this->invokeMethod($this->generator, 'getUniqueFieldName', [$proto, $default]);

        $this->assertEquals($expected, $actual);
    }

    public function testGetUniqueUnknownFieldSetName()
    {
        $default = 'unknownFieldSet';
        $proto   = new DescriptorProto();
        $field1  = new FieldDescriptorProto();
        $field2  = new FieldDescriptorProto();
        $field3  = new FieldDescriptorProto();

        $field1->setName('unknownFieldSet');
        $field2->setName('unknownFieldSet0');
        $field3->setName('unknownFieldSet1');

        $proto->addField($field1);
        $proto->addField($field2);
        $proto->addField($field3);

        $expected = 'unknownFieldSet2';
        $actual   = $this->invokeMethod($this->generator, 'getUniqueFieldName', [$proto, $default]);

        $this->assertEquals($expected, $actual);
    }
}
