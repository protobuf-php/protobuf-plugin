<?php

namespace Protobuf\Compiler\Generator\Message;

use google\protobuf\FieldDescriptorProto;

use Protobuf\Compiler\Entity;
use Protobuf\Compiler\Generator\BaseGenerator;
use Protobuf\Compiler\Generator\GeneratorVisitor;

use Zend\Code\Generator\MethodGenerator;
use Zend\Code\Generator\PropertyGenerator;
use Zend\Code\Generator\GeneratorInterface;

/**
 * Message extension generator
 *
 * @author Fabio B. Silva <fabio.bat.silva@gmail.com>
 */
class ExtensionsGenerator extends BaseGenerator implements GeneratorVisitor
{
    /**
     * {@inheritdoc}
     */
    public function visit(Entity $entity, GeneratorInterface $class)
    {
        $class->addProperties($this->generateExtensionFields($entity));
        $class->addMethods($this->generateExtensionMethods($entity));

        if ($entity->getDescriptor()->hasExtensionList()) {
            $class->setImplementedInterfaces(['\Protobuf\Extension']);
        }
    }

    /**
     * @param \Protobuf\Compiler\Entity             $entity
     * @param \google\protobuf\FieldDescriptorProto $field
     *
     * @return string[]
     */
    public function generateBody(Entity $entity, FieldDescriptorProto $field)
    {
        $name      = $field->getName();
        $tag       = $field->getNumber();
        $extEntity = $this->getEntity($field->getExtendee());
        $extendee  = $extEntity->getNamespacedName();

        $nameQuoted     = var_export($name, true);
        $extendeeQuoted = var_export($extendee, true);

        $sizeCallback  = $this->generateSizeCallback($entity, $field);
        $readCallback  = $this->generateReadCallback($entity, $field);
        $writeCallback = $this->generateWriteCallback($entity, $field);
        $callbacks     = array_merge($readCallback, [null], $writeCallback, [null], $sizeCallback);
        $arguments     = [
            $extendeeQuoted,
            $nameQuoted,
            $tag,
            '$readCallback',
            '$writeCallback',
            '$sizeCallback'
        ];

        $body[] = 'if (self::$' . $name . ' !== null) {';
        $body[] = '    return self::$' . $name . ';';
        $body[] = '}';
        $body[] = null;
        $body   = array_merge($body, $callbacks);
        $body[] = null;
        $body[] = 'return self::$' . $name . ' = new \Protobuf\Extension\ExtensionField(' . implode(', ', $arguments) . ');';

        return $body;
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
     * @param \Protobuf\Compiler\Entity $entity
     *
     * @return string[]
     */
    protected function generateExtensionFields(Entity $entity)
    {
        $properties = [];
        $descriptor = $entity->getDescriptor();
        $extensions = $descriptor->getExtensionList() ?: [];

        foreach ($extensions as $field) {
            $properties[] = $this->generateExtensionField($entity, $field);
        }

        return $properties;
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
        $methodName = $this->getCamelizedName($field);
        $lines      = $this->generateBody($entity, $field);
        $body       = implode(PHP_EOL, $lines);
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
     * @param \Protobuf\Compiler\Entity             $entity
     * @param \google\protobuf\FieldDescriptorProto $field
     *
     * @return string[]
     */
    protected function generateReadCallback(Entity $entity, FieldDescriptorProto $field)
    {
        $body  = [];
        $sttm  = $this->generateFieldReadStatement($entity, $field);
        $lines = $this->addIndentation($sttm, 1);

        $body[] = '$readCallback = function (\Protobuf\ReadContext $context, $wire) {';
        $body[] = '    $reader = $context->getReader();';
        $body[] = '    $length = $context->getLength();';
        $body[] = '    $stream = $context->getStream();';
        $body[] = null;
        $body   = array_merge($body, $lines);
        $body[] = '};';

        return $body;
    }

    /**
     * @param \Protobuf\Compiler\Entity             $entity
     * @param \google\protobuf\FieldDescriptorProto $field
     *
     * @return string[]
     */
    protected function generateWriteCallback(Entity $entity, FieldDescriptorProto $field)
    {
        $body  = [];
        $sttm  = $this->generateFieldWriteStatement($entity, $field);
        $lines = $this->addIndentation($sttm, 1);

        $body[] = '$writeCallback = function (\Protobuf\WriteContext $context, $value) {';
        $body[] = '    $stream      = $context->getStream();';
        $body[] = '    $writer      = $context->getWriter();';
        $body[] = '    $sizeContext = $context->getComputeSizeContext();';
        $body[] = null;
        $body   = array_merge($body, $lines);
        $body[] = '};';

        return $body;
    }

    /**
     * @param \Protobuf\Compiler\Entity             $entity
     * @param \google\protobuf\FieldDescriptorProto $field
     *
     * @return string[]
     */
    protected function generateSizeCallback(Entity $entity, FieldDescriptorProto $field)
    {
        $body  = [];
        $sttm  = $this->generateFieldSizeStatement($entity, $field);
        $lines = $this->addIndentation($sttm, 1);

        $body[] = '$sizeCallback = function (\Protobuf\ComputeSizeContext $context, $value) {';
        $body[] = '    $calculator = $context->getSizeCalculator();';
        $body[] = '    $size       = 0;';
        $body[] = null;
        $body   = array_merge($body, $lines);
        $body[] = null;
        $body[] = '    return $size;';
        $body[] = '};';

        return $body;
    }

    /**
     * @param \Protobuf\Compiler\Entity             $entity
     * @param \google\protobuf\FieldDescriptorProto $field
     *
     * @return string[]
     */
    protected function generateFieldReadStatement(Entity $entity, FieldDescriptorProto $field)
    {
        $generator = new ReadFieldStatementGenerator($this->context);

        $generator->setBreakMode(ReadFieldStatementGenerator::BREAK_MODE_RETURN);
        $generator->setTargetVar('$value');

        return $generator->generateFieldReadStatement($entity, $field);
    }

    /**
     * @param \Protobuf\Compiler\Entity             $entity
     * @param \google\protobuf\FieldDescriptorProto $field
     *
     * @return string[]
     */
    protected function generateFieldWriteStatement(Entity $entity, FieldDescriptorProto $field)
    {
        $generator = new WriteFieldStatementGenerator($this->context);

        $generator->setTargetVar('$value');

        return $generator->generateFieldWriteStatement($entity, $field);
    }

    /**
     * @param \Protobuf\Compiler\Entity             $entity
     * @param \google\protobuf\FieldDescriptorProto $field
     *
     * @return string[]
     */
    protected function generateFieldSizeStatement(Entity $entity, FieldDescriptorProto $field)
    {
        $generator = new SerializedSizeFieldStatementGenerator($this->context);

        $generator->setTargetVar('$value');

        return $generator->generateFieldSizeStatement($entity, $field);
    }
}
