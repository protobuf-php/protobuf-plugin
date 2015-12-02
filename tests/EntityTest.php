<?php

namespace ProtobufCompilerTest;

use google\protobuf\FileDescriptorProto;
use google\protobuf\DescriptorProto;
use google\protobuf\php\Extension;
use google\protobuf\FileOptions;

use Protobuf\Compiler\Entity;

class EntityTest extends TestCase
{
    public function testFullyQualifiedName()
    {
        $name           = 'SimpleMessage';
        $type           = Entity::TYPE_MESSAGE;
        $descriptor     = new DescriptorProto();
        $fileDescriptor = new FileDescriptorProto();

        $entity = new Entity($type, $name, $descriptor, $fileDescriptor);

        $this->assertNull($this->invokeMethod($entity, 'fullyQualifiedName', [null]));
        $this->assertEquals('package.SimpleMessage', $this->invokeMethod($entity, 'fullyQualifiedName', ['package', null, 'SimpleMessage']));
        $this->assertEquals('package.Parent.SimpleMessage', $this->invokeMethod($entity, 'fullyQualifiedName', ['package', 'Parent', 'SimpleMessage']));
    }

    public function testGetPackage()
    {
        $name            = 'SimpleMessage';
        $type            = Entity::TYPE_MESSAGE;
        $descriptor      = new DescriptorProto();
        $fileDescriptor1 = new FileDescriptorProto();
        $fileDescriptor2 = new FileDescriptorProto();
        $fileDescriptor3 = new FileDescriptorProto();

        $fileDescriptor2->setPackage('package');
        $fileDescriptor3->setPackage('package');

        $entity1 = new Entity($type, $name, $descriptor, $fileDescriptor1);
        $entity2 = new Entity($type, $name, $descriptor, $fileDescriptor2);
        $entity3 = new Entity($type, $name, $descriptor, $fileDescriptor3, 'Parent');

        $this->assertNull($entity1->getPackage());
        $this->assertEquals('package', $entity2->getPackage());
        $this->assertEquals('package.Parent', $entity3->getPackage());
    }

    public function testGetClass()
    {
        $name            = 'SimpleMessage';
        $type            = Entity::TYPE_MESSAGE;
        $descriptor      = new DescriptorProto();
        $fileDescriptor1 = new FileDescriptorProto();
        $fileDescriptor2 = new FileDescriptorProto();
        $fileDescriptor3 = new FileDescriptorProto();

        $fileDescriptor2->setPackage('package');
        $fileDescriptor3->setPackage('package');

        $entity1 = new Entity($type, $name, $descriptor, $fileDescriptor1);
        $entity2 = new Entity($type, $name, $descriptor, $fileDescriptor2);
        $entity3 = new Entity($type, $name, $descriptor, $fileDescriptor3, 'Parent');

        $this->assertEquals('SimpleMessage', $entity1->getClass());
        $this->assertEquals('package.SimpleMessage', $entity2->getClass());
        $this->assertEquals('package.Parent.SimpleMessage', $entity3->getClass());
    }

    public function testGetNamespace()
    {
        $name            = 'SimpleMessage';
        $type            = Entity::TYPE_MESSAGE;
        $descriptor      = new DescriptorProto();
        $fileDescriptor1 = new FileDescriptorProto();
        $fileDescriptor2 = new FileDescriptorProto();
        $fileDescriptor3 = new FileDescriptorProto();

        $fileDescriptor2->setPackage('package');
        $fileDescriptor3->setPackage('package');

        $entity1 = new Entity($type, $name, $descriptor, $fileDescriptor1);
        $entity2 = new Entity($type, $name, $descriptor, $fileDescriptor2);
        $entity3 = new Entity($type, $name, $descriptor, $fileDescriptor3, 'Parent');

        $this->assertNull($entity1->getNamespace());
        $this->assertEquals('package', $entity2->getNamespace());
        $this->assertEquals('package\\Parent', $entity3->getNamespace());
    }

    public function testGetNamespaceUsingPhpOptions()
    {
        $name            = 'SimpleMessage';
        $type            = Entity::TYPE_MESSAGE;
        $descriptor      = new DescriptorProto();
        $fileDescriptor1 = new FileDescriptorProto();
        $fileDescriptor2 = new FileDescriptorProto();
        $fileDescriptor3 = new FileDescriptorProto();

        $fileOptions2 = new FileOptions();
        $fileOptions3 = new FileOptions();

        $fileOptions2->extensions()->put(Extension::package(), 'Package');
        $fileOptions3->extensions()->put(Extension::package(), 'Package');

        $fileDescriptor1->setPackage('package');
        $fileDescriptor2->setPackage('package');
        $fileDescriptor3->setPackage('package');
        $fileDescriptor2->setOptions($fileOptions2);
        $fileDescriptor3->setOptions($fileOptions3);

        $entity1 = new Entity($type, $name, $descriptor, $fileDescriptor1);
        $entity2 = new Entity($type, $name, $descriptor, $fileDescriptor2);
        $entity3 = new Entity($type, $name, $descriptor, $fileDescriptor3, 'Parent');

        $this->assertEquals('package', $entity1->getNamespace());
        $this->assertEquals('Package', $entity2->getNamespace());
        $this->assertEquals('Package\\Parent', $entity3->getNamespace());
    }

    public function testGetNamespacedName()
    {
        $name            = 'SimpleMessage';
        $type            = Entity::TYPE_MESSAGE;
        $descriptor      = new DescriptorProto();
        $fileDescriptor1 = new FileDescriptorProto();
        $fileDescriptor2 = new FileDescriptorProto();
        $fileDescriptor3 = new FileDescriptorProto();

        $fileDescriptor2->setPackage('package');
        $fileDescriptor3->setPackage('package');

        $entity1 = new Entity($type, $name, $descriptor, $fileDescriptor1);
        $entity2 = new Entity($type, $name, $descriptor, $fileDescriptor2);
        $entity3 = new Entity($type, $name, $descriptor, $fileDescriptor3, 'Parent');

        $this->assertEquals('\\SimpleMessage', $entity1->getNamespacedName());
        $this->assertEquals('\\package\\SimpleMessage', $entity2->getNamespacedName());
        $this->assertEquals('\\package\\Parent\\SimpleMessage', $entity3->getNamespacedName());
    }
}
