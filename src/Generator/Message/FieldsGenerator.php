<?php

namespace Protobuf\Compiler\Generator\Message;

use Protobuf\Compiler\Entity;
use Protobuf\Compiler\Generator\BaseGenerator;
use Protobuf\Compiler\Generator\GeneratorVisitor;

use google\protobuf\DescriptorProto;
use google\protobuf\FieldDescriptorProto;
use google\protobuf\FieldDescriptorProto\Type;
use google\protobuf\FieldDescriptorProto\Label;

use Zend\Code\Generator\MethodGenerator;
use Zend\Code\Generator\PropertyGenerator;
use Zend\Code\Generator\GeneratorInterface;

/**
 * Message fields generator
 *
 * @author Fabio B. Silva <fabio.bat.silva@gmail.com>
 */
class FieldsGenerator extends BaseGenerator implements GeneratorVisitor
{
    /**
     * {@inheritdoc}
     */
    public function visit(Entity $entity, GeneratorInterface $class)
    {
        $class->addProperties($this->generateFields($entity));
        $class->addMethods($this->generateAccessorsMethods($entity));
    }

    /**
     * @param \Protobuf\Compiler\Entity $entity
     *
     * @return \Zend\Code\Generator\GeneratorInterface[]
     */
    protected function generateAccessorsMethods(Entity $entity)
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

        $methods[] = $this->generateGetExtensionsMethod($entity);
        $methods[] = $this->generateGetUnknownFieldSetMethod($entity);

        return $methods;
    }

    /**
     * @param \Protobuf\Compiler\Entity             $entity
     * @param \google\protobuf\FieldDescriptorProto $field
     *
     * @return \Zend\Code\Generator\GeneratorInterface
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
     * @return \Zend\Code\Generator\GeneratorInterface
     */
    protected function generateSetterMethod(Entity $entity, FieldDescriptorProto $field)
    {
        $body       = [];
        $fieldName  = $field->getName();
        $fieldType  = $field->getType();
        $fieldLabel = $field->getLabel();
        $typeHint   = $this->getTypeHint($field);

        if ($fieldType === Type::TYPE_BYTES() && $fieldLabel !== Label::LABEL_REPEATED()) {
            $body[] = 'if ($value !== null && ! $value instanceof \Protobuf\Stream) {';
            $body[] = '    $value = \Protobuf\Stream::wrap($value);';
            $body[] = '}';
            $body[] = null;

            $typeHint = null;
        }

        $body[] = '$this->' . $fieldName . ' = $value;';

        $methodName = $this->getAccessorName('set', $field);
        $method     = MethodGenerator::fromArray([
            'name'       => $methodName,
            'body'       => implode(PHP_EOL, $body),
            'parameters' => [
                [
                    'name'   => 'value',
                    'type'   => $typeHint
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

        if ($fieldLabel !== Label::LABEL_REQUIRED()) {
            $method->getParameters()['value']->setDefaultValue(null);
        }

        return $method;
    }

    /**
     * @param \Protobuf\Compiler\Entity            $entity
     * @param google\protobuf\FieldDescriptorProto $field
     *
     * @return \Zend\Code\Generator\GeneratorInterface
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
     * @return \Zend\Code\Generator\GeneratorInterface
     */
    protected function generateAddMethod(Entity $entity, FieldDescriptorProto $field)
    {
        $fieldName  = $field->getName();
        $fieldType  = $field->getType();
        $collClass  = $this->getCollectionClassName($field);
        $methodName = 'add' . $this->getClassifiedName($field);
        $typeHint   = ($fieldType !== Type::TYPE_BYTES())
            ? $this->getDoctype($field)
            : null;

        $lines[] = 'if ( $this->' . $fieldName . ' === null) {';
        $lines[] = '    $this->' . $fieldName . ' = new ' . $collClass . '();';
        $lines[] = '}';
        $lines[] = null;
        $lines[] = ($fieldType !== Type::TYPE_BYTES())
            ? '$this->' . $fieldName . '->add($value);'
            : '$this->' . $fieldName . '->add(\Protobuf\Stream::wrap($value));';

        return MethodGenerator::fromArray([
            'name'       => $methodName,
            'body'       => implode(PHP_EOL, $lines),
            'parameters' => [
                [
                    'name'   => 'value',
                    'type'   => $typeHint
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
     * @param \Protobuf\Compiler\Entity $entity
     *
     * @return string
     */
    protected function generateGetUnknownFieldSetMethod(Entity $entity)
    {
        $methodName = 'unknownFieldSet';
        $descriptor = $entity->getDescriptor();
        $fieldName  = $this->getUniqueFieldName($descriptor, $methodName);
        $method     = MethodGenerator::fromArray([
            'name'       => $methodName,
            'body'       => 'return $this->' . $fieldName . ';',
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
    protected function generateGetExtensionsMethod(Entity $entity)
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
                'shortDescription' => "{@inheritdoc}"
            ]
        ]);
    }
}
