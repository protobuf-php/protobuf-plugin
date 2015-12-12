<?php

namespace Protobuf\Compiler\Generator;

use google\protobuf\FieldDescriptorProto;

use Zend\Code\Generator\ClassGenerator;
use Zend\Code\Generator\MethodGenerator;
use Zend\Code\Generator\DocBlockGenerator;
use Zend\Code\Generator\PropertyGenerator;
use Zend\Code\Generator\ParameterGenerator;

use Protobuf\Compiler\Entity;
use Protobuf\Compiler\Generator\Message\ExtensionsGenerator;
use Protobuf\Compiler\Generator\Message\ReadFieldStatementGenerator;
use Protobuf\Compiler\Generator\Message\ExtensionMethodBodyGenerator;
use Protobuf\Compiler\Generator\Message\WriteFieldStatementGenerator;
use Protobuf\Compiler\Generator\Message\SerializedSizeFieldStatementGenerator;

/**
 * Extension Generator
 *
 * @author Fabio B. Silva <fabio.bat.silva@gmail.com>
 */
class ExtensionGenerator extends BaseGenerator implements EntityVisitor
{
    /**
     * {@inheritdoc}
     */
    public function visit(Entity $entity)
    {
        $name             = $entity->getName();
        $namespace        = $entity->getNamespace();
        $shortDescription = 'Protobuf extension : ' . $entity->getClass();
        $class            = ClassGenerator::fromArray([
            'name'                  => $name,
            'namespacename'         => $namespace,
            'implementedinterfaces' => ['\Protobuf\Extension'],
            'properties'            => $this->generateFields($entity),
            'methods'               => $this->generateMethods($entity),
            'docblock'              => [
                'shortDescription' => $shortDescription
            ]
        ]);

        $entity->setContent($this->generateFileContent($class, $entity));
    }

    /**
     * @param \Protobuf\Compiler\Entity $entity
     *
     * @return string[]
     */
    protected function generateFields(Entity $entity)
    {
        $fields     = [];
        $descriptor = $entity->getDescriptor();
        $extensions = $descriptor->getExtensionList() ?: [];

        foreach ($extensions as $field) {
            $fields[] = $this->generateExtensionField($entity, $field);
        }

        return $fields;
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
        $descriptor = $entity->getDescriptor();
        $extensions = $descriptor->getExtensionList() ?: [];
        $methods    = [$this->generateRegisterAllExtensionsMethod($entity)];

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
        $descriptor = $entity->getDescriptor();
        $methodName = $this->getCamelizedName($field);
        $bodyGen    = new ExtensionsGenerator($this->context);
        $body       = implode(PHP_EOL, $bodyGen->generateBody($entity, $field));
        $method     = MethodGenerator::fromArray([
            'static'     => true,
            'body'       => $body,
            'name'       => $methodName,
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
    protected function generateRegisterAllExtensionsMethod(Entity $entity)
    {
        $lines      = [];
        $fields     = [];
        $descriptor = $entity->getDescriptor();
        $extensions = $descriptor->getExtensionList() ?: [];
        $messages   = $descriptor->getMessageTypeList() ?: [];

        foreach ($messages as $message) {

            if ( ! $message->hasExtensionList()) {
                continue;
            }

            foreach ($message->getExtensionList() as $extension) {
                $fields[] = $extension;
            }
        }

        foreach ($extensions as $field) {
            $fields[] = $field;
        }

        foreach ($fields as $field) {
            $type  = $field->getTypeName();
            $name  = $this->getCamelizedName($field);

            if ( ! $type) {
                $lines[] = '$registry->add(self::' . $name . '());';

                continue;
            }

            $ref     = $this->getEntity($type);
            $class   = $ref->getNamespacedName();
            $lines[] = '$registry->add(' . $class . '::' . $name. '());';
        }

        $body       = implode(PHP_EOL, $lines);
        $method     = MethodGenerator::fromArray([
            'static'     => true,
            'body'       => $body,
            'name'       => 'registerAllExtensions',
            'parameters' => [
                [
                    'name' => 'registry',
                    'type' => '\Protobuf\Extension\ExtensionRegistry'
                ]
            ],
            'docblock'   => [
                'shortDescription' => "Register all extensions",
                'tags'             => [
                    [
                        'name'        => 'param',
                        'description' => '\Protobuf\Extension\ExtensionRegistry',
                    ]
                ]
            ]
        ]);

        return $method;
    }
}
