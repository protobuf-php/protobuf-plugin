<?php

namespace Protobuf\Compiler\Generator\Message;

use google\protobuf\DescriptorProto;
use google\protobuf\FieldDescriptorProto;
use google\protobuf\FieldDescriptorProto\Label;

use Zend\Code\Generator\MethodGenerator;
use Zend\Code\Generator\GeneratorInterface;

use Protobuf\Compiler\Entity;
use Protobuf\Compiler\Generator\BaseGenerator;
use Protobuf\Compiler\Generator\GeneratorVisitor;

/**
 * Message fromArray method generator
 *
 * @author Fabio B. Silva <fabio.bat.silva@gmail.com>
 */
class FromArrayGenerator extends BaseGenerator implements GeneratorVisitor
{
    /**
     * {@inheritdoc}
     */
    public function visit(Entity $entity, GeneratorInterface $class)
    {
        $class->addMethodFromGenerator($this->generateMethod($entity));
    }

    /**
     * @param \Protobuf\Compiler\Entity $entity
     *
     * @return string
     */
    protected function generateMethod(Entity $entity)
    {
        $lines   = $this->generateBody($entity);
        $body    = implode(PHP_EOL, $lines);
        $method  = MethodGenerator::fromArray([
            'name'       => 'fromArray',
            'body'       => $body,
            'static'     => true,
            'parameters' => [
                [
                    'name'          => 'values',
                    'type'          => 'array',
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
     * @return string[]
     */
    public function generateBody(Entity $entity)
    {
        $lines      = [];
        $addLine    = false;
        $descriptor = $entity->getDescriptor();
        $fields     = $descriptor->getFieldList() ?: [];
        $defaults   = $this->generateDefaultValues($entity);

        foreach ($fields as $field) {
            if ($field->getLabel() !== Label::LABEL_REQUIRED()) {
                continue;
            }

            if ($field->hasDefaultValue()) {
                continue;
            }

            $item  = $this->generateRequiredFieldException($entity, $field);
            $lines = array_merge($lines, $item, [null]);
        }

        $lines[] = '$message = new self();';
        $lines[] = '$values  = array_merge([';
        $lines   = array_merge($lines, $this->addIndentation($defaults, 1));
        $lines[] = '], $values);';
        $lines[] = null;

        foreach ($fields as $field) {
            if ($field->getLabel() === Label::LABEL_REPEATED()) {
                continue;
            }

            $item  = $this->generateSetValue($entity, $field);
            $lines = array_merge($lines, $item);

            $addLine = true;
        }

        if ($addLine) {
            $lines[] = null;
        }

        foreach ($fields as $field) {
            if ($field->getLabel() !== Label::LABEL_REPEATED()) {
                continue;
            }

            $item  = $this->generateAddValue($entity, $field);
            $lines = array_merge($lines, $item, [null]);
        }

        $lines[] = 'return $message;';

        return $lines;
    }

    /**
     * @param \Protobuf\Compiler\Entity             $entity
     * @param \google\protobuf\FieldDescriptorProto $field
     *
     * @return string[]
     */
    protected function generateRequiredFieldException(Entity $entity, FieldDescriptorProto $field)
    {
        $name = $field->getName();
        $tag  = $field->getNumber();

        $class   = $entity->getNamespacedName();
        $format  = 'Field "%s" (tag %s) is required but has no value.';
        $message = var_export(sprintf($format, $name, $tag), true);

        $body[] = 'if ( ! isset($values[\'' . $name . '\'])) {';
        $body[] = '    throw new \InvalidArgumentException(' . $message . ');';
        $body[] = '}';

        return $body;
    }

    /**
     * @param \Protobuf\Compiler\Entity             $entity
     * @param \google\protobuf\FieldDescriptorProto $field
     *
     * @return array
     */
    protected function generateSetValue(Entity $entity, FieldDescriptorProto $field)
    {
        $lines      = [];
        $fieldName  = $field->getName();
        $methodName = $this->getAccessorName('set', $field);

        $lines[] = '$message->' . $methodName . '($values[\'' . $fieldName . '\']);';

        return $lines;
    }

    /**
     * @param \Protobuf\Compiler\Entity             $entity
     * @param \google\protobuf\FieldDescriptorProto $field
     *
     * @return array
     */
    protected function generateAddValue(Entity $entity, FieldDescriptorProto $field)
    {
        $lines      = [];
        $fieldName  = $field->getName();
        $methodName = 'add' . $this->getClassifiedName($field);

        $lines[] = 'foreach ($values[\'' . $fieldName . '\'] as $item) {';
        $lines[] = '    $message->' . $methodName . '($item);';
        $lines[] = '}';

        return $lines;
    }

    /**
     * @param \Protobuf\Compiler\Entity $entity
     *
     * @return array
     */
    protected function generateDefaultValues(Entity $entity)
    {
        $descriptor = $entity->getDescriptor();
        $fields     = $descriptor->getFieldList() ?: [];
        $size       = count($fields);
        $lines      = [];

        foreach ($fields as $i => $field) {
            $name  = $field->getName();
            $comma = ($i +1 < $size) ? ',' : '';
            $value = $this->getDefaultFieldValue($field);

            // required field throw InvalidArgumentException
            if ($field->getLabel() === Label::LABEL_REQUIRED()) {
                continue;
            }

            if ($field->getLabel() === Label::LABEL_REPEATED()) {
                $value = '[]';
            }

            $lines[] = "'$name' => $value" . $comma;
        }

        return $lines;
    }
}
