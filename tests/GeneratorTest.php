<?php

namespace ProtobufCompilerTest;

use Protobuf\Field;
use Protobuf\Compiler;
use Protobuf\Descriptor;
use Protobuf\Compiler\Options;
use Protobuf\Compiler\Context;
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
    public function descriptorProvider()
    {
        return [
            // simple message
            [
                'Simple.tpl',
                'ProtobufCompilerTest.Protos.Simple',
                [
                    'name'    => 'simple.proto',
                    'package' => 'ProtobufCompilerTest.Protos',
                    'values'  => [
                        'messages' => [
                            [
                                'name'   => 'Simple',
                                'fields' => [
                                    1  => ['double', Field::TYPE_DOUBLE, Field::LABEL_OPTIONAL],
                                    2  => ['float', Field::TYPE_FLOAT, Field::LABEL_OPTIONAL],
                                    3  => ['int64', Field::TYPE_INT64, Field::LABEL_OPTIONAL],
                                    4  => ['uint64', Field::TYPE_UINT64, Field::LABEL_OPTIONAL],
                                    5  => ['int32', Field::TYPE_INT32, Field::LABEL_OPTIONAL],
                                    6  => ['fixed64', Field::TYPE_FIXED64, Field::LABEL_OPTIONAL],
                                    7  => ['fixed32', Field::TYPE_FIXED32, Field::LABEL_OPTIONAL],
                                    8  => ['bool', Field::TYPE_BOOL, Field::LABEL_OPTIONAL],
                                    9  => ['string', Field::TYPE_STRING, Field::LABEL_OPTIONAL],
                                    12 => ['bytes', Field::TYPE_BYTES, Field::LABEL_OPTIONAL],
                                    13 => ['uint32', Field::TYPE_UINT32, Field::LABEL_OPTIONAL],
                                    15 => ['sfixed32', Field::TYPE_SFIXED32, Field::LABEL_OPTIONAL],
                                    16 => ['sfixed64', Field::TYPE_SFIXED64, Field::LABEL_OPTIONAL],
                                    17 => ['sint32', Field::TYPE_SINT32, Field::LABEL_OPTIONAL],
                                    18 => ['sint64', Field::TYPE_SINT64, Field::LABEL_OPTIONAL]
                                ]
                            ]
                        ]
                    ]
                ]
            ],

            // complex with default value
            [
                'PhoneNumber.tpl',
                'ProtobufCompilerTest.Protos.PhoneNumber',
                [
                    'name'    => 'addressbook.proto',
                    'package' => 'ProtobufCompilerTest.Protos',
                    'values'  => [
                        'messages' => [
                            [
                                'name'   => 'PhoneNumber',
                                'fields' => [
                                    1  => ['number', Field::TYPE_STRING, Field::LABEL_REQUIRED],
                                    2  => ['type', Field::TYPE_ENUM, Field::LABEL_OPTIONAL, '.ProtobufTest.Protos.Person.PhoneType', ['default' => 'HOME']],
                                ]
                            ]
                        ]
                    ]
                ]
            ],

            // complex message
            [
                'Person.tpl',
                'ProtobufCompilerTest.Protos.Person',
                [
                    'name'    => 'addressbook.proto',
                    'package' => 'ProtobufCompilerTest.Protos',
                    'values'  => [
                        'messages' => [
                            [
                                'name'   => 'Person',
                                'fields' => [
                                    1  => ['name', Field::TYPE_STRING, Field::LABEL_REQUIRED],
                                    2  => ['id', Field::TYPE_INT32, Field::LABEL_REQUIRED],
                                    3  => ['email', Field::TYPE_STRING, Field::LABEL_OPTIONAL],
                                    4  => ['phone', Field::TYPE_MESSAGE, Field::LABEL_REPEATED, '.ProtobufTest.Protos.Person.PhoneNumber'],
                                ]
                            ]
                        ]
                    ]
                ]
            ],

            // nested enum
            [
                'Person/PhoneType.tpl',
                'ProtobufCompilerTest.Protos.Person.PhoneType',
                [
                    'name'    => 'addressbook.proto',
                    'package' => 'ProtobufCompilerTest.Protos',
                    'values'  => [
                        'messages' => [
                            [
                                'name'   => 'Person',
                                'fields' => [],
                                'values' => [
                                    'enums' => [
                                        [
                                            'name'   => 'PhoneType',
                                            'values' => [
                                                0 => 'MOBILE',
                                                1 => 'HOME',
                                                2 => 'WORK'
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ],

            // extension
            [
                'Extension/Extension.tpl',
                'ProtobufCompilerTest.Protos.Extension.Extension',
                [
                    'name'    => 'extension.proto',
                    'package' => 'ProtobufCompilerTest.Protos.Extension',
                    'values'  => [
                        'extensions' => [
                            200  => ['habitat', Field::TYPE_STRING, Field::LABEL_OPTIONAL, '.ProtobufTest.Protos.Extension.Animal'],
                            201  => ['verbose', Field::TYPE_BOOL, Field::LABEL_OPTIONAL, '.ProtobufTest.Protos.Extension.Command']
                        ],
                        'messages' => [
                            [
                                'name'   => 'Dog',
                                'fields' => [],
                                'values' => [
                                    'extensions' => [
                                        101  => ['animal',  Field::TYPE_MESSAGE, Field::LABEL_OPTIONAL, '.ProtobufTest.Protos.Extension.Animal', '.ProtobufTest.Protos.Extension.Dog']
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ],

            // nested extension
            [
                'Extension/Dog.tpl',
                'ProtobufCompilerTest.Protos.Extension.Dog',
                [
                    'name'    => 'extension.proto',
                    'package' => 'ProtobufCompilerTest.Protos.Extension',
                    'values'  => [
                        'messages' => [
                            [
                                'name'   => 'Dog',
                                'fields' => [
                                    1  => ['bones_buried', Field::TYPE_INT32, Field::LABEL_OPTIONAL, '.ProtobufTest.Protos.Extension.Dog'],
                                ],
                                'values' => [
                                    'extensions' => [
                                        101  => ['animal',  Field::TYPE_MESSAGE, Field::LABEL_OPTIONAL, '.ProtobufTest.Protos.Extension.Animal', '.ProtobufTest.Protos.Extension.Dog']
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];
    }

    /**
     * @dataProvider descriptorProvider
     */
    public function testVisitEntity($fixture, $className, $descriptor)
    {
        $context = $this->createContext([$descriptor]);

        $expected  = $this->getFixtureFileContent($fixture);
        $entity    = $context->getEntity($className);
        $generator = new Generator($context);

        $generator->visit($entity);

         // file_put_contents(__DIR__ . '/Fixtures/'. $fixture, $entity->getContent());

        $this->assertEquals($expected, $entity->getContent());
    }

    public function testService()
    {
        $serviceDesc = new ServiceDescriptorProto();
        $methodDesc  = new MethodDescriptorProto();

        // rpc search (SearchRequest) returns (SearchResponse);

        $methodDesc->setName('search');
        $methodDesc->setInputType('.ProtobufCompilerTest.Protos.Service.SearchRequest');
        $methodDesc->setOutputType('.ProtobufCompilerTest.Protos.Service.SearchResponse');

        $serviceDesc->setName('SearchService');
        $serviceDesc->addMethod($methodDesc);

        $context = $this->createContext([
            [
                'name'    => 'service.proto',
                'package' => 'ProtobufCompilerTest.Protos.Service',
                'values'  => [
                    'services' => [$serviceDesc]
                ]
            ]
        ]);

        $expected  = $this->getFixtureFileContent('Service/SearchService.tpl');
        $className = 'ProtobufCompilerTest.Protos.Service.SearchService';
        $entity    = $context->getEntity($className);
        $generator = new Generator($context);

        $generator->visit($entity);

        // file_put_contents(__DIR__ . '/Fixtures/Service/SearchService.tpl', $entity->getContent());

        $this->assertEquals($expected, $entity->getContent());
    }

    public function testGetPsr4ClassPath()
    {
        $className = '\\ProtobufCompilerTest\\Protos\\Foo';
        $options   = [
            'psr4' => [
                'Protos',
                'ProtobufCompilerTest'
            ]
        ];

        $context   = $this->createContext([], $options);
        $generator = new Generator($context);

        $expected   = 'Protos/Foo.php';
        $actual     = $this->invokeMethod($generator, 'getPsr4ClassPath', [$className]);

        $this->assertEquals($expected, $actual);
    }
}
