<?php

namespace Protobuf\Compiler\Generator\Message;

use Protobuf\Compiler\Entity;
use Protobuf\Compiler\Generator\BaseGenerator;

use google\protobuf\DescriptorProto;
use google\protobuf\FieldDescriptorProto;

/**
 * Message serializedSize Body Generator
 *
 * @author Fabio B. Silva <fabio.bat.silva@gmail.com>
 */
class SerializedSizeMethodBodyGenerator extends BaseGenerator
{
    /**
     * @param \Protobuf\Compiler\Entity $entity
     *
     * @return string[]
     */
    public function generateBody(Entity $entity)
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
    public function generateExtensionsSerializedSize(Entity $entity)
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
    public function generateFieldCondition(Entity $entity, FieldDescriptorProto $field)
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
    public function generateFieldSizeStatement(Entity $entity, FieldDescriptorProto $field)
    {
        $generator = new SerializedSizeFieldStatementGenerator($this->context);
        $statement = $generator->generateFieldSizeStatement($entity, $field);

        return $statement;
    }
}
