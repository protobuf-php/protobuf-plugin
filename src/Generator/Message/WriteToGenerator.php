<?php

namespace Protobuf\Compiler\Generator\Message;

use google\protobuf\DescriptorProto;
use google\protobuf\FieldDescriptorProto;
use google\protobuf\FieldDescriptorProto\Type;
use google\protobuf\FieldDescriptorProto\Label;

use Zend\Code\Generator\MethodGenerator;
use Zend\Code\Generator\GeneratorInterface;

use Protobuf\WireFormat;
use Protobuf\Compiler\Entity;
use Protobuf\Compiler\Generator\BaseGenerator;
use Protobuf\Compiler\Generator\GeneratorVisitor;

/**
 * Message writeTo Body Generator
 *
 * @author Fabio B. Silva <fabio.bat.silva@gmail.com>
 */
class WriteToGenerator extends BaseGenerator implements GeneratorVisitor
{
    /**
     * {@inheritdoc}
     */
    public function visit(Entity $entity, GeneratorInterface $class)
    {
        $class->addMethodFromGenerator($this->generateWriteToMethod($entity));
    }

    /**
     * @param \Protobuf\Compiler\Entity $entity
     *
     * @return \Zend\Code\Generator\GeneratorInterface
     */
    protected function generateWriteToMethod(Entity $entity)
    {
        $lines   = $this->generateBody($entity);
        $body    = implode(PHP_EOL, $lines);
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
     * @return string[]
     */
    protected function generateBody(Entity $entity)
    {
        $descriptor = $entity->getDescriptor();
        $fields     = $descriptor->getFieldList() ?: [];

        $body[] = '$stream      = $context->getStream();';
        $body[] = '$writer      = $context->getWriter();';
        $body[] = '$sizeContext = $context->getComputeSizeContext();';
        $body[] = null;

        foreach ($fields as $field) {
            $lines = $this->generateRequiredFieldException($entity, $field);
            $body  = array_merge($body, $lines);
        }

        foreach ($fields as $field) {
            $lines = $this->generateFieldCondition($entity, $field);
            $body  = array_merge($body, $lines, [null]);
        }

        $extensionsField = $this->getUniqueFieldName($descriptor, 'extensions');
        $extensionsVar   = '$this->' . $extensionsField;

        $body[] = 'if (' . $extensionsVar . ' !== null) {';
        $body[] = '    ' . $extensionsVar . '->writeTo($context);';
        $body[] = '}';
        $body[] = null;
        $body[] = 'return $stream;';

        return $body;
    }

    /**
     * @param \Protobuf\Compiler\Entity             $entity
     * @param \google\protobuf\FieldDescriptorProto $field
     *
     * @return string[]
     */
    protected function generateRequiredFieldException(Entity $entity, FieldDescriptorProto $field)
    {
        $name       = $field->getName();
        $label      = $field->getLabel();
        $tag        = $field->getNumber();
        $isRequired = $label === Label::LABEL_REQUIRED();

        if ( ! $isRequired) {
            return [];
        }

        $class   = $entity->getNamespacedName();
        $format  = 'Field "%s#%s" (tag %s) is required but has no value.';
        $message = var_export(sprintf($format, $class, $name, $tag), true);

        $body[] = 'if ($this->' . $name . ' === null) {';
        $body[] = '    throw new \UnexpectedValueException(' . $message . ');';
        $body[] = '}';
        $body[] = null;

        return $body;
    }

    /**
     * @param \Protobuf\Compiler\Entity             $entity
     * @param \google\protobuf\FieldDescriptorProto $field
     *
     * @return string[]
     */
    protected function generateFieldCondition(Entity $entity, FieldDescriptorProto $field)
    {
        $body      = [];
        $fieldName = $field->getName();
        $format    = 'if ($this->%s !== null) {';
        $sttm      = $this->generateFieldWriteStatement($entity, $field);
        $lines     = $this->addIndentation($sttm, 1);

        $body[] = sprintf($format, $fieldName);
        $body   = array_merge($body, $lines);
        $body[] = '}';

        return $body;
    }

    /**
     * @param \Protobuf\Compiler\Entity            $entity
     * @param google\protobuf\FieldDescriptorProto $field
     *
     * @return string[]
     */
    protected function generateFieldWriteStatement(Entity $entity, FieldDescriptorProto $field)
    {
        $generator = new WriteFieldStatementGenerator($this->context);
        $statement = $generator->generateFieldWriteStatement($entity, $field);

        return $statement;
    }
}
