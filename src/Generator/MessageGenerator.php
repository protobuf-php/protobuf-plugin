<?php

namespace Protobuf\Compiler\Generator;

use Protobuf\WireFormat;
use Protobuf\Compiler\Options;

use google\protobuf\DescriptorProto;
use google\protobuf\FieldDescriptorProto;
use google\protobuf\FieldDescriptorProto\Type;
use google\protobuf\FieldDescriptorProto\Label;

use Zend\Code\Generator\ClassGenerator;
use Zend\Code\Generator\MethodGenerator;
use Zend\Code\Generator\DocBlockGenerator;
use Zend\Code\Generator\PropertyGenerator;
use Zend\Code\Generator\ParameterGenerator;

use Protobuf\Compiler\Entity;
use Protobuf\Compiler\Generator\Message\AnnotationGenerator;
use Protobuf\Compiler\Generator\Message\ExtensionMethodBodyGenerator;
use Protobuf\Compiler\Generator\Message\WriteToMethodBodyGenerator;
use Protobuf\Compiler\Generator\Message\ReadFromMethodBodyGenerator;
use Protobuf\Compiler\Generator\Message\ToStreamMethodBodyGenerator;
use Protobuf\Compiler\Generator\Message\ConstructMethodBodyGenerator;
use Protobuf\Compiler\Generator\Message\FromStreamMethodBodyGenerator;
use Protobuf\Compiler\Generator\Message\SerializedSizeMethodBodyGenerator;

/**
 * Message Generator
 *
 * @author Fabio B. Silva <fabio.bat.silva@gmail.com>
 */
class MessageGenerator extends BaseGenerator
{
    /**
     * @param \Protobuf\Compiler\Entity $entity
     */
    public function visit(Entity $entity)
    {
        $name             = $entity->getName();
        $namespace        = $entity->getNamespace();
        $descriptor       = $entity->getDescriptor();
        $longDescription  = $this->generateMessageAnnotation($entity);
        $shortDescription = 'Protobuf message : ' . $entity->getClass();
        $class            = ClassGenerator::fromArray([
            'name'          => $name,
            'namespacename' => $namespace,
            'extendedClass' => '\Protobuf\AbstractMessage',
            'properties'    => $this->generateFields($entity),
            'methods'       => $this->generateMethods($entity),
            'docblock'      => [
                'shortDescription' => $shortDescription,
                'longDescription'  => $longDescription
            ]
        ]);

        if ($descriptor->hasExtensionList()) {
            $class->setImplementedInterfaces(['\Protobuf\Extension']);
        }

        $entity->setContent($this->generateFileContent($class, $entity));
    }

    /**
     * @param \Protobuf\Compiler\Entity $entity
     *
     * @return string
     */
    protected function generateMessageAnnotation(Entity $entity)
    {
        $generator  = new AnnotationGenerator($this->context);
        $annotation = implode(PHP_EOL, $generator->generateAnnotation($entity));

        return $annotation;
    }

    /**
     * @param \Protobuf\Compiler\Entity $entity
     *
     * @return string[]
     */
    protected function generateFields(Entity $entity)
    {
        $properties = [];
        $descriptor = $entity->getDescriptor();
        $fields     = $descriptor->getFieldList() ?: [];
        $extensions = $descriptor->getExtensionList() ?: [];

        $properties[] = PropertyGenerator::fromArray([
            'name'       => $this->getUniqueFieldName($descriptor, 'unknownFieldSet'),
            'visibility' => PropertyGenerator::VISIBILITY_PROTECTED,
            'docblock'   => [
                'tags'   => [
                    [
                        'name'        => 'var',
                        'description' => '\Protobuf\UnknownFieldSet',
                    ]
                ]
            ]
        ]);

        $properties[] = PropertyGenerator::fromArray([
            'name'       => $this->getUniqueFieldName($descriptor, 'extensions'),
            'visibility' => PropertyGenerator::VISIBILITY_PROTECTED,
            'docblock'   => [
                'tags'   => [
                    [
                        'name'        => 'var',
                        'description' => '\Protobuf\Extension\ExtensionFieldMap',
                    ]
                ]
            ]
        ]);

        foreach ($extensions as $field) {
            $properties[] = $this->generateExtensionField($entity, $field);
        }

        foreach ($fields as $field) {
            $properties[] = $this->generateField($entity, $field);
        }

        return $properties;
    }

    /**
     * @param \Protobuf\Compiler\Entity             $entity
     * @param \google\protobuf\FieldDescriptorProto $field
     *
     * @return string
     */
    protected function generateField(Entity $entity, FieldDescriptorProto $field)
    {
        $name     = $field->getName();
        $number   = $field->getNumber();
        $docBlock = $this->getDocBlockType($field);
        $type     = $this->getFieldTypeName($field);
        $label    = $this->getFieldLabelName($field);
        $property = PropertyGenerator::fromArray([
            'name'         => $name,
            'visibility'   => PropertyGenerator::VISIBILITY_PROTECTED,
            'docblock'     => [
                'shortDescription' => "$name $label $type = $number",
                'tags'             => [
                    [
                        'name'        => 'var',
                        'description' => $docBlock,
                    ]
                ]
            ]
        ]);

        $property->getDocblock()->setWordWrap(false);

        return $property;
    }

    /**
     * @param \Protobuf\Compiler\Entity             $entity
     * @param \google\protobuf\FieldDescriptorProto $field
     *
     * @return string
     */
    protected function generateExtensionField(Entity $entity, FieldDescriptorProto $field)
    {
        $name     = $field->getName();
        $number   = $field->getNumber();
        $docBlock = $this->getDocBlockType($field);
        $type     = $this->getFieldTypeName($field);
        $label    = $this->getFieldLabelName($field);
        $property = PropertyGenerator::fromArray([
            'static'       => true,
            'name'         => $name,
            'visibility'   => PropertyGenerator::VISIBILITY_PROTECTED,
            'docblock'     => [
                'shortDescription' => "Extension field : $name $label $type = $number",
                'tags'             => [
                    [
                        'name'        => 'var',
                        'description' => '\Protobuf\Extension',
                    ]
                ]
            ]
        ]);

        $property->getDocblock()->setWordWrap(false);

        return $property;
    }

    /**
     * @param \Protobuf\Compiler\Entity $entity
     *
     * @return string[]
     */
    protected function generateMethods(Entity $entity)
    {
        $extensions  = $this->generateExtensionMethods($entity);
        $constructor = $this->generateConstructorMethod($entity);
        $accessors   = $this->generateGetterAndSetterMethods($entity);
        $methods     = [];

        if ($constructor) {
            $methods[] = $constructor;
        }

        $methods = array_merge($methods, $extensions, $accessors);

        $methods[] = $this->generateUnknownFieldSetMethod($entity);
        $methods[] = $this->generateExtensionsMethod($entity);
        $methods[] = $this->generateSerializedSizeMethod($entity);
        $methods[] = $this->generateToStreamMethod($entity);
        $methods[] = $this->generateReadFromMethod($entity);
        $methods[] = $this->generateWriteToMethod($entity);
        $methods[] = $this->generateFromStreamMethod($entity);

        return $methods;
    }

    /**
     * @param \Protobuf\Compiler\Entity $entity
     *
     * @return string
     */
    protected function generateGetterAndSetterMethods(Entity $entity)
    {
        $methods    = [];
        $descriptor = $entity->getDescriptor();
        $fields     = $descriptor->getFieldList() ?: [];

        foreach ($fields as $field) {
            $methods[] = $this->generateHasMethod($entity, $field);
            $methods[] = $this->generateGetterMethod($entity, $field);
            $methods[] = $this->generateSetterMethod($entity, $field);

            if ($field->getLabel() === Label::LABEL_REPEATED()) {
                $methods[] = $this->generateAddMethod($entity, $field);
            }
        }

        return $methods;
    }

    /**
     * @param \Protobuf\Compiler\Entity $entity
     *
     * @return string
     */
    protected function generateExtensionMethods(Entity $entity)
    {
        $methods    = [];
        $descriptor = $entity->getDescriptor();
        $extensions = $descriptor->getExtensionList() ?: [];

        foreach ($extensions as $field) {
            $methods[] = $this->generateExtensionMethod($entity, $field);
        }

        return $methods;
    }

    /**
     * @param \Protobuf\Compiler\Entity             $entity
     * @param \google\protobuf\FieldDescriptorProto $field
     *
     * @return string
     */
    protected function generateExtensionMethod(Entity $entity, FieldDescriptorProto $field)
    {
        $fieldName  = $field->getName();
        $bodyGen    = new ExtensionMethodBodyGenerator($this->context);
        $body       = implode(PHP_EOL, $bodyGen->generateBody($entity, $field));
        $method     = MethodGenerator::fromArray([
            'static'     => true,
            'body'       => $body,
            'name'       => $fieldName,
            'docblock'   => [
                'shortDescription' => "Extension field : $fieldName",
                'tags'             => [
                    [
                        'name'        => 'return',
                        'description' => '\Protobuf\Extension',
                    ]
                ]
            ]
        ]);

        $method->getDocblock()->setWordWrap(false);

        return $method;
    }

    /**
     * @param \Protobuf\Compiler\Entity $entity
     *
     * @return string
     */
    protected function generateUnknownFieldSetMethod(Entity $entity)
    {
        $methodName = 'unknownFieldSet';
        $descriptor = $entity->getDescriptor();
        $fieldName  = $this->getUniqueFieldName($descriptor, $methodName);
        $method     = MethodGenerator::fromArray([
            'name'       => $methodName,
            'body'       => 'return $this->' . $fieldName . ';',
            'docblock'   => [
                'shortDescription' => "Get unknown values",
                'tags'             => [
                    [
                        'name'        => 'return',
                        'description' => '\Protobuf\UnknownFieldSet',
                    ]
                ]
            ]
        ]);

        return $method;
    }

    /**
     * @param \Protobuf\Compiler\Entity $entity
     *
     * @return string
     */
    protected function generateExtensionsMethod(Entity $entity)
    {
        $lines      = [];
        $descriptor = $entity->getDescriptor();
        $fieldName  = $this->getUniqueFieldName($descriptor, 'extensions');

        $lines[] = 'if ( $this->' . $fieldName . ' !== null) {';
        $lines[] = '    return $this->' . $fieldName . ';';
        $lines[] = '}';
        $lines[] = null;
        $lines[] = 'return $this->' . $fieldName . ' = new \Protobuf\Extension\ExtensionFieldMap(__CLASS__);';

        return MethodGenerator::fromArray([
            'name'       => 'extensions',
            'body'       => implode(PHP_EOL, $lines),
            'docblock'   => [
                'shortDescription' => "{@inheritdoc}",
                'tags'             => [
                    [
                        'name'        => 'return',
                        'description' => '\Protobuf\Extension\ExtensionFieldMap',
                    ]
                ]
            ]
        ]);
    }

    /**
     * @param \Protobuf\Compiler\Entity             $entity
     * @param \google\protobuf\FieldDescriptorProto $field
     *
     * @return string
     */
    protected function generateGetterMethod(Entity $entity, FieldDescriptorProto $field)
    {
        $fieldName  = $field->getName();
        $methodName = $this->getAccessorName('get', $field);
        $method     = MethodGenerator::fromArray([
            'name'       => $methodName,
            'body'       => 'return $this->' . $fieldName . ';',
            'docblock'   => [
                'shortDescription' => "Get '$fieldName' value",
                'tags'             => [
                    [
                        'name'        => 'return',
                        'description' => $this->getDocBlockType($field),
                    ]
                ]
            ]
        ]);

        $method->getDocblock()->setWordWrap(false);

        return $method;
    }

    /**
     * @param \Protobuf\Compiler\Entity            $entity
     * @param google\protobuf\FieldDescriptorProto $field
     *
     * @return string
     */
    protected function generateSetterMethod(Entity $entity, FieldDescriptorProto $field)
    {
        $fieldName  = $field->getName();
        $methodName = $this->getAccessorName('set', $field);
        $method     = MethodGenerator::fromArray([
            'name'       => $methodName,
            'body'       => 'return $this->' . $fieldName . ' = $value;',
            'parameters' => [
                [
                    'name'   => 'value',
                    'type'   => $this->getTypeHint($field)
                ]
            ],
            'docblock'   => [
                'shortDescription' => "Set '$fieldName' value",
                'tags'             => [
                    [
                        'name'        => 'param',
                        'description' => $this->getDocBlockType($field) . ' $value'
                    ]
                ]
            ]
        ]);

        $method->getDocblock()->setWordWrap(false);

        return $method;
    }

    /**
     * @param \Protobuf\Compiler\Entity            $entity
     * @param google\protobuf\FieldDescriptorProto $field
     *
     * @return string
     */
    protected function generateHasMethod(Entity $entity, FieldDescriptorProto $field)
    {
        $fieldName  = $field->getName();
        $methodName = $this->getAccessorName('has', $field);
        $method     = MethodGenerator::fromArray([
            'name'       => $methodName,
            'body'       => 'return $this->' . $fieldName . ' !== null;',
            'docblock'   => [
                'shortDescription' => "Check if '$fieldName' has a value",
                'tags'             => [
                    [
                        'name'        => 'return',
                        'description' => 'bool',
                    ]
                ]
            ]
        ]);

        return $method;
    }

    /**
     * @param \Protobuf\Compiler\Entity            $entity
     * @param google\protobuf\FieldDescriptorProto $field
     *
     * @return string
     */
    protected function generateAddMethod(Entity $entity, FieldDescriptorProto $field)
    {
        $fieldName  = $field->getName();
        $methodName = 'add' . $this->getClassifiedName($field);
        $method     = MethodGenerator::fromArray([
            'name'       => $methodName,
            'body'       => '$this->' . $fieldName . '[] = $value;',
            'parameters' => [
                [
                    'name'   => 'value',
                    'type'   => $this->getDoctype($field)
                ]
            ],
            'docblock'   => [
                'shortDescription' => "Add a new element to '$fieldName'",
                'tags'             => [
                    [
                        'name'        => 'param',
                        'description' => $this->getDoctype($field) . ' $value'
                    ]
                ]
            ]
        ]);

        return $method;
    }

    /**
     * @param \Protobuf\Compiler\Entity $entity
     *
     * @return string
     */
    protected function generateWriteToMethod(Entity $entity)
    {
        $bodyGen = new WriteToMethodBodyGenerator($this->context);
        $body    = implode(PHP_EOL, $bodyGen->generateBody($entity));
        $method  = MethodGenerator::fromArray([
            'name'       => 'writeTo',
            'body'       => $body,
            'parameters' => [
                [
                    'name' => 'context',
                    'type' => '\Protobuf\WriteContext',
                ]
            ],
            'docblock'   => [
                'shortDescription' => "{@inheritdoc}"
            ]
        ]);

        return $method;
    }

    /**
     * @param \Protobuf\Compiler\Entity $entity
     *
     * @return string
     */
    protected function generateSerializedSizeMethod(Entity $entity)
    {
        $bodyGen = new SerializedSizeMethodBodyGenerator($this->context);
        $body    = implode(PHP_EOL, $bodyGen->generateBody($entity));
        $method  = MethodGenerator::fromArray([
            'name'       => 'serializedSize',
            'body'       => $body,
            'parameters' => [
                [
                    'name'         => 'context',
                    'type'         => '\Protobuf\ComputeSizeContext'
                ]
            ],
            'docblock'   => [
                'shortDescription' => "{@inheritdoc}"
            ]
        ]);

        return $method;
    }

    /**
     * @param \Protobuf\Compiler\Entity $entity
     *
     * @return string
     */
    protected function generateConstructorMethod(Entity $entity)
    {
        $bodyGen = new ConstructMethodBodyGenerator($this->context);
        $body    = implode(PHP_EOL, $bodyGen->generateBody($entity));
        $method  = MethodGenerator::fromArray([
            'name'       => '__construct',
            'body'       => $body,
            'docblock'   => [
                'shortDescription' => "Constructor"
            ]
        ]);

        if (trim($body) == '') {
            return null;
        }

        return $method;
    }

    /**
     * @param \Protobuf\Compiler\Entity $entity
     *
     * @return string
     */
    protected function generateReadFromMethod(Entity $entity)
    {
        $bodyGen = new ReadFromMethodBodyGenerator($this->context);
        $body    = implode(PHP_EOL, $bodyGen->generateBody($entity));
        $method  = MethodGenerator::fromArray([
            'name'       => 'readFrom',
            'body'       => $body,
            'parameters' => [
                [
                    'name'          => 'context',
                    'type'          => '\Protobuf\ReadContext',
                ]
            ],
            'docblock'   => [
                'shortDescription' => "{@inheritdoc}"
            ]
        ]);

        return $method;
    }

    /**
     * @param \Protobuf\Compiler\Entity $entity
     *
     * @return string
     */
    protected function generateFromStreamMethod(Entity $entity)
    {
        $bodyGen = new FromStreamMethodBodyGenerator($this->context);
        $body    = implode(PHP_EOL, $bodyGen->generateBody($entity));
        $method  = MethodGenerator::fromArray([
            'name'       => 'fromStream',
            'body'       => $body,
            'static'     => true,
            'parameters' => [
                [
                    'name'          => 'stream',
                    'type'          => 'mixed',
                ],
                [
                    'name'          => 'configuration',
                    'type'          => '\Protobuf\Configuration',
                    'defaultValue'  => null
                ]
            ],
            'docblock'   => [
                'shortDescription' => "{@inheritdoc}"
            ]
        ]);

        return $method;
    }

    /**
     * @param \Protobuf\Compiler\Entity $entity
     *
     * @return string
     */
    protected function generateToStreamMethod(Entity $entity)
    {
        $bodyGen = new ToStreamMethodBodyGenerator($this->context);
        $body    = implode(PHP_EOL, $bodyGen->generateBody($entity));
        $method  = MethodGenerator::fromArray([
            'name'       => 'toStream',
            'body'       => $body,
            'parameters' => [
                [
                    'name'          => 'configuration',
                    'type'          => '\Protobuf\Configuration',
                    'defaultValue'  => null
                ]
            ],
            'docblock'   => [
                'shortDescription' => "{@inheritdoc}"
            ]
        ]);

        return $method;
    }
}
