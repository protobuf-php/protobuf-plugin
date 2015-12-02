<?php

namespace ProtobufCompilerTest;

use google\protobuf\compiler\CodeGeneratorRequest;
use google\protobuf\ServiceDescriptorProto;
use google\protobuf\FileDescriptorProto;
use google\protobuf\DescriptorProto;
use google\protobuf\php\Extension;
use google\protobuf\FileOptions;

use Protobuf\Field;
use Protobuf\Compiler\Entity;
use Protobuf\Compiler\EntityBuilder;

class EntityBuilderTest extends TestCase
{
    public function testBuildEntities()
    {
        $descriptor1 = $this->createFileDescriptorProto('simple.proto', 'Protos', [
            'messages' => [
                [
                    'name'   => 'Simple',
                    'fields' => []
                ]
            ]
        ]);

        $descriptor2 = $this->createFileDescriptorProto('include.proto', 'Protos', [
            'messages' => [
                [
                    'name'   => 'Include',
                    'fields' => []
                ]
            ]
        ]);

        $request = new CodeGeneratorRequest();

        $request->addProtoFile($descriptor1);
        $request->addProtoFile($descriptor2);
        $request->addFileToGenerate($descriptor1->getName());

        $builder  = new EntityBuilder($request);
        $entities = $builder->buildEntities();

        $this->assertCount(2, $entities);
        $this->assertArrayhasKey('Protos.Simple', $entities);
        $this->assertArrayhasKey('Protos.Include', $entities);

        $this->assertInstanceOf(Entity::CLASS, $entities['Protos.Simple']);
        $this->assertInstanceOf(Entity::CLASS, $entities['Protos.Include']);

        $this->assertEquals('Protos.Simple', $entities['Protos.Simple']->getClass());
        $this->assertEquals('Protos.Include', $entities['Protos.Include']->getClass());

        $this->assertEquals(Entity::TYPE_MESSAGE, $entities['Protos.Simple']->getType());
        $this->assertEquals(Entity::TYPE_MESSAGE, $entities['Protos.Include']->getType());

        $this->assertTrue($entities['Protos.Simple']->isFileToGenerate());
        $this->assertFalse($entities['Protos.Include']->isFileToGenerate());
    }

    public function testBuildFileEntities()
    {
        $request    = new CodeGeneratorRequest();
        $descriptor = $this->createFileDescriptorProto('simple.proto', 'Protos', [
            'messages' => [
                [
                    'name'   => 'ParentMessage',
                    'fields' => [],
                    'values' => [
                        'messages' => [
                            [
                                'name'   => 'InnerMessage',
                                'fields' => [],
                                'values' => [
                                    'enums' => [
                                        [
                                            'name'   => 'InnerMessageEnum',
                                            'values' => []
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            'extensions' => [
                200  => ['extension', Field::TYPE_STRING, Field::LABEL_OPTIONAL, '.Protos.Extendee']
            ],
            'enums' => [
                [
                    'name'   => 'EnumType',
                    'values' => []
                ]
            ],
            'services' => [
                [
                    'name'   => 'SearchService',
                    'values' => []
                ]
            ]
        ]);

        $builder  = new EntityBuilder($request);
        $entities = $this->invokeMethod($builder, 'buildFileEntities', [$descriptor]);

        $this->assertCount(6, $entities);
        $this->assertInstanceOf(Entity::CLASS, $entities[0]);
        $this->assertInstanceOf(Entity::CLASS, $entities[1]);
        $this->assertInstanceOf(Entity::CLASS, $entities[2]);
        $this->assertInstanceOf(Entity::CLASS, $entities[3]);
        $this->assertInstanceOf(Entity::CLASS, $entities[4]);
        $this->assertInstanceOf(Entity::CLASS, $entities[5]);

        $this->assertEquals('Protos.ParentMessage', $entities[0]->getClass());
        $this->assertEquals('Protos.ParentMessage.InnerMessage', $entities[1]->getClass());
        $this->assertEquals('Protos.ParentMessage.InnerMessage.InnerMessageEnum', $entities[2]->getClass());
        $this->assertEquals('Protos.SearchService', $entities[3]->getClass());
        $this->assertEquals('Protos.EnumType', $entities[4]->getClass());
        $this->assertEquals('Protos.Extension', $entities[5]->getClass());

        $this->assertEquals(Entity::TYPE_MESSAGE, $entities[0]->getType());
        $this->assertEquals(Entity::TYPE_MESSAGE, $entities[1]->getType());
        $this->assertEquals(Entity::TYPE_ENUM, $entities[2]->getType());
        $this->assertEquals(Entity::TYPE_SERVICE, $entities[3]->getType());
        $this->assertEquals(Entity::TYPE_ENUM, $entities[4]->getType());
        $this->assertEquals(Entity::TYPE_EXTENSION, $entities[5]->getType());
    }

    public function testHasExtension()
    {
        $descriptor1 = $this->createFileDescriptorProto('simple.proto', 'Protos', [
            'extensions' => [
                200  => ['extension', Field::TYPE_STRING, Field::LABEL_OPTIONAL, '.Protos.Extendee']
            ]
        ]);

        $descriptor2 = $this->createFileDescriptorProto('simple.proto', 'Protos', [
            'messages' => [
                [
                    'name'   => 'Dog',
                    'fields' => [],
                    'values' => [
                        'extensions' => [
                            101  => ['animal',  Field::TYPE_MESSAGE, Field::LABEL_OPTIONAL, '.ProtobufCompilerTest.Protos.Extension.Animal', '.ProtobufCompilerTest.Protos.Extension.Dog']
                        ]
                    ]
                ]
            ]
        ]);

        $descriptor3 = $this->createFileDescriptorProto('simple.proto', 'Protos', []);
        $descriptor4 = $this->createFileDescriptorProto('simple.proto', 'Protos', [
            'messages' => [
                [
                    'name'   => 'Simple',
                    'fields' => []
                ]
            ]
        ]);

        $request = new CodeGeneratorRequest();
        $builder = new EntityBuilder($request);

        $this->assertTrue($this->invokeMethod($builder, 'hasExtension', [$descriptor1]));
        $this->assertTrue($this->invokeMethod($builder, 'hasExtension', [$descriptor2]));
        $this->assertFalse($this->invokeMethod($builder, 'hasExtension', [$descriptor3]));
        $this->assertFalse($this->invokeMethod($builder, 'hasExtension', [$descriptor4]));
    }
}
