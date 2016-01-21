<?php

namespace ProtobufCompilerTest\Descriptor;

use Protobuf\Field;
use Protobuf\Annotation;

use ProtobufCompilerTest\TestCase;
use ProtobufCompilerTest\Protos\AddressBook;

use Doctrine\Common\Annotations\SimpleAnnotationReader;

/**
 * @group functional
 */
class MessageDescriptorTest extends TestCase
{
    /**
     * @var \Protobuf\TextFormat
     */
    private $textFormat;

    public function setUp()
    {
        $this->markTestIncompleteIfProtoClassNotFound();

        parent::setUp();

        $this->reader = new SimpleAnnotationReader();

        // trigger class loader
        $this->assertTrue(class_exists(Annotation\Field::CLASS));
        $this->assertTrue(class_exists(Annotation\Descriptor::CLASS));
    }

    public function testFormatSimple()
    {
        $class      = new \ReflectionClass(AddressBook::CLASS);
        $annotation = $this->reader->getClassAnnotation($class, Annotation\Descriptor::CLASS);

        $this->assertInstanceOf(Annotation\Descriptor::CLASS, $annotation);
        $this->assertEquals('ProtobufCompilerTest.Protos', $annotation->package);
        $this->assertEquals('AddressBook', $annotation->name);
        $this->assertCount(1, $annotation->fields);

        $this->assertInstanceOf(Annotation\Field::CLASS, $annotation->fields[0]);
        $this->assertEquals(1, $annotation->fields[0]->tag);
        $this->assertEquals('person', $annotation->fields[0]->name);
        $this->assertEquals(Field::TYPE_MESSAGE, $annotation->fields[0]->type);
        $this->assertEquals('ProtobufCompilerTest.Protos.Person', $annotation->fields[0]->reference);
    }
}
