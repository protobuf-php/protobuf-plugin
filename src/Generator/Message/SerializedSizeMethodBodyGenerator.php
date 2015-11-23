<?php

namespace Protobuf\Compiler\Generator\Message;

use Protobuf\Message;
use Protobuf\WireFormat;
use Protobuf\Configuration;
use Protobuf\Compiler\Options;
use Protobuf\Binary\SizeCalculator;
use Protobuf\Compiler\Generator\BaseGenerator;

use google\protobuf\DescriptorProto;
use google\protobuf\FieldDescriptorProto;
use google\protobuf\FieldDescriptorProto\Type;
use google\protobuf\FieldDescriptorProto\Label;

/**
 * Message serializedSize Body Generator
 *
 * @author Fabio B. Silva <fabio.bat.silva@gmail.com>
 */
class SerializedSizeMethodBodyGenerator extends BaseGenerator
{
    /**
     * @return string[]
     */
    public function generateBody()
    {
        $body[] = '$calculator = $context->getSizeCalculator();';
        $body[] = '$size       = 0;';

        $body[] = null;

        foreach (($this->proto->getFieldList() ?: []) as $field) {
            $lines = $this->generateFieldCondition($field);
            $body  = array_merge($body, $lines, [null]);
        }

        // $body   = array_merge($body, $this->generateExtensionsSerializedSize());
        // $body[] = null;
        $body[] = 'return $size;';

        return $body;
    }

    /**
     * @return string[]
     */
    public function generateExtensionsSerializedSize()
    {
        $extensionsField = $this->getUniqueFieldName($this->proto, 'extensions');
        $extensionsVar   = '$this->' . $extensionsField;

        $body[] = 'if (' . $extensionsVar . ' !== null) {';
        $body[] = '    $size += ' . $extensionsVar . '->serializedSize($context);';
        $body[] = '}';

        return $body;
    }

    /**
     * @param \google\protobuf\FieldDescriptorProto $field
     *
     * @return string[]
     */
    public function generateFieldCondition(FieldDescriptorProto $field)
    {
        $sttm  = $this->generateFieldSizeStatement($field);
        $lines = $this->addIndentation($sttm, 1);
        $name  = $field->getName();

        $body[] = 'if ($this->' . $name . ' !== null) {';
        $body   = array_merge($body, $lines);
        $body[] = '}';

        return $body;
    }

    /**
     * @param \google\protobuf\FieldDescriptorProto $field
     *
     * @return string[]
     */
    public function generateFieldSizeStatement(FieldDescriptorProto $field)
    {
        $generator = new SerializedSizeFieldStatementGenerator($this->proto, $this->options, $this->package);
        $statement = $generator->generateFieldSizeStatement($field);

        return $statement;
    }
}
