<?php

namespace Protobuf\Compiler\Generator\Message;

use google\protobuf\DescriptorProto;
use google\protobuf\FieldDescriptorProto;

use Zend\Code\Generator\MethodGenerator;
use Zend\Code\Generator\GeneratorInterface;

use Protobuf\Compiler\Entity;
use Protobuf\Compiler\Generator\BaseGenerator;
use Protobuf\Compiler\Generator\GeneratorVisitor;

/**
 * Message serializedSize Body Generator
 *
 * @author Fabio B. Silva <fabio.bat.silva@gmail.com>
 */
class SerializedSizeGenerator extends BaseGenerator implements GeneratorVisitor
{
    /**
     * {@inheritdoc}
     */
    public function visit(Entity $entity, GeneratorInterface $class)
    {
        $class->addMethodFromGenerator($this->generateSerializedSizeMethod($entity));
    }

    /**
     * @param \Protobuf\Compiler\Entity $entity
     *
     * @return string
     */
    protected function generateSerializedSizeMethod(Entity $entity)
    {
        $lines   = $this->generateBody($entity);
        $body    = implode(PHP_EOL, $lines);
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
     * @return string[]
     */
    protected function generateBody(Entity $entity)
    {
        $descriptor = $entity->getDescriptor();
        $fields     = $descriptor->getFieldList() ?: [];
        $extLines   = $this->generateExtensionsSerializedSize($entity);

        $body[] = '$calculator = $context->getSizeCalculator();';
        $body[] = '$size       = 0;';
        $body[] = null;

        foreach ($fields as $field) {
            $lines = $this->generateFieldCondition($entity, $field);
            $body  = array_merge($body, $lines, [null]);
        }

        $body   = array_merge($body, $extLines);
        $body[] = null;
        $body[] = 'return $size;';

        return $body;
    }

    /**
     * @param \Protobuf\Compiler\Entity $entity
     *
     * @return string[]
     */
    protected function generateExtensionsSerializedSize(Entity $entity)
    {
        $descriptor      = $entity->getDescriptor();
        $extensionsField = $this->getUniqueFieldName($descriptor, 'extensions');

        $body[] = 'if ($this->' . $extensionsField . ' !== null) {';
        $body[] = '    $size += $this->' . $extensionsField . '->serializedSize($context);';
        $body[] = '}';

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
        $sttm  = $this->generateFieldSizeStatement($entity, $field);
        $lines = $this->addIndentation($sttm, 1);
        $name  = $field->getName();

        $body[] = 'if ($this->' . $name . ' !== null) {';
        $body   = array_merge($body, $lines);
        $body[] = '}';

        return $body;
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
        $statement = $generator->generateFieldSizeStatement($entity, $field);

        return $statement;
    }
}
