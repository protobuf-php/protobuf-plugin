<?php

namespace ProtobufCompilerTest\Generator;

use Protobuf\Compiler\Generator\BaseGenerator;
use google\protobuf\FieldDescriptorProto;
use google\protobuf\DescriptorProto;
use ProtobufCompilerTest\TestCase;

class BaseGeneratorTest extends TestCase
{
    /**
     * @var \Protobuf\Compiler\Generator\BaseGenerator
     */
    protected $generator;

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
        $this->package   = 'ProtobufCompiler.Proto';
        $this->proto     = $this->getMock('Protobuf\Message');
        $this->options   = $this->getMock('Protobuf\Compiler\Options');
        $this->generator = new BaseGenerator($this->proto, $this->options, $this->package);
    }

    public function testGetDoctype()
    {
        $field = new FieldDescriptorProto();
        $type  = FieldDescriptorProto\Type::TYPE_ENUM();
        $ref   = 'Reference';

        $field->setType($type);
        $field->setTypeName($ref);

        $expected = '\\' . $ref;
        $actual   = $this->invokeMethod($this->generator, 'getDoctype', [$field]);

        $this->assertEquals($expected, $actual);
    }

    public function testGetDefaultFieldValueEnum()
    {
        $field   = new FieldDescriptorProto();
        $type    = FieldDescriptorProto\Type::TYPE_ENUM();
        $ref     = 'Reference';
        $default = 'CONST';

        $field->setType($type);
        $field->setTypeName($ref);
        $field->setDefaultValue($default);

        $expected = '\\' . $ref . '::' . $default . '()';
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
        $field   = new FieldDescriptorProto();
        $type    = FieldDescriptorProto\Type::TYPE_INT32();
        $default = 12345;

        $field->setType($type);
        $field->setDefaultValue($default);

        $expected = '12345';
        $actual   = $this->invokeMethod($this->generator, 'getDefaultFieldValue', [$field]);

        $this->assertEquals($expected, $actual);
    }

    public function testGetTypeHintMessage()
    {
        $field = new FieldDescriptorProto();
        $label = FieldDescriptorProto\Label::LABEL_REQUIRED();
        $type  = FieldDescriptorProto\Type::TYPE_MESSAGE();
        $ref   = 'Package.Reference';

        $field->setType($type);
        $field->setLabel($label);
        $field->setTypeName($ref);

        $expected = '\Package\Reference';
        $actual   = $this->invokeMethod($this->generator, 'getTypeHint', [$field]);

        $this->assertEquals($expected, $actual);
    }

    public function testGetTypeHintMessageRepeated()
    {
        $field = new FieldDescriptorProto();
        $type  = FieldDescriptorProto\Type::TYPE_MESSAGE();
        $label = FieldDescriptorProto\Label::LABEL_REPEATED();
        $ref   = 'Package.Reference';

        $field->setType($type);
        $field->setLabel($label);
        $field->setTypeName($ref);

        $expected = '\Doctrine\Common\Collections\Collection';
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

        $this->assertNull($actual);
    }

    public function testGetUnknownFieldSetName()
    {
        $proto  = new DescriptorProto();
        $field1 = new FieldDescriptorProto();
        $field2 = new FieldDescriptorProto();

        $field1->setName('foo');
        $field2->setName('bar');

        $proto->addField($field1);
        $proto->addField($field2);

        $expected = 'unknownFieldSet';
        $actual   = $this->invokeMethod($this->generator, 'getUnknownFieldSetName', [$proto]);

        $this->assertEquals($expected, $actual);
    }

    public function testGetUniqueUnknownFieldSetName()
    {
        $proto  = new DescriptorProto();
        $field1 = new FieldDescriptorProto();
        $field2 = new FieldDescriptorProto();
        $field3 = new FieldDescriptorProto();

        $field1->setName('unknownFieldSet');
        $field2->setName('unknownFieldSet0');
        $field3->setName('unknownFieldSet1');

        $proto->addField($field1);
        $proto->addField($field2);
        $proto->addField($field3);

        $expected = 'unknownFieldSet2';
        $actual   = $this->invokeMethod($this->generator, 'getUnknownFieldSetName', [$proto]);

        $this->assertEquals($expected, $actual);
    }
}
